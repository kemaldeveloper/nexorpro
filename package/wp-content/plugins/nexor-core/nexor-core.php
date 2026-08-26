<?php

/**
 * Plugin Name: Nexor Core
 * Description: Проекты, заявки, Telegram, SEO, настройки и калькулятор Nexor.
 * Version: 1.2.0
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * Text Domain: nexor-core
 */

if (! defined('ABSPATH')) {
  exit;
}

final class Nexor_Core
{
  private const VERSION = '1.2.0';
  private const OPTION  = 'nexor_settings';
  private const LEGACY_SEO_TITLE = 'Ремонт квартир и домов под ключ — инженерный контроль, фиксированная смета | Nexor';
  private const LEGACY_SEO_DESCRIPTION = 'Выполняем ремонт квартир и частных домов под ключ. Работаем по договору, фиксируем смету до начала работ и контролируем каждый этап. Инженерный подход, прозрачные сроки и персональная ответственность за результат. Запишитесь на инженерный замер.';

  public static function init(): void
  {
    add_action('init', array(__CLASS__, 'register_content'));
    add_action('rest_api_init', array(__CLASS__, 'register_rest'));
    add_action('add_meta_boxes', array(__CLASS__, 'meta_boxes'));
    add_action('save_post', array(__CLASS__, 'save_meta'));
    add_action('admin_menu', array(__CLASS__, 'admin_menu'));
    add_action('admin_init', array(__CLASS__, 'register_settings'));
    add_action('admin_post_nexor_resend_lead', array(__CLASS__, 'resend_lead'));
    add_action('wp_footer', array(__CLASS__, 'analytics'), 30);
    add_action('wp_head', array(__CLASS__, 'schema'), 20);
    add_filter('xmlrpc_enabled', '__return_false');
    add_filter('wp_sitemaps_posts_query_args', array(__CLASS__, 'sitemap_query'), 10, 2);
    add_filter('robots_txt', array(__CLASS__, 'robots'), 10, 2);
    add_filter('manage_nexor_lead_posts_columns', array(__CLASS__, 'lead_columns'));
    add_action('manage_nexor_lead_posts_custom_column', array(__CLASS__, 'lead_column'), 10, 2);
    add_filter('nexor_content_replacements', array(__CLASS__, 'content_replacements'));
    add_filter('nexor_migrated_content', array(__CLASS__, 'legal_replacements'));
    add_action('template_redirect', array(__CLASS__, 'redirect_legacy_taxonomy_archives'), 1);
  }

  public static function activate(): void
  {
    self::register_content();
    self::seed_content();
    flush_rewrite_rules();
  }

  public static function register_content(): void
  {
    register_post_type(
      'nexor_project',
      array(
        'labels'       => array('name' => 'Проекты', 'singular_name' => 'Проект', 'add_new_item' => 'Добавить проект', 'edit_item' => 'Редактировать проект'),
        'public'       => true,
        'has_archive'  => false,
        'menu_icon'    => 'dashicons-building',
        'show_in_rest' => true,
        'supports'     => array('title', 'editor', 'thumbnail', 'revisions', 'excerpt'),
        'rewrite'      => array('slug' => 'projects', 'with_front' => false),
      )
    );
    $taxonomy_args = array(
      'public'             => false,
      'publicly_queryable' => false,
      'show_ui'            => true,
      'show_admin_column'  => true,
      'show_in_rest'       => true,
      'hierarchical'       => true,
      'rewrite'            => false,
    );
    register_taxonomy('nexor_repair_type', 'nexor_project', array_merge($taxonomy_args, array('labels' => array('name' => 'Типы ремонта'))));
    register_taxonomy('nexor_property_type', 'nexor_project', array_merge($taxonomy_args, array('labels' => array('name' => 'Типы объектов'))));
    register_post_type(
      'nexor_lead',
      array(
        'labels'              => array('name' => 'Заявки', 'singular_name' => 'Заявка'),
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-email-alt2',
        'exclude_from_search' => true,
        'supports'            => array('title'),
        'capability_type'     => 'post',
      )
    );
  }

  public static function register_rest(): void
  {
    register_rest_route('nexor/v1', '/lead', array('methods' => 'POST', 'callback' => array(__CLASS__, 'create_lead'), 'permission_callback' => '__return_true'));
    register_rest_route('nexor/v1', '/calculate', array('methods' => 'POST', 'callback' => array(__CLASS__, 'calculate'), 'permission_callback' => '__return_true'));
  }

  /** Redirect previously indexed technical taxonomy archives to the projects catalogue. */
  public static function redirect_legacy_taxonomy_archives(): void
  {
    $path = wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (is_string($path) && preg_match('#^/(?:nexor_repair_type|nexor_property_type)(?:/|$)#', $path)) {
      wp_safe_redirect(home_url('/projects/'), 301);
      exit;
    }
  }

  private static function validate_request(WP_REST_Request $request): true|WP_Error
  {
    $nonce = $request->get_header('X-WP-Nonce');
    if (! $nonce || ! wp_verify_nonce($nonce, 'wp_rest')) {
      return new WP_Error('bad_nonce', 'Сессия устарела. Обновите страницу.', array('status' => 403));
    }
    $origin = $request->get_header('origin');
    if ($origin && wp_parse_url($origin, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST)) {
      return new WP_Error('bad_origin', 'Недопустимый источник запроса.', array('status' => 403));
    }
    return true;
  }

  private static function client_ip(): string
  {
    $candidates = array(
      $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
      $_SERVER['HTTP_X_REAL_IP'] ?? '',
      $_SERVER['REMOTE_ADDR'] ?? '',
    );
    foreach ($candidates as $raw) {
      $raw = sanitize_text_field(wp_unslash((string) $raw));
      if ('' === $raw) {
        continue;
      }
      $ip = trim(explode(',', $raw)[0]);
      if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
      }
    }
    return 'unknown';
  }

  private static function rate_limit(): true|WP_Error
  {
    $ip   = self::client_ip();
    $key  = 'nexor_rate_' . hash_hmac('sha256', $ip, wp_salt('nonce'));
    $now  = time();
    $hits = array_values(array_filter((array) get_transient($key), static fn($t) => (int) $t > $now - 900));
    if (count($hits) >= 3) {
      return new WP_Error('rate_limit', 'Слишком много отправок. Повторите через 15 минут.', array('status' => 429));
    }
    $hits[] = $now;
    set_transient($key, $hits, 900);
    return true;
  }

  private static function smartcaptcha_server_key(): string
  {
    if (defined('NEXOR_SMARTCAPTCHA_SERVER_KEY') && NEXOR_SMARTCAPTCHA_SERVER_KEY) {
      return (string) NEXOR_SMARTCAPTCHA_SERVER_KEY;
    }
    $env = getenv('NEXOR_SMARTCAPTCHA_SERVER_KEY');
    return false === $env ? '' : (string) $env;
  }

  public static function smartcaptcha_sitekey(): string
  {
    return sanitize_text_field((string) (self::settings()['smartcaptcha_sitekey'] ?? ''));
  }

  private static function verify_smartcaptcha(string $token): true|WP_Error
  {
    $secret  = self::smartcaptcha_server_key();
    $sitekey = self::smartcaptcha_sitekey();
    if ('' === $sitekey) {
      return true;
    }
    if ('' === $secret) {
      error_log('Nexor SmartCaptcha sitekey is set but NEXOR_SMARTCAPTCHA_SERVER_KEY is missing'); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      return new WP_Error('captcha', 'Не удалось отправить заявку. Попробуйте ещё раз.', array('status' => 503));
    }
    if ('' === $token) {
      return new WP_Error('captcha', 'Не удалось отправить заявку. Обновите страницу.', array('status' => 400));
    }
    $response = wp_remote_post(
      'https://smartcaptcha.cloud.yandex.ru/validate',
      array(
        'timeout' => 2,
        'body'    => array(
          'secret' => $secret,
          'token'  => $token,
          'ip'     => self::client_ip(),
        ),
      )
    );
    if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
      error_log('Nexor SmartCaptcha validate unavailable, allowing request'); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      return true;
    }
    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    if (! is_array($body)) {
      error_log('Nexor SmartCaptcha invalid JSON, allowing request'); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      return true;
    }
    if (($body['status'] ?? '') !== 'ok') {
      return new WP_Error('captcha', 'Не удалось отправить заявку. Попробуйте ещё раз.', array('status' => 400));
    }
    return true;
  }

  public static function create_lead(WP_REST_Request $request): WP_REST_Response|WP_Error
  {
    $valid = self::validate_request($request);
    if (is_wp_error($valid)) return $valid;
    $data = (array) $request->get_json_params();
    if (! empty($data['website'])) return new WP_Error('spam', 'Заявка отклонена.', array('status' => 400));
    $captcha = self::verify_smartcaptcha(sanitize_text_field((string) ($data['smart-token'] ?? '')));
    if (is_wp_error($captcha)) return $captcha;
    $rate = self::rate_limit();
    if (is_wp_error($rate)) return $rate;
    $name   = sanitize_text_field($data['name'] ?? '');
    $phone  = preg_replace('/[^0-9+]/', '', (string) ($data['phone'] ?? ''));
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($name) < 2 || ! preg_match('/^(?:7|8)\d{10}$/', $digits)) {
      return new WP_Error('validation', 'Проверьте имя и российский номер телефона.', array('status' => 422));
    }
    $source  = sanitize_text_field($data['source'] ?? 'Сайт');
    $context = class_exists('Nexor_Enhancements') ? Nexor_Enhancements::resolve_lead_context($data) : array();
    if (is_wp_error($context)) return $context;
    $uuid    = wp_generate_uuid4();
    $payload = array();
    foreach ($data as $key => $value) {
      if (in_array($key, array('website', 'smart-token', 'additional_service_id', 'promotion_id', 'price_row_id'), true)) continue;
      $payload[sanitize_key($key)] = is_scalar($value) ? sanitize_textarea_field((string) $value) : wp_json_encode($value, JSON_UNESCAPED_UNICODE);
    }
    $payload = array_merge($payload, $context);
    $post_id = wp_insert_post(array('post_type' => 'nexor_lead', 'post_status' => 'private', 'post_title' => sprintf('%s — %s', $source, current_time('d.m.Y H:i'))), true);
    if (is_wp_error($post_id)) return new WP_Error('storage', 'Не удалось сохранить заявку. Позвоните нам.', array('status' => 500));
    update_post_meta($post_id, '_nexor_lead_uuid', $uuid);
    update_post_meta($post_id, '_nexor_lead_status', 'new');
    update_post_meta($post_id, '_nexor_lead_data', $payload);
    foreach (array('additional_service_id', 'promotion_id', 'price_row_id', 'additional_snapshot', 'promotion_snapshot', 'price_snapshot') as $context_key) {
      if (isset($context[$context_key])) update_post_meta($post_id, '_nexor_' . $context_key, $context[$context_key]);
    }
    if ($context) update_post_meta($post_id, '_nexor_lead_context_source', $source);
    $sent = self::send_telegram($post_id);
    if (is_wp_error($sent)) {
      update_post_meta($post_id, '_nexor_lead_status', 'telegram_error');
      error_log(sprintf('Nexor Telegram error for lead %d: %s', $post_id, sanitize_text_field($sent->get_error_message()))); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    } else {
      update_post_meta($post_id, '_nexor_lead_status', 'telegram_sent');
    }
    return new WP_REST_Response(array('ok' => true, 'uuid' => $uuid, 'redirect' => add_query_arg('lead', rawurlencode($uuid), home_url('/thank-you/'))), 201);
  }

  private static function send_telegram(int $post_id): true|WP_Error
  {
    $token   = defined('NEXOR_TELEGRAM_BOT_TOKEN') ? NEXOR_TELEGRAM_BOT_TOKEN : '';
    $chat_id = defined('NEXOR_TELEGRAM_CHAT_ID') ? NEXOR_TELEGRAM_CHAT_ID : self::settings()['telegram_chat_id'];
    if (! $token || ! $chat_id) return new WP_Error('telegram_config', 'Telegram не настроен.');
    $data   = (array) get_post_meta($post_id, '_nexor_lead_data', true);
    $labels = array(
      'name'        => '👤 Имя',
      'phone'       => '📞 Телефон',
      'address'     => '📍 Адрес',
      'object_type' => '🏠 Тип объекта',
      'area'        => '📐 Площадь',
      'repair_type' => '🛠 Тип ремонта',
      'project'     => '📁 Проект',
      'source'      => '🔗 Источник',
    );
    $lines = array('📩 <b>Новая заявка Nexor</b>');
    foreach ($labels as $key => $label) {
      if (! isset($data[$key])) {
        continue;
      }
      $value = trim((string) $data[$key]);
      if ('' === $value) {
        continue;
      }
      $lines[] = '<b>' . esc_html($label) . ':</b> ' . esc_html($value);
    }
    $datetime  = get_post_datetime($post_id, 'date', 'gmt');
    $timestamp = $datetime ? $datetime->getTimestamp() : time();
    $lines[]   = '📅 <b>Дата:</b> ' . esc_html(wp_date('d.m.Y', $timestamp));
    $lines[]   = '🕐 <b>Время:</b> ' . esc_html(wp_date('H:i', $timestamp));
    $response = wp_remote_post(
      'https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage',
      array('timeout' => 12, 'body' => array('chat_id' => $chat_id, 'text' => implode("\n", $lines), 'parse_mode' => 'HTML', 'disable_web_page_preview' => 'true'))
    );
    if (is_wp_error($response)) return $response;
    if (200 !== wp_remote_retrieve_response_code($response)) return new WP_Error('telegram_http', 'Telegram вернул HTTP ' . wp_remote_retrieve_response_code($response));
    return true;
  }

  public static function calculate(WP_REST_Request $request): WP_REST_Response|WP_Error
  {
    $valid = self::validate_request($request);
    if (is_wp_error($valid)) return $valid;
    $a = (array) $request->get_json_params();
    $s = self::settings();
    $allowed = array(
      'propertyType' => array('new-apartment', 'secondary-apartment', 'house', 'undecided'),
      'repairFormat' => array('cosmetic', 'capital', 'designer', 'consultation'),
      'currentState' => array('no-finish', 'old-repair', 'partial'),
      'designProject' => array('have', 'yes', 'not-needed'),
      'timeline'     => array('month', '2-3-months', 'planning'),
    );
    foreach ($allowed as $key => $values) if (! in_array($a[$key] ?? '', $values, true)) return new WP_Error('validation', 'Некорректные параметры расчёта.', array('status' => 422));
    $areas = str_starts_with((string) ($a['propertyType'] ?? ''), 'house') || 'house' === ($a['propertyType'] ?? '') ? array('up-to-120' => array(80, 120), '120-200' => array(120, 200), '200-350' => array(200, 350), 'over-350' => array(350, 450)) : array('up-to-40' => array(25, 40), '40-60' => array(40, 60), '60-90' => array(60, 90), '90-120' => array(90, 120), 'over-120' => array(120, 160));
    if (! isset($areas[$a['area'] ?? '']) || 2 !== count((array) ($a['priorities'] ?? array()))) return new WP_Error('validation', 'Заполните все семь шагов.', array('status' => 422));
    [$area_min, $area_max] = $areas[$a['area']];
    $rates = array('cosmetic' => (float)$s['rate_cosmetic'], 'capital' => (float)$s['rate_capital'], 'designer' => (float)$s['rate_designer'], 'consultation' => (float)$s['rate_capital']);
    $pc = array('new-apartment' => 1.05, 'secondary-apartment' => 1.02, 'house' => 1.12, 'undecided' => 1.02);
    $sc = array('no-finish' => 1.05, 'old-repair' => 1.06, 'partial' => 1.03);
    $dc = array('have' => 1.04, 'yes' => 1.06, 'not-needed' => 1.0);
    $coef = $pc[$a['propertyType']] * $sc[$a['currentState']] * $dc[$a['designProject']];
    $round = static fn($v) => round($v / ($v < 3000000 ? 50000 : 100000)) * ($v < 3000000 ? 50000 : 100000);
    $min = $round(round($area_min * (float)$s['area_shift_min']) * $rates[$a['repairFormat']] * $coef * (float)$s['market_factor']);
    $max = $round(round($area_max * (float)$s['area_shift_max']) * $rates[$a['repairFormat']] * $coef * (float)$s['market_factor']);
    $min = max((float)$s['min_budget'], $min);
    $width = ($max - $min) / $min;
    if ($width < .15) $max = $round($min * 1.20);
    elseif ($width > .25) $max = $round($min * 1.25);
    if ($max <= $min) $max = $round($min * 1.25);
    return new WP_REST_Response(array('min' => (int)$min, 'max' => (int)$max, 'formatted' => number_format_i18n($min, 0) . '–' . number_format_i18n($max, 0) . ' ₽'));
  }

  public static function meta_boxes(): void
  {
    add_meta_box('nexor_project_fields', 'Данные проекта', array(__CLASS__, 'project_box'), 'nexor_project', 'normal', 'high');
    add_meta_box('nexor_seo', 'SEO Nexor', array(__CLASS__, 'seo_box'), array('page', 'nexor_project'), 'normal');
    add_meta_box('nexor_lead', 'Данные заявки', array(__CLASS__, 'lead_box'), 'nexor_lead', 'normal', 'high');
  }

  public static function project_box(WP_Post $post): void
  {
    wp_nonce_field('nexor_meta', 'nexor_meta_nonce');
    $fields = array('location' => 'Локация', 'area' => 'Площадь', 'area_display' => 'Отображаемая площадь', 'duration' => 'Срок / формат', 'focal_point' => 'Focal point главного изображения', 'task' => 'Задача', 'works_done' => 'Выполненные работы (по одной в строке)', 'result' => 'Результат', 'key_solutions' => 'Ключевые решения (по одному в строке)', 'features' => 'Особенности (по одной в строке)', 'gallery' => 'Галерея: ID медиа, зона, подпись, alt — JSON');
    foreach ($fields as $key => $label) {
      $value = get_post_meta($post->ID, '_nexor_' . $key, true);
      printf('<p><label><strong>%s</strong><br><textarea name="nexor_%s" rows="%s" style="width:100%%">%s</textarea></label></p>', esc_html($label), esc_attr($key), in_array($key, array('task', 'result'), true) ? 4 : 2, esc_textarea($value));
    }
    printf('<p><label><input type="checkbox" name="nexor_featured" value="1" %s> Рекомендуемый проект</label> &nbsp; <label><input type="checkbox" name="nexor_floor_plan" value="1" %s> Есть планировка</label></p>', checked(get_post_meta($post->ID, '_nexor_featured', true), '1', false), checked(get_post_meta($post->ID, '_nexor_floor_plan', true), '1', false));
  }

  public static function seo_box(WP_Post $post): void
  {
    wp_nonce_field('nexor_meta', 'nexor_meta_nonce');
    foreach (array('title' => 'SEO title', 'description' => 'SEO description', 'canonical' => 'Canonical', 'og_image' => 'Open Graph image') as $key => $label) printf('<p><label><strong>%s</strong><br><input type="text" name="nexor_seo_%s" value="%s" style="width:100%%"></label></p>', esc_html($label), esc_attr($key), esc_attr(get_post_meta($post->ID, '_nexor_seo_' . $key, true)));
    printf('<p><label><input type="checkbox" name="nexor_noindex" value="1" %s> Не индексировать</label></p>', checked(get_post_meta($post->ID, '_nexor_noindex', true), '1', false));
  }

  public static function lead_box(WP_Post $post): void
  {
    $data = (array)get_post_meta($post->ID, '_nexor_lead_data', true);
    echo '<table class="widefat striped"><tbody>';
    foreach ($data as $key => $value) printf('<tr><th>%s</th><td>%s</td></tr>', esc_html($key), nl2br(esc_html((string)$value)));
    echo '</tbody></table>';
    $status = get_post_meta($post->ID, '_nexor_lead_status', true);
    echo '<p><strong>Статус:</strong> ' . esc_html($status) . '</p>';
    $url = wp_nonce_url(admin_url('admin-post.php?action=nexor_resend_lead&lead_id=' . $post->ID), 'nexor_resend_' . $post->ID);
    echo '<p><a class="button button-primary" href="' . esc_url($url) . '">Повторно отправить в Telegram</a></p>';
  }

  public static function save_meta(int $post_id): void
  {
    if (! isset($_POST['nexor_meta_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nexor_meta_nonce'])), 'nexor_meta') || ! current_user_can('edit_post', $post_id) || wp_is_post_revision($post_id)) return;
    foreach (array('location', 'area', 'area_display', 'duration', 'focal_point', 'task', 'works_done', 'result', 'key_solutions', 'features', 'gallery') as $key) if (isset($_POST['nexor_' . $key])) update_post_meta($post_id, '_nexor_' . $key, sanitize_textarea_field(wp_unslash($_POST['nexor_' . $key])));
    foreach (array('title', 'description', 'canonical', 'og_image') as $key) if (isset($_POST['nexor_seo_' . $key])) update_post_meta($post_id, '_nexor_seo_' . $key, sanitize_text_field(wp_unslash($_POST['nexor_seo_' . $key])));
    foreach (array('featured', 'floor_plan', 'noindex') as $key) update_post_meta($post_id, '_nexor_' . $key, isset($_POST['nexor_' . $key]) ? '1' : '0');
  }

  public static function resend_lead(): void
  {
    $id = absint($_GET['lead_id'] ?? 0);
    if (!$id || !current_user_can('edit_post', $id)) wp_die('Недостаточно прав.');
    check_admin_referer('nexor_resend_' . $id);
    $sent = self::send_telegram($id);
    update_post_meta($id, '_nexor_lead_status', is_wp_error($sent) ? 'telegram_error' : 'telegram_sent');
    wp_safe_redirect(get_edit_post_link($id, 'url'));
    exit;
  }

  private static function defaults(): array
  {
    return array('phone_display' => '+7 (926) 083-23-24', 'phone_link' => '+79260832324', 'email' => 'nexor.msk@mail.ru', 'hours' => 'Ежедневно с 9:00 до 21:00', 'region' => 'Москва и Московская область', 'telegram_url' => 'https://t.me/nexor_msk', 'vk_url' => 'https://vk.com/club238015413', 'inn' => '352803113189', 'ogrnip' => '324350000048081', 'telegram_chat_id' => '', 'metrika_id' => '107066852', 'botfaqtor_id' => '172926', 'smartcaptcha_sitekey' => '', 'rate_cosmetic' => '25000', 'rate_capital' => '35000', 'rate_designer' => '50000', 'area_shift_min' => '1.10', 'area_shift_max' => '1.05', 'market_factor' => '0.9', 'min_budget' => '1700000');
  }
  private static function settings(): array
  {
    return wp_parse_args((array)get_option(self::OPTION, array()), self::defaults());
  }
  public static function register_settings(): void
  {
    register_setting('nexor_settings', self::OPTION, array('sanitize_callback' => array(__CLASS__, 'sanitize_settings')));
  }
  public static function sanitize_settings($input): array
  {
    $out = array();
    foreach (self::defaults() as $key => $default) $out[$key] = sanitize_text_field($input[$key] ?? $default);
    return $out;
  }
  public static function admin_menu(): void
  {
    add_options_page('Настройки Nexor', 'Nexor', 'manage_options', 'nexor-settings', array(__CLASS__, 'settings_page'));
  }
  public static function settings_page(): void
  {
    if (!current_user_can('manage_options')) return;
    $s = self::settings();
    $labels = array('phone_display' => 'Телефон (отображение)', 'phone_link' => 'Телефон (ссылка)', 'email' => 'Email', 'hours' => 'Часы работы', 'region' => 'Регион', 'telegram_url' => 'Ссылка Telegram', 'vk_url' => 'Ссылка VK', 'inn' => 'ИНН', 'ogrnip' => 'ОГРНИП', 'telegram_chat_id' => 'Telegram Chat ID', 'metrika_id' => 'Yandex Metrika ID', 'botfaqtor_id' => 'BotFAQtor ID', 'smartcaptcha_sitekey' => 'Yandex SmartCaptcha (ключ клиента)', 'rate_cosmetic' => 'Ставка: косметический', 'rate_capital' => 'Ставка: капитальный', 'rate_designer' => 'Ставка: дизайнерский', 'area_shift_min' => 'Area shift min', 'area_shift_max' => 'Area shift max', 'market_factor' => 'Market adjustment', 'min_budget' => 'Минимальный бюджет');
    echo '<div class="wrap"><h1>Настройки Nexor</h1><form method="post" action="options.php">';
    settings_fields('nexor_settings');
    echo '<table class="form-table">';
    foreach ($labels as $key => $label) printf('<tr><th><label for="%s">%s</label></th><td><input class="regular-text" id="%s" name="%s[%s]" value="%s"></td></tr>', esc_attr($key), esc_html($label), esc_attr($key), esc_attr(self::OPTION), esc_attr($key), esc_attr($s[$key]));
    echo '</table>';
    if (class_exists('Nexor_Enhancements')) Nexor_Enhancements::render_admin_sections();
    submit_button();
    echo '</form><p>Bot token хранится только в <code>wp-config.php</code> как <code>NEXOR_TELEGRAM_BOT_TOKEN</code>. Ключ сервера SmartCaptcha — <code>NEXOR_SMARTCAPTCHA_SERVER_KEY</code> (secret / .env), не в БД.</p></div>';
  }

  public static function analytics(): void
  {
    if (is_admin()) return;
    $s = self::settings();
    if ($s['botfaqtor_id']) printf('<script>window._ab_id_=%d</script><script src="https://cdn.botfaqtor.ru/one.js" async></script>', absint($s['botfaqtor_id']));
    if ($s['metrika_id']) printf('<script>(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();k=e.createElement(t);a=e.getElementsByTagName(t)[0];k.async=1;k.src=r;a.parentNode.insertBefore(k,a)})(window,document,"script","https://mc.yandex.ru/metrika/tag.js?id=%1$d","ym");ym(%1$d,"init",{clickmap:true,trackLinks:true,accurateTrackBounce:true,webvisor:true});</script>', absint($s['metrika_id']));
  }
  public static function schema(): void
  {
    if (is_admin() || is_404()) return;
    $s = self::settings();
    $graph = array(
      array('@type' => 'LocalBusiness', '@id' => home_url('/#business'), 'name' => 'Nexor', 'url' => home_url('/'), 'telephone' => $s['phone_link'], 'email' => $s['email'], 'areaServed' => $s['region'], 'image' => get_theme_file_uri('og-image.jpg'), 'priceRange' => '₽₽₽'),
    );
    if (! is_front_page()) {
      $graph[] = array('@type' => 'BreadcrumbList', 'itemListElement' => array(array('@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => home_url('/')), array('@type' => 'ListItem', 'position' => 2, 'name' => wp_get_document_title(), 'item' => home_url(wp_parse_url(add_query_arg(array()), PHP_URL_PATH)))));
    }
    if (is_singular('nexor_project')) {
      $graph[] = array('@type' => 'CreativeWork', 'name' => get_the_title(), 'url' => get_permalink(), 'about' => 'Ремонт недвижимости', 'provider' => array('@id' => home_url('/#business')));
    } elseif (is_page(array('capital-remont', 'design-remont', 'remont-v-novostroyke', 'cosmetic-remont', 'remont-domov-pod-klyuch', 'remont-kvartir-pod-klyuch'))) {
      $graph[] = array('@type' => 'Service', 'name' => get_the_title(), 'url' => get_permalink(), 'areaServed' => $s['region'], 'provider' => array('@id' => home_url('/#business')));
    } elseif (is_front_page()) {
      $graph[] = array('@type' => 'FAQPage', 'mainEntity' => array(
        array('@type' => 'Question', 'name' => 'Как формируется стоимость ремонта?', 'acceptedAnswer' => array('@type' => 'Answer', 'text' => 'После инженерного замера составляется подробная поэтапная смета, которая фиксируется в договоре.')),
        array('@type' => 'Question', 'name' => 'Кто контролирует качество работ?', 'acceptedAnswer' => array('@type' => 'Answer', 'text' => 'За объект отвечает прораб, а ключевые этапы дополнительно проверяет внутренний контроль качества.')),
      ));
    }
    $schema = array('@context' => 'https://schema.org', '@graph' => $graph);
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
  }
  public static function sitemap_query(array $args, string $post_type): array
  {
    if ('page' === $post_type) {
      $thank = get_page_by_path('thank-you');
      if ($thank) $args['post__not_in'] = array($thank->ID);
    }
    return $args;
  }
  public static function robots(string $output, bool $public): string
  {
    return str_contains($output, 'wp-sitemap.xml') ? $output : $output . "\nSitemap: " . home_url('/wp-sitemap.xml') . "\n";
  }
  public static function lead_columns(array $cols): array
  {
    $cols['nexor_status'] = 'Статус';
    $cols['nexor_phone'] = 'Телефон';
    return $cols;
  }
  public static function lead_column(string $col, int $id): void
  {
    if ('nexor_status' === $col) echo esc_html(get_post_meta($id, '_nexor_lead_status', true));
    if ('nexor_phone' === $col) {
      $d = (array)get_post_meta($id, '_nexor_lead_data', true);
      echo esc_html($d['phone'] ?? '');
    }
  }
  public static function content_replacements(array $r): array
  {
    $s = self::settings();
    return array_merge($r, array('+7 (926) 083-23-24' => $s['phone_display'], '+79260832324' => preg_replace('/\D/', '', $s['phone_link']) ? '+' . preg_replace('/\D/', '', $s['phone_link']) : $s['phone_link'], 'nexor.msk@mail.ru' => $s['email'], 'Ежедневно с 9:00 до 21:00' => $s['hours'], 'Москва и Московская область' => $s['region'], 'https://t.me/nexor_msk' => $s['telegram_url'], 'https://vk.com/club238015413' => $s['vk_url']));
  }
  public static function legal_replacements(string $content): string
  {
    $s = self::settings();
    return str_replace(array('352803113189', '324350000048081'), array($s['inn'], $s['ogrnip']), $content);
  }

  /** Return safe, page-specific defaults used only for missing or legacy duplicate SEO data. */
  private static function page_seo_defaults(): array
  {
    return array(
      'home' => array(
        'title'       => self::LEGACY_SEO_TITLE,
        'description' => self::LEGACY_SEO_DESCRIPTION,
      ),
      'projects' => array(
        'title'       => 'Реализованные проекты ремонта квартир и домов | Nexor',
        'description' => 'Проекты ремонта квартир и частных домов от Nexor: фотографии, задачи, выполненные работы и результаты на реальных объектах.',
      ),
      'capital-remont' => array(
        'title'       => 'Капитальный ремонт квартир в Москве под ключ | Nexor',
        'description' => 'Капитальный ремонт квартир в Москве: инженерные работы, черновая и чистовая отделка, прозрачная смета и контроль качества.',
      ),
      'design-remont' => array(
        'title'       => 'Дизайнерский ремонт квартир и домов в Москве | Nexor',
        'description' => 'Дизайнерский ремонт квартир и домов в Москве: реализация проекта, комплектация, авторские решения и контроль каждого этапа.',
      ),
      'remont-v-novostroyke' => array(
        'title'       => 'Ремонт квартиры в новостройке под ключ в Москве | Nexor',
        'description' => 'Ремонт квартир в новостройках Москвы под ключ: от инженерной подготовки и черновых работ до чистовой отделки и сдачи объекта.',
      ),
      'cosmetic-remont' => array(
        'title'       => 'Косметический ремонт квартир в Москве | Nexor',
        'description' => 'Косметический ремонт квартир в Москве: обновление отделки, понятный состав работ, согласованные сроки и контроль результата.',
      ),
      'remont-domov-pod-klyuch' => array(
        'title'       => 'Ремонт домов под ключ в Москве и Подмосковье | Nexor',
        'description' => 'Комплексный ремонт частных домов под ключ в Москве и Московской области: инженерные системы, отделка и управление работами.',
      ),
      'remont-kvartir-pod-klyuch' => array(
        'title'       => 'Ремонт квартир под ключ в Москве и Подмосковье | Nexor',
        'description' => 'Ремонт квартир под ключ в Москве и Московской области: фиксируем смету, контролируем сроки и выполняем полный комплекс работ.',
      ),
      'privacy' => array(
        'title'       => 'Политика конфиденциальности | Nexor',
        'description' => 'Политика конфиденциальности сайта Nexor и правила работы с персональными данными посетителей.',
      ),
      'consent' => array(
        'title'       => 'Согласие на обработку персональных данных | Nexor',
        'description' => 'Условия согласия пользователя сайта Nexor на обработку персональных данных.',
      ),
    );
  }

  /** Repair only the known migration-era duplicate metadata, preserving later editor changes. */
  public static function repair_legacy_seo(): array
  {
    $updated = array('pages' => 0, 'projects' => 0);
    foreach (self::page_seo_defaults() as $slug => $defaults) {
      $page = get_page_by_path($slug);
      if (! $page) continue;
      $title = (string) get_post_meta($page->ID, '_nexor_seo_title', true);
      $description = (string) get_post_meta($page->ID, '_nexor_seo_description', true);
      if ('home' !== $slug && ('' === $title || self::LEGACY_SEO_TITLE === $title)) {
        update_post_meta($page->ID, '_nexor_seo_title', $defaults['title']);
        $updated['pages']++;
      }
      if ('' === $description || self::LEGACY_SEO_DESCRIPTION === $description) {
        update_post_meta($page->ID, '_nexor_seo_description', $defaults['description']);
      }
    }
    $projects = get_posts(array('post_type' => 'nexor_project', 'post_status' => 'publish', 'posts_per_page' => -1));
    foreach ($projects as $project) {
      $title = (string) get_post_meta($project->ID, '_nexor_seo_title', true);
      $description = (string) get_post_meta($project->ID, '_nexor_seo_description', true);
      $project_title = get_the_title($project);
      if ('' === $title || self::LEGACY_SEO_TITLE === $title) {
        update_post_meta($project->ID, '_nexor_seo_title', $project_title);
        $updated['projects']++;
      }
      if ('' === $description || self::LEGACY_SEO_DESCRIPTION === $description) {
        update_post_meta($project->ID, '_nexor_seo_description', sprintf('Фото и описание проекта «%s»: задача, выполненные работы, ключевые решения и результат ремонта.', $project_title));
      }
    }
    return $updated;
  }

  public static function seed_content(): void
  {
    $dir = get_theme_file_path('content');
    $meta_file = $dir . '/metadata.json';
    if (!is_readable($meta_file)) return;
    $meta = json_decode(file_get_contents($meta_file), true);
    $pages = array('home' => 'Главная', 'projects' => 'Проекты', 'capital-remont' => 'Капитальный ремонт', 'design-remont' => 'Дизайнерский ремонт', 'remont-v-novostroyke' => 'Ремонт в новостройке', 'cosmetic-remont' => 'Косметический ремонт', 'remont-domov-pod-klyuch' => 'Ремонт домов под ключ', 'remont-kvartir-pod-klyuch' => 'Ремонт квартир под ключ', 'privacy' => 'Политика конфиденциальности', 'consent' => 'Согласие на обработку персональных данных');
    $front = 0;
    $seo_defaults = self::page_seo_defaults();
    foreach ($pages as $file => $title) {
      $slug     = 'home' === $file ? 'home' : $file;
      $existing = get_page_by_path($slug);
      $id       = $existing ? $existing->ID : wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => file_get_contents($dir . '/' . $file . '.html')));
      if ('home' === $file) $front = $id;
      if (! $existing && $id && ! is_wp_error($id)) {
        $seo = $seo_defaults[$file] ?? array('title' => $title, 'description' => '');
        update_post_meta($id, '_nexor_seo_title', $seo['title']);
        update_post_meta($id, '_nexor_seo_description', $seo['description']);
      }
    }
    $thank = get_page_by_path('thank-you');
    if (!$thank) {
      $id = wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Спасибо за заявку', 'post_name' => 'thank-you', 'post_content' => '<main class="nexor-thank-you"><div><p class="text-primary text-6xl">✓</p><h1 class="heading-section">Заявка принята</h1><p class="text-muted-foreground">Инженер Nexor свяжется с вами в ближайшее время.</p><p><a href="/">Вернуться на главную</a></p><script>setTimeout(function(){location.href="/"},7000)</script></div></main>'));
      update_post_meta($id, '_nexor_noindex', '1');
    }
    $projects = array('remont-kvartiry-79-9-m2-zhk-yuzhnaya-bittsa-moskva', 'remont-doma-142-m2-kp-pavlovy-ozera', 'remont-kvartiry-35-4-m2-zhk-symbol', 'remont-kvartiry-115-m2-zhk-lucky', 'remont-kvartiry-39-m2-zhk-lyublinskiy-park', 'remont-kvartiry-59-9-m2-zhk-level-yuzhnoportovaya', 'remont-doma-200-m2-kp-anosino-park', 'remont-doma-120-m2-kp-rizhskiy-park', 'remont-kvartiry-94-m2-zhk-oktyabrskoe-pole');
    $catalog_file = __DIR__ . '/project-data.json';
    $catalog      = is_readable($catalog_file) ? json_decode((string) file_get_contents($catalog_file), true) : array();
    $project_data = array();
    foreach ((array) $catalog as $item) if (! empty($item['slug'])) $project_data[sanitize_title($item['slug'])] = $item;
    foreach ($projects as $slug) {
      $key      = 'project-' . $slug;
      $existing = get_page_by_path($slug, OBJECT, 'nexor_project');
      if ($existing) continue;
      $title = sanitize_text_field($project_data[$slug]['title'] ?? ucwords(str_replace('-', ' ', $slug)));
      $id    = wp_insert_post(array('post_type' => 'nexor_project', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => file_get_contents($dir . '/' . $key . '.html')));
      if ($id && ! is_wp_error($id)) {
        update_post_meta($id, '_nexor_seo_title', $title);
        update_post_meta($id, '_nexor_seo_description', sprintf('Фото и описание проекта «%s»: задача, выполненные работы, ключевые решения и результат ремонта.', $title));
        update_post_meta($id, '_nexor_featured', '1');
        wp_set_object_terms($id, str_contains($slug, 'doma') ? 'Дом' : 'Квартира', 'nexor_property_type');
      }
    }
    if ($front) {
      update_option('show_on_front', 'page');
      update_option('page_on_front', $front);
    }
    update_option('permalink_structure', '/%postname%/');
  }

  /** Populate project fields and complete the migrated projects catalogue. */
  public static function migrate_projects(): array
  {
    $data_file = __DIR__ . '/project-data.json';
    $projects  = is_readable($data_file) ? json_decode((string) file_get_contents($data_file), true) : array();
    if (! is_array($projects) || ! $projects) {
      return array('error' => 'Некорректные данные проектов.');
    }

    $attachments = get_posts(
      array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
        'fields'         => 'ids',
      )
    );
    $media_by_name = array();
    foreach ($attachments as $attachment_id) {
      $file = get_attached_file($attachment_id);
      if ($file) {
        $media_by_name[basename($file)] = (int) $attachment_id;
      }
    }

    $updated      = 0;
    $missing      = array();
    $catalog_cards = '';
    foreach ($projects as $project) {
      $slug = sanitize_title($project['slug'] ?? '');
      $post = $slug ? get_page_by_path($slug, OBJECT, 'nexor_project') : null;
      if (! $post) {
        $missing[] = $slug;
        continue;
      }

      $title = sanitize_text_field($project['title'] ?? $post->post_title);
      wp_update_post(array('ID' => $post->ID, 'post_title' => $title));
      $line_fields = array('works_done', 'key_solutions', 'features');
      foreach (array('location', 'area', 'area_display', 'duration', 'focal_point', 'task', 'result') as $key) {
        update_post_meta($post->ID, '_nexor_' . $key, sanitize_textarea_field((string) ($project[$key] ?? '')));
      }
      foreach ($line_fields as $key) {
        $lines = array_map('sanitize_text_field', (array) ($project[$key] ?? array()));
        update_post_meta($post->ID, '_nexor_' . $key, implode("\n", $lines));
      }

      $gallery = array();
      foreach ((array) ($project['gallery'] ?? array()) as $image) {
        $name = basename((string) ($image['src'] ?? ''));
        $id   = $media_by_name[$name] ?? 0;
        if (! $id) {
          $missing[] = $name;
          continue;
        }
        $gallery[] = array(
          'id'      => $id,
          'zone'    => sanitize_text_field($image['zone'] ?? ''),
          'caption' => sanitize_textarea_field($image['caption'] ?? ''),
          'alt'     => (string) get_post_meta($id, '_wp_attachment_image_alt', true),
        );
      }
      update_post_meta($post->ID, '_nexor_gallery', wp_json_encode($gallery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
      update_post_meta($post->ID, '_nexor_featured', ! empty($project['featured']) ? '1' : '0');
      update_post_meta($post->ID, '_nexor_floor_plan', ! empty($project['floor_plan']) ? '1' : '0');
      wp_set_object_terms($post->ID, sanitize_text_field($project['repair_type_display'] ?? ''), 'nexor_repair_type');
      wp_set_object_terms($post->ID, 'house' === ($project['property_type'] ?? '') ? 'Дом' : 'Квартира', 'nexor_property_type');

      $hero_name = basename((string) ($project['hero_image'] ?? ''));
      $hero_id   = $media_by_name[$hero_name] ?? 0;
      if ($hero_id) {
        set_post_thumbnail($post->ID, $hero_id);
      } else {
        $missing[] = $hero_name;
      }
      ++$updated;

      if ($hero_id) {
        $catalog_cards .= sprintf(
          '<a class="group block bg-card rounded-xl border border-border overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer" href="%1$s"><article><div class="relative aspect-[4/3] overflow-hidden"><img data-attachment-id="%2$d" src="%3$s" alt="%4$s" class="w-full h-full object-cover wp-image-%2$d" style="object-position:%5$s" loading="lazy"/><div class="absolute top-4 left-4"><span class="px-3 py-1.5 bg-white/95 backdrop-blur-sm rounded-md text-xs font-medium text-foreground">%6$s</span></div></div><div class="p-5"><h3 class="text-base font-semibold text-foreground mb-2">%7$s</h3><p class="text-sm text-muted-foreground mb-2">%8$s · %9$s · %6$s · %10$s</p><p class="text-xs text-muted-foreground/70 mb-4">Фото ремонта · Реализованный проект · Nexor</p><div class="flex items-center gap-2 text-primary text-sm font-medium group-hover:text-terracotta-dark transition-colors">Открыть кейс →</div></div></article></a>',
          esc_url(get_permalink($post->ID)),
          $hero_id,
          esc_url(wp_get_attachment_url($hero_id)),
          esc_attr($project['seo_alt'] ?? $title),
          esc_attr($project['focal_point'] ?? 'center'),
          esc_html($project['repair_type_display'] ?? ''),
          esc_html($title),
          esc_html($project['location'] ?? ''),
          esc_html($project['area_display'] ?? ''),
          esc_html($project['duration'] ?? '')
        );
      }
    }

    $catalog       = get_page_by_path('projects');
    $catalog_added = 0;
    if ($catalog) {
      $content = $catalog->post_content;
      foreach ($projects as $project) {
        if (str_contains($content, '/projects/' . $project['slug'])) {
          continue;
        }
        $card_start = strpos($catalog_cards, 'href="' . esc_url(get_permalink(get_page_by_path($project['slug'], OBJECT, 'nexor_project'))) . '"');
        if (false === $card_start) {
          continue;
        }
        $card_start = strrpos(substr($catalog_cards, 0, $card_start), '<a class=');
        $card_end   = strpos($catalog_cards, '</a>', $card_start);
        if (false !== $card_start && false !== $card_end) {
          $card = substr($catalog_cards, $card_start, $card_end + 4 - $card_start);
          $marker = '</div><div class="text-center mt-12">';
          if (str_contains($content, $marker)) {
            $content = str_replace($marker, $card . $marker, $content);
            ++$catalog_added;
          }
        }
      }
      if ($content !== $catalog->post_content) {
        wp_update_post(array('ID' => $catalog->ID, 'post_content' => wp_slash($content)));
      }
    }

    return array(
      'updated_projects' => $updated,
      'catalog_added'    => $catalog_added,
      'missing'          => array_values(array_unique(array_filter($missing))),
    );
  }

  /** Replace theme placeholders in stored content with real Media Library URLs. */
  public static function migrate_media(): array
  {
    $map_file = __DIR__ . '/asset-media-map.json';
    $map      = is_readable($map_file) ? json_decode((string) file_get_contents($map_file), true) : array();
    if (! is_array($map)) {
      return array('error' => 'Некорректная карта медиа.');
    }

    $attachments = get_posts(
      array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
        'fields'         => 'ids',
      )
    );
    $by_name = array();
    foreach ($attachments as $attachment_id) {
      $file = get_attached_file($attachment_id);
      if ($file) {
        $by_name[basename($file)] = (int) $attachment_id;
      }
    }

    $resolved = array();
    $missing  = array();
    foreach ($map as $compiled => $original) {
      if (empty($by_name[$original])) {
        $missing[] = $original;
        continue;
      }
      $id  = $by_name[$original];
      $url = wp_get_attachment_url($id);
      if ($url) {
        $resolved[$compiled] = array('id' => $id, 'url' => $url);
      }
    }

    $posts = get_posts(
      array(
        'post_type'      => array('page', 'nexor_project'),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
      )
    );
    $updated_posts = 0;
    $replacements  = 0;
    foreach ($posts as $post) {
      $content = $post->post_content;
      $before  = $content;
      foreach ($resolved as $compiled => $media) {
        $needle = '{{THEME_URI}}/assets/' . $compiled;
        $count  = 0;
        $content = str_replace($needle, $media['url'], $content, $count);
        if (! $count) {
          continue;
        }
        $replacements += $count;
        $url_pattern   = preg_quote($media['url'], '#');
        $content       = preg_replace_callback(
          '#<img\\b[^>]*\\bsrc=([' . "'\"" . '])' . $url_pattern . '\\1[^>]*>#i',
          static function (array $matches) use ($media): string {
            $tag = $matches[0];
            if (preg_match('/\\bclass=([\"\'])(.*?)\\1/i', $tag, $class_match)) {
              if (! preg_match('/(?:^|\\s)wp-image-' . (int) $media['id'] . '(?:\\s|$)/', $class_match[2])) {
                $new_class = trim($class_match[2] . ' wp-image-' . (int) $media['id']);
                $tag       = preg_replace('/\\bclass=([\"\'])(.*?)\\1/i', 'class="' . esc_attr($new_class) . '"', $tag, 1);
              }
            } else {
              $tag = preg_replace('/^<img\\b/i', '<img class="wp-image-' . (int) $media['id'] . '"', $tag, 1);
            }
            if (! str_contains($tag, 'data-attachment-id=')) {
              $tag = preg_replace('/^<img\\b/i', '<img data-attachment-id="' . (int) $media['id'] . '"', $tag, 1);
            }
            if ('' === (string) get_post_meta($media['id'], '_wp_attachment_image_alt', true) && preg_match('/\\balt=([\"\'])(.*?)\\1/i', $tag, $alt_match)) {
              update_post_meta($media['id'], '_wp_attachment_image_alt', sanitize_text_field(html_entity_decode($alt_match[2], ENT_QUOTES, 'UTF-8')));
            }
            return $tag;
          },
          $content
        );
      }
      if ($content !== $before) {
        wp_update_post(array('ID' => $post->ID, 'post_content' => wp_slash($content)));
        ++$updated_posts;
      }
    }

    return array(
      'updated_posts' => $updated_posts,
      'replacements'  => $replacements,
      'mapped'        => count($resolved),
      'missing'       => array_values(array_unique($missing)),
    );
  }
}

require_once __DIR__ . '/class-nexor-enhancements.php';
Nexor_Core::init();
register_activation_hook(__FILE__, array('Nexor_Core', 'activate'));

if (defined('WP_CLI') && WP_CLI) {
  WP_CLI::add_command('nexor seed', static function () {
    Nexor_Core::seed_content();
    flush_rewrite_rules();
    WP_CLI::success('Контент Nexor импортирован.');
  });
  WP_CLI::add_command('nexor migrate-media', static function () {
    $result = Nexor_Core::migrate_media();
    if (isset($result['error'])) WP_CLI::error($result['error']);
    WP_CLI::log(wp_json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    WP_CLI::success('Ссылки на изображения перенесены в Media Library.');
  });
  WP_CLI::add_command('nexor migrate-projects', static function () {
    $result = Nexor_Core::migrate_projects();
    if (isset($result['error'])) WP_CLI::error($result['error']);
    WP_CLI::log(wp_json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    WP_CLI::success('Данные и каталог проектов обновлены.');
  });
  WP_CLI::add_command('nexor enhancements-diagnostic', static function () {
    WP_CLI::log(wp_json_encode(Nexor_Enhancements::diagnostics(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    WP_CLI::success('Диагностика выполнена без изменения данных.');
  });
}
