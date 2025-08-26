// Enhanced main.js untuk layout fixed yang benar
document.addEventListener("DOMContentLoaded", function () {
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content');
    const overlay = document.getElementById('overlay');
    const submenuData = document.getElementById("dataSubmenu");
    const toggleData = document.getElementById("toggleDataMenu");
    let isMobile = window.innerWidth <= 1024;

    // === Initialize Layout Properly ===
    function initializeLayout() {
        console.log('🚀 Initializing layout...');
        
        if (isMobile) {
            // Mobile: sidebar hidden by default
            sidebar.classList.remove('show', 'collapsed');
            content.classList.remove('ml-64', 'ml-20');
            if (overlay) overlay.classList.remove('show');
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
    }

    // === Desktop Sidebar Functions ===
    function collapseSidebar() {
        if (isMobile) return;
        
        sidebar.classList.add('collapsed');
        content.classList.remove('ml-64');
        content.classList.add('ml-20');
        localStorage.setItem('sidebarCollapsed', 'true');
        
        // Hide submenu when collapsed
        if (submenuData) {
            submenuData.classList.add("hidden");
        }
        
        updateToggleIcon();
        console.log('📱 Sidebar collapsed');
    }

    function expandSidebar() {
        if (isMobile) return;
        
        sidebar.classList.remove('collapsed');
        content.classList.remove('ml-20');
        content.classList.add('ml-64');
        localStorage.setItem('sidebarCollapsed', 'false');
        
        // Restore submenu state if it was open
        if (submenuData && localStorage.getItem("dataMenuOpen") === "true") {
            submenuData.classList.remove("hidden");
        }
        
        updateToggleIcon();
        console.log('📂 Sidebar expanded');
    }

    // === Mobile Sidebar Functions ===
    function showMobileSidebar() {
        if (!isMobile) return;
        
        sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        console.log('📱 Mobile sidebar shown');
    }

    function hideMobileSidebar() {
        if (!isMobile) return;
        
        sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
        document.body.style.overflow = '';
        console.log('📱 Mobile sidebar hidden');
    }

    // === Toggle Function ===
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

    // === Update Toggle Icon ===
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
    if (toggleSidebar) {
        toggleSidebar.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebarState();
        });
    }

    // === Overlay Click (Mobile) ===
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
            
            // Skip submenu toggle
            if (this.id === 'toggleDataMenu') {
                return; // Let submenu handler take care of this
            }
            
            if (isMobile) {
                // Mobile: hide sidebar and navigate
                hideMobileSidebar();
                if (href && href !== '#') {
                    setTimeout(() => {
                        window.location.href = href;
                    }, 300); // Wait for animation
                }
            } else if (sidebar.classList.contains('collapsed')) {
                // Desktop collapsed: expand first
                e.preventDefault();
                expandSidebar();
                
                setActiveMenuItem(this);
                
                // Navigate after expansion animation
                if (href && href !== '#') {
                    setTimeout(() => {
                        window.location.href = href;
                    }, 350);
                }
            } else {
                // Desktop expanded: navigate normally
                setActiveMenuItem(this);
                if (href && href !== '#') {
                    // Add loading state
                    content.classList.add('loading');
                }
            }
        });
    });

    // === Set Active Menu Item ===
    function setActiveMenuItem(activeItem) {
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.remove('active', 'text-blue-600', 'font-semibold');
            item.classList.add('text-gray-600');
        });
        
        activeItem.classList.remove('text-gray-600');
        activeItem.classList.add('active', 'text-blue-600', 'font-semibold');
    }

    // === Submenu Toggle Handler ===
    if (toggleData && submenuData) {
        toggleData.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Don't toggle if sidebar is collapsed on desktop
            if (!isMobile && sidebar.classList.contains('collapsed')) {
                expandSidebar();
                setTimeout(() => {
                    toggleSubmenu();
                }, 350);
                return;
            }
            
            toggleSubmenu();
        });

        function toggleSubmenu() {
            const isHidden = submenuData.classList.contains("hidden");
            submenuData.classList.toggle("hidden");
            
            // Update chevron icon
            const chevron = toggleData.querySelector('.fa-chevron-down');
            if (chevron) {
                chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            }
            
            localStorage.setItem("dataMenuOpen", isHidden ? "true" : "false");
            console.log(`📋 Submenu ${isHidden ? 'opened' : 'closed'}`);
        }

        // Restore submenu state on load
        const isDataMenuOpen = localStorage.getItem("dataMenuOpen") === "true";
        if (isDataMenuOpen && (!isMobile || !sidebar.classList.contains('collapsed'))) {
            submenuData.classList.remove("hidden");
            const chevron = toggleData.querySelector('.fa-chevron-down');
            if (chevron) {
                chevron.style.transform = 'rotate(180deg)';
            }
        }
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
                    content.classList.remove('ml-64', 'ml-20');
                    sidebar.classList.remove('collapsed');
                } else {
                    // Switched to desktop
                    sidebar.classList.remove('show');
                    if (overlay) overlay.classList.remove('show');
                    document.body.style.overflow = '';
                    initializeLayout();
                }
                updateToggleIcon();
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
    })

})