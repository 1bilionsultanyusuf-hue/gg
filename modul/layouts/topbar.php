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
/* Header styling - SYNCHRONIZED with sidebar height 60px */
.site-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: linear-gradient(135deg, #0066ff, #33ccff);
    color: #fff;
    padding: 0 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    height: 60px; /* SAMA dengan height di sidebar calculation */
}

.header-container {
    max-width: 1400px;
    margin: 0 auto;
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
    gap: 8px;
}

/* Logo styling */
.logo-it {
    background: linear-gradient(90deg, #FFD700, #FFB700, #FFA500);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 1.75rem;
    font-weight: 800;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    letter-spacing: 0.5px;
}

.logo-sep {
    color: rgba(255, 255, 255, 0.7);
    font-weight: 300;
    font-size: 1.5rem;
}

.logo-core {
    font-weight: 800;
    font-size: 1.5rem;
    color: #fff;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    letter-spacing: 1px;
}

/* Saturn O effect */
.saturn-o {
    position: relative;
    display: inline-block;
    color: #ffcc00;
    text-shadow: 0 0 10px rgba(255,204,0,0.4);
}

.saturn-o::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 1.7em;
    height: 0.4em;
    border: 2.5px solid #ffcc00;
    border-radius: 50%;
    transform: translate(-50%, -50%) rotate(-25deg);
    box-shadow: 0 0 10px rgba(255,204,0,0.4);
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
@media (max-width: 1024px) {
    .site-header {
        height: 60px;
        padding: 0 16px;
    }
    
    .logo-it {
        font-size: 1.6rem;
    }
    
    .logo-core {
        font-size: 1.35rem;
    }
    
    .logo-sep {
        font-size: 1.35rem;
    }
}

@media (max-width: 768px) {
    .site-header {
        height: 55px;
        padding: 0 15px;
    }
    
    .logo-it {
        font-size: 1.4rem;
    }
    
    .logo-core {
        font-size: 1.2rem;
    }
    
    .logo-sep {
        font-size: 1.2rem;
    }
    
    .saturn-o::before {
        width: 1.6em;
        height: 0.35em;
        border-width: 2px;
    }
}

@media (max-width: 480px) {
    .site-header {
        height: 55px;
    }
    
    .logo-it {
        font-size: 1.2rem;
    }
    
    .logo-core {
        font-size: 1rem;
    }
    
    .logo-sep {
        font-size: 1rem;
    }
    
    .saturn-o::before {
        width: 1.4em;
        height: 0.3em;
    }
}

/* Body padding untuk accommodate fixed header */
body {
    padding-top: 60px;
}

@media (max-width: 768px) {
    body {
        padding-top: 55px;
    }
}
</style>

</body>
</html>