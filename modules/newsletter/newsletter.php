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
$ingridWelcomeMessage = $config['ingrid_welcome_message'] ?? 'Ciao! Sono Ingrid, come posso aiutarti?';

// Background
$backgroundImage = $config['background_image'] ?? '';
$backgroundColor = $config['background_color'] ?? '';

// Sanitizzazione
$title = htmlspecialchars($title);
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
                <button class="newsletter__tab newsletter__tab--active" data-tab="email" type="button">
                    <i class="fas fa-envelope newsletter__tab-icon"></i>
                    <span class="newsletter__tab-text">Email</span>
                </button>
                
                <button class="newsletter__tab" data-tab="whatsapp" type="button">
                    <i class="fab fa-whatsapp newsletter__tab-icon"></i>
                    <span class="newsletter__tab-text">WhatsApp</span>
                </button>
                
                <button class="newsletter__tab" data-tab="channel" type="button">
                    <i class="fas fa-broadcast-tower newsletter__tab-icon"></i>
                    <span class="newsletter__tab-text">Canale</span>
                </button>
                
                <button class="newsletter__tab" data-tab="ingrid" type="button">
                    <i class="fas fa-robot newsletter__tab-icon"></i>
                    <span class="newsletter__tab-text">Ingrid AI</span>
                </button>
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
                        <p class="newsletter__description">Inviaci un messaggio WhatsApp per registrarti</p>
                        
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
                        
                        <p class="newsletter__note">Messaggio: "Ciao sono [Nome], vorrei registrarmi"</p>
                    </div>
                </div>
                
                <!-- Panel Canale WhatsApp -->
                <div class="newsletter__panel" data-panel="channel">
                    <div class="newsletter__channel">
                        <p class="newsletter__description">Segui il nostro canale ufficiale per aggiornamenti in tempo reale</p>
                        
                        <a href="<?= htmlspecialchars($channelUrl) ?>" 
                           class="newsletter__button newsletter__button--channel"
                           target="_blank"
                           rel="noopener">
                            <i class="fab fa-whatsapp newsletter__button-icon"></i>
                            <span class="newsletter__button-text"><?= htmlspecialchars($channelButtonText) ?></span>
                        </a>
                        
                        <div class="newsletter__features">
                            <div class="newsletter__feature">
                                <i class="fas fa-clock newsletter__feature-icon"></i>
                                <span class="newsletter__feature-text">Aggiornamenti in tempo reale</span>
                            </div>
                            <div class="newsletter__feature">
                                <i class="fas fa-star newsletter__feature-icon"></i>
                                <span class="newsletter__feature-text">Notizie esclusive</span>
                            </div>
                        </div>
                        
                        <p class="newsletter__note">Unisciti agli oltre <?= htmlspecialchars($channelMembers) ?> membri del nostro canale</p>
                    </div>
                </div>
                
                <!-- Panel Ingrid AI -->
                <div class="newsletter__panel" data-panel="ingrid">
                    <div class="newsletter__ingrid">
                        <div class="newsletter__ingrid-avatar">
                            <i class="fas fa-robot newsletter__ingrid-avatar-icon"></i>
                        </div>
                        
                        <h3 class="newsletter__ingrid-title">Chatta con Ingrid</h3>
                        <p class="newsletter__description">Chiedi qualsiasi cosa ad Ingrid, la nostra assistente AI su WhatsApp</p>
                        
                        <a href="https://wa.me/<?= htmlspecialchars($ingridNumber) ?>?text=<?= urlencode($ingridWelcomeMessage) ?>" 
                           class="newsletter__button newsletter__button--ingrid"
                           target="_blank"
                           rel="noopener">
                            <i class="fab fa-whatsapp newsletter__button-icon"></i>
                            <span class="newsletter__button-text"><?= htmlspecialchars($ingridButtonText) ?></span>
                        </a>
                        
                        <div class="newsletter__ingrid-features">
                            <span class="newsletter__ingrid-tag">
                                <i class="fas fa-robot"></i> Assistenza 24/7
                            </span>
                            <span class="newsletter__ingrid-tag">
                                <i class="fas fa-comments"></i> Risposte Immediate
                            </span>
                            <span class="newsletter__ingrid-tag">
                                <i class="fas fa-bullseye"></i> Sempre Disponibile
                            </span>
                        </div>
                    </div>
                </div>
                
            </div>
            
        </div>
    </div>
</div>