<?php
/**
 * Results Table Module
 * Tabella risultati gara modulare
 */

$moduleData = $renderer->getModuleData('results', $config);
$showCategories = $config['show_categories'] ?? true;
$sortable = $config['sortable'] ?? true;
?>

<section class="results-table">
    <div class="results-container">
        <div class="results-header">
            <h2 class="results-title">Risultati Gara</h2>
            <?php if ($showCategories): ?>
                <div class="results-filters">
                    <?php
                    // Composizione: includi il modulo select se disponibile
                    echo $renderer->renderModule('select', [
                        'id' => 'category-filter',
                        'name' => 'category',
                        'placeholder' => 'Tutte le categorie',
                        'options' => [
                            ['value' => '', 'label' => 'Tutte le categorie'],
                            ['value' => 'M', 'label' => 'Maschile'],
                            ['value' => 'F', 'label' => 'Femminile'],
                            ['value' => 'M40', 'label' => 'M40'],
                            ['value' => 'F40', 'label' => 'F40']
                        ],
                        'value' => ''
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="table-wrapper">
            <table class="results-table-content" id="results-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="position">Pos.</th>
                        <th class="sortable" data-sort="bib_number">Pettorale</th>
                        <th class="sortable" data-sort="runner_name">Nome</th>
                        <?php if ($showCategories): ?>
                            <th class="sortable" data-sort="category">Categoria</th>
                        <?php endif; ?>
                        <th class="sortable" data-sort="time_result">Tempo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($moduleData as $result): ?>
                        <tr data-category="<?= htmlspecialchars($result['category']) ?>">
                            <td class="position" data-sort="position"><?= $result['position'] ?></td>
                            <td class="bib-number" data-sort="bib_number"><?= htmlspecialchars($result['bib_number']) ?></td>
                            <td class="runner-name" data-sort="runner_name"><?= htmlspecialchars($result['runner_name']) ?></td>
                            <?php if ($showCategories): ?>
                                <td class="category" data-sort="category"><?= htmlspecialchars($result['category']) ?></td>
                            <?php endif; ?>
                            <td class="time-result" data-sort="time_result"><?= $result['time_result'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="results-footer">
            <p class="results-count">Visualizzati <?= count($moduleData) ?> risultati</p>
        </div>
    </div>
</section>
