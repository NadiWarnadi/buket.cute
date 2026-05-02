<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Media;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * Display a listing of conversations (grouped by customer)
     */
    public function index(Request $request)
    {
        // Get customers with latest message
        $query = Customer::with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->whereHas('messages');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderByDesc('created_at')->paginate(15);

        return view('admin.chat.index', compact('customers'));
    }

    /**
     * Show customer conversation (all their messages)
     */
    public function show(Customer $customer)
    {
        $messages = $customer->messages()->orderBy('created_at', 'asc')->paginate(50);
        $lastMessage = $customer->messages()->latest()->first();

        return view('admin.chat.show', compact('customer', 'messages', 'lastMessage'));
    }

    /**
     * Send reply message to customer
     */
    public function sendReply(Request $request, Customer $customer)
    {   
    
         Log::debug('ChatController sendReply request', $request->all());
        try {
            $validated = $request->validate([
                'body' => 'nullable|string|max:1000',
                'type' => 'nullable|string|in:text,image,document',
                'media' => 'nullable|file|max:25600',
            ]);

            $messageBody = $request->body ? trim($request->body) : '';
        
        // 2. Cek apakah ada file
        $hasFile = $request->hasFile('media') && $request->file('media')->isValid();

        // 3. Validasi: Hanya error jika dua-duanya (teks DAN file) kosong
        if (empty($messageBody) && !$hasFile) {
            return redirect()->back()->with('error', 'Pesan atau file harus diisi.');
        }
            // Trim whitespace from body
//             $messageBody = trim($validated['body']);
//             if ($messageBody === '' && !$request->hasFile('media')) {
//     return redirect()->back()->with('error', 'Pesan atau file harus diisi.');
// }
//             if (empty($messageBody)) {
//                 return redirect()->route('admin.chat.show', $customer)
//                     ->with('error', 'Pesan tidak boleh kosong!');
//             }

            $messageType = $validated['type'] ?? 'text';
            $mediaPath = null;
            $mediaUrl = null;
            $fileName = null;

            // Handle media upload
           if ($hasFile) {
            $file = $request->file('media');
            $fileName = $file->getClientOriginalName();
            
            // Simpan ke storage/app/public/messages/...
            $path = $file->store("messages/{$customer->id}", 'public');
            $mediaPath = $path;
            $mediaUrl = asset("storage/{$path}");

                Log::channel('whatsapp')->info('Media file stored', [
                'path' => $path,
                'customer_id' => $customer->id,
            ]);
            }   

            Log::debug('About to call sendMessageViaWhatsApp', [
    'phone' => $customer->phone,
    'body' => $messageBody,
    'type' => $messageType,
    'mediaPath' => $mediaPath,
]);

            // Send via WhatsApp
            $waResponse = $this->sendMessageViaWhatsApp(
                $customer->phone,
                $messageBody,
                $messageType,
                $mediaPath
            );

            if (! $waResponse['success']) {
                return redirect()->route('admin.chat.show', $customer)
                    ->with('error', 'Gagal mengirim: '.$waResponse['message']);
            }
            Log::debug('Response from sendMessageViaWhatsApp', $waResponse);
            // Create and save message
            $message = Message::create([
                'customer_id' => $customer->id,
                'message_id' => $waResponse['message_id'] ?? 'msg_'.time(),
                'from' => 'admin',
                'to' => $customer->phone,
                'body' => $messageBody,
                'type' => $messageType,
                'is_incoming' => false,
                'status' => 'sent',
                'chat_status' => 'active',
                'media_path' => $mediaPath,
                'media_url' => $mediaUrl,
                'file_name' => $fileName,
            ]);

            // Save media record if file was uploaded
            if ($mediaPath && $fileName) {
                Media::create([
                    'message_id' => $message->id,
                    'file_path' => $mediaPath,
                    'file_name' => $fileName,
                    'mime_type' => $request->file('media')->getMimeType(),
                    'size' => $request->file('media')->getSize(),
                    'file_type' => $messageType,
                ]);
            }
                Log::debug('Preparing to send WhatsApp message', [
                    'phone' => $customer->phone,
                    'body' => $messageBody,
                    'type' => $messageType,
                    'mediaPath' => $mediaPath,
                ]);
            Log::channel('whatsapp')->info('Message sent', ['to' => $customer->phone, 'message_id' => $message->id]);

            return redirect()->route('admin.chat.show', $customer)
                ->with('success', 'Pesan berhasil dikirim.');

        } catch (\Exception $e) {
            Log::error('Error sending message', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    /**
     * Mark message as read tapi kaya nya emmm ceklis dua biru di buat ketika admmin buka aja janagan sampai cat bot yang merubah
     */
    public function markMessageAsRead(Request $request, Message $message)
    {
        try {
            if ($message->is_incoming === false) {
                return response()->json(['success' => false, 'message' => 'Only incoming messages'], 400);
            }

            $waResponse = $this->sendReadReceiptToWhatsApp($message);

            if (! $waResponse['success']) {
                return response()->json(['success' => false, 'message' => $waResponse['message']], 500);
            }

            $message->update([
                'status' => 'read',
                'parsed' => true,
                'parsed_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Marked as read']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error'], 500);
        }
    }

    /**
     * Update chat status
     */
    public function updateStatus(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,archived,closed',
        ]);

        $customer->messages()->update(['chat_status' => $validated['status']]);

        return redirect()->route('admin.chat.show', $customer)
            ->with('success', 'Status chat diperbarui.');
    }

    /**
     * Delete conversation (all messages dari customer)
     */
    public function destroy(Customer $customer)
    {
        $customer->messages()->delete();

        return redirect()->route('admin.chat.index')
            ->with('success', 'Chat berhasil dihapus.');
    }

    /**
     * Get stats
     */
    public function getStats()
    {
        $stats = [
            'total_conversations' => Customer::whereHas('messages')->count(),
            'active_chats' => Message::where('chat_status', 'active')->distinct('customer_id')->count('customer_id'),
            'total_messages' => Message::count(),
            'unread_messages' => Message::where('is_incoming', true)->where('status', '!=', 'read')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Toggle delegation between AI and Admin (SIMPLE - cukup 1 boolean)
     */
    public function toggleDelegation(Request $request, Customer $customer)
    {
        try {
            $customer->toggleDelegation();

            $message = $customer->is_admin_handled 
                ? 'Chat diambil alih oleh admin (Bot Off)' 
                : 'Chat kembali ke AI Bot';

            return redirect()->route('admin.chat.show', $customer)
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error toggling delegation', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal toggle delegasi');
        }
    }

    /**
     * Escalate chat ke admin - SIMPLE version
     */
    public function escalateToAdmin(Request $request, Customer $customer)
    {
        try {
            $customer->update(['is_admin_handled' => true]);

            Log::channel('whatsapp')->info('Chat escalated to admin', [
                'customer_id' => $customer->id,
                'escalated_at' => now(),
            ]);

            return redirect()->route('admin.chat.show', $customer)
                ->with('success', 'Chat berhasil di-escalate ke admin handling.');

        } catch (\Exception $e) {
            Log::error('Error escalating chat', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal escalate chat');
        }
    }

    /**
     * Resume chat handling ke AI - SIMPLE version
     */
   public function resumeFromAdmin(Request $request, Customer $customer)
    {
        try {
            $customer->update(['is_admin_handled' => false]);

            Log::channel('whatsapp')->info('Chat resumed to AI', [
                'customer_id' => $customer->id,
                'resumed_at' => now(),
            ]);

            return redirect()->route('admin.chat.show', $customer)
                ->with('success', 'Chat dikembalikan ke AI Bot.');

        } catch (\Exception $e) {
            Log::error('Error resuming to AI', [
                'error' => $e->getMessage(), 
                'customer_id' => $customer->id
            ]);
            
            return redirect()->back()->with('error', 'Gagal resume ke AI: ' . $e->getMessage());
        }
    }
    /**
     * Send message via WhatsApp service
     * Calls the wa-service API
     */
    private function sendMessageViaWhatsApp(string $phone, string $content, string $type = 'text', ?string $mediaPath = null): array
    {
        Log::debug('sendMessageViaWhatsApp entry point', [
            'phone' => $phone,
            'type' => $type,
            'has_media_path' => !empty($mediaPath),
            'service_url' => env('WHATSAPP_SERVICE_URL') // Memastikan URL tujuan sudah benar
        ]);
        try {
            $waServiceUrl = rtrim(env('WHATSAPP_SERVICE_URL', 'http://localhost:3000'), '/');
            $apiKey = env('WHATSAPP_API_KEY');

        // Jika ada file media, gunakan endpoint send-media
        if ($mediaPath && $type !== 'text') {
            $fullPath = storage_path("app/public/{$mediaPath}");

            if (!file_exists($fullPath)) {
                Log::error('Media file benar-benar tidak ada', ['path' => $fullPath]);
                $fullPath = storage_path("app/{$mediaPath}");
                return ['success' => false, 'message' => 'File media tidak ditemukan.'];
            }

            Log::debug('Sending media to Node.js', [
                'url' => "{$waServiceUrl}/api/send-media",
                'to' => $phone,
                'type' => $type,
                'file' => $fullPath,
                'file_exists' => file_exists($fullPath),
                'file_size' => filesize($fullPath),
            ]);

            // Check if file is readable
            if (!is_readable($fullPath)) {
                Log::error('File not readable', ['path' => $fullPath]);
                return ['success' => false, 'message' => 'File tidak bisa dibaca.'];
            }

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
            ])
            ->timeout(120) // Increase timeout
            ->attach(
                'file',                         // nama field (harus 'file')
                fopen($fullPath, 'r'),          // stream file
                basename($fullPath)             // nama file
            )
            ->post("{$waServiceUrl}/api/send-media", [
                'to'      => $phone,
                'caption' => $content ?: '',    // caption boleh kosong
                'type'    => $type,
            ]);
        } else {
            // Kirim teks biasa
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post("{$waServiceUrl}/api/send-text", [
                'to'   => $phone,
                'text' => $content,
            ]);
        }

        if ($response->successful()) {
            return [
                'success'    => true,
                'message_id' => $response->json('message_id') ?? 'msg_' . uniqid(),
            ];
        }

        // Gagal
        Log::error('Node.js returned error', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);
        return [
            'success' => false,
            'message' => $response->json('error') ?? 'Gagal mengirim pesan.',
        ];

    } catch (\Exception $e) {
        Log::error('Exception when calling WhatsApp service', [
            'message' => $e->getMessage(),
        ]);
        return [
            'success' => false,
            'message' => $e->getMessage(),
        ];
    }
}

    /**
     * Send read receipt to WhatsApp service
     * Tells WhatsApp to mark the message as read (blue checkmark)
     */
    private function sendReadReceiptToWhatsApp(Message $message): array
    {
        try {
            $waServiceUrl = env('WHATSAPP_SERVICE_URL', 'http://localhost:3000');
            $apiKey = env('WHATSAPP_API_KEY');

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$waServiceUrl}/api/mark-read", [
                'message_id' => $message->message_id,
                'phone' => $message->from, // The phone that sent the message
            ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            return [
                'success' => false,
                'message' => $response->json('error') ?? 'Failed to send read receipt',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}