<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rapportini')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-gradient: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            --primary-color: #dc2626;
            --dark-color: #1a1a1a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1a1a1a 0%, #000000 100%);
            color: white;
            transition: transform 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h3 {
            font-size: 24px;
            font-weight: 700;
            margin: 10px 0 5px 0;
        }

        .sidebar-header p {
            font-size: 12px;
            opacity: 0.7;
            margin: 0;
        }

        .sidebar-menu {
            padding: 20px 0;
            flex: 1;
            overflow-y: auto;
            scrollbar-width: none;
        }

        .sidebar-menu::-webkit-scrollbar {
            display: none;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .menu-item:hover {
            background-color: rgba(220, 38, 38, 0.1);
            color: white;
            border-left-color: #dc2626;
        }

        .menu-item.active {
            background-color: rgba(220, 38, 38, 0.2);
            color: white;
            border-left-color: #dc2626;
        }

        .menu-item i {
            font-size: 20px;
            margin-right: 15px;
            width: 25px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .topbar {
            position: sticky;
            top: 0;
            height: 65px;
            background: white;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            z-index: 999;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #2d3748;
        }

        .topbar-title {
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
        }

        .content-wrapper {
            padding: 30px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: none;
        }

        .card-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px;
            border: none;
        }

        .card-header h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .card-body {
            padding: 30px;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
        }

        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            border: 2px solid #e9ecef;
        }

        .form-check-input:checked {
            background-color: #dc2626;
            border-color: #dc2626;
        }

        .invalid-feedback {
            display: block;
            font-size: 14px;
            margin-top: 5px;
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 38, 38, 0.3);
        }

        .btn-danger {
            background: #dc2626;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 14px;
        }

        .btn-warning {
            background: #1a1a1a;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 14px;
            color: white;
        }

        .btn-warning:hover {
            background: #000000;
            color: white;
        }

        .btn-info {
            background: #4b5563;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 14px;
            color: white;
        }

        .btn-info:hover {
            background: #374151;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: #2d3748;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-success {
            background-color: #10b981;
            color: white;
        }

        .badge-danger {
            background-color: #dc2626;
            color: white;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .alert-danger {
            background-color: #fee;
            color: #c33;
        }

        .alert-info {
            background-color: #e7f5ff;
            border: none;
            border-radius: 10px;
            color: #0066cc;
            padding: 12px 16px;
        }

        .logout-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #dc3545;
            color: white;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }

        @media (max-height: 760px) {
            .sidebar-header {
                padding: 12px 20px;
            }
            .sidebar-header i {
                font-size: 28px !important;
            }
            .sidebar-header h3 {
                font-size: 20px;
                margin: 6px 0 3px;
            }
            .menu-item {
                padding: 10px 25px;
            }
        }

        @media (max-height: 620px) {
            .sidebar-header {
                padding: 8px 20px;
            }
            .sidebar-header i {
                font-size: 20px !important;
            }
            .sidebar-header h3 {
                font-size: 16px;
                margin: 4px 0 2px;
            }
            .sidebar-header p {
                display: none;
            }
            .sidebar-menu {
                padding: 8px 0;
            }
            .menu-item {
                padding: 7px 25px;
                font-size: 14px;
            }
            .menu-item i {
                font-size: 16px;
                margin-right: 10px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .topbar {
                padding: 0 15px;
            }

            .topbar-title {
                font-size: 20px;
            }

            .content-wrapper {
                padding: 20px 15px;
            }

            .table {
                font-size: 14px;
            }
        }

        @yield('extra-css')
    </style>
    @stack('styles')
    @livewireStyles
</head>
<body>
    @include('layouts.sidebar')

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="topbar-title">@yield('page-title')</h1>
            </div>
        </div>

        <div class="content-wrapper">
            @yield('content')
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        @if(session('success'))
            <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if($errors->any())
            @foreach($errors->all() as $error)
                <div class="toast align-items-center text-bg-warning border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="7000">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ $error }}
                        </div>
                        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#menuToggle').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('#sidebarOverlay').toggleClass('active');
            });

            $('#sidebarOverlay').on('click', function() {
                $('#sidebar').removeClass('active');
                $(this).removeClass('active');
            });

            $(window).on('resize', function() {
                if ($(window).width() > 768) {
                    $('#sidebar').removeClass('active');
                    $('#sidebarOverlay').removeClass('active');
                }
            });

            // Mostra i toast di Bootstrap
            $('.toast').each(function() {
                var toast = new bootstrap.Toast(this);
                toast.show();
            });
        });
    </script>
    @yield('extra-js')
    @stack('scripts')
    @livewireScripts
</body>
</html>
