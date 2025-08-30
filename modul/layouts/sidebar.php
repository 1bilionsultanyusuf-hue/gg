<div class="sidebar" id="sidebar">
    <!-- Profile Section -->
    <div class="profile-section">
        <div class="profile-avatar-container">
            <img src="http://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70d693f7-a49d-4f3c-bb82-4a70c1893573.png" 
                 alt="Profile" 
                 class="profile-avatar"
                 onerror="this.src='https://ui-avatars.com/api/?name=Prototype&background=0066ff&color=fff&size=80'">
        </div>
        <div class="profile-info">
            <a href="?page=profile" class="profile-edit-btn">
                <i class="fas fa-user-edit"></i>
                <span class="nav-text">Lihat profil</span>
            </a>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="menu-container">
        <!-- Dashboard -->
        <a href="?page=dashboard"
           class="menu-item <?php echo ($page=='dashboard') ? 'menu-active' : ''; ?>"
           data-tooltip="Dashboard">
            <i class="fas fa-home menu-icon"></i>
            <span class="nav-text">Dashboard</span>
        </a>

        <!-- Apps -->
        <a href="?page=apps"
           class="menu-item <?php echo ($page=='apps') ? 'menu-active' : ''; ?>"
           data-tooltip="Apps">
            <i class="fas fa-th-large menu-icon"></i>
            <span class="nav-text">Apps</span>
        </a>

        <!-- Users -->
        <a href="?page=users"
           class="menu-item <?php echo ($page=='users') ? 'menu-active' : ''; ?>"
           data-tooltip="Users">
            <i class="fas fa-users menu-icon"></i>
            <span class="nav-text">Users</span>
        </a>

        <!-- Todos -->
        <a href="?page=todos"
           class="menu-item <?php echo ($page=='todos') ? 'menu-active' : ''; ?>"
           data-tooltip="Todos">
            <i class="fas fa-list-check menu-icon"></i>
            <span class="nav-text">Todos</span>
        </a>

        <!-- Pelaporan -->
        <a href="?page=pelaporan"
           class="menu-item <?php echo ($page=='pelaporan') ? 'menu-active' : ''; ?>"
           data-tooltip="Pelaporan">
            <i class="fas fa-chart-line menu-icon"></i>
            <span class="nav-text">Pelaporan</span>
        </a>

        <!-- Settings -->
        <a href="?page=taken"
           class="menu-item <?php echo ($page=='taken') ? 'menu-active' : ''; ?>"
           data-tooltip="Taken">
            <i class="fas fa-code menu-icon"></i>
            <span class="nav-text">Taken</span>
        </a>
    </div>

    <!-- Logout Section -->
    <div class="logout-section">
        <button class="logout-btn" onclick="handleLogout()">
            <i class="fas fa-sign-out-alt"></i>
            <span class="nav-text">Logout</span>
        </button>
    </div>
</div>

<!-- Overlay for Mobile -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<style>
/* Enhanced Sidebar Styling - TANPA TOMBOL TOGGLE INTERNAL */
.sidebar {
    background: white;
    width: 256px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 60px; /* Account for header height */
    box-shadow: 4px 0 15px rgba(0,0,0,0.1);
    z-index: 50;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    display: flex;
    flex-direction: column;
}

/* Profile Section Styling */
.profile-section {
    padding: 24px 20px;
    text-align: center;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.profile-avatar-container {
    margin-bottom: 16px;
    position: relative;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    object-fit: cover;
    margin: 0 auto;
    border-radius: 50%;
    border: 4px solid #0066ff;
    box-shadow: 0 4px 12px rgba(0,102,255,0.2);
    transition: all 0.3s ease;
}

.profile-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(0,102,255,0.3);
}

.profile-name {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
    transition: opacity 0.3s ease;
}

.profile-email {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 16px;
    transition: opacity 0.3s ease;
}

.profile-edit-btn {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
    padding: 8px 16px;
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

/* Menu Container */
.menu-container {
    flex: 1;
    overflow-y: auto;
    padding: 8px 0;
    scrollbar-width: thin;
    scrollbar-color: rgba(0,102,255,0.3) transparent;
}

.menu-container::-webkit-scrollbar {
    width: 4px;
}

.menu-container::-webkit-scrollbar-track {
    background: transparent;
}

.menu-container::-webkit-scrollbar-thumb {
    background: rgba(0,102,255,0.3);
    border-radius: 2px;
}

.menu-container::-webkit-scrollbar-thumb:hover {
    background: rgba(0,102,255,0.5);
}

/* Menu Items */
.menu-item {
    display: flex;
    align-items: center;
    padding: 14px 20px;
    color: #64748b;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
    margin: 2px 0;
    position: relative;
    overflow: hidden;
}

.menu-item:hover {
    background: linear-gradient(90deg, rgba(0,102,255,0.05), rgba(51,204,255,0.02));
    color: #334155;
    border-left-color: #0066ff;
    transform: translateX(2px);
}

.menu-item.menu-active {
    background: linear-gradient(90deg, rgba(0,102,255,0.12), rgba(51,204,255,0.06));
    color: #0066ff;
    border-left-color: #0066ff;
    font-weight: 600;
}

.menu-icon {
    width: 20px;
    text-align: center;
    margin-right: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.menu-item:hover .menu-icon {
    transform: scale(1.1);
}

/* Enhanced menu animations */
.menu-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(0,102,255,0.1), transparent);
    transition: left 0.6s ease;
}

.menu-item:hover::before {
    left: 100%;
}

/* Logout Section */
.logout-section {
    flex-shrink: 0;
    padding: 16px 20px 20px;
    background: linear-gradient(135deg, #fafafa, #f1f5f9);
    border-top: 1px solid #e2e8f0;
}

.logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 10px 16px;
    background: linear-gradient(90deg, #ef4444, #f87171);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
}

.logout-btn:hover {
    background: linear-gradient(90deg, #dc2626, #ef4444);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.logout-btn:active {
    transform: translateY(0);
}

.logout-btn i {
    font-size: 0.85rem;
    margin-right: 8px;
}

/* Sidebar Hidden State - Controlled by hamburger */
.sidebar.hidden {
    transform: translateX(-100%);
}

/* Mobile Responsiveness */
@media (max-width: 1024px) {
    .sidebar {
        transform: translateX(-100%);
        z-index: 1100;
        width: 280px;
        top: 0;
        height: 100vh;
    }
    
    .sidebar.show {
        transform: translateX(0);
        box-shadow: 8px 0 25px rgba(0,0,0,0.2);
    }
    
    .menu-item {
        padding: 16px 24px;
        font-size: 1rem;
    }
    
    .menu-icon {
        font-size: 1.1rem;
        margin-right: 16px;
    }
    
    .profile-section {
        padding: 24px 20px;
    }
    
    .profile-avatar {
        width: 90px;
        height: 90px;
    }

    .logout-section {
        padding: 20px 24px 24px;
    }
}

@media (max-width: 480px) {
    .sidebar {
        width: 260px;
    }
    
    .menu-item {
        padding: 14px 20px;
    }
    
    .profile-section {
        padding: 20px 16px;
    }
    
    .profile-avatar {
        width: 75px;
        height: 75px;
    }

    .logout-section {
        padding: 16px 20px 20px;
    }

    .logout-btn {
        padding: 8px 14px;
        font-size: 0.85rem;
    }
}

/* Overlay for mobile */
.overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1050;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.overlay.show {
    display: block;
    opacity: 1;
}

/* Focus states for accessibility */
.menu-item:focus {
    outline: 2px solid #0066ff;
    outline-offset: -2px;
    background: rgba(0,102,255,0.1);
}

.profile-edit-btn:focus,
.logout-btn:focus {
    outline: 2px solid #fff;
    outline-offset: 2px;
}
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const overlay = document.getElementById('overlay');
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    
    if (window.innerWidth <= 1024) {
        // Mobile behavior - show/hide sidebar
        const isVisible = sidebar.classList.contains('show');
        
        if (isVisible) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            hamburgerBtn.classList.remove('active');
        } else {
            sidebar.classList.add('show');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
            hamburgerBtn.classList.add('active');
        }
    } else {
        // Desktop behavior - hide/show sidebar completely
        const isHidden = sidebar.classList.contains('hidden');
        
        if (isHidden) {
            sidebar.classList.remove('hidden');
            if (mainContent) mainContent.classList.remove('sidebar-hidden');
            hamburgerBtn.classList.remove('active');
        } else {
            sidebar.classList.add('hidden');
            if (mainContent) mainContent.classList.add('sidebar-hidden');
            hamburgerBtn.classList.add('active');
        }
    }
}

function handleLogout() {
    if (confirm('Apakah Anda yakin ingin logout?')) {
        alert('Logout berhasil!');
        // Tambahkan logika logout di sini
        // window.location.href = 'login.php';
    }
}

// Close sidebar when clicking menu item on mobile
document.querySelectorAll('.menu-item').forEach(item => {
    item.addEventListener('click', function() {
        if (window.innerWidth <= 1024) {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            hamburgerBtn.classList.remove('active');
        }
    });
});

// Handle window resize
window.addEventListener('resize', function() {
    if (window.innerWidth > 1024) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        hamburgerBtn.classList.remove('active');
    }
});
</script>