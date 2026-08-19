@extends('layouts.appManager')
<title>Kelola Hierarki Tim</title>

@section('content')
    <div class="container-fluid">
        <div class="title-wrapper pt-30">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="title mb-30">
                        <h2>Kelola Bawahan</h2>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="breadcrumb-wrapper mb-30">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Manager</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Kelola Bawahan</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert" style="border-left: 4px solid #0d6efd; background-color: #f0f7ff;">
            <div class="d-flex align-items-start">
                <div class="me-3">
                    <i class="fas fa-users-cog fa-2x text-primary"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Kelola Bawahan Anda
                    </h5>
                    <p class="mb-1">
                        <span class="badge bg-success me-2"><i class="fas fa-user-check"></i></span>
                        <strong>Anda dapat menambah atau mencabut bawahan yang berada di bawah tanggung jawab Anda.</strong>
                    </p>
                    <p class="mb-0">
                        <span class="badge bg-warning text-dark me-2"><i class="fas fa-exclamation-triangle"></i></span>
                        <span class="text-muted">
                            Hanya karyawan (type: User) yang dapat dijadikan bawahan.
                        </span>
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>

        <!-- Tombol Tambah Bawahan -->
        <button type="button" class="btn btn-success mb-3 btn-sm" data-bs-toggle="modal"
            data-bs-target="#assignManagerModal">
            <i class="fas fa-user-plus"></i> Tambah Bawahan
        </button>

        <!-- Modal Tambah Bawahan -->
        <div class="modal fade" id="assignManagerModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" id="assignForm" action="{{ route('manager.hierarchy.store') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-user-plus text-success me-2"></i>Tambah Bawahan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="manager_id" class="form-label">Manajer (Anda) <span class="text-danger">*</span></label>
                                <select class="form-select" id="manager_id" disabled>
                                    @foreach ($managers as $mgr)
                                        <option value="{{ $mgr->id }}" selected>{{ $mgr->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="manager_id" value="{{ Auth::id() }}">
                                <small class="text-muted">Anda akan menjadi atasan dari karyawan yang dipilih.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pilih Karyawan <span class="text-danger">*</span></label>
                                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px;">
                                    @forelse ($availableEmployees as $emp)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="employee_ids[]" value="{{ $emp->id }}" id="emp_{{ $emp->id }}">
                                            <label class="form-check-label" for="emp_{{ $emp->id }}">
                                                {{ $emp->name }}
                                                @if($emp->branch)
                                                    <small class="text-muted">({{ $emp->branch->name }})</small>
                                                @else
                                                    <small class="text-muted">(Tanpa Cabang)</small>
                                                @endif
                                            </label>
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0">Tidak ada karyawan tersedia.</p>
                                    @endforelse
                                </div>
                                <small class="text-muted">Hanya menampilkan karyawan yang belum memiliki manajer.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Tambah Bawahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Cabut Bawahan -->
        <div class="modal fade" id="unassignModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" id="unassignForm" action="{{ route('manager.hierarchy.unassign') }}">
                    @csrf
                    <input type="hidden" name="employee_id" id="unassign_employee_id">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-user-minus text-warning me-2"></i>Cabut Bawahan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Yakin ingin mencabut status bawahan untuk karyawan <strong id="unassign_employee_name"></strong>?</p>
                            <p class="text-sm text-muted">Karyawan ini akan kehilangan akses ke tugas-tugas yang diberikan oleh Anda.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning">Ya, Cabut</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="display" id="hierarchy-table" style="width: 100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Karyawan</th>
                                <th>Cabang</th>
                                <th>Manajer (Atasan)</th>
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
    // Setup CSRF Token
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // 1. Inisialisasi DataTable
    var dataTable = $('#hierarchy-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('manager.manage.hierarchy') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'employee_name', name: 'users.name' },
            { data: 'branch_name', name: 'branches.name' },
            {
                data: 'manager_name',
                name: 'manager.name',
                render: function(data) {
                    return data ? `<span class="badge bg-success">${data}</span>` : `<span class="badge bg-secondary">Belum Diatur</span>`;
                }
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
        ]
    });

    // 2. Toast Notification
    function tampilkanNotifikasi(pesan, tipe = 'success') {
        if ($('#toast-container-kiri').length === 0) {
            $('body').append('<div id="toast-container-kiri" style="position: fixed; top: 20px; right: 20px; z-index: 1055; min-width: 320px;"></div>');
        }

        var bgWarna = tipe === 'success' ? 'bg-success' : 'bg-danger';
        var simbol = tipe === 'success' ? '✅' : '❌';

        var toastHtml = `
            <div class="toast show align-items-center text-white ${bgWarna} border-0 mb-2 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: 6px;">
                <div class="d-flex p-3">
                    <div class="toast-body fw-bold">
                        ${simbol} ${pesan}
                    </div>
                </div>
            </div>
        `;
        $('#toast-container-kiri').append(toastHtml);
    }

    // 3. Handle Form Tambah Bawahan
    $('#assignForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('Menyimpan...');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#assignManagerModal').modal('hide');
                tampilkanNotifikasi(response.message || 'Bawahan berhasil ditambahkan!', 'success');
                setTimeout(function() { location.reload(); }, 1200);
            },
            error: function(xhr) {
                let errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan!';
                tampilkanNotifikasi(errorMsg, 'error');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // 4. Buka Modal Cabut Bawahan
    $(document).on('click', '.unassignBtn', function() {
        $('#unassign_employee_id').val($(this).data('id'));
        $('#unassign_employee_name').text($(this).data('name'));
        $('#unassignModal').modal('show');
    });

    // 5. Handle Submit Cabut Bawahan
    $('#unassignForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('Mencabut...');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#unassignModal').modal('hide');
                tampilkanNotifikasi(response.message || 'Bawahan berhasil dicabut!', 'success');
                setTimeout(function() { location.reload(); }, 1200);
            },
            error: function(xhr) {
                let errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan!';
                tampilkanNotifikasi(errorMsg, 'error');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endpush
