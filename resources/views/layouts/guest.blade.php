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
    <!-- Custom Temple Theme -->
    <link rel="stylesheet" href="{{ asset('assets/css/temple-theme.css') }}">

    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <!-- Top Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark" href="{{ route('home') }}">
                <span class="temple-emblem">ॐ</span>
                <span>{{ __('common.app_name') }}</span>
            </a>

            <div class="d-flex align-items-center gap-3">
                <!-- Language Switcher -->
                <div class="d-flex align-items-center bg-light rounded-pill p-1 border">
                    <a href="{{ route('lang.switch', 'en') }}"
                       class="lang-badge {{ app()->getLocale() === 'en' ? 'active' : '' }}"
                       title="Switch to English">
                        EN
                    </a>
                    <a href="{{ route('lang.switch', 'bn') }}"
                       class="lang-badge {{ app()->getLocale() === 'bn' ? 'active' : '' }}"
                       title="বাংলায় পরিবর্তন করুন">
                        বাং
                    </a>
                </div>

                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-sm temple-btn-maroon">
                        <i class="bi bi-speedometer2 me-1"></i> {{ __('nav.dashboard') }}
                    </a>
                @else
                    @if(!request()->routeIs('login'))
                        <a href="{{ route('login') }}" class="btn btn-sm temple-btn-maroon">
                            <i class="bi bi-box-arrow-in-right me-1"></i> {{ __('nav.login') }}
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>

    <!-- Content -->
    <main class="flex-grow-1 py-4">
        <div class="container">
            @include('partials.alerts')
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    @include('partials.footer')

    <!-- jQuery -->
    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 5 Bundle JS -->
    <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <!-- Toastr Notifications JS -->
    <script src="{{ asset('assets/vendor/toastr/toastr.min.js') }}"></script>

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
            }
        };
    </script>
    <script src="{{ asset('assets/js/temple-ui.js') }}"></script>

    @stack('scripts')
</body>
</html>
