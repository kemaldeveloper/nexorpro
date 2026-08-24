<?php

if (! defined('ABSPATH')) {
  exit;
}

$heading = trim((string) ($args['heading'] ?? 'Ремонт квартир и домов под ключ'));
$sub = trim((string) ($args['sub'] ?? 'в Москве и Московской области'));
$eyebrow = trim((string) ($args['eyebrow'] ?? 'Работаем по фиксированной смете'));
$lead = trim((string) ($args['lead'] ?? 'Фиксируем стоимость в договоре, заранее обозначаем честный диапазон бюджета и берём на себя весь процесс — от подготовки до сдачи объекта.'));
$calculate_label = trim((string) ($args['calculate_label'] ?? 'Рассчитать стоимость'));
$calculate_url = trim((string) ($args['calculate_url'] ?? '#calculator'));
$projects_label = trim((string) ($args['projects_label'] ?? 'Реализованные проекты'));
$projects_url = trim((string) ($args['projects_url'] ?? ''));
$promo = (string) ($args['promo'] ?? '');
$features = $args['features'] ?? array(
  array('num' => '01', 'title' => 'Фиксированная смета', 'text' => 'Без скрытых работ'),
  array('num' => '02', 'title' => 'Поэтапная оплата', 'text' => 'Платите за результат'),
  array('num' => '03', 'title' => 'Гарантия 3 года', 'text' => 'На выполненные работы'),
);

if (! $projects_url) {
  $projects_url = function_exists('home_url') ? home_url('/projects/') : '/projects/';
}

if (! $heading) {
  return;
}

$cta_class = 'inline-flex items-center justify-center gap-2 whitespace-nowrap ring-offset-background transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-primary text-primary-foreground hover:bg-terracotta-dark rounded-[10px] h-12 px-7 text-base font-medium';
?>
<section class="nexor-home-hero">
  <div class="container-nexor">
    <div class="nexor-home-hero__layout">
      <?php if ($promo) : ?>
        <?php echo $promo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Nexor_Enhancements::hero_promotion() ?>
      <?php endif; ?>
      <div class="nexor-home-hero__main">
        <div class="nexor-home-hero__copy">
          <h1 class="heading-hero text-white"><?php echo esc_html($heading); ?></h1>
          <?php if ($sub) : ?>
            <p class="nexor-home-hero__sub"><?php echo esc_html($sub); ?></p>
          <?php endif; ?>
        </div>
        <div class="nexor-home-hero__aside">
          <?php if ($eyebrow) : ?>
            <p class="nexor-home-hero__eyebrow"><?php echo esc_html($eyebrow); ?></p>
          <?php endif; ?>
          <?php if ($lead) : ?>
            <p class="nexor-home-hero__lead"><?php echo esc_html($lead); ?></p>
          <?php endif; ?>
          <div class="nexor-home-hero__actions">
            <a href="<?php echo esc_url($calculate_url); ?>" class="<?php echo esc_attr($cta_class); ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calculator mr-2.5 h-5 w-5" aria-hidden="true">
                <rect width="16" height="20" x="4" y="2" rx="2"></rect>
                <line x1="8" x2="16" y1="6" y2="6"></line>
                <line x1="16" x2="16" y1="14" y2="18"></line>
                <path d="M16 10h.01"></path>
                <path d="M12 10h.01"></path>
                <path d="M8 10h.01"></path>
                <path d="M12 14h.01"></path>
                <path d="M8 14h.01"></path>
                <path d="M12 18h.01"></path>
                <path d="M8 18h.01"></path>
              </svg>
              <?php echo esc_html($calculate_label); ?>
            </a>
            <a href="<?php echo esc_url($projects_url); ?>" class="group inline-flex items-center justify-center gap-2 font-medium text-base">
              <?php echo esc_html($projects_label); ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-4 h-4 group-hover:translate-x-1 transition-transform duration-200" aria-hidden="true">
                <path d="M5 12h14"></path>
                <path d="m12 5 7 7-7 7"></path>
              </svg>
            </a>
          </div>
        </div>
      </div>
      <?php if ($features) : ?>
        <div class="nexor-home-hero__features" aria-label="Преимущества Nexor">
          <?php foreach ($features as $feature) : ?>
            <?php
            $num = trim((string) ($feature['num'] ?? ''));
            $title = trim((string) ($feature['title'] ?? ''));
            $text = trim((string) ($feature['text'] ?? ''));
            if (! $num || ! $title || ! $text) {
              continue;
            }
            ?>
            <div class="nexor-home-hero__feature">
              <span class="nexor-home-hero__num" aria-hidden="true"><?php echo esc_html($num); ?></span>
              <div>
                <strong><?php echo esc_html($title); ?></strong>
                <span><?php echo esc_html($text); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
