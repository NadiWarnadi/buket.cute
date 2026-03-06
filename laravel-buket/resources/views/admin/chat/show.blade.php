@extends('layouts.admin')

@section('title', 'Chat - ' . ($customer->name ?? $customer->phone ?? 'Customer'))

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
        color: rgba(255, 255, 255, 0.8);
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

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <!-- Customer Info Sidebar -->
    <div class="col-12 col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-person"></i> {{ $customer->name ?? $customer->phone }}</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Telepon</h6>
                    <p class="mb-0 small"><strong>{{ $customer->phone ?? 'N/A' }}</strong></p>
                </div>

                @if($customer->email)
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Email</h6>
                        <p class="mb-0 small"><strong>{{ $customer->email }}</strong></p>
                    </div>
                @endif

                @if($customer->address)
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Alamat</h6>
                        <p class="mb-0 small">{{ $customer->address }}</p>
                    </div>
                @endif

                <hr>

                <div class="mb-3">
                    <h6 class="text-muted mb-2">Status Chat</h6>
                    <p class="mb-0">
                        @php
                            $chatStatus = $customer->getChatStatus();
                        @endphp
                        @if($chatStatus === 'active')
                            <span class="badge bg-success">Aktif</span>
                        @elseif($chatStatus === 'archived')
                            <span class="badge bg-warning">Archive</span>
                        @else
                            <span class="badge bg-secondary">Ditutup</span>
                        @endif
                    </p>
                </div>

                <div class="mb-3">
                    <h6 class="text-muted mb-2">Jumlah Pesan</h6>
                    <p class="mb-0"><strong>{{ $customer->messages->count() }}</strong></p>
                </div>

                @if($customer->phone)
                    <a href="{{ route('admin.customers.show-by-phone', $customer->phone) }}" class="btn btn-sm btn-outline-info w-100 mb-3">
                        <i class="bi bi-eye"></i> Lihat Detail Customer
                    </a>
                @else
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 mb-3" disabled>
                        <i class="bi bi-x-circle"></i> Data Customer Belum Ada
                    </button>
                @endif

                @php
                    $chatStatus = $customer->getChatStatus();
                @endphp
                @if($chatStatus === 'active')
                    <form method="POST" action="{{ route('admin.chat.updateStatus', $customer) }}" class="d-grid gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="archived">
                        <button type="submit" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-archive"></i> Archive Chat
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.chat.updateStatus', $customer) }}" class="d-grid gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="active">
                        <button type="submit" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-arrow-counterclockwise"></i> Aktifkan Kembali
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.chat.destroy', $customer->id) }}" 
                      onsubmit="return confirm('Yakin hapus semua pesan dari customer ini? Data tidak bisa dikembalikan.')" class="mt-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                        <i class="bi bi-trash"></i> Hapus Semua Pesan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="col-12 col-lg-8 mb-4">
        <div class="card border-0 shadow-sm" style="display: flex; flex-direction: column; height: calc(100vh - 200px);">
            <!-- Chat Header -->
            <div class="chat-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">{{ $customer->name ?? 'Customer ' . $customer->phone }}</h5>
                    <small>{{ $customer->phone }}</small>
                </div>
                <div>
                    <a href="https://wa.me/{{ $customer->phone }}" target="_blank" class="btn btn-sm btn-light">
                        <i class="bi bi-whatsapp"></i> Chat di WA
                    </a>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="messages-area" id="messagesArea">
                @if($customer->messages->count())
                    @php $lastDate = null; @endphp
                    @foreach($customer->messages as $msg)
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
                                <div class="message-sender">
                                    @if($msg->is_incoming)
                                        <i class="bi bi-person-circle"></i> {{ $customer->name ?? 'Customer' }}
                                    @else
                                        <i class="bi bi-person-fill"></i> Admin
                                    @endif
                                </div>
                                <p class="mb-0" style="word-break: break-word;">{{ $msg->body }}</p>
                                <div class="message-time">
                                    {{ $msg->created_at->format('H:i') }}
                                    @if(!$msg->is_incoming && $msg->status)
                                        @if($msg->status === 'read')
                                            <i class="bi bi-check2-all ms-1" title="Dibaca"></i>
                                        @elseif($msg->status === 'sent')
                                            <i class="bi bi-check2 ms-1" title="Terkirim"></i>
                                        @endif
                                    @endif
                                </div>
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

            <!-- Input Area -->
            <div class="chat-input-area">
                <div id="alertContainer"></div>

                @php
                    $chatStatus = $customer->getChatStatus();
                @endphp
                @if($chatStatus === 'active' && $customer)
                    <form id="chatForm" action="{{ route('admin.chat.send', $customer) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="input-group mb-2">
                            <textarea 
                                class="form-control @error('body') is-invalid @enderror" 
                                id="messageBody" 
                                name="body" 
                                rows="2" 
                                placeholder="Ketik pesan..." 
                                maxlength="1000"
                                style="resize: none; border-radius: 18px 0 0 18px;">
                            </textarea>
                            <button class="btn btn-success" type="submit" id="sendBtn" style="border-radius: 0 18px 18px 0;">
                                <i class="bi bi-send"></i> Kirim
                            </button>
                        </div>

                        <small class="text-muted d-block"><span id="charCount">0</span>/1000</small>

                        @error('body')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        <!-- Media Upload Section -->
                        <div class="mt-2 pt-2 border-top">
                            <input type="hidden" name="type" id="messageType" value="text">
                            <input type="file" id="mediaInput" name="media" class="d-none" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx">
                            
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="mediaBtn">
                                <i class="bi bi-paperclip"></i> Lampir Berkas (Max 25MB)
                            </button>
                            
                            <small class="text-muted d-block mt-1" id="fileInfo"></small>
                            
                            @error('media')
                                <div class="alert alert-danger alert-sm mt-2 mb-0">
                                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </form>
                @else
                    <div class="alert alert-warning alert-dismissible mb-0">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Chat {{ $chatStatus === 'archived' ? 'diarsipkan' : 'ditutup' }}</strong><br>
                        Tidak dapat mengirim pesan pada chat yang {{ $chatStatus === 'archived' ? 'diarsipkan' : 'ditutup' }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
const MAX_FILE_SIZE = 25 * 1024 * 1024; // 25MB in bytes

document.addEventListener('DOMContentLoaded', function() {
    // Character counter
    var messageInput = document.getElementById('messageBody');
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            var charCount = document.getElementById('charCount');
            if (charCount) {
                charCount.textContent = this.value.length;
            }
        });
    }

    // Auto-scroll to bottom
    var messagesArea = document.getElementById('messagesArea');
    if (messagesArea) {
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    // Media upload button handler
    var mediaBtn = document.getElementById('mediaBtn');
    var mediaInput = document.getElementById('mediaInput');
    var messageTypeInput = document.getElementById('messageType');
    var fileInfo = document.getElementById('fileInfo');
    
    if (mediaBtn && mediaInput) {
        mediaBtn.addEventListener('click', function() {
            mediaInput.click();
        });

        mediaInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                var file = this.files[0];
                var fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);

                // Check file size
                if (file.size > MAX_FILE_SIZE) {
                    fileInfo.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> File terlalu besar (${fileSizeMB}MB > 25MB). Silakan pilih file lain.</span>`;
                    mediaInput.value = '';
                    return;
                }

                // Update message type based on file
                if (file.type.startsWith('image/')) {
                    messageTypeInput.value = 'image';
                } else if (file.type.startsWith('video/')) {
                    messageTypeInput.value = 'video';
                } else {
                    messageTypeInput.value = 'document';
                }

                fileInfo.innerHTML = `<span class="text-success"><i class="bi bi-check-circle"></i> File dipilih: ${file.name} (${fileSizeMB}MB)</span>`;
            }
        });
    }

    // Form submission
    var chatForm = document.getElementById('chatForm');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            var messageText = document.getElementById('messageBody').value.trim();
            if (!messageText) {
                e.preventDefault();
                alert('Pesan tidak boleh kosong!');
            }
        });
    }
});
</script>
@endsection

