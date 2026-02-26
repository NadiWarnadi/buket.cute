<?php

namespace App\Http\Controllers\Admin;

use App\Models\Message;
use App\Models\Customer;
use App\Models\OutgoingMessage;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ChatController extends Controller
{
    /**
     * Show chat list - all customers with messages
     */
    public function index()
    {
        // Get customers dengan messages, ordered by latest message
        // PENTING: Hanya show customers yang punya incoming messages (dari WhatsApp/Chat)
        $customers = Customer::whereHas('messages', function ($q) {
                $q->where('is_incoming', true); // Only show customers with incoming messages
            })
            ->with(['messages' => function ($q) {
                $q->where('is_incoming', true)->latest()->limit(1);
            }])
            ->orderByDesc(function ($q) {
                $q->selectRaw('MAX(created_at)')
                    ->from('messages')
                    ->where('is_incoming', true)
                    ->whereColumn('customer_id', 'customers.id');
            })
            ->paginate(15);

        return view('admin.chat.index', compact('customers'));
    }

    /**
     * Show chat with specific customer
     */
    public function show(Customer $customer)
    {
        // Ensure customer is properly loaded
        $customer->load(['orders', 'messages']);
        
        // Get all messages (both incoming and outgoing) for this customer
        $messages = Message::where('customer_id', $customer->id)
            ->with('order')
            ->orderBy('created_at', 'asc')
            ->paginate(30);

        // Mark incoming messages as read
        Message::where('customer_id', $customer->id)
            ->where('is_incoming', true)
            ->where('status', '!=', 'read')
            ->update(['status' => 'read']);

        logger()->info("Loading chat for customer {$customer->id}: Found {$messages->count()} messages");

        return view('admin.chat.show', compact('customer', 'messages'));
    }

    /**
     * Send reply message
     */
    public function sendReply(Request $request, Customer $customer)
    {
        try {
            \Log::info("ChatController sendReply called", [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'is_json' => $request->expectsJson(),
            ]);

            $validated = $request->validate([
                'message' => 'required|string|max:1000',
                'to_whatsapp' => 'nullable|boolean',
            ]);

            // Store message as outgoing in messages table (untuk chat history)
            $savedMsg = Message::create([
                'customer_id' => $customer->id,
                'from' => 'admin',
                'to' => $customer->phone,
                'body' => $validated['message'],
                'type' => 'text',
                'is_incoming' => false,
                'status' => 'pending',
                'parsed' => true,
            ]);

            \Log::info("Message saved to database", ['message_id' => $savedMsg->id]);

            // If send via WhatsApp is checked, create OutgoingMessage dan job
            $responseMessage = 'Pesan disimpan';
            if ($validated['to_whatsapp']) {
                $msg = OutgoingMessage::create([
                    'customer_id' => $customer->id,
                    'to' => $customer->getWhatsAppNumber(),
                    'body' => $validated['message'],
                    'type' => OutgoingMessage::TYPE_TEXT,
                    'status' => OutgoingMessage::STATUS_PENDING,
                ]);

                // Dispatch job untuk send ke WhatsApp
                \App\Jobs\SendWhatsAppNotification::dispatch($msg);
                $responseMessage = 'Pesan dikirim ke WhatsApp';
                \Log::info("Job dispatched", ['outgoing_id' => $msg->id]);
            }

            // Jika AJAX request, return JSON
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => $responseMessage,
                ], 201);
            }

            // Jika form biasa, redirect dengan session
            return back()->with('success', $responseMessage);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error', ['errors' => $e->errors()]);

            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error: ' . json_encode($e->errors()),
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error sending message', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Get chat statistics
     */
    public function getStats()
    {
        $totalChats = Customer::whereHas('messages')->count();
        $unreadMessages = Message::where('is_incoming', true)
            ->where('status', '!=', 'read')
            ->count();
        $pendingOutgoing = OutgoingMessage::where('status', 'pending')->count();

        return response()->json([
            'total_chats' => $totalChats,
            'unread_messages' => $unreadMessages,
            'pending_outgoing' => $pendingOutgoing,
        ]);
    }
}
