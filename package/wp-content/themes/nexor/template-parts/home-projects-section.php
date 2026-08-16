<?php

if (! defined('ABSPATH')) {
  exit;
}

$cards = $args['cards'] ?? array();
$heading = $args['heading'] ?? 'Реализованные проекты';
$intro = $args['intro'] ?? 'Показываем реальные объекты с понятными сроками, бюджетами и результатом.';
$cta_url = $args['cta_url'] ?? home_url('/projects/');
$cta_label = $args['cta_label'] ?? 'Все проекты';

if (! $cards) {
  return;
}
?>
<section id="cases" class="nexor-projects-section">
  <div class="container-nexor">
    <div class="nexor-projects-section__header nexor-reveal">
      <div class="nexor-section-heading">
        <h2 class="heading-section"><?php echo esc_html($heading); ?></h2>
        <p class="nexor-projects-section__intro"><?php echo esc_html($intro); ?></p>
      </div>
      <a class="nexor-projects-section__cta" href="<?php echo esc_url($cta_url); ?>">
        <?php echo esc_html($cta_label); ?>
        <span class="nexor-projects-section__cta-arrow" aria-hidden="true"></span>
      </a>
    </div>
    <div class="nexor-projects-editorial">
      <?php foreach ($cards as $index => $card) : ?>
        <article class="nexor-project-card<?php echo 0 === $index ? ' nexor-project-card--featured' : ''; ?> nexor-reveal">
          <a href="<?php echo esc_url($card['url']); ?>">
            <span class="nexor-project-card__media">
              <img
                <?php if (! empty($card['image_id'])) : ?>
                data-attachment-id="<?php echo (int) $card['image_id']; ?>"
                <?php endif; ?>
                src="<?php echo esc_url($card['image']); ?>"
                alt="<?php echo esc_attr($card['alt']); ?>"
                loading="lazy"
                width="900"
                height="700"
                style="object-position: <?php echo esc_attr($card['focal_point'] ?? 'center'); ?>" />
            </span>
            <span class="nexor-project-card__icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"></path>
                <path d="m12 5 7 7-7 7"></path>
              </svg>
            </span>
            <span class="nexor-project-card__body">
              <?php if (! empty($card['badge'])) : ?>
                <span class="nexor-project-card__badge"><?php echo esc_html($card['badge']); ?></span>
              <?php endif; ?>
              <h3><?php echo esc_html($card['title']); ?></h3>
              <?php if (! empty($card['meta'])) : ?>
                <p class="nexor-project-card__meta"><?php echo esc_html($card['meta']); ?></p>
              <?php endif; ?>
              <?php if (0 === $index) : ?>
                <span class="nexor-project-card__link">
                  Смотреть проект
                  <span class="nexor-project-card__arrow" aria-hidden="true"></span>
                </span>
              <?php endif; ?>
            </span>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>