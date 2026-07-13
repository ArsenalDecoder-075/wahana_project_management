@extends('layouts.appAdmin')
@section('title', 'Kelola Hierarki Tim')

@section('content')
<div class="container-fluid">
    <div class="title-wrapper pt-30">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="title mb-30">
                    <h2>Hierarki Karyawan</h2>
                </div>
            </div>
            <div class="col-md-6">
                <div class="breadcrumb-wrapper mb-30">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#">Admin</a></li>
                            <li class="breadcrumb-item active">Hierarki</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info alert-dismissible fade show mb-4">
        <div class="d-flex align-items-start">
            <div class="me-3"><i class="fas fa-code fa-2x text-primary"></i></div>
            <div class="flex-grow-1">
                <h5 class="alert-heading">Development Info: Hierarki 1 Level</h5>
                <p><span class="badge bg-primary">Type</span> Single-Level Hierarchy (1 Level / Flat Structure)</p>
                <p><span class="badge bg-info">Structure</span> Manager (Atasan) → Employee (Bawahan)</p>
                <p class="mb-0"><span class="badge bg-warning text-dark">Note</span> Hanya mendukung 1 level atasan-bawahan.</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>

    <!-- Tombol Atur Manajer -->
    {{-- Ini buat semuan manager + karyawan --}}
    <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#assignManagerModal">
        <i class="fas fa-user-plus"></i> Atur Manajer Karyawan
    </button>

    <!-- Modal Tambah -->
    <div class="modal fade" id="assignManagerModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.hierarchy.store') }}" id="assignManagerForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="assignManagerModalLabel">Atur Manajer Karyawan</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="manager_id" id="hidden_manager_id" value="">

                        <div class="mb-3">
                            <label for="manager_id_select" class="form-label fw-bold">Pilih Manajer <span class="text-danger">*</span></label>
                            <select class="form-select select2" id="manager_id_select">
                                <option value="">Pilih Manajer</option>
                                @foreach ($managers as $mgr)
                                    <option value="{{ $mgr->id }}">{{ $mgr->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted" id="manager_locked_info" style="display:none;">
                                <i class="fas fa-lock"></i> Manajer terkunci (dari tombol Tambah)
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="employee_id" class="form-label fw-bold">Pilih Karyawan <span class="text-danger">*</span></label>
                            <select class="form-select select2" name="employee_id" id="employee_id" required>
                                <option value="">Pilih Karyawan</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->branch ? $emp->branch->name : 'Tanpa Cabang' }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hanya karyawan yang belum memiliki manajer</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Unassign -->
    <div class="modal fade" id="unassignModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.hierarchy.unassign') }}">
                @csrf
                <input type="hidden" name="employee_id" id="unassign_employee_id">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Cabut Manajer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Yakin ingin mencabut manajer untuk <strong id="unassign_employee_name"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Ya, Cabut</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="display" id="hierarchy-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Manager</th>
                            <th>Cabang</th>
                            <th>Anggota Pada Hierarki</th>
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
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // DataTable
    var table = $('#hierarchy-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.manage.hierarchy') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
            { data: 'manager_display', name: 'manager_name', width: '15%' },
            { data: 'branch_display', name: 'branch_display', defaultContent: '-', width: '15%' },
            { data: 'employee_display', name: 'employee_display', orderable: false, searchable: false, width: '40%' },
            { data: 'action', name: 'action', orderable: false, searchable: false, width: '15%' }
        ],
        order: [[1, 'asc']],
        pageLength: 10,
        language: {
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ entri',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
            infoEmpty: 'Tidak ada data',
            zeroRecords: 'Tidak ada data hierarki'
        }
    });

    // Toast notifikasi
    function tampilkanNotifikasi(pesan, tipe = 'success') {
        if ($('#toast-container-kiri').length === 0) {
            $('body').append('<div id="toast-container-kiri" style="position:fixed; top:20px; right:20px; z-index:1055; min-width:300px;"></div>');
        }
        var bg = tipe === 'success' ? 'bg-success' : 'bg-danger';
        var icon = tipe === 'success' ? '✅' : '❌';
        var html = `<div class="toast show align-items-center text-white ${bg} border-0 mb-2 shadow-lg">
                        <div class="d-flex p-3"><div class="toast-body fw-bold">${icon} ${pesan}</div></div>
                    </div>`;
        $('#toast-container-kiri').append(html);
        setTimeout(function() {
            $('#toast-container-kiri .toast:first').remove();
        }, 3000);
    }

    // Modal Assign Manager – dua mode
    $('#assignManagerModal').on('show.bs.modal', function (event) {
        var managerId = $(this).data('manager-id');
        var managerName = $(this).data('manager-name');

        if (managerId) {
            $('#hidden_manager_id').val(managerId);
            $('#manager_id_select').val(managerId).prop('disabled', true);
            $('#manager_locked_info').show();
            $('#assignManagerModalLabel').text('Tambah Karyawan untuk ' + managerName);
        } else {
            $('#hidden_manager_id').val('');
            $('#manager_id_select').prop('disabled', false).val('');
            $('#manager_locked_info').hide();
            $('#assignManagerModalLabel').text('Atur Manajer Karyawan');
        }
    });

    $('#assignManagerModal').on('hidden.bs.modal', function () {
        $(this).removeData('manager-id').removeData('manager-name');
        $('#manager_id_select').prop('disabled', false).val('');
        $('#hidden_manager_id').val('');
        $('#manager_locked_info').hide();
    });

    // Tombol "Atur Manajer Karyawan" di atas
    $('button[data-bs-target="#assignManagerModal"]').on('click', function() {
        $('#assignManagerModal').removeData('manager-id').removeData('manager-name');
    });

    // Tombol Tambah di kolom Aksi
    $(document).on('click', '.addEmployeeBtn', function() {
        var managerId = $(this).data('manager-id');
        var managerName = $(this).data('manager-name');
        $('#assignManagerModal').data('manager-id', managerId);
        $('#assignManagerModal').data('manager-name', managerName);
        $('#assignManagerModal').modal('show');
    });

    // Submit form tambah
    $('#assignManagerForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var btn = form.find('button[type="submit"]');
        btn.prop('disabled', true).html('Menyimpan...');

        var managerId = $('#hidden_manager_id').val();
        if (!$('#manager_id_select').prop('disabled')) {
            managerId = $('#manager_id_select').val();
        }
        $('#hidden_manager_id').val(managerId);

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(res) {
                $('#assignManagerModal').modal('hide');
                tampilkanNotifikasi(res.message, 'success');
                setTimeout(() => location.reload(), 1200);
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                tampilkanNotifikasi(msg, 'error');
                btn.prop('disabled', false).html('Simpan');
            }
        });
    });

    // Unassign
    $(document).on('click', '.unassignBtn', function() {
        $('#unassign_employee_id').val($(this).data('id'));
        $('#unassign_employee_name').text($(this).data('name'));
        $('#unassignModal').modal('show');
    });

    $('#unassignModal form').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('Mencabut...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                $('#unassignModal').modal('hide');
                tampilkanNotifikasi(res.message, 'success');
                setTimeout(() => location.reload(), 1200);
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || 'Gagal cabut';
                tampilkanNotifikasi(msg, 'error');
                btn.prop('disabled', false).html('Ya, Cabut');
            }
        });
    });
});
</script>
@endpush
