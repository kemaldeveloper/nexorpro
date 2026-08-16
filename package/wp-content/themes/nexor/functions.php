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

/** Contacts for the global PHP header (from Nexor settings with safe defaults). */
function nexor_contact_settings(): array
{
  $defaults = array(
    'phone_display' => '+7 (926) 083-23-24',
    'phone_link'    => '+79260832324',
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
      )
    );
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
  $replacements = apply_filters('nexor_content_replacements', array());
  if ($replacements) {
    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
  }
  $content = apply_filters('nexor_migrated_content', $content);
  $content = str_replace('url(/assets/', 'url(' . get_template_directory_uri() . '/assets/', $content);
  $content = str_replace('{{THEME_URI}}', get_template_directory_uri(), $content);
  echo do_shortcode($content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted post content managed by editors.
}
