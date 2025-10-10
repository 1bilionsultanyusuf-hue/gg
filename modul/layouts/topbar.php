<?php
// modul/layouts/topbar.php - Updated with hamburger button
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- FontAwesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<header class="site-header">
    <div class="header-container">
        <!-- Hamburger Menu Button - LEFT -->
        <button class="hamburger-menu" id="hamburgerBtn" type="button" aria-label="Toggle Menu">
            <i class="fas fa-bars"></i>
        </button>

        <!-- CENTER - Logo utama -->
        <h1 class="logo-main">
            <span class="logo-it">IT</span>
            <span class="logo-sep">|</span>
            <span class="logo-core">
                C<span class="saturn-o">O</span>RE
            </span>
        </h1>
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
    max-width: 1050px;
    margin: auto;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
    position: relative;
}

/* Hamburger Menu Button - Positioned at LEFT */
.hamburger-menu {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    z-index: 1001;
}

.hamburger-menu:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-50%) scale(1.05);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
}

.hamburger-menu:active {
    transform: translateY(-50%) scale(0.95);
}

.hamburger-menu i {
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

/* Animasi hamburger ketika sidebar terbuka */
.hamburger-menu.active i {
    transform: rotate(90deg);
}

/* Logo positioning - CENTER */
.logo-main {
    display: flex;
    align-items: center;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
    margin: 0;
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

/* Hover effects for logo */
.logo-main:hover {
    transform: scale(1.05);
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

    .hamburger-menu {
        width: 35px;
        height: 35px;
    }

    .hamburger-menu i {
        font-size: 1rem;
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
}

/* Focus states for accessibility */
.hamburger-menu:focus {
    outline: 2px solid #fff;
    outline-offset: 2px;
}

/* Body padding untuk fixed header - REMOVED untuk menghindari konflik */
/* Padding akan diatur di main-content saja */
</style>

<script>
// Initialize hamburger button functionality
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Call toggleSidebar function if it exists
            if (typeof toggleSidebar === 'function') {
                toggleSidebar();
            } else {
                console.warn('toggleSidebar function not found');
            }
        });
        
        console.log('Hamburger button initialized successfully');
    } else {
        console.error('Hamburger button not found');
    }
});
</script>