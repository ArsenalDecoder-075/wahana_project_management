@extends('layouts.appManager')
<title>Dashboard Manager</title>

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="row align-items-center mb-4 pt-10">
            <div class="col-md-6">
                <h2 class="text-bold">Anda Login sebagai Manager</h2>
            </div>
        </div>
        {{-- Row Konten Dashboard --}}
        {{-- Row 1: Notifikasi + Statistik + Progress --}}
        <div class="row">
            {{-- Kiri : Notifikasi Tugas Review --}}
            <div class="col-md-4 d-flex">
                <div class="card shadow-sm w-100">
                    <div class="card-header bg-warning text-white d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-bell me-2"></i>
                            <span class="fw-bold">Tugas Yang Butuh Review!</span>
                        </div>
                        @if(isset($reviewProjects) && count($reviewProjects) > 0)
                            <span class="badge bg-light text-dark rounded-pill">
                                {{ $totalReviewTasks }} Tugas
                            </span>
                        @endif
                    </div>
                    <div class="card-body d-flex flex-column">
                        @if(isset($reviewProjects) && count($reviewProjects) > 0)
                            <div class="flex-grow-1">
                                @foreach($reviewProjects as $item)
                                    <div class="mb-2">
                                        <a href="{{ route('manager.tasks.manage', $item['project']->id) }}"
                                        class="text-decoration-none">
                                            <div class="d-flex align-items-center p-2 rounded-3 hover-bg-light"
                                                style="transition: all 0.2s; border: 1px solid #f0f0f0;">
                                                <i class="fas fa-project-diagram text-primary me-2"></i>
                                                <span class="fw-semibold text-dark">{{ $item['project']->title }}</span>
                                                <span class="badge bg-danger ms-2 rounded-pill">
                                                    {{ $item['review_count'] }}
                                                </span>
                                                <small class="text-muted ms-1">Review</small>
                                            </div>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex-grow-1 d-flex align-items-center justify-content-center">
                                <div>
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <span class="text-muted">Tidak ada tugas yang menunggu review.</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tengah : Statistik Karyawan --}}
            <div class="col-md-4 d-flex">
                <div class="card shadow-sm w-100">
                    <div class="card-header bg-secondary">
                        <h5 class="mb-0 text-white"><i class="fas fa-chart-simple me-2"></i>Statistik Karyawan</h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="flex-grow-1">
                            @forelse($employeeStats as $stat)
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <div>
                                        <span class="fw-bold">{{ $stat['employee']->name }}</span>
                                        <div class="small text-muted">
                                            {{ $stat['completed'] }} selesai / {{ $stat['total'] }} tugas
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">{{ $stat['progress'] }}%</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted text-center">Belum ada karyawan bawahan.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kanan : Progress Proyek --}}
            <div class="col-md-4 d-flex">
                <div class="card shadow-sm w-100">
                    <div class="card-header bg-success">
                        <h5 class="mb-0 text-white"><i class="fas fa-project-diagram me-2"></i>Progress Proyek</h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="flex-grow-1">
                            @forelse($projectProgress as $item)
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="card-title fw-bold mb-0 text-truncate" style="max-width: 70%;">
                                            {{ $item['project']->title }}
                                        </h6>
                                        <span class="badge
                                            @if($item['status'] == 'Completed') bg-success
                                            @elseif($item['status'] == 'On Progress') bg-primary
                                            @else bg-warning text-dark
                                            @endif
                                        ">
                                            {{ $item['status'] }}
                                        </span>
                                    </div>
                                    <p class="small text-muted mb-1">
                                        {{ $item['completed_tasks'] }} / {{ $item['total_tasks'] }} tugas selesai
                                    </p>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar
                                            @if($item['progress'] == 100) bg-success
                                            @elseif($item['progress'] >= 50) bg-primary
                                            @elseif($item['progress'] >= 25) bg-warning
                                            @else bg-danger
                                            @endif
                                            progress-bar-striped progress-bar-animated"
                                            role="progressbar"
                                            style="width: {{ $item['progress'] }}%"
                                            aria-valuenow="{{ $item['progress'] }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100">
                                            {{ $item['progress'] }}%
                                        </div>
                                    </div>
                                    <div class="mt-1 text-center">
                                        <a href="{{ route('manager.tasks.manage', $item['project']->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-tasks me-1"></i> Lihat Tugas
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted text-center">Belum ada proyek yang dikelola.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // ============================================
    // GRAFIK KARYAWAN
    // ============================================
    const ctx = document.getElementById('employeeChart').getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Tugas Selesai',
                    data: @json($chartCompleted),
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Tugas Belum Selesai',
                    data: @json($chartPending),
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { size: 12 }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    title: {
                        display: true,
                        text: 'Jumlah Tugas'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Karyawan'
                    }
                }
            }
        }
    });
});
</script>
@endpush
