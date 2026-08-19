@extends('layouts.app')
<title>Daftar Tugas - {{ $project->title }}</title>

@section('content')
    <div class="container-fluid">
        <div class="row align-items-center mb-4">
            <div class="col-md-8">
                <h2 class="text-bold">
                    <i class="fas fa-tasks text-primary me-2 pt-5"></i>
                    Tugas Anda di Proyek: {{ $project->title }}
                </h2>
                <div class="pt-2 mt-sm-0">
                    {{-- Manajer --}}
                    <span class="badge bg-primary me-1 mb-1">
                        <i class="fas fa-user-tie me-1"></i> Manajer: {{ $project->manager->name ?? 'Tidak ada' }}
                    </span>
                    {{-- Periode Pekerjaan --}}
                    <span class="badge bg-secondary me-1 mb-1">
                        <i class="fas fa-calendar-alt me-1"></i> Periode: {{ \Carbon\Carbon::parse($project->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($project->end_date)->format('d M Y') }}
                    </span>
                    {{-- Kategori --}}
                    <span class="badge bg-dark me-1 mb-1">
                        <i class="fas fa-tag me-1"></i>
                        {{ $project->category?->name ?? 'Tanpa Kategori' }}
                    </span>
                    {{-- Progress --}}
                    @php
                        $total = $tasks->count();
                        $completed = $tasks->where('status', 'Completed')->count();
                        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
                    @endphp

                    <span class="badge bg-success me-1 mb-1">
                        <i class="fas fa-chart-line me-1"></i> Progress: {{ $completed }}/{{ $total }}
                    </span>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <a href="{{ route('user.dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @if($tasks->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="border-collapse: collapse; width: 100%;">
                            <thead>
                                <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="width: 5%; text-align: center; padding: 12px 8px; border: 1px solid #dee2e6;">#</th>
                                    <th style="width: 45%; padding: 12px 8px; border: 1px solid #dee2e6;">Judul Tugas</th>
                                    <th style="width: 10%; text-align: center; padding: 12px 8px; border: 1px solid #dee2e6;">Prioritas</th>
                                    <th style="width: 20%; text-align: center; padding: 12px 8px; border: 1px solid #dee2e6;">Deadline</th>
                                    <th style="width: 10%; text-align: center; padding: 12px 8px; border: 1px solid #dee2e6;">Status</th>
                                    <th style="width: 10%; text-align: center; padding: 12px 8px; border: 1px solid #dee2e6;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tasks as $index => $task)
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="text-align: center; padding: 12px 8px; border: 1px solid #dee2e6;">{{ $index + 1 }}</td>
                                        <td style="padding: 12px 8px; border: 1px solid #dee2e6;">
                                            <strong>{{ $task->title }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $task->description }}</small>
                                        </td>
                                        <td style="text-align: center; padding: 12px 8px; border: 1px solid #dee2e6;">
                                            @php
                                                $badges = [
                                                    'high'   => '<span class="badge text-bold bg-danger"><i class="fas fa-arrow-up"></i> HIGH</span>',
                                                    'medium' => '<span class="badge text-bold bg-warning text-dark"><i class="fas fa-minus"></i> MEDIUM</span>',
                                                    'low'    => '<span class="badge text-bold bg-info"><i class="fas fa-arrow-down"></i> LOW</span>'
                                                ];
                                            @endphp
                                            {!! $badges[$task->priority] ?? '<span class="badge text-bold bg-secondary">-</span>' !!}
                                        </td>
                                        <td style="text-align: center; padding: 12px 8px; border: 1px solid #dee2e6;">
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
                                        <td style="text-align: center; padding: 12px 8px; border: 1px solid #dee2e6;">
                                            @if($task->status == 'Pending')
                                                <span class="badge bg-warning text-dark" style="padding: 6px 12px;">
                                                    <i class="fas fa-clock me-1"></i> Pending
                                                </span>
                                            @elseif($task->status == 'On Progress')
                                                <span class="badge bg-info" style="padding: 6px 12px;">
                                                    <i class="fas fa-spinner me-1"></i> On Progress
                                                </span>
                                            @elseif($task->status == 'Review')
                                                <span class="badge bg-secondary" style="padding: 6px 12px;">
                                                    <i class="fa-solid fa-magnifying-glass"></i> Review
                                                </span>
                                            @elseif($task->status == 'Completed')
                                                <span class="badge bg-success" style="padding: 6px 12px;">
                                                    <i class="fas fa-check me-1"></i> Completed
                                                </span>
                                                @elseif($task->status == 'Rejected')
                                                    <span class="badge bg-danger" style="padding: 6px 12px;">
                                                        <i class="fa-regular fa-circle-xmark"></i> Rejected
                                                    </span>
                                            @else
                                                <span class="badge bg-secondary" style="padding: 6px 12px;">
                                                    {{ $task->status }}
                                                </span>
                                            @endif
                                        </td>
                                        <td style="text-align: center; padding: 12px 8px; border: 1px solid #dee2e6;">
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
