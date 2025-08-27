<?php
// modul/layouts/topbar.php - Updated without profile section
?>

<header class="site-header">
    <div class="header-container">
        <!-- LEFT SIDE - Mobile menu button -->
        <div class="header-left">
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

        <!-- RIGHT SIDE - Notification & Actions -->
        <div class="header-right">
            <div class="header-actions">
            </div>
        </div>
    </div>
</header>

<style>
/* Header styling updates */
.site-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: linear-gradient(135deg, #0066ff, #33ccff);
    color: #fff;
    padding: 12px 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    height: 60px;
}

.header-container {
    max-width: 1200px;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 100%;
    position: relative;
}

/* Logo positioning - CENTER */
.logo-main {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    text-decoration: none;
    transition: all 0.3s ease;
    z-index: 10;
    cursor: pointer;
}

.header-left,
.header-right {
    flex: 1;
    display: flex;
    align-items: center;
}

.header-left {
    justify-content: flex-start;
}

.header-right {
    justify-content: flex-end;
}

/* Logo styling */
.logo-it {
    background: linear-gradient(90deg, #FFD700, #FFB700, #FFA500);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 1.6rem;
    font-weight: bold;
    text-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.logo-sep {
    color: rgba(255, 255, 255, 0.8);
    font-weight: 200;
    margin: 0 6px;
    font-size: 1.2rem;
}

.logo-core {
    font-weight: bold;
    font-size: 1.4rem;
    color: #fff;
    text-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Saturn O effect */
.saturn-o {
    position: relative;
    display: inline-block;
    color: #ffcc00;
}

.saturn-o::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 1.6em;
    height: 0.35em;
    border: 2px solid #ffcc00;
    border-radius: 50%;
    transform: translate(-50%, -50%) rotate(-25deg);
    box-shadow: 0 0 8px rgba(255,204,0,0.3);
}

/* Header actions */
.header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.icon-btn {
    background: rgba(255,255,255,0.1);
    border: none;
    color: rgba(255,255,255,0.9);
    padding: 8px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
}

.icon-btn:hover {
    background: rgba(255,255,255,0.2);
    color: #fff;
    transform: translateY(-1px);
}

/* Mobile menu button */
.mobile-menu-btn {
    background: rgba(255,255,255,0.1);
    color: #fff;
    border: none;
    padding: 8px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mobile-menu-btn:hover {
    background: rgba(255,255,255,0.2);
    transform: scale(1.1);
}

/* Hover effects for logo */
.logo-main:hover {
    transform: translate(-50%, -50%) scale(1.05);
}

.logo-main:hover .saturn-o::before {
    animation: rotate-ring 2s linear infinite;
}

@keyframes rotate-ring {
    0% { transform: translate(-50%, -50%) rotate(-25deg); }
    100% { transform: translate(-50%, -50%) rotate(335deg); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .site-header {
        height: 55px;
        padding: 8px 15px;
    }
    
    .logo-it {
        font-size: 1.3rem;
    }
    
    .logo-core {
        font-size: 1.1rem;
    }
    
    .logo-sep {
        font-size: 1rem;
        margin: 0 4px;
    }
    
    .header-actions {
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .logo-it {
        font-size: 1.1rem;
    }
    
    .logo-core {
        font-size: 0.95rem;
    }
    
    .saturn-o::before {
        width: 1.3em;
        height: 0.3em;
    }
    
    .header-actions {
        gap: 6px;
    }
    
    .icon-btn:last-child {
        display: none; /* Hide help button on very small screens */
    }
}
</style>

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
    
    // Search click handler
document.querySelectorAll('.icon-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // tidak ada aksi lagi
    });
});
</script>