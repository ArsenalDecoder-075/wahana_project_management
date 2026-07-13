@extends('layouts.appAdmin')
<title>Data Admin</title>

@section('content')
    <div class="container-fluid">
        <div class="title-wrapper pt-30">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="title mb-30">
                        <h2>Data User</h2>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="breadcrumb-wrapper mb-30">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="#0">Admin</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    User
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Button to trigger the modal -->
        <button type="button" class="btn btn-success mb-3 btn-sm" data-bs-toggle="modal"
            data-bs-target="#addUserModal">Tambah User</button>

        <!-- Modal for adding a new user -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">Tambah User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addUserForm" method="POST" action="{{ route('admin.addUser') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="type" class="form-label">Tipe User <span class="text-red">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="0">User Cabang</option>
                                    <option value="2">HO</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama <span class="text-red">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Username <span class="text-red">*</span></label>
                                <input type="text" class="form-control" id="email" name="email" required>
                            </div>
                            <!-- Field Cabang (hanya untuk User Cabang) -->
                            <div class="mb-3" id="branchField">
                                <label for="branch_id" class="form-label">Nama Cabang <span
                                        class="text-red">*</span></label>
                                <select class="form-select select2" id="branch_id" name="branch_id" required>
                                    <option selected disabled value="">Pilih Cabang</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->area }} - {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batalkan</button>
                                <button type="submit" class="btn btn-success">Tambahkan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for editing user -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.updateUser') }}">
                    @csrf
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_type" class="form-label">Tipe User <span class="text-red">*</span></label>
                                <select class="form-select" id="edit_type" name="type" required>
                                    <option value="0">User Cabang</option>
                                    <option value="2">HO</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Nama <span class="text-red">*</span></label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Username <span
                                        class="text-red">*</span></label>
                                <input type="text" class="form-control" name="email" id="edit_email" required>
                            </div>
                            <!-- Field Cabang (hanya untuk User Cabang) -->
                            <div class="mb-3" id="edit_branchField">
                                <label for="edit_branch_id" class="form-label">Nama Cabang <span
                                        class="text-red">*</span></label>
                                <select class="form-select select2" name="branch_id" id="edit_branch_id" required>
                                    <option value="">Pilih Cabang</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->area }} - {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal for confirming delete -->
        <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.deleteUser') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Konfirmasi Hapus User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <p>Apakah kamu yakin ingin menghapus User <strong id="delete_user_name"></strong>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal for confirming reset password -->
        <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.resetUserPassword') }}">
                    @csrf
                    <input type="hidden" name="user_id" id="reset_user_id">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Konfirmasi Reset Password</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <p>Apakah kamu yakin ingin mereset password untuk <strong id="reset_user_name"></strong>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning">Ya, Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="display" id="users-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Cabang</th>
                                <th>Username</th>
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
        // Helper function untuk menampilkan pesan error
        function showErrorMessage(message) {
            const errorDiv = $(
                '<div id="error-message" class="alert alert-danger position-fixed top-0 end-0 m-3" style="max-width: 300px; z-index: 9999;"><p class="mb-0">' +
                message + '</p></div>');
            $('body').append(errorDiv);
            setTimeout(() => errorDiv.fadeOut(() => errorDiv.remove()), 5000);
        }

        // Helper function untuk menampilkan pesan sukses
        function showSuccessMessage(message) {
            const successDiv = $(
                '<div id="success-message" class="alert alert-success position-fixed top-0 end-0 m-3" style="max-width: 300px; z-index: 9999;"><p class="mb-0">' +
                message + '</p></div>');
            $('body').append(successDiv);
            setTimeout(() => successDiv.fadeOut(() => successDiv.remove()), 5000);
        }

        $(document).ready(function() {
            // Setup CSRF untuk AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Function untuk reset form dan tutup modal
            function resetFormAndCloseModal(formId, modalId) {
                $(formId)[0].reset();
                $(modalId).modal('hide');
                $('.select2').val(null).trigger('change');
            }

            // Inisialisasi DataTable
            $('#users-table').DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.user') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'branch',
                        name: 'branch'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // AJAX Form Handler - Add User
            $('#addUserForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.text();

                submitBtn.prop('disabled', true).text('Memproses...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            resetFormAndCloseModal('#addUserForm', '#addUserModal');
                            $('#users-table').DataTable().ajax.reload();
                            showSuccessMessage(response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            let errorMessage = 'Validasi gagal:\n';
                            Object.keys(errors).forEach(key => {
                                errorMessage += '- ' + errors[key][0] + '\n';
                            });
                            showErrorMessage(errorMessage);
                        } else {
                            showErrorMessage('Terjadi kesalahan. Silakan coba lagi.');
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });

            // AJAX Form Handler - Edit User
            $('#editUserModal form').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.text();

                submitBtn.prop('disabled', true).text('Memproses...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#editUserModal').modal('hide');
                            $('#users-table').DataTable().ajax.reload();
                            showSuccessMessage(response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            let errorMessage = 'Validasi gagal:\n';
                            Object.keys(errors).forEach(key => {
                                errorMessage += '- ' + errors[key][0] + '\n';
                            });
                            showErrorMessage(errorMessage);
                        } else {
                            showErrorMessage('Terjadi kesalahan. Silakan coba lagi.');
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });

            // AJAX Form Handler - Reset Password
            $('#resetPasswordModal form').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.text();

                submitBtn.prop('disabled', true).text('Memproses...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#resetPasswordModal').modal('hide');
                            showSuccessMessage(response.message);
                        }
                    },
                    error: function(xhr) {
                        showErrorMessage('Terjadi kesalahan. Silakan coba lagi.');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });

            // AJAX Form Handler - Delete User
            $('#deleteUserModal form').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.text();

                submitBtn.prop('disabled', true).text('Memproses...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'DELETE',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#deleteUserModal').modal('hide');
                            $('#users-table').DataTable().ajax.reload();
                            showSuccessMessage(response.message);
                        }
                    },
                    error: function(xhr) {
                        showErrorMessage('Terjadi kesalahan. Silakan coba lagi.');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });

            // Toggle field berdasarkan tipe user (Tambah)
            $('#type').change(function() {
                if ($(this).val() == '2') {
                    $('#branchField').addClass('d-none');
                    $('#branch_id').prop('required', false).val(null).trigger('change');
                } else {
                    $('#branchField').removeClass('d-none');
                    $('#branch_id').prop('required', true);
                }
            }).trigger('change');

            // Toggle field berdasarkan tipe user (Edit)
            $('#edit_type').change(function() {
                if ($(this).val() == '2') {
                    $('#edit_branchField').addClass('d-none');
                    $('#edit_branch_id').prop('required', false).val('').trigger('change');
                } else {
                    $('#edit_branchField').removeClass('d-none');
                    $('#edit_branch_id').prop('required', true);
                }
            });

            // Delete User
            $(document).on('click', '.deleteBtn', function() {
                const userId = $(this).data('id');
                const userName = $(this).closest('tr').find('td:eq(1)').text();

                $('#delete_user_id').val(userId);
                $('#delete_user_name').text(userName);

                new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
            });

            // Edit User
            $(document).on('click', '.editBtn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const email = $(this).data('email');
                const branch = $(this).data('branch');
                const type = $(this).data('type');

                $('#edit_user_id').val(id);
                $('#edit_name').val(name);
                $('#edit_email').val(email);
                $('#edit_type').val(type).trigger('change');

                // Set branch setelah type diset
                setTimeout(function() {
                    if (type == '0' && branch) {
                        $('#edit_branch_id').val(branch).trigger('change');
                    }
                }, 100);

                $('#editUserModal').modal('show');
            });

            // Reset Password
            $(document).on('click', '.resetPasswordBtn', function() {
                const userId = $(this).data('id');
                const userName = $(this).closest('tr').find('td:eq(1)').text();

                $('#reset_user_id').val(userId);
                $('#reset_user_name').text(userName);

                new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
            });
        });
    </script>
@endpush
