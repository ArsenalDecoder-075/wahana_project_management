@extends('layouts.appAdmin')
<title>Kelola Tugas</title>

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="title-wrapper pt-30">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="title mb-30">
                    <h2>Manajemen Tugas</h2>
                </div>
            </div>
            <div class="col-md-6">
                <div class="breadcrumb-wrapper mb-30">
                    <nav>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.manage.project') }}">Proyek</a>
                            </li>
                            <li class="breadcrumb-item active">Tugas</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Proyek & Form Tambah Tugas --}}
    <div class="row mb-4" style="display: flex; flex-wrap: wrap;">
        {{-- Kolom Kiri: Informasi Proyek --}}
        <div class="col-lg-4" style="display: flex;">
            <div class="card-style mb-30"  style="flex: 1;">
                <div class="title mb-20">
                    <h5><i class="fa-regular fa-circle-question"></i> Informasi Proyek</h5>
                </div>

                <div class="row">
                    {{-- Nama Proyek --}}
                    <div class="col-md-12 mb-3">
                        <strong>{{ $project->title }}</strong> <br>
                        <i>{{ $project->status }}</i>
                    </div>
                    
                    {{-- Manager --}}
                    <div class="col-md-6 mb-3">
                        <strong>Manager:</strong>
                        <p>
                            @if($project->manager)
                                <span class="badge bg-success">{{ $project->manager->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </p>
                    </div>
        
                    {{-- Kategori --}}
                    <div class="col-md-6 mb-3">
                        <strong>Kategori:</strong>
                        <p>
                            @if($project->category)
                                <span class="badge" style="background-color: #6c757d; color: white;">
                                    {{ $project->category->name }}
                                </span>
                            @else
                                <span class="text-muted">Tidak Ada Kategori</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-12 mb-3">
                        <strong>Prioritas</strong>
                        <div class="mt-1">
                            <span class="badge bg-info">
                                Low: {{ $priorityStats['low']['completed'] }}/{{ $priorityStats['low']['total'] }}
                            </span>
                            <span class="badge bg-warning text-dark">
                                Med: {{ $priorityStats['medium']['completed'] }}/{{ $priorityStats['medium']['total'] }}
                            </span>
                            <span class="badge bg-danger">
                                High: {{ $priorityStats['high']['completed'] }}/{{ $priorityStats['high']['total'] }}
                            </span>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-check-circle text-success"></i>
                                Total: {{ $totalCompleted }}/{{ $totalTasks }} selesai
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Deadline:</strong>
                        <p>{{ $project->end_date }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Dibuat:</strong>
                        <p>{{ $project->created_at->format('d M Y') }}</p>
                    </div>
                    <div class="col-12 mb-3">
                        <strong>Deskripsi:</strong>
                        <p>{{ $project->description ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Form Tambah Tugas --}}
        <div class="col-lg-8" style="display: flex;">
            <div class="card-style mb-30"  style="flex: 1;">
                <div class="title mb-20">
                    <h5><i class="fas fa-tasks"></i> Penambahan Tugas</h5>
                    <p class="text-muted">Tambahkan tugas baru untuk proyek ini</p>
                </div>

                <form action="{{ route('admin.tasks.store') }}" method="POST" id="addTaskForm">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">

                    <div class="row g-3">
                        <!-- Nama Tugas -->
                        <div class="col-md-12">
                            <label for="title" class="form-label fw-bold">
                                <i class="fas fa-tag text-primary"></i> Nama Tugas <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="title" name="title" class="form-control"
                                placeholder="Masukkan nama tugas" required>
                        </div>

                        <!-- Prioritas Tugas -->
                        <div class="col-md-3">
                            <label for="priority" class="form-label fw-bold">
                                <i class="fas fa-flag text-warning"></i> Prioritas <span class="text-danger">*</span>
                            </label>
                            <select name="priority" id="priority" class="form-select" required>
                                <option value="">Pilih Prioritas</option>
                                <option value="low">🔵 Low (Rendah)</option>
                                <option value="medium">🟡 Medium (Sedang)</option>
                                <option value="high">🔴 High (Tinggi)</option>
                            </select>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Urgensi tugas
                            </small>
                        </div>

                        <!-- Startdate -->
                        <div class="col-md-3">
                            <label for="startdate" class="form-label fw-bold">
                                <i class="fas fa-calendar-alt text-success"></i> Startdate <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="startdate" name="startdate" class="form-control"
                                min="{{ $project->start_date }}"
                                max="{{ $project->end_date }}"
                                required>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Min: {{ $project->start_date }} | Max: {{ $project->end_date }}
                            </small>
                        </div>

                        <!-- Deadline -->
                        <div class="col-md-3">
                            <label for="deadline" class="form-label fw-bold">
                                <i class="fas fa-calendar-alt text-danger"></i> Deadline <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="deadline" name="deadline" class="form-control"
                                min="{{ $project->start_date }}"
                                max="{{ $project->end_date }}"
                                required>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Min: {{ $project->start_date }} | Max: {{ $project->end_date }}
                            </small>
                        </div>

                        <!-- Assign To -->
                        <div class="col-md-3">
                            <label for="assigned_to" class="form-label fw-bold">
                                <i class="fas fa-user text-success"></i> Assign Ke <span class="text-danger">*</span>
                            </label>
                            <select name="assigned_to" id="assigned_to" class="form-select" required>
                                <option value="">Pilih Karyawan</option>
                                @foreach($employees as $emp)
                                    @php
                                        $count = $employeeTaskCounts[$emp->id] ?? 0;
                                    @endphp
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $count }} tugas)</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Pilih penanggung jawab tugas</small>
                        </div>

                        {{-- Deskripsi Tugas --}}
                        <div class="col-md-12">
                            <label for="description" class="form-label fw-bold">
                                <i class="fas fa-solid fa-book"></i> Deskripsi Tugas
                            </label>
                            <textarea class="form-control"
                                    id="description"
                                    name="description"
                                    rows="2"
                                    placeholder="Deskripsi tugas... (opsional)"></textarea>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Detail tentang tugas
                            </small>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="col-md-12 mt-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus-circle"></i> Tambah Tugas
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Tugas -->
    <div class="modal fade" id="editTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editTaskForm" action="{{ route('admin.tasks.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="edit_task_id">

                    <div class="modal-header bg-warning">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Tugas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_title" class="form-label fw-bold">
                                    Nama Tugas <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="title" id="edit_title" class="form-control" placeholder="Nama Tugas" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="edit_description" class="form-label fw-bold">
                                    Deskripsi Tugas
                                </label>
                                <textarea name="description" id="edit_description" class="form-control" rows="3" placeholder="Deskripsi tugas..."></textarea>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="edit_priority" class="form-label fw-bold">
                                    Prioritas <span class="text-danger">*</span>
                                </label>
                                <select name="priority" id="edit_priority" class="form-select" required>
                                    <option value="low">🔵 LOW (Rendah)</option>
                                    <option value="medium">🟡 MEDIUM (Sedang)</option>
                                    <option value="high">🔴 HIGH (Tinggi)</option>

                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="edit_startdate" class="form-label fw-bold">
                                    Startdate <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="startdate" id="edit_startdate" class="form-control"
                                       min="{{ $project->start_date }}"
                                       max="{{ $project->end_date }}"
                                       required>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Startdate harus antara {{ $project->start_date }} dan {{ $project->end_date }}
                                </small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="edit_deadline" class="form-label fw-bold">
                                    Deadline <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="deadline" id="edit_deadline" class="form-control"
                                       min="{{ $project->start_date }}"
                                       max="{{ $project->end_date }}"
                                       required>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Deadline harus antara {{ $project->start_date }} dan {{ $project->end_date }}
                                </small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="edit_assigned_to" class="form-label fw-bold">
                                    Assign Ke <span class="text-danger">*</span>
                                </label>
                                <select name="assigned_to" id="edit_assigned_to" class="form-select" required>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Delete Task -->
    <div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <form id="deleteTaskForm" action="{{ route('admin.tasks.delete') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="delete_task_id">

                    <!-- Header dengan gradasi -->
                    <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); border: none;">
                        <div class="d-flex align-items-center">
                                <h5 class="modal-title text-white fw-bold">Konfirmasi Hapus Tugas</h5>                      </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body p-4">
                        <!-- Icon & Peringatan -->
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <div class="rounded-circle bg-danger bg-opacity-10 p-4" style="width: 100px; height: 100px;">
                                    <i class="fas fa-trash-alt text-danger" style="font-size: 48px; line-height: 68px;"></i>
                                </div>
                                <div class="position-absolute top-0 end-0">
                                    <span class="badge bg-danger rounded-pill p-2">
                                        <i class="fas fa-exclamation fa-1x"></i>
                                    </span>
                                </div>
                            </div>
                            <h6 class="mt-3 text-danger fw-bold">Anda yakin ingin menghapus tugas ini?</h6>
                            <p class="text-muted small">Semua data terkait tugas akan hilang secara permanen</p>
                        </div>

                        <!-- Card Informasi Tugas -->
                        <div class="card border-0 shadow-sm mb-0" style="background: #f8f9fa;">
                            <div class="card-body p-3">
                                <!-- Nama Tugas -->
                                <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-primary rounded-circle p-2">
                                            <i class="fas fa-tag fa-1x text-white"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <small class="text-muted d-block">Nama Tugas</small>
                                        <span id="delete_info_title" class="fw-bold text-dark"></span>
                                    </div>
                                </div>

                                <!-- Deskripsi -->
                                <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-info rounded-circle p-2">
                                            <i class="fas fa-align-left fa-1x text-white"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <small class="text-muted d-block">Deskripsi</small>
                                        <span id="delete_info_description" class="text-dark"></span>
                                    </div>
                                </div>

                                <!-- Karyawan -->
                                <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-success rounded-circle p-2">
                                            <i class="fas fa-user fa-1x text-white"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <small class="text-muted d-block">Assign Ke</small>
                                        <span id="delete_info_employee" class="text-dark"></span>
                                    </div>
                                </div>

                                <!-- Prioritas, Startdate, dan Deadline (3 Kolom) -->
                                <div class="row g-3">
                                    <div class="col-4">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <span class="badge bg-warning rounded-circle p-2">
                                                    <i class="fas fa-flag fa-1x text-dark"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <small class="text-muted d-block">Prioritas</small>
                                                <span id="delete_info_priority"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-8">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <span class="badge bg-secondary rounded-circle p-2">
                                                    <i class="fas fa-calendar-alt fa-1x text-white"></i>
                                                </span>
                                            </div>

                                            <div class="flex-grow-1 ms-2">
                                                <small class="text-muted d-block">Start Date</small>
                                                <span id="delete_info_start_date" class="fw-bold"></span>
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <small class="text-muted d-block">Deadline</small>
                                                <span id="delete_info_deadline" class="fw-bold"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="d-flex align-items-start mt-3 pt-3 border-top">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-primary rounded-circle p-2">
                                            <i class="fas fa-check-circle fa-1x text-white"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <small class="text-muted d-block">Status</small>
                                        <span id="delete_info_status"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-0 p-3" style="background: #f8f9fa; border-radius: 0 0 10px 10px;">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="fas fa-trash me-2"></i> Ya, Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Table Tasks --}}
    <div class="card-style">
        <div class="title mb-20">
            <h5><i class="fas fa-list-check"></i> List Tugas</h5>
            <p class="text-muted">Daftar semua tugas dalam proyek ini</p>
        </div>

        <div class="table-responsive">
            <table class="display" id="tasks-table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Tugas</th>
                        <th>Deskripsi</th>
                        <th>Assign Ke</th>
                        <th>Prioritas</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>Deadline</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Setup CSRF Token untuk keamanan AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Registrasi custom sorting untuk priority
    $.fn.dataTable.ext.order['priority-sort'] = function(settings, col) {
        return this.api().column(col, { order: 'index' }).nodes().map(function(td, i) {
            var priority = $(td).text().trim();
            var order = { 'HIGH': 1, 'MEDIUM': 2, 'LOW': 3 };
            return order[priority] || 4;
        });
    };

    // 1. Inisialisasi DataTable
    var table = $('#tasks-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.tasks.manage', $project->id) }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
            { data: 'title', name: 'title', width: '10%' },
            { data: 'description', name: 'description', width: '30%' },
            { data: 'employee_name', name: 'assignee.name'},
            {
                data: 'priority_badge',
                name: 'priority',
                // Pake custom sorting yg diatas
                orderDataType: 'priority-sort'
            },
            { data: 'status_badge', name: 'status'},
            { data: 'startdate_format', name: 'start_date'},
            { data: 'deadline_format', name: 'deadline'},
            // { data: 'submission_status', name: 'submission_status'},

            { data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        ordering: false,  // Matiin sorting untuk semua kolom
        pageLength: 10,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: '<i class="fas fa-search"></i> Cari:',
            lengthMenu: 'Tampilkan _MENU_ entri',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
            infoEmpty: 'Tidak ada data',
            infoFiltered: '(difilter dari _MAX_ total entri)',
            zeroRecords: 'Tidak ada tugas ditemukan'
        },
        columnDefs: [
            { className: 'text-center', targets: [0, 4, 5, 6, 7] },
            { className: 'align-middle', targets: '_all' }
        ],
        drawCallback: function() {
            // Tambahkan tooltip untuk deskripsi panjang
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    // 2. Fungsi Fungsi Kustom untuk Toast Notification (Atas Kiri)
    function tampilkanNotifikasi(pesan, tipe = 'success') {
        // Buat container toast di atas kiri jika belum ada di HTML
        if ($('#toast-container-kiri').length === 0) {
            $('body').append('<div id="toast-container-kiri" style="position: fixed; top: 20px; right: 20px; z-index: 10000; min-width: 300px;"></div>');
        }

        var warnaBg = tipe === 'success' ? 'bg-success' : 'bg-danger';
        var ikon = tipe === 'success' ? '⚡' : '⚠️';

        var toastHtml = `
            <div class="toast show align-items-center text-white ${warnaBg} border-0 mb-2 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: 8px;">
                <div class="d-flex p-3">
                    <div class="toast-body fw-bold">
                        ${ikon} ${pesan}
                    </div>
                </div>
            </div>
        `;

        // Munculkan toast
        $('#toast-container-kiri').append(toastHtml);
    }

    // 3. Proses Tambah Tugas via AJAX
    $('#addTaskForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                tampilkanNotifikasi(response.message, 'success');
                $('#addTaskForm')[0].reset();

                // Refresh otomatis setelah 1 detik agar user sempat membaca notifikasi
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr) {
                var errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menambahkan tugas';
                tampilkanNotifikasi(errorMsg, 'error');
            }
        });
    });

    // 4. Trigger Pengisian Modal Edit saat tombol Edit diklik
    $(document).on('click', '.editTaskBtn', function() {
        $('#edit_task_id').val($(this).data('id'));
        $('#edit_title').val($(this).data('title'));
        $('#edit_description').val($(this).data('description') || '');
        $('#edit_priority').val($(this).data('priority'));
        $('#edit_startdate').val($(this).data('start_date'));
        $('#edit_deadline').val($(this).data('deadline'));
        $('#edit_assigned_to').val($(this).data('assigned'));
        $('#editTaskModal').modal('show');
    });

    // 5. Proses Simpan Update Tugas via AJAX
    $('#editTaskForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                tampilkanNotifikasi(response.message, 'success');
                $('#editTaskModal').modal('hide');

                // Refresh otomatis untuk menghitung ulang bobot di progress bar
                setTimeout(function() {
                    location.reload();
                }, 1200);
            },
            error: function(xhr) {
                var errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal memperbarui tugas';
                tampilkanNotifikasi(errorMsg, 'error');
            }
        });
    });

    // 6. Trigger Konfirmasi Hapus Data saat tombol Hapus diklik
    $(document).on('click', '.deleteTaskBtn', function() {
        $('#delete_task_id').val($(this).data('id'));
        $('#delete_info_title').text($(this).data('title'));
        $('#delete_info_description').text($(this).data('description'));
        $('#delete_info_employee').text($(this).data('employee'));
        $('#delete_info_priority').text($(this).data('priority'));
        $('#delete_info_start_date').text($(this).data('start_date'));
        $('#delete_info_deadline').text($(this).data('deadline'));
        $('#delete_info_status').text($(this).data('deadline'));
        $('#deleteTaskModal').modal('show');
    });

    // 7. Proses Eksekusi Hapus Tugas via AJAX
    $('#deleteTaskForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                tampilkanNotifikasi(response.message, 'success');
                $('#deleteTaskModal').modal('hide');

                // Refresh otomatis setelah data terhapus
                setTimeout(function() {
                    location.reload();
                }, 1200);
            },
            error: function(xhr) {
                var errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menghapus tugas';
                tampilkanNotifikasi(errorMsg, 'error');
            }
        });
    });
});
</script>
@endpush
