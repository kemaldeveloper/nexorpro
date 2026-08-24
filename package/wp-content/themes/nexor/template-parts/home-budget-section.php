<?php

if (! defined('ABSPATH')) {
  exit;
}

$heading = $args['heading'] ?? '';
$metric = trim((string) ($args['metric'] ?? ''));
$metric_label = $args['metric_label'] ?? '';
$metric_note = $args['metric_note'] ?? '';
$rows = $args['rows'] ?? array();

if (! $rows || ! trim($heading) || ! $metric || ! trim($metric_label)) {
  return;
}

$metric_value = $metric;
$metric_suffix = '';
if (preg_match('/^(.+?)([%‰]+)$/', $metric, $matches)) {
  $metric_value = $matches[1];
  $metric_suffix = $matches[2];
}
?>
<section id="budget-control" class="nexor-budget-section">
  <div class="container-nexor">
    <div class="nexor-budget__layout nexor-reveal">
      <div class="nexor-budget__metric flex-col">
        <div class="nexor-budget__figure" aria-hidden="true">
          <span class="nexor-budget__value"><?php echo esc_html($metric_value); ?></span>
          <?php if ('' !== $metric_suffix) : ?>
            <span class="nexor-budget__suffix"><?php echo esc_html($metric_suffix); ?></span>
          <?php endif; ?>
        </div>
        <div class="flex flex-col gap-6">
          <p class="nexor-budget__stat-label"><?php echo esc_html($metric_label); ?></p>
          <?php if (trim($metric_note)) : ?>
            <p class="nexor-budget__stat-note"><?php echo esc_html($metric_note); ?></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="nexor-budget__content">
        <h2 class="nexor-budget__heading"><?php echo esc_html($heading); ?></h2>
        <ol class="nexor-budget__list">
          <?php foreach ($rows as $row) : ?>
            <li class="nexor-budget__item">
              <span class="nexor-budget__index" aria-hidden="true"><?php echo esc_html(sprintf('%02d', (int) ($row['index'] ?? 0))); ?></span>
              <div class="nexor-budget__item-copy">
                <h3><?php echo esc_html($row['title'] ?? ''); ?></h3>
                <p><?php echo esc_html($row['description'] ?? ''); ?></p>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </div>
</section>