<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- ========== All CSS files linkup ========= -->
    <link rel="icon" href="{{ asset('favicon2.png') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/lineicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/main.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <link rel="preload" href="/fonts/LineIcons.woff2" as="font" type="font/woff2" crossorigin>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
    <title>@yield('title')</title>
</head>

<body>
    <!-- ======== sidebar-nav start =========== -->
    <aside class="sidebar-nav-wrapper">
        <div class="navbar-logo" style="display: flex; justify-content: center;">
            <a href="{{ route('manager.dashboard') }}">
                <img src="{{ asset('logo.png') }}" alt="logo" style="width: 200px;" />
            </a>
        </div>
        <nav class="sidebar-nav">
            @include('layouts.sidebarManager')
        </nav>
    </aside>
    <div class="overlay"></div>
    <!-- ======== sidebar-nav end =========== -->

    <!-- ======== main-wrapper start =========== -->
    <main class="main-wrapper bg-light">
        <!-- ========== header start ========== -->
        <header class="header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-6">
                        <div class="header-left d-flex align-items-center">
                            <div class="menu-toggle-btn mr-20">
                                <button id="menu-toggle" class="main-btn wred-btn btn-hover">
                                    <i class="lni lni-chevron-left me-2"></i> {{ __('Menu') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-6">
                        <div class="header-right">
                            <!-- fullscreen toggle start -->
                            <div class="fullscreen-toggle ml-15">
                                <button id="fullscreen-toggle" class="bg-transparent border-0 fs-4 text-secondary"
                                    type="button" title="Toggle Fullscreen">
                                    <i class="fas fa-expand" id="fullscreen-icon"></i>
                                </button>
                            </div>
                            <!-- fullscreen toggle end -->

                            <!-- profile start -->
                            <div class="profile-box ml-15">
                                <button class="dropdown-toggle bg-transparent border-0" type="button" id="profile"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="profile-info">
                                        <div class="info">
                                            <h6>{{ Auth::user()->name }}</h6>
                                        </div>
                                    </div>
                                    <i class="lni lni-chevron-down"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profile">
                                    <li>
                                        <a href="{{ route('manager.profile') }}">
                                            <i class="lni lni-user"></i> {{ __('Profile Saya') }}
                                        </a>
                                    </li>

                                    <li>
                                        <a href="#" class="logout-link" data-url="{{ route('logout') }}">
                                            <i class="lni lni-exit"></i> {{ __('Keluar') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <!-- profile end -->
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- ========== header end ========== -->

        <!-- ========== section start ========== -->
        <section class="section bg-light">
            <div class="container-fluid px-0 px-md-3">

                <!-- Error Handler -->
                @if ($errors->any())
                    <div id="error-message" class="alert alert-danger position-fixed top-0 end-0 m-3"
                        style="max-width: 300px; z-index: 9999;">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (\Session::has('success'))
                    <div id="success-message" class="alert alert-success position-fixed top-0 end-0 m-3"
                        style="max-width: 300px; z-index: 9999;">
                        <p class="mb-0">{{ \Session::get('success') }}</p>
                    </div>
                @endif

                @if (\Session::has('error'))
                    <div id="error-message" class="alert alert-danger position-fixed top-0 end-0 m-3"
                        style="max-width: 300px; z-index: 9999;">
                        <p class="mb-0">{{ \Session::get('error') }}</p>
                    </div>
                @endif

                <div id="message-container"></div>

                <!-- Content -->
                @yield('content')

            </div>
        </section>
        <!-- ========== section end ========== -->

        <!-- ========== footer start =========== -->
        <footer class="footer bg-light">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6 order-last order-md-first">
                        <div class="copyright text-md-start">
                            <p class="text-sm">
                                Developed by
                                <a href="https://www.wahanaritelindo.com/" rel="nofollow" target="_blank"
                                    class="text-red">
                                    Wahana Ritelindo
                                </a>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6 order-last order-md-first">
                        <div class="copyright text-md-end">
                            <p class="text-sm">
                                Version
                                <a class="text-red">
                                    1.0.0
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- ========== footer end =========== -->
    </main>
    <!-- ======== main-wrapper end =========== -->

    <!-- ========= All Javascript files linkup ======== -->
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/Chart.min.js') }}"></script>
    <script src="{{ asset('js/moment.min.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script>

    <style>
        /* Fullscreen toggle styles */
        .fullscreen-toggle button {
            padding: 8px 12px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .fullscreen-toggle button:hover {
            background-color: #e21a1a !important;
            color: white !important;
            transform: scale(1.1);
        }

        .fullscreen-toggle button:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(226, 26, 26, 0.25);
        }

        /* Animation for icon transition */
        #fullscreen-icon {
            transition: transform 0.3s ease;
        }

        .fullscreen-active #fullscreen-icon {
            transform: rotate(45deg);
        }
    </style>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $(document).ready(function() {
            // Handle Logout Link
            $('.logout-link').on('click', function(e) {
                e.preventDefault();

                const logoutUrl = $(this).data('url');

                $.ajax({
                    url: logoutUrl,
                    method: 'POST',
                    data: {
                        '_token': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            showSuccessMessage(response.message);

                            setTimeout(function() {
                                window.location.href = response.redirect_url ||
                                    '/login';
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        // Fallback: redirect to logout with POST
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': logoutUrl
                        });
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': $('meta[name="csrf-token"]').attr('content')
                        }));
                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        });

        // Fungsi global untuk menampilkan pesan sukses
        function showSuccessMessage(message) {
            // Hapus pesan sebelumnya jika ada
            $('#message-container .alert').remove();

            // Buat elemen alert baru dengan style yang sama seperti di layout
            const alertElement = `
                <div class="alert alert-success position-fixed top-0 end-0 m-3" 
                    style="max-width: 300px; z-index: 9999;">
                    <p class="mb-0">${message}</p>
                </div>
            `;

            // Tambahkan ke message-container
            $('#message-container').append(alertElement);

            // Auto-hide setelah 5 detik
            setTimeout(function() {
                $('#message-container .alert').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        // Fungsi global untuk menampilkan pesan error
        function showErrorMessage(message) {
            $('#message-container .alert').remove();

            const alertElement = `
                <div class="alert alert-danger position-fixed top-0 end-0 m-3" 
                    style="max-width: 300px; z-index: 9999;">
                    <p class="mb-0">${message}</p>
                </div>
            `;

            $('#message-container').append(alertElement);

            setTimeout(function() {
                $('#message-container .alert').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Fullscreen functionality
            const fullscreenToggle = document.getElementById('fullscreen-toggle');
            const fullscreenIcon = document.getElementById('fullscreen-icon');

            fullscreenToggle.addEventListener('click', function() {
                if (!document.fullscreenElement) {
                    // Enter fullscreen
                    document.documentElement.requestFullscreen().then(() => {
                        fullscreenIcon.className = 'fas fa-compress';
                        fullscreenToggle.title = 'Exit Fullscreen';
                        document.body.classList.add('fullscreen-active');
                    }).catch(err => {
                        console.error('Error attempting to enable fullscreen:', err);
                    });
                } else {
                    // Exit fullscreen
                    document.exitFullscreen().then(() => {
                        fullscreenIcon.className = 'fas fa-expand';
                        fullscreenToggle.title = 'Toggle Fullscreen';
                        document.body.classList.remove('fullscreen-active');
                    }).catch(err => {
                        console.error('Error attempting to exit fullscreen:', err);
                    });
                }
            });

            // Listen for fullscreen change events (e.g., when user presses ESC)
            document.addEventListener('fullscreenchange', function() {
                if (!document.fullscreenElement) {
                    fullscreenIcon.className = 'fas fa-expand';
                    fullscreenToggle.title = 'Toggle Fullscreen';
                    document.body.classList.remove('fullscreen-active');
                } else {
                    fullscreenIcon.className = 'fas fa-compress';
                    fullscreenToggle.title = 'Exit Fullscreen';
                    document.body.classList.add('fullscreen-active');
                }
            });


            // Auto-hide alert messages
            setTimeout(function() {
                const alerts = document.querySelectorAll('#success-message, #error-message');
                alerts.forEach(alert => {
                    if (alert) {
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 300);
                    }
                });
            }, 5000);
        });
    </script>
    @stack('scripts')
</body>

</html>
