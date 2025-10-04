<?php
/**
 * Footer Module
 * Footer modulare del sito
 */

$moduleData = $renderer->getModuleData('footer', $config);
$columns = $config['columns'] ?? 4;
$social = $config['social'] ?? true;
$copyright = $config['copyright'] ?? '&copy; 2025 Bologna Marathon. Tutti i diritti riservati.';
?>

<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-section">
                <h3 class="footer-title">Bologna Marathon</h3>
                <p class="footer-description">La corsa più bella d'Itaxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</p>
                <?php if ($social): ?>
                    <div class="social-links">
                        <a href="#" class="social-link" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="footer-section">
                <h4 class="footer-subtitle">Link Utili</h4>
                <ul class="footer-links">
                    <li><a href="#regolamento">Regolamento</a></li>
                    <li><a href="#percorso">Percorso</a></li>
                    <li><a href="#servizi">Servizi</a></li>
                    <li><a href="#sponsor">Sponsor</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4 class="footer-subtitle">Contatti</h4>
                <div class="contact-info">
                    <p><i class="fas fa-envelope"></i> info@bolognamarathon.run</p>
                    <p><i class="fas fa-phone"></i> +39 051 123456</p>
                    <p><i class="fas fa-map-marker-alt"></i> Bologna, Italia</p>
                </div>
            </div>
            
            <div class="footer-section">
                <h4 class="footer-subtitle">Seguici</h4>
                <div class="newsletter">
                    <p>Rimani aggiornato sulle novità</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="La tua email" class="newsletter-input">
                        <button type="submit" class="newsletter-btn">Iscriviti</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p class="copyright"><?= $copyright ?></p>
        </div>
    </div>
</footer>
