<nav class="navbar navbar-expand-md shadow-sm sticky-top bg-body-tertiary">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}"
            @auth data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ 'V. ' . config('app.version') }}" @endauth>
            {{ config('app.name', 'Laravel') }}
            @if (config('app.env', 'production') != 'production')
                <span class="badge text-bg-info align-text-top">dev</span>
            @endif
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation menu') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav me-auto">
                @auth
                    <!-- Monitors -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('monitors.*') ? 'active' : '' }}"
                            href="{{ route('monitors.index') }}" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="{{ __('List Monitors') }}"
                            @if (request()->routeIs('monitors.*')) aria-current="page" @endif>
                            {{ __('Monitors') }}
                        </a>
                    </li>
                    <!-- Events actions -->
                    <li class="nav-item dropdown">
                        <a id="dropDownEvents"
                            class="nav-link dropdown-toggle {{ request()->routeIs('events.*', 'templates.*', 'eventsImports.*') ? 'active' : '' }}"
                            href="#" role="button"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ __('Events') }} <span class="caret"></span>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="dropDownEvents">
                            <a class="dropdown-item" href="{{ route('events.index') }}" data-bs-toggle="tooltip"
                                data-bs-placement="left" data-bs-title="{{ __('List Events') }}">
                                <i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Events') }}
                            </a>
                            <a class="dropdown-item" href="{{ route('templates.index') }}" data-bs-toggle="tooltip"
                                data-bs-placement="left" data-bs-title="{{ __('List Templates') }}">
                                <i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Templates') }}
                            </a>
                            @if (Auth::user()->is_realm_admin)
                                <hr>
                                <a class="dropdown-item" href="{{ route('eventsImports.index') }}"
                                    data-bs-toggle="tooltip" data-bs-placement="left"
                                    data-bs-title="{{ __('List Events Imports') }}">
                                    <i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Events Imports') }}
                                </a>
                            @endif
                        </div>
                    </li>
                    <!-- Menus -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('menus.*') ? 'active' : '' }}"
                            href="{{ route('menus.index') }}" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="{{ __('List Menus') }}"
                            @if (request()->routeIs('menus.*')) aria-current="page" @endif>
                            {{ __('Menus') }}
                        </a>
                    </li>
                    <!-- Slides -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('slides.*') ? 'active' : '' }}"
                            href="{{ route('slides.index') }}" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="{{ __('List Slides') }}"
                            @if (request()->routeIs('slides.*')) aria-current="page" @endif>
                            {{ __('Slides') }}
                        </a>
                    </li>
                    <!-- Canteens -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('canteens.*') ? 'active' : '' }}"
                            href="{{ route('canteens.index') }}" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="{{ __('List Canteens') }}"
                            @if (request()->routeIs('canteens.*')) aria-current="page" @endif>
                            {{ __('Canteens') }}
                        </a>
                    </li>
                    @if (Auth::user()->is_realm_admin)
                        <!-- Admin actions -->
                        <li class="nav-item dropdown">
                            <a id="dropDownAdmin"
                                class="nav-link dropdown-toggle {{ request()->routeIs('alerts.*', 'register', 'log-viewer.*', 'realms.*') ? 'active' : '' }}"
                                href="#" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ __('Admin') }} <span class="caret"></span>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="dropDownAdmin">
                                <a class="dropdown-item text-danger" href="{{ route('alerts.index') }}"><i
                                        class="fa-solid fa-fw fa-bell"></i>&nbsp;{{ __('Send Alert') }}</a>
                                <a class="dropdown-item text-warning" href="{{ route('register') }}"><i
                                        class="fa-solid fa-fw fa-user-plus"></i>&nbsp;{{ __('Register User') }}</a>
                                <hr>
                                @if (auth()->user()->is_admin)
                                    <a class="dropdown-item text-info-emphasis" href="{{ route('log-viewer.index') }}"><i
                                            class="fa-solid fa-fw fa-virus-covid-slash"></i>&nbsp;{{ __('View Logs') }}</a>
                                    <hr>
                                @endif
                                @foreach (\App\Models\Realm::all() as $realm)
                                        @if (auth()->user()->is_admin or auth()->user()->realm_id == $realm->id)
                                        <a class="dropdown-item" href="{{ route('realms.edit', $realm->id) }}"
                                            data-bs-toggle="tooltip" data-bs-placement="left"
                                            data-bs-title="{{ __('Edit Realm') }}">
                                            <i class="fa-solid fa-fw fa-pen-to-square"></i>{{__('Edit :realm_name',['realm_name'=>$realm->name]) }}
                                        </a>
                                        @endif
                                @endforeach
                            </div>
                        </li>
                    @endif
                @endauth
            </ul>

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ms-auto">
                <!-- Report an issue -->
                <li class="nav-item">
                    <a class="nav-link" href="{{ config('ads.report_issue_url') }}" target="_blank"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ __('Report an issue') }}">
                        <i class="fas fa-fw fa-bug"></i>
                    </a>
                </li>
                <!-- Language toggler -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fas fa-fw fa-language" data-bs-toggle="tooltip" data-bs-placement="left"
                            data-bs-title="{{ __('Switch Language') }}"></i>
                        @php
                            $locale = App::currentLocale() ?? 'en';
                        @endphp
                        @switch($locale)
                            @case('en')
                                EN
                            @break

                            @case('de')
                                DE
                            @break

                            @case('it')
                                IT
                            @break

                            @default
                                EN
                        @endswitch
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/lang/en">English</a></li>
                        <li><a class="dropdown-item" href="/lang/de">Deutsch</a></li>
                        <li><a class="dropdown-item" href="/lang/it">Italiano</a></li>
                    </ul>
                </li>

                <!-- Dark mode toggler -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fas fa-fw fa-lightbulb" id="bd-theme" data-bs-toggle="tooltip" data-bs-placement="left"
                            data-bs-title="{{ __('Toggle Dark Mode') }}"></i>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="bd-theme-text" data-bs-popper="static">
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center"
                                data-bs-theme-value="light" aria-pressed="false">
                                <i class="bi me-2 opacity-50 theme-icon  fas fa-fw fa-sun"></i>
                                {{ __('Light') }}
                                <i class="bi ms-auto d-none fas fa-fw fa-check"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center"
                                data-bs-theme-value="dark" aria-pressed="false">
                                <i class="bi me-2 opacity-50 theme-icon fas fa-fw fa-moon"></i>
                                {{ __('Dark') }}
                                <i class="bi ms-auto d-none fas fa-fw fa-check"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center active"
                                data-bs-theme-value="auto" aria-pressed="false">
                                <i class="bi me-2 opacity-50 theme-icon fas fa-fw fa-circle-half-stroke"></i>
                                {{ __('Auto') }}
                                <i class="bi ms-auto d-none fas fa-fw fa-check"></i>
                            </button>
                        </li>
                    </ul>
                </li>

                <!-- Authentication Links -->
                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                    </li>
                    <!--
                                                                        @if (Route::has('register'))
    <li class="nav-item">
                                                                                <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                                                            </li>
    @endif
                                                                        -->
                @else
                    <li class="nav-item dropdown">
                        <a id="dropDownUser" class="nav-link dropdown-toggle" href="#" role="button"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ Auth::user()->name }}
                            @if (Auth::user()->is_admin)
                                <span class="badge text-bg-danger align-text-top" data-bs-placement="bottom"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="{{ __('Server Admin') }} ({{ Auth::user()->realm->name }})"><i
                                        class="fas fa-fw fa-crown"></i></span>
                            @elseif (Auth::user()->is_realm_admin)
                                <span class="badge text-bg-warning align-text-top" data-bs-placement="bottom"
                                    data-bs-toggle="tooltip" data-bs-title="{{ __('Admin') }} ({{ Auth::user()->realm->name }})"><i
                                        class="fas fa-fw fa-crown"></i></span>
                            @endif
                            <span class="caret"></span>
                        </a>

                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropDownUser">
                            <a class="dropdown-item" href="{{ route('users.edit', Auth::user()->id) }}"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                data-bs-title="{{ __('Edit Account') }}">
                                <i class="fa-solid fa-fw fa-user-pen"></i>&nbsp;{{ __('Account') }}
                            </a>
                            @if (Auth::user()->realms->count() > 1)
                                <hr>
                                <h6 class="dropdown-header">{{ __('Switch Realm') }}</h6>
                                @foreach (Auth::user()->realms as $realm)
                                    <form action="{{ route('realm.switch', $realm) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="dropdown-item {{ $realm->id === Auth::user()->realm_id ? 'active' : '' }}">
                                            <i class="fa-solid fa-fw fa-building"></i>&nbsp;{{ $realm->name }}
                                        </button>
                                    </form>
                                @endforeach
                            @endif
                            <hr>
                            <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                onclick="event.preventDefault();
                                             document.getElementById('logout-form').submit();">
                                <i class="fa-solid fa-fw fa-person-through-window"></i>&nbsp;{{ __('Logout') }}
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
