<?php
define('ABSPATH', __DIR__);
class WP_Error
{
  public function __construct(public string $code = '', public string $message = '', public array $data = array()) {}
}
class WP_Query
{
  public array $values = array();
  public function __construct(public string $term = '') {}
  public function is_main_query()
  {
    return true;
  }
  public function is_search()
  {
    return true;
  }
  public function get($key)
  {
    return $key === 's' ? $this->term : ($this->values[$key] ?? null);
  }
  public function set($key, $value)
  {
    $this->values[$key] = $value;
  }
}
function add_action(...$args) {}
function add_filter(...$args) {}
function sanitize_key($v)
{
  return preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$v));
}
function sanitize_text_field($v)
{
  return trim(strip_tags((string)$v));
}
function sanitize_textarea_field($v)
{
  return trim(strip_tags((string)$v));
}
function esc_url_raw($v)
{
  return filter_var((string)$v, FILTER_VALIDATE_URL) ? (string)$v : '';
}
function absint($v)
{
  return abs((int)$v);
}
function wp_generate_uuid4()
{
  return '12345678-1234-1234-1234-123456789abc';
}
function wp_parse_args($a, $b)
{
  return array_merge($b, $a);
}
function home_url($p = '/')
{
  return 'https://example.test' . $p;
}
function current_time($f)
{
  return '2026-07-16T12:00:00+03:00';
}
function wp_json_encode($v, $flags = 0)
{
  return json_encode($v, $flags);
}
function is_admin()
{
  return false;
}
$GLOBALS['is_front_page'] = true;
$GLOBALS['active_page'] = '';
function is_front_page()
{
  return $GLOBALS['is_front_page'];
}
function is_page($v = null)
{
  $active = $GLOBALS['active_page'];
  if (!$active) return false;
  if (is_array($v)) return in_array($active, $v, true);
  return null === $v || $v === $active;
}
function is_singular($v = '')
{
  return false;
}
function get_queried_object_id()
{
  return 0;
}
function esc_html($v)
{
  return htmlspecialchars((string)$v, ENT_QUOTES);
}
function esc_attr($v)
{
  return htmlspecialchars((string)$v, ENT_QUOTES);
}
function esc_textarea($v)
{
  return esc_html($v);
}
function checked($a, $b = true, $echo = true)
{
  $value = $a == $b ? ' checked' : '';
  if ($echo) echo $value;
  return $value;
}
function esc_url($v)
{
  return esc_attr($v);
}
function number_format_i18n($v, $d = 0)
{
  return number_format($v, $d, '.', ' ');
}
function wp_get_attachment_url($id)
{
  return '';
}
function wp_get_attachment_image_url($id, $size)
{
  return $id ? ('https://example.test/media/' . $id . '-' . $size . '.webp') : '';
}
function wp_enqueue_media() {}
function get_theme_file_uri($path = '')
{
  return 'https://example.test/wp-content/themes/nexor/' . ltrim($path, '/');
}
function get_post_status($id)
{
  return 'publish';
}
function get_post_type($id)
{
  return 'nexor_project';
}
function get_the_title($post)
{
  return is_object($post) ? $post->post_title : 'Project ' . $post;
}
function get_permalink($post)
{
  $id = is_object($post) ? $post->ID : $post;
  return 'https://example.test/item/' . $id . '/';
}
function get_post_meta($id, $key, $single = true)
{
  return '';
}
class WP_Post {
  public $ID;
  public $post_status;
  public $post_title;
  public $post_name;
}
function get_page_by_path($slug)
{
  static $id = 10;
  $page = new WP_Post();
  $page->ID = $id++;
  $page->post_status = 'publish';
  $page->post_title = ucwords(str_replace('-', ' ', $slug));
  $page->post_name = $slug;
  return $page;
}
function nexor_render_home_services_section(array $cards, array $headings = array()): string
{
  if (! $cards) {
    return '';
  }
  $html = '<section id="main-services"><div>';
  foreach ($cards as $card) {
    $html .= '<article><a href="' . esc_url($card['url']) . '"><h3>' . esc_html($card['title']) . '</h3></a></article>';
  }
  return $html . '</div></section>';
}
$GLOBALS['options'] = array(
  'nexor_home_prices' => array('enabled' => 1, 'heading' => 'Цены и сроки', 'intro' => '', 'disclaimer' => 'После осмотра.', 'rows' => array(array('id' => 'price-1', 'enabled' => 1, 'order' => 10, 'service_page_id' => 10, 'service_label' => 'Капитальный ремонт', 'price_label' => 'По расчёту', 'duration_label' => 'После осмотра', 'note' => '', 'cta_label' => 'Уточнить'))),
  'nexor_home_video' => array('enabled' => 0),
  'nexor_additional_services' => array('enabled' => 1, 'heading' => 'Дополнительная помощь, которая экономит ваше время', 'intro' => 'Не ограничиваемся только ремонтом.', 'rows' => array(array('id' => 'extra-1', 'enabled' => 1, 'order' => 10, 'title' => 'Подбор материалов', 'subtitle' => 'Поможем выбрать материалы без переплат', 'description' => 'Подберем материалы с учетом вашего бюджета.', 'included_items' => "Подбор материалов по бюджету.\nПомощь с выбором цветов и фактур.", 'benefit' => 'Экономите время и избегаете лишних расходов.', 'cta_label' => '', 'cta_mode' => 'form', 'cta_target' => ''))),
  'nexor_promotions' => array('enabled' => 1, 'heading' => 'Акции', 'featured_enabled' => 1, 'featured_id' => 'full-design-project-from-5000000', 'featured_eyebrow' => 'До 31 августа', 'featured_deadline' => '2026-08-31T23:59:59+03:00', 'rows' => array(array('id' => 'promo-1', 'enabled' => 1, 'order' => 10, 'title' => 'Акция', 'summary' => '', 'threshold_amount' => 0, 'condition_text' => 'Условие', 'cta_label' => 'Выбрать', 'legal_text' => 'Правила'), array('id' => 'full-design-project-from-5000000', 'enabled' => 1, 'order' => 50, 'title' => 'Дизайн-проект в подарок', 'summary' => '', 'threshold_amount' => 5000000, 'condition_text' => 'При ремонте под ключ', 'cta_label' => 'Получить дизайн-проект', 'legal_text' => 'До 31 августа'))),
  'nexor_budget_control' => array('enabled' => 1, 'heading' => 'Как мы держим смету', 'metric' => '0%', 'metric_label' => 'отклонение итоговой сметы от первоначальной', 'rows' => array(array('id' => 'budget-1', 'enabled' => 1, 'order' => 10, 'title' => 'Считаем детально на замере', 'description' => 'Закладываем все работы'))),
  'nexor_home_timeline' => array('enabled' => 1, 'heading' => 'Реальные сроки ремонта без обещаний «за 30 дней»', 'disclaimer' => 'Точные сроки фиксируем в договоре после замера, составления сметы и согласования объема работ. Они могут измениться только при изменении объема работ или по инициативе заказчика.', 'rows' => array(array('id' => 'timeline-1', 'enabled' => 1, 'order' => 10, 'area' => 'До 50 м²', 'new_build' => 'от 45 дней', 'capital' => '60–90 дней', 'designer' => '90–120 дней'))),
  'nexor_exit_intent' => array('enabled' => 0),
  'nexor_home_stages' => array('enabled' => 1, 'eyebrow' => '5 этапов работы Nexor', 'heading' => 'Как мы делаем ремонт предсказуемым', 'intro' => 'Фиксированный бюджет, понятные сроки и полная прозрачность на каждом этапе.', 'rows' => array(array('id' => 'consultation', 'enabled' => 1, 'order' => 10, 'title' => 'Консультация', 'description' => 'Обсуждаем задачи и бюджет.', 'image_id' => 0))),
);
function get_option($key, $default = array())
{
  return $GLOBALS['options'][$key] ?? $default;
}
require dirname(__DIR__) . '/package/wp-content/plugins/nexor-core/class-nexor-enhancements.php';
if (($argv[1] ?? '') === '--admin-js') {
  ob_start();
  Nexor_Enhancements::render_admin_sections();
  $html = ob_get_clean();
  preg_match_all('#<script>([\s\S]*?)</script>#', $html, $matches);
  echo implode("\n", $matches[1]);
  exit;
}
function assert_true($value, $message)
{
  if (!$value) {
    fwrite(STDERR, "FAIL: $message\n");
    exit(1);
  }
  echo "PASS: $message\n";
}
$seed = Nexor_Enhancements::sanitize_promotions(array());
assert_true(count($seed['rows']) === 5, 'promotion seed contains exactly five stable rows');
$design = array_values(array_filter($seed['rows'], fn($row) => $row['id'] === 'full-design-project-from-5000000'))[0] ?? array();
assert_true(!empty($design['enabled']) && (int)$design['threshold_amount'] === 5000000, 'design project bonus uses the confirmed 5M threshold');
$seed2 = Nexor_Enhancements::sanitize_promotions($seed);
assert_true(count(array_unique(array_column($seed2['rows'], 'id'))) === 5, 'promotion seed is idempotent');
$valid = Nexor_Enhancements::resolve_lead_context(array('additional_service_id' => 'extra-1'));
assert_true(is_array($valid) && isset($valid['additional_snapshot']), 'enabled stable ID resolves to server snapshot');
$invalid = Nexor_Enhancements::resolve_lead_context(array('additional_service_id' => 'missing'));
assert_true($invalid instanceof WP_Error, 'invalid stable ID fails safely');
$search = new WP_Query('ремонт');
Nexor_Enhancements::search_policy($search);
assert_true($search->values['post_type'] === array('page', 'post', 'nexor_project') && $search->values['post_status'] === 'publish', 'search allowlist excludes leads and private entities');
$empty = new WP_Query('   ');
Nexor_Enhancements::search_policy($empty);
assert_true($empty->values['post__in'] === array(0), 'empty search cannot return all content');
$source = '<main><section id="calculator"></section><section id="cases"><h2 class="heading-section text-foreground mb-5">Реализованные проекты</h2></section><section><h2>Ремонт без неприятных сюрпризов — благодаря системе Nexor</h2></section><section id="about-company-nexor"></section></main>';
$html = Nexor_Enhancements::inject_frontend_content($source);
$order = array_map(fn($needle) => strpos($html, $needle), array('id="main-services"', 'id="cases"', 'id="calculator"', 'id="budget-control"', 'id="prices"', 'id="repair-timeline"', 'Ремонт без неприятных сюрпризов', 'id="additional-services"', 'id="promotions"', 'id="about-company-nexor"'));
assert_true($order === $sorted = call_user_func(function ($v) {
  $s = $v;
  sort($s);
  return $s;
}, $order), 'home sections follow approved order');
assert_true(!str_contains($html, 'id="video"'), 'disabled video leaves no blank section');
assert_true(str_contains($html, 'Что входит:') && str_contains($html, 'Экономите время и избегаете лишних расходов.'), 'additional-services card keeps the customer structure and benefit');
assert_true(str_contains($html, 'Помощь с выбором цветов и фактур.') && !str_contains($html, '<li></li>'), 'additional-services line splitting preserves Cyrillic UTF-8 text');
assert_true(!str_contains(substr($html, strpos($html, 'id="main-services"'), strpos($html, 'id="calculator"') - strpos($html, 'id="main-services"')), 'Cosmetic Remont'), 'cosmetic repair is absent from homepage service cards');
$hero_source = '<main><section class="relative min-h-[85vh] flex items-center pt-16 md:pt-20"><div><img data-attachment-id="155" src="https://example.test/old.webp" alt="Интерьер"></div><div class="container-nexor relative z-10"><div class="max-w-3xl"><h1>Оффер</h1></div></div></section><section id="calculator"></section><section id="cases"></section><section id="about-company-nexor"></section></main>';
$hero_html = Nexor_Enhancements::inject_frontend_content($hero_source);
assert_true(str_contains($hero_html, 'class="nexor-home-hero"') && !preg_match('/nexor-home-hero">\s*<div[^>]*>\s*<img\b/s', $hero_html), 'homepage hero uses section background without absolute image markup');
assert_true(str_contains($hero_html, 'class="nexor-hero-promo') && str_contains($hero_html, 'Дизайн-проект в подарок'), 'homepage hero contains the featured design-project countdown');
assert_true(str_contains($hero_html, 'Спецпредложение') && str_contains($hero_html, 'До конца акции осталось') && str_contains($hero_html, 'nexor-hero-promo__head') && str_contains($hero_html, 'nexor-hero-promo__copy') && str_contains($hero_html, 'nexor-hero-promo__media') && str_contains($hero_html, 'nexor-hero-promo__countdown'), 'homepage hero promo matches the glass special-offer card');
assert_true(str_contains($hero_html, 'при ремонте от 5 млн') && str_contains($hero_html, 'Получить подарок'), 'homepage hero promo shows threshold note and clickable CTA hint');
assert_true(!str_contains($hero_html, '5 000 000'), 'featured design-project offer does not expose the raw price threshold');
$hero_compose_source = '<main class="pt-[104px] md:pt-[124px]"><section class="relative min-h-[85vh] flex items-center pt-16 md:pt-20"><div><img src="https://example.test/old.webp" alt=""></div><div class="container-nexor relative z-10"><div class="max-w-3xl"><h1 class="heading-hero text-white mb-8">Ремонт квартир и домов под ключ в Москве и Московской области</h1><p class="text-body-large text-white/90 mb-10 max-w-xl">Фиксируем стоимость в договоре.</p><div class="flex flex-col sm:flex-row gap-6 mb-12"><button>Рассчитать стоимость</button><a href="#cases">Реализованные проекты</a></div><div class="flex flex-wrap gap-x-8 gap-y-3"><div class="flex items-center gap-2.5 text-white/90"><div class="w-5 h-5"></div><span class="text-sm font-medium">Фиксированная смета без скрытых работ</span></div><div class="flex items-center gap-2.5 text-white/90"><div class="w-5 h-5"></div><span class="text-sm font-medium">Поэтапная оплата — платите за результат</span></div><div class="flex items-center gap-2.5 text-white/90"><div class="w-5 h-5"></div><span class="text-sm font-medium">Гарантия на выполненные работы</span></div></div></div></div></section><section id="calculator"></section><section id="cases"></section><section id="about-company-nexor"></section></main>';
$hero_compose_html = Nexor_Enhancements::inject_frontend_content($hero_compose_source);
assert_true(str_contains($hero_compose_html, 'nexor-home-hero__main') && str_contains($hero_compose_html, 'nexor-home-hero__aside') && str_contains($hero_compose_html, 'nexor-home-hero__features'), 'homepage hero compose builds main/aside/features layout');
assert_true(str_contains($hero_compose_html, 'Гарантия 3 года') && !str_contains($hero_compose_html, 'class="max-w-3xl"') && !str_contains($hero_compose_html, 'flex flex-wrap gap-x-8 gap-y-3'), 'legacy hero shell is replaced by reference layout');
$GLOBALS['is_front_page'] = false;
$GLOBALS['active_page'] = 'capital-remont';
$service_source = '<main><div class="bg-muted/30"><nav aria-label="breadcrumb"></nav></div><section class="relative min-h-[85vh] flex items-center overflow-hidden"><div class="container-nexor"><div class="max-w-[680px]"><h1>Капитальный ремонт</h1><p>Описание</p><button>Записаться на замер</button></div></div></section><section><h2>Что входит</h2></section></main>';
$service_html = Nexor_Enhancements::inject_frontend_content($service_source);
assert_true(str_contains($service_html, '<main class="nexor-service-page">') && str_contains($service_html, 'class="nexor-service-hero '), 'service page receives the unified editorial shell');
assert_true(str_contains($service_html, 'Nexor · системный ремонт') && str_contains($service_html, 'class="nexor-service-hero__card"'), 'service hero receives trust content without replacing the H1');
assert_true(substr_count($service_html, 'class="nexor-service-standards"') === 1 && substr_count($service_html, '<h1>') === 1, 'service standards are injected once and preserve one H1');
$stages_html = Nexor_Enhancements::sanitize_stages(array('enabled' => 1, 'heading' => 'Stages test', 'intro' => 'Intro', 'rows' => array(array('id' => 'step-1', 'enabled' => 1, 'order' => 10, 'title' => 'Шаг 1', 'description' => 'Описание', 'image_id' => 0))));
assert_true(count($stages_html['rows']) >= 1, 'stages sanitize keeps enabled rows');
$GLOBALS['is_front_page'] = true;
$stages_source = '<main><section class="py-[120px] md:py-[140px] bg-card"><div class="grid grid-cols-5"><h3>Старые пять шагов</h3></div></section><section id="before-after"></section></main>';
$stages_page = Nexor_Enhancements::inject_frontend_content($stages_source);
assert_true(str_contains($stages_page, 'id="stages"') && str_contains($stages_page, 'Консультация'), 'stages section renders on the homepage');
assert_true(!str_contains($stages_page, 'Старые пять шагов') && !str_contains($stages_page, 'bg-card'), 'migrated five-step block is dropped in favour of the stages dial');
assert_true(strpos($stages_page, 'id="stages"') < strpos($stages_page, 'id="before-after"'), 'stages section precedes before-after block');
assert_true(str_contains($stages_page, 'nexor-stage-card__index') && str_contains($stages_page, '<span>01</span> / <span>01</span>'), 'stage slides expose index above headings');
assert_true(str_contains($stages_page, 'nexor-stage-card__nav') && str_contains($stages_page, 'aria-selected="true"'), 'stage cards expose numbered step nav with active marker');
assert_true(substr_count($stages_page, 'class="nexor-stage-card"') === 1 && substr_count($stages_page, 'nexor-stage-card__slide ') + substr_count($stages_page, 'nexor-stage-card__slide"') === 1, 'stages render a single card with one slide per stage');
assert_true(str_contains($stages_page, 'data-stage-dial') && str_contains($stages_page, 'data-stage-knob') && str_contains($stages_page, 'role="slider"'), 'stage card exposes the rotary knob control');
assert_true(str_contains($stages_page, 'nexor-stage-card__heading') && str_contains($stages_page, 'nexor-stage-card__eyebrow') && str_contains($stages_page, 'nexor-stage-card__intro'), 'stage card keeps the section heading block');
assert_true(str_contains($stages_page, 'nexor-stage-card__main') && str_contains($stages_page, 'nexor-stage-card__copy') && str_contains($stages_page, 'nexor-stage-card__description'), 'stage cards group visual and copy blocks like the reference');
assert_true(substr_count($stages_page, '>Записаться на замер</button>') === 1 && str_contains($stages_page, 'bg-primary text-primary-foreground hover:bg-terracotta-dark') && !str_contains($stages_page, 'nexor-stage-card__description">Обсуждаем задачи и бюджет.</p><button'), 'stages keep a single static header CTA outside animated slides');
assert_true(!str_contains($stages_page, 'nexor-stage-card__media'), 'stages without media library image_id render no fallback theme images');
$GLOBALS['options']['nexor_home_stages']['rows'][0]['image_id'] = 42;
$stages_with_image = Nexor_Enhancements::inject_frontend_content($stages_source);
assert_true(str_contains($stages_with_image, 'nexor-stage-card__media') && str_contains($stages_with_image, 'https://example.test/media/42-large.webp'), 'stages use attachment URL from image_id');
assert_true(!str_contains($stages_with_image, 'themes/nexor/assets/'), 'stages never fall back to static theme assets');
ob_start();
Nexor_Enhancements::render_admin_sections();
$admin_html = ob_get_clean();
assert_true(str_contains($admin_html, 'nexor-media-select') && str_contains($admin_html, 'nexor-media-field'), 'stages admin exposes media library picker instead of raw image ID input');
