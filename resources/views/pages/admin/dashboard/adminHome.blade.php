@extends('layouts.appAdmin')
<title>Dashboard Admin</title>

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="row align-items-center mb-4 pt-10">
            <div class="col-md-6">
                <h2 class="text-bold">Anda login sebagai Admin</h2>
                <p class="text-muted mb-0">Kelola dan pantau sistem dari seluruh cabang</p>
            </div>
            <div class="col-md-6 text-end">
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Filter Dashboard</h5>
            </div>
            <div class="card-body">
                <form id="dashboardFilterForm" action="{{ route('admin.home') }}" method="GET">
                    <div class="row g-3">
                        <!-- Period Type -->
                        <div class="col-md-3">
                            <label class="form-label">Tipe Periode</label>
                            <select name="period_type" id="period_type" class="form-select" onchange="toggleDateFields()">
                                <option value="all" {{ $filters['period_type'] === 'all' ? 'selected' : '' }}>Semua
                                    Periode</option>
                                <option value="current_week"
                                    {{ $filters['period_type'] === 'current_week' ? 'selected' : '' }}>Minggu Ini</option>
                                <option value="custom" {{ $filters['period_type'] === 'custom' ? 'selected' : '' }}>Kustom
                                </option>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="col-md-4" id="dateRangeContainer"
                            style="{{ $filters['period_type'] === 'custom' ? '' : 'display: none;' }}">
                            <label class="form-label">Periode</label>
                            <div class="input-group">
                                <input type="date" class="form-control" name="start_date" id="start_date"
                                    value="{{ $filters['start_date'] ?? '' }}" onchange="updateEndDate(this.value)">
                                <span class="input-group-text bg-light">s/d</span>
                                <input type="date" class="form-control" name="end_date" id="end_date" readonly
                                    value="{{ $filters['end_date'] ?? '' }}">
                            </div>
                            <small class="text-muted">Pilih tanggal awal (Jumat)</small>
                        </div>

                        <!-- Area & City -->
                        <div class="col-md-2">
                            <label class="form-label">Area</label>
                            <select name="area" class="form-select">
                                <option value="all" {{ $filters['area'] === 'all' ? 'selected' : '' }}>Semua Area
                                </option>
                                @foreach ($areaLabels as $area)
                                    <option value="{{ $area }}" {{ $filters['area'] === $area ? 'selected' : '' }}>
                                        {{ $area }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Kota</label>
                            <select name="city" class="form-select">
                                <option value="all" {{ $filters['city'] === 'all' ? 'selected' : '' }}>Semua Kota
                                </option>
                                @foreach (['Jakarta', 'Tangerang', 'Luar Kota'] as $city)
                                    <option value="{{ $city }}"
                                        {{ $filters['city'] === $city ? 'selected' : '' }}>{{ $city }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="col-12 text-end">
                            <a href="{{ route('admin.home') }}" class="btn btn-outline-secondary me-2">Reset</a>
                            <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            @php
                $stats = [
                    [
                        'title' => 'Total Cabang',
                        'value' => $totalBranchCount ?? 0,
                        'icon' => 'lni-apartment',
                        'color' => 'primary',
                        'route' => 'admin.branch',
                    ],
                    [
                        'title' => 'Total Proyek',
                        'value' => $totalProjectCount ?? 0,
                        'icon' => 'lni-clipboard',
                        'color' => 'info',
                        'route' => 'admin.manage.project',
                    ],

                    [
                        'title' => 'Manager',
                        'value' => $totalManagerCount ?? 0,
                        'icon' => 'lni-user',
                        'color' => 'success',
                        'badge' => 'Admin',
                        'route' => 'admin.user',
                    ],
                    [
                        'title' => 'User/Worker',
                        'value' => $totalWorkerCount ?? 0,
                        'icon' => 'lni-user',
                        'color' => 'warning',
                        'badge' => 'User',
                        'route' => 'admin.user',
                    ],
                ];
            @endphp

            @foreach ($stats as $stat)
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card h-100 border shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="icon-lg bg-{{ $stat['color'] }} bg-opacity-10 rounded-circle">
                                    <i class="lni {{ $stat['icon'] }} text-{{ $stat['color'] }}"></i>
                                </div>
                                @if (isset($stat['badge']))
                                    <span
                                        class="badge bg-{{ $stat['color'] }} bg-opacity-10 text-{{ $stat['color'] }}">{{ $stat['badge'] }}</span>
                                @else
                                    <span
                                        class="badge bg-{{ $stat['color'] }} bg-opacity-10 text-{{ $stat['color'] }}">Total</span>
                                @endif
                            </div>
                            <h6 class="text-muted">{{ $stat['title'] }}</h6>
                            <h3 class="mb-0 mt-2">{{ number_format($stat['value']) }}</h3>
                            <div class="mt-2">
                                <a href="{{ route($stat['route']) }}" class="text-{{ $stat['color'] }} small">
                                    Lihat Detail <i class="lni lni-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Branch Distribution Chart -->
            {{-- <div class="col-xl-8 col-lg-7">
                <div class="card border shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0">
                            @if ($filters['period_type'] === 'all')
                                Distribusi Cabang per Area (Semua Periode)
                            @else
                                Distribusi Cabang per Area
                            @endif
                        </h5>
                        <small class="text-muted">
                            @if ($filters['period_type'] === 'all')
                                Grafik jumlah cabang per area
                            @else
                                Grafik jumlah cabang per area pada periode terpilih
                            @endif
                        </small>
                    </div>
                    <div class="card-body">
                        <canvas id="branchChart" height="300"></canvas>
                    </div>
                </div>
            </div> --}}

            <!-- Branch Project Status Chart -->
            {{-- lg gk guna!!! --}}
            {{-- <div class="col-xl-8 col-lg-7">
                <div class="card border shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0">
                            @if ($filters['period_type'] === 'all')
                                Jumlah Proyek per Cabang (Semua Periode)
                            @else
                                Jumlah Proyek per Cabang
                            @endif
                        </h5>
                        <small class="text-muted">
                            @if ($filters['period_type'] === 'all')
                                Grafik jumlah proyek per cabang berdasarkan status (selesai vs belum selesai)
                            @else
                                Grafik jumlah proyek per cabang pada periode terpilih
                            @endif
                        </small>
                    </div>
                    <div class="card-body">
                        <canvas id="branchProjectStatusChart" height="300"></canvas>
                    </div>
                </div>
            </div> --}}

        </div>
    </div>
@endsection
@push('scripts')
    <script>
        // Toggle date range visibility
        function toggleDateFields() {
            const periodType = document.getElementById('period_type').value;
            const dateRangeContainer = document.getElementById('dateRangeContainer');

            if (periodType === 'custom') {
                dateRangeContainer.style.display = '';
            } else {
                dateRangeContainer.style.display = 'none';
            }
        }

        // Update end date (add 6 days to start date for weekly period)
        function updateEndDate(startDate) {
            if (startDate) {
                const start = new Date(startDate);
                const end = new Date(start);
                end.setDate(start.getDate() + 6);

                const year = end.getFullYear();
                const month = String(end.getMonth() + 1).padStart(2, '0');
                const day = String(end.getDate()).padStart(2, '0');

                document.getElementById('end_date').value = `${year}-${month}-${day}`;
            }
        }

        // Chart.js - Branch Project Status Chart (Stacked Bar)
        document.addEventListener('DOMContentLoaded', function() {
            const branchCtx = document.getElementById('branchProjectStatusChart');
            if (!branchCtx) return;

            const ctx = branchCtx.getContext('2d');
            const labels = @json($branchProjectStatusData['labels'] ?? ['Tidak Ada Data']);
            const completedData = @json($branchProjectStatusData['completed'] ?? [0]);
            const pendingData = @json($branchProjectStatusData['pending'] ?? [0]);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Selesai (Completed)',
                            data: completedData,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'Belum Selesai (Pending/On Hold)',
                            data: pendingData,
                            backgroundColor: 'rgba(255, 193, 7, 0.7)',
                            borderColor: 'rgba(255, 193, 7, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true, // Stacked bar
                        },
                        y: {
                            stacked: true, // Stacked bar
                            beginAtZero: true,
                            min: 0,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' Proyek';
                                }
                            }
                        }
                    }
                }
            });
        });

        // Chart.js - Branch Distribution
        const branchCtx = document.getElementById('branchChart').getContext('2d');
        const branchChart = new Chart(branchCtx, {
            type: 'bar',
            data: {
                labels: @json($branchChartData['labels'] ?? []),
                datasets: [{
                    label: 'Jumlah Cabang',
                    data: @json($branchChartData['data'] ?? []),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Chart.js - User Type Donut Chart
        const userTypeCtx = document.getElementById('userTypeChart').getContext('2d');
        const userTypeChart = new Chart(userTypeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Admin', 'Manager', 'User'],
                datasets: [{
                    data: [
                        {{ $adminCount ?? 0 }},
                        {{ $managerCount ?? 0 }},
                        {{ $userCount ?? 0 }}
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(23, 162, 184, 0.7)',
                        'rgba(255, 193, 7, 0.7)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(23, 162, 184, 1)',
                        'rgba(255, 193, 7, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
@endpush
