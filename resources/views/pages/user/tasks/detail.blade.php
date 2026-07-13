@extends('layouts.app')
<title>Detail Tugas - {{ $task->title }}</title>

@section('content')
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

        <!-- Informasi Tugas -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="mb-2">{{ $task->title }}</h4>
                        @if($task->description)
                            <p class="text-muted">{{ $task->description }}</p>
                        @else
                            <p class="text-muted"><em>Tidak ada deskripsi</em></p>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <div class="border-start ps-3">
                            <div class="mb-2">
                                <span class="text-muted">Status:</span>
                                @if($task->status == 'Pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($task->status == 'On Progress')
                                    <span class="badge bg-info">On Progress</span>
                                @elseif($task->status == 'Completed')
                                    <span class="badge bg-success">Completed</span>
                                @endif
                            </div>
                            <div class="mb-2">
                                <span class="text-muted">Bobot:</span>
                                <strong>{{ $task->weight }}%</strong>
                            </div>
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
                            <div>
                                <span class="text-muted">Dibuat oleh:</span>
                                <strong>{{ $task->creator->name ?? '-' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Submit Catatan -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-pen me-2"></i>
                    Kirim Catatan Tugas
                </h5>
            </div>
            <div class="card-body">
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
                    <form id="submitNotesForm" method="POST" action="{{ route('user.tasks.submitNotes') }}">
                        @csrf
                        <input type="hidden" name="task_id" value="{{ $task->id }}">
                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                            <textarea class="form-control" id="notes" name="notes" rows="5" placeholder="Tulis catatan atau penjelasan tentang pengerjaan tugas ini..."></textarea>
                        </div>
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
            </div>
        </div>

        <!-- Riwayat Submission (opsional) -->
        @if($task->submissions->count() > 1)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Riwayat Catatan
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Catatan</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($task->submissions->sortByDesc('created_at') as $index => $sub)
                                @if($sub->employee_id == Auth::id())
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $sub->notes ?? '-' }}</td>
                                    <td>
                                        @if($sub->status == 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($sub->status == 'reviewed')
                                            <span class="badge bg-success">Reviewed</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $sub->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $sub->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection

@push('script')
<script>
$(document).ready(function() {
    $('#submitNotesForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const btn = $('#submitNotesBtn');
        const originalText = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Mengirim...');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    alert('Catatan berhasil dikirim!');
                    location.reload();
                } else {
                    alert('Gagal: ' + response.message);
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                let errorMsg = 'Terjadi kesalahan saat mengirim catatan';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endpush
