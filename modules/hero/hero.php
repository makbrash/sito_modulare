<?php
/**
 * Action Hero Module
 * Modulo hero con layout 2 colonne: testo + cards
 */

$moduleData = $renderer->getModuleData('actionHero', $config);
$height = $config['height'] ?? 'fit-content';
?>

<section class="hero-module" style="--hero-height: <?= $height ?>">
    <div class="hero-container">
        <div class="hero-layout">
            <!-- Colonna Sinistra - Hero Text (50%) -->
            <div class="hero-column hero-text-column">
                <div class="hero-text">
                    <div class="hero-date-tag">
                        <i class="fas fa-calendar-alt"></i>
                        <span>1 MARZO 2026</span>
                    </div>
                    <h1 class="hero-title"><strong>TERMAL</strong> BOLOGNA MARATHON</h1>
                    <p class="hero-subtitle">Corri Attraverso la Storia</p>
                    <p class="hero-description">
                        Tre percorsi unici nel cuore di Bologna. Scegli la tua sfida e vivi 
                        un'esperienza indimenticabile tra storia, cultura e sport.
                    </p>
                    <div class="hero-actions">
                        <?php
                        // Pulsante modulare per massima coerenza
                        echo $renderer->renderModule('button', [
                            'text' => 'Scopri le Gare',
                            'variant' => 'primary',
                            'size' => 'large',
                            'icon' => 'play',
                            'iconPosition' => 'left',
                            'href' => '#maratona'
                        ]);
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Colonna Destra - Cards (50%) -->
            <div class="hero-column hero-cards-column">
                <div class="hero-cards-grid">
                    <!-- Card 1: Maratona -->
                    <div class="hero-card theme-marathon">
                        <div class="card-number">01</div>
                        <div class="card-tag">GARA REGINA</div>
                        <h3 class="card-title">MARATONA</h3>
                        <h4 class="card-distance">42.195 KM</h4>
                        <p class="card-description">
                        La sfida definitiva attraverso tutti i monumenti storici di Bologna. Un percorso che unisce sport, storia e cultura in un'esperienza indimenticabile.
                        </p>
                        <div class="card-details">
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span>Tempo limite: 6 ore</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-euro-sign"></i>
                                <span>Quota: €65</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-medal"></i>
                                <span>Medaglia finisher</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-tshirt"></i>
                                <span>T-shirt tecnica</span>
                            </div>
                        </div>
                        <button class="card-button">Info e iscrizioni</button>
                    </div>
                    
                    <!-- Card 2: 30K Portici -->
                    <div class="hero-card theme-portici">
                        <div class="card-number">02</div>
                        <div class="card-tag">PATRIMONIO UNESCO</div>
                        <h3 class="card-title">30K DEI PORTICI</h3>
                        <h4 class="card-distance">30 KM</h4>
                        <p class="card-description">
                        Un percorso unico al mondo sotto i portici patrimonio UNESCO di Bologna. Corri attraverso la storia architettonica più affascinante d'Europa.
                        </p>
                        <div class="card-details">
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span>Tempo limite: 4 ore</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-euro-sign"></i>
                                <span>Quota: €45</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-landmark"></i>
                                <span>Portici UNESCO</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-star"></i>
                                <span>Esperienza unica</span>
                            </div>
                        </div>
                        <button class="card-button">Info 30K Portici</button>
                    </div>
                    
                    <!-- Card 3: Run Tune Up -->
                    <div class="hero-card theme-run-tune-up">
                        <div class="card-number">03</div>
                        <div class="card-tag">ACCESSIBILE A TUTTI</div>
                        <h3 class="card-title">RUN TUNE UP</h3>
                        <h4 class="card-distance">21.097 KM</h4>
                        <p class="card-description">
                        La mezza maratona nel cuore della città. Perfetta per chi vuole vivere l'esperienza Bologna Marathon in una distanza più accessibile.
                        </p>
                        <div class="card-details">
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span>Tempo limite: 3 ore</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-euro-sign"></i>
                                <span>Quota: €35</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-users"></i>
                                <span>Per tutti i livelli</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Centro storico</span>
                            </div>
                        </div>
                        <button class="card-button">Info Run Tune Up</button>
                    </div>
                    
                    <!-- Cards 4-5: 5K City Run e Kids Run in due colonne -->
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <!-- Card 4: 5K City Run -->
                            <div class="hero-card theme-5k">
                            <div class="card-number">04</div>
                                <div class="card-tag">SOLIDARIETÀ</div>
                                <h3 class="card-title">CITY RUN</h3>
                                <h4 class="card-distance">5 KM</h4>
                                <p class="card-description">
                                Corsa di solidarietà nel centro storico di Bologna. Unisciti alla grande squadra del Terzo Settore.
                                </p>
                                <div class="card-details">
                                    <div class="detail-item">
                                        <i class="fas fa-heart"></i>
                                        <span>Solidarietà</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-users"></i>
                                        <span>Per tutti</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <!-- Card 5: Kids Run -->
                            <div class="hero-card theme-race-kidsrun">
                                <div class="card-number">05</div>
                                <div class="card-tag">BAMBINI</div>
                                <h3 class="card-title">KIDS RUN</h3>
                                <h4 class="card-distance">1 KM</h4>
                                <p class="card-description">
                                La corsa dedicata ai più piccoli. Un'esperienza divertente e sicura per i bambini.
                                </p>
                                <div class="card-details">
                                    <div class="detail-item">
                                        <i class="fas fa-child"></i>
                                        <span>Bambini</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-smile"></i>
                                        <span>Divertimento</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Overlay per leggibilità -->
    <div class="hero-overlay"></div>
    
    <!-- Background image -->
    <!--<div class="hero-bg" style="background-image: url('assets/images/hero-bg.jpg')"></div>-->
</section>
