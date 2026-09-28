<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <!--begin::Sidebar Brand-->
  <div class="sidebar-brand">
    <!--begin::Brand Link-->
    <a href="{{ route('apriori.dashboard') }}" class="brand-link d-flex align-items-center gap-2 px-2" style="height: 56px; overflow: visible;">
      <!--begin::Brand Image-->
      <img
        src="{{ asset('images/beautyqueenzy-logo.svg') }}"
        alt="BeautyQueenzy Logo"
        class="opacity-75"
        style="float:none; margin:0; width:28px; height:28px; object-fit:contain;"
      />
      <!--end::Brand Image-->
      <!--begin::Brand Text-->
      <span class="brand-text fw-semibold" style="margin-left:0; font-size:0.95rem; white-space:nowrap;">BeautyQueenzy</span>
      <!--end::Brand Text-->
    </a>
    <!--end::Brand Link-->
  </div>
  <!--end::Sidebar Brand-->
  <!--begin::Sidebar Wrapper-->
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <!--begin::Sidebar Menu-->
      <ul
        class="nav sidebar-menu flex-column"
        data-lte-toggle="treeview"
        role="navigation"
        aria-label="Main navigation"
        data-accordion="false"
      >
        <li class="nav-item menu-open">
          <a href="{{ route('apriori.dashboard') }}" class="nav-link {{ request()->routeIs('apriori.dashboard') ? 'active' : '' }}">
            <i class="nav-icon bi bi-speedometer"></i>
            <p>
              Dashboard
            </p>
          </a>
        </li>
        <li class="nav-item">
          <a href="{{ route('apriori.dashboard') }}#parameter" class="nav-link {{ request()->routeIs('apriori.dashboard') ? 'active' : '' }}">
            <i class="nav-icon bi bi-cloud-upload"></i>
            <p>
              Unggah Data
            </p>
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon bi bi-tree-fill"></i>
            <p>
              Hasil Analisis
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('apriori.dashboard') }}#grafik" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>Grafik</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('apriori.dashboard') }}#laporan" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>Cetak Laporan</p>
              </a>
            </li>
          </ul>
        </li>
      </ul>
      <!--end::Sidebar Menu-->
    </nav>
  </div>
  <!--end::Sidebar Wrapper-->
</aside>
<!--end::Sidebar-->
