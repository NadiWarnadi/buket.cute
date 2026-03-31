@extends('layouts.admin')

@section('title', 'Chat - ' . ($customer->name ?? $customer->phone ?? 'Customer'))

@section('content')
<style>
    /* Mobile-First Responsive Design */
    :root {
        --wa-green: #25d366;
        --wa-dark-green: #128c7e;
        --wa-darker-green: #075e54;
        --message-bubble-radius: 18px;
        --transition: all 0.3s ease;
    }

    .chat-wrapper {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 100px);
        background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
    }

    /* Header */
    .chat-header {
        background: linear-gradient(135deg, var(--wa-dark-green) 0%, var(--wa-darker-green) 100%);
        color: white;
        padding: 12px 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .chat-header-info h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .chat-header-info small {
        opacity: 0.8;
        font-size: 13px;
    }

    .chat-header-actions {
        display: flex;
        gap: 8px;
    }

    .chat-header-actions .btn {
        padding: 6px 12px;
        font-size: 13px;
    }

    /* Messages Area */
    .messages-area {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
        display: flex;
        flex-direction: column;
        -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
    }

    .message-group {
        display: flex;
        margin-bottom: 12px;
        animation: slideIn 0.3s ease-in;
        max-width: 100%;
    }

    @keyframes slideIn {
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
        max-width: 85%;
        border-radius: var(--message-bubble-radius);
        padding: 10px 14px;
        word-wrap: break-word;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        position: relative;
    }

    .message-bubble.incoming {
        background-color: #fff;
        color: #000;
        border: 1px solid #d0d0d0;
    }

    .message-bubble.outgoing {
        background: linear-gradient(135deg, var(--wa-green) 0%, var(--wa-dark-green) 100%);
        color: #fff;
    }

    @media (max-width: 576px) {
        .message-bubble {
            max-width: 90%;
        }
    }

    .message-content {
        margin-bottom: 6px;
    }

    .message-body {
        margin: 0;
        font-size: 15px;
        line-height: 1.4;
    }

    .message-time {
        font-size: 12px;
        opacity: 0.7;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .message-bubble.incoming .message-time {
        color: #888;
    }

    .message-bubble.outgoing .message-time {
        color: rgba(255,255,255,0.8);
    }

    /* Date Divider */
    .date-divider {
        text-align: center;
        margin: 16px 0;
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .date-divider::before,
    .date-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #d0d0d0;
    }

    .date-divider span {
        background: #fff;
        padding: 4px 12px;
        color: #888;
        font-size: 12px;
        border-radius: 12px;
        white-space: nowrap;
    }

    /* Media Styling */
    .message-media {
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 8px;
        max-width: 100%;
    }

    .message-media img {
        max-width: 100%;
        display: block;
        max-height: 300px;
        object-fit: cover;
    }

    .message-media video {
        max-width: 100%;
        display: block;
        border-radius: 8px;
    }

    .message-document {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 12px;
        background: rgba(0,0,0,0.05);
    }

    .message-bubble.incoming .message-document {
        background: #f0f0f0;
    }

    .message-bubble.outgoing .message-document {
        background: rgba(255,255,255,0.2);
    }

    .message-document-icon {
        font-size: 24px;
        flex-shrink: 0;
    }

    .message-document-info {
        min-width: 0;
        flex: 1;
    }

    .message-document-name {
        font-size: 13px;
        font-weight: 500;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .message-document-link {
        font-size: 12px;
        display: inline-block;
        margin-top: 2px;
    }

    /* Input Area */
    .chat-input-area {
        border-top: 1px solid #d0d0d0;
        padding: 12px;
        background: white;
        flex-shrink: 0;
    }

    .input-group {
        display: flex;
        gap: 8px;
        margin-bottom: 0;
    }

    #messageBody {
        resize: none;
        border-radius: 20px;
        padding: 10px 16px;
        font-size: 15px;
        max-height: 100px;
        transition: var(--transition);
    }

    #messageBody:focus {
        background-color: #f9f9f9;
    }

    .btn-send {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .char-count {
        font-size: 12px;
        color: #999;
        margin-bottom: 8px;
    }

    .media-upload-section {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #eee;
    }

    .btn-media {
        font-size: 13px;
        padding: 6px 12px;
    }

    .file-info {
        font-size: 12px;
        margin-top: 6px;
        padding: 6px;
        border-radius: 6px;
    }

    .file-info.success {
        background: #d4edda;
        color: #155724;
    }

    .file-info.error {
        background: #f8d7da;
        color: #721c24;
    }

    /* Scrollbar Styling */
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

    /* Sidebar */
    .sidebar-customer-info {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .info-header {
        background: var(--wa-dark-green);
        color: white;
        padding: 16px;
    }

    .info-header h5 {
        margin: 0;
        font-size: 16px;
    }

    .info-body {
        padding: 16px;
    }

    .info-item {
        margin-bottom: 16px;
    }

    .info-item:last-child {
        margin-bottom: 0;
    }

    .info-label {
        font-size: 12px;
        color: #888;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 14px;
        color: #333;
        margin: 0;
    }

    .info-divider {
        border-top: 1px solid #eee;
        margin: 12px -16px;
    }

    .btn-action {
        width: 100%;
        font-size: 13px;
        padding: 8px 12px;
        margin-bottom: 8px;
    }

    .btn-action:last-child {
        margin-bottom: 0;
    }

    /* Empty State */
    .empty-state {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        padding: 20px;
        text-align: center;
    }

    .empty-state-icon {
        font-size: 48px;
        opacity: 0.2;
        margin-bottom: 12px;
    }

    .empty-state-text {
        color: #999;
        font-size: 14px;
    }

    /* Alert Styling */
    .alert-custom {
        border-radius: 8px;
        font-size: 13px;
        padding: 10px 12px;
        margin-bottom: 8px;
    }

    .alert-custom.error {
        background: #ffe6e6;
        border: 1px solid #ffcccc;
        color: #cc0000;
    }

    .alert-custom.success {
        background: #e6ffe6;
        border: 1px solid #ccffcc;
        color: #00cc00;
    }

    /* Responsive Layout */
    @media (max-width: 768px) {
        .chat-wrapper {
            height: calc(100vh - 60px);
        }

        .sidebar-left {
            margin-bottom: 16px;
        }

        .chat-header {
            padding: 10px 12px;
        }

        .chat-header-info h5 {
            font-size: 15px;
        }

        .chat-header-info small {
            font-size: 12px;
        }

        .message-bubble {
            max-width: 95%;
        }

        .messages-area {
            padding: 10px;
        }

        .chat-input-area {
            padding: 10px;
        }

        #messageBody {
            font-size: 14px;
        }

        .btn-back {
            margin-bottom: 12px;
        }
    }

    @media (max-width: 480px) {
        .chat-header {
            gap: 8px;
        }

        .chat-header-info h5 {
            font-size: 14px;
        }

        .chat-header-actions {
            width: 100%;
            font-size: 12px;
        }

        .chat-header-actions .btn {
            flex: 1;
            font-size: 12px;
            padding: 4px 8px;
        }

        .message-bubble {
            max-width: 92%;
            padding: 8px 12px;
        }

        .message-body {
            font-size: 14px;
        }

        .message-time {
            font-size: 11px;
        }

        .input-group {
            gap: 6px;
        }

        .btn-send {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-media {
            font-size: 12px;
            padding: 4px 8px;
        }
    }

    /* Loading State */
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .spinner {
        display: inline-block;
        width: 12px;
        height: 12px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid var(--wa-green);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-right: 6px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('admin.chat.index') }}" class="btn btn-secondary btn-sm btn-back">
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

<div class="row g-3 flex-md-row-reverse">
    <!-- Sidebar (Right on Desktop, Bottom on Mobile) -->
    <div class="col-12 col-lg-4 col-md-5">
        <div class="sidebar-customer-info">
            <div class="info-header">
                <h5><i class="bi bi-person-circle"></i> {{ $customer->name ?? $customer->phone }}</h5>
            </div>
            <div class="info-body">
                <div class="info-item">
                    <div class="info-label">Nomor Telepon</div>
                    <p class="info-value">{{ $customer->phone ?? 'N/A' }}</p>
                </div>

                @if($customer->email)
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <p class="info-value">{{ $customer->email }}</p>
                    </div>
                @endif

                @if($customer->address)
                    <div class="info-item">
                        <div class="info-label">Alamat</div>
                        <p class="info-value">{{ $customer->address }}</p>
                    </div>
                @endif

                <div class="info-divider"></div>

                <div class="info-item">
                    <div class="info-label">Status Chat</div>
                    @php
                        $chatStatus = $customer->getChatStatus();
                    @endphp
                    <p class="info-value">
                        @if($chatStatus === 'active')
                            <span class="badge bg-success"><i class="bi bi-circle-fill"></i> Aktif</span>
                        @elseif($chatStatus === 'archived')
                            <span class="badge bg-warning"><i class="bi bi-archive"></i> Diarsipkan</span>
                        @else
                            <span class="badge bg-secondary">Ditutup</span>
                        @endif
                    </p>
                </div>

                <div class="info-item">
                    <div class="info-label">Total Pesan</div>
                    <p class="info-value">{{ $customer->messages->count() }}</p>
                </div>

                <div class="info-divider"></div>

                @if($customer->phone)
                    <a href="{{ route('admin.customers.show-by-phone', $customer->phone) }}" class="btn btn-sm btn-outline-info btn-action">
                        <i class="bi bi-eye"></i> Detail Customer
                    </a>
                @endif

                <a href="https://wa.me/{{ $customer->phone }}" target="_blank" class="btn btn-sm btn-outline-success btn-action">
                    <i class="bi bi-whatsapp"></i> Chat di WhatsApp
                </a>

                @if($chatStatus === 'active')
                    <form method="POST" action="{{ route('admin.chat.updateStatus', $customer) }}" class="d-inline w-100">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="archived">
                        <button type="submit" class="btn btn-sm btn-outline-warning btn-action w-100">
                            <i class="bi bi-archive"></i> Arsipkan
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.chat.updateStatus', $customer) }}" class="d-inline w-100">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="active">
                        <button type="submit" class="btn btn-sm btn-outline-success btn-action w-100">
                            <i class="bi bi-arrow-counterclockwise"></i> Aktifkan
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.chat.destroy', $customer->id) }}" 
                      onsubmit="return confirm('Yakin hapus semua pesan? Tidak bisa dikembalikan!')" class="w-100">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger btn-action w-100">
                        <i class="bi bi-trash"></i> Hapus Pesan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Chat Area (Left on Desktop, Top on Mobile) -->
    <div class="col-12 col-lg-8 col-md-7">
        <div class="chat-wrapper">
            <!-- Chat Header -->
            <div class="chat-header">
                <div class="chat-header-info">
                    <h5>{{ $customer->name ?? 'Customer ' . $customer->phone }}</h5>
                    <small>{{ $customer->phone }}</small>
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
                                @if($msg->media_url)
                                    <div class="message-media">
                                        @if($msg->type === 'image')
                                            <img src="{{ $msg->media_url }}" alt="Image" loading="lazy">
                                        @elseif($msg->type === 'video')
                                            <video controls width="100%">
                                                <source src="{{ $msg->media_url }}" type="video/mp4">
                                                Browser Anda tidak mendukung video.
                                            </video>
                                        @elseif($msg->type === 'document')
                                            <div class="message-document">
                                                <div class="message-document-icon">
                                                    <i class="bi bi-file-earmark"></i>
                                                </div>
                                                <div class="message-document-info">
                                                    <p class="message-document-name" title="{{ $msg->file_name }}">
                                                        {{ $msg->file_name ?? 'Document' }}
                                                    </p>
                                                    <a href="{{ $msg->media_url }}" download class="message-document-link">
                                                        <i class="bi bi-download"></i> Unduh
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if($msg->body)
                                    <div class="message-content">
                                        <p class="message-body">{{ $msg->body }}</p>
                                    </div>
                                @endif

                                <div class="message-time">
                                    <span>{{ $msg->created_at->format('H:i') }}</span>
                                    @if(!$msg->is_incoming && $msg->status)
                                        @if($msg->status === 'read')
                                            <i class="bi bi-check2-all" title="Dibaca"></i>
                                        @elseif($msg->status === 'sent')
                                            <i class="bi bi-check2" title="Terkirim"></i>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty-state">
                        <div>
                            <div class="empty-state-icon">
                                <i class="bi bi-chat-dots"></i>
                            </div>
                            <p class="empty-state-text">Belum ada pesan</p>
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

                        <div class="input-group">
                            <textarea 
                                class="form-control @error('body') is-invalid @enderror" 
                                id="messageBody" 
                                name="body" 
                                rows="1" 
                                placeholder="Ketik pesan..." 
                                maxlength="1000"
                            ></textarea>
                            <button class="btn btn-success btn-send" type="submit" id="sendBtn">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>

                        <div class="char-count">
                            <span id="charCount">0</span>/1000
                        </div>

                        @error('body')
                            <div class="alert-custom error">
                                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror

                        <!-- Media Upload Section -->
                        <div class="media-upload-section">
                            <input type="hidden" name="type" id="messageType" value="text">
                            <input type="file" id="mediaInput" name="media" class="d-none" 
                                   accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx">
                            
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-media" id="mediaBtn">
                                <i class="bi bi-paperclip"></i> Lampir (Max 25MB)
                            </button>
                            
                            <div class="file-info" id="fileInfo"></div>

                            @error('media')
                                <div class="alert-custom error">
                                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </form>
                @else
                    <div class="alert-custom error">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Chat {{ $chatStatus === 'archived' ? 'diarsipkan' : 'ditutup' }}</strong><br>
                        <small>Tidak dapat mengirim pesan pada chat yang {{ $chatStatus === 'archived' ? 'diarsipkan' : 'ditutup' }}</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
const MAX_FILE_SIZE = 25 * 1024 * 1024;
const CUSTOMER_ID = {{ $customer->id }};
let autoRefreshInterval = null;
let lastMessagesCount = {{ $customer->messages->count() }};

document.addEventListener('DOMContentLoaded', function() {
    initUI();
    startAutoRefresh();
});

function initUI() {
    // Auto-expand textarea
    const messageBody = document.getElementById('messageBody');
    if (messageBody) {
        messageBody.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            
            const charCount = document.getElementById('charCount');
            if (charCount) {
                charCount.textContent = this.value.length;
            }
        });

        messageBody.addEventListener('keydown', function(e) {
            // Send on Enter (but not Shift+Enter for new line)
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('chatForm')?.submit();
            }
        });
    }

    // Auto-scroll to bottom
    scrollToBottom();

    // Media upload handler
    const mediaBtn = document.getElementById('mediaBtn');
    const mediaInput = document.getElementById('mediaInput');
    const messageTypeInput = document.getElementById('messageType');
    const fileInfo = document.getElementById('fileInfo');
    
    if (mediaBtn && mediaInput) {
        mediaBtn.addEventListener('click', () => mediaInput.click());

        mediaInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);

                if (file.size > MAX_FILE_SIZE) {
                    fileInfo.innerHTML = `<span class="error"><i class="bi bi-exclamation-triangle"></i> File terlalu besar (${fileSizeMB}MB > 25MB)</span>`;
                    fileInfo.className = 'file-info error';
                    mediaInput.value = '';
                    return;
                }

                if (file.type.startsWith('image/')) {
                    messageTypeInput.value = 'image';
                } else if (file.type.startsWith('video/')) {
                    messageTypeInput.value = 'video';
                } else {
                    messageTypeInput.value = 'document';
                }

                fileInfo.innerHTML = `<i class="bi bi-check-circle"></i> ${file.name} (${fileSizeMB}MB)`;
                fileInfo.className = 'file-info success';
            }
        });
    }

    // Form submission
    const chatForm = document.getElementById('chatForm');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            const messageTextarea = document.getElementById('messageBody');
            const messageText = messageTextarea.value.trim();
            
            messageTextarea.value = messageText;
            
            if (!messageText && !document.getElementById('mediaInput')?.files?.length) {
                e.preventDefault();
                showAlert('Pesan atau file harus diisi!', 'error');
            }
        });
    }
}

function scrollToBottom() {
    const messagesArea = document.getElementById('messagesArea');
    if (messagesArea) {
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }
}

function startAutoRefresh() {
    autoRefreshInterval = setInterval(refreshMessages, 3000);
    window.addEventListener('beforeunload', () => {
        if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    });
}

function refreshMessages() {
    fetch(window.location.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        }
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, 'text/html');
        const newMessagesArea = newDoc.querySelector('#messagesArea');
        const oldMessagesArea = document.querySelector('#messagesArea');
        
        if (newMessagesArea && oldMessagesArea) {
            if (oldMessagesArea.innerHTML !== newMessagesArea.innerHTML) {
                oldMessagesArea.innerHTML = newMessagesArea.innerHTML;
                scrollToBottom();
            }
        }
    })
    .catch(error => console.log('Auto-refresh error:', error));
}

function showAlert(message, type = 'info') {
    const container = document.getElementById('alertContainer');
    if (container) {
        container.innerHTML = `<div class="alert-custom ${type}">
            <i class="bi bi-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i> ${message}
        </div>`;
        setTimeout(() => {
            container.innerHTML = '';
        }, 3000);
    }
}
</script>
@endsection
