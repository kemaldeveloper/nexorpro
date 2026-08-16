<?php

if (! defined('ABSPATH')) {
  exit;
}

$cards = $args['cards'] ?? array();
$eyebrow = $args['eyebrow'] ?? 'Направления работы';
$heading = $args['heading'] ?? 'Основные услуги';

if (! $cards) {
  return;
}
?>
<section id="main-services" class="nexor-services-section">
  <div class="container-nexor">
    <div class="nexor-section-heading nexor-reveal">
      <h2 class="heading-section"><?php echo esc_html($heading); ?></h2>
      <p class="nexor-services-section__intro"><?php echo esc_html($eyebrow); ?></p>
    </div>
    <div class="nexor-services-editorial">
      <?php foreach ($cards as $card) : ?>
        <!-- nexor-reveal -->
        <!-- --service-index:<?php echo (int) $card['index']; ?> -->
        <article class="nexor-service-card " style="">
          <a href="<?php echo esc_url($card['url']); ?>">
            <span class="nexor-service-card__media">
              <img src="<?php echo esc_url($card['image']); ?>" alt="<?php echo esc_attr($card['title']); ?>" loading="lazy" width="900" height="700">
            </span>
            <span class="nexor-service-card__body">
              <h3><?php echo esc_html($card['title']); ?></h3>
              <span class="nexor-service-card__link">
                Подробнее <span class="nexor-service-card__arrow" aria-hidden="true"></span>
              </span>
            </span>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>