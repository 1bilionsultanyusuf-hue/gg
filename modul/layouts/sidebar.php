<div class="sidebar bg-white w-64 h-full fixed shadow-lg z-10">
    <div class="flex items-center p-5 border-b">
        <span class="logo-text ml-3 font-bold text-xl text-gray-800">MENU</span>
        <button id="toggleSidebar" class="text-gray-500 hover:text-gray-700 menu-button">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="menu-container overflow-y-auto h-[calc(100%-4rem)]">
        <a href="?page=dashboard" class="menu-item flex items-center p-3 text-gray-600 hover:text-gray-900">
            <i class="fas fa-tachometer-alt w-6 text-center"></i>
            <span class="nav-text ml-3">Dashboard</span>
        </a>

        <div class="group">
            <a href="#" id="toggleDataMenu"
               class="menu-item flex items-center p-3 text-gray-600 hover:text-gray-900">
                <i class="fas fa-database w-6 text-center"></i>
                <span class="nav-text ml-3">Data</span>
                <i class="fas fa-chevron-down ml-auto text-xs"></i>
            </a>
            <div id="dataSubmenu" class="submenu hidden ml-8">
                <a href="?page=apps" class="block px-4 py-2 hover:bg-blue-50">Applications</a>
                <a href="?page=users" class="block px-4 py-2 hover:bg-blue-50">Users</a>
            </div>
        </div>

        <a href="?page=todos" class="menu-item flex items-center p-3 text-gray-600 hover:text-gray-900">
            <i class="fas fa-tasks w-6 text-center"></i>
            <span class="nav-text ml-3">Todos</span>
        </a>

        <a href="?page=pelaporan" class="menu-item flex items-center p-3 text-gray-600 hover:text-gray-900">
            <i class="fas fa-file-alt w-6 text-center"></i>
            <span class="nav-text ml-3">Pelaporan</span>
        </a>

        <!-- Menu setting -->
        <a href="?page=settings" class="menu-item flex items-center p-3 text-gray-600 hover:text-gray-900">
            <i class="fas fa-cog w-6 text-center"></i>
            <span class="nav-text ml-3">Setting</span>
        </a>
    </div>
</div>
