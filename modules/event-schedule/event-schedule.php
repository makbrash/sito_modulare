<?php
/**
 * Event Schedule Module
 * Timeline degli eventi principali della manifestazione
 */

$moduleData = $renderer->getModuleData('event-schedule', $config);

$eyebrow = $moduleData['eyebrow'] ?? '';
$title = $moduleData['title'] ?? '';
$subtitle = $moduleData['subtitle'] ?? '';
$days = is_array($moduleData['days'] ?? null) ? $moduleData['days'] : [];
$cta = is_array($moduleData['cta'] ?? null) ? $moduleData['cta'] : null;
?>

<section class="event-schedule">
    <div class="site-container">
        <header class="section-header">
            <?php if (!empty($eyebrow)): ?>
                <span class="section-eyebrow"><?= htmlspecialchars($eyebrow) ?></span>
            <?php endif; ?>

            <?php if (!empty($title)): ?>
                <h2 class="section-title"><?= htmlspecialchars($title) ?></h2>
            <?php endif; ?>

            <?php if (!empty($subtitle)): ?>
                <p class="section-subtitle"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </header>

        <?php if (!empty($days)): ?>
            <div class="schedule-grid">
                <?php foreach ($days as $day):
                    if (!is_array($day)) { continue; }
                    $dayLabel = $day['label'] ?? '';
                    $dayDate = $day['date'] ?? '';
                    $events = is_array($day['events'] ?? null) ? $day['events'] : [];
                    if (!$dayLabel && !$dayDate && empty($events)) { continue; }
                ?>
                    <article class="schedule-day">
                        <div class="schedule-day__header">
                            <?php if (!empty($dayLabel)): ?>
                                <span class="schedule-day__label"><?= htmlspecialchars($dayLabel) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($dayDate)): ?>
                                <span class="schedule-day__date"><?= htmlspecialchars($dayDate) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($events)): ?>
                            <ul class="schedule-events">
                                <?php foreach ($events as $event):
                                    if (!is_array($event)) { continue; }
                                    $time = $event['time'] ?? '';
                                    $eventTitle = $event['title'] ?? '';
                                    $location = $event['location'] ?? '';
                                    $description = $event['description'] ?? '';
                                    if (!$time && !$eventTitle) { continue; }
                                ?>
                                    <li class="schedule-event">
                                        <div class="schedule-event__time">
                                            <?php if (!empty($time)): ?>
                                                <span><?= htmlspecialchars($time) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="schedule-event__content">
                                            <?php if (!empty($eventTitle)): ?>
                                                <h3><?= htmlspecialchars($eventTitle) ?></h3>
                                            <?php endif; ?>
                                            <?php if (!empty($location)): ?>
                                                <span class="schedule-event__location">
                                                    <i class="fas fa-location-dot" aria-hidden="true"></i>
                                                    <?= htmlspecialchars($location) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($description)): ?>
                                                <p><?= htmlspecialchars($description) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($cta)): ?>
            <div class="schedule-cta">
                <?php
                if (!empty($cta['module']) && is_array($cta['module'])) {
                    $moduleName = $cta['module']['name'] ?? null;
                    $moduleConfig = $cta['module']['config'] ?? [];
                    if ($moduleName) {
                        echo $renderer->renderModule($moduleName, $moduleConfig);
                    }
                } else {
                    $buttonConfig = array_merge([
                        'text' => 'Scopri tutti i dettagli',
                        'variant' => 'primary',
                        'size' => 'large'
                    ], $cta);
                    echo $renderer->renderModule('button', $buttonConfig);
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>
