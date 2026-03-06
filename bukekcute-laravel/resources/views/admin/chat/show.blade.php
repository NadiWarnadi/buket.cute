@extends('layouts.admin')

@section('title', 'Chat - ' . $customer->name)

@section('content')
<style>
    .chat-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 200px);
        background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
    }

    .messages-area {
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        padding: 16px;
    }

    .message-group {
        display: flex;
        margin-bottom: 8px;
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message-group.incoming {
        justify-content: flex-start;
    }

    .message-group.outgoing {
        justify-content: flex-end;
    }

    .message-bubble {
        max-width: 60%;
        border-radius: 18px;
        padding: 8px 12px;
        word-wrap: break-word;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .message-bubble.incoming {
        background-color: #fff;
        color: #000;
        border: 1px solid #d0d0d0;
    }

    .message-bubble.outgoing {
        background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
        color: #fff;
    }

    .message-time {
        font-size: 12px;
        margin-top: 4px;
        opacity: 0.7;
    }

    .message-bubble.incoming .message-time {
        color: #666;
    }

    .message-bubble.outgoing .message-time {
        color: rgba(255,255,255,0.8);
    }

    .message-sender {
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 4px;
        opacity: 0.8;
    }

    .message-bubble.incoming .message-sender {
        color: #128c7e;
    }

    .message-bubble.outgoing .message-sender {
        display: none;
    }

    .date-divider {
        text-align: center;
        margin: 12px 0;
        position: relative;
    }

    .date-divider span {
        background: #fff;
        padding: 0 12px;
        color: #888;
        font-size: 12px;
        display: inline-block;
    }

    .date-divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #d0d0d0;
    }

    .chat-header {
        background: linear-gradient(135deg, #128c7e 0%, #075e54 100%);
        color: white;
        padding: 16px;
        border-radius: 12px 12px 0 0;
    }

    .chat-input-area {
        border-top: 1px solid #d0d0d0;
        padding: 12px;
        background: white;
    }

    /* Media styling */
    .message-media {
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 4px;
    }

    .message-media img,
    .message-media video {
        max-width: 100%;
        display: block;
    }

    .message-document {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(0,0,0,0.05);
        padding: 8px 12px;
        border-radius: 12px;
    }

    .message-bubble.incoming .message-document {
        background: #f0f0f0;
    }

    .message-bubble.outgoing .message-document {
        background: rgba(255,255,255,0.2);
    }

    /* Scrollbar styling */
    .messages-area::-webkit-scrollbar {
        width: 6px;
    }

    .messages-area::-webkit-scrollbar-track {
        background: transparent;
    }

    .messages-area::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }

    .messages-area::-webkit-scrollbar-thumb:hover {
        background: #999;
    }

    .debug-info {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 12px;
        margin-bottom: 12px;
        font-size: 12px;
    }
</style>

<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('admin.chat.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-person"></i> {{ $customer->name ?? 'Unknown' }}</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Telepon</h6>
                    <p class="mb-0">
                        {{ $customer->phone ? 'wa.me/' . $customer->getWhatsAppNumber() : 'Tidak ada' }}
                    </p>
                </div>

           
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Alamat</h6>
                    <p class="mb-0 small">{{ $customer->address ?? '-' }}</p>
                </div>

                <hr>

                <div class="mb-3">
                    <h6 class="text-muted mb-2">Total Pesanan</h6>
                    <h4>{{ $customer->orders?->count() ?? 0 }}</h4>
                </div>

                @if($customer->id)
                    <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-sm btn-outline-info w-100">
                        <i class="bi bi-eye"></i> Lihat Detail
                    </a>
                @endif
            </div>
        </div>
<!--  ini buat sambungan ke wa 
         -->

        {{-- Order Session Panel --}}
        @php
            $orderSession = \App\Models\OrderSession::where('customer_id', $customer->id)
                ->where('status', 'active')
                ->first();
        @endphp

        @if($orderSession)
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-clipboard-check"></i> DRAFT PESANAN</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Progress</h6>
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: {{ $orderSession->getCompletionPercentage() }}%"
                                 aria-valuenow="{{ $orderSession->getCompletionPercentage() }}" 
                                 aria-valuemin="0" aria-valuemax="100">
                                {{ $orderSession->getCompletionPercentage() }}%
                            </div>
                        </div>
                    </div>

                    {{-- Requirements Checklist --}}
                    <div class="mb-3">
                        <h6 class="text-muted mb-2"><i class="bi bi-list-check"></i> Syarat Minimal</h6>
                        <div class="small">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" disabled 
                                    @if($orderSession->customer_name) checked @endif>
                                <label class="form-check-label">
                                    Nama
                                    @if($orderSession->customer_name)
                                        <span class="text-success">✓</span>
                                    @else
                                        <span class="text-danger">✗</span>
                                    @endif
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" disabled 
                                    @if($orderSession->customer_address) checked @endif>
                                <label class="form-check-label">
                                    Alamat
                                    @if($orderSession->customer_address)
                                        <span class="text-success">✓</span>
                                    @else
                                        <span class="text-danger">✗</span>
                                    @endif
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" disabled 
                                    @if($orderSession->product_description) checked @endif>
                                <label class="form-check-label">
                                    Deskripsi Produk
                                    @if($orderSession->product_description)
                                        <span class="text-success">✓</span>
                                    @else
                                        <span class="text-danger">✗</span>
                                    @endif
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" disabled 
                                    @if($orderSession->delivery_type) checked @endif>
                                <label class="form-check-label">
                                    Pengiriman
                                    @if($orderSession->delivery_type)
                                        <span class="text-success">✓</span>
                                    @else
                                        <span class="text-danger">✗</span>
                                    @endif
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Data Yang Dikumpulkan --}}
                    <div class="mb-3">
                        <h6 class="text-muted mb-2"><i class="bi bi-info-circle"></i> Data Pesanan</h6>
                        <div class="small">
                            @if($orderSession->customer_name)
                                <div class="mb-2">
                                    <strong>📝 Nama:</strong> {{ $orderSession->customer_name }}
                                </div>
                            @endif
                            @if($orderSession->customer_address)
                                <div class="mb-2">
                                    <strong>📍 Alamat:</strong> <br>
                                    <span class="small">{{ Str::limit($orderSession->customer_address, 50) }}</span>
                                </div>
                            @endif
                            @if($orderSession->product_description)
                                <div class="mb-2">
                                    <strong>💐 Produk:</strong><br>
                                    <span class="small">{{ Str::limit($orderSession->product_description, 50) }}</span>
                                </div>
                            @endif
                            @if($orderSession->delivery_type)
                                <div class="mb-2">
                                    <strong>🚚 Pengiriman:</strong> 
                                    {{ $orderSession->delivery_type === 'delivery' ? 'Dikirim' : 'Ambil di Toko' }}
                                </div>
                            @endif
                            @if($orderSession->reference_image_url)
                                <div class="mb-2">
                                    <strong>📸 Referensi:</strong> Ada
                                </div>
                            @endif
                            @if($orderSession->greeting_note)
                                <div class="mb-2">
                                    <strong>📝 Kartu Ucapan:</strong><br>
                                    <span class="small">{{ Str::limit($orderSession->greeting_note, 50) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Tombol Konfirmasi --}}
                    @if($orderSession->isComplete())
                        <form action="{{ route('admin.orders.confirm-from-chat', [$customer->id, $orderSession->id]) }}" 
                              method="POST" class="d-grid gap-2">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-check-circle"></i> KONFIRMASI PESANAN
                            </button>
                        </form>
                        <small class="text-success d-block mt-2">
                            <i class="bi bi-check2"></i> Semua data terpenuhi!
                        </small>
                    @else
                        @php $missing = $orderSession->getMissingFields(); @endphp
                        <button class="btn btn-secondary btn-sm w-100" disabled>
                            <i class="bi bi-lock"></i> Konfirmasi Pesanan
                        </button>
                        <small class="text-danger d-block mt-2">
                            <i class="bi bi-exclamation-circle"></i> 
                            Lengkapi: {{ implode(', ', $missing) }}
                        </small>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="col-12 col-lg-8 mb-4">
        <div class="card border-0 shadow-sm" style="display: flex; flex-direction: column; height: calc(100vh - 200px);">
            <!-- Chat Header -->
            <div class="chat-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">{{ $customer->name }}</h5>
                    <small>{{ $customer->formatted_phone ?? $customer->phone }}</small>
                </div>
                <div>
                    <a href="https://wa.me/{{ $customer->getWhatsAppNumber() }}" target="_blank" class="btn btn-sm btn-light">
                        <i class="bi bi-whatsapp"></i> Chat
                    </a>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="messages-area" id="messagesArea">
                @if($messages->count())
                    @php
                        $lastDate = null;
                    @endphp
                    @foreach($messages as $msg)
                        @php
                            $msgDate = $msg->created_at->format('Y-m-d');
                            if ($lastDate !== $msgDate) {
                                $lastDate = $msgDate;
                                $showDateDivider = true;
                            } else {
                                $showDateDivider = false;
                            }
                        @endphp

                        @if($showDateDivider)
                            <div class="date-divider">
                                <span>{{ $msg->created_at->format('d M Y') }}</span>
                            </div>
                        @endif

                        <div class="message-group @if($msg->is_incoming) incoming @else outgoing @endif">
                            <div class="message-bubble @if($msg->is_incoming) incoming @else outgoing @endif">
                                @if(!$msg->is_incoming)
                                    <div class="message-sender">
                                        <i class="bi bi-person-fill"></i> Admin
                                    </div>
                                @endif

                                {{-- Media Messages --}}
                                @if($msg->media_url)
                                    @if($msg->media_type === 'image')
                                        <div class="message-media">
                                            <img src="{{ $msg->media_url }}" alt="Gambar" style="max-height: 300px; width: 100%; object-fit: cover;">
                                        </div>
                                        @if($msg->caption)
                                            <p class="mb-1">{{ $msg->caption }}</p>
                                        @endif

                                    @elseif($msg->media_type === 'video')
                                        <div class="message-media">
                                            <video controls style="max-height: 300px; width: 100%;">
                                                <source src="{{ $msg->media_url }}" type="{{ $msg->mime_type ?? 'video/mp4' }}">
                                            </video>
                                        </div>
                                        @if($msg->caption)
                                            <p class="mb-1">{{ $msg->caption }}</p>
                                        @endif

                                    @elseif($msg->media_type === 'audio')
                                        <div class="message-document">
                                            <i class="bi bi-file-earmark-music" style="font-size: 20px;"></i>
                                            <audio controls style="margin: 0;">
                                                <source src="{{ $msg->media_url }}" type="{{ $msg->mime_type ?? 'audio/mpeg' }}">
                                            </audio>
                                        </div>

                                    @elseif($msg->media_type === 'sticker')
                                        <div class="message-media">
                                            <img src="{{ $msg->media_url }}" alt="Stiker" style="max-height: 120px; width: auto;">
                                        </div>

                                    @elseif($msg->media_type === 'document')
                                        <div class="message-document">
                                            <i class="bi bi-file-earmark-pdf" style="font-size: 20px;"></i>
                                            <div>
                                                <div class="small font-weight-bold">{{ $msg->caption ?: 'Dokumen' }}</div>
                                                @if($msg->media_size)
                                                    <small>{{ number_format($msg->media_size / 1024, 1) }} KB</small>
                                                @endif
                                            </div>
                                        </div>
                                        <a href="{{ $msg->media_url }}" class="btn btn-xs btn-link" download title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    @else
                                        <div class="message-document">
                                            <i class="bi bi-file-earmark" style="font-size: 20px;"></i>
                                            <span>{{ $msg->caption ?: 'File' }}</span>
                                        </div>
                                    @endif
                                @else
                                    {{-- Text Messages --}}
                                    <p class="mb-0" style="word-break: break-word;">{{ $msg->body }}</p>
                                @endif

                                <div class="message-time">{{ $msg->created_at->format('H:i') }}</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                        <div class="text-center text-muted">
                            <i class="bi bi-chat-dots" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="mt-3">Belum ada pesan</p>
                        </div>
                    </div>
                @endif
            </div>

            @if($messages->hasPages())
            <div style="text-align: center; padding: 12px; background: #f5f5f5; border-top: 1px solid #d0d0d0;">
                {{ $messages->links() }}
            </div>
            @endif

            <!-- Input Area -->
            <div class="chat-input-area">
                <div id="alertContainer"></div>

                @if($customer->id)
                    <form id="chatForm" action="{{ route('admin.chat.send', $customer->id) }}" method="POST">
                        @csrf

                        <div class="input-group mb-2">
                            <textarea 
                                class="form-control" 
                                id="message" 
                                name="message" 
                                rows="2" 
                                placeholder="Ketik pesan..." 
                                required 
                                style="resize: none; border-radius: 18px 0 0 18px;">
                            </textarea>
                            <button class="btn btn-success" type="submit" id="sendBtn" style="border-radius: 0 18px 18px 0;">
                                <i class="bi bi-send"></i> Kirim
                            </button>
                        </div>

                        <small class="text-muted d-block"><span id="charCount">0</span>/1000</small>

                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="toWhatsapp" name="to_whatsapp" value="1" checked>
                            <label class="form-check-label" for="toWhatsapp">
                                <i class="bi bi-whatsapp"></i> Kirim ke WhatsApp
                            </label>
                        </div>
                    </form>
                @else
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Opps! Pelanggan tidak ditemukan</strong><br>
                        Silakan kembali ke daftar chat.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
// Initialize
console.log('Chat form script initialized');

// Tunggu DOM fully loaded sebelum inisialisasi
document.addEventListener('DOMContentLoaded', function() {
    // Character counter
    var messageInput = document.getElementById('message');
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            var charCount = document.getElementById('charCount');
            if (charCount) {
                charCount.textContent = this.value.length;
            }
        });
    }

    // Auto-scroll to bottom on page load
    var messagesArea = document.getElementById('messagesArea');
    if (messagesArea) {
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    // Handle form submission with AJAX
    var chatForm = document.getElementById('chatForm');
    if (chatForm) {
        console.log('Chat form found, attaching submit handler');
        chatForm.addEventListener('submit', handleFormSubmit);
    } else {
        console.warn('Chat form not found!');
    }
});

async function handleFormSubmit(e) {
    e.preventDefault();
    console.log('✓ Form submitted!');

    var messageInput = document.getElementById('message');
    var sendBtn = document.getElementById('sendBtn');
    var alertContainer = document.getElementById('alertContainer');
    var messageText = messageInput.value.trim();

    if (!messageText) {
        showAlert('❌ Pesan tidak boleh kosong!', 'warning');
        return;
    }

    // Disable button
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengirim...';

    try {
        var token = document.querySelector('input[name="_token"]');
        if (!token || !token.value) {
            throw new Error('CSRF token not found');
        }

        var chatForm = document.getElementById('chatForm');
        var toWhatsappCheckbox = document.getElementById('toWhatsapp');
        var toWhatsapp = toWhatsappCheckbox && toWhatsappCheckbox.checked ? 1 : 0;

        var formData = new FormData();
        formData.append('_token', token.value);
        formData.append('message', messageText);
        formData.append('to_whatsapp', toWhatsapp);

        console.log('→ Sending to:', chatForm.action);
        console.log('→ Message:', messageText);
        console.log('→ To WhatsApp:', toWhatsapp);

        var response = await fetch(chatForm.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData
        });

        console.log('← Response status:', response.status);

        var data = await response.json();
        console.log('← Response data:', data);

        if (response.ok) {
            messageInput.value = '';
            var charCount = document.getElementById('charCount');
            if (charCount) charCount.textContent = '0';
            showAlert('✅ Pesan berhasil dikirim!', 'success');
            
            // Reload halaman untuk tampil pesan baru
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('❌ ' + (data.message || 'Gagal mengirim pesan'), 'danger');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        showAlert('❌ Error: ' + error.message, 'danger');
    } finally {
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="bi bi-send"></i> Kirim';
    }
}

function showAlert(message, type) {
    var alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        console.warn('Alert container not found');
        return;
    }

    var alertClass = type ? 'alert-' + type : 'alert-info';
    var alertHTML = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
        message +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>';
    
    alertContainer.innerHTML = alertHTML;

    // Auto dismiss after 5 seconds
    setTimeout(() => {
        var alert = alertContainer.querySelector('.alert');
        if (alert) {
            alert.style.display = 'none';
            setTimeout(() => alert.remove(), 300);
        }
    }, 5000);
}
</script>
@endsection
