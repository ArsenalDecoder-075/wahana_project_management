@extends('layouts.appAdmin')
<title>Kelola Kategori</title>

@section('content')
    <div class="container-fluid">
        <div class="title-wrapper pt-30">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="title mb-30">
                        <h2>Data Kategori</h2>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="breadcrumb-wrapper mb-30">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Admin</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Kategori</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Tambah -->
        <button type="button" class="btn btn-success mb-3 btn-sm" data-bs-toggle="modal"
            data-bs-target="#addCategoryModal">
            <i class="fas fa-plus"></i> Tambah Kategori
        </button>

        <!-- MODAL TAMBAH KATEGORI -->
        <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="addCategoryForm">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah Kategori</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="add_name" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_name" name="name" placeholder="Masukkan nama kategori" required>
                                <div class="invalid-feedback" id="add_name_error"></div>
                            </div>
                            <div class="mb-3">
                                <label for="add_description" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="add_description" name="description" rows="3" placeholder="Masukkan deskripsi kategori"></textarea>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" id="add_is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="add_is_active">Aktif</label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success" id="addSubmitBtn">
                                <i class="fas fa-save"></i> Tambahkan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- MODAL EDIT KATEGORI -->
        <!-- ============================================ -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog">
                <form id="editCategoryForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Kategori</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" id="edit_id">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                                <div class="invalid-feedback" id="edit_name_error"></div>
                            </div>
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">Aktif</label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success" id="updateBtn">
                                <i class="fas fa-edit"></i> Ubah
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- MODAL HAPUS KATEGORI -->
        <!-- ============================================ -->
        <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
            <div class="modal-dialog">
                <form id="deleteCategoryForm">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="id" id="delete_id">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Hapus Kategori</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Yakin ingin menghapus kategori <strong id="delete_category_name"></strong>?</p>
                            <p class="text-danger small"><i class="fas fa-exclamation-triangle"></i> Kategori yang sedang digunakan oleh project tidak dapat dihapus</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Ya, Hapus
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- TABLE -->
        <!-- ============================================ -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="display" id="categories-table" style="width: 100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                                {{-- <th>Dibuat</th> --}}
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
        $(document).ready(function() {
            // 1. INIT DATATABLE
            var dataTable = $('#categories-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.manage.categories') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'description', name: 'description' },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    // { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });

            // 2. ADD CATEGORY (AJAX)
            $('#addCategoryForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const formData = form.serialize();
                const submitBtn = $('#addSubmitBtn');
                const originalText = submitBtn.html();

                // Reset error
                $('#add_name').removeClass('is-invalid');
                $('#add_name_error').text('');

                submitBtn.prop('disabled', true).html('Menambahkan...');

                $.ajax({
                    url: "{{ route('admin.store.category') }}",
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#addCategoryModal').modal('hide');
                            form[0].reset();
                            $('#add_is_active').prop('checked', true);
                            dataTable.ajax.reload();
                            showSuccessMessage(response.message);
                        }
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.name) {
                                $('#add_name').addClass('is-invalid');
                                $('#add_name_error').text(errors.name[0]);
                            }
                        } else {
                            showErrorMessage('Terjadi kesalahan saat menambah kategori');
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // 3. EDIT CATEGORY (TANPA getCategory)
            $(document).on('click', '.edit-btn', function() {
                // Ambil data dari atribut button
                const id = $(this).data('id');
                const name = $(this).data('name');
                const description = $(this).data('description');
                const isActive = $(this).data('is_active');

                // Reset error
                $('#edit_name').removeClass('is-invalid');
                $('#edit_name_error').text('');

                // Isi form dengan data dari atribut
                $('#edit_id').val(id);
                $('#edit_name').val(name);
                $('#edit_description').val(description || '');
                $('#edit_is_active').prop('checked', isActive == 1);

                // Tampilkan modal
                $('#editModal').modal('show');
            });

            // 4. UPDATE CATEGORY (AJAX)
            $('#editCategoryForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const formData = form.serialize();
                const updateBtn = $('#updateBtn');
                const originalText = updateBtn.html();

                // Reset error
                $('#edit_name').removeClass('is-invalid');
                $('#edit_name_error').text('');

                updateBtn.prop('disabled', true).html('Mengubah...');

                $.ajax({
                    url: "{{ route('admin.edit.category') }}",
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#editModal').modal('hide');
                            dataTable.ajax.reload();
                            showSuccessMessage(response.message);
                        }
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.name) {
                                $('#edit_name').addClass('is-invalid');
                                $('#edit_name_error').text(errors.name[0]);
                            }
                        } else {
                            showErrorMessage('Terjadi kesalahan saat mengupdate kategori');
                        }
                    },
                    complete: function() {
                        updateBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // 5. DELETE CATEGORY (AJAX)
            $(document).on('click', '.delete-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                
                $('#delete_id').val(id);
                $('#delete_category_name').text(name);
                $('#deleteCategoryModal').modal('show');
            });

            $('#deleteCategoryForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const formData = form.serialize();
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();

                submitBtn.prop('disabled', true).html('Menghapus...');

                $.ajax({
                    url: "{{ route('admin.delete.category') }}",
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#deleteCategoryModal').modal('hide');
                            dataTable.ajax.reload();
                            showSuccessMessage(response.message);
                        } else {
                            showErrorMessage(response.message);
                        }
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                        let errorMsg = 'Terjadi kesalahan saat menghapus kategori';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        showErrorMessage(errorMsg);
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // 6. HELPER FUNCTIONS
            function showSuccessMessage(message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: message,
                    timer: 3000,
                    showConfirmButton: false
                });
            }

            function showErrorMessage(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message,
                    confirmButtonColor: '#d33'
                });
            }

            // 7. RESET FORM
            $('#addCategoryModal').on('hidden.bs.modal', function() {
                $('#addCategoryForm')[0].reset();
                $('#add_name').removeClass('is-invalid');
                $('#add_name_error').text('');
                $('#add_is_active').prop('checked', true);
            });

            $('#editModal').on('hidden.bs.modal', function() {
                $('#editCategoryForm')[0].reset();
                $('#edit_name').removeClass('is-invalid');
                $('#edit_name_error').text('');
            });
        });
    </script>
@endpush