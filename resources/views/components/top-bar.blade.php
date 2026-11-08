<!-- Topbar Start -->
<div class="navbar-custom">

    <!-- Left: sidebar toggle + collapsed logo -->
    <ul class="list-unstyled topnav-menu topnav-menu-left m-0 d-flex align-items-center" style="gap:4px;">
        <li>
            <button class="button-menu-mobile waves-effect">
                <i class="mdi mdi-menu"></i>
            </button>
        </li>
        <li class="topbar-logo-sm align-items-center" style="padding-left:4px;">
            <a href="{{ route('dashboard') }}" style="display:flex; align-items:center;">
                <img src="{{ asset('app-assets/logo.png') }}" alt="{{ config('app.name') }}" height="32">
            </a>
        </li>
    </ul>

    <!-- Right: language + user -->
    <ul class="list-unstyled topnav-menu float-right mb-0">

        <li class="d-flex align-items-center">
            @include('components.language-switcher')
        </li>

        <!-- User dropdown -->
        <li class="dropdown notification-list">
            @php
                $nameParts = explode(' ', trim(Auth::user()->name));
                $initials  = strtoupper(substr($nameParts[0], 0, 1));
                if (count($nameParts) > 1) $initials .= strtoupper(substr(end($nameParts), 0, 1));
            @endphp

            <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button">
                <!-- Initials avatar -->
                <span style="
                    display:inline-flex; align-items:center; justify-content:center;
                    width:32px; height:32px; border-radius:50%;
                    background:var(--brand-blue); color:#fff;
                    font-size:0.75rem; font-weight:700;
                    flex-shrink:0; letter-spacing:0.02em;
                ">{{ $initials }}</span>

                <span class="d-none d-sm-flex flex-column ms-2" style="line-height:1.3; text-align:left;">
                    <span style="font-size:0.82rem; font-weight:600; color:var(--heading-dark);">{{ Auth::user()->name }}</span>
                    <span style="font-size:0.7rem; font-weight:400; color:var(--muted-mid);">
                        {{ Auth::user()->getRoleNames()->first() ?? __('No role') }}
                    </span>
                </span>

                <i class="mdi mdi-chevron-down ms-1 d-none d-sm-block" style="color:var(--muted-soft); font-size:1rem;"></i>
            </a>

            <div class="dropdown-menu dropdown-menu-right" style="min-width:200px; padding:6px 0;">
                <!-- User info header -->
                <div style="padding:10px 16px 10px; border-bottom:1px solid var(--divider-faint);">
                    <div style="font-size:0.82rem; font-weight:600; color:var(--heading-dark);">{{ Auth::user()->name }}</div>
                    <div style="font-size:0.72rem; color:var(--muted-mid); margin-top:1px;">
                        {{ Auth::user()->getRoleNames()->first() }}
                    </div>
                </div>

                @if (Auth::user()->auth_provider === 'local')
                <a href="{{ route('local-password.edit') }}" class="dropdown-item" style="padding:9px 16px;">
                    <i class="mdi mdi-lock-outline me-2" style="font-size:0.95rem; color:var(--muted-mid);"></i>
                    {{ __('Change Password') }}
                </a>
                @else
                <a href="{{ route('profile.edit') }}" class="dropdown-item" style="padding:9px 16px;">
                    <i class="mdi mdi-account-outline me-2" style="font-size:0.95rem; color:var(--muted-mid);"></i>
                    {{ __('Profile') }}
                </a>
                @endif

                <div class="dropdown-divider"></div>

                <div style="padding:2px 0;">
                    <form method="POST" action="{{ Auth::user()->auth_provider === 'local' ? route('local-logout') : route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item w-100 text-start border-0 bg-transparent"
                                style="padding:9px 16px; font-size:0.82rem; color:#ef4444; font-weight:500; cursor:pointer;">
                            <i class="mdi mdi-logout-variant me-2" style="font-size:0.95rem;"></i>
                            {{ __('Sign out') }}
                        </button>
                    </form>
                </div>
            </div>
        </li>

    </ul>
</div>
<!-- Topbar End -->
