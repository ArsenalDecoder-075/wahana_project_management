@extends('layouts.appAdmin')
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
        border-bottom: 3px solid #cbd5e1;
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
        border-right: 3px solid #cbd5e1;
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
        align-items: stretch;
        border-bottom: 1px solid #e5e7eb;
        height: 60px;
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
        border-right: 3px solid #cbd5e1;
        z-index: 5;
        height: 100%;
        box-sizing: border-box;
    }

    /* --- WARNA PRIORITAS --- */
    .priority-label.high { background: #ffcdd2; color: #b71c1c; }
    .priority-label.medium { background: #fff9c4; color: #f57f17; }
    .priority-label.low { background: #e0f7fa; color: #006064; }

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
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        filter: brightness(1.15);
    }

    /* Warna status */
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
                Kalender Tugas (Admin)
            </h2>
            <p class="text-muted">Semua tugas dari semua proyek, dikelompokkan berdasarkan prioritas</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('admin.home') }}" class="btn btn-secondary">
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
        <div class="card-body p-0" style="overflow: visible;">
            <div id="calendarContainer" class="calendar-wrapper" style="overflow: visible;">
                {{-- Kalender diisi oleh JavaScript --}}
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

{{-- MODAL POPUP DETAIL TUGAS (BARU)            --}}
<div class="modal fade" id="taskDetailModal" tabindex="-1" aria-labelledby="taskDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="taskDetailModalLabel">
                    <i class="fas fa-tasks me-2"></i> Detail Tugas
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h6 class="fw-bold">Judul Tugas</h6>
                        <p id="modalTaskTitle" class="fs-5 fw-semibold"></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6 class="fw-bold">Proyek</h6>
                        <p id="modalTaskProject"></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6 class="fw-bold">Ditugaskan Kepada</h6>
                        <p id="modalTaskAssignee"></p>
                    </div>
                    <div class="col-md-12 mb-3">
                        <h6 class="fw-bold">Deskripsi Tugas</h6>
                        <p id="modalTaskDescription" class="text-muted" style="white-space: pre-wrap;"></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold">Prioritas</h6>
                        <p id="modalTaskPriority"></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold">Status</h6>
                        <p id="modalTaskStatus"></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold">Durasi</h6>
                        <p id="modalTaskDuration"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="modalTaskDetailBtn" class="btn btn-primary">
                    <i class="fas fa-eye me-1"></i> Lihat Detail Tugas
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let currentYear = {{ $year }};
    let currentMonth = {{ $month }};

    const priorityLabels = {
        high: 'High Priority',
        medium: 'Medium Priority',
        low: 'Low Priority'
    };
    const priorityColors = {
        high: '#ffcdd2',
        medium: '#fff9c4',
        low: '#e0f7fa'
    };
    const priorityTextColors = {
        high: '#b71c1c',
        medium: '#f57f17',
        low: '#006064'
    };

    function renderCalendar(year, month) {
        $('#calendarContainer').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');

        $.ajax({
            url: "{{ route('admin.calendar.data') }}",
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

        const rowHeight = 60;

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
        const priorities = ['high', 'medium', 'low'];
        let bodyHtml = '';
        let isFirstPriority = true;

        priorities.forEach(priority => {
            const tasks = eventsByPriority[priority] || [];
            const totalRows = totalRowsByPriority[priority] || 1;
            const label = priorityLabels[priority];
            const bgLabel = priorityColors[priority];
            const textLabel = priorityTextColors[priority];

            for (let rowIdx = 0; rowIdx < totalRows; rowIdx++) {
                const tasksInRow = tasks.filter(t => t.row_index === rowIdx);

                let extraRowStyle = '';
                if (rowIdx === 0 && !isFirstPriority) {
                    extraRowStyle = 'border-top: 5px solid #cbd5e1;';
                }

                bodyHtml += `<div class="priority-row" style="height: ${rowHeight}px;${extraRowStyle}">`;

                if (rowIdx === 0) {
                    bodyHtml += `<div class="priority-label ${priority}" style="background: ${bgLabel}; color: ${textLabel};">${label}</div>`;
                } else {
                    bodyHtml += `<div class="priority-label ${priority}" style="background: ${bgLabel}; color: ${textLabel};">&nbsp;</div>`;
                }

                bodyHtml += `<div class="task-area" style="position: relative;">`;

                tasksInRow.forEach(task => {
                    const startCol = task.start_col;
                    const endCol = task.end_col;
                    const duration = endCol - startCol + 1;

                    const leftPercent = ((startCol - 1) / days.length) * 100;
                    const widthPercent = (duration / days.length) * 100;

                    let bgColor = '';
                    if (task.status === 'Pending') bgColor = '#ffc107';
                    else if (task.status === 'On Progress') bgColor = '#0d6efd';
                    else if (task.status === 'Review') bgColor = '#6c757d';
                    else if (task.status === 'Completed') bgColor = '#198754';
                    else if (task.status === 'Rejected') bgColor = '#dc3545';
                    else bgColor = '#17a2b8';

                    const textColor = (task.status === 'Pending') ? '#212529' : '#fff';
                    const tooltip = `Proyek: ${task.project}\nTugas: ${task.title}\nDeskripsi: ${task.description}\nDurasi: ${duration} hari\nPekerja: ${task.assignee}\nStatus: ${task.status}`;

                    bodyHtml += `<div class="task-block status-${task.status.toLowerCase().replace(' ', '')}"
                            style="left: ${leftPercent}%; width: ${widthPercent}%; top: 4px; height: calc(100% - 8px); background: ${bgColor}; color: ${textColor};"
                            data-task-id="${task.id}"
                            data-project-id="${task.project_id}"
                            data-priority="${task.priority}"
                            title="${tooltip}">
                            ${task.title} (${duration}h)
                        </div>`;
                });

                bodyHtml += `</div>`;
                bodyHtml += `</div>`;
            }

            isFirstPriority = false;
        });

        $('#calendarContainer').html(headerHtml + bodyHtml);

        // EVENT KLIK TASK BLOCK (BUKA MODAL)
        $('.task-block').on('click', function() {
            const projectId = $(this).data('project-id');
            const taskId = $(this).data('task-id');
            if (!taskId || !projectId) return;

            // Ambil data dari atribut title (tooltip)
            const fullTooltip = $(this).attr('title');
            const lines = fullTooltip.split('\n');
            let dataObj = {};

            lines.forEach(line => {
                const parts = line.split(': ');
                if (parts.length === 2) {
                    const key = parts[0].trim();
                    const value = parts[1].trim();
                    if (key === 'Proyek') dataObj.project = value;
                    if (key === 'Tugas') dataObj.title = value;
                    if (key === 'Deskripsi') dataObj.description = value;
                    if (key === 'Durasi') dataObj.duration = value;
                    if (key === 'Pekerja') dataObj.assignee = value;
                    if (key === 'Status') dataObj.status = value;
                }
            });

            const statusBg = $(this).css('background-color');

            // Isi Modal
            $('#modalTaskTitle').text(dataObj.title || 'Tidak ada judul');
            $('#modalTaskProject').text(dataObj.project || '-');
            $('#modalTaskAssignee').text(dataObj.assignee || '-');
            $('#modalTaskDescription').text(dataObj.description || 'Tidak ada deskripsi.');
            $('#modalTaskDuration').text(dataObj.duration || '-');

            // Set Status badge
            const statusHtml = `<span class="badge" style="background-color: ${statusBg}; color: #fff; padding: 5px 10px;">${dataObj.status || '-'}</span>`;
            $('#modalTaskStatus').html(statusHtml);

            // Set Prioritas badge
            // const priorityLabel = $(this).closest('.priority-row').find('.priority-label').text().trim();
            // let priorityBadgeClass = 'bg-secondary';
            // if (priorityLabel.includes('High')) priorityBadgeClass = 'bg-danger';
            // else if (priorityLabel.includes('Medium')) priorityBadgeClass = 'bg-warning';
            // else if (priorityLabel.includes('Low')) priorityBadgeClass = 'bg-info';

            // $('#modalTaskPriority').html(`<span class="badge ${priorityBadgeClass}">${priorityLabel}</span>`);

            // Set Prioritas badge
            // PERBAIKAN: Ambil langsung dari atribut data-priority, bukan dari closest row
            const taskPriority = $(this).data('priority');

            let priorityBadgeClass = 'bg-secondary';
            let priorityLabelText = 'Unknown';

            if (taskPriority === 'high') {
                priorityBadgeClass = 'bg-danger';
                priorityLabelText = 'High Priority';
            } else if (taskPriority === 'medium') {
                priorityBadgeClass = 'bg-warning';
                priorityLabelText = 'Medium Priority';
            } else if (taskPriority === 'low') {
                priorityBadgeClass = 'bg-info';
                priorityLabelText = 'Low Priority';
            }

            $('#modalTaskPriority').html(`<span class="badge ${priorityBadgeClass}">${priorityLabelText}</span>`);

            // PERBAIKAN URL: Bangun URL secara langsung (Hardcode string URL)
            // /admin/tasks/{project_id}/review/{task_id} sama kayak web.php
            const detailUrl = "/admin/projects/" + projectId + "/tasks/" + taskId + "/review";

            $('#modalTaskDetailBtn').attr('href', detailUrl);

            // Tampilkan Modal
            const myModal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
            myModal.show();
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
