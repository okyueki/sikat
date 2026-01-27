@php
    /** @var string|null $active */
    $active = $active ?? '';
@endphp

<div class="appBottomMenu">
    <a href="{{ url('/mobile') }}" class="item {{ $active === 'today' ? 'active' : '' }}">
        <div class="col">
            <ion-icon name="file-tray-full-outline"></ion-icon>
            <strong>Today</strong>
        </div>
    </a>
    <a href="{{ url('/mobile/calendar') }}" class="item {{ $active === 'calendar' ? 'active' : '' }}">
        <div class="col">
            <ion-icon name="calendar-outline"></ion-icon>
            <strong>Calendar</strong>
        </div>
    </a>
    <a href="{{ url('/mobile/presence') }}" class="item {{ $active === 'presence' ? 'active' : '' }}">
        <div class="col">
            <div class="action-button large">
                <ion-icon name="camera"></ion-icon>
            </div>
        </div>
    </a>
    <a href="{{ url('/mobile/docs') }}" class="item {{ $active === 'docs' ? 'active' : '' }}">
        <div class="col">
            <ion-icon name="document-text-outline"></ion-icon>
            <strong>Docs</strong>
        </div>
    </a>
    <a href="{{ url('/mobile/profile') }}" class="item {{ $active === 'profile' ? 'active' : '' }}">
        <div class="col">
            <ion-icon name="people-outline"></ion-icon>
            <strong>Profile</strong>
        </div>
    </a>
</div>
