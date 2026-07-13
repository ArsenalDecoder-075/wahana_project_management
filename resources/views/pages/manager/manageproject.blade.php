@extends('layouts.appManager')
<title>Kelola Proyek</title>

@section('content')
    <div class="container-fluid">
        <div class="title-wrapper pt-30">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="title mb-30">
                        <h2>Manajemen Proyek & Tugas</h2>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="breadcrumb-wrapper mb-30">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('manager.dashboard') }}">Admin</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    Proyek
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-success mb-3 btn-sm" data-bs-toggle="modal" data-bs-target="#addProjectModal">
            Tambah Proyek Baru
        </button>

        <!-- Modal Tambah Proyek -->
        <div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProjectModalLabel">Buat Proyek Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addProjectForm" method="POST" action="{{ route('manager.projects.store') }}">
                            @csrf

                            {{-- Tampilkan nama manager yang login (readonly) --}}
                            <div class="mb-3">
                                <label class="form-label">Penanggung Jawab <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly disabled>
                                <small class="text-muted">Anda akan menjadi penanggung jawab proyek ini.</small>
                            </div>

                            <div class="mb-3">
                                <label for="title" class="form-label">Nama Proyek <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi Proyek</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="start_date" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>

                            <div class="mb-3">
                                <label for="end_date" class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batalkan</button>
                                <button type="submit" class="btn btn-success">Simpan Proyek</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Edit Proyek -->
        <div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="editProjectForm" method="POST" action="{{ route('manager.projects.update') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="editProjectModalLabel">Edit Proyek</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="edit_project_id" name="project_id">

                            <div class="mb-3">
                                <label>Nama Proyek <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_title" name="title" required>
                            </div>

                            {{-- Tampilkan nama manager (readonly) --}}
                            <div class="mb-3">
                                <label>Penanggung Jawab</label>
                                <input type="text" class="form-control" id="edit_manager_name" value="{{ Auth::user()->name }}" readonly disabled>
                                <small class="text-muted">Penanggung jawab tidak dapat diubah.</small>
                            </div>

                            <div class="mb-3">
                                <label>Deskripsi</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label>Mulai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Selesai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label>Status <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" id="edit_status">
                                    <option value="Pending">Pending</option>
                                    <option value="On Progress">On Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteProjectModal" tabindex="-1">
            <div class="modal-dialog">
                <form id="deleteProjectForm" method="POST" action="{{ route('manager.projects.delete') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Konfirmasi Hapus</h5>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="delete_project_id" name="project_id">
                            <p>Apakah Anda yakin ingin menghapus proyek <b><span id="delete_title"></span></b>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Hapus Proyek</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delegasikan Tugas Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addTaskForm" method="POST" action="#">
                            @csrf
                            <input type="hidden" name="project_id" id="task_project_id">

                            <div class="mb-3">
                                <label class="form-label">Proyek Terpilih</label>
                                <input type="text" class="form-control" id="task_project_title" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="task_title" class="form-label">Nama Tugas <span class="text-red">*</span></label>
                                <input type="text" class="form-control" id="task_title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="assigned_to" class="form-label">Penerima Tugas (Karyawan) <span class="text-red">*</span></label>
                                <select class="form-select select2" id="assigned_to" name="assigned_to" required>
                                    <option value="" selected disabled>Pilih Anggota Tim</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="weight" class="form-label">Bobot Progres (%) <span class="text-red">*</span></label>
                                <input type="number" class="form-control" id="weight" name="weight" min="1" max="100" required>
                                <small class="text-muted">Nilai kontribusi tugas terhadap total progres proyek.</small>
                            </div>
                            <div class="mb-3">
                                <label for="deadline" class="form-label">Batas Waktu (Deadline) <span class="text-red">*</span></label>
                                <input type="date" class="form-control" id="deadline" name="deadline" required>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Kirim ke Karyawan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="display" id="projects-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Proyek</th>
                                {{-- <th>Penanggung Jawab</th> --}}
                                <th>Durasi Kontrak</th>
                                <th>Progres Sistem</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Menggunakan Toast Helper System bawaan aplikasi Anda
        function showErrorMessage(message) {
            const errorDiv = $('<div id="error-message" class="alert alert-danger position-fixed top-0 end-0 m-3" style="max-width: 300px; z-index: 9999;"><p class="mb-0">' + message + '</p></div>');
            $('body').append(errorDiv);
            setTimeout(() => errorDiv.fadeOut(() => errorDiv.remove()), 5000);
        }

        function showSuccessMessage(message) {
            const successDiv = $('<div id="success-message" class="alert alert-success position-fixed top-0 end-0 m-3" style="max-width: 300px; z-index: 9999;"><p class="mb-0">' + message + '</p></div>');
            $('body').append(successDiv);
            setTimeout(() => successDiv.fadeOut(() => successDiv.remove()), 5000);
        }

        $(document).ready(function() {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });

            function resetFormAndCloseModal(formId, modalId) {
                $(formId)[0].reset();
                $(modalId).modal('hide');
                $('.select2').val(null).trigger('change');
            }

            // Inisialisasi DataTable Proyek (AJAX Server Side)
            $('#projects-table').DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                ajax: "{{ route('manager.manage.project') }}",
                // ajax: "{{ route(request()->route()->getName()) }}", // Mengambil data otomatis dari route index aktif
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'title', name: 'title' },
                    { data: 'duration', name: 'duration' },
                    { data: 'total_tasks', name: 'total_tasks' },
                    { data: 'status', name: 'status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });

            // AJAX Form Handler - Tambah Proyek
            $('#addProjectForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.text();

                submitBtn.prop('disabled', true).text('Memproses...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        resetFormAndCloseModal('#addProjectForm', '#addProjectModal');
                        $('#projects-table').DataTable().ajax.reload();
                        showSuccessMessage(response.message || 'Proyek baru berhasil didaftarkan!');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let msg = 'Validasi Gagal:\n';
                            Object.keys(errors).forEach(key => { msg += '- ' + errors[key][0] + '\n'; });
                            showErrorMessage(msg);
                        } else {
                            showErrorMessage('Gagal menyimpan proyek. Periksa kembali form data.');
                        }
                    },
                    complete: function() { submitBtn.prop('disabled', false).text(originalText); }
                });
            });

            // Action Trigger - Menampilkan Modal Tambah Tugas berdasarkan Proyek Terpilih
            $(document).on('click', '.addTaskBtn', function() {
                const projectId = $(this).data('id');
                const projectTitle = $(this).data('title');

                $('#task_project_id').val(projectId);
                $('#task_project_title').val(projectTitle);

                $('#addTaskModal').modal('show');
            });

            // AJAX Form Handler - Tambah Tugas Baru
            $('#addTaskForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.text();

                submitBtn.prop('disabled', true).text('Mengirim...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        resetFormAndCloseModal('#addTaskForm', '#addTaskModal');
                        $('#projects-table').DataTable().ajax.reload();
                        showSuccessMessage(response.message || 'Tugas berhasil didelegasikan ke Karyawan!');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let msg = 'Validasi Gagal:\n';
                            Object.keys(errors).forEach(key => { msg += '- ' + errors[key][0] + '\n'; });
                            showErrorMessage(msg);
                        } else {
                            showErrorMessage('Gagal mendistribusikan tugas.');
                        }
                    },
                    complete: function() { submitBtn.prop('disabled', false).text(originalText); }
                });
            });

            // Event Edit Project - Buka Modal Edit
            $(document).on('click', '.editProjectBtn', function() {
                let id = $(this).data('id');
                let title = $(this).data('title');
                let description = $(this).data('description') || '';
                let startDate = $(this).data('start');
                let endDate = $(this).data('end');
                let status = $(this).data('status') || 'Pending';

                $('#edit_project_id').val(id);
                $('#edit_title').val(title);
                $('#edit_description').val(description);
                $('#edit_start_date').val(startDate);
                $('#edit_end_date').val(endDate);
                $('#edit_status').val(status);

                $('#editProjectModal').modal('show');
            });

            // AJAX Form Handler - Edit Proyek
            $('#editProjectForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.text();

                submitBtn.prop('disabled', true).text('Menyimpan...');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        $('#editProjectModal').modal('hide');
                        $('#projects-table').DataTable().ajax.reload();
                        showSuccessMessage(response.message || 'Proyek berhasil diperbarui!');
                        submitBtn.prop('disabled', false).text(originalText);
                    },
                    error: function(xhr) {
                        let errorMsg = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Terjadi kesalahan saat menyimpan.';
                        showErrorMessage(errorMsg);
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });

            // AJAX Form Handler - Tambah Tugas
            $('#addTaskForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.text();

                submitBtn.prop('disabled', true).text('Mengirim...');

                $.ajax({
                    url: "{{ route('manager.tasks.store') }}", // ✅ Route manager
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        resetFormAndCloseModal('#addTaskForm', '#addTaskModal');
                        $('#projects-table').DataTable().ajax.reload();
                        showSuccessMessage(response.message || 'Tugas berhasil didelegasikan!');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let msg = 'Validasi Gagal:\n';
                            Object.keys(errors).forEach(key => { msg += '- ' + errors[key][0] + '\n'; });
                            showErrorMessage(msg);
                        } else {
                            let errorMsg = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : 'Gagal mendistribusikan tugas.';
                            showErrorMessage(errorMsg);
                        }
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });

            // EVENT DELETE
            $(document).on('click', '.deleteProjectBtn', function() {
                let id = $(this).data('id');
                let title = $(this).data('title');

                if(confirm('Apakah Anda yakin ingin menghapus proyek "' + title + '"?')) {
                    $.ajax({
                        url: "{{ route('manager.projects.delete') }}",
                        method: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            project_id: id
                        },
                        success: function(response) {
                            $('#projects-table').DataTable().ajax.reload();
                            alert(response.message);
                        },
                        error: function() { alert('Gagal menghapus proyek'); }
                    });
                }
            });
        });
    </script>
@endpush
