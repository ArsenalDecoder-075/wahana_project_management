@extends('layouts.appManager')
<title>Ubah Password</title>
 
@section('content')
<!-- ========== section start ========== -->
    <div class="container-fluid">
        <!-- ========== title-wrapper start ========== -->
        <div class="title-wrapper pt-30">
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="title mb-15">
                  <h2>Ubah Password</h2>
                </div>
              </div>
            </div>
            <!-- end row -->
          </div>
        <!-- end row -->

        <!-- ========== title-wrapper end ========== -->

        <div class="row justify-content-center mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Ganti Password anda</div>
                    <div class="card-body">
                        <form action="{{ route('manager.updatePassword') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="old_password" class="form-label">Password Lama</label>
                                <input type="password" name="old_password" id="old_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Password Baru</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8">
                            </div>
                            <div class="mb-3">
                                <label for="new_password_confirmation" class="form-label">Ulangi Password Baru</label>
                                <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success">Ubah Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

@endsection