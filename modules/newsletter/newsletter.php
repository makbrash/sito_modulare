<?php
/**
 * Modulo: Newsletter Registration Multi-Platform
 * Descrizione: Modulo con tab navigation per scegliere tra 4 modalità:
 *              - Email Newsletter (form classico)
 *              - WhatsApp Chat Diretta (invio messaggio con nome)
 *              - Canale WhatsApp Ufficiale (link diretto)
 *              - Chatta con Ingrid AI (assistente WhatsApp)
 * 
 * @var ModuleRenderer $renderer
 * @var array $config
 */

// Ottieni dati del modulo
$moduleData = $renderer->getModuleData('newsletter', $config);

// Configurazione globale
$title = $config['title'] ?? $moduleData['title'] ?? 'Resta in Contatto';
$subtitle = $config['subtitle'] ?? $moduleData['subtitle'] ?? 'Scegli il tuo canale preferito per ricevere aggiornamenti';
$variant = $config['variant'] ?? 'primary';
$defaultTab = $config['default_tab'] ?? 'email'; // email, whatsapp, channel, ingrid

// Configurazione Email
$emailPlaceholder = $config['email_placeholder'] ?? 'Email *';
$namePlaceholder = $config['name_placeholder'] ?? 'Nome *';
$privacyLink = $config['privacy_link'] ?? '/privacy-policy';
$emailButtonText = $config['email_button_text'] ?? 'Iscriviti via Email';

// Configurazione WhatsApp Chat
$whatsappNumber = $config['whatsapp_number'] ?? '393514383455';
$whatsappNamePlaceholder = $config['whatsapp_name_placeholder'] ?? 'Il tuo nome *';
$whatsappButtonText = $config['whatsapp_button_text'] ?? 'Invia Messaggio WhatsApp';

// Configurazione Canale WhatsApp
$channelUrl = $config['channel_url'] ?? 'https://whatsapp.com/channel/0029Vb2BgN0GOj9uUN5Zjp3Z';
$channelButtonText = $config['channel_button_text'] ?? 'Unisciti al Canale';
$channelMembers = $config['channel_members'] ?? '1.2K';

// Configurazione Ingrid AI
$ingridNumber = $config['ingrid_number'] ?? '393514383455';
$ingridButtonText = $config['ingrid_button_text'] ?? 'Chatta con Ingrid';
$ingridWelcomeMessage = $config['ingrid_welcome_message'] ?? 'Ciao Ingrid, puoi aiutarmi?';

// Background
$backgroundImage = $config['background_image'] ?? '';
$backgroundColor = $config['background_color'] ?? '';

// Sanitizzazione (solo per subtitle, title può contenere HTML)
$subtitle = htmlspecialchars($subtitle);
?>

<div class="newsletter newsletter--<?= htmlspecialchars($variant) ?>" 
     data-module="newsletter" 
     data-default-tab="<?= htmlspecialchars($defaultTab) ?>"
     data-config='<?= htmlspecialchars(json_encode($config)) ?>'
     <?php if ($backgroundColor): ?>
     style="--newsletter-bg: <?= htmlspecialchars($backgroundColor) ?>;"
     <?php endif; ?>>
    
    <?php if ($backgroundImage): ?>
        <div class="newsletter__background" style="background-image: url('<?= htmlspecialchars($backgroundImage) ?>');"></div>
    <?php endif; ?>
    
    <div class="newsletter__container">
        <div class="newsletter__content">
            
            <!-- Header -->
            <div class="newsletter__header">
                <?php if (!empty($title)): ?>
                    <h2 class="newsletter__title"><?= $title ?></h2>
                <?php endif; ?>
                
                <?php if (!empty($subtitle)): ?>
                    <p class="newsletter__subtitle"><?= $subtitle ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Tab Navigation -->
            <div class="newsletter__tabs">

                
                <button class="newsletter__tab newsletter__tab--channel" data-tab="channel" type="button" aria-label="Canale WhatsApp ufficiale">
                    <div class="newsletter__tab-circle">
                        <svg  class="iconSvg channel_whatsapp" svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#ffffff" d="M320 64C306.7 64 293.7 65 280.9 67L284.6 90.7C296.1 88.9 308 88 320 88C332 88 343.9 88.9 355.4 90.7L359.1 67C346.3 65 333.3 64 320 64zM380.8 71.3L375.1 94.6C398.5 100.3 420.5 109.5 440.5 121.7L453 101.2C430.9 87.8 406.6 77.6 380.8 71.3zM457.2 132.9C476.3 146.9 493.2 163.8 507.3 183L526.7 168.8C511 147.6 492.4 129 471.3 113.5L457.1 132.8zM538.9 187.1L518.4 199.6C530.6 219.6 539.8 241.6 545.5 265L568.8 259.3C562.5 233.5 552.3 209.2 538.9 187.1zM549.4 284.6C551.2 296.1 552.1 308 552.1 320C552.1 332 551.2 343.9 549.4 355.4L573.1 359.1C575 346.4 576.1 333.3 576.1 320C576.1 306.7 575.1 293.7 573.1 280.9L549.4 284.6zM518.4 440.5L538.9 453C552.3 430.9 562.5 406.6 568.8 380.8L545.5 375.1C539.8 398.5 530.6 420.5 518.4 440.5zM526.6 471.3L507.2 457.1C493.2 476.2 476.3 493.1 457.1 507.2L471.3 526.6C492.4 511.1 511.1 492.5 526.5 471.4zM440.5 518.3C420.5 530.5 398.5 539.7 375.1 545.4L380.8 568.7C406.6 562.4 430.9 552.2 453 538.8L440.5 518.3zM359.1 573L355.4 549.3C343.9 551.1 332 552 320 552C308 552 296.1 551.1 284.6 549.3L280.9 573C293.6 574.9 306.7 576 320 576C333.3 576 346.3 575 359.1 573zM265 545.4C247.4 541.1 230.6 534.8 214.9 526.8L207.1 522.8L174.3 530.5L179.8 553.9L204.1 548.2C221.5 557.1 240 564 259.4 568.7L265.1 545.4zM159.4 558.6L154 535.3L112.3 545C101.9 547.4 92.6 538.1 95 527.7L104.7 486.1L81.3 480.6L71.6 522.2C65.2 550 90 574.8 117.8 568.4L159.4 558.7zM109.4 465.7L117.1 432.9L113.1 425.1C105.1 409.4 98.8 392.6 94.5 375L71.3 380.7C76 400.1 82.9 418.7 91.7 436L86 460.3L109.4 465.8zM67 359.1L90.7 355.4C88.9 343.9 88 332 88 320C88 308 88.9 296.1 90.7 284.6L67 280.9C65 293.7 64 306.7 64 320C64 333.3 65 346.3 67 359.1zM94.6 265C100.3 241.6 109.5 219.6 121.7 199.6L101.2 187.1C87.8 209.2 77.6 233.5 71.3 259.3L94.6 265zM113.5 168.8L132.9 183C146.9 163.9 163.8 147 183 132.9L168.7 113.5C147.6 129 129 147.6 113.5 168.7zM199.6 121.8C219.6 109.6 241.6 100.4 265 94.7L259.2 71.3C233.4 77.6 209.1 87.8 187 101.2L199.5 121.7zM320 528C434.9 528 528 434.9 528 320C528 205.1 434.9 112 320 112C205.1 112 112 205.1 112 320C112 356.4 121.4 390.7 137.8 420.5C139.4 423.4 139.9 426.7 139.2 429.9L117.6 522.4L210.1 500.8C213.3 500.1 216.6 500.6 219.5 502.2C249.3 518.7 283.5 528 320 528z"/></class>
                    </div>
                    <span class="newsletter__tab-text">Canale</span>
                </button> 
                
                
                <button class="newsletter__tab newsletter__tab--ingrid" data-tab="ingrid" type="button" aria-label="Chat con Ingrid AI">
                    <div class="newsletter__tab-circle">
                    <svg class="iconSvg" data-prefix="fas" data-icon="sparkles" role="img" viewBox="0 0 576 512" aria-hidden="true" class="margin-y-4xs svg-inline--fa fa-sparkles fa-2x"><path fill="currentColor" d="M391.5 53.2c-4.5 1.7-7.5 6-7.5 10.8s3 9.1 7.5 10.8L448 96 469.2 152.5c1.7 4.5 6 7.5 10.8 7.5s9.1-3 10.8-7.5L512 96 568.5 74.8c4.5-1.7 7.5-6 7.5-10.8s-3-9.1-7.5-10.8L512 32 490.8-24.5c-1.7-4.5-6-7.5-10.8-7.5s-9.1 3-10.8 7.5L448 32 391.5 53.2zm-185 20.1c-2.6-5.7-8.3-9.3-14.5-9.3s-11.9 3.6-14.5 9.3l-53.1 115-115 53.1C3.6 244.1 0 249.8 0 256s3.6 11.9 9.3 14.5l115 53.1 53.1 115c2.6 5.7 8.3 9.3 14.5 9.3s11.9-3.6 14.5-9.3l53.1-115 115-53.1c5.7-2.6 9.3-8.3 9.3-14.5s-3.6-11.9-9.3-14.5l-115-53.1-53.1-115zM416 416l-56.5 21.2c-4.5 1.7-7.5 6-7.5 10.8s3 9.1 7.5 10.8L416 480 437.2 536.5c1.7 4.5 6 7.5 10.8 7.5s9.1-3 10.8-7.5L480 480 536.5 458.8c4.5-1.7 7.5-6 7.5-10.8s-3-9.1-7.5-10.8L480 416 458.8 359.5c-1.7-4.5-6-7.5-10.8-7.5s-9.1 3-10.8 7.5L416 416z" class=""></path></svg>
                    </div>
                    <span class="newsletter__tab-text">Ingrid AI</span>
                </button>  

                
                <button class="newsletter__tab newsletter__tab--whatsapp" data-tab="whatsapp" type="button" aria-label="Registrazione via WhatsApp">
                    <div class="newsletter__tab-circle">
                        <i class="fab fa-whatsapp newsletter__tab-icon"></i>
                    </div>
                    <span class="newsletter__tab-text">WhatsApp</span>
                </button>

                <button class="newsletter__tab newsletter__tab--active newsletter__tab--email" data-tab="email" type="button" aria-label="Registrazione via Email">
                    <div class="newsletter__tab-circle">
                        <i class="fas fa-envelope newsletter__tab-icon"></i>
                    </div>
                    <span class="newsletter__tab-text">Email</span>
                </button>

            </div>
            
            <!-- Descrizione dinamica del servizio selezionato -->
            <div class="newsletter__service-description">
                <div class="newsletter__service-info newsletter__service-info--email newsletter__service-info--active">
                    <h3 class="newsletter__service-title">Newsletter via Email</h3>
                    <p class="newsletter__service-text">Ricevi aggiornamenti direttamente nella tua casella di posta. Contenuti esclusivi, risultati gare e news in anteprima.</p>
                    <ul class="newsletter__service-pros">
                        <li><i class="fas fa-check"></i> Facile da gestire e disiscrizione rapida</li>
                    </ul>
                </div>
                
                <div class="newsletter__service-info newsletter__service-info--whatsapp">
                    <h3 class="newsletter__service-title">Chat WhatsApp Diretta</h3>
                    <p class="newsletter__service-text">Contattaci direttamente su WhatsApp per registrarti. Ricevi assistenza immediata e risposte in tempo reale.</p>
                    <ul class="newsletter__service-pros">
                        <li><i class="fas fa-check"></i> Comunicazione istantanea e personale</li>
                        <li><i class="fas fa-check"></i> Supporto diretto del nostro team</li>
                        <li><i class="fas fa-check"></i> Notifiche push immediate sul tuo smartphone</li>
                    </ul>
                </div>
                
                <div class="newsletter__service-info newsletter__service-info--channel">
                    <h3 class="newsletter__service-title">Canale WhatsApp Ufficiale</h3>
                    <p class="newsletter__service-text">Unisciti al nostro canale broadcast con oltre <?= htmlspecialchars($channelMembers) ?> membri. Aggiornamenti ufficiali in tempo reale.</p>
                    <ul class="newsletter__service-pros">
                        <li><i class="fas fa-check"></i> Comunicazioni ufficiali verificate</li>
                        <li><i class="fas fa-check"></i> Zero spam, solo contenuti di valore</li>
                        <li><i class="fas fa-check"></i> Community di appassionati di running</li>
                        <li><i class="fas fa-check"></i> Offerte e sconti esclusivi</li>
                    </ul>
                </div>
                
                <div class="newsletter__service-info newsletter__service-info--ingrid">
                    <h3 class="newsletter__service-title">Ingrid AI Assistant</h3>
                    <p class="newsletter__service-text">Chatta con Ingrid, la nostra assistente virtuale disponibile 24/7 su WhatsApp. Risposte immediate a qualsiasi domanda.</p>
                    <ul class="newsletter__service-pros">
                        <li><i class="fas fa-check"></i> Assistenza intelligente disponibile sempre</li>
                        <li><i class="fas fa-check"></i> Risposte istantanee e personalizzate</li>
                        <li><i class="fas fa-check"></i> Informazioni su gare, iscrizioni e percorsi</li>
                        <li><i class="fas fa-check"></i> tantissime sorprese e giochi</li>
                    </ul>
                </div>
            </div>
            
            <!-- Tab Panels -->
            <div class="newsletter__panels">
                
                <!-- Panel Email -->
                <div class="newsletter__panel newsletter__panel--active" data-panel="email">
                    <form class="newsletter__form" method="POST" action="api/newsletter-subscribe.php">
                        <div class="newsletter__form-group">
                            <input type="text" 
                                   name="name" 
                                   class="newsletter__input" 
                                   placeholder="<?= htmlspecialchars($namePlaceholder) ?>"
                                   required
                                   aria-label="Nome">
                        </div>
                        
                        <div class="newsletter__form-group">
                            <input type="email" 
                                   name="email" 
                                   class="newsletter__input" 
                                   placeholder="<?= htmlspecialchars($emailPlaceholder) ?>"
                                   required
                                   aria-label="Email">
                        </div>
                        
                        <div class="newsletter__form-group newsletter__form-group--checkbox">
                            <label class="newsletter__checkbox-label">
                                <input type="checkbox" 
                                       name="privacy" 
                                       class="newsletter__checkbox" 
                                       required
                                       aria-label="Privacy">
                                <span class="newsletter__checkbox-text">
                                    Accetto la
                                    <a href="<?= htmlspecialchars($privacyLink) ?>" 
                                       class="newsletter__privacy-link" 
                                       target="_blank"
                                       rel="noopener">
                                        Privacy Policy
                                    </a>
                                </span>
                            </label>
                        </div>
                        
                        <button type="submit" class="newsletter__button newsletter__button--email">
                            <span class="newsletter__button-text"><?= htmlspecialchars($emailButtonText) ?></span>
                            <i class="fas fa-arrow-right newsletter__button-icon"></i>
                        </button>
                        
                        <div class="newsletter__message newsletter__message--success" style="display: none;">
                            <i class="fas fa-check-circle newsletter__message-icon"></i>
                            <span class="newsletter__message-text">Iscrizione completata con successo!</span>
                        </div>
                        
                        <div class="newsletter__message newsletter__message--error" style="display: none;">
                            <i class="fas fa-exclamation-triangle newsletter__message-icon"></i>
                            <span class="newsletter__message-text">Errore durante l'iscrizione. Riprova.</span>
                        </div>
                    </form>
                </div>
                
                <!-- Panel WhatsApp Chat -->
                <div class="newsletter__panel" data-panel="whatsapp">
                    <div class="newsletter__whatsapp">
                        <div class="newsletter__form-group">
                            <input type="text" 
                                   id="whatsapp-name" 
                                   class="newsletter__input" 
                                   placeholder="<?= htmlspecialchars($whatsappNamePlaceholder) ?>"
                                   aria-label="Nome per WhatsApp">
                        </div>
                        
                        <a href="#"
                           class="newsletter__button newsletter__button--whatsapp"
                           data-whatsapp-number="<?= htmlspecialchars($whatsappNumber) ?>"
                           target="_blank"
                           rel="noopener"
                           aria-label="Apri WhatsApp per registrazione">
                            <i class="fab fa-whatsapp newsletter__button-icon"></i>
                            <span class="newsletter__button-text"><?= htmlspecialchars($whatsappButtonText) ?></span>
                        </a>
                    </div>
                </div>
                
                <!-- Panel Canale WhatsApp -->
                <div class="newsletter__panel" data-panel="channel">
                    <div class="newsletter__channel">
                        <a href="<?= htmlspecialchars($channelUrl) ?>" 
                           class="newsletter__button newsletter__button--channel"
                           target="_blank"
                           rel="noopener">
                            <i class="fab fa-whatsapp newsletter__button-icon"></i>
                            <span class="newsletter__button-text"><?= htmlspecialchars($channelButtonText) ?></span>
                        </a>
                    </div>
                </div>
                
                <!-- Panel Ingrid AI -->
                <div class="newsletter__panel" data-panel="ingrid">
                    <div class="newsletter__ingrid">
                        <a href="https://wa.me/<?= htmlspecialchars($ingridNumber) ?>?text=<?= urlencode($ingridWelcomeMessage) ?>" 
                           class="newsletter__button newsletter__button--ingrid"
                           target="_blank"
                           rel="noopener">
                            <i class="fab fa-whatsapp newsletter__button-icon"></i>
                            <span class="newsletter__button-text"><?= htmlspecialchars($ingridButtonText) ?></span>
                        </a>
                    </div>
                </div>
                
            </div>
            
        </div>
    </div>
</div>