<?php

?>

<footer class="site-footer">
    <div class="footer-content">
        <span>&copy; <?= date('Y') ?> made with <i class="fas fa-star"></i> by <strong>Todos list</strong> for a better web.</span>
    </div>
</footer>

<style>
/* SIMPLE FOOTER - Matches screenshot style */
.site-footer {
    padding: 20px;
    text-align: center;
    position: relative;
    z-index: 800;
    font-size: 0.875rem;
}

/* Footer on desktop with sidebar */
@media (min-width: 1025px) {
    .site-footer {
        margin-left: 260px; /* Match sidebar width */
    }
}

.footer-content {
    max-width: 1400px;
    margin: 0 auto;
}

.footer-content i.fa-star {
    color: #FFD700;
    margin: 0 4px;
    animation: heartbeat 1.5s ease-in-out infinite;
}

.footer-content strong {
    color: #64748b;
    font-weight: 600;
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    10%, 30% { transform: scale(1.1); }
    20%, 40% { transform: scale(1); }
}

/* Responsive Design */
@media (max-width: 1024px) {
    .site-footer {
        margin-left: 0; /* No sidebar margin on mobile/tablet */
        padding: 16px;
    }
}

@media (max-width: 768px) {
    .site-footer {
        font-size: 0.8rem;
        padding: 14px;
    }
}

@media (max-width: 480px) {
    .site-footer {
        font-size: 0.75rem;
        padding: 12px;
    }
}

/* Body structure to ensure footer stays at bottom */
body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.main-content {
    flex: 1;
}
</style>