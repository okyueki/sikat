<header class="app-header">

            <!-- Start::main-header-container -->
            <div class="main-header-container container-fluid">

                <!-- Start::header-content-left -->
                <div class="header-content-left">

                    <!-- Start::header-element -->
                    <div class="header-element">
                        <div class="horizontal-logo">
                            <a href="{{ route('dashboard') }}" class="header-logo">
                                <img src="./backend/assets/images/brand-logos/desktop-logo.png" alt="logo" class="desktop-logo">
                                <img src="./backend/assets/images/brand-logos/toggle-logo.png" alt="logo" class="toggle-logo">
                                <img src="./backend/assets/images/brand-logos/desktop-white.png" alt="logo" class="desktop-white">
                                <img src="./backend/assets/images/brand-logos/toggle-white.png" alt="logo" class="toggle-white">
                            </a>
                        </div>
                    </div>
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <div class="header-element">
                        <!-- Start::header-link -->
                        <a aria-label="Hide Sidebar" class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle" data-bs-toggle="sidebar" href="javascript:void(0);">
                            <i class="header-icon fe fe-align-left"></i>
                        </a>
                        <!-- Modern Menu Modal Button -->
                        <a href="javascript:void(0);" class="header-link ms-2" id="openMenuModal" data-bs-toggle="modal" data-bs-target="#menuModal">
                            <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </a>
                        <div class="main-header-center d-none d-lg-block">
                            <input class="form-control" placeholder="Search for anything..." type="search"> <button class="btn"><i class="fa fa-search d-none d-md-block"></i></button>
                        </div>
                        <!-- End::header-link -->
                    </div>
                    <!-- End::header-element -->

                </div>
                <!-- End::header-content-left -->

                <!-- Start::header-content-right -->
                <div class="header-content-right">

                    <div class="header-element Search-element d-block d-lg-none">
                        <!-- Start::header-link|dropdown-toggle -->
                        <a href="javascript:void(0);" class="header-link dropdown-toggle" data-bs-auto-close="outside" data-bs-toggle="dropdown">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"  class="header-link-icon"><path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/></svg>
                        </a>
                        <!-- End::header-link|dropdown-toggle -->
                        <ul class="main-header-dropdown dropdown-menu dropdown-menu-end Search-element-dropdown" data-popper-placement="none">
                            <li>
                                <div class="input-group w-100 p-2"> 
                                    <input type="text" class="form-control" placeholder="Search....">
                                    <div class="btn btn-primary"> 
                                        <i class="fa fa-search" aria-hidden="true"></i> 
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Start::header-element -->
                    <div class="header-element header-theme-mode">
                        <!-- Start::header-link|layout-setting -->
                        <a href="javascript:void(0);" class="header-link layout-setting">
                            <span class="light-layout">
                                <!-- Start::header-link-icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" height="24" viewBox="0 -960 960 960" width="24"><path d="M480-120q-150 0-255-105T120-480q0-150 105-255t255-105q14 0 27.5 1t26.5 3q-41 29-65.5 75.5T444-660q0 90 63 153t153 63q55 0 101-24.5t75-65.5q2 13 3 26.5t1 27.5q0 150-105 255T480-120Zm0-80q88 0 158-48.5T740-375q-20 5-40 8t-40 3q-123 0-209.5-86.5T364-660q0-20 3-40t8-40q-78 32-126.5 102T200-480q0 116 82 198t198 82Zm-10-270Z"/></svg>
                                <!-- End::header-link-icon -->
                            </span>
                            <span class="dark-layout">
                                <!-- Start::header-link-icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" fill="currentColor" height="24" viewBox="0 -960 960 960" width="24"><path d="M480-360q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Zm0 80q-83 0-141.5-58.5T280-480q0-83 58.5-141.5T480-680q83 0 141.5 58.5T680-480q0 83-58.5 141.5T480-280ZM200-440H40v-80h160v80Zm720 0H760v-80h160v80ZM440-760v-160h80v160h-80Zm0 720v-160h80v160h-80ZM256-650l-101-97 57-59 96 100-52 56Zm492 496-97-101 53-55 101 97-57 59Zm-98-550 97-101 59 57-100 96-56-52ZM154-212l101-97 55 53-97 101-59-57Zm326-268Z"/></svg>
                                <!-- End::header-link-icon -->
                            </span>
                        </a>
                        <!-- End::header-link|layout-setting -->
                    </div>
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <div class="header-element messages-dropdown">
                        <!-- Start::header-link|dropdown-toggle -->
                        <a href="javascript:void(0);" class="header-link dropdown-toggle" data-bs-auto-close="outside" data-bs-toggle="dropdown">
                            <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" height="24px" viewBox="0 0 24 24" width="24px" fill="currentColor"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6zm-2 0l-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z"/></svg>
                            <span class="pulse-danger"></span>
                            <span class="badge rounded-pill bg-danger" id="notif-count">0</span>

                        </a>
                        <!-- End::header-link|dropdown-toggle -->
                        <!-- Start::main-header-dropdown -->
                        <div class="main-header-dropdown dropdown-menu dropdown-menu-end main-header-message" data-popper-placement="none">
                            <div class="menu-header-content bg-primary text-fixed-white">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0 fs-15 fw-semibold text-fixed-white">Messages</h6>
                                    <!--<span class="badge rounded-pill bg-warning pt-1 text-fixed-black">Mark All Read</span>-->
                                </div>
                                <p class="dropdown-title-text subtext mb-0 text-fixed-white op-6 pb-0 fs-12 ">
                                    Pesan yang belum dibaca (<span id="notif-count-text">0</span>)
                                </p>
                            </div>
                            <div><hr class="dropdown-divider"></div>
                            <ul class="list-unstyled mb-0" id="header-cart-items-scroll">
                                <li class="dropdown-item px-3">Loading...</li>
                            </ul>
                            <div class="text-center dropdown-footer">
                                <a href="{{ route('surat_masuk.index') }}" class="text-primary fs-13">VIEW ALL</a>
                            </div>
                        </div>
                        <!-- End::main-header-dropdown -->
                    </div>
                    <!-- End::header-element -->
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <div class="header-element header-fullscreen">
                        <!-- Start::header-link -->
                        <a onclick="openFullscreen();" href="javascript:void(0);" class="header-link">
                            <svg xmlns="http://www.w3.org/2000/svg" class="full-screen-open full-screen-icon header-link-icon" height="24px" viewBox="0 0 24 24" width="24px" fill="currentColor"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="full-screen-close full-screen-icon header-link-icon d-none" fill="currentColor" height="24" viewBox="0 -960 960 960" width="24"><path d="M320-200v-120H200v-80h200v200h-80Zm240 0v-200h200v80H640v120h-80ZM200-560v-80h120v-120h80v200H200Zm360 0v-200h80v120h120v80H560Z"/></svg>
                        </a>
                        <!-- End::header-link -->
                    </div>
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <div class="header-element header-sidebar">
                        <!-- Start::header-link-->
                        <a href="javascript:void(0);" class="header-link" data-bs-toggle="offcanvas" data-bs-target="#header-sidebar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                        </a>
                        <!-- End::header-link-->
                    </div>
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <div class="header-element headerProfile-dropdown">
                        <!-- Start::header-link|dropdown-toggle -->
                        <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <img src="./backend/assets/images/faces/6.jpg" alt="img" width="37" height="37" class="rounded-circle">
                        </a>
                        <!-- End::header-link|dropdown-toggle -->
                        <ul class="main-header-dropdown dropdown-menu pt-0 header-profile-dropdown dropdown-menu-end main-profile-menu" aria-labelledby="mainHeaderProfile">
                            <li>
                                <div class="main-header-profile bg-primary menu-header-content text-fixed-white">
                                    <div class="my-auto">
                                        <h6 class="mb-0 lh-1 text-fixed-white">@if(Auth::check())
    {{ Auth::user()->name }}
@endif</h6><span class="fs-11 op-7 lh-1">Premium Member</span>
                                    </div>
                                </div>
                            </li>
                            <li><a class="dropdown-item d-flex" href="{{ route('profil.show') }}"><i class="bx bx-user-circle fs-18 me-2 op-7"></i>Profile</a></li>

                            <li><a class="dropdown-item d-flex" href="{{ route('kepegawaian.rekap_presensi.index') }}"><i class="bx bx-cog fs-18 me-2 op-7"></i>Rekap Presensi </a></li>
                            <li><a class="dropdown-item d-flex border-block-end" href="mail.html"><i class="bx bxs-inbox fs-18 me-2 op-7"></i>Inbox</a></li>
                            <li><a class="dropdown-item d-flex" href="chat.html"><i class="bx bx-envelope fs-18 me-2 op-7"></i>Messages</a></li>
                            <li><a class="dropdown-item d-flex border-block-end" href="{{ route('ubahpassword.index') }}"><i class="bx bx-slider-alt fs-18 me-2 op-7"></i>Ubah Password</a></li>
                            <li><a class="dropdown-item d-flex" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="bx bx-log-out fs-18 me-2 op-7"></i>Sign Out</a>
                            <form id="logout-form" action="{{ route('logout') }}" method="GET" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        </ul>
                    </div>  
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <div class="header-element">
                        <!-- Start::header-link|switcher-icon -->
                        <a href="javascript:void(0);" class="header-link switcher-icon" data-bs-toggle="offcanvas" data-bs-target="#switcher-canvas">
                            <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" width="24" height="24" viewBox="0 0 24 24"><path d="M12 16c2.206 0 4-1.794 4-4s-1.794-4-4-4-4 1.794-4 4 1.794 4 4 4zm0-6c1.084 0 2 .916 2 2s-.916 2-2 2-2-.916-2-2 .916-2 2-2z"></path><path d="m2.845 16.136 1 1.73c.531.917 1.809 1.261 2.73.73l.529-.306A8.1 8.1 0 0 0 9 19.402V20c0 1.103.897 2 2 2h2c1.103 0 2-.897 2-2v-.598a8.132 8.132 0 0 0 1.896-1.111l.529.306c.923.53 2.198.188 2.731-.731l.999-1.729a2.001 2.001 0 0 0-.731-2.732l-.505-.292a7.718 7.718 0 0 0 0-2.224l.505-.292a2.002 2.002 0 0 0 .731-2.732l-.999-1.729c-.531-.92-1.808-1.265-2.731-.732l-.529.306A8.1 8.1 0 0 0 15 4.598V4c0-1.103-.897-2-2-2h-2c-1.103 0-2 .897-2 2v.598a8.132 8.132 0 0 0-1.896 1.111l-.529-.306c-.924-.531-2.2-.187-2.731.732l-.999 1.729a2.001 2.001 0 0 0 .731 2.732l.505.292a7.683 7.683 0 0 0 0 2.223l-.505.292a2.003 2.003 0 0 0-.731 2.733zm3.326-2.758A5.703 5.703 0 0 1 6 12c0-.462.058-.926.17-1.378a.999.999 0 0 0-.47-1.108l-1.123-.65.998-1.729 1.145.662a.997.997 0 0 0 1.188-.142 6.071 6.071 0 0 1 2.384-1.399A1 1 0 0 0 11 5.3V4h2v1.3a1 1 0 0 0 .708.956 6.083 6.083 0 0 1 2.384 1.399.999.999 0 0 0 1.188.142l1.144-.661 1 1.729-1.124.649a1 1 0 0 0-.47 1.108c.112.452.17.916.17 1.378 0 .461-.058.925-.171 1.378a1 1 0 0 0 .471 1.108l1.123.649-.998 1.729-1.145-.661a.996.996 0 0 0-1.188.142 6.071 6.071 0 0 1-2.384 1.399A1 1 0 0 0 13 18.7l.002 1.3H11v-1.3a1 1 0 0 0-.708-.956 6.083 6.083 0 0 1-2.384-1.399.992.992 0 0 0-1.188-.141l-1.144.662-1-1.729 1.124-.651a1 1 0 0 0 .471-1.108z"></path></svg>
                        </a>
                        <!-- End::header-link|switcher-icon -->
                    </div>
                    <!-- End::header-element -->

                </div>
                <!-- End::header-content-right -->

            </div>
            <!-- End::main-header-container -->

        </header>