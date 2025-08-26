// Enhanced main.js untuk layout fixed
document.addEventListener("DOMContentLoaded", function () {
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content');
    const overlay = document.getElementById('overlay');
    const submenuData = document.getElementById("dataSubmenu");
    const toggleData = document.getElementById("toggleDataMenu");
    let pendingMenuItem = null;
    let isMobile = window.innerWidth <= 1024;

    // === Initialize Layout ===
    initializeLayout();
    
    function initializeLayout() {
        // Set initial state based on screen size
        if (isMobile) {
            sidebar.classList.remove('show');
            content.classList.remove('ml-64', 'ml-20');
            overlay.style.display = 'none';
        } else {
            // Desktop: show sidebar by default
            sidebar.classList.remove('collapsed');
            content.classList.add('ml-64');
            content.classList.remove('ml-20');
        }
        
        // Restore sidebar state from localStorage (desktop only)
        if (!isMobile) {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                collapseSidebar();
            } else {
                expandSidebar();
            }
        }
    }

    // === Toggle Sidebar Function ===
    function toggleSidebarState() {
        if (isMobile) {
            // Mobile: slide in/out
            const isVisible = sidebar.classList.contains('show');
            if (isVisible) {
                hideMobileSidebar();
            } else {
                showMobileSidebar();
            }
        } else {
            // Desktop: collapse/expand
            const isCollapsed = sidebar.classList.contains('collapsed');
            if (isCollapsed) {
                expandSidebar();
            } else {
                collapseSidebar();
            }
        }
    }

    // === Desktop Sidebar Functions ===
    function collapseSidebar() {
        sidebar.classList.add('collapsed');
        content.classList.remove('ml-64');
        content.classList.add('ml-20');
        localStorage.setItem('sidebarCollapsed', 'true');
        
        // Hide submenu when collapsed
        if (submenuData) {
            submenuData.classList.add("hidden");
        }
        
        // Update toggle button icon
        updateToggleIcon(true);
    }

    function expandSidebar() {
        sidebar.classList.remove('collapsed');
        content.classList.remove('ml-20');
        content.classList.add('ml-64');
        localStorage.setItem('sidebarCollapsed', 'false');
        
        // Restore submenu state
        if (submenuData && localStorage.getItem("dataMenuOpen") === "true") {
            submenuData.classList.remove("hidden");
        }
        
        // Update toggle button icon
        updateToggleIcon(false);
    }

    // === Mobile Sidebar Functions ===
    function showMobileSidebar() {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent background scroll
    }

    function hideMobileSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = ''; // Restore scroll
    }

    // === Update Toggle Icon ===
    function updateToggleIcon(isCollapsed) {
        const icon = toggleSidebar.querySelector('i');
        if (icon) {
            if (isMobile) {
                icon.className = 'fas fa-bars';
            } else {
                icon.className = isCollapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
            }
        }
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
        overlay.addEventListener('click', function() {
            if (isMobile) {
                hideMobileSidebar();
            }
        });
    }

    // === Menu Item Click Handling ===
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            
            // Skip if it's a submenu toggle
            if (this.id === 'toggleDataMenu') {
                return;
            }
            
            if (isMobile) {
                // Mobile: hide sidebar after navigation
                hideMobileSidebar();
                // Navigate immediately
                if (href && href !== '#') {
                    window.location.href = href;
                }
            } else if (sidebar.classList.contains('collapsed')) {
                // Desktop collapsed: expand first, then navigate
                e.preventDefault();
                expandSidebar();
                
                // Highlight active menu
                setActiveMenuItem(this);
                
                // Navigate after animation
                setTimeout(() => {
                    if (href && href !== '#') {
                        window.location.href = href;
                    }
                }, 300);
            } else {
                // Desktop expanded: navigate normally
                setActiveMenuItem(this);
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

    // === Submenu Toggle ===
    if (toggleData && submenuData) {
        toggleData.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Don't toggle if sidebar is collapsed on desktop
            if (!isMobile && sidebar.classList.contains('collapsed')) {
                expandSidebar();
                return;
            }
            
            const isHidden = submenuData.classList.contains("hidden");
            submenuData.classList.toggle("hidden");
            
            // Update chevron icon
            const chevron = this.querySelector('.fa-chevron-down');
            if (chevron) {
                chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            }
            
            localStorage.setItem("dataMenuOpen", isHidden ? "true" : "false");
        });

        // Restore submenu state
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
                // Screen size category changed
                if (isMobile) {
                    // Switched to mobile
                    hideMobileSidebar();
                    content.classList.remove('ml-64', 'ml-20');
                } else {
                    // Switched to desktop
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                    initializeLayout();
                }
                updateToggleIcon(sidebar.classList.contains('collapsed'));
            }
        }, 250);
    });

    // === Keyboard Shortcuts ===
    document.addEventListener('keydown', function(e) {
        // Alt + S to toggle sidebar
        if (e.altKey && e.key === 's') {
            e.preventDefault();
            toggleSidebarState();
        }
        
        // Escape to close mobile sidebar
        if (e.key === 'Escape' && isMobile && sidebar.classList.contains('show')) {
            hideMobileSidebar();
        }
    });

    // === Page Load Animation ===
    function animatePageLoad() {
        const content = document.querySelector('main');
        if (content) {
            content.style.opacity = '0';
            content.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                content.style.transition = 'all 0.5s ease';
                content.style.opacity = '1';
                content.style.transform = 'translateY(0)';
            }, 100);
        }
    }

    // === Smooth Scroll for Hash Links ===
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // === Auto-hide Sidebar on Outside Click (Desktop) ===
    document.addEventListener('click', function(e) {
        if (!isMobile && !sidebar.classList.contains('collapsed')) {
            const isClickInsideSidebar = sidebar.contains(e.target);
            const isToggleButton = toggleSidebar.contains(e.target);
            
            if (!isClickInsideSidebar && !isToggleButton && window.innerWidth < 1200) {
                // Auto-collapse on smaller desktop screens
                collapseSidebar();
            }
        }
    });

    // === Initialize ===
    updateToggleIcon(sidebar.classList.contains('collapsed'));
    animatePageLoad();

    // === Performance Optimization ===
    // Debounce scroll events
    let scrollTimer;
    window.addEventListener('scroll', function() {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            // Add any scroll-based functionality here
        }, 10);
    });

    // === Accessibility Improvements ===
    // Focus management for keyboard navigation
    toggleSidebar.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });

    // Add ARIA labels
    toggleSidebar.setAttribute('aria-label', 'Toggle navigation menu');
    sidebar.setAttribute('aria-label', 'Main navigation');
    
    // Update ARIA states
    function updateAriaStates() {
        const isExpanded = !sidebar.classList.contains('collapsed') && (isMobile ? sidebar.classList.contains('show') : true);
        toggleSidebar.setAttribute('aria-expanded', isExpanded.toString());
    }

    // Call on state changes
    const observer = new MutationObserver(updateAriaStates);
    observer.observe(sidebar, { 
        attributes: true, 
        attributeFilter: ['class'] 
    });

    updateAriaStates();

    console.log('🎯 Layout initialized successfully!');
});