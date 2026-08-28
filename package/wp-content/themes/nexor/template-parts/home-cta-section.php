<?php

if (! defined('ABSPATH')) {
  exit;
}

$heading = trim((string) ($args['heading'] ?? 'Запишитесь на профессиональный замер с инженером Nexor'));
$lead = trim((string) ($args['lead'] ?? 'Инженер изучит ваш объект, ответит на вопросы и соберёт всё необходимое для точного расчёта сметы и планирования ремонта.'));
$button_label = trim((string) ($args['button_label'] ?? 'Записаться на замер'));
$note = trim((string) ($args['note'] ?? 'Консультация и выезд инженера бесплатны и ни к чему не обязывают.'));
$phone_display = trim((string) ($args['phone_display'] ?? '+7 (926) 083-23-24'));
$phone_link = trim((string) ($args['phone_link'] ?? '+79260832324'));
$phone_href = 'tel:' . preg_replace('/[^\d+]/', '', $phone_link);

if (! $heading) {
  return;
}

$button_class = 'inline-flex items-center justify-center gap-2 whitespace-nowrap ring-offset-background transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-primary text-primary-foreground hover:bg-terracotta-dark rounded-[10px] h-12 px-7 text-base font-medium';
$phone_class = 'inline-flex items-center justify-center gap-2 whitespace-nowrap ring-offset-background transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border hover:text-accent-foreground rounded-[10px] h-12 px-7 text-base border-white/25 text-white bg-white/5 hover:bg-white/10 hover:border-white/40 font-medium';
$heading_html = str_replace('Nexor', '<span class="cta__brand">Nexor</span>', esc_html($heading));
?>
<section class="cta py-16 md:py-32 flex flex-col">
  <div class="container-nexor flex-1 flex flex-col">
    <div class="max-w-6xl mx-auto text-center flex-1 flex flex-col items-center justify-center">
      <h2 class="cta__title text-white mb-10"><?php echo $heading_html; ?></h2>
      <?php if ($lead) : ?>
        <p class="text-xl text-white/70 mb-10 max-w-2xl mx-auto"><?php echo esc_html($lead); ?></p>
      <?php endif; ?>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <button type="button" class="<?php echo esc_attr($button_class); ?>">
          <?php echo esc_html($button_label); ?>
        </button>
        <?php if ($phone_display && $phone_href !== 'tel:') : ?>
          <a href="<?php echo esc_url($phone_href); ?>" class="<?php echo esc_attr($phone_class); ?>" aria-label="<?php echo esc_attr($phone_display); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone mr-2 h-5 w-5" aria-hidden="true">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
            Позвонить
          </a>
        <?php endif; ?>
      </div>
      <?php if ($note) : ?>
        <p class="cta__note mt-12 text-lg text-white/60"><?php echo esc_html($note); ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>