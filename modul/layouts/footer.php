<?php
// modul/layouts/footer.php - Enhanced Footer with Better Design
?>

<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-bottom">
            <div class="footer-copyright">
                <p>&copy; <?= date('Y') ?> IT | CORE. All rights reserved.</p>
            </div>
            <div class="footer-social">
                <a href="#" title="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
    </div>
</footer>

<style>
/* Enhanced Footer Styles */
.site-footer {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: #e2e8f0;
    margin-left: 256px;
    margin-top: 60px;
    padding: 0;
    transition: margin-left 0.3s ease;
    position: relative;
    width: calc(100% - 256px);
    box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
}

.site-footer.sidebar-hidden {
    margin-left: 0;
    width: 100%;
}

.footer-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0;
}

.footer-content {
    display: grid;
    grid-template-columns: 1.5fr 2.5fr;
    gap: 60px;
    padding: 50px 40px 30px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

/* Footer Brand */
.footer-brand {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
}

.footer-logo i {
    font-size: 2rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    padding: 12px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(102,126,234,0.3);
}

.footer-tagline {
    color: #94a3b8;
    font-size: 0.95rem;
    margin: 0;
    line-height: 1.6;
    max-width: 300px;
}

/* Footer Links */
.footer-links {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
}

.footer-section h4 {
    color: #fff;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 10px;
}

.footer-section h4::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 2px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 2px;
}

.footer-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-section ul li {
    margin-bottom: 12px;
}

.footer-section ul li a,
.footer-section ul li {
    color: #cbd5e1;
    text-decoration: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.footer-section ul li a:hover {
    color: #fff;
    transform: translateX(5px);
}

.footer-section ul li i {
    width: 16px;
    font-size: 0.85rem;
    color: #94a3b8;
}

/* Footer Bottom */
.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 40px;
    background: rgba(0,0,0,0.2);
}

.footer-copyright p {
    margin: 0;
    color: #94a3b8;
    font-size: 0.9rem;
}

.footer-social {
    display: flex;
    gap: 12px;
}

.footer-social a {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    color: #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.footer-social a:hover {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(102,126,234,0.4);
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.site-footer {
    animation: fadeInUp 0.6s ease;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .site-footer {
        margin-left: 0;
        width: 100%;
    }
    
    .footer-content {
        grid-template-columns: 1fr;
        gap: 40px;
        padding: 40px 30px 25px;
    }
    
    .footer-links {
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
    }
    
    .footer-bottom {
        flex-direction: column;
        gap: 20px;
        text-align: center;
        padding: 25px 30px;
    }
}

@media (max-width: 768px) {
    .footer-content {
        padding: 35px 20px 20px;
        gap: 30px;
    }
    
    .footer-links {
        grid-template-columns: 1fr;
        gap: 25px;
    }
    
    .footer-section h4 {
        font-size: 1rem;
        margin-bottom: 15px;
    }
    
    .footer-bottom {
        padding: 20px;
    }
    
    .footer-brand {
        text-align: center;
        align-items: center;
    }
    
    .footer-tagline {
        text-align: center;
        max-width: 100%;
    }
    
    .footer-section h4::after {
        left: 50%;
        transform: translateX(-50%);
    }
}

@media (max-width: 480px) {
    .footer-logo {
        font-size: 1.3rem;
    }
    
    .footer-logo i {
        font-size: 1.5rem;
        padding: 10px;
    }
    
    .footer-content {
        padding: 30px 16px 20px;
    }
    
    .footer-section ul li a,
    .footer-section ul li {
        font-size: 0.85rem;
    }
    
    .footer-social a {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
    
    .footer-copyright p {
        font-size: 0.85rem;
    }
}

/* Print Styles */
@media print {
    .site-footer {
        display: none;
    }
}
</style>