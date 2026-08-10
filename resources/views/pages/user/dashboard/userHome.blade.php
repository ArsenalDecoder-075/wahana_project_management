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

        {{-- Kartu Statistik --}}
        @php
            $totalTasks = 0;
            $completedTasks = 0;
            foreach($projects as $project) {
                $totalTasks += $project->total_tasks;
                $completedTasks += $project->completed_tasks;
            }
            $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
        @endphp

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Tugas</h6>
                                <h2 class="mb-0 text-white">{{ $totalTasks }}</h2>
                            </div>
                            <i class="fas fa-tasks fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Selesai</h6>
                                <h2 class="mb-0 text-white">{{ $completedTasks }}</h2>
                            </div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Progress</h6>
                                <h2 class="mb-0 text-white">{{ $completionRate }}%</h2>
                            </div>
                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart Statistik --}}
        <div class="row mb-4">
            <!-- Status Distribution Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Distribusi Status Tugas
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" height="280"></canvas>
                    </div>
                </div>
            </div>

            <!-- Project Progress Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Progress per Proyek
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="projectChart" height="280"></canvas>
                    </div>
                </div>
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

                                                {{-- Badge Status --}}
                                                <div class="mb-2">
                                                    Pending: {{ $project->pending_tasks ?? 0 }}
                                                    <br>
                                                    On Progress: {{ $project->on_progress_tasks ?? 0 }}
                                                    <br>
                                                    Completed: {{ $project->completed_tasks ?? 0 }}
                                                    </span>
                                                </div>

                                                {{-- Progress Bar Full Width dengan Label --}}
                                                @php
                                                    $progress = $project->progress_percentage ?? 0;
                                                    $barColor = $progress >= 100 ? 'bg-success' : ($progress >= 50 ? 'bg-info' : ($progress >= 25 ? 'bg-warning' : 'bg-danger'));
                                                @endphp

                                                <div class="mt-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small class="text-muted">
                                                            <i class="fas fa-chart-line me-1"></i> Progress Proyek
                                                        </small>
                                                    </div>
                                                    <div class="progress" style="height: 20px; border-radius: 4px; background-color: #e9ecef;">
                                                        <div class="progress-bar {{ $barColor }} d-flex align-items-center justify-content-center fw-bold"
                                                            role="progressbar"
                                                            style="width: {{ $progress }}%; font-size: 12px; transition: width 0.5s ease;"
                                                            aria-valuenow="{{ $progress }}"
                                                            aria-valuemin="0"
                                                            aria-valuemax="100">
                                                            @if($progress > 5)
                                                                {{ $progress }}%
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between mt-1">
                                                        <small class="text-muted">Mulai</small>
                                                        <small class="text-muted">{{ $project->completed_tasks ?? 0 }}/{{ $project->total_tasks ?? 0 }} tugas</small>
                                                        <small class="text-muted">Selesai</small>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== COLORS =====
    const colors = {
        pending: '#ffc107',
        progress: '#0dcaf0',
        review: '#6f42c1',
        completed: '#198754',
        rejected: '#dc3545'
    };

    // ===== 1. STATUS DISTRIBUTION CHART (Doughnut) =====
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'On Progress', 'Review', 'Completed', 'Rejected'],
            datasets: [{
                data: [
                    {{ $statusStats['Pending'] ?? 0 }},
                    {{ $statusStats['On Progress'] ?? 0 }},
                    {{ $statusStats['Review'] ?? 0 }},
                    {{ $statusStats['Completed'] ?? 0 }},
                    {{ $statusStats['Rejected'] ?? 0 }}
                ],
                backgroundColor: [
                    colors.pending,
                    colors.progress,
                    colors.review,
                    colors.completed,
                    colors.rejected
                ],
                borderWidth: 3,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            return context.label + ': ' + context.parsed + ' tugas (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // ===== 2. PROJECT PROGRESS CHART (Bar) =====
    @if($projectChartData->count() > 0)
        const projectCtx = document.getElementById('projectChart').getContext('2d');
        new Chart(projectCtx, {
            type: 'bar',
            data: {
                labels: [
                    @foreach($projectChartData as $data)
                        '{{ Str::limit($data['name'], 20) }}',
                    @endforeach
                ],
                datasets: [
                    {
                        label: 'Total Tugas',
                        data: [
                            @foreach($projectChartData as $data)
                                {{ $data['total'] }},
                            @endforeach
                        ],
                        backgroundColor: 'rgba(13, 110, 253, 0.5)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 2
                    },
                    {
                        label: 'Selesai',
                        data: [
                            @foreach($projectChartData as $data)
                                {{ $data['completed'] }},
                            @endforeach
                        ],
                        backgroundColor: 'rgba(25, 135, 84, 0.6)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    @else
        // Show message if no projects
        document.getElementById('projectChart').parentElement.innerHTML =
            '<div class="text-center py-5"><i class="fas fa-inbox fa-3x text-muted mb-3"></i><p class="text-muted">Belum ada data proyek</p></div>';
    @endif
});
</script>
@endpush

