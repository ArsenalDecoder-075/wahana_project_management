@extends('layouts.appManager')
@section('title', 'Review Submission - ' . $task->title)

{{-- Push CSS for toastr --}}
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endpush

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold"><i class="fas fa-check-double text-success me-2"></i>Review Submission</h2>
            <p class="text-muted mb-0">Proyek: <strong>{{ $project->title }}</strong></p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('manager.tasks.manage', $project->id) }}"
               class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row" style="display: flex; align-items: stretch;">
        {{-- KOLOM KIRI: Informasi + Statistik + Aksi Status --}}
        <div class="col-lg-4 d-flex flex-column">
            {{-- Card Informasi Tugas & Statistik --}}
            <div class="card shadow-sm mb-4 flex-shrink-0">
                <div class="card-header bg-primary">
                    <h5 class="mb-0 text-white"><i class="fas fa-info-circle me-2"></i>Informasi Tugas</h5>
                </div>
                <div class="card-body">
                    {{-- Judul & Deskripsi --}}
                    <div class="mb-3">
                        <p class="fw-bold mb-1">{{ $task->title }}</p>
                        <p class="text-muted mb-0">{{ $task->description ?? 'Tidak ada deskripsi' }}</p>
                        <p class="text-muted small mt-1 mb-0">
                            <i class="fas fa-user me-1"></i> Dikerjakan Oleh: <strong>{{ $task->assignee->name ?? '-' }}</strong>
                        </p>
                    </div>

                    {{-- Divider --}}
                    <hr>

                    {{-- Kiri (Status, Deadline, Prioritas) & Kanan (Statistik Submission) --}}
                    <div class="row">
                        {{-- Kolom Kiri --}}
                        <div class="col-6">
                            <div class="mb-2">
                                <span class="text-muted small d-block">Status</span>
                                @php
                                    $statusBadges = [
                                        'Pending'     => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pending</span>',
                                        'On Progress' => '<span class="badge bg-info text-dark"><i class="fas fa-spinner fa-spin me-1"></i> On Progress</span>',
                                        'Review'      => '<span class="badge bg-secondary text-white"><i class="fas fa-search me-1"></i> Review</span>',
                                        'Completed'   => '<span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i> Completed</span>',
                                        'Rejected'    => '<span class="badge bg-danger text-white"><i class="fas fa-times-circle me-1"></i> Rejected</span>'
                                    ];
                                @endphp
                                {!! $statusBadges[$task->status] ?? '<span class="badge bg-secondary text-white">' . $task->status . '</span>' !!}
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small d-block">Deadline</span>
                                <span class="badge bg-dark text-white">
                                    <i class="fas fa-calendar-alt"></i> {{ \Carbon\Carbon::parse($task->deadline)->format('d M Y') }}
                                </span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small d-block">Prioritas</span>
                                @php
                                    $priorityBadges = [
                                        'high'   => '<span class="badge bg-danger text-white"><i class="fas fa-arrow-up me-1"></i> HIGH</span>',
                                        'medium' => '<span class="badge bg-warning text-dark"><i class="fas fa-minus me-1"></i> MEDIUM</span>',
                                        'low'    => '<span class="badge bg-info text-white"><i class="fas fa-arrow-down me-1"></i> LOW</span>'
                                    ];
                                @endphp
                                {!! $priorityBadges[$task->priority] ?? '<span class="badge bg-secondary text-white">' . strtoupper($task->priority ?? 'LOW') . '</span>' !!}
                            </div>
                        </div>

                        {{-- Kolom Kanan --}}
                        <div class="col-6">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total Pengajuan</span>
                                <span class="fw-bold">{{ $submissions->count() }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Menunggu Review</span>
                                <span class="fw-bold text-primary">
                                    {{ $submissions->filter(fn($s) => $s->review_status == 'pending')->count() }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Sudah Direview</span>
                                <span class="fw-bold text-success">
                                    {{ $submissions->filter(fn($s) => $s->review_status == 'accepted')->count() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pelaksanaan Status Tugas (dua tombol) --}}
            @if(!in_array($task->status, ['Completed', 'Rejected']))
            <div class="card shadow-sm mb-4 flex-shrink-0">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Pelaksanaan Status Tugas</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Apakah tugas ini memenuhi kebutuhan?</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-success change-status-btn"
                                data-task-id="{{ $task->id }}"
                                data-status="Completed">
                            <i class="fas fa-check me-2"></i> Iya, Setuju (Complete)
                        </button>
                        <button class="btn btn-danger change-status-btn"
                                data-task-id="{{ $task->id }}"
                                data-status="Rejected">
                            <i class="fas fa-times me-2"></i> Tidak, Menolak (Reject)
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- KOLOM KANAN: Riwayat Submission --}}
        <div class="col-lg-8 d-flex">
            <div class="card shadow-sm w-100 d-flex flex-column">
                <div class="card-header bg-success flex-shrink-0">
                    <h5 class="mb-0 text-white"><i class="fas fa-file-alt me-2"></i>Riwayat Submission</h5>
                </div>
                <div class="card-body flex-grow-1 overflow-auto" style="max-height: 600px;">
                    @foreach($submissions as $sub)
                        @php
                            $reviewStatus = $sub->review_status;
                            $statusLabel = 'Unknown';
                            $badgeClass = 'bg-secondary';
                            $borderClass = 'border-secondary';
                            $bgClass = 'bg-secondary bg-opacity-10';
                            // $icon = 'question';

                            if ($reviewStatus == 'pending') {
                                $statusLabel = 'Menunggu Review';
                                $badgeClass = 'bg-info text-dark';
                                $borderClass = 'border-info';
                                $bgClass = 'bg-info bg-opacity-10';
                                $textClass = 'text-dark';
                                // $icon = 'clock';
                            } elseif ($reviewStatus == 'accepted') {
                                $statusLabel = 'Sudah Direview';
                                $badgeClass = 'bg-success';
                                $borderClass = 'border-success';
                                $bgClass = 'bg-success bg-opacity-10';
                                $textClass = 'text-success';
                                // $icon = 'check';
                            } elseif ($reviewStatus == 'rejected') {
                                $statusLabel = 'Ditolak';
                                $badgeClass = 'bg-danger';
                                $borderClass = 'border-danger';
                                $bgClass = 'bg-danger bg-opacity-10';
                                $textClass = 'text-danger';
                                $icon = 'times';
                            } elseif ($reviewStatus == 'revision needed') {
                                $statusLabel = 'Revisi Diperlukan';
                                $badgeClass = 'bg-warning text-dark';
                                $borderClass = 'border-warning';
                                $bgClass = 'bg-warning bg-opacity-10';
                                $textClass = 'text-warning';
                                // $icon = 'undo';
                            }
                        @endphp
                        <div class="border rounded p-3 mb-3 {{ $borderClass }} {{ $bgClass }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    {{-- Header --}}
                                    <div class="d-flex align-items-center mb-2 flex-wrap">
                                        <span class="fw-bold me-2">{{ $sub->created_at->format('d M Y, H:i') }}</span>
                                        <span class="badge {{ $badgeClass }}">
                                            <i class="fas fa-{{ $reviewStatus === 'pending' ? 'clock' : ($reviewStatus === 'accepted' ? 'check' : 'times') }} me-1"></i>
                                            {{ $statusLabel }}
                                        </span>
                                    </div>

                                    {{-- Catatan submission (garis di kiri) --}}
                                    <div class="bg-white p-2 rounded border mb-2" style="border-left: 4px solid #0d6efd;">
                                        <small class="fw-bold">Catatan:</small>
                                        <p class="mb-0">{{ $sub->notes ?? 'Tidak ada catatan' }}</p>
                                    </div>

                                    {{-- Tampilan gambar kalo ada --}}
                                    @if($sub->files->count() > 0)
                                    <div class="mt-2">
                                        <small class="fw-bold">Lampiran:</small>
                                        <div class="d-flex flex-wrap gap-2 mt-1">
                                            @foreach($sub->files as $file)
                                                @php
                                                    $isImage = in_array($file->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
                                                @endphp
                                                @if($isImage)
                                                    <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="d-inline-block">
                                                        <img src="{{ asset('storage/' . $file->file_path) }}"
                                                            alt="{{ $file->file_name }}"
                                                            style="width: 100%; height: auto; max-width: 100%; border-radius: 8px; border: 1px solid #ddd; object-fit: contain;">
                                                    </a>
                                                @else
                                                    <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-file me-1"></i> {{ $file->file_name }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Feedback (jika sudah ada review) --}}
                                    @if($sub->latest_review)
                                        <div class="p-2 rounded" style="background: #f8f9fa; border-right: 4px solid #28a745;">
                                            <small class="fw-bold {{ $textClass }}">Feedback:</small>
                                            <p class="mb-1">{{ $sub->latest_review->feedback_notes ?? 'Tidak ada catatan' }}</p>
                                            <small class="text-muted">
                                                Oleh: {{ $sub->latest_review->reviewer->name ?? 'Unknown' }}
                                                pada {{ $sub->latest_review->reviewed_at ? $sub->latest_review->reviewed_at->format('d M Y, H:i') : '-' }}
                                            </small>
                                        </div>
                                    @endif

                                    {{-- Tombol Beri Feedback (hanya jika pending) --}}
                                    @if($reviewStatus === 'pending')
                                        <div class="mt-3">
                                            <button class="btn btn-sm btn-primary toggle-feedback-btn"
                                                    data-id="{{ $sub->id }}">
                                                <i class="fas fa-pen me-1"></i> Berikan Feedback
                                            </button>
                                            <div class="feedback-container mt-2" id="feedback-{{ $sub->id }}" style="display: none;">
                                                <textarea class="form-control form-control-sm feedback-notes"
                                                          rows="2"
                                                          placeholder="Tulis feedback/komentar..."></textarea>
                                                <div class="mt-2">
                                                    <button class="btn btn-sm btn-success submit-feedback-btn"
                                                            data-id="{{ $sub->id }}"
                                                            data-status="accepted">
                                                        <i class="fas fa-check me-1"></i> Setujui
                                                    </button>
                                                    <button class="btn btn-sm btn-warning submit-feedback-btn"
                                                            data-id="{{ $sub->id }}"
                                                            data-status="revision needed">
                                                        <i class="fas fa-check me-1"></i> Butuh Revisi
                                                    </button>
                                                    <button class="btn btn-sm btn-danger submit-feedback-btn"
                                                            data-id="{{ $sub->id }}"
                                                            data-status="rejected">
                                                        <i class="fas fa-times me-1"></i> Tolak
                                                    </button>
                                                    <button class="btn btn-sm btn-secondary cancel-feedback-btn"
                                                            data-id="{{ $sub->id }}">
                                                        Batal
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
$(document).ready(function() {
    // Toggle untuk buka textbox feedback
    $('.toggle-feedback-btn').on('click', function() {
        const id = $(this).data('id');
        $('#feedback-' + id).toggle();
        $(this).hide();
    });

    // Feedback gk jadi
    $('.cancel-feedback-btn').on('click', function() {
        const id = $(this).data('id');
        $('#feedback-' + id).hide();
        $('.toggle-feedback-btn[data-id="' + id + '"]').show();
    });

    // Submit Feedback
    $('.submit-feedback-btn').on('click', function() {
        const submissionId = $(this).data('id');
        const notesContainer = $('#feedback-' + submissionId);
        const status = $(this).data('status'); // ✅ Ambil status dari tombol
        const feedback_notes = notesContainer.find('.feedback-notes').val(); // ✅ variabel didefinisikan
        const btn = $(this);
        const originalText = btn.html();

        if (!feedback_notes.trim()) {
            toastr.warning('Silakan tulis feedback terlebih dahulu!');
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>');

        $.ajax({
            url: "{{ route('manager.submissions.review-feedback') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                submission_id: submissionId,
                feedback_notes: feedback_notes,   // sekarang terdefinisi
                status: status // Kirim status ke server
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message);
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Ubah Status Tugas
    $('.change-status-btn').on('click', function() {
        const taskId = $(this).data('task-id');
        const status = $(this).data('status');
        const statusText = status === 'Completed' ? 'Complete' : 'Reject (On Progress)';

        if (!confirm('Anda yakin ingin mengubah status tugas menjadi ' + statusText + '?')) {
            return;
        }

        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>');

        $.ajax({
            url: "{{ route('manager.tasks.update-status') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                task_id: taskId,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message);
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endpush
