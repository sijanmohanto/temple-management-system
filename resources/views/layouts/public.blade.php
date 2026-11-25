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
    <!-- Select2 CSS with Bootstrap 5 Theme -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/select2-bootstrap-5-theme.min.css') }}">
    <!-- Toastr Notifications CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/toastr/toastr.min.css') }}">
    <!-- DataTables CSS with Bootstrap 5 Theme -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/responsive.bootstrap5.min.css') }}">
    <!-- Custom Temple Theme -->
    <link rel="stylesheet" href="{{ asset('assets/css/temple-theme.css') }}?v={{ file_exists(public_path('assets/css/temple-theme.css')) ? filemtime(public_path('assets/css/temple-theme.css')) : '1.0' }}">

    <style>
        .public-navbar {
            background-color: #ffffff;
            border-bottom: 2px solid rgba(180, 83, 9, 0.15);
            transition: all 0.2s ease;
        }
        .public-nav-link {
            color: #334155;
            font-weight: 500;
            padding: 0.5rem 0.9rem;
            border-radius: 0.5rem;
            transition: all 0.15s ease-in-out;
            text-decoration: none;
        }
        .public-nav-link:hover {
            color: var(--temple-maroon);
            background-color: #fef3c7;
        }
        .public-nav-link.active {
            color: var(--temple-maroon);
            background-color: rgba(117, 20, 28, 0.08);
            font-weight: 700;
        }
        .public-footer {
            background: #1e1b18;
            color: #e2e8f0;
            border-top: 3px solid var(--temple-gold);
        }
        .public-footer a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .public-footer a:hover {
            color: #f59e0b;
        }
        .public-event-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #ffffff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .public-event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
            border-color: #cbd5e1;
        }
        .public-event-banner {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-color: #f1f5f9;
        }
        .public-event-banner-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, rgba(117, 20, 28, 0.9), rgba(180, 83, 9, 0.85));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            text-align: center;
            padding: 1.5rem;
        }
        .public-hero {
            background: linear-gradient(135deg, #560e15 0%, #75141c 50%, #b45309 100%);
            color: #ffffff;
            border-radius: 1rem;
            position: relative;
            overflow: hidden;
        }
    </style>

    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Header / Navigation -->
    <header class="sticky-top">
        <nav class="navbar navbar-expand-lg public-navbar shadow-sm py-2">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark text-decoration-none" href="{{ route('home') }}">
                    <span class="temple-emblem shadow-sm" style="width: 38px; height: 38px; font-size: 1.25rem;">ॐ</span>
                    <div class="d-flex flex-column">
                        <span class="fs-5 lh-1 text-dark">{{ __('common.app_name') }}</span>
                        <small class="text-muted fw-normal" style="font-size: 0.72rem;">{{ __('common.app_subtitle') }}</small>
                    </div>
                </a>

                <!-- Mobile Toggler -->
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#publicPortalNav" aria-controls="publicPortalNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation Items -->
                <div class="collapse navbar-collapse" id="publicPortalNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-1 my-2 my-lg-0">
                        <li class="nav-item">
                            <a class="public-nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                                <i class="bi bi-house-door me-1"></i> {{ __('portal.public.home') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="public-nav-link {{ request()->routeIs('public.events.*') ? 'active' : '' }}" href="{{ route('public.events.index') }}">
                                <i class="bi bi-calendar3 me-1"></i> {{ __('portal.public.events') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="public-nav-link {{ request()->routeIs('public.previous-events.*') ? 'active' : '' }}" href="{{ route('public.previous-events.index') }}">
                                <i class="bi bi-journal-text me-1"></i> {{ __('portal.public.previous_events_reports') }}
                            </a>
                        </li>
                        @auth
                            <li class="nav-item">
                                <a class="public-nav-link {{ request()->routeIs('public.payments.*') ? 'active' : '' }}" href="{{ route('public.payments.history') }}">
                                    <i class="bi bi-wallet2 me-1"></i> {{ __('portal.public.my_contributions') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="public-nav-link {{ request()->routeIs('public.complaints.*') ? 'active' : '' }}" href="{{ route('public.complaints.index') }}">
                                    <i class="bi bi-chat-left-dots me-1"></i> {{ __('portal.public.my_complaints') }}
                                </a>
                            </li>
                        @endauth

                        <!-- Language Switcher -->
                        <li class="nav-item ms-lg-2 my-2 my-lg-0">
                            <div class="d-inline-flex align-items-center bg-light rounded-pill p-1 border">
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
                        </li>

                        <!-- Authentication link for Devotee / Staff / Admin -->
                        <li class="nav-item ms-lg-2">
                            @auth
                                <a href="{{ Auth::user()->hasRole('devotee') && !Auth::user()->hasAnyRole(['super_admin', 'temple_admin', 'treasurer']) ? route('devotee.dashboard') : route('dashboard') }}" class="btn btn-sm temple-btn-maroon">
                                    <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->hasRole('devotee') && !Auth::user()->hasAnyRole(['super_admin', 'temple_admin', 'treasurer']) ? __('devotee.my_dashboard') : __('nav.dashboard') }}
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> {{ __('portal.public.admin_login') }}
                                </a>
                            @endauth
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow-1 py-4">
        <div class="container">
            @include('partials.alerts')
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="public-footer mt-auto py-5">
        <div class="container">
            <div class="row g-4 mb-4">
                <!-- Column 1: Organization Identity -->
                <div class="col-lg-5 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="temple-emblem" style="width: 36px; height: 36px; font-size: 1.2rem; background: var(--temple-gold); color: #ffffff;">ॐ</span>
                        <h5 class="fw-bold mb-0 text-white">{{ __('common.app_name') }}</h5>
                    </div>
                    <p class="text-light text-opacity-75 small mb-3">
                        {{ __('common.app_subtitle') }}. {{ __('portal.public.footer_intro') }}
                    </p>
                    <div class="small text-warning">
                        <i class="bi bi-flower1 me-1"></i> {{ __('common.temple_motto') }}
                    </div>
                </div>

                <!-- Column 2: Useful Navigation Links -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold text-white text-uppercase tracking-wider small mb-3">
                        {{ __('portal.public.quick_links') }}
                    </h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                        <li>
                            <a href="{{ route('home') }}">
                                <i class="bi bi-chevron-right text-warning small me-1"></i> {{ __('portal.public.home') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('public.events.index') }}">
                                <i class="bi bi-chevron-right text-warning small me-1"></i> {{ __('portal.public.events') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('public.previous-events.index') }}">
                                <i class="bi bi-chevron-right text-warning small me-1"></i> {{ __('portal.public.previous_events_reports') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('login') }}">
                                <i class="bi bi-chevron-right text-warning small me-1"></i> {{ __('portal.public.admin_login') }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Column 3: Trust & Transparency Note -->
                <div class="col-lg-4 col-md-12">
                    <h6 class="fw-bold text-white text-uppercase tracking-wider small mb-3">
                        {{ __('portal.public.transparency_badge') }}
                    </h6>
                    <p class="text-light text-opacity-75 small mb-3">
                        {{ __('portal.public.transparency_text') }}
                    </p>
                    <div>
                        <span class="badge bg-dark bg-opacity-75 text-warning border border-warning border-opacity-50 small p-2">
                            <i class="bi bi-shield-check me-1"></i> {{ __('portal.public.portal_notice') }}
                        </span>
                    </div>
                </div>
            </div>

            <hr class="border-secondary border-opacity-50 my-4">

            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 text-light text-opacity-75 small">
                <div>
                    &copy; {{ date('Y') }} <strong>{{ __('common.app_name') }}</strong>. {{ __('common.copyright') }}
                </div>
                <div>
                    <span class="badge bg-secondary bg-opacity-25 text-light border border-secondary border-opacity-25">
                        {{ __('portal.public.bilingual_badge') }}
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/toastr/toastr.min.js') }}"></script>
    <!-- Select2 JS -->
    <script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>
    <!-- DataTables JS -->
    <script src="{{ asset('assets/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/responsive.bootstrap5.min.js') }}"></script>

    <script>
        window.TempleConfig = {
            locale: "{{ app()->getLocale() }}",
            datatablesLanguage: @json(trans('datatables')),
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
            }
        };
    </script>
    <script src="{{ asset('assets/js/temple-ui.js') }}"></script>

    @stack('scripts')
</body>
</html>
