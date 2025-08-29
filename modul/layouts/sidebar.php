<div class="sidebar bg-white w-64 h-full fixed shadow-lg z-10 transition-all duration-300">
    <!-- Profile Section -->
    <div class="profile-section p-6 text-center border-b bg-gray-50">
        <div class="profile-avatar-container mb-4">
            <img src="http://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70d693f7-a49d-4f3c-bb82-4a70c1893573.png" 
                 alt="Profile" 
                 class="profile-avatar mx-auto rounded-full border-4 border-blue-500 shadow-lg"
                 onerror="this.src='https://ui-avatars.com/api/?name=Prototype&background=0066ff&color=fff&size=80'">
        </div>
        <div class="profile-info">
            <h3 class="profile-name text-lg font-semibold text-gray-800">Prototype</h3>
            <p class="profile-email text-sm text-gray-600 mb-3">user@example.com</p>
            <a href="?page=profile" class="profile-edit-btn">
                <i class="fas fa-user-edit mr-1"></i>
                <span class="nav-text">Lihat Profil</span>
            </a>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="menu-container overflow-y-auto h-[calc(100%-16rem)]">
        <!-- Dashboard -->
        <a href="?page=dashboard"
           class="menu-item <?php echo ($page=='dashboard') ? 'menu-active' : ''; ?>">
            <i class="fas fa-home menu-icon"></i>
            <span class="nav-text">Dashboard</span>
        </a>

        <!-- Apps -->
        <a href="?page=apps"
           class="menu-item <?php echo ($page=='apps') ? 'menu-active' : ''; ?>">
            <i class="fas fa-th-large menu-icon"></i>
            <span class="nav-text">Apps</span>
        </a>

        <!-- Users -->
        <a href="?page=users"
           class="menu-item <?php echo ($page=='users') ? 'menu-active' : ''; ?>">
            <i class="fas fa-users menu-icon"></i>
            <span class="nav-text">Users</span>
        </a>

        <!-- Todos -->
        <a href="?page=todos"
           class="menu-item <?php echo ($page=='todos') ? 'menu-active' : ''; ?>">
            <i class="fas fa-list-check menu-icon"></i>
            <span class="nav-text">Todos</span>
        </a>

        <!-- Pelaporan -->
        <a href="?page=pelaporan"
           class="menu-item <?php echo ($page=='pelaporan') ? 'menu-active' : ''; ?>">
            <i class="fas fa-chart-line menu-icon"></i>
            <span class="nav-text">Pelaporan</span>
        </a>

        <!-- Taken -->
        <a href="?page=taken"
           class="menu-item <?php echo ($page=='taken') ? 'menu-active' : ''; ?>">
            <i class="fas fa-cog menu-icon"></i>
            <span class="nav-text">Taken</span>
        </a>
    </div>
</div>

<style>
/* Profile Section Styling */
.profile-section {
    transition: all 0.3s ease;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    object-fit: cover;
    transition: all 0.3s ease;
}

.profile-name {
    transition: opacity 0.3s ease;
}

.profile-email {
    transition: opacity 0.3s ease;
}

.profile-edit-btn {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.profile-edit-btn:hover {
    background: linear-gradient(90deg, #0044cc, #00aaff);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,102,255,0.3);
}

/* Menu Toggle Button */
.menu-toggle-container {
    background: #f8fafc;
}

.menu-toggle-btn {
    width: 100%;
    display: flex;
    align-items: center;
    padding: 8px 12px;
    background: transparent;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #64748b;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.menu-toggle-btn:hover {
    background: #e2e8f0;
    border-color: #cbd5e1;
    color: #475569;
}

/* Menu Items */
.menu-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #64748b;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    margin: 2px 0;
}

.menu-item:hover {
    background: #f1f5f9;
    color: #334155;
    border-left-color: #0066ff;
}

.menu-item.menu-active {
    background: linear-gradient(90deg, rgba(0,102,255,0.1), rgba(51,204,255,0.05));
    color: #0066ff;
    border-left-color: #0066ff;
    font-weight: 600;
}

.menu-icon {
    width: 20px;
    text-align: center;
    margin-right: 12px;
    font-size: 1rem;
}

/* Logout Button */
.logout-section {
    background: #fef2f2;
    border-top: 1px solid #fecaca;
}

.logout-btn {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 10px 16px;
    background: linear-gradient(90deg, #dc2626, #ef4444);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.logout-btn:hover {
    background: linear-gradient(90deg, #b91c1c, #dc2626);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220,38,38,0.3);
}

/* Collapsed Sidebar Styles */
.sidebar.collapsed {
    width: 5rem !important;
}

.sidebar.collapsed .profile-section {
    padding: 1rem 0.5rem;
}

.sidebar.collapsed .profile-avatar {
    width: 40px;
    height: 40px;
    margin: 0 auto;
}

.sidebar.collapsed .profile-name,
.sidebar.collapsed .profile-email,
.sidebar.collapsed .profile-edit-btn {
    display: none;
}

.sidebar.collapsed .menu-toggle-container {
    padding: 8px;
}

.sidebar.collapsed .nav-text {
    display: none;
}

.sidebar.collapsed .menu-item {
    justify-content: center;
    padding: 12px;
}

.sidebar.collapsed .menu-icon {
    margin-right: 0;
}

.sidebar.collapsed .logout-section {
    padding: 8px;
}

.sidebar.collapsed .logout-btn {
    justify-content: center;
    padding: 10px;
}

/* Mobile Responsiveness */
@media (max-width: 1024px) {
    .sidebar {
        transform: translateX(-100%);
        z-index: 50;
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
    
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 40;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .overlay.show {
        opacity: 1;
        visibility: visible;
    }
}

/* Tooltips for collapsed state */
.sidebar.collapsed .menu-item {
    position: relative;
}

.sidebar.collapsed .menu-item:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    background: #1f2937;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    white-space: nowrap;
    z-index: 100;
    margin-left: 8px;
    opacity: 0;
    animation: fadeInTooltip 0.3s ease forwards;
}

.sidebar.collapsed .menu-item:hover::before {
    content: '';
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    border: 6px solid transparent;
    border-right-color: #1f2937;
    z-index: 100;
    margin-left: 2px;
    opacity: 0;
    animation: fadeInTooltip 0.3s ease forwards;
}

@keyframes fadeInTooltip {
    0% { opacity: 0; transform: translateY(-50%) translateX(-10px); }
    100% { opacity: 1; transform: translateY(-50%) translateX(0); }
}

/* Enhanced animations */
.menu-item {
    position: relative;
    overflow: hidden;
}

.menu-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s;
}

.menu-item:hover::before {
    left: 100%;
}

/* Profile hover effects */
.profile-avatar-container {
    position: relative;
}

.profile-avatar-container::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 0;
    height: 0;
    background: radial-gradient(circle, rgba(0,102,255,0.2), transparent);
    border-radius: 50%;
    transition: all 0.3s ease;
    z-index: 0;
}

.profile-avatar-container:hover::before {
    width: 100px;
    height: 100px;
}

.profile-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(0,102,255,0.3);
}
</style>