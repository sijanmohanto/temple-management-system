<header class="app-navbar">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" id="sidebarToggleBtn" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>

        <div class="d-none d-md-flex align-items-center text-muted small">
            <i class="bi bi-geo-alt-fill text-danger me-1"></i>
            <span>{{ __('common.app_subtitle') }}</span>
        </div>
    </div>

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
            <!-- User Dropdown Menu -->
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="temple-emblem" style="width: 30px; height: 30px; font-size: 0.9rem;">
                        {{ mb_substr(Auth::user()->name, 0, 1) }}
                    </span>
                    <span class="d-none d-sm-inline fw-semibold text-truncate" style="max-width: 150px;">
                        {{ Auth::user()->name }}
                    </span>
                    <span class="badge badge-role-{{ Auth::user()->roles->first()?->slug ?? 'devotee' }} rounded-pill ms-1 d-none d-md-inline" style="font-size: 0.72rem;">
                        {{ Auth::user()->primary_role_title }}
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold">{{ Auth::user()->name }}</div>
                        <div class="text-muted small">{{ Auth::user()->email }}</div>
                        <div class="mt-1">
                            <span class="badge badge-role-{{ Auth::user()->roles->first()?->slug ?? 'devotee' }}">
                                {{ Auth::user()->primary_role_title }}
                            </span>
                        </div>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('profile.edit') }}">
                            <i class="bi bi-person-gear me-2 text-primary"></i> {{ __('nav.my_profile') }}
                        </a>
                    </li>
                    @if(Auth::user()->hasRole('super_admin'))
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('admin.audit-logs') }}">
                                <i class="bi bi-shield-check me-2 text-danger"></i> {{ __('nav.audit_logs') }}
                            </a>
                        </li>
                    @endif
                    @if(Auth::user()->hasAnyRole(['super_admin', 'treasurer']))
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('finance.overview') }}">
                                <i class="bi bi-cash-stack me-2 text-success"></i> {{ __('nav.finance') }}
                            </a>
                        </li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item py-2 text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i> {{ __('nav.logout') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        @else
            <a href="{{ route('login') }}" class="btn btn-sm temple-btn-maroon">
                <i class="bi bi-box-arrow-in-right me-1"></i> {{ __('nav.login') }}
            </a>
        @endauth
    </div>
</header>
