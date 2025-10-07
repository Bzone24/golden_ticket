<aside class="left-sidebar">
    <!-- Sidebar scroll -->
    <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
        <i class="ti ti-x fs-6"></i>
    </div>

    <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="./index.html" class="text-nowrap logo-img">
                <img src="{{ asset('assets/images/logos/logo.svg') }}" alt="" />
            </a>
        </div>

        <!-- Sidebar navigation -->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
            <ul id="sidebarnav">
                @hasanyrole('admin|shopkeeper|master')
                <li class="sidebar-item">
                    <a @class([
                        'sidebar-link',
                        'active' => request()->is(['admin/dashboard/*']),
                    ]) href="{{ route('admin.dashboard') }}" aria-expanded="false">
                        <i class="ti ti-atom"></i>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>
                @endhasanyrole

                @hasanyrole('admin|shopkeeper|master')
                <li class="sidebar-item">
                    <a @class([
                        'sidebar-link',
                        'active' => request()->is(['admin/shopkeepers/*']),
                    ]) href="{{ route('admin.shopkeepers') }}" aria-expanded="false">
                        <i class="ti ti-users"></i>
                        <span class="hide-menu">
                            @role('admin')
                                Shopkeeper
                            @elserole('shopkeeper')
                                User
                            @else
                                Shopkeeper/User
                            @endrole
                        </span>
                    </a>
                </li>
                @endhasanyrole

                @hasanyrole('master')
                <li class="sidebar-item">
                    <a @class(['sidebar-link', 'active' => request()->is(['admin/draw/*'])]) href="{{ route('admin.draw') }}" aria-expanded="false">
                        <i class="ti ti-plus"></i>
                        <span class="hide-menu">Draw</span>
                    </a>
                </li>
                 {{-- Cross Trace: add this directly after the Draw menu item --}}
                <li class="sidebar-item">
                    <a @class(['sidebar-link', 'active' => request()->is(['admin/cross-trace*'])]) 
                       href="{{ route('admin.cross-trace') }}" aria-expanded="false">
                        <i class="ti ti-trending-up"></i>
                        <span class="hide-menu">Cross Trace</span>
                    </a>
                </li>
                @endhasanyrole
            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll -->
</aside>
