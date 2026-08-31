<?php

if (! defined('ABSPATH')) {
  exit;
}

$eyebrow = trim((string) ($args['eyebrow'] ?? 'Наша система работы'));
$heading = trim((string) ($args['heading'] ?? 'Вы всегда понимаете, что происходит на вашем объекте'));
$intro = trim((string) ($args['intro'] ?? 'Мы выстроили процессы так, чтобы вы были уверены в каждом решении и на каждом этапе ремонта.'));
$image_alt = trim((string) ($args['image_alt'] ?? 'Рабочий чертёж планировки квартиры с размерами комнат и пометками прораба'));
$quote = trim((string) ($args['quote'] ?? 'Сложные процессы — простой для вас результат.'));
$quote_note = trim((string) ($args['quote_note'] ?? 'Мы берём на себя всё, что может пойти не так. Вы получаете спокойствие и уверенность.'));
$points = $args['points'] ?? array(
  array(
    'key' => 'estimate',
    'title' => 'Прозрачная смета',
    'text' => 'Фиксируем стоимость до начала работ. Никаких скрытых расходов и неожиданных доплат.',
  ),
  array(
    'key' => 'control',
    'title' => 'Контроль ремонта',
    'text' => 'Вы всегда знаете, что происходит на объекте. Фотоотчёты, планёрки, связь с прорабом.',
  ),
  array(
    'key' => 'contract',
    'title' => 'Договор и гарантия',
    'text' => "Фиксируем сроки и обязательства в договоре. Гарантия на все работы — 3\u{00A0}года.",
  ),
  array(
    'key' => 'payment',
    'title' => 'Поэтапная оплата',
    'text' => 'Оплачиваете только после принятия каждого этапа. Никаких авансов «вперёд».',
  ),
);

if (! $heading || ! $points) {
  return;
}

$point_keys = array('estimate', 'control', 'contract', 'payment');
?>
<section id="nexor-system" class="nexor-system-section" aria-labelledby="nexor-system-title">
  <div class="nexor-system__layout nexor-reveal">
    <div class="nexor-system__intro">
      <?php if ($eyebrow) : ?>
        <p class="nexor-system__eyebrow"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <h2 id="nexor-system-title" class="nexor-system__title"><?php echo esc_html($heading); ?></h2>
      <?php if ($intro) : ?>
        <p class="nexor-system__lead"><?php echo esc_html($intro); ?></p>
      <?php endif; ?>
    </div>

    <figure class="nexor-system__plan">
      <picture>
        <source type="image/webp" srcset="{{THEME_URI}}/assets/nexor-system-floor-plan.webp">
        <img
          class="nexor-system__plan-image"
          src="{{THEME_URI}}/assets/nexor-system-floor-plan.jpg"
          width="1481"
          height="966"
          alt="<?php echo esc_attr($image_alt); ?>"
          loading="lazy"
          decoding="async"
        />
      </picture>
    </figure>

    <ul class="nexor-system__points">
      <?php
      foreach ($points as $index => $point) :
        $title = trim((string) ($point['title'] ?? ''));
        $text = trim((string) ($point['text'] ?? ''));
        if (! $title || ! $text) {
          continue;
        }
        $key = (string) ($point['key'] ?? '');
        if (! in_array($key, $point_keys, true)) {
          $key = $point_keys[$index] ?? 'estimate';
        }
        ?>
        <li class="nexor-system__point nexor-system__point--<?php echo esc_attr($key); ?>">
          <h3 class="nexor-system__point-title"><?php echo esc_html($title); ?></h3>
          <p class="nexor-system__point-text"><?php echo esc_html($text); ?></p>
        </li>
      <?php endforeach; ?>
    </ul>

    <figure class="nexor-system__quote">
      <blockquote class="nexor-system__quote-body">
        <p><?php echo esc_html($quote); ?></p>
      </blockquote>
      <figcaption class="nexor-system__quote-note"><?php echo esc_html($quote_note); ?></figcaption>
    </figure>
  </div>
</section>
