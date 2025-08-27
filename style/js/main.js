// Updated main.js with profile sidebar and improved functionality
document.addEventListener("DOMContentLoaded", function () {
    const toggleSidebar = document.getElementById('toggleSidebar');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content');
    const overlay = document.getElementById('overlay');
    let isMobile = window.innerWidth <= 1024;

    console.log('🚀 Initializing sidebar with profile...');

    // === Initialize Layout ===
    function initializeLayout() {
        if (isMobile) {
            // Mobile: sidebar hidden by default
            sidebar.classList.remove('show', 'collapsed');
            content.style.marginLeft = '0';
            if (overlay) overlay.classList.remove('show');
            document.body.style.overflow = '';
        } else {
            // Desktop: sidebar visible, check saved state
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true') {
                collapseSidebar();
            } else {
                expandSidebar();
            }
        }
        
        updateToggleIcon();
        addTooltips();
        updateMobileMenuIcon();
    }

    // === Desktop Sidebar Functions ===
    function collapseSidebar() {
        if (isMobile) return;
        
        sidebar.classList.add('collapsed');
        content.style.marginLeft = '5rem';
        localStorage.setItem('sidebarCollapsed', 'true');
        
        updateToggleIcon();
        console.log('📱 Sidebar collapsed');
        
        // Add collapsed class to body for additional styling hooks
        document.body.classList.add('sidebar-collapsed');
    }

    function expandSidebar() {
        if (isMobile) return;
        
        sidebar.classList.remove('collapsed');
        content.style.marginLeft = '256px';
        localStorage.setItem('sidebarCollapsed', 'false');
        
        updateToggleIcon();
        console.log('📂 Sidebar expanded');
        
        document.body.classList.remove('sidebar-collapsed');
    }

    // === Mobile Sidebar Functions ===
    function showMobileSidebar() {
        if (!isMobile) return;
        
        sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        updateMobileMenuIcon();
        console.log('📱 Mobile sidebar shown');
    }

    function hideMobileSidebar() {
        if (!isMobile) return;
        
        sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
        document.body.style.overflow = '';
        updateMobileMenuIcon();
        console.log('📱 Mobile sidebar hidden');
    }

    // === Toggle Functions ===
    function toggleSidebarState() {
        if (isMobile) {
            const isVisible = sidebar.classList.contains('show');
            if (isVisible) {
                hideMobileSidebar();
            } else {
                showMobileSidebar();
            }
        } else {
            const isCollapsed = sidebar.classList.contains('collapsed');
            if (isCollapsed) {
                expandSidebar();
            } else {
                collapseSidebar();
            }
        }
    }

    // === Update Icons ===
    function updateToggleIcon() {
        const icon = toggleSidebar?.querySelector('i');
        if (!icon) return;
        
        if (isMobile) {
            icon.className = 'fas fa-bars';
        } else {
            const isCollapsed = sidebar.classList.contains('collapsed');
            icon.className = isCollapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
        }
    }

    function updateMobileMenuIcon() {
        const icon = mobileMenuToggle?.querySelector('i');
        if (!icon) return;
        
        const isVisible = sidebar.classList.contains('show');
        icon.className = isVisible ? 'fas fa-times' : 'fas fa-bars';
    }

    // === Add Tooltips for Collapsed State ===
    function addTooltips() {
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            const text = item.querySelector('.nav-text')?.textContent?.trim();
            if (text) {
                item.setAttribute('data-tooltip', text);
            }
        });
    }

    // === Event Listeners ===
    
    // Desktop sidebar toggle
    if (toggleSidebar) {
        toggleSidebar.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebarState();
        });
    }

    // Mobile menu toggle
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isVisible = sidebar.classList.contains('show');
            if (isVisible) {
                hideMobileSidebar();
            } else {
                showMobileSidebar();
            }
        });
    }

    // Overlay click (Mobile)
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            e.preventDefault();
            if (isMobile) {
                hideMobileSidebar();
            }
        });
    }

    // === Menu Item Click Handling ===
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            
            if (isMobile) {
                // Mobile: hide sidebar and navigate
                hideMobileSidebar();
                if (href && href !== '#') {
                    setTimeout(() => {
                        window.location.href = href;
                    }, 300);
                }
            } else if (sidebar.classList.contains('collapsed')) {
                // Desktop collapsed: expand first
                e.preventDefault();
                expandSidebar();
                
                setActiveMenuItem(this);
                
                if (href && href !== '#') {
                    setTimeout(() => {
                        window.location.href = href;
                    }, 350);
                }
            } else {
                // Desktop expanded: navigate normally
                setActiveMenuItem(this);
                if (href && href !== '#') {
                    content.classList.add('loading');
                }
            }
        });
    });

    // === Set Active Menu Item ===
    function setActiveMenuItem(activeItem) {
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.remove('menu-active');
        });
        
        activeItem.classList.add('menu-active');
    }

    // === Window Resize Handler ===
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const wasMobile = isMobile;
            isMobile = window.innerWidth <= 1024;
            
            if (wasMobile !== isMobile) {
                console.log(`📱 Screen changed: ${isMobile ? 'Mobile' : 'Desktop'}`);
                
                if (isMobile) {
                    // Switched to mobile
                    hideMobileSidebar();
                    content.style.marginLeft = '0';
                    sidebar.classList.remove('collapsed');
                    document.body.classList.remove('sidebar-collapsed');
                } else {
                    // Switched to desktop
                    sidebar.classList.remove('show');
                    if (overlay) overlay.classList.remove('show');
                    document.body.style.overflow = '';
                    initializeLayout();
                }
                updateToggleIcon();
                updateMobileMenuIcon();
            }
        }, 100);
    });

    // === Keyboard Shortcuts ===
    document.addEventListener('keydown', function(e) {
        // Alt + S to toggle sidebar
        if (e.altKey && e.key.toLowerCase() === 's') {
            e.preventDefault();
            toggleSidebarState();
        }
        
        // Escape to close mobile sidebar
        if (e.key === 'Escape' && isMobile && sidebar.classList.contains('show')) {
            hideMobileSidebar();
        }
        
        // Ctrl + P for profile (when sidebar is collapsed)
        if (e.ctrlKey && e.key.toLowerCase() === 'p') {
            if (!isMobile && sidebar.classList.contains('collapsed')) {
                e.preventDefault();
                window.location.href = '?page=profile';
            }
        }
    });

    // === Profile Image Error Handling ===
    const profileImages = document.querySelectorAll('.profile-avatar, .profile-img');
    profileImages.forEach(img => {
        img.addEventListener('error', function() {
            this.src = 'https://ui-avatars.com/api/?name=Prototype&background=0066ff&color=fff&size=80';
        });
    });

    // === Smooth Page Transitions ===
    function addPageTransition() {
        const links = document.querySelectorAll('a[href*="?page="]');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                if (!this.href.includes('#')) {
                    content.classList.add('loading');
                    
                    // Remove loading state if navigation fails
                    setTimeout(() => {
                        content.classList.remove('loading');
                    }, 3000);
                }
            });
        });
    }

    // === Profile Section Interactions ===
    function setupProfileInteractions() {
        const profileSection = document.querySelector('.profile-section');
        const profileAvatar = document.querySelector('.profile-avatar');
        
        if (profileSection && profileAvatar) {
            // Profile avatar click handler
            profileAvatar.addEventListener('click', function(e) {
                if (!sidebar.classList.contains('collapsed')) {
                    e.preventDefault();
                    window.location.href = '?page=profile';
                }
            });
            
            // Profile section hover effects
            profileSection.addEventListener('mouseenter', function() {
                if (!sidebar.classList.contains('collapsed')) {
                    this.style.background = 'linear-gradient(135deg, #f1f5f9, #e2e8f0)';
                }
            });
            
            profileSection.addEventListener('mouseleave', function() {
                this.style.background = 'linear-gradient(135deg, #f8fafc, #e2e8f0)';
            });
        }
    }

    // === Initialize Dashboard Chart (if exists) ===
    function initializeDashboardChart() {
        const chartCanvas = document.getElementById('activityChart');
        if (chartCanvas) {
            const ctx = chartCanvas.getContext('2d');
            
            // Set canvas size
            chartCanvas.width = chartCanvas.offsetWidth;
            chartCanvas.height = 200;
            
            // Draw a simple activity line chart
            const gradient = ctx.createLinearGradient(0, 0, 0, chartCanvas.height);
            gradient.addColorStop(0, 'rgba(0, 102, 255, 0.3)');
            gradient.addColorStop(1, 'rgba(0, 102, 255, 0.05)');
            
            // Sample data points
            const dataPoints = [20, 45, 28, 80, 99, 43, 50, 75, 65, 88];
            const maxVal = Math.max(...dataPoints);
            const stepX = chartCanvas.width / (dataPoints.length - 1);
            
            // Draw area under curve
            ctx.beginPath();
            ctx.moveTo(0, chartCanvas.height);
            dataPoints.forEach((point, index) => {
                const x = index * stepX;
                const y = chartCanvas.height - (point / maxVal) * (chartCanvas.height * 0.8);
                if (index === 0) {
                    ctx.lineTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.lineTo(chartCanvas.width, chartCanvas.height);
            ctx.closePath();
            ctx.fillStyle = gradient;
            ctx.fill();
            
            // Draw line
            ctx.beginPath();
            dataPoints.forEach((point, index) => {
                const x = index * stepX;
                const y = chartCanvas.height - (point / maxVal) * (chartCanvas.height * 0.8);
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.strokeStyle = '#0066ff';
            ctx.lineWidth = 3;
            ctx.stroke();
            
            // Draw points
            dataPoints.forEach((point, index) => {
                const x = index * stepX;
                const y = chartCanvas.height - (point / maxVal) * (chartCanvas.height * 0.8);
                
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, Math.PI * 2);
                ctx.fillStyle = '#0066ff';
                ctx.fill();
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.stroke();
            });
        }
    }

    // === Profile Section Enhancement ===
    function setupProfileInteractions() {
        const profileSection = document.querySelector('.profile-section');
        const profileAvatar = document.querySelector('.profile-avatar');
        const profileEditBtn = document.querySelector('.profile-edit-btn');
        
        if (profileSection && profileAvatar) {
            // Profile avatar click for quick profile access
            profileAvatar.addEventListener('click', function(e) {
                if (!sidebar.classList.contains('collapsed')) {
                    e.preventDefault();
                    showProfilePreview();
                } else {
                    window.location.href = '?page=profile';
                }
            });
            
            // Profile section hover effects
            profileSection.addEventListener('mouseenter', function() {
                if (!sidebar.classList.contains('collapsed')) {
                    this.style.background = 'linear-gradient(135deg, #f1f5f9, #e2e8f0)';
                    profileAvatar.style.transform = 'scale(1.05)';
                }
            });
            
            profileSection.addEventListener('mouseleave', function() {
                this.style.background = 'linear-gradient(135deg, #f8fafc, #e2e8f0)';
                profileAvatar.style.transform = 'scale(1)';
            });
        }
    }

    // === Profile Preview Modal ===
    function showProfilePreview() {
        const modal = document.createElement('div');
        modal.className = 'profile-preview-modal';
        modal.innerHTML = `
            <div class="modal-overlay" onclick="closeProfilePreview()"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Profile Quick View</h3>
                    <button onclick="closeProfilePreview()" class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="profile-preview">
                        <img src="http://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70d693f7-a49d-4f3c-bb82-4a70c1893573.png" 
                             alt="Profile" class="preview-avatar"
                             onerror="this.src='https://ui-avatars.com/api/?name=Prototype&background=0066ff&color=fff&size=100'">
                        <div class="preview-info">
                            <h4>Prototype User</h4>
                            <p>user@example.com</p>
                            <div class="preview-stats">
                                <div class="stat-item">
                                    <span class="stat-number">24</span>
                                    <span class="stat-label">Tasks Done</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">7d</span>
                                    <span class="stat-label">Last Login</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button onclick="window.location.href='?page=profile'" class="btn-primary">
                        <i class="fas fa-edit mr-2"></i>Edit Full Profile
                    </button>
                    <button onclick="closeProfilePreview()" class="btn-secondary">Close</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Add modal styles
        const style = document.createElement('style');
        style.id = 'profile-modal-styles';
        style.innerHTML = `
        .profile-preview-modal {
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
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
            position: relative;
            animation: slideUp 0.3s ease;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .modal-header {
            padding: 20px 20px 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            color: #1f2937;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #6b7280;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        .modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }
        .modal-body {
            padding: 20px;
        }
        .profile-preview {
            text-align: center;
        }
        .preview-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid #0066ff;
            margin-bottom: 15px;
            object-fit: cover;
        }
        .preview-info h4 {
            margin: 0 0 5px 0;
            color: #1f2937;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .preview-info p {
            margin: 0 0 15px 0;
            color: #6b7280;
            font-size: 0.9rem;
        }
        .preview-stats {
            display: flex;
            gap: 20px;
            justify-content: center;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: bold;
            color: #0066ff;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #6b7280;
        }
        .modal-footer {
            padding: 0 20px 20px 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn-primary, .btn-secondary {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        .btn-primary {
            background: linear-gradient(90deg, #0066ff, #33ccff);
            color: white;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #0044cc, #00aaff);
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        `;
        document.head.appendChild(style);
        
        setTimeout(() => modal.classList.add('show'), 10);
    }

    // === Close Profile Preview ===
    window.closeProfilePreview = function() {
        const modal = document.querySelector('.profile-preview-modal');
        const styles = document.getElementById('profile-modal-styles');
        
        if (modal) {
            modal.style.opacity = '0';
            modal.style.transform = 'scale(0.95)';
            setTimeout(() => {
                modal.remove();
                if (styles) styles.remove();
            }, 300);
        }
    }

    // === Menu Item Enhancements ===
    function enhanceMenuItems() {
        const menuItems = document.querySelectorAll('.menu-item');
        
        menuItems.forEach(item => {
            // Add ripple effect on click
            item.addEventListener('click', function(e) {
                if (!this.classList.contains('menu-active')) {
                    const ripple = document.createElement('span');
                    ripple.className = 'menu-ripple';
                    
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => ripple.remove(), 600);
                }
            });
            
            // Add focus handling for keyboard navigation
            item.addEventListener('focus', function() {
                this.style.outline = '2px solid #0066ff';
                this.style.outlineOffset = '2px';
            });
            
            item.addEventListener('blur', function() {
                this.style.outline = 'none';
            });
        });
        
        // Add ripple styles
        const rippleStyles = document.createElement('style');
        rippleStyles.innerHTML = `
        .menu-ripple {
            position: absolute;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(0, 102, 255, 0.3);
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0);
                opacity: 1;
            }
            100% {
                transform: scale(1);
                opacity: 0;
            }
        }
        `;
        document.head.appendChild(rippleStyles);
    }

    // === Logout Confirmation Function ===
    window.confirmLogout = function(e) {
        e.preventDefault();
        
        const confirmModal = document.createElement('div');
        confirmModal.className = 'logout-modal';
        confirmModal.innerHTML = `
            <div class="modal-overlay"></div>
            <div class="logout-modal-content">
                <div class="logout-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h3>Konfirmasi Logout</h3>
                <p>Apakah Anda yakin ingin keluar dari sistem?</p>
                <div class="logout-actions">
                    <button onclick="proceedLogout()" class="btn-logout-confirm">
                        <i class="fas fa-check mr-2"></i>Ya, Logout
                    </button>
                    <button onclick="cancelLogout()" class="btn-logout-cancel">
                        <i class="fas fa-times mr-2"></i>Batal
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(confirmModal);
        
        // Add logout modal styles
        const logoutStyles = document.createElement('style');
        logoutStyles.id = 'logout-modal-styles';
        logoutStyles.innerHTML = `
        .logout-modal {
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
        .logout-modal .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            animation: fadeIn 0.3s ease;
        }
        .logout-modal-content {
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
        .logout-modal-content h3 {
            margin: 0 0 10px 0;
            color: #1f2937;
            font-size: 1.3rem;
            font-weight: 600;
        }
        .logout-modal-content p {
            margin: 0 0 25px 0;
            color: #6b7280;
            font-size: 1rem;
        }
        .logout-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn-logout-confirm, .btn-logout-cancel {
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
        .btn-logout-confirm {
            background: linear-gradient(90deg, #ef4444, #dc2626);
            color: white;
        }
        .btn-logout-confirm:hover {
            background: linear-gradient(90deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
        }
        .btn-logout-cancel {
            background: #f3f4f6;
            color: #374151;
        }
        .btn-logout-cancel:hover {
            background: #e5e7eb;
        }
        `;
        document.head.appendChild(logoutStyles);
        
        return false;
    }

    // === Logout Handlers ===
    window.proceedLogout = function() {
        const modal = document.querySelector('.logout-modal');
        const styles = document.getElementById('logout-modal-styles');
        
        // Create success popup
        const popup = document.createElement('div');
        popup.className = 'popup-logout';
        popup.innerHTML = `
            <div class="popup-circle">
                <div class="checkmark">✔</div>
            </div>
            <div class="popup-text">Logout berhasil!</div>
        `;
        document.body.appendChild(popup);

        // Add success popup styles
        const successStyles = document.createElement('style');
        successStyles.innerHTML = `
        .popup-logout {
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
            font-family: 'Segoe UI', sans-serif;
            color: #ef4444;
            z-index: 10000;
            opacity: 1;
            transition: all 0.3s ease;
        }
        .popup-circle {
            width: 30px;
            height: 30px;
            border: 3px solid #ef4444;
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
            color: #ef4444;
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

        // Remove modal first
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
                if (styles) styles.remove();
            }, 300);
        }

        // Redirect after popup animation
        setTimeout(() => { popup.style.opacity = '0'; }, 1800);
        setTimeout(() => { 
            window.location.href = 'modul/auth/login.php'; 
        }, 2000);
    }

    window.cancelLogout = function() {
        const modal = document.querySelector('.logout-modal');
        const styles = document.getElementById('logout-modal-styles');
        
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
                if (styles) styles.remove();
            }, 300);
        }
    }

    // === Initialize Everything ===
    initializeLayout();
    addPageTransition();
    setupProfileInteractions();
    enhanceMenuItems();
    initializeDashboardChart();
    
    // Page load animations
    setTimeout(() => {
        document.body.classList.add('loaded');
    }, 100);
    
    console.log('✅ Enhanced sidebar with profile initialized successfully');
    
    // === Welcome Message (Optional) ===
    if (window.location.search.includes('login=success')) {
        setTimeout(() => {
            const welcomeToast = document.createElement('div');
            welcomeToast.className = 'welcome-toast';
            welcomeToast.innerHTML = `
                <i class="fas fa-hand-wave"></i>
                <span>Selamat datang kembali, Prototype!</span>
            `;
            document.body.appendChild(welcomeToast);
            
            const toastStyles = document.createElement('style');
            toastStyles.innerHTML = `
            .welcome-toast {
                position: fixed;
                top: 80px;
                right: 20px;
                background: linear-gradient(90deg, #10b981, #059669);
                color: white;
                padding: 12px 20px;
                border-radius: 12px;
                box-shadow: 0 8px 25px rgba(16,185,129,0.3);
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 10px;
                font-weight: 500;
                animation: slideInRight 0.5s ease, fadeOut 0.5s ease 3s forwards;
            }
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes fadeOut {
                to { opacity: 0; transform: translateX(100%); }
            }
            `;
            document.head.appendChild(toastStyles);
            
            setTimeout(() => {
                welcomeToast.remove();
                toastStyles.remove();
            }, 4000);
        }, 500);
    }
});