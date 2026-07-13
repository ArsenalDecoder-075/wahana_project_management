@extends('layouts.app')
<title>Dashboard User</title>

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <h2 class="text-bold">Dashboard User</h2>
                <p class="text-muted">Selamat datang, {{ Auth::user()->name }}</p>
            </div>
        </div>

        <!-- Daftar Proyek -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-project-diagram me-2"></i>
                            Proyek yang Melibatkan Anda
                            <span class="badge bg-secondary ms-2">{{ $projects->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($projects->count() > 0)
                            <div class="row">
                                @foreach($projects as $project)
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <i class="fas fa-folder-open text-primary me-2"></i>
                                                    {{ $project->title }}
                                                </h5>
                                                <p class="card-text text-muted small">
                                                    {{ Str::limit($project->description ?? 'Tidak ada deskripsi', 100) }}
                                                </p>
                                                <div class="mb-2">
                                                    <span class="badge bg-secondary me-1">
                                                        <i class="fas fa-user-tie me-1"></i>
                                                        {{ $project->manager->name ?? 'Tanpa Manajer' }}
                                                    </span>
                                                    <span class="badge bg-info">
                                                        <i class="far fa-calendar-alt me-1"></i>
                                                        {{ \Carbon\Carbon::parse($project->start_date)->format('d M Y') }}
                                                        -
                                                        {{ \Carbon\Carbon::parse($project->end_date)->format('d M Y') }}
                                                    </span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                    <div>
                                                        <span class="badge bg-warning text-dark me-1">
                                                            Pending: {{ $project->pending_tasks }}
                                                        </span>
                                                        <span class="badge bg-info me-1">
                                                            On Progress: {{ $project->on_progress_tasks }}
                                                        </span>
                                                        <span class="badge bg-success">
                                                            Completed: {{ $project->completed_tasks }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="fw-bold">{{ $project->progress_percentage }}%</span>
                                                        <div class="progress" style="height: 6px; width: 80px; display: inline-block;">
                                                            <div class="progress-bar bg-success" role="progressbar"
                                                                 style="width: {{ $project->progress_percentage }}%;"
                                                                 aria-valuenow="{{ $project->progress_percentage }}"
                                                                 aria-valuemin="0"
                                                                 aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent text-center">
                                                <a href="{{ route('user.projects.tasks', $project->id) }}" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-tasks me-1"></i> Lihat Tugas
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Belum ada proyek yang melibatkan Anda</h5>
                                <p class="text-muted">Anda akan melihat proyek di sini setelah diberi tugas oleh manajer.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
