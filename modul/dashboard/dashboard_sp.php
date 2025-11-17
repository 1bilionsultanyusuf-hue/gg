<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f6fa;
    color: #2c3e50;
}

.dashboard-container {
    padding: 20px 30px;
    max-width: 100%;
}

/* Page Header */
.page-header {
    margin-bottom: 24px;
    padding: 8px 0;
}

.page-title {
    font-size: 2.1rem;
    font-weight: 600;
    color: #0d8af5;
    margin-bottom: 8px;
}

.page-subtitle {
    color: #6b7280;
    font-size: 0.9rem;
}

/* Welcome Card */
.welcome-card {
    background: linear-gradient(135deg, #0d8af5 0%, #0b7ad6 100%);
    border-radius: 12px;
    padding: 28px 32px;
    margin-bottom: 24px;
    color: white;
    box-shadow: 0 4px 20px rgba(13, 138, 245, 0.3);
}

.welcome-card h2 {
    font-size: 1.6rem;
    margin-bottom: 8px;
}

.welcome-card p {
    font-size: 0.95rem;
    opacity: 0.95;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 16px 20px;
    }
    
    .welcome-card {
        padding: 20px 24px;
    }
    
    .welcome-card h2 {
        font-size: 1.3rem;
    }
    
    .page-title {
        font-size: 1.6rem;
    }
}
</style>

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
    </div>

    <!-- Welcome Card -->
    <div class="welcome-card">
        <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>
        <p>Berikut adalah ringkasan sistem Anda hari ini</p>
    </div>
    
    <!-- Content khusus untuk dashboard_sp.php bisa ditambahkan di bawah ini -->
    
</div>

<script>
// Add animation on load
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.welcome-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 50);
    });
});
</script>