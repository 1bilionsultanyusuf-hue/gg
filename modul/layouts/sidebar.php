<?php
// Role-based menu configuration
$user_role = $_SESSION['user_role'];

$menu_items = [
    'dashboard' => [
        'icon' => 'fas fa-home',
        'text' => 'Dashboard',
        'roles' => ['admin', 'manager', 'programmer', 'support'],
        'tooltip' => 'Dashboard'
    ],
    'apps' => [
        'icon' => 'fas fa-th-large',
        'text' => 'Apps',
        'roles' => ['admin', 'manager', 'programmer', 'support'],
        'tooltip' => 'Kelola Aplikasi'
    ],
    'users' => [
        'icon' => 'fas fa-users',
        'text' => 'Users',
        'roles' => ['admin', 'manager'],
        'tooltip' => 'Kelola Pengguna'
    ],
    'todos' => [
        'icon' => 'fas fa-list-check',
        'text' => 'Todos',
        'roles' => ['admin', 'manager'],
        'tooltip' => 'Kelola Tugas'
    ],
    'taken' => [
        'icon' => 'fas fa-chart-line',
        'text' => 'Taken',
        'roles' => ['admin', 'manager', 'programmer', 'support'],
        'tooltip' => 'Progress Tugas'
    ],
    'reports' => [
        'icon' => 'fas fa-chart-bar',
        'text' => 'Reports',
        'roles' => ['admin', 'manager', 'programmer'],
        'tooltip' => 'Laporan Sistem'
    ]
    // Settings menu telah dihapus
];

// Function to check if user has access to a menu item
function hasAccess($menu_roles, $user_role) {
    return in_array($user_role, $menu_roles);
}
?>

<div class="sidebar" id="sidebar">
    <!-- Profile Section -->
    <div class="profile-section">
        <div class="profile-avatar-container">
            <img src="<?= getProfilePhotoUrl($_SESSION['user_id']) ?>" 
                 alt="<?= htmlspecialchars($_SESSION['user_name']) ?>" 
                 class="profile-avatar"
                 onerror="this.src='<?= asset('images/default_profile_' . getUserGender($_SESSION['user_id']) . '.jpg') ?>'">
            <div class="role-indicator role-<?= $user_role ?>">
                <?php if($user_role == 'admin'): ?>
                    <i class="fas fa-crown"></i>
                <?php elseif($user_role == 'manager'): ?>
                    <i class="fas fa-user-tie"></i>
                <?php elseif($user_role == 'programmer'): ?>
                    <i class="fas fa-code"></i>
                <?php else: ?>
                    <i class="fas fa-headset"></i>
                <?php endif; ?>
            </div>
        </div>
        <div class="profile-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
        <div class="profile-role"><?= ucfirst($_SESSION['user_role']) ?></div>
        <a href="?page=profile" class="profile-edit-btn">
            <i class="fas fa-edit"></i>
            Edit Profile
        </a>
    </div>

    <!-- Navigation Menu -->
    <div class="menu-container">
        <div class="menu-items">
            <?php foreach($menu_items as $page_key => $menu_item): ?>
                <?php if(hasAccess($menu_item['roles'], $user_role)): ?>
                    <a href="?page=<?= $page_key ?>"
                       class="menu-item <?php echo ($page == $page_key) ? 'menu-active' : ''; ?>"
                       data-tooltip="<?= $menu_item['tooltip'] ?>">
                        <i class="<?= $menu_item['icon'] ?> menu-icon"></i>
                        <span class="nav-text"><?= $menu_item['text'] ?></span>
                        
                        <!-- Access indicator untuk role tertentu -->
                        <?php if($page_key == 'users' && $user_role == 'admin'): ?>
                            <span class="access-badge admin-only">Admin</span>
                        <?php elseif($page_key == 'apps' && in_array($user_role, ['admin', 'manager', 'programmer'])): ?>
                            <span class="access-badge dev-only">Dev</span>
                        <?php elseif($page_key == 'reports' && in_array($user_role, ['admin', 'manager', 'programmer'])): ?>
                            <span class="access-badge dev-only">Dev</span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>

        </div>

        <!-- Logout - Always at bottom -->
        <div class="logout-section">
            <a href="#" onclick="confirmLogout(event); return false;"
               class="menu-item logout-menu-item"
               data-tooltip="Logout">
                <i class="fas fa-sign-out-alt menu-icon"></i>
                <span class="nav-text">Logout</span>
            </a>
        </div>
    </div>
</div>

<!-- Overlay for Mobile -->
<div class="overlay" id="overlay"></div>

<style>
/* Enhanced Sidebar Styling dengan Role-Based Features - COMPLETELY FIXED VERSION */
.sidebar {
    background: white;
    width: 256px;
    height: calc(100vh - 60px);
    position: fixed;
    left: 0;
    top: 60px;
    box-shadow: 4px 0 15px rgba(0,0,0,0.1);
    z-index: 900;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* DESKTOP: Hidden state */
.sidebar.hidden {
    transform: translateX(-100%);
}

/* Main content adjustment - FIXED VERSION */
.main-content {
    margin-left: 256px;
    min-height: calc(100vh - 60px);
    transition: margin-left 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    padding: 20px;
    width: calc(100% - 256px);
    box-sizing: border-box;
}

.main-content.sidebar-hidden {
    margin-left: 0;
    width: 100%;
}

/* Untuk memastikan konten di tengah saat sidebar hidden */
body.sidebar-hidden .main-content {
    margin-left: 0 !important;
    width: 100% !important;
}

/* Profile Section dengan Role Indicator */
.profile-section {
    padding: 16px 20px;
    text-align: center;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.profile-avatar-container {
    margin-bottom: 12px;
    position: relative;
    display: inline-block;
}

.profile-avatar {
    width: 60px;
    height: 60px;
    object-fit: cover;
    margin: 0 auto;
    border-radius: 50%;
    border: 3px solid #0066ff;
    box-shadow: 0 4px 12px rgba(0,102,255,0.2);
    transition: all 0.3s ease;
    display: block;
}

/* Role Indicator pada Avatar */
.role-indicator {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: white;
    border: 2px solid white;
}

.role-indicator.role-admin {
    background: linear-gradient(135deg, #dc2626, #ef4444);
}

.role-indicator.role-manager {
    background: linear-gradient(135deg, #7c3aed, #a855f7);
}

.role-indicator.role-programmer {
    background: linear-gradient(135deg, #0066ff, #33ccff);
}

.role-indicator.role-support {
    background: linear-gradient(135deg, #10b981, #34d399);
}

.profile-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 2px;
    transition: opacity 0.3s ease;
}

.profile-role {
    font-size: 11px;
    color: #64748b;
    margin-bottom: 12px;
    transition: opacity 0.3s ease;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-edit-btn {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
    padding: 6px 12px;
    border-radius: 16px;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    gap: 6px;
}

.profile-edit-btn:hover {
    background: linear-gradient(90deg, #0044cc, #00aaff);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,102,255,0.3);
    color: white;
    text-decoration: none;
}

/* Menu Container - FIXED VERSION */
.menu-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-height: 0;
}

/* Menu Items Container - Scrollable area */
.menu-items {
    flex: 1;
    overflow-y: auto;
    padding: 8px 0;
    min-height: 0;
}

/* Logout Section - Always at bottom */
.logout-section {
    flex-shrink: 0;
    border-top: 1px solid #fee2e2;
    padding: 8px 0;
    background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(254,242,242,0.3));
}

/* Menu Items dengan Access Badges */
.menu-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #64748b;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
    margin: 1px 0;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}

.menu-item:hover {
    background: linear-gradient(90deg, rgba(0,102,255,0.05), rgba(51,204,255,0.02));
    color: #334155;
    border-left-color: #0066ff;
    transform: translateX(2px);
    text-decoration: none;
}

.menu-item.menu-active {
    background: linear-gradient(90deg, rgba(0,102,255,0.12), rgba(51,204,255,0.06));
    color: #0066ff;
    border-left-color: #0066ff;
    font-weight: 600;
}

.menu-icon {
    width: 18px;
    text-align: center;
    margin-right: 12px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.menu-item:hover .menu-icon {
    transform: scale(1.1);
}

.nav-text {
    font-size: 0.9rem;
    flex: 1;
}

/* Access Badges */
.access-badge {
    font-size: 0.6rem;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.3s ease;
}

.menu-item:hover .access-badge {
    opacity: 1;
    transform: scale(1);
}

.admin-only {
    background: linear-gradient(90deg, #dc2626, #ef4444);
    color: white;
}

.dev-only {
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
}

.support-only {
    background: linear-gradient(90deg, #10b981, #34d399);
    color: white;
}

/* Menu Divider */
.menu-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
    margin: 8px 16px;
    flex-shrink: 0;
}

/* Logout Menu Item - FIXED VERSION */
.logout-menu-item {
    color: #ef4444 !important;
    background: rgba(254, 242, 242, 0.3) !important;
    margin: 0 !important;
    border-left: 4px solid transparent !important;
    position: relative;
    overflow: hidden;
}

.logout-menu-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, rgba(239, 68, 68, 0.05), rgba(220, 38, 38, 0.02));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.logout-menu-item:hover {
    background: rgba(254, 242, 242, 0.6) !important;
    color: #dc2626 !important;
    border-left-color: #ef4444 !important;
    transform: translateX(2px);
    text-decoration: none;
}

.logout-menu-item:hover::before {
    opacity: 1;
}

.logout-menu-item:hover .menu-icon {
    transform: scale(1.1);
    color: #dc2626;
}

.logout-menu-item:active {
    transform: translateX(0);
    background: rgba(254, 226, 226, 0.8) !important;
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
    
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
    }
    
    .page-content {
        align-items: stretch; /* Full width di mobile */
    }
    
    .content-wrapper {
        max-width: none; /* Full width di mobile */
        padding: 0 16px;
    }
    
    .menu-item {
        padding: 14px 24px;
        font-size: 1rem;
    }
    
    .menu-icon {
        font-size: 1rem;
        margin-right: 16px;
    }
    
    .profile-section {
        padding: 20px;
    }
    
    .profile-avatar {
        width: 70px;
        height: 70px;
    }
    
    .role-indicator {
        width: 22px;
        height: 22px;
        font-size: 0.75rem;
    }
}

@media (max-width: 768px) {
    .sidebar {
        top: 55px;
        height: calc(100vh - 55px);
    }
    
    .sidebar.show {
        top: 0;
        height: 100vh;
    }
}

@media (max-width: 480px) {
    .sidebar {
        width: 260px;
    }
    
    .menu-item {
        padding: 12px 20px;
    }
    
    .profile-section {
        padding: 16px;
    }
    
    .profile-avatar {
        width: 60px;
        height: 60px;
    }
    
    .role-indicator {
        width: 18px;
        height: 18px;
        font-size: 0.65rem;
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
}

.logout-menu-item:focus {
    outline: 2px solid #ef4444;
    outline-offset: -2px;
}

.profile-edit-btn:focus {
    outline: 2px solid #fff;
    outline-offset: 2px;
}

/* Scrollbar untuk menu items - FIXED VERSION */
.menu-items::-webkit-scrollbar {
    width: 4px;
}

.menu-items::-webkit-scrollbar-track {
    background: transparent;
}

.menu-items::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.menu-items::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Smooth transitions for all elements */
* {
    box-sizing: border-box;
}

/* Prevent horizontal scroll on body */
body {
    overflow-x: hidden;
}
</style>

<script>
// ENHANCED SIDEBAR TOGGLE SCRIPT - COMPLETELY FIXED VERSION
document.addEventListener('DOMContentLoaded', function() {
    console.log('Sidebar script loaded');
    
    // Check if elements exist
    const sidebar = document.getElementById('sidebar');
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const overlay = document.getElementById('overlay');
    const mainContent = document.querySelector('.main-content');
    
    console.log('Elements found:', {
        sidebar: !!sidebar,
        hamburgerBtn: !!hamburgerBtn,
        overlay: !!overlay,
        mainContent: !!mainContent
    });
    
    // Initialize main content if not exists and wrap existing content
    if (!mainContent) {
        // Buat wrapper untuk main content
        const existingContent = Array.from(document.body.children).filter(child => 
            !child.classList.contains('sidebar') && 
            !child.classList.contains('overlay') && 
            child.tagName !== 'SCRIPT' &&
            child.tagName !== 'STYLE'
        );
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'main-content';
        
        // Buat page-content wrapper untuk centering
        const pageContentDiv = document.createElement('div');
        pageContentDiv.className = 'page-content';
        
        // Pindahkan konten yang ada ke dalam wrapper
        existingContent.forEach(element => {
            pageContentDiv.appendChild(element);
        });
        
        contentDiv.appendChild(pageContentDiv);
        document.body.appendChild(contentDiv);
        console.log('Main content wrapper created with page-content centering');
    } else if (!mainContent.querySelector('.page-content')) {
        // Jika main-content ada tapi belum ada page-content wrapper
        const pageContentDiv = document.createElement('div');
        pageContentDiv.className = 'page-content';
        
        // Pindahkan semua children ke dalam page-content
        while (mainContent.firstChild) {
            pageContentDiv.appendChild(mainContent.firstChild);
        }
        
        mainContent.appendChild(pageContentDiv);
        console.log('Page-content wrapper added to existing main-content');
    }
    
    // Initialize overlay click handler
    if (overlay) {
        overlay.addEventListener('click', function() {
            console.log('Overlay clicked');
            toggleSidebar();
        });
    }
    
    // Initialize menu item click handlers for mobile
    const menuItems = document.querySelectorAll('.menu-item:not(.logout-menu-item)');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                console.log('Menu item clicked on mobile');
                closeMobileSidebar();
            }
        });
    });
    
    // Set initial state based on screen size
    if (window.innerWidth > 1024) {
        showDesktopSidebar();
    }
    
    console.log('Sidebar initialization complete');
});

// Main toggle function - FIXED VERSION
function toggleSidebar() {
    console.log('toggleSidebar called, window width:', window.innerWidth);
    
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const overlay = document.getElementById('overlay');
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    
    if (!sidebar) {
        console.error('Sidebar element not found!');
        return;
    }
    
    if (window.innerWidth <= 1024) {
        // Mobile behavior
        const isVisible = sidebar.classList.contains('show');
        console.log('Mobile mode - sidebar visible:', isVisible);
        
        if (isVisible) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    } else {
        // Desktop behavior
        const isHidden = sidebar.classList.contains('hidden');
        console.log('Desktop mode - sidebar hidden:', isHidden);
        
        if (isHidden) {
            showDesktopSidebar();
        } else {
            hideDesktopSidebar();
        }
    }
}

// Mobile sidebar functions
function openMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    
    sidebar.classList.add('show');
    if (overlay) overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
    if (hamburgerBtn) hamburgerBtn.classList.add('active');
    
    console.log('Mobile sidebar opened');
}

function closeMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    
    sidebar.classList.remove('show');
    if (overlay) overlay.classList.remove('show');
    document.body.style.overflow = '';
    if (hamburgerBtn) hamburgerBtn.classList.remove('active');
    
    console.log('Mobile sidebar closed');
}

// Desktop sidebar functions - FIXED VERSION
function showDesktopSidebar() {
    const sidebar = document.getElementById('sidebar');
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const mainContent = document.querySelector('.main-content');
    
    sidebar.classList.remove('hidden');
    document.body.classList.remove('sidebar-hidden');
    if (mainContent) {
        mainContent.classList.remove('sidebar-hidden');
    }
    if (hamburgerBtn) hamburgerBtn.classList.remove('active');
    
    console.log('Desktop sidebar shown');
}

function hideDesktopSidebar() {
    const sidebar = document.getElementById('sidebar');
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const mainContent = document.querySelector('.main-content');
    
    sidebar.classList.add('hidden');
    document.body.classList.add('sidebar-hidden');
    if (mainContent) {
        mainContent.classList.add('sidebar-hidden');
    }
    if (hamburgerBtn) hamburgerBtn.classList.add('active');
    
    console.log('Desktop sidebar hidden');
}

// Handle window resize - FIXED VERSION
window.addEventListener('resize', function() {
    console.log('Window resized to:', window.innerWidth);
    
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const mainContent = document.querySelector('.main-content');
    
    if (window.innerWidth > 1024) {
        // Switch to desktop mode - close mobile sidebar if open
        if (sidebar && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            if (overlay) overlay.classList.remove('show');
            document.body.style.overflow = '';
            if (hamburgerBtn) hamburgerBtn.classList.remove('active');
            console.log('Closed mobile sidebar on resize');
        }
        
        // Ensure proper desktop state
        if (sidebar && !sidebar.classList.contains('hidden')) {
            showDesktopSidebar();
        }
    } else {
        // Mobile mode - ensure main content takes full width
        if (mainContent) {
            mainContent.classList.remove('sidebar-hidden');
        }
        document.body.classList.remove('sidebar-hidden');
    }
});

// Logout confirmation functions - ENHANCED VERSION
function confirmLogout(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const confirmModal = document.createElement('div');
    confirmModal.className = 'logout-confirm-modal';
    confirmModal.innerHTML = `
        <div class="modal-overlay" onclick="cancelLogout()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <div class="logout-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h3>Konfirmasi Logout</h3>
                <p>Apakah Anda yakin ingin keluar dari sistem?</p>
            </div>
            <div class="modal-actions">
                <button onclick="proceedLogout()" class="btn-confirm">
                    <i class="fas fa-check"></i>
                    <span>Ya, Logout</span>
                </button>
                <button onclick="cancelLogout()" class="btn-cancel">
                    <i class="fas fa-times"></i>
                    <span>Batal</span>
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(confirmModal);
    
    // Add modal styles if not exists
    if (!document.getElementById('logout-modal-styles')) {
        const style = document.createElement('style');
        style.id = 'logout-modal-styles';
        style.innerHTML = `
        .logout-confirm-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .logout-confirm-modal .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            animation: fadeIn 0.3s ease;
            cursor: pointer;
        }
        .logout-confirm-modal .modal-content {
            background: white;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            position: relative;
            animation: slideUp 0.3s ease;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 100%;
            z-index: 10000;
        }
        .logout-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        .logout-confirm-modal h3 {
            margin: 0 0 10px 0;
            color: #1f2937;
            font-size: 1.3rem;
            font-weight: 600;
        }
        .logout-confirm-modal p {
            margin: 0 0 25px 0;
            color: #6b7280;
            font-size: 1rem;
        }
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .btn-confirm, .btn-cancel {
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 100px;
            justify-content: center;
        }
        .btn-confirm {
            background: linear-gradient(90deg, #ef4444, #dc2626);
            color: white;
        }
        .btn-confirm:hover {
            background: linear-gradient(90deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        .btn-confirm:active {
            transform: translateY(0);
        }
        .btn-cancel {
            background: #f3f4f6;
            color: #374151;
        }
        .btn-cancel:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
        }
        .btn-cancel:active {
            transform: translateY(0);
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 480px) {
            .modal-actions {
                flex-direction: column;
            }
            .btn-confirm, .btn-cancel {
                width: 100%;
            }
        }
        `;
        document.head.appendChild(style);
    }
    
    // Focus on confirm button
    setTimeout(() => {
        const confirmBtn = confirmModal.querySelector('.btn-confirm');
        if (confirmBtn) confirmBtn.focus();
    }, 100);
    
    return false;
}

function proceedLogout() {
    const modal = document.querySelector('.logout-confirm-modal');
    const styles = document.getElementById('logout-modal-styles');
    
    // Remove modal with animation
    if (modal) {
        modal.style.opacity = '0';
        modal.style.transform = 'scale(0.95)';
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
    
    // Create success popup
    const popup = document.createElement('div');
    popup.className = 'logout-success-popup';
    popup.innerHTML = `
        <div class="popup-content">
            <div class="popup-circle">
                <div class="checkmark">✓</div>
            </div>
            <div class="popup-text">Logout berhasil!</div>
        </div>
    `;
    document.body.appendChild(popup);

    // Add success popup styles if not exists
    if (!document.getElementById('success-popup-styles')) {
        const successStyles = document.createElement('style');
        successStyles.id = 'success-popup-styles';
        successStyles.innerHTML = `
        .logout-success-popup {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10000;
            opacity: 1;
            transition: all 0.3s ease;
        }
        .popup-content {
            background: white;
            border-radius: 12px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border: 1px solid #d1fae5;
        }
        .popup-circle {
            width: 30px;
            height: 30px;
            border: 3px solid #10b981;
            border-radius: 50%;
            position: relative;
            animation: spin 0.5s ease forwards;
            flex-shrink: 0;
        }
        .checkmark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            font-size: 16px;
            color: #10b981;
            font-weight: bold;
            animation: scaleCheck 0.5s 0.5s forwards;
        }
        .popup-text { 
            font-size: 14px;
            font-weight: 600;
            color: #10b981;
        }
        @keyframes spin { 
            0%{transform:rotate(0deg);} 
            100%{transform:rotate(360deg);} 
        }
        @keyframes scaleCheck { 
            0%{transform:translate(-50%,-50%) scale(0);} 
            50%{transform:translate(-50%,-50%) scale(1.2);} 
            100%{transform:translate(-50%,-50%) scale(1);} 
        }
        `;
        document.head.appendChild(successStyles);
    }

    // Animate popup out and redirect
    setTimeout(() => { 
        popup.style.opacity = '0';
        popup.style.transform = 'translateX(-50%) translateY(-20px)';
    }, 1800);
    
    setTimeout(() => { 
        window.location.href = '?logout=1'; 
    }, 2200);
}

function cancelLogout() {
    const modal = document.querySelector('.logout-confirm-modal');
    
    if (modal) {
        modal.style.opacity = '0';
        modal.style.transform = 'scale(0.95)';
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

// Keyboard navigation for modal
document.addEventListener('keydown', function(e) {
    const modal = document.querySelector('.logout-confirm-modal');
    if (!modal) return;
    
    if (e.key === 'Escape') {
        cancelLogout();
    } else if (e.key === 'Enter') {
        const confirmBtn = modal.querySelector('.btn-confirm');
        if (confirmBtn && document.activeElement === confirmBtn) {
            proceedLogout();
        }
    } else if (e.key === 'Tab') {
        e.preventDefault();
        const buttons = modal.querySelectorAll('.btn-confirm, .btn-cancel');
        const currentIndex = Array.from(buttons).indexOf(document.activeElement);
        const nextIndex = e.shiftKey ? 
            (currentIndex <= 0 ? buttons.length - 1 : currentIndex - 1) :
            (currentIndex >= buttons.length - 1 ? 0 : currentIndex + 1);
        buttons[nextIndex].focus();
    }
});

// Test function for debugging
function testToggle() {
    console.log('Testing toggle function...');
    toggleSidebar();
}

// Global function untuk debugging - bisa dipanggil dari console
window.debugSidebar = function() {
    console.log('=== SIDEBAR DEBUG INFO ===');
    console.log('Window width:', window.innerWidth);
    console.log('Sidebar element:', document.getElementById('sidebar'));
    console.log('Hamburger button:', document.getElementById('hamburgerBtn'));
    console.log('Overlay element:', document.getElementById('overlay'));
    console.log('Main content:', document.querySelector('.main-content'));
    console.log('Sidebar classes:', document.getElementById('sidebar')?.className);
    console.log('Body classes:', document.body.className);
    console.log('Main content classes:', document.querySelector('.main-content')?.className);
    console.log('========================');
}

// Auto-adjust on page load - FIXED VERSION
window.addEventListener('load', function() {
    const mainContent = document.querySelector('.main-content');
    if (mainContent && window.innerWidth > 1024) {
        const sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('hidden')) {
            mainContent.classList.add('sidebar-hidden');
            document.body.classList.add('sidebar-hidden');
        } else {
            mainContent.classList.remove('sidebar-hidden');
            document.body.classList.remove('sidebar-hidden');
        }
    }
});
</script>