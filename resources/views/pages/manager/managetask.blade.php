@extends('layouts.appAdmin')
<title>Kelola Tugas</title>

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="title-wrapper pt-30">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="title mb-30">
                    <h2>Manajemen Tugas Proyek</h2>
                </div>
            </div>
            <div class="col-md-6">
                <div class="breadcrumb-wrapper mb-30">
                    <nav>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('manager.manage.project') }}">Proyek</a>
                            </li>
                            <li class="breadcrumb-item active">Tugas</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Box Project --}}
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card-style mb-30">
                <div class="title mb-20">
                    <h5><i class="fa-regular fa-circle-question"></i> Informasi Proyek</h5>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <strong>Nama Proyek:</strong>
                        <p>{{ $project->title }}</p>
                    </div>

                    <div class="col-md-3">
                        <strong>Status:</strong>
                        <p>{{ $project->status }}</p>
                    </div>

                    <div class="col-md-3">
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

                    <div class="col-md-3">
                        <strong>Deadline:</strong>
                        <p>{{ $project->end_date }}</p>
                    </div>
                </div>

                <div class="mt-3">
                    <strong>Deskripsi:</strong>
                    <p>{{ $project->description ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>
    @php
        $totalBobot = $project->tasks->sum('weight');
    @endphp
    <div class="card p-3 mb-4">
        <div class="d-flex justify-content-between">
            <span>Total Bobot Proyek: <strong>{{ $totalBobot }}%</strong> / <strong>100%</strong></span>
        </div>
        <div class="progress" style="height: 20px;">
            <div class="progress-bar {{ $totalBobot > 100 ? 'bg-danger' : 'bg-success' }}"
                style="width: {{ $totalBobot }}%"></div>
        </div>
    </div>

    {{-- Form Tambah Tugas --}}
    <div class="card-style mb-30">
        <div class="title mb-20">
            <h5><i class="fas fa-tasks"></i> Penambahan Tugas</h5>
            <p class="text-muted">Tambahkan tugas baru untuk proyek ini</p>
        </div>

        <form action="{{ route('manager.tasks.store') }}" method="POST" id="addTaskForm" class="card p-4 mb-4">
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">

            <div class="row g-3">
                <!-- Nama Tugas -->
                <div class="col-md-4">
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
                        <i class="fas fa-info-circle"></i> Prioritas menentukan urgensi tugas
                    </small>
                </div>

                <!-- Deadline -->
                <div class="col-md-3">
                    <label for="deadline" class="form-label fw-bold">
                        <i class="fas fa-calendar-alt text-danger"></i> Deadline <span class="text-danger">*</span>
                    </label>
                    <input type="date" id="deadline" name="deadline" class="form-control"
                           min="{{ $project->start_date }}" required>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Tanggal batas penyelesaian tugas ({{ $project->start_date }})
                    </small>
                </div>

                <!-- Assign To -->
                <div class="col-md-2">
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
                    <small class="text-muted">Pilih penanggung jawab</small>
                </div>

                {{-- Deskripsi Tugas --}}
                <div class="col-md-12">
                    <label for="description" class="form-label fw-bold">
                        <i class="fas fa-solid fa-book"></i> Deskripsi Tugas
                    </label>
                    <textarea class="form-control"
                            id="description"
                            name="description"
                            rows="3"
                            placeholder="Deskripsi tugas... (opsional)"></textarea>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Deskripsi detail tentang tugas yang akan dikerjakan
                    </small>
                </div>

                <!-- Tombol Submit -->
                <div class="col-md-12 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Tambah Tugas
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Modal Edit Tampil --}}
    <div class="modal fade" id="editTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editTaskForm" action="{{ route('manager.tasks.update') }}" method="POST">
                    @csrf
                    @method('POST') <input type="hidden" name="id" id="edit_task_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Tugas</h5>
                    </div>
                    <div class="modal-body">
                        <input type="text" name="title" id="edit_title" class="form-control mb-3" placeholder="Nama Tugas" required>
                        <input type="number" name="weight" id="edit_weight" class="form-control mb-3" placeholder="Bobot (%)" required>
                        <select name="assigned_to" id="edit_assigned_to" class="form-control mb-3" required>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Delete Task -->
    <div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="deleteTaskForm" action="{{ route('manager.tasks.delete') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="delete_task_id">

                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Konfirmasi Hapus Tugas</h5>
                    </div>

                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus tugas berikut ini? Tindakan ini tidak dapat dibatalkan.</p>

                        <table class="table table-bordered table-sm mt-3">
                            <tr>
                                <th class="bg-light" style="width: 35%;">Nama Tugas</th>
                                <td id="delete_info_title"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Karyawan</th>
                                <td id="delete_info_employee"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Bobot Tugas</th>
                                <td id="delete_info_weight"></td>
                            </tr>
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Hapus Tugas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Table Tasks --}}
    <div class="card-style">
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

    // Buat bikin priority HIGH paling atas dan LOW paling bawah
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
        ajax: "{{ route('manager.tasks.manage', $project->id) }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
            { data: 'title', name: 'title', width: '10%' },
            { data: 'description', name: 'description', width: '40%' },
            { data: 'employee_name', name: 'assignee.name'},
            {
                data: 'priority_badge',
                name: 'priority',
                // Pake custom sorting yg diatas
                orderDataType: 'priority-sort'
            },
            { data: 'status_badge', name: 'status'},
            { data: 'deadline_format', name: 'deadline'},
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

                // Refresh otomatis setelah 1.2 detik agar user sempat membaca notifikasi
                setTimeout(function() {
                    location.reload();
                }, 1200);
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
        $('#edit_weight').val($(this).data('weight'));
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
        $('#delete_info_employee').text($(this).data('employee'));
        $('#delete_info_weight').text($(this).data('weight') + '%');
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
