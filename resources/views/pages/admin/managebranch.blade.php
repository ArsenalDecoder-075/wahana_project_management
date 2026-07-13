@extends('layouts.appAdmin')
<title>Kelola Cabang</title>

@section('content')
    <div class="container-fluid">
        <div class="title-wrapper pt-30">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="title mb-30">
                        <h2>Data Cabang</h2>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="breadcrumb-wrapper mb-30">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Admin</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Branch</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Tambah -->
        <button type="button" class="btn btn-success mb-3 btn-sm" data-bs-toggle="modal"
            data-bs-target="#addBranchModal">Tambah Cabang</button>

        <!-- Modal Tambah Cabang -->
        <div class="modal fade" id="addBranchModal" tabindex="-1" aria-labelledby="addBranchModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.store.branch') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah Cabang</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="area" class="form-label">Area Cabang<span class="text-red">*</span></label>
                                <select class="form-select" name="area" required>
                                    <option selected disabled value="">Pilih Area</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="city" class="form-label">Kota<span class="text-red">*</span></label>
                                <select class="form-select" name="city" required>
                                    <option selected disabled value="">Pilih Kota</option>
                                    <option value="Jakarta">Jakarta</option>
                                    <option value="Tangerang">Tangerang</option>
                                    <option value="Luar Kota">Luar Kota</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Cabang<span class="text-red">*</span></label>
                                <input class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="initials" class="form-label">Inisial<span class="text-red">*</span></label>
                                <input class="form-control" name="initials" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Kategori<span class="text-red">*</span></label>
                                <select class="form-select" name="category" required>
                                    <option selected disabled value="">Pilih kategori</option>
                                    <option value="H1">H1</option>
                                    <option value="H23">H23</option>
                                    <option value="H123">H123</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Alamat Cabang</label>
                                <input class="form-control" name="address">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Tambahkan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.edit.branch') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Cabang</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" id="branchId">
                            <div class="mb-3">
                                <label class="form-label">Area Cabang<span class="text-red">*</span></label>
                                <select class="form-select" id="area" name="area" required>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kota<span class="text-red">*</span></label>
                                <select class="form-select" id="city" name="city" required>
                                    <option value="Jakarta">Jakarta</option>
                                    <option value="Tangerang">Tangerang</option>
                                    <option value="Luar Kota">Luar Kota</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Cabang<span class="text-red">*</span></label>
                                <input class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Inisial<span class="text-red">*</span></label>
                                <input class="form-control" id="initials" name="initials" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kategori<span class="text-red">*</span></label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="H1">H1</option>
                                    <option value="H23">H23</option>
                                    <option value="H123">H123</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat Cabang</label>
                                <input class="form-control" id="address" name="address">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-success" id="updateBtn">Ubah</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Delete -->
        <div class="modal fade" id="deleteBranchModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.delete.branch') }}">
                    @csrf
                    <input type="hidden" name="id" id="delete_branch_id">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Hapus Cabang</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Yakin ingin menghapus cabang <strong id="delete_branch_name"></strong>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="display" id="users-table" style="width: 100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Area</th>
                                <th>Kota</th>
                                <th>Nama Cabang</th>
                                <th>Inisial</th>
                                <th>Kategori</th>
                                <th>Alamat</th>
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
            var dataTable = $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.manage.branch') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        className: "text-center",
                        data: 'area',
                        name: 'area'
                    },
                    {
                        data: 'city',
                        name: 'city'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        className: "text-center",
                        data: 'initials',
                        name: 'initials'
                    },
                    {
                        className: "text-center",
                        data: 'category',
                        name: 'category'
                    },
                    {
                        data: 'address',
                        name: 'address'
                    },
                    {
                        data: null,
                        className: "text-center",
                        render: function(data) {
                            return `
                <div class="btn-group">
                <button class="btn btn-sm btn-primary me-1 rounded editBtn" data-id="${data.id}" title="Edit"><i class="fa fa-edit"></i></button>
                <button class="btn btn-sm btn-danger me-1 rounded deleteBtn" data-id="${data.id}" data-name="${data.area} - ${data.name}" title="Hapus"><i class="fa fa-trash"></i></button>
                </div>
                `;
                        }
                    },
                ]

            });

            $('#addBranchModal form').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const formData = form.serialize();
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();

                submitBtn.prop('disabled', true).html('Menambahkan...');
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#addBranchModal').modal('hide');
                        form[0].reset();
                        dataTable.ajax.reload();
                        showSuccessMessage(response.message || 'Cabang berhasil ditambahkan!');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error adding branch:', error);
                        let errorMsg = 'Terjadi kesalahan saat menambah cabang';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = Object.values(xhr.responseJSON.errors).flat();
                            errorMsg = errors.join('<br>');
                        }
                        showErrorMessage(errorMsg);
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Open edit modal
            $(document).on('click', '.editBtn', function() {
                const data = dataTable.row($(this).parents('tr')).data();
                $('#branchId').val(data.id);
                $('#area').val(data.area);
                $('#city').val(data.city);
                $('#name').val(data.name);
                $('#initials').val(data.initials);
                $('#category').val(data.category);
                $('#address').val(data.address);
                $('#editModal').modal('show');
            });

            // Submit edit dengan AJAX
            $('#updateBtn').click(function() {
                const form = $(this).closest('form');
                const formData = form.serialize();
                const updateBtn = $(this);
                const originalText = updateBtn.html();

                updateBtn.prop('disabled', true).html('Mengubah...');
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#editModal').modal('hide');
                        dataTable.ajax.reload();
                        showSuccessMessage(response.message || 'Cabang berhasil diupdate!');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error updating branch:', error);
                        let errorMsg = 'Terjadi kesalahan saat mengupdate cabang';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = Object.values(xhr.responseJSON.errors).flat();
                            errorMsg = errors.join('<br>');
                        }
                        showErrorMessage(errorMsg);
                    },
                    complete: function() {
                        updateBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Handle Delete dengan AJAX
            $('#deleteBranchModal form').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const formData = form.serialize();
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();

                submitBtn.prop('disabled', true).html('Menghapus...');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#deleteBranchModal').modal('hide');
                        dataTable.ajax.reload();
                        showSuccessMessage(response.message || 'Cabang berhasil dihapus!');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error deleting branch:', error);
                        showErrorMessage('Terjadi kesalahan saat menghapus cabang');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Delete modal
            $(document).on('click', '.deleteBtn', function() {
                $('#delete_branch_id').val($(this).data('id'));
                $('#delete_branch_name').text($(this).data('name'));
                $('#deleteBranchModal').modal('show');
            });
        });
    </script>
@endpush
