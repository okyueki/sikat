{{-- Header halaman: judul, breadcrumb, waktu lokal; toolbar opsional via @section('pageToolbar') --}}
{{-- Breadcrumb multi-level: @section('breadcrumbs') … @include komponen <x-sikat.breadcrumbs> --}}
<div class="sikat-page-header d-md-flex d-block align-items-start align-items-md-center justify-content-between gap-3 mb-4 pb-3">
    <div class="flex-grow-1 min-w-0">
        <h5 class="page-title fs-21 mb-1 text-truncate">@yield('pageTitle', 'Beranda')</h5>
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Beranda</a>
                </li>
                @hasSection('breadcrumbs')
                    @yield('breadcrumbs')
                @else
                    <li class="breadcrumb-item active text-truncate" aria-current="page">
                        @hasSection('pageHeader')
                            @yield('pageHeader')
                        @else
                            @yield('pageTitle', 'Beranda')
                        @endif
                    </li>
                @endif
            </ol>
        </nav>
    </div>
    <div class="d-flex align-items-center gap-2 flex-shrink-0 flex-wrap justify-content-md-end">
        <time class="text-muted small d-none d-sm-inline sikat-header-clock" datetime="{{ now()->timezone(config('app.timezone'))->toIso8601String() }}">
            {{ now()->timezone(config('app.timezone'))->format('d/m/Y · H:i') }}
        </time>
        @hasSection('pageToolbar')
            <div class="d-flex align-items-center gap-2 flex-wrap sikat-page-toolbar">
                @yield('pageToolbar')
            </div>
        @endif
    </div>
</div>
