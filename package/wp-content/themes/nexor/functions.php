<?php

/**
 * Nexor theme bootstrap.
 *
 * @package Nexor
 */

if (! defined('ABSPATH')) {
  exit;
}

define('NEXOR_THEME_VERSION', '1.6.0');

/** Contacts for the global PHP header and footer (from Nexor settings with safe defaults). */
function nexor_contact_settings(): array
{
  $defaults = array(
    'phone_display' => '+7 (926) 083-23-24',
    'phone_link'    => '+79260832324',
    'email'         => 'nexor.msk@mail.ru',
    'hours'         => 'Ежедневно с 9:00 до 21:00',
    'inn'           => '352803113189',
    'ogrnip'        => '324350000048081',
    'telegram_url'  => 'https://t.me/nexor_msk',
    'vk_url'        => 'https://vk.com/club238015413',
  );
  $option = get_option('nexor_settings', array());
  if (! is_array($option)) {
    return $defaults;
  }
  return wp_parse_args($option, $defaults);
}

/** Strip legacy per-page &lt;header&gt; blocks from migrated HTML (now rendered in header.php). */
function nexor_strip_embedded_header(string $content): string
{
  $stripped = preg_replace('/<header\b[^>]*\bfixed\b[^>]*>.*?<\/header>/is', '', $content, 1);
  return is_string($stripped) ? $stripped : $content;
}

/** Strip legacy per-page &lt;footer&gt; blocks from migrated HTML (now rendered in footer.php). */
function nexor_strip_embedded_footer(string $content): string
{
  $stripped = preg_replace('/<footer\b[^>]*>.*?<\/footer>/is', '', $content);
  return is_string($stripped) ? $stripped : $content;
}

/** Return flat menu data for the migrated-header compatibility layer. */
function nexor_menu_payload(string $location): array
{
  $result = array('items' => array(), 'services' => array());
  $locations = get_nav_menu_locations();
  if (empty($locations[$location])) return $result;
  $menu_items = wp_get_nav_menu_items((int) $locations[$location]);
  if (! is_array($menu_items)) return $result;
  $services_parent = 0;
  foreach ($menu_items as $item) {
    if (0 === (int) $item->menu_item_parent && 'услуги' === mb_strtolower(trim(wp_strip_all_tags($item->title)))) {
      $services_parent = (int) $item->ID;
      break;
    }
  }
  foreach ($menu_items as $item) {
    $entry = array('label' => wp_strip_all_tags($item->title), 'url' => esc_url_raw($item->url));
    if ($services_parent && $services_parent === (int) $item->menu_item_parent) $result['services'][] = $entry;
    elseif (0 === (int) $item->menu_item_parent && (int) $item->ID !== $services_parent) $result['items'][] = $entry;
  }
  return $result;
}

/** Build WordPress-managed navigation while retaining the original markup and design. */
function nexor_navigation_payload(): array
{
  $services = array(
    array('label' => 'Ремонт квартир под ключ', 'url' => home_url('/remont-kvartir-pod-klyuch/')),
    array('label' => 'Капитальный ремонт', 'url' => home_url('/capital-remont/')),
    array('label' => 'Дизайнерский ремонт', 'url' => home_url('/design-remont/')),
    array('label' => 'Ремонт в новостройке', 'url' => home_url('/remont-v-novostroyke/')),
    array('label' => 'Косметический ремонт', 'url' => home_url('/cosmetic-remont/')),
    array('label' => 'Ремонт домов под ключ', 'url' => home_url('/remont-domov-pod-klyuch/')),
  );
  $items = array(
    array('label' => 'Калькулятор', 'url' => home_url('/#calculator')),
    array('label' => 'Проекты', 'url' => home_url('/projects/')),
    array('label' => 'О компании', 'url' => home_url('/#about-company-nexor')),
    array('label' => 'FAQ', 'url' => home_url('/#faq')),
  );
  $primary = nexor_menu_payload('primary');
  $mobile = nexor_menu_payload('mobile');
  $primary['items'] = $primary['items'] ?: $items;
  $primary['services'] = $primary['services'] ?: $services;
  $mobile['items'] = $mobile['items'] ?: $primary['items'];
  $mobile['services'] = $mobile['services'] ?: $primary['services'];
  $section_links = class_exists('Nexor_Enhancements') ? Nexor_Enhancements::active_section_links() : array();
  return array('primary' => $primary, 'mobile' => $mobile, 'sectionLinks' => $section_links);
}

function nexor_render_home_hero_section(array $copy = array()): string
{
  ob_start();
  get_template_part(
    'template-parts/home',
    'hero-section',
    array(
      'heading' => $copy['heading'] ?? 'Ремонт квартир и домов под ключ',
      'sub' => $copy['sub'] ?? 'в Москве и Московской области',
      'eyebrow' => $copy['eyebrow'] ?? 'Работаем по фиксированной смете',
      'lead' => $copy['lead'] ?? 'Фиксируем стоимость в договоре, заранее обозначаем честный диапазон бюджета и берём на себя весь процесс — от подготовки до сдачи объекта.',
      'calculate_label' => $copy['calculate_label'] ?? 'Рассчитать стоимость',
      'calculate_url' => $copy['calculate_url'] ?? '#calculator',
      'projects_label' => $copy['projects_label'] ?? 'Реализованные проекты',
      'projects_url' => $copy['projects_url'] ?? home_url('/projects/'),
      'promo' => $copy['promo'] ?? '',
      'features' => $copy['features'] ?? array(
        array('num' => '01', 'title' => 'Фиксированная смета', 'text' => 'Без скрытых работ'),
        array('num' => '02', 'title' => 'Поэтапная оплата', 'text' => 'Платите за результат'),
        array('num' => '03', 'title' => 'Гарантия 3 года', 'text' => 'На выполненные работы'),
      ),
    )
  );
  return (string) ob_get_clean();
}

function nexor_render_home_services_section(array $cards, array $headings = array()): string
{
  if (! $cards) {
    return '';
  }

  ob_start();
  get_template_part(
    'template-parts/home',
    'services-section',
    array(
      'cards' => $cards,
      'eyebrow' => $headings['eyebrow'] ?? 'Направления работы',
      'heading' => $headings['heading'] ?? 'Основные услуги',
    )
  );
  return (string) ob_get_clean();
}

function nexor_render_home_projects_section(array $cards, array $headings = array()): string
{
  if (! $cards) {
    return '';
  }

  ob_start();
  get_template_part(
    'template-parts/home',
    'projects-section',
    array(
      'cards' => $cards,
      'heading' => $headings['heading'] ?? 'Реализованные проекты',
      'intro' => $headings['intro'] ?? 'Показываем реальные объекты с понятными сроками, бюджетами и результатом.',
      'cta_label' => $headings['cta_label'] ?? 'Все проекты',
    )
  );
  return (string) ob_get_clean();
}

function nexor_render_home_calculator_section(array $copy = array()): string
{
  ob_start();
  get_template_part(
    'template-parts/home',
    'calculator-section',
    array(
      'eyebrow' => $copy['eyebrow'] ?? 'Расчёт стоимости',
      'heading' => $copy['heading'] ?? 'Рассчитайте ориентировочную стоимость ремонта',
      'intro' => $copy['intro'] ?? 'Ответьте на 7 коротких вопросов и получите ориентир по бюджету.',
      'cta_label' => $copy['cta_label'] ?? 'Рассчитать стоимость',
    )
  );
  return (string) ob_get_clean();
}

function nexor_render_home_budget_section(array $copy = array()): string
{
  $rows = $copy['rows'] ?? array();
  if (! $rows) {
    return '';
  }

  ob_start();
  get_template_part(
    'template-parts/home',
    'budget-section',
    array(
      'heading' => $copy['heading'] ?? '',
      'metric' => $copy['metric'] ?? '',
      'metric_label' => $copy['metric_label'] ?? '',
      'metric_note' => $copy['metric_note'] ?? '',
      'rows' => $rows,
    )
  );
  return (string) ob_get_clean();
}

function nexor_render_home_timeline_section(array $copy = array()): string
{
  $rows = $copy['rows'] ?? array();
  if (! $rows) {
    return '';
  }

  ob_start();
  get_template_part(
    'template-parts/home',
    'timeline-section',
    array(
      'heading' => $copy['heading'] ?? '',
      'disclaimer' => $copy['disclaimer'] ?? '',
      'rows' => $rows,
    )
  );
  return (string) ob_get_clean();
}

function nexor_render_home_nexor_system_section(array $copy = array()): string
{
  ob_start();
  get_template_part('template-parts/home', 'nexor-system-section', $copy);
  return (string) ob_get_clean();
}

function nexor_render_home_additional_section(array $copy = array()): string
{
  ob_start();
  get_template_part(
    'template-parts/home',
    'additional-section',
    array(
      'eyebrow' => $copy['eyebrow'] ?? 'Сервис полного цикла',
      'heading' => $copy['heading'] ?? '',
      'intro' => $copy['intro'] ?? '',
      'rows' => $copy['rows'] ?? array(),
    )
  );
  return (string) ob_get_clean();
}

function nexor_render_home_promotions_section(array $copy = array()): string
{
  ob_start();
  get_template_part(
    'template-parts/home',
    'promotions-section',
    array(
      'heading' => $copy['heading'] ?? 'Бонусы, которые делают ремонт выгоднее',
      'disclaimer' => $copy['disclaimer'] ?? '',
      'deadline_label' => $copy['deadline_label'] ?? '',
      'featured' => $copy['featured'] ?? array(),
      'cards' => $copy['cards'] ?? array(),
    )
  );
  return (string) ob_get_clean();
}

function nexor_render_home_about_section(array $copy = array()): string
{
  ob_start();
  get_template_part(
    'template-parts/home',
    'about-section',
    array(
      'eyebrow' => $copy['eyebrow'] ?? 'О компании',
      'heading' => $copy['heading'] ?? 'Nexor — <br/> не бригада.',
      'sub' => $copy['sub'] ?? 'Системная компания по ремонту квартир и домов в Москве.',
      'points' => $copy['points'] ?? array(
        array(
          'title' => 'Проблема не в ремонте, а в хаосе без системы.',
          'text' => 'Большинство проблем в ремонте возникает не из-за самих работ, а из за отсуствия системы. Когда каждый отвечает только за свою часть, появляются срывы сроков, непредвиденные расходы и постоянная необходимость все контролировать самостоятельно.',
        ),
        array(
          'title' => 'В Nexor процесс выстроен иначе.',
          'text' => 'В штате работают собственные мастера, прорабы и технический контроль. Один проект ведёт одна команда — от первого замера до сдачи объекта. Более 8 лет мы применяем этот подход при капитальном и дизайнерском ремонте квартир и частных домов в Москве и Московской области.',
        ),
        array(
          'title' => 'Для клиента это понятный процесс без постоянного контроля.',
          'text' => 'Каждый этап регламентирован, сроки и бюджет фиксируются в договоре, ход работ прозрачен. Вы получаете управляемый процесс без скрытых платежей и необходимости всё контролировать самостоятельно.',
        ),
      ),
      'stats' => $copy['stats'] ?? array(
        array('value' => '340+', 'label' => 'объектов сдано', 'note' => 'квартиры и дома под ключ'),
        array('value' => '8 лет', 'label' => 'на рынке', 'note' => 'опыт, проверенный годами'),
        array('value' => '40+', 'label' => 'специалистов в штате', 'note' => 'мастера, прорабы, инженеры и дизайнеры'),
        array('value' => '98%', 'label' => 'рекомендуют нас', 'note' => 'по отзывам наших клиентов'),
      ),
    )
  );
  return (string) ob_get_clean();
}

function nexor_render_home_faq_section(array $copy = array()): string
{
  ob_start();
  get_template_part(
    'template-parts/home',
    'faq-section',
    array(
      'heading' => $copy['heading'] ?? 'Частые вопросы о ремонте',
      'intro' => $copy['intro'] ?? 'Отвечаем на вопросы, которые чаще всего задают перед началом ремонта. Всё фиксируем в договоре — без скрытых условий и неожиданных расходов.',
      'items' => $copy['items'] ?? array(
        array(
          'question' => 'Как формируется стоимость ремонта?',
          'answer' => 'Стоимость зависит от площади, состояния объекта, объёма работ и выбранных материалов. После замера мы составляем подробную смету по этапам и фиксируем её в договоре — вы заранее понимаете бюджет ремонта.',
        ),
        array(
          'question' => 'Можно ли заранее понять примерный бюджет?',
          'answer' => 'Да. По телефону или в мессенджере уточним основные параметры и дадим ориентир по стоимости. Точную смету готовим после замера.',
        ),
        array(
          'question' => 'Гарантируете ли вы соблюдение сроков?',
          'answer' => 'Да. Сроки фиксируются в договоре и контролируются прорабом и техническим надзором. Мы планируем работы по графику, чтобы исключить простои и затягивание ремонта.',
        ),
        array(
          'question' => 'Что входит в ремонт «под ключ»?',
          'answer' => 'Полный цикл работ: демонтаж, подготовка оснований, инженерные коммуникации, черновая и чистовая отделка, установка сантехники, электрики и дверей, финальная уборка. При необходимости подключаем дизайн-проект и комплектацию материалов.',
        ),
        array(
          'question' => 'Кто контролирует качество работ?',
          'answer' => 'За объект отвечает прораб, а ключевые этапы дополнительно проверяет внутренний контроль качества. Перед сдачей проводим финальную проверку — вы принимаете результат без недочётов.',
        ),
        array(
          'question' => 'Работаете ли вы с материалами заказчика?',
          'answer' => 'Да. Можем работать с вашими материалами или полностью взять закупку на себя. Помогаем подобрать решения под бюджет и привозим материалы точно к этапам работ.',
        ),
        array(
          'question' => 'Какую гарантию вы даёте?',
          'answer' => 'Гарантия фиксируется в договоре. Если возникает гарантийный случай по нашей вине — устраняем его бесплатно и в приоритетном порядке.',
        ),
        array(
          'question' => 'Как происходит оплата?',
          'answer' => 'Оплата поэтапная — вы оплачиваете только выполненные и принятые работы. Все этапы и суммы заранее прописываются в договоре.',
        ),
      ),
    )
  );
  return (string) ob_get_clean();
}

function nexor_render_home_cta_section(array $copy = array()): string
{
  $contacts = function_exists('nexor_contact_settings') ? nexor_contact_settings() : array();

  ob_start();
  get_template_part(
    'template-parts/home',
    'cta-section',
    array(
      'heading' => $copy['heading'] ?? 'Запишитесь на профессиональный замер с инженером Nexor',
      'lead' => $copy['lead'] ?? 'Инженер изучит ваш объект, ответит на вопросы и соберёт всё необходимое для точного расчёта сметы и планирования ремонта.',
      'button_label' => $copy['button_label'] ?? 'Записаться на замер',
      'note' => $copy['note'] ?? 'Консультация и выезд инженера бесплатны и ни к чему не обязывают.',
      'phone_display' => $copy['phone_display'] ?? ($contacts['phone_display'] ?? '+7 (926) 083-23-24'),
      'phone_link' => $copy['phone_link'] ?? ($contacts['phone_link'] ?? '+79260832324'),
    )
  );
  return (string) ob_get_clean();
}

remove_action('wp_head', 'rel_canonical');

add_action(
  'wp_head',
  static function () {
    if (! is_admin()) {
      echo "\n<meta name=\"yandex-verification\" content=\"c99ae8eeb3386f97\">";
    }
  },
  1
);

add_action(
  'after_setup_theme',
  static function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
    register_nav_menus(
      array(
        'primary' => 'Главное меню',
        'mobile'  => 'Мобильное меню',
        'footer'  => 'Меню в подвале',
      )
    );
  }
);

add_action(
  'wp_enqueue_scripts',
  static function () {
    $styles = glob(get_template_directory() . '/assets/index-*.css');
    if ($styles) {
      wp_enqueue_style('nexor-design', get_template_directory_uri() . '/assets/' . basename($styles[0]), array(), filemtime($styles[0]));
    }
    $style_file = get_template_directory() . '/assets/nexor.css';
    wp_enqueue_style('nexor-theme', get_template_directory_uri() . '/assets/nexor.css', array('nexor-design'), file_exists($style_file) ? filemtime($style_file) : NEXOR_THEME_VERSION);

    $gsap_deps = array();
    $gsap_dir  = get_template_directory() . '/assets/vendor/gsap/minified';
    $gsap_uri  = get_template_directory_uri() . '/assets/vendor/gsap/minified';
    if (file_exists($gsap_dir . '/gsap.min.js')) {
      wp_enqueue_script('gsap', $gsap_uri . '/gsap.min.js', array(), (string) filemtime($gsap_dir . '/gsap.min.js'), true);
      $gsap_deps[] = 'gsap';
      $gsap_plugins = array(
        'gsap-scrolltrigger' => 'ScrollTrigger.min.js',
        'gsap-inertia'       => 'InertiaPlugin.min.js',
        'gsap-draggable'     => 'Draggable.min.js',
      );
      foreach ($gsap_plugins as $handle => $file) {
        if (! file_exists($gsap_dir . '/' . $file)) {
          continue;
        }
        wp_enqueue_script($handle, $gsap_uri . '/' . $file, array('gsap'), (string) filemtime($gsap_dir . '/' . $file), true);
        $gsap_deps[] = $handle;
      }
    }

    $script_file = get_template_directory() . '/assets/nexor.js';
    wp_enqueue_script('nexor-theme', get_template_directory_uri() . '/assets/nexor.js', $gsap_deps, file_exists($script_file) ? filemtime($script_file) : NEXOR_THEME_VERSION, true);
    $enhancements = class_exists('Nexor_Enhancements') ? Nexor_Enhancements::frontend_config() : array();
    $contacts = nexor_contact_settings();
    $captcha_sitekey = class_exists('Nexor_Core') ? Nexor_Core::smartcaptcha_sitekey() : '';
    wp_localize_script(
      'nexor-theme',
      'NexorSettings',
      array(
        'restUrl'  => esc_url_raw(rest_url('nexor/v1/')),
        'nonce'    => wp_create_nonce('wp_rest'),
        'thankYou' => home_url('/thank-you/'),
        'privacy'  => home_url('/privacy/'),
        'consent'  => home_url('/consent/'),
        'phoneDisplay' => $contacts['phone_display'],
        'phoneLink'    => $contacts['phone_link'],
        'telegramUrl'  => $contacts['telegram_url'],
        'vkUrl'        => $contacts['vk_url'],
        'navigation' => nexor_navigation_payload(),
        'enhancements' => $enhancements,
        'themeUri' => get_template_directory_uri(),
        'smartCaptchaSitekey' => $captcha_sitekey,
      )
    );
    if ('' !== $captcha_sitekey) {
      wp_enqueue_script(
        'yandex-smartcaptcha',
        'https://smartcaptcha.cloud.yandex.ru/captcha.js?render=explicit&onload=nexorSmartCaptchaReady',
        array('nexor-theme'),
        false,
        true
      );
    }
  }
);

add_action(
  'wp_enqueue_scripts',
  static function () {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
  },
  100
);

add_action(
  'wp_head',
  static function () {
    if (is_admin()) {
      return;
    }
    $object_id = get_queried_object_id();
    $title     = $object_id ? get_post_meta($object_id, '_nexor_seo_title', true) : '';
    $desc      = $object_id ? get_post_meta($object_id, '_nexor_seo_description', true) : '';
    $custom_canonical = $object_id ? get_post_meta($object_id, '_nexor_seo_canonical', true) : '';
    $custom_image     = $object_id ? get_post_meta($object_id, '_nexor_seo_og_image', true) : '';
    $canonical        = is_404() ? '' : (is_search() ? add_query_arg('s', get_search_query(), home_url('/')) : ($custom_canonical ?: home_url(wp_parse_url(add_query_arg(array()), PHP_URL_PATH))));
    $image            = $custom_image ?: get_theme_file_uri('og-image.jpg');
    if ($desc) {
      printf("\n<meta name=\"description\" content=\"%s\">", esc_attr($desc));
    }
    if ($canonical) {
      printf("\n<link rel=\"canonical\" href=\"%s\">", esc_url(user_trailingslashit($canonical)));
    }
    printf("\n<meta property=\"og:type\" content=\"website\"><meta property=\"og:url\" content=\"%s\"><meta property=\"og:image\" content=\"%s\"><meta name=\"twitter:card\" content=\"summary_large_image\"><meta name=\"twitter:image\" content=\"%s\">", esc_url($canonical ?: home_url('/')), esc_url($image), esc_url($image));
    if ($title) {
      printf("\n<meta property=\"og:title\" content=\"%s\">", esc_attr($title));
    }
    if ($desc) {
      printf("\n<meta property=\"og:description\" content=\"%s\">", esc_attr($desc));
    }
    if (is_search() || is_page('thank-you') || is_404() || ($object_id && '1' === get_post_meta($object_id, '_nexor_noindex', true))) {
      echo "\n<meta name=\"robots\" content=\"noindex, follow\">";
    }
  },
  2
);

add_filter(
  'pre_get_document_title',
  static function ($title) {
    $id     = get_queried_object_id();
    $custom = $id ? get_post_meta($id, '_nexor_seo_title', true) : '';
    return $custom ?: $title;
  }
);

add_action(
  'send_headers',
  static function () {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
  }
);

/** Render trusted, administrator-managed migration markup. */
function nexor_render_migrated_content(): void
{
  $content = get_the_content();
  $content = nexor_strip_embedded_header($content);
  $content = nexor_strip_embedded_footer($content);
  $replacements = apply_filters('nexor_content_replacements', array());
  if ($replacements) {
    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
  }
  $content = apply_filters('nexor_migrated_content', $content);
  $content = str_replace('url(/assets/', 'url(' . get_template_directory_uri() . '/assets/', $content);
  $content = str_replace('{{THEME_URI}}', get_template_directory_uri(), $content);
  echo do_shortcode($content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted post content managed by editors.
}
