@extends('layouts.appAdmin')
<title>Profile</title>

@section('content')
    <!-- ========== section start ========== -->
    <div class="container-fluid">
        <!-- ========== title-wrapper start ========== -->
        <div class="title-wrapper pt-30">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="title mb-30">
                        <h2>Profile Admin</h2>
                    </div>
                </div>
            </div>
            <!-- end row -->
        </div>
        <!-- ========== title-wrapper end ========== -->

        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="card-style mb-30">
                    <div class="card-body">
                        <!-- Header Profile -->
                        <div class="text-center mb-4 pb-3 border-bottom">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <h4 class="mb-1">{{ $user->name }}</h4>
                            <p class="text-muted mb-0">Administrator</p>
                        </div>

                        <!-- Profile Information -->
                        <div class="profile-info">
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <div class="info-item p-3 bg-light rounded">
                                        <div class="row">
                                            <div class="col-sm-4">
                                                <span class="fw-semibold text-dark">Nama Lengkap</span>
                                            </div>
                                            <div class="col-sm-8">
                                                <span class="text-muted">{{ $user->name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="info-item p-3 bg-light rounded">
                                        <div class="row">
                                            <div class="col-sm-4">
                                                <span class="fw-semibold text-dark">Email</span>
                                            </div>
                                            <div class="col-sm-8">
                                                <span class="text-muted">{{ $user->email }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="text-center pt-3 border-top">
                            <a href="{{ route('admin.changePassword') }}" class="btn btn-success px-4">
                                <i class="fas fa-key me-2"></i>Ubah Password
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
