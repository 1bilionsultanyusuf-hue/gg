<div class="sidebar" id="sidebar">
    <!-- Profile Section -->
    <div class="profile-section">
        <div class="profile-avatar-container">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name']) ?>&background=0066ff&color=fff&size=80" 
                 alt="<?= htmlspecialchars($_SESSION['user_name']) ?>" 
                 class="profile-avatar"
                 onerror="this.src='https://ui-avatars.com/api/?name=User&background=0066ff&color=fff&size=80'">
        </div>
        <div class="profile-info">
            <h4 class="profile-name"><?= htmlspecialchars($_SESSION['user_name']) ?></h4>
            <p class="profile-role"><?= ucfirst($_SESSION['user_role']) ?></p>
            <a href="?page=profile" class="profile-edit-btn">
                <i class="fas fa-user-edit"></i>
                <span class="nav-text">Edit Profil</span>
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

        <!-- Taken -->
        <a href="?page=taken"
           class="menu-item <?php echo ($page=='taken') ? 'menu-active' : ''; ?>"
           data-tooltip="taken">
            <i class="fas fa-chart-line menu-icon"></i>
            <span class="nav-text">Taken</span>
        </a>

        <!-- Logout -->
        <a href="#" onclick="confirmLogout(event); return false;"
           class="menu-item logout-menu-item"
           data-tooltip="Logout">
            <i class="fas fa-sign-out-alt menu-icon"></i>
            <span class="nav-text">Logout</span>
        </a>
    </div>
</div>

<!-- Overlay for Mobile -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<style>
/* Enhanced Sidebar Styling - No Scroll Version */
.sidebar {
    background: white;
    width: 256px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 60px;
    box-shadow: 4px 0 15px rgba(0,0,0,0.1);
    z-index: 50;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    display: flex;
    flex-direction: column;
    overflow: hidden; /* Prevent any scrolling */
}

/* Profile Section Styling - Compact */
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
}

.profile-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(0,102,255,0.3);
}

.profile-name {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
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
}

.profile-edit-btn:hover {
    background: linear-gradient(90deg, #0044cc, #00aaff);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,102,255,0.3);
}

/* Menu Container - Fixed Height */
.menu-container {
    flex: 1;
    padding: 8px 0 16px 0;
    overflow: hidden; /* No scroll */
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

/* Menu Items - Compact */
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
}

/* Logout Menu Item - Red Style */
.logout-menu-item {
    color: #ef4444 !important;
    margin-top: 8px;
    border-top: 1px solid #fee2e2;
    padding-top: 16px !important;
}

.logout-menu-item:hover {
    background: linear-gradient(90deg, rgba(239, 68, 68, 0.08), rgba(220, 38, 38, 0.04)) !important;
    color: #dc2626 !important;
    border-left-color: #ef4444 !important;
}

.logout-menu-item.menu-active {
    background: linear-gradient(90deg, rgba(239, 68, 68, 0.12), rgba(220, 38, 38, 0.06)) !important;
    color: #dc2626 !important;
    border-left-color: #ef4444 !important;
}



/* Sidebar Hidden State */
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
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const overlay = document.getElementById('overlay');
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    
    if (window.innerWidth <= 1024) {
        // Mobile behavior
        const isVisible = sidebar.classList.contains('show');
        
        if (isVisible) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            if (hamburgerBtn) hamburgerBtn.classList.remove('active');
        } else {
            sidebar.classList.add('show');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
            if (hamburgerBtn) hamburgerBtn.classList.add('active');
        }
    } else {
        // Desktop behavior
        const isHidden = sidebar.classList.contains('hidden');
        
        if (isHidden) {
            sidebar.classList.remove('hidden');
            if (mainContent) mainContent.classList.remove('sidebar-hidden');
            if (hamburgerBtn) hamburgerBtn.classList.remove('active');
        } else {
            sidebar.classList.add('hidden');
            if (mainContent) mainContent.classList.add('sidebar-hidden');
            if (hamburgerBtn) hamburgerBtn.classList.add('active');
        }
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
            if (hamburgerBtn) hamburgerBtn.classList.remove('active');
        }
    });
});

// Logout confirmation function
function confirmLogout(e) {
    e.preventDefault();
    
    const confirmModal = document.createElement('div');
    confirmModal.className = 'logout-confirm-modal';
    confirmModal.innerHTML = `
        <div class="modal-overlay"></div>
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
                    <i class="fas fa-check mr-2"></i>Ya, Logout
                </button>
                <button onclick="cancelLogout()" class="btn-cancel">
                    <i class="fas fa-times mr-2"></i>Batal
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(confirmModal);
    
    // Add modal styles
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
        gap: 10px;
        justify-content: center;
    }
    .btn-confirm, .btn-cancel {
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }
    .btn-confirm {
        background: linear-gradient(90deg, #ef4444, #dc2626);
        color: white;
    }
    .btn-confirm:hover {
        background: linear-gradient(90deg, #dc2626, #b91c1c);
        transform: translateY(-1px);
    }
    .btn-cancel {
        background: #f3f4f6;
        color: #374151;
    }
    .btn-cancel:hover {
        background: #e5e7eb;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    `;
    document.head.appendChild(style);
    
    return false;
}

function proceedLogout() {
    const modal = document.querySelector('.logout-confirm-modal');
    const styles = document.getElementById('logout-modal-styles');
    
    // Remove modal
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.remove();
            if (styles) styles.remove();
        }, 300);
    }
    
    // Create success popup
    const popup = document.createElement('div');
    popup.className = 'logout-success-popup';
    popup.innerHTML = `
        <div class="popup-circle">
            <div class="checkmark">✓</div>
        </div>
        <div class="popup-text">Logout berhasil!</div>
    `;
    document.body.appendChild(popup);

    // Add success popup styles
    const successStyles = document.createElement('style');
    successStyles.innerHTML = `
    .logout-success-popup {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: white;
        border-radius: 12px;
        padding: 15px 25px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        font-weight: bold;
        color: #10b981;
        z-index: 10000;
        opacity: 1;
        transition: all 0.3s ease;
    }
    .popup-circle {
        width: 30px;
        height: 30px;
        border: 3px solid #10b981;
        border-radius: 50%;
        position: relative;
        animation: spin 0.5s ease forwards;
    }
    .checkmark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        font-size: 16px;
        color: #10b981;
        animation: scaleCheck 0.5s 0.5s forwards;
    }
    .popup-text { font-size: 14px; }
    @keyframes spin { 0%{transform:rotate(0deg);} 100%{transform:rotate(360deg);} }
    @keyframes scaleCheck { 
        0%{transform:translate(-50%,-50%) scale(0);} 
        50%{transform:translate(-50%,-50%) scale(1.2);} 
        100%{transform:translate(-50%,-50%) scale(1);} 
    }
    `;
    document.head.appendChild(successStyles);

    // Redirect after animation
    setTimeout(() => { popup.style.opacity = '0'; }, 1800);
    setTimeout(() => { 
        window.location.href = '?logout=1'; 
    }, 2000);
}

function cancelLogout() {
    const modal = document.querySelector('.logout-confirm-modal');
    const styles = document.getElementById('logout-modal-styles');
    
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.remove();
            if (styles) styles.remove();
        }, 300);
    }
}

// Handle window resize
window.addEventListener('resize', function() {
    if (window.innerWidth > 1024) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        if (hamburgerBtn) hamburgerBtn.classList.remove('active');
    }
});
</script>