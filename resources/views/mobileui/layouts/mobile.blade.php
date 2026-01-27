<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, viewport-fit=cover" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#000000">

    <title>@yield('title', 'SIKAT Mobile')</title>
    <meta name="description" content="@yield('meta_description', 'SIKAT Mobile')" />

    <link rel="icon" type="image/png" href="{{ asset('assetsmobileui/img/favicon.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assetsmobileui/img/icon/192x192.png') }}">
    <link rel="manifest" href="{{ asset('manifest-mobile.json') }}">

    <link rel="stylesheet" href="{{ asset('assetsmobileui/css/style.css') }}">
    <style>
        /* Mobilekit template ini #appCapsule padding-top-nya dikomentari.
           Jadi kalau ada header fixed (.appHeader), konten jadi "mepet".
           Kita aktifkan padding-top khusus halaman yang punya header. */
        body.has-header #appCapsule {
            padding-top: 56px;
        }

        /* Fix overlap issue: hapus position absolute dari menu dan presence section */
        #menu-section {
            position: relative !important;
            top: auto !important;
            width: auto !important;
            z-index: auto !important;
        }

        #presence-section {
            position: relative !important;
            top: auto !important;
            width: auto !important;
            background-color: transparent !important;
            border-radius: 0 !important;
        }

        .todaypresence {
            margin-top: 0 !important;
        }

        /* Pastikan spacing konsisten antar section */
        .section {
            margin-bottom: 16px;
        }

        /* User section perlu padding bottom lebih besar */
        #user-section {
            padding-bottom: 40px;
        }
    </style>
    @stack('styles')
</head>

<body class="@yield('body_class') @hasSection('has_header') has-header @endif" style="@yield('body_style')">
    <!-- loader -->
    <div id="loader">
        <div class="spinner-border text-primary" role="status"></div>
    </div>
    <!-- * loader -->

    @yield('content')

    <!-- ///////////// Js Files ////////////////////  -->
    <script src="{{ asset('assetsmobileui/js/lib/jquery-3.4.1.min.js') }}"></script>
    <script src="{{ asset('assetsmobileui/js/lib/popper.min.js') }}"></script>
    <script src="{{ asset('assetsmobileui/js/lib/bootstrap.min.js') }}"></script>

    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

    <!-- Owl Carousel -->
    <script src="{{ asset('assetsmobileui/js/plugins/owl-carousel/owl.carousel.min.js') }}"></script>
    <!-- jQuery Circle Progress -->
    <script src="{{ asset('assetsmobileui/js/plugins/jquery-circle-progress/circle-progress.min.js') }}"></script>

    <!-- Base Js File -->
    <script src="{{ asset('assetsmobileui/js/base.js') }}"></script>

    @stack('scripts')
</body>

</html>
