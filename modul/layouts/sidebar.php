<div class="sidebar bg-white w-64 h-full fixed shadow-lg z-10 transition-all duration-300">
    <div class="flex items-center justify-between p-5 border-b">
        <span class="logo-text font-bold text-xl text-gray-800">MENU</span>
        <button id="toggleSidebar" class="text-gray-500 hover:text-gray-700 menu-button">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="menu-container overflow-y-auto h-[calc(100%-4rem)]">
        <!-- Dashboard -->
        <a href="?page=dashboard"
           class="menu-item flex items-center p-3 hover:text-gray-900 
           <?php echo ($page=='dashboard') ? 'text-blue-600 font-semibold' : 'text-gray-600'; ?>">
            <i class="fas fa-tachometer-alt w-6 text-center"></i>
            <span class="nav-text ml-3">Dashboard</span>
        </a>

        <!-- Data -->
        <div class="group">
            <a href="#" id="toggleDataMenu"
               class="menu-item flex items-center p-3 hover:text-gray-900 
               <?php echo in_array($page,['apps','users']) ? 'text-blue-600 font-semibold' : 'text-gray-600'; ?>">
                <i class="fas fa-database w-6 text-center"></i>
                <span class="nav-text ml-3">Data</span>
                <i class="fas fa-chevron-down ml-auto text-xs"></i>
            </a>
            <div id="dataSubmenu" class="submenu ml-8 <?php echo in_array($page,['apps','users']) ? '' : 'hidden'; ?>">
                <a href="?page=apps"
                   class="block px-4 py-2 rounded hover:bg-blue-50 
                   <?php echo ($page=='apps') ? 'bg-blue-100 text-blue-600 font-medium' : ''; ?>">
                   Applications
                </a>
                <a href="?page=users"
                   class="block px-4 py-2 rounded hover:bg-blue-50 
                   <?php echo ($page=='users') ? 'bg-blue-100 text-blue-600 font-medium' : ''; ?>">
                   Users
                </a>
            </div>
        </div>

        <!-- Todos -->
        <a href="?page=todos"
           class="menu-item flex items-center p-3 hover:text-gray-900 
           <?php echo ($page=='todos') ? 'text-blue-600 font-semibold' : 'text-gray-600'; ?>">
            <i class="fas fa-tasks w-6 text-center"></i>
            <span class="nav-text ml-3">Todos</span>
        </a>

        <!-- Pelaporan -->
        <a href="?page=pelaporan"
           class="menu-item flex items-center p-3 hover:text-gray-900 
           <?php echo ($page=='pelaporan') ? 'text-blue-600 font-semibold' : 'text-gray-600'; ?>">
            <i class="fas fa-file-alt w-6 text-center"></i>
            <span class="nav-text ml-3">Pelaporan</span>
        </a>

        <!-- Settings -->
        <a href="?page=settings"
           class="menu-item flex items-center p-3 hover:text-gray-900 
           <?php echo ($page=='settings') ? 'text-blue-600 font-semibold' : 'text-gray-600'; ?>">
            <i class="fas fa-cog w-6 text-center"></i>
            <span class="nav-text ml-3">Setting</span>
        </a>
    </div>
 </div>