<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('common.app_name')) — {{ __('common.app_name') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap-icons.min.css') }}">
    <!-- Custom Temple Theme -->
    <link rel="stylesheet" href="{{ asset('assets/css/temple-theme.css') }}">

    @stack('styles')
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar Navigation -->
        @include('partials.sidebar')

        <!-- Main Body -->
        <div class="app-main">
            <!-- Top Navbar -->
            @include('partials.navbar')

            <!-- Page Content -->
            <main class="app-content">
                <!-- Alerts / Notifications -->
                @include('partials.alerts')

                @yield('content')
            </main>

            <!-- Footer -->
            @include('partials.footer')
        </div>
    </div>

    <!-- jQuery -->
    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 5 Bundle JS -->
    <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>

    <!-- Sidebar Responsive Toggle Script -->
    <script>
        $(document).ready(function() {
            $('#sidebarToggleBtn').on('click', function() {
                $('#appSidebar').toggleClass('show');
                $('#sidebarBackdrop').toggleClass('show');
            });

            $('#sidebarBackdrop').on('click', function() {
                $('#appSidebar').removeClass('show');
                $(this).removeClass('show');
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
