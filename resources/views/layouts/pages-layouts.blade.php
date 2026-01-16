<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">

<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title> {{ config('app.name', 'SIKAT') }} || Sistem Informasi Kepegawaian dan Arsip Surat</title>
    <meta name="Description" content="Bootstrap Responsive Admin Web Dashboard HTML5 Template">
    <meta name="Author" content="Spruko Technologies Private Limited">
    <meta name="keywords" content="admin dashboard template,admin panel html,bootstrap dashboard,admin dashboard,html template,template dashboard html,html css,bootstrap 5 admin template,bootstrap admin template,bootstrap 5 dashboard,admin panel html template,dashboard template bootstrap,admin dashboard html template,bootstrap admin panel,simple html template,admin dashboard bootstrap">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('backend/assets/images/brand-logos/favicon.ico'); }}" type="image/x-icon">
    <!-- Bootstrap Css -->
    <link id="style" href="{{ asset('backend/assets/libs/bootstrap/css/bootstrap.min.css'); }}" rel="stylesheet" >
    <!-- Style Css -->
    <link href="{{ asset('backend/assets/css/styles.min.css'); }}" rel="stylesheet" >
    <!-- Icons Css -->
    <link href="{{ asset('backend/assets/css/icons.css'); }}" rel="stylesheet" >
    <link href="{{ asset('backend/assets/libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Node Waves Css -->
    <link href="{{ asset('backend/assets/libs/node-waves/waves.min.css') }}" rel="stylesheet">
    <!-- Simplebar Css -->
    <link href="{{ asset('backend/assets/libs/simplebar/simplebar.min.css') }}" rel="stylesheet">
    <!-- Color Picker Css -->
    <link rel="stylesheet" href="{{ asset('backend/assets/libs/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/libs/@simonwep/pickr/themes/nano.min.css') }}"> 
    <!-- Full Calender Css -->
    <link href="{{ asset('vendor/fullcalendar/dist/index.global.js') }}" rel="stylesheet" />
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- DataTables CSS -->
    <link href="{{ asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />   
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{ asset('backend/assets/libs/sweetalert2/sweetalert2.min.css') }}">
    <!-- Sweetalerts JS -->
    <script src="{{ asset('backend/assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Apex Charts JS -->
    <script src="{{ asset('backend/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <!-- Choices JS -->
    <script src="{{ asset('assets/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>
    <!-- Main Theme Js -->
    <script src="{{ asset('backend/assets/js/main.js'); }}"></script>
    <!-- DataTables JS -->
    <script src="{{ asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Dropify -->
    <link rel="stylesheet" href="{{ asset('backend/assets/libs/dropify/dist/dropify.min.css') }}">
    <script src="{{ asset('backend/assets/libs/dropify/dist/dropify.min.js') }}"></script>
    <script src="{{ asset('backend/assets/libs/flatpickr/flatpickr.min.js'); }}"></script>
    <script src="{{ asset('backend/assets/libs/@simonwep/pickr/pickr.es5.min.js'); }}"></script>
    <script src="{{ asset('backend/assets/js/date&time_pickers.js'); }}"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
     
<style>
   .choices__inner {
        min-height: 44px; /* Sesuaikan dengan kebutuhan */
        max-height: 200px; /* Membatasi tinggi maksimal dropdown */
    }

    .choices__list--dropdown .choices__item {
        white-space: nowrap; /* Mencegah teks terpotong jika terlalu panjang */
    }
    .dz-progress {
        display: none !important;
    }
    .flatpickr-time input {
        padding: 15px;
    }
    table.dataTable {
        margin-top: 15px !important;
        margin-bottom: 15px !important;
        border-collapse: collapse !important;
    }

    .list-group-item {
            border: none;
            position: relative;
        }
        .sub-list {
            padding-left: 30px;
            border-left: 2px solid #007bff;
        }
    .table-responsive {
    overflow-x: auto !important; /* Tambahkan !important untuk memaksa */
    
}
#header-cart-items-scroll, #header-notification-scroll, #header-shortcut-scroll {
    max-height: 32rem !important;
    overflow-y: auto !important; /* Gunakan native scroll karena SimpleBar disabled untuk elemen yang di-update via AJAX */
    -webkit-overflow-scrolling: touch; /* Smooth scrolling di iOS */
}
    
</style>
</head>

<body>

@include('layouts.backend.swithcer')
    <!-- Loader -->
    <div id="loader" >
        <img src="{{ asset('backend/assets/images/media/loader.svg'); }}" alt="">
    </div>
    <!-- Loader -->

    <div class="page">
         <!-- app-header -->
@include('layouts.backend.header')
        <!-- /app-header -->
@include('layouts.backend.modal')
         <!-- Start::app-sidebar -->
        <aside class="app-sidebar sticky" id="sidebar">

            <!-- Start::main-sidebar-header -->
            <div class="main-sidebar-header">
                <a href="{{ route('dashboard') }}" class="header-logo">
                    <img src="{{ asset('backend/assets/images/brand-logos/desktop-logo.png'); }}" alt="logo" class="desktop-logo">
                    <img src="{{ asset('backend/assets/images/brand-logos/toggle-logo.png'); }}" alt="logo" class="toggle-logo">
                    <img src="{{ asset('backend/assets/images/brand-logos/desktop-white.png'); }}" alt="logo" class="desktop-white">
                    <img src="{{ asset('backend/assets/images/brand-logos/toggle-white.png'); }}" alt="logo" class="toggle-white">
                </a>
            </div>
            <!-- End::main-sidebar-header -->

            <!-- Start::main-sidebar -->
            <div class="main-sidebar" id="sidebar-scroll">

                <!-- Start::nav -->
@include('layouts.backend.navbar')
                <!-- End::nav -->

            </div>
            <!-- End::main-sidebar -->

        </aside>
        <!-- End::app-sidebar -->

        <!-- Start::app-content -->
         <div class="main-content app-content">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div class="my-auto">
        <h5 class="page-title fs-21 mb-1">@yield('pageTitle')</h5>
        <nav>
            <ol class="breadcrumb mb-0">
                <!-- Breadcrumb dynamic section, customize as needed -->
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    @yield('pageHeader')
                </li>
            </ol>
        </nav>
    </div>
    
    <div class="d-flex my-xl-auto right-content align-items-center">
        <div class="pe-1 mb-xl-0">
            <button type="button" class="btn btn-info btn-icon me-2 btn-b">
                <i class="mdi mdi-filter-variant"></i>
            </button>
        </div>
        <div class="pe-1 mb-xl-0">
            <button type="button" class="btn btn-danger btn-icon me-2">
                <i class="mdi mdi-star"></i>
            </button>
        </div>
        <div class="pe-1 mb-xl-0">
            <button type="button" class="btn btn-warning btn-icon me-2">
                <i class="mdi mdi-refresh"></i>
            </button>
        </div>
        <div class="mb-xl-0">
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuDate" data-bs-toggle="dropdown" aria-expanded="false">
                    14 Aug 2019
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuDate">
                    <li><a class="dropdown-item" href="javascript:void(0);">2015</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);">2016</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);">2017</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);">2018</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

                <!-- End Page Header -->
                @yield('content')
            </div>
        </div>

        <footer>
        @include('layouts.backend.footer')
        </footer>
    </div>

    
    <!-- Scroll To Top -->
    <div class="scrollToTop">
        <span class="arrow"><i class="las la-angle-double-up"></i></span>
    </div>
    <div id="responsive-overlay"></div>
    
    <!-- Modern Menu Modal -->
    <div class="modal fade" id="menuModal" tabindex="-1" aria-labelledby="menuModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center w-100">
                        <div class="d-flex align-items-center flex-grow-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="me-2" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            <h5 class="modal-title mb-0 fw-semibold" id="menuModalLabel">Dashboard</h5>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fe fe-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-0 bg-light" id="menuSearch" placeholder="Pencarian modul...">
                            </div>
                        </div>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3" id="menuGrid">
                        <!-- Menu items will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="{{ asset('vendor/fullcalendar/dist/index.global.min.js') }}"></script>
@stack('scripts')
    <script src="{{ asset('backend/assets/libs/@popperjs/core/umd/popper.min.js'); }}"></script>
    <script src="{{ asset('backend/assets/libs/bootstrap/js/bootstrap.bundle.min.js'); }}"></script>
    <script src="{{ asset('backend/assets/js/defaultmenu.min.js'); }}"></script>
    <script src="{{ asset('backend/assets/libs/node-waves/waves.min.js'); }}"></script>
    <script src="{{ asset('backend/assets/js/sticky.js'); }}"></script>
    <script src="{{ asset('backend/assets/libs/simplebar/simplebar.min.js'); }}"></script>
    <script src="{{ asset('backend/assets/js/simplebar.js'); }}"></script>
    <script src="{{ asset('backend/assets/js/custom-switcher.min.js'); }}"></script>
    <script src="{{ asset('backend/assets/js/custom.js'); }}"></script>
    
    <!-- Modern Menu Modal Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuModal = document.getElementById('menuModal');
        const menuGrid = document.getElementById('menuGrid');
        const menuSearch = document.getElementById('menuSearch');
        
        // Menu items configuration - Sesuai dengan navbar.blade.php
        const menuItems = [
            {
                title: 'Dashboard',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
                url: '{{ route("dashboard") }}'
            },
            {
                title: 'Surat Masuk',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
                url: '{{ route("surat_masuk.index") }}'
            },
            {
                title: 'Surat Keluar',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
                url: '{{ route("surat_keluar.index") }}'
            },
            {
                title: 'Pengajuan Cuti / Libur',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                url: '{{ route("cuti.index") }}'
            },
            {
                title: 'Verifikasi Pengajuan Libur',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
                url: '{{ route("verifikasi_pengajuan_libur.index") }}'
            },
            {
                title: 'Pengajuan Ijin',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>',
                url: '{{ route("ijin.index") }}'
            },
            {
                title: 'Pengajuan Lembur',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
                url: '{{ route("pengajuan_lembur.index") }}'
            },
            {
                title: 'Kalender Event',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"></path></svg>',
                url: '{{ route("acara_index") }}'
            },
            {
                title: 'Absensi Agenda',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>',
                url: '{{ route("absensi_agenda.index") }}'
            },
            {
                title: 'Daftar Pegawai',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
                url: '{{ route("pegawai.index") }}'
            },
            {
                title: 'Absensi',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
                url: '{{ route("absensi.show") }}'
            },
            {
                title: 'Jadwal',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                url: '{{ route("jadwal.index") }}'
            },
            {
                title: 'Berkas Pegawai',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
                url: '{{ route("berkas_pegawai.index") }}'
            },
            {
                title: 'Penilaian Bulanan',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
                url: '{{ route("penilaian_individu.index") }}'
            },
            {
                title: 'Master Barang',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>',
                url: '{{ route("inventaris-barang.index") }}'
            },
            {
                title: 'Inventaris',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>',
                url: '{{ route("inventaris.index") }}'
            },
            {
                title: 'Helpdesk',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
                url: '{{ route("helpdesk.dashboard") }}'
            },
            {
                title: 'Permintaan Perbaikan',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
                url: '{{ route("tickets.index") }}'
            },
            {
                title: 'Rekap Libur',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
                url: '{{ route("pengajuan_libur.rekap-libur") }}'
            },
            {
                title: 'Rekap Lembur',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
                url: '{{ route("rekap.lembur") }}'
            },
            {
                title: 'Rekap Presensi',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
                url: '{{ route("kepegawaian.rekap_presensi.index") }}'
            },
            {
                title: 'Pemakaian Kamar',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>',
                url: '{{ route("pemakaiankamar.index") }}'
            },
            {
                title: 'Pasien Rawat Inap',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>',
                url: '{{ route("pasienrawatinap.index") }}'
            },
            {
                title: 'Pasien Rawat Jalan',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>',
                url: '{{ route("pasienrawatjalan.index") }}'
            },
            {
                title: 'Ubah Password',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>',
                url: '{{ route("ubahpassword.index") }}'
            },
            {
                title: 'Kamar Inap',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>',
                url: '{{ route("kamar_inap.index") }}'
            },
            {
                title: 'Data Discharge Note',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
                url: '{{ route("datadischargenote.index") }}'
            },
            {
                title: 'Users',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
                url: '{{ route("users.index") }}'
            },
            {
                title: 'Struktur Organisasi',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
                url: '{{ route("struktur_organisasi.index") }}'
            },
            {
                title: 'Template Surat',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
                url: '{{ route("template_surat.index") }}'
            },
            {
                title: 'Jenis Berkas',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
                url: '{{ route("jenis_berkas.index") }}'
            }
        ];
        
        // Render menu items
        function renderMenuItems(items) {
            menuGrid.innerHTML = '';
            items.forEach(item => {
                const col = document.createElement('div');
                col.className = 'col-xl-2 col-lg-3 col-md-4 col-sm-6';
                col.innerHTML = `
                    <a href="${item.url}" class="menu-card-link text-decoration-none">
                        <div class="card menu-card h-100 border transition-all">
                            <div class="card-body text-center p-3">
                                <div class="menu-icon mb-2">
                                    ${item.icon}
                                </div>
                                <h6 class="mb-0 fw-semibold" style="font-size: 0.85rem;">${item.title}</h6>
                            </div>
                        </div>
                    </a>
                `;
                menuGrid.appendChild(col);
            });
        }
        
        // Initial render
        renderMenuItems(menuItems);
        
        // Search functionality
        menuSearch.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const filteredItems = menuItems.filter(item => 
                item.title.toLowerCase().includes(searchTerm)
            );
            renderMenuItems(filteredItems);
        });
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && menuModal.classList.contains('show')) {
                const bsModal = bootstrap.Modal.getInstance(menuModal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
        });
        
        // Focus search input when modal opens
        menuModal.addEventListener('shown.bs.modal', function() {
            menuSearch.focus();
        });
        
        // Clear search when modal closes
        menuModal.addEventListener('hidden.bs.modal', function() {
            menuSearch.value = '';
            renderMenuItems(menuItems);
        });
    });
    </script>
    
    <style>
    .menu-card {
        transition: all 0.2s ease;
        cursor: pointer;
        border-radius: 8px !important;
        border: 1px solid #e0e0e0 !important;
        background: #ffffff;
    }
    
    .menu-card:hover {
        transform: translateY(-2px);
        border-color: #6c757d !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
    }
    
    .menu-card-link:hover {
        text-decoration: none !important;
    }
    
    .menu-icon {
        font-size: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #495057;
    }
    
    .menu-icon svg {
        width: 40px;
        height: 40px;
        stroke: #495057;
    }
    
    .menu-card:hover .menu-icon,
    .menu-card:hover .menu-icon svg {
        color: #212529;
        stroke: #212529;
    }
    
    .menu-card h6 {
        color: #495057;
    }
    
    .menu-card:hover h6 {
        color: #212529;
    }
    
    .transition-all {
        transition: all 0.2s ease;
    }
    
    #menuModal .modal-content {
        border-radius: 12px;
        border: 1px solid #e0e0e0;
    }
    
    #menuModal .modal-header {
        background: #f8f9fa;
        border-bottom: 1px solid #e0e0e0;
        border-radius: 12px 12px 0 0;
    }
    
    #menuModal .modal-header .modal-title,
    #menuModal .modal-header svg {
        color: #212529 !important;
    }
    
    #menuModal .modal-header .input-group-text {
        background: #ffffff !important;
        border: 1px solid #e0e0e0 !important;
        color: #495057 !important;
    }
    
    #menuModal .modal-header .form-control {
        background: #ffffff !important;
        border: 1px solid #e0e0e0 !important;
        color: #212529 !important;
    }
    
    #menuModal .modal-header .form-control:focus {
        border-color: #6c757d !important;
        box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.15) !important;
    }
    
    #menuModal .modal-header .form-control::placeholder {
        color: #adb5bd !important;
    }
    
    #menuModal .modal-body {
        background: #ffffff;
    }
    
    @media (min-width: 1200px) {
        #menuModal .modal-xl {
            max-width: 1400px;
        }
    }
    </style>
    
    <script>
      document.addEventListener("DOMContentLoaded", function() {
    const activeItem = document.querySelector(".side-menu__item.active");
    //console.log("Active item:", activeItem);
    if (activeItem) {
        let parent = activeItem.closest(".has-sub");
        //console.log("Parent:", parent);
        if (parent) {
            parent.classList.add("open");
            let childMenu = parent.querySelector(".slide-menu");
            //console.log("Child menu:", childMenu);
            if (childMenu) {
                childMenu.style.display = "block";
            }
        }
    }
});
    </script>
    <script>
function loadNotifications() {
    $.ajax({
        url: "{{ route('notifications') }}",
        type: "GET",
        success: function(response) {
            $('#header-cart-items-scroll').html(response.html);
            $('#notif-count').text("You have " + response.count + " unread messages");
        },
        error: function() {
            $('#header-cart-items-scroll').html('<li class="dropdown-item text-center">Gagal memuat notifikasi</li>');
        }
    });
}

loadNotifications(); // Load pertama kali
setInterval(loadNotifications, 30000); // Refresh tiap 30 detik

    </script>
    
    <script>
function loadNotifications() {
    $.ajax({
        url: "{{ route('notifications') }}",
        type: "GET",
        success: function(response) {
            var cartElement = document.getElementById('header-cart-items-scroll');
            
            // Destroy SimpleBar instance jika ada sebelum mengubah konten
            if (cartElement && typeof SimpleBar !== 'undefined') {
                try {
                    // Cek apakah SimpleBar sudah diinisialisasi
                    if (cartElement.hasAttribute('data-simplebar') || cartElement.classList.contains('simplebar-wrapper')) {
                        // Coba destroy dengan berbagai cara tergantung versi SimpleBar
                        if (SimpleBar.instances && SimpleBar.instances.get) {
                            var instance = SimpleBar.instances.get(cartElement);
                            if (instance && typeof instance.unMount === 'function') {
                                instance.unMount();
                            }
                        }
                        // Hapus attribute dan class yang ditambahkan SimpleBar
                        cartElement.removeAttribute('data-simplebar');
                        cartElement.classList.remove('simplebar-wrapper');
                    }
                } catch (e) {
                    // Ignore error jika instance tidak ada atau sudah di-destroy
                }
            }
            
            $('#header-cart-items-scroll').html(response.html);

            // DISABLED: Re-inisialisasi SimpleBar setelah AJAX update
            // SimpleBar tidak cocok untuk elemen yang kontennya di-update via AJAX
            // karena elemen bisa berubah dan menyebabkan error WeakMap
            // Gunakan CSS overflow-y: auto sebagai gantinya (sudah ada di CSS)
            
            // Tidak perlu re-initialize SimpleBar karena konten di-update via AJAX
            // SimpleBar akan error jika elemen diubah setelah initialize

            // Update count
            $('#notif-count').text(response.count);
            $('#notif-count-text').text(response.count);
            // Sembunyikan jika kosong
            if (response.count == 0) {
                $('#notif-count').hide();
            } else {
                $('#notif-count').show();
            }
        },
        error: function() {
            $('#header-cart-items-scroll').html('<li class="dropdown-item text-danger text-center">Gagal memuat notifikasi</li>');
        }
    });
}

loadNotifications();
setInterval(loadNotifications, 30000);
</script>

<!--<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>-->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addBtn = document.getElementById('add-btn');
    const noRawatHidden = document.getElementById('no-rawat-hidden');
    if (!addBtn || !noRawatHidden) {
        return; // Skip jika elemen tidak ada (halaman ini tidak memerlukan script ini)
    }
    const noRawat = noRawatHidden.value;
  
    addBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const tindakanInput = document.getElementById('input-tindakan');
        const tanggalInput = document.getElementById('input-tanggal');
        if (!tindakanInput || !tanggalInput) return;
        
        const tindakan = tindakanInput.value;
        const tanggal = tanggalInput.value;
        
        if (!tindakan || !tanggal) return alert('Isi tindakan dan tanggal');

        fetch('/tindakan/'+noRawat, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({ tindakan, tanggal })
        })
        .then(res => res.json())
        .then(data => location.reload());
    });

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const tindakanEl = document.querySelector(`.tindakan-text[data-id="${id}"]`);
            const tanggalEl = document.querySelector(`.tanggal-input[data-id="${id}"]`);
            if (!tindakanEl || !tanggalEl) return;
            
            const tindakan = tindakanEl.value;
            const tanggal = tanggalEl.value;

            fetch(`/tindakan/${id}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ tindakan, tanggal })
            })
            .then(res => res.json())
            .then(data => alert('Berhasil disimpan'));
        });
    });

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            if (!confirm('Yakin hapus?')) return;
            fetch(`/tindakan/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                }
            })
            .then(res => res.json())
            .then(data => document.getElementById(`row-${id}`).remove());
        });
    });
});
</script>
   <script>
document.addEventListener('DOMContentLoaded', function () {
    const noRawatHidden = document.getElementById('no-rawat-hidden');
    if (!noRawatHidden) {
        return; // Skip jika elemen tidak ada (halaman ini tidak memerlukan script ini)
    }
    const noRawat = noRawatHidden.value;

    const addObatBtn = document.getElementById('add-obat');
    if (!addObatBtn) return; // Skip jika elemen tidak ada
    
    addObatBtn.addEventListener('click', function (e) {
        e.preventDefault();

        const nama_obatEl = document.getElementById('new-nama_obat');
        const dosisEl = document.getElementById('new-dosis');
        const cara_pakaiEl = document.getElementById('new-cara_pakai');
        const frekuensiEl = document.getElementById('new-frekuensi');
        const fungsi_obatEl = document.getElementById('new-fungsi_obat');
        const dosis_terakhirEl = document.getElementById('new-dosis_terakhir');
        const keteranganEl = document.getElementById('new-keterangan');
        
        if (!nama_obatEl || !dosisEl || !cara_pakaiEl || !frekuensiEl || !fungsi_obatEl || !dosis_terakhirEl || !keteranganEl) {
            return;
        }

        const nama_obat = nama_obatEl.value;
        const dosis = dosisEl.value;
        const cara_pakai = cara_pakaiEl.value;
        const frekuensi = frekuensiEl.value;
        const fungsi_obat = fungsi_obatEl.value;
        const dosis_terakhir = dosis_terakhirEl.value;
        const keterangan = keteranganEl.value;

        fetch(`/obat/${noRawat}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                nama_obat,
                dosis,
                cara_pakai,
                frekuensi,
                fungsi_obat,
                dosis_terakhir,
                keterangan
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.message) {
                location.reload();
            } else {
                alert('Gagal menambah obat');
            }
        });
    });

    document.querySelectorAll('.ubah-obat').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (!row) return;

            const namaObatEl = row.querySelector('.nama_obat');
            const dosisEl = row.querySelector('.dosis');
            const caraPakaiEl = row.querySelector('.cara_pakai');
            const frekuensiEl = row.querySelector('.frekuensi');
            const fungsiObatEl = row.querySelector('.fungsi_obat');
            const dosisTerakhirEl = row.querySelector('.dosis_terakhir');
            const keteranganEl = row.querySelector('.keterangan');
            
            if (!namaObatEl || !dosisEl || !caraPakaiEl || !frekuensiEl || !fungsiObatEl || !dosisTerakhirEl || !keteranganEl) {
                return;
            }

            const data = {
                nama_obat: namaObatEl.value,
                dosis: dosisEl.value,
                cara_pakai: caraPakaiEl.value,
                frekuensi: frekuensiEl.value,
                fungsi_obat: fungsiObatEl.value,
                dosis_terakhir: dosisTerakhirEl.value,
                keterangan: keteranganEl.value,
            };

            fetch(`/obat/${id}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => alert('Berhasil disimpan'))
            .catch(() => alert('Gagal menyimpan'));
        });
    });

    document.querySelectorAll('.delete-obat').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            if (!confirm('Yakin ingin hapus?')) return;

            fetch(`/obat/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                }
            })
            .then(res => res.json())
            .then(data => document.querySelector(`tr[data-id="${id}"]`).remove())
            .catch(() => alert('Gagal menghapus'));
        });
    });
});
</script>



</body>

</html>