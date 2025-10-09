<?php

/**
 * Modulo: Footer
 * Descrizione: Footer modulare super responsive
 * 
 * @var ModuleRenderer $renderer
 * @var array $config
 */

$moduleData = $renderer->getModuleData('footer', $config);

// Configurazione
$logo = $config['logo'] ?? 'assets/images/logo-bologna-marathon.svg';
$title = $config['title'] ?? '<strong >TERMAL</strong> BOLOGNA MARATHON';
$description = $config['description'] ?? 'Tre percorsi unici nel cuore di Bologna. Un\'esperienza indimenticabile tra storia, cultura e sport.';
$date = $config['date'] ?? '1 Marzo 2026';
$location = $config['location'] ?? 'Bologna, Italia';

// Gare
$races = $config['races'] ?? [
    ['label' => 'Maratona 42K', 'url' => '#maratona', 'class' => 'theme-marathon'],
    ['label' => '30K Portici', 'url' => '#portici', 'class' => 'theme-portici'],
    ['label' => 'Run Tune Up 21K', 'url' => '#tuneup', 'class' => 'theme-run-tune-up'],
    ['label' => '5km City Run', 'url' => '#5k', 'class' => 'theme-5k'],
    ['label' => 'Kids Run', 'url' => '#kids', 'class' => 'theme-kidsrun']
];

// Informazioni
$infoLinks = $config['info_links'] ?? [
    ['label' => 'Info Percorso', 'url' => '#percorso'],
    ['label' => 'Regolamento', 'url' => '#regolamento'],
    ['label' => 'Iscrizioni', 'url' => '#iscrizioni'],
    ['label' => 'News', 'url' => '#news'],
    ['label' => 'FAQ', 'url' => '#faq']
];

// Contatti
$email = $config['email'] ?? 'info@bolognamarathon.run';
$phone = $config['phone'] ?? '+39 051 123 4567';
$address = $config['address'] ?? 'Via Indipendenza, 8';
$city = $config['city'] ?? '40121 Bologna (BO)';

// Social
$socialLinks = $config['social_links'] ?? [
    ['icon' => 'fab fa-facebook-f', 'url' => '#', 'label' => 'Facebook'],
    ['icon' => 'fab fa-instagram', 'url' => '#', 'label' => 'Instagram'],
    ['icon' => 'fab fa-youtube', 'url' => '#', 'label' => 'YouTube'],
    ['icon' => 'fab fa-twitter', 'url' => '#', 'label' => 'Twitter'],
    ['icon' => 'fab fa-strava', 'url' => '#', 'label' => 'Strava']
];

// Footer bottom
$copyright = $config['copyright'] ?? '2024 Bologna Marathon. Tutti i diritti riservati.';
$legalLinks = $config['legal_links'] ?? [
    ['label' => 'Privacy Policy', 'url' => '#privacy'],
    ['label' => 'Cookie Policy', 'url' => '#cookie'],
    ['label' => 'Termini di Servizio', 'url' => '#termini']
];
?>

<footer class="footer">
    <div class="container-fluid">

        <div class="footer__container">


            <!-- Footer Top -->
            <div class="footer__top row row--gap-sm">

                <!-- Logo e Descrizione -->
                <div class="footer__brand  footer__section  col-12 col-md-12 col-lg-4">
                    <img src="<?= htmlspecialchars($logo) ?>"
                        alt="Bologna Marathon Logo"
                        class="footer__logo">
                    <h3 class="footer__title"><?= $title ?></h3>
                    <p class="footer__description"><?= htmlspecialchars($description) ?></p>

                    <!-- Info Evento -->
                    <div class="footer__event-info">
                        <div class="footer__event-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?= htmlspecialchars($date) ?></span>
                        </div>
                        <div class="footer__event-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($location) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Gare -->
                <div class="footer__section  col-12 col-md-4 col-lg-2">
                    <h4 class="footer__section-title">GARE</h4>
                    <ul class="footer__links">
                        <?php foreach ($races as $race): ?>
                            <li class="footer__link-item">
                                <i class="fas fa-circle footer__bullet"></i>
                                <a href="<?= htmlspecialchars($race['url']) ?>"
                                    class="footer__link <?= htmlspecialchars($race['class'] ?? '') ?>">
                                    <?= htmlspecialchars($race['label']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Informazioni -->
                <div class="footer__section  col-12 col-md-6 col-lg-2">
                    <h4 class="footer__section-title">INFO</h4>
                    <ul class="footer__links">
                        <?php foreach ($infoLinks as $link): ?>
                            <li class="footer__link-item">
                                <a href="<?= htmlspecialchars($link['url']) ?>"
                                    class="footer__link">
                                    <?= htmlspecialchars($link['label']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Contatti -->
                <div class="footer__section  col-12 col-md-12 col-lg-4">
                    <h4 class="footer__section-title">CONTATTI</h4>
                    <div class="footer__contacts">
                        <a href="mailto:<?= htmlspecialchars($email) ?>" class="footer__contact">
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($email) ?></span>
                        </a>
                        <a href="tel:<?= str_replace(' ', '', $phone) ?>" class="footer__contact">
                            <i class="fas fa-phone"></i>
                            <span><?= htmlspecialchars($phone) ?></span>
                        </a>
                        <div class="footer__contact">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($address) ?><br><?= htmlspecialchars($city) ?></span>
                        </div>
                    </div>

                    <!-- Social -->
                    <h5 class="footer__social-title">SEGUICI</h5>
                    <div class="footer__social">
                        <?php foreach ($socialLinks as $social): ?>
                            <a href="<?= htmlspecialchars($social['url']) ?>"
                                class="footer__social-link"
                                aria-label="<?= htmlspecialchars($social['label']) ?>"
                                target="_blank"
                                rel="noopener">
                                <i class="<?= htmlspecialchars($social['icon']) ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- Footer Bottom -->
            <div class="footer__bottom row row--gap-sm">
                <p class="footer__copyright">
                    <i class="far fa-copyright"></i> <?= $copyright ?>
                </p>
                <div class="footer__legal">
                    <?php foreach ($legalLinks as $index => $link): ?>
                        <a href="<?= htmlspecialchars($link['url']) ?>" class="footer__legal-link">
                            <?= htmlspecialchars($link['label']) ?>
                        </a>
                        <?php if ($index < count($legalLinks) - 1): ?>
                            <span class="footer__legal-separator">|</span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</footer>