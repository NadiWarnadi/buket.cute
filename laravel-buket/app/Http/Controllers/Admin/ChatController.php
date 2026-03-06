<?php

namespace App\Http\Controllers\Admin;

use App\Models\Message;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Display a listing of conversations (grouped by customer)
     */
    public function index(Request $request)
    {
        // Get customers with latest message
        $query = Customer::with(['messages' => fn($q) => $q->latest()->limit(1)])
                        ->whereHas('messages');

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
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
        try {
            $validated = $request->validate([
                'body' => 'required|string|max:1000',
                'type' => 'nullable|string|in:text,image,document',
                'media' => 'nullable|file|max:25600',
            ]);

            $messageType = $validated['type'] ?? 'text';

            // Send via WhatsApp
            $waResponse = $this->sendMessageViaWhatsApp(
                $customer->phone,
                $validated['body'],
                $messageType
            );

            if (!$waResponse['success']) {
                return redirect()->route('admin.chat.show', $customer)
                                ->with('error', 'Gagal mengirim: ' . $waResponse['message']);
            }

            // Simpan message
            Message::create([
                'customer_id' => $customer->id,
                'message_id' => $waResponse['message_id'] ?? 'msg_' . time(),
                'from' => 'admin',
                'to' => $customer->phone,
                'body' => $validated['body'],
                'type' => $messageType,
                'is_incoming' => false,
                'status' => 'sent',
                'chat_status' => 'active',
            ]);

            Log::channel('whatsapp')->info('Message sent', ['to' => $customer->phone]);

            return redirect()->route('admin.chat.show', $customer)
                            ->with('success', 'Pesan berhasil dikirim.');

        } catch (\Exception $e) {
            Log::error('Error sending message', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Mark message as read
     */
    public function markMessageAsRead(Request $request, Message $message)
    {
        try {
            if ($message->is_incoming === false) {
                return response()->json(['success' => false, 'message' => 'Only incoming messages'], 400);
            }

            $waResponse = $this->sendReadReceiptToWhatsApp($message);

            if (!$waResponse['success']) {
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
     * Send message via WhatsApp service
     * Calls the wa-service API
     */
    private function sendMessageViaWhatsApp(string $phone, string $content, string $type = 'text'): array
    {
        try {
            $waServiceUrl = env('WHATSAPP_SERVICE_URL', 'http://localhost:3000');
            $apiKey = env('WHATSAPP_API_KEY');

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$waServiceUrl}/api/send-{$type}", [
                'to' => $phone,
                'text' => $content, // For text messages
                'content' => $content, // For generic use
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('message_id'),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('error') ?? 'Unknown error from WhatsApp service',
            ];

        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error calling WhatsApp service', [
                'phone' => $phone,
                'error' => $e->getMessage(),
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

