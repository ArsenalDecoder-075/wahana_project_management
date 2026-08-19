@extends('layouts.app')
<title>Detail Tugas - {{ $task->title }}</title>

@section('content')
<style>
    /* ===== CHAT CONTAINER ===== */
    .chat-container {
        display: flex;
        flex-direction: column;
        gap: 16px;
        padding: 8px 0;
    }

    /* ===== CHAT MESSAGE WRAPPER ===== */
    .chat-message {
        display: flex;
        gap: 12px;
        width: 100%;
        margin-bottom: 12px;
    }

    .chat-message-left {
        justify-content: flex-start;
    }

    .chat-message-right {
        justify-content: flex-end;
    }

    /* ===== AVATAR ===== */
    .chat-avatar {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
    }

    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 16px;
        text-transform: uppercase;
    }

    .avatar-circle.bg-primary { background: #4e73df; }
    .avatar-circle.bg-success { background: #1cc88a; }
    .avatar-circle.bg-danger { background: #e74a3b; }
    .avatar-circle.bg-warning { background: #f6c23e; color: #333; }

    /* ===== CHAT BUBBLE ===== */
    .chat-bubble {
        max-width: 75%;
        padding: 10px 14px;
        border-radius: 12px;
        background: #f1f3f5;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        word-wrap: break-word;
        flex: 0 1 auto;
    }

    .chat-bubble-left {
        background: #e9ecef;
        border-bottom-left-radius: 4px;
    }

    .chat-bubble-success {
        background: #d4edda;
        border-bottom-right-radius: 4px;
        border-left: 4px solid #28a745;
    }

    .chat-bubble-danger {
        background: #f8d7da;
        border-bottom-right-radius: 4px;
        border-left: 4px solid #dc3545;
    }

    .chat-bubble-warning {
        background: #fff3cd;
        border-bottom-right-radius: 4px;
        border-left: 4px solid #ffc107;
    }

    /* Urutan elemen */
    .chat-message-left .chat-avatar { order: 0; }
    .chat-message-left .chat-bubble { order: 1; }

    .chat-message-right .chat-avatar { order: 1; }
    .chat-message-right .chat-bubble { order: 0; }

    /* ===== CHAT HEADER ===== */
    .chat-header {
        display: flex;
        justify-content: flex-start !important;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
        flex-wrap: wrap;
    }

    .chat-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: #2c3e50;
    }

    .chat-time {
        font-size: 0.7rem;
        color: #6c757d;
    }

    /* ===== CHAT BODY ===== */
    .chat-body {
        text-align: left !important;
        font-size: 0.95rem;
        color: #212529;
        word-break: break-word;
    }

    /* ===== CHAT FOOTER ===== */
    .chat-footer {
        margin-top: 8px;
        display: flex;
        justify-content: flex-end;
    }

    .status-badge {
        font-size: 0.7rem;
        padding: 4px 10px;
        border-radius: 20px;
    }

    /* ===== SCROLLBAR STYLING ===== */
    .card-body::-webkit-scrollbar {
        width: 6px;
    }

    .card-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .card-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 10px;
    }

    .card-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .chat-bubble {
            max-width: 100%;
        }
        .chat-avatar {
            width: 32px;
            height: 32px;
        }
        .avatar-circle {
            width: 32px;
            height: 32px;
            font-size: 12px;
        }
        .chat-bubble {
            padding: 8px 12px;
        }
        .chat-name {
            font-size: 0.8rem;
        }
        .chat-body {
            font-size: 0.85rem;
        }
    }

    .chat-reply-quote {
        background: rgba(0,0,0,0.05);
        border-left: 3px solid #6c757d;
        padding: 4px 10px;
        margin-bottom: 6px;
        border-radius: 4px;
        font-size: 0.85rem;
        color: #495057;
    }

    .chat-reply-quote .quote-text {
        font-style: italic;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    /* Jika pesan kanan, kutipan juga rata kiri */
    .chat-message-right .chat-reply-quote {
        text-align: left;
    }

    /* ===== ANIMASI ===== */
    .chat-message {
        animation: fadeInUp 0.3s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .preview-item {
        display: inline-block;
        text-align: center;
        margin: 4px;
    }
    .preview-item img {
        object-fit: cover;
    }
</style>

    <div class="container-fluid">
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <h2 class="text-bold">
                    <i class="fas fa-tasks text-primary me-2"></i>
                    Detail Tugas
                </h2>
                <p class="text-muted">Proyek: {{ $task->project->title ?? '-' }}</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="{{ route('user.projects.tasks', $task->project_id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Tugas
                </a>
            </div>
        </div>


        {{-- Informasi Tugas + Tombol Aksi --}}
        <div class="col-md-12" style="display: flex;">
            <div class="card mb-4" style="flex: 1;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-info me-2"></i>
                        Informasi Tugas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        {{-- Judul & Deskripsi --}}
                        <div class="col-md-8">
                            <h4 class="mb-2">{{ $task->title }}</h4>
                            <p class="text-muted mb-0">
                                {{ $task->description ?? 'Tidak ada deskripsi' }}
                            </p>
                        </div>

                        {{-- Status & Info --}}
                        <div class="col-md-4">
                            <div class="border-start ps-3">
                                {{-- Status --}}
                                <div class="mb-2">
                                    <span class="text-muted">Status:</span>
                                    <span class="badge
                                        @if($task->status == 'Pending') bg-warning text-dark
                                        @elseif($task->status == 'On Progress') bg-info
                                        @elseif($task->status == 'Review') bg-secondary
                                        @elseif($task->status == 'Rejected') bg-danger
                                        @elseif($task->status == 'Completed') bg-success
                                        @endif
                                        fs-6" id="currentStatusBadge">
                                        @if($task->status == 'Pending')<i class="fas fa-clock me-1"></i>
                                        @elseif($task->status == 'On Progress')<i class="fas fa-spinner me-1"></i>
                                        @elseif($task->status == 'Review')<i class="fa-solid fa-magnifying-glass"></i>
                                        @elseif($task->status == 'Completed')<i class="fas fa-check me-1"></i>
                                        @elseif($task->status == 'Rejected')<i class="fa-regular fa-circle-xmark"></i>
                                        @endif
                                        {{ $task->status }}
                                    </span>
                                </div>

                                {{-- Prioritas --}}
                                <div class="mb-2">
                                    <span class="text-muted">Prioritas:</span>
                                    @php
                                        $badges = [
                                            'high'   => '<span class="badge bg-danger"><i class="fas fa-arrow-up"></i> HIGH</span>',
                                            'medium' => '<span class="badge bg-warning text-dark"><i class="fas fa-minus"></i> MEDIUM</span>',
                                            'low'    => '<span class="badge bg-info"><i class="fas fa-arrow-down"></i> LOW</span>'
                                        ];
                                    @endphp
                                    {!! $badges[$task->priority] ?? '<span class="badge bg-secondary">-</span>' !!}
                                </div>

                                {{-- Deadline --}}
                                <div class="mb-2">
                                    <span class="text-muted">Deadline:</span>
                                    <strong>{{ \Carbon\Carbon::parse($task->deadline)->format('d M Y') }}</strong>
                                    @php
                                        $daysLeft = \Carbon\Carbon::now()->diffInDays($task->deadline, false);
                                    @endphp
                                    @if($task->status != 'Completed')
                                        @if($daysLeft < 0)
                                            <span class="badge bg-danger ms-1">Terlambat</span>
                                        @elseif($daysLeft <= 3)
                                            <span class="badge bg-warning text-dark ms-1">Mendesak</span>
                                        @endif
                                    @endif
                                </div>

                                {{-- Pembuat --}}
                                <div class="mb-2">
                                    <span class="text-muted">Dibuat oleh:</span>
                                    <strong>{{ $task->creator->name ?? '-' }}</strong>
                                </div>

                                {{-- Tombol Aksi --}}
                                <div>
                                    @if($task->status == 'Pending')
                                        <button class="btn btn-warning w-100" id="btnOnProgress">
                                            <i class="fas fa-play me-2"></i> Mulai Kerjakan
                                        </button>
                                        <small class="text-muted d-block mt-2" id="statusMessage">
                                            <i class="fas fa-info-circle me-1"></i> Mulai kerjakan tugas
                                        </small>
                                    @elseif($task->status == 'On Progress')
                                        <button class="btn btn-success w-100" id="btnReview">
                                            <i class="fas fa-check me-2"></i> Mulai Review
                                        </button>
                                        <small class="text-muted d-block mt-2" id="statusMessage">
                                            <i class="fas fa-info-circle me-1"></i> Ini akan memberikan notif ke atasan bahwa tugas ini siap untuk diverifikasi dan menunggu review. Masih bisa mengirim catatan tambahan jika diperlukan.
                                        </small>
                                    @elseif($task->status == 'Review')
                                        <button class="btn btn-secondary w-100" disabled>
                                            <i class="fas fa-clock me-2"></i> Sedang Direview
                                        </button>
                                        <small class="text-muted d-block mt-2" id="statusMessage">
                                            <i class="fas fa-info-circle me-1"></i> Tugas sedang dalam proses review oleh atasan. Anda masih dapat menambahkan catatan jika diperlukan.
                                        </small>
                                    @elseif($task->status == 'Rejected')
                                        <button class="btn btn-warning w-100" id="btnOnProgress">
                                            <i class="fas fa-redo me-2"></i> Mulai Kerjakan Kembali
                                        </button>
                                        <small class="text-muted d-block mt-2" id="statusMessage">
                                            <i class="fas fa-info-circle me-1"></i> Tugas ditolak, silakan perbaiki dan kerjakan kembali.
                                        </small>
                                    @elseif($task->status == 'Completed')
                                        <button class="btn btn-secondary w-100" disabled>
                                            <i class="fas fa-check-circle me-2"></i> Selesai
                                        </button>
                                        <small class="text-muted d-block mt-2" id="statusMessage">
                                            <i class="fas fa-check-circle text-success me-1"></i> Tugas selesai!
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4 mt-2" style="display: flex; flex-wrap: wrap; align-items: stretch;">
            {{-- Riwayat Catatan & Feedback (Tampilan Chat) --}}
            <div class="col-md-6 d-flex">
                @if($messages->count() > 0)
                <div class="card mb-4 w-100 flex-fill">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-comments me-2"></i>
                            Riwayat Catatan & Feedback
                        </h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <div class="chat-container">
                            @foreach($messages as $msg)
                                @if($msg->type == 'submission')
                                    {{-- Pesan dari Karyawan (KIRI) --}}
                                    <div class="chat-message chat-message-left">
                                        <div class="chat-avatar">
                                            <div class="avatar-circle bg-primary">
                                                {{ substr($msg->user_name, 0, 1) }}
                                            </div>
                                        </div>
                                        <div class="chat-bubble chat-bubble-left">
                                            <div class="chat-header">
                                                <span class="chat-name">{{ $msg->user_name }}</span>
                                                <span class="chat-time">{{ $msg->created_at->format('d M Y, H:i') }}</span>
                                            </div>
                                            {{-- Tidak ada reply_to untuk submission --}}
                                            <div class="chat-body {{ $msg->is_deleted ? 'text-muted fst-italic opacity-50' : '' }}">
                                                {{ $msg->message }}
                                            </div>
                                            {{-- Bagian untuk tampilin gambar submission karyawan --}}
                                            @if($msg->type == 'submission' && isset($msg->files) && $msg->files->count() > 0)
                                                <div class="chat-files mt-2">
                                                    @foreach($msg->files as $file)
                                                        <div class="file-item d-inline-block me-2 mb-2">
                                                            @php
                                                                $isImage = in_array($file->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
                                                            @endphp
                                                            @if($isImage)
                                                                <a href="{{ $file->url }}" target="_blank" title="{{ $file->file_name }}" style="display: block; width: 100%;">
                                                                    <img src="{{ $file->url }}"
                                                                        alt="{{ $file->file_name }}"
                                                                        style="width: 100%; height: auto; max-width: 100%; border-radius: 8px; border: 1px solid #ddd; object-fit: contain;">
                                                                </a>
                                                                <div class="file-info small text-muted mt-1">
                                                                    <i class="fas fa-image me-1"></i> {{ $file->file_name }}
                                                                </div>
                                                            @else
                                                                @php
                                                                    $icon = 'fa-file';
                                                                    if (str_contains($file->mime_type, 'pdf')) $icon = 'fa-file-pdf';
                                                                    elseif (str_contains($file->mime_type, 'word')) $icon = 'fa-file-word';
                                                                    elseif (str_contains($file->mime_type, 'excel') || str_contains($file->mime_type, 'spreadsheet')) $icon = 'fa-file-excel';
                                                                @endphp
                                                                <a href="{{ $file->url }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                                    <i class="fas {{ $icon }} me-1"></i> {{ $file->file_name }}
                                                                </a>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            {{-- Tombol Hapus di bagian bawah --}}
                                            @if(!$msg->is_deleted && $msg->review_status == 'pending')
                                                <div class="mt-2 text-end">
                                                    <button class="btn btn-sm btn-danger delete-submission-btn"
                                                            data-id="{{ $msg->id }}"
                                                            title="Hapus pesan">
                                                        <i class="fas fa-trash me-1"></i> Hapus
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    {{-- Feedback dari Atasan (KANAN) --}}
                                    <div class="chat-message chat-message-right">
                                        <div class="chat-bubble
                                            @if($msg->status == 'accepted') chat-bubble-success
                                            @elseif($msg->status == 'rejected') chat-bubble-danger
                                            @else chat-bubble-warning
                                            @endif
                                        ">
                                            <div class="chat-header">
                                                <span class="chat-name">
                                                    @if($msg->status == 'accepted')
                                                        <i class="fas fa-check-circle text-success me-1"></i>
                                                    @elseif($msg->status == 'rejected')
                                                        <i class="fas fa-times-circle text-danger me-1"></i>
                                                    @else
                                                        <i class="fas fa-clock text-warning me-1"></i>
                                                    @endif
                                                    {{ $msg->user_name }}
                                                </span>
                                                <span class="chat-time">{{ $msg->created_at->format('d M Y, H:i') }}</span>
                                            </div>

                                            {{-- ✅ Tampilkan "Membalas" jika ada reply_to --}}
                                            @if($msg->reply_to)
                                                <div class="chat-reply-quote">
                                                    <i class="fas fa-reply fa-flip-horizontal me-1 text-muted"></i>
                                                    <span class="text-muted">Membalas:</span>
                                                    <div class="quote-text">
                                                        {{ \Illuminate\Support\Str::limit($msg->reply_to, 50) }}
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="chat-body">
                                                {{ $msg->message }}
                                            </div>
                                            <div class="chat-footer">
                                                <span class="badge
                                                    @if($msg->status == 'accepted') bg-success
                                                    @elseif($msg->status == 'rejected') bg-danger
                                                    @else bg-warning text-dark
                                                    @endif
                                                    status-badge">
                                                    @if($msg->status == 'accepted')
                                                        <i class="fas fa-check me-1"></i> Disetujui
                                                    @elseif($msg->status == 'rejected')
                                                        <i class="fas fa-times me-1"></i> Ditolak
                                                    @else
                                                        <i class="fas fa-clock me-1"></i> Menunggu
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        <div class="chat-avatar">
                                            <div class="avatar-circle
                                                @if($msg->status == 'accepted') bg-success
                                                @elseif($msg->status == 'rejected') bg-danger
                                                @else bg-warning
                                                @endif
                                            ">
                                                {{ substr($msg->user_name, 0, 1) }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>


            {{-- Kolom Kanan: Kirim Catatan Tugas --}}
            <div class="col-md-6" style="display: flex;">
                <div class="card mb-4" style="flex: 1;">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-pen me-2"></i>
                            Kirim Catatan Tugas
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($task->status == 'Pending')
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Tugas belum dimulai. Silakan klik tombol <strong>"Mulai Kerjakan"</strong> terlebih dahulu untuk dapat mengirim catatan.
                            </div>
                        @elseif(in_array($task->status, ['On Progress', 'Review', 'Rejected']))
                        @if($submission && $submission->status == 'pending')
                            <div class="alert alert-warning">
                                <i class="fas fa-clock me-2"></i>
                                Anda memiliki catatan yang sedang menunggu review. Tunggu review selesai sebelum mengirim catatan baru.
                            </div>
                            <div class="border-start border-4 border-warning ps-3 bg-light p-3 rounded">
                                <strong>Catatan terakhir:</strong>
                                <p class="mb-0 text-muted">{{ $submission->notes ?? 'Tidak ada catatan' }}</p>
                                <small class="text-muted">Dikirim: {{ $submission->created_at->format('d M Y H:i') }}</small>
                            </div>
                        @else
                            <form id="submitNotesForm" method="POST" action="{{ route('user.tasks.submitNotes') }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="task_id" value="{{ $task->id }}">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                                    <textarea class="form-control" id="notes" name="notes" rows="5" placeholder="Tulis catatan atau penjelasan tentang pengerjaan tugas ini..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="files" class="form-label">
                                        <i class="fas fa-paperclip me-1"></i> Lampiran (opsional)
                                    </label>
                                    <input type="file"
                                        class="form-control"
                                        id="files"
                                        name="files[]"
                                        multiple
                                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv">
                                    <small class="text-muted">
                                        Format: JPG, PNG, GIF, WebP, PDF, DOC, DOCX. Maksimal 5MB per file.
                                    </small>
                                </div>
                                <div id="file-preview" class="mt-2 d-flex flex-wrap gap-2"></div>
                                <button type="submit" class="btn btn-success" id="submitNotesBtn">
                                    <i class="fas fa-paper-plane me-1"></i> Kirim Catatan
                                </button>
                            </form>
                        @endif

                        @if($submission && $submission->status == 'reviewed')
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Catatan Anda telah direview.
                            </div>
                            <div class="border-start border-4 border-success ps-3 bg-light p-3 rounded">
                                <strong>Catatan Anda:</strong>
                                <p class="mb-0 text-muted">{{ $submission->notes ?? 'Tidak ada catatan' }}</p>
                                <small class="text-muted">Dikirim: {{ $submission->created_at->format('d M Y H:i') }}</small>
                            </div>
                        @endif
                    @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- Scripts --}}
@push('scripts')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>


    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $(document).ready(function() {
        console.log('✅ 1. Document ready!');
        console.log('🔍 2. Tombol btnOnProgress:', $('#btnOnProgress').length);
        console.log('🔍 3. Tombol btnComplete:', $('#btnComplete').length);
        console.log('🔍 4. Badge status:', $('#currentStatusBadge').length);
        console.log('🔍 5. Pesan status:', $('#statusMessage').length);

        // 1. FUNGSI SHOW TOAST
        function showToast(message, type = 'success') {
            if ($('#toast-container').length === 0) {
                $('body').append(`
                    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;"></div>
                `);
            }

            const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
            const icon = type === 'success' ? '✅' : '❌';

            const toast = `
                <div class="toast show align-items-center text-white ${bgClass} border-0 mb-2 shadow-lg" role="alert" style="border-radius: 8px;">
                    <div class="d-flex p-3">
                        <div class="toast-body fw-bold">${icon} ${message}</div>
                        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;

            $('#toast-container').append(toast);
            setTimeout(() => {
                $('#toast-container .toast:first').remove();
            }, 5000);
        }

        // 2. FUNGSI UPDATE STATUS
        function updateTaskStatus(status) {
            console.log('🔄 updateTaskStatus dipanggil, status:', status);

            const btn = status === 'On Progress' ? $('#btnOnProgress') : status === 'Review' ? $('#btnReview') : $('#btnComplete');
            const originalText = btn.html();

            if (!btn.length) {
                console.error('❌ Tombol tidak ditemukan!');
                return;
            }

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Memproses...');

            $.ajax({
                url: "{{ route('user.tasks.updateStatus') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    task_id: "{{ $task->id }}",
                    status: status
                },
                success: function(response) {
                    console.log('✅ Response success:', response);

                    if (response.success) {
                        // 1. Update badge status
                        const statusMap = {
                            'Pending': 'bg-warning text-dark',
                            'On Progress': 'bg-info',
                            'Review': 'bg-primary',
                            'Rejected': 'bg-danger',
                            'Completed': 'bg-success'
                        };
                        $('#currentStatusBadge')
                            .removeClass('bg-warning text-dark bg-info bg-success')
                            .addClass(statusMap[response.new_status])
                            .text(response.new_status);

                        // 2. Update tombol dan pesan
                        if (response.new_status === 'On Progress') {
                            $('#btnOnProgress')
                                // .replaceWith(`
                                //     <button class="btn btn-success w-100" id="btnComplete">
                                //         <i class="fas fa-check me-2"></i> Selesaikan Tugas
                                //     </button>
                                // `);
                            $('#statusMessage').html('<i class="fas fa-info-circle me-1"></i> Selesaikan tugas dengan mengubah status ke "Completed"');
                        } else if (response.new_status === 'Completed') {
                            $('#btnComplete')
                                // .replaceWith(`
                                //     <button class="btn btn-secondary w-100" disabled>
                                //         <i class="fas fa-check-circle me-2"></i> Selesai
                                //     </button>
                                // `);
                            $('#statusMessage').html('<i class="fas fa-check-circle text-success me-1"></i> Tugas ini sudah selesai!');
                        }

                        // 3. Reload halaman agar tampilan card catatan dan riwayat otomatis update
                        showToast(response.message, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(response.message || 'Gagal update status', 'error');
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr) {
                    console.error('❌ Error response:', xhr);
                    let errorMsg = 'Terjadi kesalahan saat update status';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showToast(errorMsg, 'error');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        }

        $(document).on('click', '#btnOnProgress', function() {
            console.log('🔥 Tombol On Progress DIKLIK!');
            if (confirm('Apakah Anda yakin ingin mulai mengerjakan tugas ini?')) {
                updateTaskStatus('On Progress');
            }
        });

        // Tombol Mulai Review (dari On Progress)
        $(document).on('click', '#btnReview', function() {
            console.log('🔥 Tombol Review DIKLIK!');
            if (confirm('Apakah Anda yakin ingin mengirim tugas ke review?')) {
                updateTaskStatus('Review');
            }
        });

        $(document).on('click', '#btnComplete', function(e) {
            if (!$(this).data('handled')) {
                $(this).data('handled', true);
                console.log('🔥 Tombol Complete DIKLIK (delegasi)!');
                if (confirm('Apakah Anda yakin tugas ini sudah selesai?')) {
                    updateTaskStatus('Completed');
                }
                setTimeout(() => $(this).data('handled', false), 500);
            }
        });

        // 5. SUBMIT CATATAN
        $('#submitNotesForm').on('submit', function(e) {
            e.preventDefault();
            console.log('📝 Form catatan disubmit');

            // Ambil form element
            const form = this;
            const formData = new FormData(form);
            const btn = $('#submitNotesBtn');
            const originalText = btn.html();

            // Disable button saat proses
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Mengirim...');

            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('✅ Response submit catatan:', response);
                    if (response.success) {
                        showToast(response.message || 'Catatan berhasil dikirim!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Gagal: ' + response.message, 'error');
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr) {
                    console.error('❌ Error submit catatan:', xhr);
                    let errorMsg = 'Terjadi kesalahan saat mengirim catatan';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showToast(errorMsg, 'error');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        $('#files').on('change', function() {
            const preview = $('#file-preview');
            preview.empty();
            const files = this.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.append(`
                            <div class="preview-item">
                                <img src="${e.target.result}" style="max-width:100px; max-height:100px; border-radius:8px; border:1px solid #ddd;">
                                <div class="small text-muted">${file.name}</div>
                            </div>
                        `);
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.append(`
                        <div class="preview-item">
                            <i class="fas fa-file fa-2x text-secondary"></i>
                            <div class="small text-muted">${file.name}</div>
                        </div>
                    `);
                }
            }
        });

        $(document).on('click', '.delete-submission-btn', function() {
            const submissionId = $(this).data('id');
            const btn = $(this);

            if (!confirm('Apakah Anda yakin ingin menghapus pesan ini? Semua lampiran akan dihapus.')) {
                return;
            }

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: "{{ route('user.tasks.submission.delete') }}",
                method: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}",
                    submission_id: submissionId
                },
                success: function(response) {
                    console.log('✅ Response success:', response);
                    if (response.success) {
                        toastr.success(response.message);
                        // 🔥 Langsung reload tanpa delay
                        location.reload(true);
                    } else {
                        toastr.error(response.message);
                        btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                    }
                },
                error: function(xhr) {
                    console.error('❌ Error:', xhr);
                    let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                    toastr.error(errorMsg);
                    btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                }
            });
        });

        console.log('✅ Semua event listener terpasang!');
    });
</script>
@endpush
