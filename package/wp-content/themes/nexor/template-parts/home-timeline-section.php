<?php

if (! defined('ABSPATH')) {
  exit;
}

$heading = trim((string) ($args['heading'] ?? ''));
$disclaimer = trim((string) ($args['disclaimer'] ?? ''));
$rows = $args['rows'] ?? array();

if (! $rows || ! $heading || ! $disclaimer) {
  return;
}

$columns = array(
  'new-build' => array('key' => 'new_build', 'label' => 'Новостройка', 'filter' => 'Новостройка'),
  'capital' => array('key' => 'capital', 'label' => 'Капитальный ремонт', 'filter' => 'Капитальный'),
  'designer' => array('key' => 'designer', 'label' => 'Дизайнерский ремонт', 'filter' => 'Дизайнерский'),
);
$default_mode = 'new-build';
$note_paragraphs = preg_split('/\R\s*\R/', $disclaimer);
if (1 === count($note_paragraphs) && str_contains($note_paragraphs[0], '. ')) {
  $note_paragraphs = preg_split('/(?<=\.)\s+/', trim($note_paragraphs[0]), 2);
}
?>
<section id="repair-timeline" class="nexor-timeline-section nexor-reveal" data-timeline-active="<?php echo esc_attr($default_mode); ?>">
  <div class="container-nexor">
    <div class="nexor-timeline__layout">
      <header class="nexor-section-heading">
        <h2 class="heading-section"><?php echo esc_html($heading); ?></h2>
      </header>
      <div class="nexor-timeline__filters" aria-label="Показать сроки по типу ремонта">
        <?php foreach ($columns as $mode => $column) : ?>
          <button type="button" data-timeline-mode="<?php echo esc_attr($mode); ?>" aria-pressed="<?php echo $mode === $default_mode ? 'true' : 'false'; ?>"><?php echo esc_html($column['filter']); ?></button>
        <?php endforeach; ?>
      </div>
      <div class="nexor-timeline__wrap">
        <table class="nexor-timeline" aria-describedby="repair-timeline-note">
          <thead>
            <tr>
              <th scope="col">Площадь объекта</th>
              <?php foreach ($columns as $mode => $column) : ?>
                <th scope="col" data-timeline-column="<?php echo esc_attr($mode); ?>"><?php echo esc_html($column['label']); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row) : ?>
              <tr>
                <th scope="row"><?php echo esc_html($row['area'] ?? ''); ?></th>
                <?php foreach ($columns as $mode => $column) : ?>
                  <td data-timeline-column="<?php echo esc_attr($mode); ?>" data-label="<?php echo esc_attr($column['label']); ?>"><?php echo esc_html($row[$column['key']] ?? ''); ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div id="repair-timeline-note" class="nexor-timeline__note">
        <?php foreach ($note_paragraphs as $paragraph) : ?>
          <p><?php echo nl2br(esc_html(trim($paragraph))); ?></p>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>