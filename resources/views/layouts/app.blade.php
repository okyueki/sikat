{{--
  Layout ringkas untuk halaman autentikasi tamu (login/register/lupa password).
  Halaman admin memakai layouts.pages-layouts.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') — {{ config('app.name', 'SIKAT') }}</title>
    <meta name="description" content="{{ config('app.name', 'SIKAT') }} — Akses aman untuk pegawai.">
    <meta name="theme-color" content="#0d6efd">
    <link rel="icon" href="{{ asset('backend/assets/images/brand-logos/favicon.ico') }}" type="image/x-icon">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css">
    @stack('styles')
    @stack('head')
    <style>
        .sikat-auth-shell { min-height: 100vh; display: flex; flex-direction: column; }
        .sikat-auth-brand { font-weight: 600; letter-spacing: 0.02em; }
    </style>
</head>
<body class="auth-body-bg">
    <div class="sikat-auth-shell">
        <header class="border-bottom bg-white py-3">
            <div class="container">
                <a href="{{ url('/') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-2">
                    <img src="{{ asset('backend/assets/images/brand-logos/toggle-logo.png') }}" alt="" height="28" class="d-inline-block">
                    <span class="sikat-auth-brand">{{ config('app.name', 'SIKAT') }}</span>
                </a>
            </div>
        </header>
        <main class="flex-grow-1 d-flex align-items-center py-4">
            @yield('content')
        </main>
        <footer class="py-3 text-center text-muted small border-top bg-white">
            © {{ date('Y') }} {{ config('app.name', 'SIKAT') }}
        </footer>
    </div>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
