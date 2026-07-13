@extends('layouts.app')
<title>Daftar Tugas - {{ $project->title }}</title>

@section('content')
    <div class="container-fluid">
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <h2 class="text-bold">
                    <i class="fas fa-tasks text-primary me-2"></i>
                    Tugas Anda di Proyek: {{ $project->title }}
                </h2>
                <p class="text-muted">
                    Manajer: {{ $project->manager->name ?? 'Tidak ada' }} &nbsp;|&nbsp;
                    Periode: {{ \Carbon\Carbon::parse($project->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($project->end_date)->format('d M Y') }}
                </p>
            </div>
            <div class="col-md-6 text-end">
                <a href="{{ route('user.dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Daftar Tugas Anda
                    <span class="badge bg-secondary ms-2">{{ $tasks->count() }}</span>
                </h5>
            </div>
            <div class="card-body">
                @if($tasks->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 5%;" class="text-center">#</th>
                                    <th style="width: 30%;">Judul Tugas</th>
                                    <th style="width: 15%;" class="text-center">Bobot</th>
                                    <th style="width: 18%;" class="text-center">Deadline</th>
                                    <th style="width: 17%;" class="text-center">Status</th>
                                    <th style="width: 15%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tasks as $index => $task)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $task->title }}</strong>
                                            @if($task->description)
                                                <br>
                                                <small class="text-muted">{{ Str::limit($task->description, 60) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold">{{ $task->weight }}%</span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $deadline = \Carbon\Carbon::parse($task->deadline);
                                                $today = \Carbon\Carbon::now();
                                                $daysLeft = $today->diffInDays($deadline, false);
                                            @endphp
                                            <div>
                                                <i class="far fa-calendar-alt me-1"></i>
                                                {{ $deadline->format('d M Y') }}
                                                @if($task->status != 'Completed')
                                                    @if($daysLeft < 0)
                                                        <br><span class="badge bg-danger mt-1">Terlambat</span>
                                                    @elseif($daysLeft <= 3)
                                                        <br><span class="badge bg-warning text-dark mt-1">Mendesak</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($task->status == 'Pending')
                                                <span class="badge bg-warning text-dark" style="padding: 6px 12px;">
                                                    <i class="fas fa-clock me-1"></i> Pending
                                                </span>
                                            @elseif($task->status == 'On Progress')
                                                <span class="badge bg-info" style="padding: 6px 12px;">
                                                    <i class="fas fa-spinner me-1"></i> On Progress
                                                </span>
                                            @elseif($task->status == 'Completed')
                                                <span class="badge bg-success" style="padding: 6px 12px;">
                                                    <i class="fas fa-check me-1"></i> Completed
                                                </span>
                                            @else
                                                <span class="badge bg-secondary" style="padding: 6px 12px;">
                                                    {{ $task->status }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('user.tasks.detail', $task->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada tugas dalam proyek ini untuk Anda</h5>
                        <p class="text-muted">Anda belum ditugaskan untuk tugas apapun di proyek ini.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
