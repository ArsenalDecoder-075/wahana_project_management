<ul>
    {{-- Dashboard --}}
    <li class="nav-item @if (request()->routeIs('manager.dashboard')) active @endif">
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

    <li class="nav-item @if (request()->routeIs('manager.manage.hierarchy')) active @endif">
        <a href="{{ route('manager.manage.hierarchy') }}">
            <span class="icon"><i class="fa-solid fa-solidLarge fa-user-group">‌</i></span>
            <span class="text">Hierarki</span>
        </a>
    </li>

    <li class="nav-item @if (request()->routeIs('manager.manage.project')) active @endif">
        <a href="{{ route('manager.manage.project') }}">
            <span class="icon"><i class="fa-solid fa-solidLarge fa-folder-closed">‌</i></span>
            <span class="text">Manage Project</span>
        </a>
    </li>
</ul>
