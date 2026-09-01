<?php

if (! defined('ABSPATH')) {
  exit;
}

$heading = trim((string) ($args['heading'] ?? ''));
$intro = trim((string) ($args['intro'] ?? ''));
$rows = $args['rows'] ?? array();

if (! $heading || ! $intro || ! $rows) {
  return;
}

// The heading renders as two lines, the second one accented. An editor can
// force the break with a comma or dash; without one it falls before the first
// preposition, where Russian marketing copy wraps naturally («Управляем
// ремонтом» / «от идеи до вашего комфорта»). Copy with neither degrades to a
// single plain line.
$heading_lead = $heading;
$heading_accent = '';
$heading_parts = preg_split('/\s*[,—–-]\s+/u', $heading, 2);
if (isset($heading_parts[1]) && '' !== trim($heading_parts[1])) {
  $heading_lead = trim((string) $heading_parts[0]);
  $heading_accent = trim($heading_parts[1]);
} else {
  $heading_words = preg_split('/\s+/u', $heading) ?: array();
  $heading_breaks = array('без', 'в', 'во', 'для', 'до', 'за', 'и', 'из', 'к', 'на', 'от', 'по', 'под', 'при', 'с', 'со', 'у', 'через');
  foreach ($heading_words as $index => $word) {
    if ($index > 0 && in_array(mb_strtolower($word), $heading_breaks, true)) {
      $heading_lead = implode(' ', array_slice($heading_words, 0, $index));
      $heading_accent = implode(' ', array_slice($heading_words, $index));
      break;
    }
  }
}
?>
<section id="additional-services" class="nexor-additional-section">
  <div class="nexor-additional-scene nexor-reveal">
    <div class="nexor-additional__copy">
      <h2 class="heading-section">
        <?php echo esc_html($heading_lead); ?>
        <?php if ($heading_accent) : ?>
          <em><?php echo esc_html($heading_accent); ?></em>
        <?php endif; ?>
      </h2>
      <p class="nexor-additional__intro"><?php echo esc_html($intro); ?></p>
    </div>

    <div class="nexor-additional-scene__stage">
      <?php
      foreach ($rows as $index => $row) :
        $id = (string) ($row['id'] ?? '');
        $title = trim((string) ($row['title'] ?? ''));
        if (! $id || ! $title) {
          continue;
        }
        ?>
        <button
          type="button"
          class="nexor-additional-pin"
          data-service-panel="nexor-service-panel-<?php echo esc_attr($id); ?>"
          aria-haspopup="dialog"
        >
          <span class="nexor-additional-pin__marker"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span>
          <span class="nexor-additional-pin__label"><?php echo esc_html($title); ?></span>
        </button>
      <?php endforeach; ?>
    </div>

    <p class="nexor-additional-scene__hint">Нажмите на метку, чтобы узнать подробнее</p>
  </div>

  <?php
  // Details for each pin live in an inert <template>; JS clones the matching
  // one into a shared modal on click, so the markup here is never rendered
  // as-is on the page.
  foreach ($rows as $index => $row) :
    $id = (string) ($row['id'] ?? '');
    $title = trim((string) ($row['title'] ?? ''));
    if (! $id || ! $title) {
      continue;
    }
    $subtitle = (string) ($row['subtitle'] ?? '');
    $description = (string) ($row['description'] ?? '');
    $benefit = (string) ($row['benefit'] ?? '');
    $items = $row['items'] ?? array();
    ?>
    <template id="nexor-service-panel-<?php echo esc_attr($id); ?>">
      <span class="nexor-service-modal__number"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span>
      <h3 class="nexor-service-modal__title"><?php echo esc_html($title); ?></h3>
      <?php if ($subtitle) : ?>
        <p class="nexor-service-modal__subtitle"><?php echo esc_html($subtitle); ?></p>
      <?php endif; ?>
      <?php if ($description) : ?>
        <p class="nexor-service-modal__description"><?php echo esc_html($description); ?></p>
      <?php endif; ?>
      <?php if ($items) : ?>
        <h4 class="nexor-service-modal__items-label">Что входит:</h4>
        <ul class="nexor-service-modal__items">
          <?php foreach ($items as $item) : ?>
            <li><?php echo esc_html($item); ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?php if ($benefit) : ?>
        <p class="nexor-service-modal__benefit"><?php echo esc_html($benefit); ?></p>
      <?php endif; ?>
      <button
        type="button"
        class="nexor-service-modal__cta"
        data-nexor-context-type="additional"
        data-nexor-context-id="<?php echo esc_attr($id); ?>"
      >Обсудить задачу</button>
    </template>
  <?php endforeach; ?>
</section>
