@extends('layouts.appAdmin')
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
            <h2 class="fw-bold">
                <i class="fas fa-check-double text-success me-2"></i>
                Review Submission
            </h2>
            <p class="text-muted mb-0">
                Proyek: <strong>{{ $project->title }}</strong> &nbsp;|&nbsp;
                Tugas: <strong>{{ $task->title }}</strong>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.tasks.manage', $project->id) }}"
               class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        {{-- KOLOM KIRI: Informasi + Statistik + Aksi Status --}}
        <div class="col-lg-4">
            {{-- Card Informasi Tugas --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary">
                    <h5 class="mb-0 text-white"><i class="fas fa-info-circle me-2"></i>Informasi Tugas</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Judul Tugas</label>
                        <p class="fw-bold mb-0">{{ $task->title }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Deskripsi</label>
                        <p class="mb-0">{{ $task->description ?? 'Tidak ada deskripsi' }}</p>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">
                            <label class="text-muted small mb-1">Status</label>
                            <span class="badge
                                @if($task->status == 'Completed') bg-success
                                @elseif($task->status == 'On Progress') bg-primary
                                @elseif($task->status == 'Review') bg-warning text-dark
                                @elseif($task->status == 'Rejected') bg-danger text-dark
                                @else bg-secondary
                                @endif
                            ">
                                {{ $task->status }}
                            </span>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small mb-1">Deadline</label>
                            <p class="mb-0">{{ \Carbon\Carbon::parse($task->deadline)->format('d M Y') }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="text-muted small mb-1">Prioritas</label>
                            <span class="badge
                                @if($task->priority == 'high') bg-danger
                                @elseif($task->priority == 'medium') bg-warning text-dark
                                @else bg-info
                                @endif
                            ">
                                {{ strtoupper($task->priority ?? 'LOW') }}
                            </span>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small mb-1">Karyawan</label>
                            <p class="mb-0">{{ $task->assignee->name ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Statistik Submission --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary">
                    <h5 class="mb-0 text-white"><i class="fas fa-chart-bar me-2"></i>Statistik Submission</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Submission</span>
                        <span class="badge bg-primary">{{ $submissions->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Menunggu Review</span>
                        <span class="badge bg-warning text-dark">
                            {{ $submissions->filter(fn($s) => $s->review_status == 'pending')->count() }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sudah direview</span>
                        <span class="badge bg-success">
                            {{ $submissions->filter(fn($s) => $s->review_status == 'accepted')->count() }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Pelaksanaan Status Tugas (dua tombol) --}}
            @if(!in_array($task->status, ['Completed', 'Rejected']))
            <div class="card shadow-sm">
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
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success">
                    <h5 class="mb-0 text-white"><i class="fas fa-file-alt me-2"></i>Riwayat Submission</h5>
                </div>
                <div class="card-body">
                    @foreach($submissions as $sub)
                        {{-- @php
                            $reviewStatus = $sub->review_status; // 'pending', 'approved', 'rejected'
                            $borderClass = $reviewStatus === 'pending' ? 'border-warning' : ($reviewStatus === 'approved' ? 'border-success' : 'border-danger');
                            $bgClass = $reviewStatus === 'pending' ? 'bg-warning bg-opacity-10' : ($reviewStatus === 'approved' ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10');
                            $badgeClass = $reviewStatus === 'pending' ? 'bg-warning text-dark' : ($reviewStatus === 'approved' ? 'bg-success' : 'bg-danger');
                            $statusLabel = $reviewStatus === 'pending' ? 'Menunggu Review' : ($reviewStatus === 'approved' ? 'Disetujui' : 'Ditolak');
                        @endphp --}}
                        @php
                            $reviewStatus = $sub->review_status;
                            $statusLabel = 'Unknown';
                            $badgeClass = 'bg-secondary';
                            $borderClass = 'border-secondary';
                            $bgClass = 'bg-secondary bg-opacity-10';
                            $icon = 'question';

                            if ($reviewStatus == 'pending') {
                                $statusLabel = 'Menunggu Review';
                                $badgeClass = 'bg-warning text-dark';
                                $borderClass = 'border-warning';
                                $bgClass = 'bg-warning bg-opacity-10';
                                $icon = 'clock';
                            } elseif ($reviewStatus == 'accepted') {
                                $statusLabel = 'Sudah Direview';
                                $badgeClass = 'bg-success';
                                $borderClass = 'border-success';
                                $bgClass = 'bg-success bg-opacity-10';
                                $icon = 'check';
                            } elseif ($reviewStatus == 'rejected') {
                                $statusLabel = 'Ditolak';
                                $badgeClass = 'bg-danger';
                                $borderClass = 'border-danger';
                                $bgClass = 'bg-danger bg-opacity-10';
                                $icon = 'times';
                            } elseif ($reviewStatus == 'revision needed') {
                                $statusLabel = 'Revisi Diperlukan';
                                $badgeClass = 'bg-warning text-dark';
                                $borderClass = 'border-warning';
                                $bgClass = 'bg-warning bg-opacity-10';
                                $icon = 'undo';
                            }
                        @endphp
                        <div class="border rounded p-3 mb-3 {{ $borderClass }} {{ $bgClass }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    {{-- Header --}}
                                    <div class="d-flex align-items-center mb-2 flex-wrap">
                                        <span class="fw-bold me-2">{{ $sub->created_at->format('d M Y, H:i') }}</span>
                                        <span class="badge {{ $badgeClass }}">
                                            <i class="fas fa-{{ $reviewStatus === 'pending' ? 'clock' : ($reviewStatus === 'approved' ? 'check' : 'times') }} me-1"></i>
                                            {{ $statusLabel }}
                                        </span>
                                    </div>

                                    {{-- Catatan submission (garis di kiri) --}}
                                    <div class="bg-white p-2 rounded border mb-2" style="border-left: 4px solid #0d6efd;">
                                        <small class="fw-bold">Catatan:</small>
                                        <p class="mb-0">{{ $sub->notes ?? 'Tidak ada catatan' }}</p>
                                    </div>

                                    {{-- Feedback (jika sudah ada review) --}}
                                    @if($sub->latest_review)
                                        <div class="p-2 rounded" style="background: #f8f9fa; border-right: 4px solid #28a745;">
                                            <small class="fw-bold text-success">Feedback:</small>
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
                                                            data-status="approved">
                                                        <i class="fas fa-check me-1"></i> Setujui
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
        const feedback_notes = notesContainer.find('.feedback-notes').val(); // ✅ variabel didefinisikan
        const btn = $(this);
        const originalText = btn.html();

        if (!feedback_notes.trim()) {
            toastr.warning('Silakan tulis feedback terlebih dahulu!');
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>');

        $.ajax({
            url: "{{ route('admin.submissions.review-feedback') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                submission_id: submissionId,
                feedback_notes: feedback_notes   // ✅ sekarang terdefinisi
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
            url: "{{ route('admin.tasks.update-status') }}",
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
