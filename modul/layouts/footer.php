<?php
// modul/layouts/footer.php - Updated footer
?>

<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-text">
            &copy; <?= date('Y') ?> IT | CORE - Creative Prototype System
        </div>
        <div class="footer-meta">
            <span class="footer-version">v1.0.0</span>
            <span class="footer-separator">•</span>
            <span class="footer-author">Developed by pr</span>
        </div>
    </div>
</footer>

<style>
/* Enhanced footer styling */
.site-footer {
    background: linear-gradient(135deg, #0066ff, #33ccff);
    color: #fff;
    padding: 20px;
    text-align: center;
    margin-top: auto;
    flex-shrink: 0;
    border-top: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.footer-text {
    font-size: 0.95rem;
    font-weight: 500;
    color: #fff;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.footer-meta {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.footer-separator {
    color: rgba(255,255,255,0.6);
}

.footer-version {
    background: rgba(255,255,255,0.15);
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
    font-size: 0.75rem;
}

.footer-author {
    font-style: italic;
}

/* Responsive footer */
@media (max-width: 768px) {
    .site-footer {
        padding: 15px;
    }
    
    .footer-content {
        gap: 6px;
    }
    
    .footer-text {
        font-size: 0.85rem;
    }
    
    .footer-meta {
        font-size: 0.75rem;
        gap: 6px;
    }
}

@media (max-width: 480px) {
    .footer-meta {
        flex-direction: column;
        gap: 4px;
    }
    
    .footer-separator {
        display: none;
    }
}

/* Animation effects */
.site-footer:hover .footer-version {
    background: rgba(255,255,255,0.25);
    transform: scale(1.05);
    transition: all 0.3s ease;
}

.site-footer:hover .footer-text {
    transform: translateY(-1px);
    transition: all 0.3s ease;
}
</style>