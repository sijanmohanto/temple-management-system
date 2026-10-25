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
    <!-- Toastr Notifications CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/toastr/toastr.min.css') }}">
    <!-- Select2 CSS with Bootstrap 5 Theme -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/select2-bootstrap-5-theme.min.css') }}">
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/responsive.bootstrap5.min.css') }}">
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
    <!-- Toastr Notifications JS -->
    <script src="{{ asset('assets/vendor/toastr/toastr.min.js') }}"></script>
    <!-- Select2 JS -->
    <script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>
    <!-- DataTables JS & Responsive -->
    <script src="{{ asset('assets/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/responsive.bootstrap5.min.js') }}"></script>

    <!-- Global Temple Configuration -->
    <script>
        window.TempleConfig = {
            locale: "{{ app()->getLocale() }}",
            flashes: {
                success: @json(session('success')),
                error: @json(session('error')),
                warning: @json(session('warning')),
                info: @json(session('info'))
            },
            validationErrors: @json($errors->all()),
            i18n: {
                loading: @json(__('common.loading')),
                processing: @json(__('common.processing')),
                validationError: @json(__('common.validation_error_occurred'))
            },
            select2i18n: {
                placeholder: @json(__('select2.placeholder')),
                noResults: @json(__('select2.no_results')),
                searching: @json(__('select2.searching'))
            },
            datatablesLanguage: @json(trans('datatables'))
        };
    </script>

    <!-- Centralized Temple UI Module -->
    <script src="{{ asset('assets/js/temple-ui.js') }}"></script>

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
