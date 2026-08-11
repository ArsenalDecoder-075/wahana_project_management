<ul>
    {{-- Dashboard --}}
    <li class="nav-item @if (request()->routeIs('admin.home')) active @endif">
        <a href="{{ route('admin.home') }}">
            <span class="icon">
                <svg width="22" height="22" viewBox="0 0 22 22">
                    <path
                        d="M17.4167 4.58333V6.41667H13.75V4.58333H17.4167Z M8.25 4.58333V10.0833H4.58333V4.58333H8.25Z M17.4167 11.9167V17.4167H13.75V11.9167H17.4167Z M8.25 15.5833V17.4167H4.58333V15.5833H8.25Z M19.25 2.75H11.9167V8.25H19.25V2.75Z M10.0833 2.75H2.75V11.9167H10.0833V2.75Z M19.25 10.0833H11.9167V19.25H19.25V10.0833Z M10.0833 13.75H2.75V19.25H10.0833V13.75Z" />
                </svg>
            </span>
            <span class="text">{{ __('Dashboard') }}</span>
        </a>
    </li>

    {{-- Master Data --}}
    <li class="nav-item nav-item-has-children">
        <a class="collapsed" href="#0" data-bs-toggle="collapse" data-bs-target="#master_data">
            <span class="icon"><i class="fa fa-database"></i></span>
            <span class="text">Master Data</span>
        </a>
        <ul id="master_data" class="dropdown-nav collapse @if (request()->routeIs('admin.branch', 'admin.user', 'admin.manage.categories')) show @endif">
            <li class="nav-item">
                <a href="{{ route('admin.branch') }}" class="@if (request()->routeIs('admin.branch')) active @endif">
                    <span class="text">{{ __('Cabang') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.user') }}" class="@if (request()->routeIs('admin.user')) active @endif">
                    <span class="text">{{ __('Pengguna') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.manage.categories') }}" class="@if (request()->routeIs('admin.manage.categories')) active @endif">
                    <span class="text">{{ __('Kategori') }}</span>
                </a>
            </li>
        </ul>
    </li>

    <li class="nav-item @if (request()->routeIs('admin.manage.hierarchy')) active @endif">
        <a href="{{ route('admin.manage.hierarchy') }}">
            <span class="icon"><i class="fa-solid fa-solidLarge fa-user-group">‌</i></span>
            <span class="text">Hierarki</span>
        </a>
    </li>

    <li class="nav-item @if (request()->routeIs('admin.manage.project')) active @endif">
        <a href="{{ route('admin.manage.project') }}">
            <span class="icon"><i class="fa-solid fa-solidLarge fa-folder-closed">‌</i></span>
            <span class="text">Manage Project</span>
        </a>
    </li>

    <li class="nav-item @if (request()->routeIs('admin.calendar')) active @endif">
        <a href="{{ route('admin.calendar') }}">
            <span class="icon"><i class="fa-solid fa-calendar-days"></i></span>
            <span class="text">Calender</span>
        </a>
    </li>
</ul>
