<?php

if (! defined('ABSPATH')) {
  exit;
}

$heading = trim((string) ($args['heading'] ?? 'Бонусы, которые делают ремонт выгоднее'));
$disclaimer = trim((string) ($args['disclaimer'] ?? ''));
$deadline_label = trim((string) ($args['deadline_label'] ?? ''));
$featured = $args['featured'] ?? array();
$cards = $args['cards'] ?? array();

if (! $cards && ! $featured) {
  return;
}

$heading_words = preg_split('/\s+/u', $heading) ?: array();
$heading_last = array_pop($heading_words);
$heading_lead = implode(' ', $heading_words);
$arrow_icon = static function (int $size): string {
  return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path fill-rule="evenodd" d="M4 8a.5.5 0 0 1 .5-.5h5.793L8.146 5.354a.5.5 0 1 1 .708-.708l3 3a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708-.708L10.293 8.5H4.5A.5.5 0 0 1 4 8"/></svg>';
};
$more_icon = $arrow_icon(24);
$cta_icon = $arrow_icon(16);
?>
<section id="promotions" class="nexor-promotions-section">
  <div class="container-nexor">
    <div class="nexor-promotions__layout">
      <div class="nexor-promotions__header nexor-reveal">
        <?php if ($deadline_label) : ?>
          <p class="nexor-promotions__deadline"><?php echo esc_html($deadline_label); ?></p>
        <?php endif; ?>
        <h2 class="heading-section">
          <?php if ($heading_lead) : ?>
            <?php echo esc_html($heading_lead); ?> <em><?php echo esc_html($heading_last); ?></em>
          <?php else : ?>
            <?php echo esc_html($heading); ?>
          <?php endif; ?>
        </h2>
      </div>
      <div class="nexor-promotions__grid<?php echo $featured ? ' nexor-promotions__grid--featured' : ''; ?>">
        <?php if ($featured) : ?>
          <?php
          $featured_title = (string) ($featured['title'] ?? '');
          $featured_id = (string) ($featured['id'] ?? '');
          $featured_cta = (string) ($featured['cta_label'] ?? 'Получить подарок');
          $featured_note = trim((string) ($featured['note'] ?? ''));
          ?>
          <article class="nexor-promo-card nexor-promo-card--featured nexor-reveal">
            <div class="nexor-promo-card__copy flex-1">
              <p class="nexor-promo-card__badge">Главный подарок</p>
              <h3><?php echo esc_html($featured_title); ?></h3>
              <?php if ($featured_note) : ?>
                <p class="nexor-promo-card__note"><?php echo esc_html($featured_note); ?></p>
              <?php endif; ?>
              <button type="button" class="nexor-promo-card__cta inline-flex items-center gap-1" data-nexor-context-type="promotion" data-nexor-context-id="<?php echo esc_attr($featured_id); ?>">
                <?php echo esc_html($featured_cta); ?> <?php echo $cta_icon; ?>
              </button>
            </div>
          </article>
        <?php endif; ?>
        <?php foreach ($cards as $card) : ?>
          <?php
          $id = (string) ($card['id'] ?? '');
          $title = (string) ($card['title'] ?? '');
          $details = trim((string) ($card['details'] ?? ''));
          $cta_label = (string) ($card['cta_label'] ?? 'Узнать условия');
          $variant = (string) ($card['variant'] ?? 'plain');
          $image = (string) ($card['image'] ?? '');
          $amount = absint($card['threshold_amount'] ?? 0);
          $kicker = trim((string) ($card['kicker'] ?? ''));
          $value = trim((string) ($card['value'] ?? ''));
          $note = trim((string) ($card['note'] ?? ''));
          $is_photo = '' !== $image && 'discount' !== $variant;
          $card_class = 'nexor-promo-card nexor-promo-card--' . $variant . ' nexor-reveal';
          if ($is_photo) {
            $card_class .= ' nexor-promo-card--photo';
          }
          ?>
          <article class="<?php echo esc_attr($card_class); ?>">
            <?php if ($is_photo) : ?>
              <img class="nexor-promo-card__media" src="<?php echo esc_url($image); ?>" alt="" loading="lazy" decoding="async">
            <?php endif; ?>
            <div class="nexor-promo-card__body">
              <?php if ('discount' === $variant && $value) : ?>
                <?php if ($kicker) : ?>
                  <p class="nexor-promo-card__kicker"><?php echo esc_html($kicker); ?></p>
                <?php endif; ?>
                <p class="nexor-promo-card__value"><?php echo esc_html($value); ?></p>
                <?php if ($note) : ?>
                  <p class="nexor-promo-card__caption"><?php echo esc_html($note); ?></p>
                <?php endif; ?>
              <?php else : ?>
                <h3><?php echo esc_html($title); ?></h3>
                <?php if ($amount) : ?>
                  <p class="nexor-promo-card__amount">от <?php echo esc_html(number_format_i18n($amount, 0)); ?> ₽</p>
                <?php endif; ?>
              <?php endif; ?>
              <button type="button" class="nexor-promo-card__more" data-nexor-bonus-details data-bonus-id="<?php echo esc_attr($id); ?>" data-bonus-title="<?php echo esc_attr($title); ?>" data-bonus-details="<?php echo esc_attr($details); ?>" data-bonus-cta="<?php echo esc_attr($cta_label); ?>" aria-label="Подробнее">
                <?php echo $more_icon; ?>
              </button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <?php if ($disclaimer) : ?>
        <p class="nexor-promotions__disclaimer"><?php echo esc_html($disclaimer); ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>