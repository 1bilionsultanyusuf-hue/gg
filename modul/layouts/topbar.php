<?php
// modul/layouts/topbar.php - Updated with centered logo
?>

<header class="site-header">
    <div class="header-container">
        <!-- LEFT SIDE - Mobile menu button atau kosong -->
        <div class="header-left">
            <!-- Mobile menu button (hidden on desktop) -->
            <button class="mobile-menu-btn lg:hidden" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- CENTER - Logo utama -->
        <h1 class="logo-main">
            <span class="logo-it">IT</span>
            <span class="logo-sep">|</span>
            <span class="logo-core">
                C<span class="saturn-o">O</span>RE
            </span>
        </h1>

        <!-- RIGHT SIDE - Profile & Actions -->
        <div class="header-right">
            <div class="header-actions">
                <!-- Notification button -->
                <button class="icon-btn text-gray-200 hover:text-white" title="Notifications">
                    <i class="fas fa-bell"></i>
                </button>
                
                <!-- Profile section -->
                <div class="profile" title="User Profile">
                    <img
                        src="http://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/70d693f7-a49d-4f3c-bb82-4a70c1893573.png"
                        alt="User Avatar" 
                        class="profile-img" 
                        onerror="this.src='https://ui-avatars.com/api/?name=Prototype&background=0066ff&color=fff&size=32'"
                    />
                    <span class="profile-name">Prototype</span>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// Mobile menu toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle mobile sidebar
            const isVisible = sidebar.classList.contains('show');
            if (isVisible) {
                sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
                document.body.style.overflow = '';
            } else {
                sidebar.classList.add('show');
                if (overlay) overlay.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
            
            // Update icon
            const icon = this.querySelector('i');
            icon.className = isVisible ? 'fas fa-bars' : 'fas fa-times';
        });
    }
    
    // Logo hover effect
    const logo = document.querySelector('.logo-main');
    if (logo) {
        logo.addEventListener('mouseenter', function() {
            this.classList.add('logo-hover');
        });
        
        logo.addEventListener('mouseleave', function() {
            this.classList.remove('logo-hover');
        });
    }
});
</script>