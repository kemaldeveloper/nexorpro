<?php

if (! defined('ABSPATH')) {
  exit;
}

$eyebrow = trim((string) ($args['eyebrow'] ?? 'О компании'));
$heading = trim((string) ($args['heading'] ?? 'Nexor — <br/> не бригада.'));
$sub = trim((string) ($args['sub'] ?? 'Системная компания по ремонту квартир и домов в Москве.'));
$points = $args['points'] ?? array(
  array(
    'title' => 'Проблема не в ремонте, а в хаосе без системы.',
    'text' => 'Когда нет системы, смета плывёт, сроки срываются, а ответственность растворяется между бригадами и посредниками. Ремонт превращается в постоянный стресс для владельца.',
  ),
  array(
    'title' => 'В Nexor процесс выстроен иначе.',
    'text' => 'Работаем без посредников: в штате свои мастера, прорабы и технический контроль. Более 8 лет выполняем капитальный и дизайнерский ремонт квартир и домов в Москве и области.',
  ),
  array(
    'title' => 'Для клиента это понятный процесс без постоянного контроля.',
    'text' => 'Каждый этап регламентирован, сроки и бюджет фиксируются в договоре, ход работ прозрачен. Вы получаете управляемый процесс без скрытых платежей и необходимости всё контролировать самостоятельно.',
  ),
);
$stats = $args['stats'] ?? array(
  array('value' => '340+', 'label' => 'объектов сдано', 'note' => 'квартиры и дома под ключ'),
  array('value' => '8 лет', 'label' => 'на рынке', 'note' => 'опыт, проверенный годами'),
  array('value' => '40+', 'label' => 'специалистов в штате', 'note' => 'мастера, прорабы, инженеры и дизайнеры'),
  array('value' => '98%', 'label' => 'рекомендуют нас', 'note' => 'по отзывам наших клиентов'),
);

if (! $heading || ! $points || ! $stats) {
  return;
}
?>
<section id="about-company-nexor" class="nexor-about-section" aria-labelledby="nexor-about-heading">
  <div class="container-nexor">
    <div class="nexor-about__layout nexor-reveal">
      <div class="nexor-about__header">
        <?php if ($eyebrow) : ?>
          <p class="nexor-about__eyebrow"><?php echo esc_html($eyebrow); ?></p>
        <?php endif; ?>
        <h2 id="nexor-about-heading" class="title mb-7"><?php echo wp_kses_post($heading); ?></h2>
        <?php if ($sub) : ?>
          <p class="nexor-about__sub"><?php echo esc_html($sub); ?></p>
        <?php endif; ?>
      </div>
      <ul class="nexor-about__points">
        <?php foreach ($points as $point) : ?>
          <?php
          $title = trim((string) ($point['title'] ?? ''));
          $text = trim((string) ($point['text'] ?? ''));
          if (! $title || ! $text) {
            continue;
          }
          ?>
          <li class="nexor-about__point">
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php echo esc_html($text); ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
      <ul class="nexor-about__stats" aria-label="Показатели компании">
        <?php foreach ($stats as $stat) : ?>
          <?php
          $value = trim((string) ($stat['value'] ?? ''));
          $label = trim((string) ($stat['label'] ?? ''));
          $note = trim((string) ($stat['note'] ?? ''));
          if (! $value || ! $label) {
            continue;
          }
          ?>
          <li class="nexor-about__stat flex flex-col items-start">
            <span class="nexor-about__stat-value flex flex-col gap-3 mb-5"><?php echo esc_html($value); ?></span>
            <span class="nexor-about__stat-label mb-4 font-normal"><?php echo esc_html($label); ?></span>
            <?php if ($note) : ?>
              <span class="nexor-about__stat-note font-normal"><?php echo esc_html($note); ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>