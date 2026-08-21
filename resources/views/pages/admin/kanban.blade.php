@extends('layouts.appAdmin')
<title>Kanban Tugas</title>

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="title-wrapper pt-30">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="title mb-30">
                    <h2><i class="fas fa-columns me-2"></i> Kanban Tugas</h2>
                    <p class="text-muted">Papan tugas dari semua proyek</p>
                    <div class="dropdown mt-2">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-filter me-1"></i> Pilih Proyek
                        </button>
                        <div class="dropdown-menu p-3" style="max-height: 400px; overflow-y: auto; min-width: 280px;">
                            <div id="project-checklist">
                                @foreach($projects as $proj)
                                    <div class="form-check">
                                        <input class="form-check-input project-checkbox" type="checkbox" value="{{ $proj->id }}" id="proj_{{ $proj->id }}">
                                        <label class="form-check-label" for="proj_{{ $proj->id }}">
                                            {{ $proj->title }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <hr class="my-2">
                            <button class="btn btn-sm btn-outline-primary w-100" id="showAllBtn">
                                <i class="fas fa-eye me-1"></i> Tampilkan Semua
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-style">
        {{-- Tempat Kanban akan di-render oleh JavaScript --}}
        <div id="kanban-container"></div>
    </div>

</div>

{{-- MODAL POPUP DETAIL TUGAS (SAMA DENGAN KALENDER) --}}
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
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // 1. Fungsi utama untuk merender semua proyek
    function renderAllProjects(tasks) {
        $('#kanban-container').html('');
        
        // Kelompokkan tugas berdasarkan project_id
        var projects = {};
        tasks.forEach(function(task) {
            if (!projects[task.project_id]) {
                projects[task.project_id] = {
                    id: task.project_id,
                    name: task.project_name || 'Proyek Tanpa Nama',
                    tasks: []
                };
            }
            projects[task.project_id].tasks.push(task);
        });

        // Loop setiap proyek dan buat Kanban-nya masing-masing
        for (let projId in projects) {
            let proj = projects[projId];
            
            // HTML Wrapper untuk 1 Proyek
            let projectHtml = `
                <div class="project-wrapper mb-5" data-project-id="${proj.id}">
                    <h4 class="mb-3 text-primary fw-bold">
                        <i class="fas fa-folder-open me-2"></i> ${proj.name}
                    </h4>
                    <div class="row g-3 project-kanban-board" data-project-id="${proj.id}">
                        <!-- KOLOM PENDING (Warna Kuning) -->
                        <div class="col">
                            <div class="card h-100 border-warning">
                                <div class="card-header bg-warning text-dark fw-bold text-center"><i class="fas fa-clock"></i> Pending</div>
                                <div class="card-body bg-light bg-opacity-25 p-2 kanban-body" data-status="Pending"></div>
                            </div>
                        </div>
                        <!-- KOLOM ON PROGRESS (Warna Biru) -->
                        <div class="col">
                            <div class="card h-100 border-primary">
                                <div class="card-header bg-primary text-white fw-bold text-center"><i class="fas fa-spinner fa-spin"></i> On Progress</div>
                                <div class="card-body bg-light bg-opacity-25 p-2 kanban-body" data-status="On Progress"></div>
                            </div>
                        </div>
                        <!-- KOLOM REVIEW (Warna Abu-Abu) -->
                        <div class="col">
                            <div class="card h-100 border-secondary">
                                <div class="card-header bg-secondary text-white fw-bold text-center"><i class="fa-solid fa-magnifying-glass"></i> Review</div>
                                <div class="card-body bg-light bg-opacity-25 p-2 kanban-body" data-status="Review"></div>
                            </div>
                        </div>
                        <!-- KOLOM COMPLETED (Warna Hijau) -->
                        <div class="col">
                            <div class="card h-100 border-success">
                                <div class="card-header bg-success text-white fw-bold text-center"><i class="fas fa-check-circle"></i> Completed</div>
                                <div class="card-body bg-light bg-opacity-25 p-2 kanban-body" data-status="Completed"></div>
                            </div>
                        </div>
                        <!-- KOLOM REJECTED (Warna Merah) -->
                        <div class="col">
                            <div class="card h-100 border-danger">
                                <div class="card-header bg-danger text-white fw-bold text-center"><i class="fas fa-times-circle me-1"></i> Rejected</div>
                                <div class="card-body bg-light bg-opacity-25 p-2 kanban-body" data-status="Rejected"></div>
                            </div>
                        </div>
                    </div>
                    <hr>
                </div>
            `;
            $('#kanban-container').append(projectHtml);

            // Render kartu ke dalam proyek ini
            renderProjectKanban(proj.tasks, proj.id);
        }
    }

    // 2. Fungsi untuk merender kartu di dalam 1 proyek
    function renderProjectKanban(tasks, projectId) {
        let board = $(`.project-kanban-board[data-project-id="${projectId}"]`);
        board.find('.kanban-body').html('');

        if (!tasks || tasks.length === 0) {
            board.find('.kanban-body').html('<div class="text-center py-4 text-muted small">Tidak ada tugas</div>');
            return;
        }

        tasks.forEach(function(task) {
            var status = task.status;
            
            // Pilih border warna Bootstrap berdasarkan status
            var borderClass = 'border-warning'; // default Pending
            if (status === 'On Progress') borderClass = 'border-primary';
            else if (status === 'Review') borderClass = 'border-secondary';
            else if (status === 'Completed') borderClass = 'border-success';
            else if (status === 'Rejected') borderClass = 'border-danger';

            // MODIFIED: Tambahkan data attributes untuk modal
            var cardHtml = `
                <div class="card mb-2 shadow-sm ${borderClass} task-card" 
                     style="cursor: pointer;"
                     data-task-id="${task.id}"
                     data-project-id="${task.project_id}"
                     data-task-title="${task.title}"
                     data-project-name="${task.project_name || 'Proyek Tanpa Nama'}"
                     data-assignee="${task.employee_name}"
                     data-description="${task.description || 'Tidak ada deskripsi.'}"
                     data-status="${task.status}"
                     data-priority="${task.priority || 'medium'}"
                     data-duration="${task.duration || '-'}">
                    <div class="card-body p-2">
                        <div class="fw-bold small text-dark text-start">${task.title}</div>
                        <hr>
                        <div class = "row">
                            <div class="col-md-6"><div class="text-muted small text-start"><i class="fa-solid fa-calendar-days"></i> ${task.duration}</div></div>
                            <div class="col-md-6"><div class="text-muted small text-end"><i class="fas fa-user me-1"></i> ${task.employee_name}</div></div>
                        </div>
                    </div>
                </div>
            `;

            var targetColumn = board.find(`.kanban-body[data-status="${status}"]`);
            if (targetColumn.length > 0) {
                targetColumn.append(cardHtml);
            }
        });

        // 3. Event listener untuk klik kartu tugas
        $('.task-card').off('click').on('click', function() {
            const taskId = $(this).data('task-id');
            const projectId = $(this).data('project-id');
            const taskTitle = $(this).data('task-title');
            const projectName = $(this).data('project-name');
            const assignee = $(this).data('assignee');
            const description = $(this).data('description');
            const status = $(this).data('status');
            const priority = $(this).data('priority');
            const duration = $(this).data('duration');

            // Isi Modal
            $('#modalTaskTitle').text(taskTitle || 'Tidak ada judul');
            $('#modalTaskProject').text(projectName || '-');
            $('#modalTaskAssignee').text(assignee || '-');
            $('#modalTaskDescription').text(description || 'Tidak ada deskripsi.');
            $('#modalTaskDuration').text(duration || '-');

            // Set Status badge dengan warna yang sesuai
            let statusBg = '';
            let statusText = status || 'Pending';
            if (status === 'Pending') statusBg = '#ffc107';
            else if (status === 'On Progress') statusBg = '#0d6efd';
            else if (status === 'Review') statusBg = '#6c757d';
            else if (status === 'Completed') statusBg = '#198754';
            else if (status === 'Rejected') statusBg = '#dc3545';
            else statusBg = '#17a2b8';

            const statusHtml = `<span class="badge" style="background-color: ${statusBg}; color: #fff; padding: 5px 10px;">${statusText}</span>`;
            $('#modalTaskStatus').html(statusHtml);

            // Set Prioritas badge
            let priorityBadgeClass = 'bg-secondary';
            let priorityLabelText = 'Medium Priority';

            if (priority === 'high') {
                priorityBadgeClass = 'bg-danger';
                priorityLabelText = 'High Priority';
            } else if (priority === 'medium') {
                priorityBadgeClass = 'bg-warning';
                priorityLabelText = 'Medium Priority';
            } else if (priority === 'low') {
                priorityBadgeClass = 'bg-info';
                priorityLabelText = 'Low Priority';
            }

            $('#modalTaskPriority').html(`<span class="badge ${priorityBadgeClass}">${priorityLabelText}</span>`);

            // Set URL untuk tombol "Lihat Detail Tugas"
            const detailUrl = "/admin/projects/" + projectId + "/tasks/" + taskId + "/review";
            $('#modalTaskDetailBtn').attr('href', detailUrl);

            // Tampilkan Modal
            const myModal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
            myModal.show();
        });
    }

    // 3. Ambil data tugas dari server berdasarkan proyek yang dipilih
    function renderSelectedProjects() {
        const selectedIds = $('.project-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            $('#kanban-container').html('<div class="text-center py-5 text-muted">Pilih proyek untuk menampilkan kanban</div>');
            return;
        }

        $('#kanban-container').html('<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Memuat data tugas...</div>');

        $.ajax({
            url: '{{ route("admin.kanban.tasks") }}',
            method: 'POST',
            data: { project_ids: selectedIds },
            success: function(tasks) {
                renderAllProjects(tasks);
            },
            error: function() {
                $('#kanban-container').html('<div class="text-center py-5 text-danger">Gagal memuat data tugas. Coba lagi.</div>');
            }
        });
    }

    // Debounce agar tidak terlalu banyak request saat user mengecek banyak checkbox
    let debounceTimer;
    $('.project-checkbox').on('change', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(renderSelectedProjects, 300);
    });

    $('#showAllBtn').on('click', function() {
        $('.project-checkbox').prop('checked', true);
        renderSelectedProjects();
    });

    // 4. Render awal: kosong dulu, tunggu pilihan user
    $('#kanban-container').html('<div class="text-center py-5 text-muted">Pilih proyek untuk menampilkan kanban</div>');
});
</script>
@endpush