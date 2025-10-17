<?php
// modul/layouts/topbar.php - Without hamburger button
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
    height: 55px;
}

.header-container {
    max-width: 1050px;
    margin: auto;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
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
</style>

</body>
</html>