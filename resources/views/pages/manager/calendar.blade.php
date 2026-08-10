@extends('layouts.appManager')
@section('title', 'Kalender Tugas')

@section('content')
<style>
    /* --- KONTAINER UTAMA --- */
    .calendar-wrapper {
        position: relative;
        padding: 0 10px;
    }

    /* --- HEADER TANGGAL (kolom atas) --- */
    .calendar-header-row {
        display: flex;
        border-bottom: 2px solid #dee2e6;
        background: #f8f9fa;
        font-weight: bold;
        height: 40px;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 50;
    }
    .header-priority-label {
        width: 120px;
        flex-shrink: 0;
        padding-left: 10px;
        font-size: 14px;
        color: #495057;
        background: #fff;
        border-right: 2px solid #dee2e6;
    }
    .header-day {
        flex: 1;
        text-align: center;
        min-width: 30px;
        font-size: 12px;
        color: #495057;
        border-left: 1px solid #f0f0f0;
    }
    .header-day.weekend {
        color: #dc3545;
    }

    /* --- BARIS PRIORITAS --- */
    .priority-row {
        display: flex;
        align-items: stretch; /* ← kunci: label dan area tugas sama tinggi */
        border-bottom: 1px solid #f0f0f0;
        height: 60px; /* sama dengan rowHeight di JavaScript */
        position: relative;
        background: #fff;
    }
    .priority-label {
        width: 120px;
        flex-shrink: 0;
        padding-left: 10px;
        font-weight: bold;
        font-size: 14px;
        display: flex;
        align-items: center;
        background: #f8f9fa;
        border-right: 2px solid #e9ecef;
        z-index: 5;
        height: 100%;
    }
    .priority-label.low { background: #e0f7fa; color: #006064; }
    .priority-label.medium { background: #fff9c4; color: #f57f17; }
    .priority-label.high { background: #ffcdd2; color: #b71c1c; }
    .priority-label.low { background: #e0f7fa; color: #006064; }
    .priority-label.medium { background: #fff9c4; color: #f57f17; }
    .priority-label.high { background: #ffcdd2; color: #b71c1c; }

    /* --- AREA TUGAS DI DALAM BARIS --- */
    .task-area {
        flex: 1;
        position: relative;
        height: 100%;
        min-height: 60px;
        background: #fff;
    }

    /* --- BLOK TUGAS --- */
    .task-block {
        position: absolute;
        box-sizing: border-box;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        white-space: normal;
        color: #fff;
        overflow: hidden;
        cursor: pointer;
        z-index: 10;
        border-left: 4px solid rgba(255,255,255,0.4);
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
        line-height: 1.3;
    }
    .task-block:hover {
        z-index: 20;
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        filter: brightness(1.15);
    }

    /* Warna status (sama seperti sebelumnya) */
    .status-pending { background: #ffc107; color: #212529; }
    .status-onprogress { background: #0d6efd; }
    .status-review { background: #6c757d; }
    .status-completed { background: #198754; }
    .status-rejected { background: #dc3545; }

    /* Legend */
    .legend { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px; }
    .legend-item { display: flex; align-items: center; gap: 5px; }
    .legend-color { width: 18px; height: 18px; border-radius: 4px; }
</style>

<div class="container-fluid">
    <div class="row align-items-center mb-4 pt-10">
        <div class="col-md-6">
            <h2 class="text-bold">
                <i class="fas fa-calendar-alt text-primary me-2"></i>
                Kalender Tugas (Manager)
            </h2>
            <p class="text-muted">Semua tugas dari semua proyek, dikelompokkan berdasarkan prioritas</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('manager.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
            <div>
                <i class="fas fa-calendar-alt me-2"></i>
                <span class="fw-bold">Daftar Tugas Bulanan</span>
            </div>
            <div>
                <button class="btn btn-sm btn-light me-1" id="prevMonth">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="fw-bold text-white" id="monthLabel">Loading...</span>
                <button class="btn btn-sm btn-light ms-1" id="nextMonth">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="btn btn-sm btn-outline-light ms-2" id="todayBtn">
                    <i class="fas fa-calendar-day me-1"></i> Hari Ini
                </button>
            </div>
        </div>
        <div class="card-body p-0" style="overflow-x: auto;">
            <div id="calendarContainer" class="calendar-wrapper">
                {{-- Akan diisi oleh JavaScript --}}
            </div>
        </div>
    </div>

    <div class="legend">
        <div class="legend-item"><span class="legend-color" style="background:#ffc107;"></span> Pending</div>
        <div class="legend-item"><span class="legend-color" style="background:#0d6efd;"></span> On Progress</div>
        <div class="legend-item"><span class="legend-color" style="background:#6c757d;"></span> Review</div>
        <div class="legend-item"><span class="legend-color" style="background:#198754;"></span> Completed</div>
        <div class="legend-item"><span class="legend-color" style="background:#dc3545;"></span> Rejected</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let currentYear = {{ $year }};
    let currentMonth = {{ $month }};

    const priorityLabels = {
        low: 'Low Priority',
        medium: 'Medium Priority',
        high: 'High Priority'
    };
    const priorityColors = {
        low: '#e0f7fa',
        medium: '#fff9c4',
        high: '#ffcdd2'
    };
    const priorityTextColors = {
        low: '#006064',
        medium: '#f57f17',
        high: '#b71c1c'
    };

    function renderCalendar(year, month) {
        $('#calendarContainer').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');

        $.ajax({
            url: "{{ route('manager.calendar.data') }}",
            method: 'GET',
            data: { year: year, month: month },
            success: function(response) {
                buildCalendar(response);
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                let msg = xhr.responseJSON?.error || 'Gagal memuat data';
                $('#calendarContainer').html('<div class="text-center py-4 text-danger">' + msg + '</div>');
            }
        });
    }

    function buildCalendar(data) {
        const days = data.days;
        const monthLabel = data.monthLabel;
        const eventsByPriority = data.events_by_priority;
        const totalRowsByPriority = data.total_rows_by_priority;

        $('#monthLabel').text(monthLabel);

        const rowHeight = 60; // tinggi setiap baris prioritas
        const dayWidth = (100 / days.length).toFixed(4) + '%'; // lebar per tanggal

        // --- 1. HEADER: Tanggal ---
        let headerHtml = `<div class="calendar-header-row">
                            <div class="header-priority-label">Prioritas</div>`;
        days.forEach(day => {
            const isWeekend = (day.dayName === 'Sabtu' || day.dayName === 'Minggu');
            const weekendClass = isWeekend ? 'weekend' : '';
            headerHtml += `<div class="header-day ${weekendClass}">${day.day}<br><small>${day.dayName.substring(0,3)}</small></div>`;
        });
        headerHtml += `</div>`;

        // --- 2. BARIS PRIORITAS ---
        const priorities = ['low', 'medium', 'high'];
        let bodyHtml = '';

        priorities.forEach(priority => {
            const tasks = eventsByPriority[priority] || [];
            const totalRows = totalRowsByPriority[priority] || 1;
            const label = priorityLabels[priority];
            const bgLabel = priorityColors[priority];
            const textLabel = priorityTextColors[priority];

            // Untuk setiap row_index (karena bisa overlap), kita buat baris terpisah
            for (let rowIdx = 0; rowIdx < totalRows; rowIdx++) {
                const tasksInRow = tasks.filter(t => t.row_index === rowIdx);
                bodyHtml += `<div class="priority-row" style="height: ${rowHeight}px;">`;
                // Label prioritas (hanya tampil di baris pertama, atau kita tampilkan di semua baris dengan indent)
                if (rowIdx === 0) {
                    bodyHtml += `<div class="priority-label ${priority}" style="background: ${bgLabel}; color: ${textLabel};">${label}</div>`;
                } else {
                    bodyHtml += `<div class="priority-label" style="background: #f8f9fa; color: #999; font-size: 12px;">&nbsp;</div>`;
                }

                // Area tugas
                bodyHtml += `<div class="task-area" style="position: relative;">`;

                // Render blok tugas di dalam task-area
                tasksInRow.forEach(task => {
                    const startCol = task.start_col;
                    const endCol = task.end_col;
                    const duration = endCol - startCol + 1;

                    // Hitung posisi kiri dan lebar berdasarkan tanggal
                    const leftPercent = ((startCol - 1) / days.length) * 100;
                    const widthPercent = (duration / days.length) * 100;

                    // Warna status
                    let bgColor = '';
                    if (task.status === 'Pending') bgColor = '#ffc107';
                    else if (task.status === 'On Progress') bgColor = '#0d6efd';
                    else if (task.status === 'Review') bgColor = '#6c757d';
                    else if (task.status === 'Completed') bgColor = '#198754';
                    else if (task.status === 'Rejected') bgColor = '#dc3545';
                    else bgColor = '#17a2b8';

                    const textColor = (task.status === 'Pending') ? '#212529' : '#fff';
                    const tooltip = `Tugas: ${task.title}\nProyek: ${task.project}\nDurasi: ${duration} hari\nPekerja: ${task.assignee}\nStatus: ${task.status}`;

                    bodyHtml += `<div class="task-block status-${task.status.toLowerCase().replace(' ', '')}"
                                    style="left: ${leftPercent}%; width: ${widthPercent}%; top: 4px; height: calc(100% - 8px); background: ${bgColor}; color: ${textColor};"
                                    data-task-id="${task.id}"
                                    title="${tooltip}">
                                    ${task.title} (${duration}h)
                                </div>`;
                });

                bodyHtml += `</div>`; // end task-area
                bodyHtml += `</div>`; // end priority-row
            }
        });

        $('#calendarContainer').html(headerHtml + bodyHtml);

        // Event click pada task block
        $('.task-block').on('click', function() {
            const taskId = $(this).data('task-id');
            if (taskId) {
                alert('Task ID: ' + taskId + '\n' + $(this).attr('title'));
            }
        });
    }

    function changeMonth(delta) {
        let newMonth = currentMonth + delta;
        let newYear = currentYear;
        if (newMonth > 12) { newMonth = 1; newYear++; }
        else if (newMonth < 1) { newMonth = 12; newYear--; }
        currentYear = newYear;
        currentMonth = newMonth;
        renderCalendar(currentYear, currentMonth);
    }

    $('#prevMonth').on('click', function() { changeMonth(-1); });
    $('#nextMonth').on('click', function() { changeMonth(1); });
    $('#todayBtn').on('click', function() {
        const today = new Date();
        currentYear = today.getFullYear();
        currentMonth = today.getMonth() + 1;
        renderCalendar(currentYear, currentMonth);
    });

    renderCalendar(currentYear, currentMonth);
});
</script>
@endpush
