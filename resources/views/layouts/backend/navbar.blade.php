                <nav class="main-menu-container nav nav-pills flex-column sub-open">
                    <div class="slide-left" id="slide-left">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
                    </div>
                    <ul class="main-menu">
                        <li class="slide__category"><span class="category-name">Menu Utama</span></li>
                        <li class="slide">
                            <a href="{{ route('dashboard') }}" class="side-menu__item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house-door-fill" viewBox="0 0 16 16">
                                  <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5"/>
                                </svg>
                                <span class="side-menu__label">Dashboard</span>
                                <span class="badge bg-success ms-auto menu-badge">1</span>
                            </a>
                        </li>
                
                        <li class="slide has-sub">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('surat_masuk.*') || request()->routeIs('surat_keluar.*') || request()->routeIs('cuti.*') || request()->routeIs('ijin.*') || request()->routeIs('pengajuan_lembur.*') || request()->routeIs('verifikasi_pengajuan_libur.*') || request()->routeIs('verifikasi_pengajuan_lembur.*') || request()->routeIs('sifat_surat.*') || request()->routeIs('klasifikasi_surat.*') ? 'active' : '' }}">
                                <svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope-at-fill" viewBox="0 0 16 16">
                                  <path d="M2 2A2 2 0 0 0 .05 3.555L8 8.414l7.95-4.859A2 2 0 0 0 14 2zm-2 9.8V4.698l5.803 3.546zm6.761-2.97-6.57 4.026A2 2 0 0 0 2 14h6.256A4.5 4.5 0 0 1 8 12.5a4.49 4.49 0 0 1 1.606-3.446l-.367-.225L8 9.586zM16 9.671V4.697l-5.803 3.546.338.208A4.5 4.5 0 0 1 12.5 8c1.414 0 2.675.652 3.5 1.671"/>
                                  <path d="M15.834 12.244c0 1.168-.577 2.025-1.587 2.025-.503 0-1.002-.228-1.12-.648h-.043c-.118.416-.543.643-1.015.643-.77 0-1.259-.542-1.259-1.434v-.529c0-.844.481-1.4 1.26-1.4.585 0 .87.333.953.63h.03v-.568h.905v2.19c0 .272.18.42.411.42.315 0 .639-.415.639-1.39v-.118c0-1.277-.95-2.326-2.484-2.326h-.04c-1.582 0-2.64 1.067-2.64 2.724v.157c0 1.867 1.237 2.654 2.57 2.654h.045c.507 0 .935-.07 1.18-.18v.731c-.219.1-.643.175-1.237.175h-.044C10.438 16 9 14.82 9 12.646v-.214C9 10.36 10.421 9 12.485 9h.035c2.12 0 3.314 1.43 3.314 3.034zm-4.04.21v.227c0 .586.227.8.581.8.31 0 .564-.17.564-.743v-.367c0-.516-.275-.708-.572-.708-.346 0-.573.245-.573.791"/>
                                </svg>
                                <span class="side-menu__label">Surat Menyurat</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">Surat Menyurat</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('surat_masuk.index') }}" class="side-menu__item {{ request()->routeIs('surat_masuk.*') ? 'active' : '' }}">Surat Masuk</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('surat_keluar.index') }}" class="side-menu__item {{ request()->routeIs('surat_keluar.*') ? 'active' : '' }}">Surat Keluar</a>
                                </li>
                
                                <li class="slide has-sub {{ request()->routeIs('cuti.*') || request()->routeIs('verifikasi_pengajuan_libur.*') ? 'open' : '' }}">
                                    <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('cuti.*') ? 'active' : '' }}">Surat Cuti / Libur
                                        <i class="fe fe-chevron-right side-menu__angle"></i></a>
                                    <ul class="slide-menu child2">
                                        <li class="slide">
                                            <a href="{{ route('cuti.index') }}" class="side-menu__item {{ request()->routeIs('cuti.*') ? 'active' : '' }}">Pengajuan Cuti / Libur</a>
                                        </li>
                                        <li class="slide">
                                            <a href="{{ route('verifikasi_pengajuan_libur.index') }}" class="side-menu__item {{ request()->routeIs('verifikasi_pengajuan_libur.*') ? 'active' : '' }}">Verifikasi Ijin</a>
                                        </li>
                                    </ul>
                                </li>
                                
                                <li class="slide has-sub {{ request()->routeIs('ijin.*') || request()->routeIs('verifikasi_pengajuan_libur.*') ? 'open' : '' }}">
                                    <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('ijin.*') ? 'active' : '' }}">Surat Ijin
                                        <i class="fe fe-chevron-right side-menu__angle"></i></a>
                                    <ul class="slide-menu child2">
                                        <li class="slide">
                                            <a href="{{ route('ijin.index') }}" class="side-menu__item {{ request()->routeIs('ijin.*') ? 'active' : '' }}">Pengajuan Ijin</a>
                                        </li>
                                        <li class="slide">
                                            <a href="{{ route('verifikasi_pengajuan_libur.index') }}" class="side-menu__item {{ request()->routeIs('verifikasi_pengajuan_libur.*') ? 'active' : '' }}">Verifikasi Ijin</a>
                                        </li>
                                    </ul>
                                </li>
                
                                <li class="slide has-sub {{ request()->routeIs('pengajuan_lembur.*') || request()->routeIs('verifikasi_pengajuan_lembur.*') ? 'open' : '' }}">
                                    <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('pengajuan_lembur.*') ? 'active' : '' }}">Surat Lembur
                                        <i class="fe fe-chevron-right side-menu__angle"></i></a>
                                    <ul class="slide-menu child2">
                                        <li class="slide">
                                            <a href="{{ route('pengajuan_lembur.index') }}" class="side-menu__item {{ request()->routeIs('pengajuan_lembur.*') ? 'active' : '' }}">Pengajuan Lembur</a>
                                        </li>
                                        <li class="slide">
                                            <a href="{{ route('verifikasi_pengajuan_lembur.index') }}" class="side-menu__item {{ request()->routeIs('verifikasi_pengajuan_lembur.*') ? 'active' : '' }}">Verifikasi Lembur</a>
                                        </li>
                                    </ul>
                                </li>
                
                                <li class="slide has-sub {{ request()->routeIs('klasifikasi_surat.*') || request()->routeIs('sifat_surat.*') ? 'open' : '' }}">
                                    <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('sifat_surat.*') || request()->routeIs('klasifikasi_surat.*') ? 'active' : '' }}">Pengaturan Surat
                                        <i class="fe fe-chevron-right side-menu__angle"></i></a>
                                    <ul class="slide-menu child2">
                                        <li class="slide">
                                            <a href="{{ route('sifat_surat.index') }}" class="side-menu__item {{ request()->routeIs('sifat_surat.*') ? 'active' : '' }}">Sifat Surat</a>
                                        </li>
                                        <li class="slide">
                                            <a href="{{ route('klasifikasi_surat.index') }}" class="side-menu__item {{ request()->routeIs('klasifikasi_surat.*') ? 'active' : '' }}">Klasifikasi Surat</a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <!-- End::slide -->
                        
                        <!-- Start::slide -->
                        <li class="slide has-sub">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('acara_*') || request()->routeIs('backend_acara') || request()->routeIs('absensi_agenda.*') || request()->routeIs('rekap-absensi') || request()->routeIs('absensi_event.*') ? 'active' : '' }}">
                                <svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar2-event-fill" viewBox="0 0 16 16">
                                  <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5m9.954 3H2.545c-.3 0-.545.224-.545.5v1c0 .276.244.5.545.5h10.91c.3 0 .545-.224.545-.5v-1c0-.276-.244-.5-.546-.5M11.5 7a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5z"/>
                                </svg>
                                <span class="side-menu__label">Event</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">Agenda</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('acara_index') }}" class="side-menu__item {{ request()->routeIs('acara_*') ? 'active' : '' }}">Kalender Event</a>
                                </li>
                                @if (in_array(Auth::user()->level, ['Kabag','Kasie']))
                                <li class="slide">
                                    <a href="{{ route('backend_acara') }}" class="side-menu__item {{ request()->routeIs('backend_acara') ? 'active' : '' }}">Agenda</a>
                                </li>
                                @endif
                                <li class="slide">
                                    <a href="{{ route('absensi_agenda.index') }}" class="side-menu__item {{ request()->routeIs('absensi_agenda.*') ? 'active' : '' }}">Absensi Agenda</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('rekap-absensi') }}" class="side-menu__item {{ request()->routeIs('rekap-absensi') ? 'active' : '' }}">Rekap Absensi Agenda</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('absensi_event.create') }}" class="side-menu__item {{ request()->routeIs('absensi_event.*') ? 'active' : '' }}">
                                        Absensi Event
                                    </a>
                                </li>
                            </ul>
                            
                        </li>
                        <!-- End::slide -->

                        <!-- Start::slide -->
                        <li class="slide has-sub">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('pegawai.*') || request()->routeIs('presensi.*') || request()->routeIs('absensi.*') || request()->routeIs('jadwal.*') || request()->routeIs('berkas_pegawai.*') || request()->routeIs('budayakerja.*') || request()->routeIs('jadwalbudayakerja.*') || request()->routeIs('penilaian_individu.*') || request()->routeIs('kepegawaian.rekap_presensi.*') ? 'active' : '' }}">
                                <svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                                  <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
                                </svg>
                                <span class="side-menu__label">Kepegawaian</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">Kepegawaian</a>
                                </li>
                                <li class="slide has-sub">
                                    <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('presensi.*') || request()->routeIs('absensi.*') || request()->routeIs('jadwal.*') ? 'active' : '' }}">Presensi
                                        <i class="fe fe-chevron-right side-menu__angle"></i>
                                    </a>
                                    <ul class="slide-menu child2">
                                        @if (in_array(Auth::user()->level, ['Kasie','Kabag']))
                                        <li class="slide">
                                            <a href="{{ route('presensi.index') }}" class="side-menu__item {{ request()->routeIs('presensi.*') ? 'active' : '' }}">Temporary Presensi</a>
                                        </li>
                                        @endif
                                        <li class="slide">
                                            <a href="{{ route('absensi.show') }}" class="side-menu__item {{ request()->routeIs('absensi.*') ? 'active' : '' }}">Absensi</a>
                                        </li>
                                        <li class="slide">
                                            <a href="{{ route('jadwal.index') }}" class="side-menu__item {{ request()->routeIs('jadwal.*') ? 'active' : '' }}">Jadwal</a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('pegawai.index') }}" class="side-menu__item {{ request()->routeIs('pegawai.index') ? 'active' : '' }}"> Daftar Pegawai</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('berkas_pegawai.index') }}" class="side-menu__item {{ request()->routeIs('berkas_pegawai.*') ? 'active' : '' }}">Berkas Pegawai</a>
                                </li>
                                <li class="slide has-sub">
                                    <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('budayakerja.*') || request()->routeIs('jadwalbudayakerja.*') ? 'active' : '' }}">Budaya Kerja
                                        <i class="fe fe-chevron-right side-menu__angle"></i>
                                    </a>
                                    <ul class="slide-menu child2">
                                        <li class="slide">
                                            <a href="{{ route('budayakerja.index') }}" class="side-menu__item {{ request()->routeIs('budayakerja.*') ? 'active' : '' }}">Penilaian Budaya Kerja</a>
                                        </li>
                                        <li class="slide">
                                            <a href="{{ route('jadwalbudayakerja.index') }}" class="side-menu__item {{ request()->routeIs('jadwalbudayakerja.*') ? 'active' : '' }}">Jadwal Budaya Kerja</a>
                                        </li>
                                        <li class="slide">
                                            <a href="{{ route('jadwalbudayakerja.kalender') }}" class="side-menu__item {{ request()->routeIs('jadwalbudayakerja.kalender') ? 'active' : '' }}">Kalender Budaya Kerja</a>
                                        </li>
                                    </ul>
                                </li>

                                <li class="slide">
                                    <a href="{{ route('pegawai.birthday') }}" class="side-menu__item {{ request()->routeIs('pegawai.birthday') ? 'active' : '' }}">Kalender Milad</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('penilaian_individu.index') }}" class="side-menu__item {{ request()->routeIs('penilaian_individu.*') ? 'active' : '' }}">Penilaian Bulanan</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('kepegawaian.rekap_presensi.index') }}" class="side-menu__item {{ request()->routeIs('kepegawaian.rekap_presensi.*') ? 'active' : '' }}">Rekap Presensi</a>
                                </li>
                            </ul>
                        </li>
           
                        <!-- End::slide -->
                        
                        <!-- Start::slide -->
                        <li class="slide has-sub">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('inventaris-barang.*') || request()->routeIs('inventaris.*') ? 'active' : '' }}">
                                <span class="side-menu__label">
                                <svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pc-display" viewBox="0 0 16 16">
                                  <path d="M8 1a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1zm1 13.5a.5.5 0 1 0 1 0 .5.5 0 0 0-1 0m2 0a.5.5 0 1 0 1 0 .5.5 0 0 0-1 0M9.5 1a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1zM9 3.5a.5.5 0 0 0 .5.5h5a.5.5 0 0 0 0-1h-5a.5.5 0 0 0-.5.5M1.5 2A1.5 1.5 0 0 0 0 3.5v7A1.5 1.5 0 0 0 1.5 12H6v2h-.5a.5.5 0 0 0 0 1H7v-4H1.5a.5.5 0 0 1-.5-.5v-7a.5.5 0 0 1 .5-.5H7V2z"/>
                                </svg>
                        		Inventaris</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">Inventaris</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('inventaris-barang.index') }}" class="side-menu__item {{ request()->routeIs('inventaris-barang.*') ? 'active' : '' }}">Master Barang</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('inventaris.index') }}" class="side-menu__item {{ request()->routeIs('inventaris.index') || request()->routeIs('inventaris.create') || request()->routeIs('inventaris.edit') || request()->routeIs('inventaris.show') ? 'active' : '' }}">Inventaris</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('inventaris.visualisasi') }}" class="side-menu__item {{ request()->routeIs('inventaris.visualisasi*') ? 'active' : '' }}">
                                        <i class="fa fa-chart-bar me-2"></i>Visualisasi Data
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- End::slide -->
                        
                        <!-- Start::slide -->
                        <li class="slide has-sub">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('tickets.*') || request()->routeIs('helpdesk.*') ? 'active' : '' }}">
                                <svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-patch-question-fill" viewBox="0 0 16 16">
                                  <path d="M5.933.87a2.89 2.89 0 0 1 4.134 0l.622.638.89-.011a2.89 2.89 0 0 1 2.924 2.924l-.01.89.636.622a2.89 2.89 0 0 1 0 4.134l-.637.622.011.89a2.89 2.89 0 0 1-2.924 2.924l-.89-.01-.622.636a2.89 2.89 0 0 1-4.134 0l-.622-.637-.89.011a2.89 2.89 0 0 1-2.924-2.924l.01-.89-.636-.622a2.89 2.89 0 0 1 0-4.134l.637-.622-.011-.89a2.89 2.89 0 0 1 2.924-2.924l.89.01zM7.002 11a1 1 0 1 0 2 0 1 1 0 0 0-2 0m1.602-2.027c.04-.534.198-.815.846-1.26.674-.475 1.05-1.09 1.05-1.986 0-1.325-.92-2.227-2.262-2.227-1.02 0-1.792.492-2.1 1.29A1.7 1.7 0 0 0 6 5.48c0 .393.203.64.545.64.272 0 .455-.147.564-.51.158-.592.525-.915 1.074-.915.61 0 1.03.446 1.03 1.084 0 .563-.208.885-.822 1.325-.619.433-.926.914-.926 1.64v.111c0 .428.208.745.585.745.336 0 .504-.24.554-.627"/>
                                </svg>
                                <span class="side-menu__label">Pusat Bantuan</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">Pusat Bantuan</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('tickets.index') }}" class="side-menu__item {{ request()->routeIs('tickets.*') ? 'active' : '' }}">Permintaan Perbaikan</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('helpdesk.dashboard') }}" class="side-menu__item {{ request()->routeIs('helpdesk.*') ? 'active' : '' }}">Helpdesk</a>
                                </li>
                            </ul>
                        </li>
                        
                        <li class="slide has-sub">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('pengajuan_libur.rekap-libur') || request()->routeIs('rekap.lembur') ? 'active' : '' }}">
                                <svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-signpost-2-fill" viewBox="0 0 16 16">
                                  <path d="M7.293.707A1 1 0 0 0 7 1.414V2H2a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h5v1H2.5a1 1 0 0 0-.8.4L.725 8.7a.5.5 0 0 0 0 .6l.975 1.3a1 1 0 0 0 .8.4H7v5h2v-5h5a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1H9V6h4.5a1 1 0 0 0 .8-.4l.975-1.3a.5.5 0 0 0 0-.6L14.3 2.4a1 1 0 0 0-.8-.4H9v-.586A1 1 0 0 0 7.293.707"/>
                                </svg>
                                <span class="side-menu__label">Laporan</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">Laporan</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('pengajuan_libur.rekap-libur') }}" class="side-menu__item {{ request()->routeIs('pengajuan_libur.rekap-libur') ? 'active' : '' }}">Rekap Libur</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('rekap.lembur') }}" class="side-menu__item {{ request()->routeIs('rekap.lembur') ? 'active' : '' }}">Rekap Lembur</a>
                                </li>
                            </ul>
                        </li>
                        <li class="slide has-sub">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('pemakaiankamar.*') || request()->routeIs('pasienrawatinap.*') || request()->routeIs('pasienrawatjalan.*') ? 'active' : '' }}">
                                <svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-emoji-grin" viewBox="0 0 16 16">
                                  <path d="M12.946 11.398A6.002 6.002 0 0 1 2.108 9.14c-.114-.595.426-1.068 1.028-.997C4.405 8.289 6.48 8.5 8 8.5s3.595-.21 4.864-.358c.602-.07 1.142.402 1.028.998a5.95 5.95 0 0 1-.946 2.258m-.078-2.25C11.588 9.295 9.539 9.5 8 9.5s-3.589-.205-4.868-.352c.11.468.286.91.517 1.317A37 37 0 0 0 8 10.75a37 37 0 0 0 4.351-.285c.231-.407.407-.85.517-1.317m-1.36 2.416c-1.02.1-2.255.186-3.508.186s-2.488-.086-3.507-.186A5 5 0 0 0 8 13a5 5 0 0 0 3.507-1.436ZM6.488 7c.114-.294.179-.636.179-1 0-1.105-.597-2-1.334-2C4.597 4 4 4.895 4 6c0 .364.065.706.178 1 .23-.598.662-1 1.155-1 .494 0 .925.402 1.155 1M12 6c0 .364-.065.706-.178 1-.23-.598-.662-1-1.155-1-.494 0-.925.402-1.155 1a2.8 2.8 0 0 1-.179-1c0-1.105.597-2 1.334-2C11.403 4 12 4.895 12 6"/>
                                  <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m0-1A7 7 0 1 1 8 1a7 7 0 0 1 0 14"/>
                                </svg>
                                <span class="side-menu__label">Manajemen</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">Manajemen</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('pemakaiankamar.index') }}" class="side-menu__item {{ request()->routeIs('pemakaiankamar.*') ? 'active' : '' }}">Pemakaian Kamar</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('pasienrawatinap.index') }}" class="side-menu__item {{ request()->routeIs('pasienrawatinap.*') ? 'active' : '' }}">Pasien Rawat Inap</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('pasienrawatjalan.index') }}" class="side-menu__item {{ request()->routeIs('pasienrawatjalan.*') ? 'active' : '' }}">Pasien Rawat Jalan</a>
                                </li>
                            </ul>
                        </li>
                        <li class="slide">
                            <a href="{{ route('kamar_inap.index') }}" class="side-menu__item {{ request()->routeIs('kamar_inap.index') ? 'active' : '' }}">
                                <svg class="me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                    <path d="M21 20H23V22H1V20H3V3C3 2.44772 3.44772 2 4 2H20C20.5523 2 21 2.44772 21 3V20ZM11 8H9V10H11V12H13V10H15V8H13V6H11V8ZM14 20H16V14H8V20H10V16H14V20Z"></path>
                                </svg>
                                <span class="side-menu__label">Kamar Inap</span>
                            </a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('datadischargenote.index') }}" class="side-menu__item {{ request()->routeIs('datadischargenote.*') ? 'active' : '' }}">
                                <svg class="me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                    <path d="M21 20H23V22H1V20H3V3C3 2.44772 3.44772 2 4 2H20C20.5523 2 21 2.44772 21 3V20ZM11 8H9V10H11V12H13V10H15V8H13V6H11V8ZM14 20H16V14H8V20H10V16H14V20Z"></path>
                                </svg>
                                <span class="side-menu__label">Data Discharge Note</span>
                            </a>
                        </li>

                        
                        <!-- End::slide -->

                        <!-- Start::slide__category -->
                        <li class="slide__category"><span class="category-name">Pengaturan</span></li>
                        <!-- End::slide__category -->


                        <!-- Start::slide -->
                        <li class="slide has-sub">
                            <a href="javascript:void(0);" class="side-menu__item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M6.26 9L12 13.47 17.74 9 12 4.53z" opacity=".3"/><path d="M19.37 12.8l-7.38 5.74-7.37-5.73L3 14.07l9 7 9-7zM12 2L3 9l1.63 1.27L12 16l7.36-5.73L21 9l-9-7zm0 11.47L6.26 9 12 4.53 17.74 9 12 13.47z"/></svg>
                                <span class="side-menu__label">Pengaturan</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1 mega-menu">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">Pengaturan</a>
                                </li>
                                @if (in_array(Auth::user()->level, ['Kabag']))
                                <li class="slide">
                                    <a href="{{ route('sifat_surat.index') }}" class="side-menu__item {{ request()->routeIs('sifat_surat.*') ? 'active' : '' }}">Sifat Surat</a>
                                </li>
                                @endif
                                <li class="slide">
                                    <a href="{{ route('users.index') }}" class="side-menu__item {{ request()->routeIs('users.*') ? 'active' : '' }}">Users</a>
                                </li>
                                @if (in_array(Auth::user()->level, ['Kabag']))
                                <li class="slide">
                                    <a href="{{ route('klasifikasi_surat.index') }}" class="side-menu__item {{ request()->routeIs('klasifikasi_surat.*') ? 'active' : '' }}">Klasifikasi Surat</a>
                                </li>
                                @endif
                                <li class="slide">
                                    <a href="{{ route('struktur_organisasi.index') }}" class="side-menu__item {{ request()->routeIs('struktur_organisasi.*') ? 'active' : '' }}">Struktur Organisasi</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('template_surat.index') }}" class="side-menu__item {{ request()->routeIs('template_surat.*') ? 'active' : '' }}">Template Surat</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('jenis_berkas.index') }}" class="side-menu__item {{ request()->routeIs('jenis_berkas.*') ? 'active' : '' }}">Jenis Berkas</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('ubahpassword.index') }}" class="side-menu__item {{ request()->routeIs('ubahpassword.*') ? 'active' : '' }}">Ubah Password</a>
                                </li>
                            </ul>
                        </li>
                        <!-- End::slide -->

                        <!-- End::slide -->
                    </ul>
                    <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path> </svg></div>
                </nav>