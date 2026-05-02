@extends('layouts.admin')

@section('title', 'Chat - ' . ($customer->name ?? $customer->phone ?? 'Customer'))

@section('content')
@inject('Str', 'Illuminate\Support\Str')
@push('styles')
    @vite(['resources/css/chat-wa.css'])
@endpush
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
                <div class="chat-header-actions">
                    @php
                        $isAdminHandled = $customer->is_admin_handled ?? false;
                    @endphp
                    <button type="button" class="btn btn-sm delegation-toggle" 
                            id="delegationBtn"
                            data-customer-id="{{ $customer->id }}"
                            data-is-admin="{{ $isAdminHandled ? 'true' : 'false' }}">
                        @if($isAdminHandled)
                            <i class="bi bi-robot"></i> Kembalikan ke AI
                        @else
                            <i class="bi bi-person"></i> Tangani Admin
                        @endif
                    </button>
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
                               @if($msg->media->isNotEmpty())
    @php
        $mediaItem = $msg->media->first();
        $mediaUrl = Storage::url($mediaItem->file_path);
        $mime = $mediaItem->mime_type;
    @endphp
    <div class="message-media">
        @if(Str::startsWith($mime, 'image/'))
            <a href="{{ $mediaUrl }}" target="_blank">
                <img src="{{ $mediaUrl }}" alt="Media" loading="lazy" style="max-width: 250px; border-radius: 8px;" />
            </a>
        @elseif(Str::startsWith($mime, 'video/'))
            <video controls style="max-width: 250px; border-radius: 8px;">
                <source src="{{ $mediaUrl }}" type="{{ $mime }}">
                Browser tidak mendukung video.
            </video>
        @else
            <div class="document-preview">
                <i class="bi bi-file-earmark"></i>
                <a href="{{ $mediaUrl }}" target="_blank">
                    {{ $mediaItem->file_name ?? 'Unduh file' }}
                </a>
            </div>
        @endif
    </div>
@elseif($msg->media_url)
    <div class="message-media">
        <i class="bi bi-link-45deg"></i>
        <a href="{{ $msg->media_url }}" target="_blank">Lihat Media (WhatsApp)</a>
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

    // Delegation button
    const delegationBtn = document.getElementById('delegationBtn');
    if (delegationBtn) {
        delegationBtn.addEventListener('click', toggleDelegation);
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

function toggleDelegation() {
    const btn = document.getElementById('delegationBtn');
    const customerId = btn.getAttribute('data-customer-id');
    const isAdminHandled = btn.getAttribute('data-is-admin') === 'true';
    
    btn.disabled = true;
    
    fetch(`{{ route('admin.chat.toggle-delegation', ':id') }}`.replace(':id', customerId), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            is_admin_handled: !isAdminHandled
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.setAttribute('data-is-admin', !isAdminHandled ? 'true' : 'false');
            btn.innerHTML = !isAdminHandled 
                ? '<i class="bi bi-robot"></i> Kembalikan ke AI'
                : '<i class="bi bi-person"></i> Tangani Admin';
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message || 'Gagal mengubah delegasi', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Terjadi kesalahan', 'error');
    })
    .finally(() => {
        btn.disabled = false;
    });
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