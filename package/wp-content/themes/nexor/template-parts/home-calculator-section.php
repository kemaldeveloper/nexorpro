<?php

if (! defined('ABSPATH')) {
  exit;
}

$eyebrow = $args['eyebrow'] ?? 'Расчёт стоимости';
$heading = $args['heading'] ?? 'Рассчитайте ориентировочную стоимость ремонта';
$intro = $args['intro'] ?? 'Ответьте на 7 коротких вопросов и получите ориентир по бюджету.';
$cta_label = $args['cta_label'] ?? 'Рассчитать стоимость';
?>
<section id="calculator" class="section-padding bg-background" aria-labelledby="nexor-calculator-heading">
  <div class="container-nexor">
    <div class="nexor-calculator">
      <p class="text-sm text-muted-foreground mb-3"><?php echo esc_html($eyebrow); ?></p>
      <h2 id="nexor-calculator-heading" class="heading-section mb-5"><?php echo esc_html($heading); ?></h2>
      <p class="text-body text-muted-foreground mb-7"><?php echo esc_html($intro); ?></p>
      <button type="button" class="nexor-calculator__button" data-next><?php echo esc_html($cta_label); ?></button>
    </div>
  </div>
</section>