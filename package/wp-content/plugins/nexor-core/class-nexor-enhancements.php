<?php

/** Nine-stage Nexor enhancements. */

if (! defined('ABSPATH')) exit;

final class Nexor_Enhancements
{
  private const VERSION = '1.7.0';
  private const VERSION_OPTION = 'nexor_enhancements_schema_version';
  private const PRICES = 'nexor_home_prices';
  private const VIDEO = 'nexor_home_video';
  private const ADDITIONAL = 'nexor_additional_services';
  private const PROMOTIONS = 'nexor_promotions';
  private const BUDGET = 'nexor_budget_control';
  private const TIMELINE = 'nexor_home_timeline';
  private const STAGES = 'nexor_home_stages';
  private const POPUP = 'nexor_exit_intent';
  private const SERVICE_SLUGS = array('remont-kvartir-pod-klyuch', 'capital-remont', 'design-remont', 'remont-v-novostroyke', 'cosmetic-remont', 'remont-domov-pod-klyuch');

  public static function init(): void
  {
    add_action('admin_init', array(__CLASS__, 'register_settings'));
    add_action('admin_init', array(__CLASS__, 'migrate'), 5);
    add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
    add_action('add_meta_boxes', array(__CLASS__, 'add_service_meta_box'));
    add_action('save_post_page', array(__CLASS__, 'save_service_meta'));
    add_action('pre_get_posts', array(__CLASS__, 'search_policy'));
    add_filter('nexor_migrated_content', array(__CLASS__, 'inject_frontend_content'), 20);
  }

  public static function enqueue_admin_assets(string $hook): void
  {
    if ('settings_page_nexor-settings' !== $hook) {
      return;
    }
    wp_enqueue_media();
  }

  private static function additional_seed(): array
  {
    return array(
      array('id' => 'material-selection', 'enabled' => 1, 'order' => 10, 'title' => 'Подбор материалов', 'subtitle' => 'Поможем выбрать материалы без переплат', 'description' => 'Подберем материалы с учетом вашего бюджета, подскажем, где действительно стоит вложиться, а где можно сэкономить без потери качества.', 'included_items' => "Подбор материалов по бюджету.\nПомощь с выбором цветов и фактур.\nКонсультация по напольным покрытиям, дверям, сантехнике и другим материалам.\nПомощь с выбором проверенных производителей.", 'benefit' => 'Экономите время и избегаете лишних расходов.', 'cta_label' => '', 'cta_mode' => 'form', 'cta_target' => ''),
      array('id' => 'designer-consultation', 'enabled' => 1, 'order' => 20, 'title' => 'Консультация дизайнера', 'subtitle' => 'Поможем создать интерьер, в котором будет комфортно жить', 'description' => 'Если нужен взгляд профессионала, дизайнер поможет определиться со стилем, планировкой и цветовыми решениями.', 'included_items' => "Консультация по интерьеру.\nПодбор цветовых решений.\nПланировка и зонирование.\nРекомендации по освещению и эргономике.", 'benefit' => 'Получаете продуманные решения еще до начала ремонта.', 'cta_label' => '', 'cta_mode' => 'form', 'cta_target' => ''),
      array('id' => 'interior-design-project', 'enabled' => 1, 'order' => 30, 'title' => 'Дизайн-проект', 'subtitle' => 'Разработаем дизайн-проект для вашего ремонта', 'description' => 'Если нужен полноценный проект, подготовим всю необходимую документацию для реализации без лишних вопросов на стройке.', 'included_items' => "Обмерный план.\nПланировочные решения.\nКомплект рабочих чертежей.\nРазвертки стен, пола и потолка.\n3D-визуализация будущего интерьера.", 'benefit' => 'Ремонт проходит без лишних переделок и неожиданностей.', 'cta_label' => '', 'cta_mode' => 'form', 'cta_target' => ''),
      array('id' => 'furniture-completion', 'enabled' => 1, 'order' => 40, 'title' => 'Комплектация мебелью', 'subtitle' => 'Поможем подобрать мебель и двери', 'description' => 'Работаем с проверенными партнерами и помогаем подобрать мебель и двери по выгодным условиям.', 'included_items' => "Подбор кухни.\nКорпусная мебель.\nМежкомнатные и входные двери.\nКонтроль доставки и установки.\nПартнерские скидки.", 'benefit' => 'Не тратите время на поиск поставщиков и организацию доставки.', 'cta_label' => '', 'cta_mode' => 'form', 'cta_target' => ''),
      array('id' => 'own-materials-warehouse', 'enabled' => 1, 'order' => 50, 'title' => 'Собственный склад материалов', 'subtitle' => 'Не придется искать материалы самостоятельно', 'description' => 'Используем проверенные материалы и организуем поставку прямо на объект.', 'included_items' => "Быстрая доставка материалов.\nПроверенные поставщики.\nЦены производителей.\nВыкуп неиспользованных материалов после окончания ремонта.", 'benefit' => 'Материалы приезжают вовремя, без задержек ремонта.', 'cta_label' => '', 'cta_mode' => 'form', 'cta_target' => ''),
      array('id' => 'photo-video-reports', 'enabled' => 1, 'order' => 60, 'title' => 'Фото- и видеоотчеты', 'subtitle' => 'Всегда знаете, что происходит на объекте', 'description' => 'Даже если нет возможности приезжать на объект, вы сможете контролировать ход ремонта дистанционно.', 'included_items' => "Регулярные фотоотчеты.\nВидео выполненных этапов.\nИнформация о ходе работ.\nКонтроль каждого этапа ремонта.", 'benefit' => 'Контролируете ремонт из любой точки, даже если не можете приехать на объект.', 'cta_label' => '', 'cta_mode' => 'form', 'cta_target' => ''),
    );
  }

  private static function stages_seed(): array
  {
    return array(
      array('id' => 'consultation', 'enabled' => 1, 'order' => 10, 'title' => 'Консультация и анализ объекта', 'description' => 'Обсуждаем ваши задачи, пожелания и бюджет. Выезжаем на объект, изучаем все особенности и фиксируем исходные данные.', 'image_id' => 0, 'cta_label' => 'Записаться на замер'),
      array('id' => 'measurement', 'enabled' => 1, 'order' => 20, 'title' => 'Выезд инженера и точные замеры', 'description' => 'Проводим профессиональные замеры всех помещений, фиксируем коммуникации и технические особенности.', 'image_id' => 0, 'cta_label' => ''),
      array('id' => 'estimate-contract', 'enabled' => 1, 'order' => 30, 'title' => 'Детальная смета и договор', 'description' => 'Составляем подробную смету с точным расчетом стоимости и сроков. Фиксируем все в договоре — без скрытых условий.', 'image_id' => 0, 'cta_label' => ''),
      array('id' => 'execution', 'enabled' => 1, 'order' => 40, 'title' => 'Реализация под контролем специалистов', 'description' => 'Выполняем работы строго по проекту и графику. Технический надзор, контроль качества и фотоотчёты на каждом этапе.', 'image_id' => 0, 'cta_label' => ''),
      array('id' => 'handover', 'enabled' => 1, 'order' => 50, 'title' => 'Сдача объекта и сопровождение', 'description' => 'Сдаем объект, проводим финальную проверку и передаём вам всю документацию. Остаёмся на связи после завершения проекта.', 'image_id' => 0, 'cta_label' => ''),
    );
  }

  private static function promotion_seed(): array
  {
    return array(
      array('id' => 'visualization-gift-turnkey', 'enabled' => 1, 'order' => 10, 'title' => 'Визуализация в подарок', 'summary' => '', 'threshold_amount' => '', 'condition_text' => 'При заключении договора на ремонт под ключ', 'cta_label' => 'Узнать условия', 'legal_text' => 'Бонус действует постоянно.'),
      array('id' => 'works-discount-5-five-days', 'enabled' => 1, 'order' => 20, 'title' => 'Скидка 5% на работы', 'summary' => '', 'threshold_amount' => '', 'condition_text' => 'При заключении договора на ремонт под ключ в течение пяти дней после получения сметы', 'cta_label' => 'Узнать условия', 'legal_text' => 'Бонус действует постоянно.'),
      array('id' => 'air-conditioner-from-2000000', 'enabled' => 1, 'order' => 30, 'title' => 'Кондиционер в подарок', 'summary' => '', 'threshold_amount' => '2000000', 'condition_text' => 'При заключении договора на ремонт под ключ', 'cta_label' => 'Узнать условия', 'legal_text' => 'Бонус действует постоянно.'),
      array('id' => 'tv-from-3000000', 'enabled' => 1, 'order' => 40, 'title' => 'Телевизор в подарок', 'summary' => '', 'threshold_amount' => '3000000', 'condition_text' => 'При заключении договора на ремонт под ключ', 'cta_label' => 'Узнать условия', 'legal_text' => 'Бонус действует постоянно.'),
      array('id' => 'full-design-project-from-5000000', 'enabled' => 1, 'order' => 50, 'title' => 'Дизайн-проект в подарок', 'summary' => '', 'threshold_amount' => '5000000', 'condition_text' => 'При заключении договора на ремонт под ключ', 'cta_label' => 'Получить дизайн-проект', 'legal_text' => 'Предложение действует до 31 августа 2026 года.'),
    );
  }

  private static function defaults(): array
  {
    return array(
      self::PRICES => array('enabled' => 0, 'heading' => 'Цены и сроки', 'intro' => '', 'disclaimer' => 'Окончательная стоимость и сроки определяются после расчёта и осмотра объекта.', 'rows' => array()),
      self::VIDEO => array('enabled' => 0, 'heading' => 'Видеоматериал', 'text' => '', 'source_type' => 'url', 'attachment_id' => 0, 'url' => '', 'poster_id' => 0, 'transcript' => '', 'caption_attachment_id' => 0),
      self::ADDITIONAL => array('enabled' => 1, 'heading' => 'Дополнительная помощь, которая экономит ваше время', 'intro' => 'Не ограничиваемся только ремонтом. При необходимости поможем с подбором материалов, дизайном, мебелью и другими вопросами, чтобы вам не пришлось искать отдельных специалистов.', 'rows' => self::additional_seed()),
      self::PROMOTIONS => array('enabled' => 1, 'heading' => 'Бонусы для клиентов', 'disclaimer' => 'Бонусы не суммируются и не комбинируются.', 'featured_enabled' => 1, 'featured_id' => 'full-design-project-from-5000000', 'featured_eyebrow' => 'Временное предложение до 31 августа', 'featured_deadline' => '2026-08-31T23:59:59+03:00', 'rows' => self::promotion_seed()),
      self::BUDGET => array('enabled' => 1, 'heading' => 'Как мы держим смету', 'metric' => '0%', 'metric_label' => 'отклонение итоговой сметы от первоначальной', 'rows' => array(
        array('id' => 'detailed-measurement', 'enabled' => 1, 'order' => 10, 'title' => 'Считаем детально на замере', 'description' => 'Закладываем работы, которые другие забывают и потом выставляют дополнительно'),
        array('id' => 'fixed-contract', 'enabled' => 1, 'order' => 20, 'title' => 'Фиксируем стоимость и объём', 'description' => 'В договоре до старта работ'),
        array('id' => 'written-approval', 'enabled' => 1, 'order' => 30, 'title' => 'Любые изменения', 'description' => 'Только по вашему письменному согласию'),
      )),
      self::TIMELINE => array('enabled' => 1, 'heading' => 'Реальные сроки ремонта без обещаний «за 30 дней»', 'disclaimer' => 'Точные сроки фиксируем в договоре после замера, составления сметы и согласования объема работ. Они могут измениться только при изменении объема работ или по инициативе заказчика.', 'rows' => array(
        array('id' => 'up-to-50', 'enabled' => 1, 'order' => 10, 'area' => 'До 50 м²', 'new_build' => 'от 45 дней', 'capital' => '60–90 дней', 'designer' => '90–120 дней'),
        array('id' => '50-to-70', 'enabled' => 1, 'order' => 20, 'area' => '50–70 м²', 'new_build' => 'от 50 дней', 'capital' => '90–105 дней', 'designer' => '105–135 дней'),
        array('id' => '70-to-100', 'enabled' => 1, 'order' => 30, 'area' => '70–100 м²', 'new_build' => 'от 60 дней', 'capital' => 'от 105 дней', 'designer' => 'от 135 дней'),
        array('id' => 'over-100', 'enabled' => 1, 'order' => 40, 'area' => 'Более 100 м²', 'new_build' => 'Индивидуально', 'capital' => 'Индивидуально', 'designer' => 'Индивидуально'),
      )),
      self::STAGES => array(
        'enabled' => 1,
        'eyebrow' => '5 этапов работы Nexor',
        'heading' => 'Как мы делаем ремонт предсказуемым',
        'intro'   => 'Фиксированный бюджет, понятные сроки и полная прозрачность на каждом этапе.',
        'rows'    => self::stages_seed(),
      ),
      self::POPUP => array('enabled' => 0, 'heading' => '', 'body' => '', 'offer_text' => '', 'cta_label' => 'Получить консультацию', 'minimum_delay_seconds' => 20, 'suppression_days' => 7, 'storage_version' => '1'),
    );
  }

  public static function migrate(): void
  {
    if (self::VERSION === (string) get_option(self::VERSION_OPTION, '')) return;
    foreach (self::defaults() as $option => $default) {
      $current = get_option($option, null);
      if (null === $current) add_option($option, $default, '', false);
      elseif (is_array($current)) {
        $merged = wp_parse_args($current, $default);
        if (self::PROMOTIONS === $option) $merged['rows'] = self::merge_seed_rows((array) ($current['rows'] ?? array()));
        if (self::ADDITIONAL === $option) {
          if (empty($current['rows'])) $merged = $default;
          else $merged['rows'] = self::merge_additional_rows((array) $current['rows']);
        }
        if (self::STAGES === $option) {
          if (empty($current['rows'])) $merged = $default;
          else $merged['rows'] = self::merge_stages_rows((array) $current['rows']);
        }
        update_option($option, $merged, false);
      }
    }
    if (class_exists('Nexor_Core')) Nexor_Core::repair_legacy_seo();
    self::seed_navigation();
    update_option(self::VERSION_OPTION, self::VERSION, false);
  }

  /** Create editable WordPress menus only when a registered location is still empty. */
  private static function seed_navigation(): void
  {
    $locations = get_theme_mod('nav_menu_locations', array());
    $services  = array(
      'remont-kvartir-pod-klyuch' => 'Ремонт квартир под ключ',
      'capital-remont'             => 'Капитальный ремонт',
      'design-remont'              => 'Дизайнерский ремонт',
      'remont-v-novostroyke'       => 'Ремонт в новостройке',
      'cosmetic-remont'             => 'Косметический ремонт',
      'remont-domov-pod-klyuch'     => 'Ремонт домов под ключ',
    );
    foreach (array('primary' => 'Главное меню Nexor', 'mobile' => 'Мобильное меню Nexor') as $location => $name) {
      if (! empty($locations[$location])) continue;
      $menu = wp_get_nav_menu_object($name);
      $menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu($name);
      if (is_wp_error($menu_id) || ! $menu_id) continue;
      if (! $menu) {
        $parent = wp_update_nav_menu_item($menu_id, 0, array('menu-item-title' => 'Услуги', 'menu-item-url' => home_url('/'), 'menu-item-status' => 'publish'));
        foreach ($services as $slug => $label) {
          $page = get_page_by_path($slug);
          if ($page) wp_update_nav_menu_item($menu_id, 0, array('menu-item-title' => $label, 'menu-item-object' => 'page', 'menu-item-object-id' => $page->ID, 'menu-item-type' => 'post_type', 'menu-item-parent-id' => $parent, 'menu-item-status' => 'publish'));
        }
        foreach (
          array(
            array('Калькулятор', home_url('/#calculator')),
            array('Проекты', home_url('/projects/')),
            array('О компании', home_url('/#about-company-nexor')),
            array('FAQ', home_url('/#faq')),
          ) as $item
        ) wp_update_nav_menu_item($menu_id, 0, array('menu-item-title' => $item[0], 'menu-item-url' => $item[1], 'menu-item-status' => 'publish'));
      }
      $locations[$location] = $menu_id;
    }
    set_theme_mod('nav_menu_locations', $locations);
  }

  private static function merge_seed_rows(array $rows): array
  {
    $by_id = array();
    foreach ($rows as $row) if (! empty($row['id'])) $by_id[sanitize_key($row['id'])] = $row;
    foreach (self::promotion_seed() as $seed) if (! isset($by_id[$seed['id']])) $by_id[$seed['id']] = $seed;
    return array_values($by_id);
  }
  private static function merge_additional_rows(array $rows): array
  {
    $by_id = array();
    foreach ($rows as $row) if (!empty($row['id'])) $by_id[sanitize_key($row['id'])] = $row;
    foreach (self::additional_seed() as $seed) if (!isset($by_id[$seed['id']])) $by_id[$seed['id']] = $seed;
    return array_values($by_id);
  }
  private static function merge_stages_rows(array $rows): array
  {
    $by_id = array();
    foreach ($rows as $row) {
      if (! empty($row['id'])) {
        $by_id[sanitize_key($row['id'])] = $row;
      }
    }
    foreach (self::stages_seed() as $seed) {
      if (! isset($by_id[$seed['id']])) {
        $by_id[$seed['id']] = $seed;
      }
    }
    return array_values($by_id);
  }

  public static function register_settings(): void
  {
    register_setting('nexor_settings', self::PRICES, array('type' => 'array', 'sanitize_callback' => array(__CLASS__, 'sanitize_prices'), 'default' => self::defaults()[self::PRICES]));
    register_setting('nexor_settings', self::VIDEO, array('type' => 'array', 'sanitize_callback' => array(__CLASS__, 'sanitize_video'), 'default' => self::defaults()[self::VIDEO]));
    register_setting('nexor_settings', self::ADDITIONAL, array('type' => 'array', 'sanitize_callback' => array(__CLASS__, 'sanitize_additional'), 'default' => self::defaults()[self::ADDITIONAL]));
    register_setting('nexor_settings', self::PROMOTIONS, array('type' => 'array', 'sanitize_callback' => array(__CLASS__, 'sanitize_promotions'), 'default' => self::defaults()[self::PROMOTIONS]));
    register_setting('nexor_settings', self::BUDGET, array('type' => 'array', 'sanitize_callback' => array(__CLASS__, 'sanitize_budget'), 'default' => self::defaults()[self::BUDGET]));
    register_setting('nexor_settings', self::TIMELINE, array('type' => 'array', 'sanitize_callback' => array(__CLASS__, 'sanitize_timeline'), 'default' => self::defaults()[self::TIMELINE]));
    register_setting('nexor_settings', self::STAGES, array('type' => 'array', 'sanitize_callback' => array(__CLASS__, 'sanitize_stages'), 'default' => self::defaults()[self::STAGES]));
    register_setting('nexor_settings', self::POPUP, array('type' => 'array', 'sanitize_callback' => array(__CLASS__, 'sanitize_popup'), 'default' => self::defaults()[self::POPUP]));
  }

  private static function clean_rows(array $rows, array $fields): array
  {
    $out = array();
    $seen = array();
    foreach (array_slice($rows, 0, 50) as $row) {
      if (! is_array($row)) continue;
      $id = sanitize_key($row['id'] ?? '');
      if (! $id) $id = 'item-' . substr(wp_generate_uuid4(), 0, 12);
      if (isset($seen[$id])) continue;
      $seen[$id] = true;
      $item = array('id' => $id, 'enabled' => empty($row['enabled']) ? 0 : 1, 'order' => intval($row['order'] ?? 0));
      foreach ($fields as $key => $type) {
        $value = $row[$key] ?? '';
        if ('int' === $type) $item[$key] = absint($value);
        elseif ('url' === $type) $item[$key] = esc_url_raw($value);
        elseif ('textarea' === $type) $item[$key] = sanitize_textarea_field($value);
        else $item[$key] = sanitize_text_field($value);
      }
      $out[] = $item;
    }
    usort($out, static fn($a, $b) => $a['order'] <=> $b['order']);
    return $out;
  }

  public static function sanitize_prices($input): array
  {
    $default = self::defaults()[self::PRICES];
    $input = is_array($input) ? $input : array();
    return array('enabled' => empty($input['enabled']) ? 0 : 1, 'heading' => sanitize_text_field($input['heading'] ?? $default['heading']), 'intro' => sanitize_textarea_field($input['intro'] ?? ''), 'disclaimer' => sanitize_textarea_field($input['disclaimer'] ?? $default['disclaimer']), 'rows' => self::clean_rows((array)($input['rows'] ?? array()), array('service_page_id' => 'int', 'service_label' => 'text', 'price_label' => 'text', 'duration_label' => 'text', 'note' => 'textarea', 'cta_label' => 'text', 'cta_mode' => 'text', 'cta_target' => 'text')));
  }
  public static function sanitize_video($input): array
  {
    $d = self::defaults()[self::VIDEO];
    $input = is_array($input) ? $input : array();
    return array('enabled' => empty($input['enabled']) ? 0 : 1, 'heading' => sanitize_text_field($input['heading'] ?? $d['heading']), 'text' => sanitize_textarea_field($input['text'] ?? ''), 'source_type' => in_array(($input['source_type'] ?? ''), array('attachment', 'url'), true) ? $input['source_type'] : 'url', 'attachment_id' => absint($input['attachment_id'] ?? 0), 'url' => esc_url_raw($input['url'] ?? ''), 'poster_id' => absint($input['poster_id'] ?? 0), 'transcript' => sanitize_textarea_field($input['transcript'] ?? ''), 'caption_attachment_id' => absint($input['caption_attachment_id'] ?? 0));
  }
  public static function sanitize_additional($input): array
  {
    $d = self::defaults()[self::ADDITIONAL];
    $input = is_array($input) ? $input : array();
    return array('enabled' => empty($input['enabled']) ? 0 : 1, 'heading' => sanitize_text_field($input['heading'] ?? $d['heading']), 'intro' => sanitize_textarea_field($input['intro'] ?? $d['intro']), 'rows' => self::clean_rows((array)($input['rows'] ?? array()), array('title' => 'text', 'subtitle' => 'text', 'description' => 'textarea', 'included_items' => 'textarea', 'benefit' => 'textarea', 'cta_label' => 'text', 'cta_mode' => 'text', 'cta_target' => 'url')));
  }
  public static function sanitize_promotions($input): array
  {
    $d = self::defaults()[self::PROMOTIONS];
    $input = is_array($input) ? $input : array();
    $rows = self::clean_rows((array)($input['rows'] ?? array()), array('title' => 'text', 'summary' => 'textarea', 'threshold_amount' => 'int', 'condition_text' => 'textarea', 'cta_label' => 'text', 'legal_text' => 'textarea'));
    return array('enabled' => empty($input['enabled']) ? 0 : 1, 'heading' => sanitize_text_field($input['heading'] ?? $d['heading']), 'disclaimer' => sanitize_textarea_field($input['disclaimer'] ?? $d['disclaimer']), 'featured_enabled' => empty($input['featured_enabled']) ? 0 : 1, 'featured_id' => sanitize_key($input['featured_id'] ?? $d['featured_id']), 'featured_eyebrow' => sanitize_text_field($input['featured_eyebrow'] ?? $d['featured_eyebrow']), 'featured_deadline' => sanitize_text_field($input['featured_deadline'] ?? $d['featured_deadline']), 'rows' => self::merge_seed_rows($rows));
  }
  public static function sanitize_budget($input): array
  {
    $d = self::defaults()[self::BUDGET];
    $input = is_array($input) ? $input : array();
    return array('enabled' => empty($input['enabled']) ? 0 : 1, 'heading' => sanitize_text_field($input['heading'] ?? $d['heading']), 'metric' => sanitize_text_field($input['metric'] ?? $d['metric']), 'metric_label' => sanitize_textarea_field($input['metric_label'] ?? $d['metric_label']), 'rows' => self::clean_rows((array)($input['rows'] ?? array()), array('title' => 'text', 'description' => 'textarea')));
  }
  public static function sanitize_timeline($input): array
  {
    $d = self::defaults()[self::TIMELINE];
    $input = is_array($input) ? $input : array();
    return array('enabled' => empty($input['enabled']) ? 0 : 1, 'heading' => sanitize_text_field($input['heading'] ?? $d['heading']), 'disclaimer' => sanitize_textarea_field($input['disclaimer'] ?? $d['disclaimer']), 'rows' => self::clean_rows((array)($input['rows'] ?? array()), array('area' => 'text', 'new_build' => 'text', 'capital' => 'text', 'designer' => 'text')));
  }
  public static function sanitize_stages($input): array
  {
    $d = self::defaults()[self::STAGES];
    $input = is_array($input) ? $input : array();
    return array(
      'enabled' => empty($input['enabled']) ? 0 : 1,
      'eyebrow' => sanitize_text_field($input['eyebrow'] ?? $d['eyebrow']),
      'heading' => sanitize_text_field($input['heading'] ?? $d['heading']),
      'intro'   => sanitize_textarea_field($input['intro'] ?? $d['intro']),
      'rows'    => self::merge_stages_rows(self::clean_rows((array) ($input['rows'] ?? array()), array('title' => 'text', 'description' => 'textarea', 'image_id' => 'int', 'cta_label' => 'text'))),
    );
  }
  public static function sanitize_popup($input): array
  {
    $d = self::defaults()[self::POPUP];
    $input = is_array($input) ? $input : array();
    return array('enabled' => empty($input['enabled']) ? 0 : 1, 'heading' => sanitize_text_field($input['heading'] ?? ''), 'body' => sanitize_textarea_field($input['body'] ?? ''), 'offer_text' => sanitize_textarea_field($input['offer_text'] ?? ''), 'cta_label' => sanitize_text_field($input['cta_label'] ?? $d['cta_label']), 'minimum_delay_seconds' => min(600, max(5, absint($input['minimum_delay_seconds'] ?? 15))), 'suppression_days' => min(365, max(1, absint($input['suppression_days'] ?? 7))), 'storage_version' => sanitize_key($input['storage_version'] ?? '1'));
  }

  private static function option(string $name): array
  {
    return wp_parse_args((array) get_option($name, array()), self::defaults()[$name]);
  }
  private static function enabled_rows(string $name, array $required): array
  {
    $option = self::option($name);
    if (empty($option['enabled'])) return array();
    $rows = array_filter((array)$option['rows'], static function ($row) use ($required) {
      if (empty($row['enabled'])) return false;
      foreach ($required as $key) if ('' === trim((string)($row[$key] ?? ''))) return false;
      return true;
    });
    usort($rows, static fn($a, $b) => intval($a['order'] ?? 0) <=> intval($b['order'] ?? 0));
    return array_values($rows);
  }

  public static function active_section_links(): array
  {
    $links = array();
    $prices = self::enabled_rows(self::PRICES, array('service_label', 'price_label', 'duration_label'));
    $price_option = self::option(self::PRICES);
    if ($prices && trim((string)$price_option['disclaimer'])) $links[] = array('label' => 'Цены и сроки', 'url' => home_url('/#prices'));
    if (self::enabled_rows(self::ADDITIONAL, array('title', 'description', 'included_items', 'benefit'))) $links[] = array('label' => 'Дополнительные услуги', 'url' => home_url('/#additional-services'));
    if (self::enabled_rows(self::PROMOTIONS, array('title', 'condition_text', 'cta_label', 'legal_text'))) $links[] = array('label' => 'Акции', 'url' => home_url('/#promotions'));
    return $links;
  }

  public static function frontend_config(): array
  {
    $popup = self::option(self::POPUP);
    $valid = !empty($popup['enabled']) && trim($popup['heading']) && trim($popup['body']) && trim($popup['offer_text']);
    return array('searchUrl' => home_url('/'), 'exitIntent' => $valid ? $popup : array('enabled' => 0));
  }

  public static function render_admin_sections(): void
  {
    self::admin_styles();
    self::render_timeline_admin();
    self::render_stages_admin();
    self::render_budget_admin();
    self::render_prices_admin();
    self::render_video_admin();
    self::render_additional_admin();
    self::render_promotions_admin();
    self::render_popup_admin();
    self::admin_script();
  }
  private static function enabled_field(string $option, array $value): void
  {
    printf('<label><input type="checkbox" name="%s[enabled]" value="1" %s> Включить секцию</label>', esc_attr($option), checked(!empty($value['enabled']), true, false));
  }
  private static function text_field(string $option, string $key, string $label, array $value, string $type = 'text'): void
  {
    printf('<p><label><strong>%s</strong><br><input class="regular-text" type="%s" name="%s[%s]" value="%s"></label></p>', esc_html($label), esc_attr($type), esc_attr($option), esc_attr($key), esc_attr($value[$key] ?? ''));
  }
  private static function textarea_field(string $option, string $key, string $label, array $value): void
  {
    printf('<p><label><strong>%s</strong><br><textarea class="large-text" rows="3" name="%s[%s]">%s</textarea></label></p>', esc_html($label), esc_attr($option), esc_attr($key), esc_textarea($value[$key] ?? ''));
  }
  private static function render_rows(string $option, array $rows, array $fields): void
  {
    echo '<div class="nexor-repeater" data-option="' . esc_attr($option) . '"><table class="widefat striped"><thead><tr><th>Вкл.</th><th>ID / порядок</th>';
    foreach ($fields as $key => $field) echo '<th>' . esc_html($field[0]) . '</th>';
    echo '<th>Действия</th></tr></thead><tbody>';
    $render = static function ($row, $index) use ($option, $fields) {
      echo '<tr><td><input type="checkbox" name="' . esc_attr($option) . '[rows][' . esc_attr($index) . '][enabled]" value="1" ' . checked(!empty($row['enabled']), true, false) . '></td><td><input class="nexor-id" name="' . esc_attr($option) . '[rows][' . esc_attr($index) . '][id]" value="' . esc_attr($row['id'] ?? '') . '" placeholder="stable-id"><br><input class="small-text" type="number" name="' . esc_attr($option) . '[rows][' . esc_attr($index) . '][order]" value="' . esc_attr($row['order'] ?? 10) . '"></td>';
      foreach ($fields as $key => $field) {
        $tag = $field[1] ?? 'text';
        echo '<td>';
        if ('textarea' === $tag) {
          echo '<textarea rows="3" name="' . esc_attr($option) . '[rows][' . esc_attr($index) . '][' . esc_attr($key) . ']">' . esc_textarea($row[$key] ?? '') . '</textarea>';
        } elseif ('image' === $tag) {
          $id  = absint($row[$key] ?? 0);
          $url = $id ? (string) wp_get_attachment_image_url($id, 'medium') : '';
          echo '<div class="nexor-media-field">';
          echo '<input type="hidden" class="nexor-media-id" name="' . esc_attr($option) . '[rows][' . esc_attr($index) . '][' . esc_attr($key) . ']" value="' . esc_attr((string) $id) . '">';
          echo '<div class="nexor-media-preview">' . ($url ? '<img src="' . esc_url($url) . '" alt="">' : '') . '</div>';
          echo '<p class="nexor-media-actions"><button type="button" class="button nexor-media-select">Выбрать изображение</button> <button type="button" class="button nexor-media-clear"' . ($id ? '' : ' hidden') . '>Убрать</button></p>';
          echo '</div>';
        } else {
          echo '<input type="' . esc_attr($tag) . '" name="' . esc_attr($option) . '[rows][' . esc_attr($index) . '][' . esc_attr($key) . ']" value="' . esc_attr($row[$key] ?? '') . '">';
        }
        echo '</td>';
      }
      echo '<td><button type="button" class="button nexor-up">↑</button> <button type="button" class="button nexor-down">↓</button> <button type="button" class="button-link-delete nexor-remove">Удалить</button></td></tr>';
    };
    foreach ($rows as $i => $row) $render($row, $i);
    echo '</tbody></table><button type="button" class="button nexor-add">Добавить строку</button><template>';
    $render(array('id' => '', 'enabled' => 0, 'order' => (count($rows) + 1) * 10), '__INDEX__');
    echo '</template></div>';
  }
  private static function render_prices_admin(): void
  {
    $o = self::option(self::PRICES);
    echo '<section class="nexor-admin-section"><h2>Цены и сроки</h2>';
    self::enabled_field(self::PRICES, $o);
    self::text_field(self::PRICES, 'heading', 'Заголовок', $o);
    self::textarea_field(self::PRICES, 'intro', 'Вводный текст', $o);
    self::textarea_field(self::PRICES, 'disclaimer', 'Дисклеймер', $o);
    self::render_rows(self::PRICES, (array)$o['rows'], array('service_page_id' => array('ID страницы', 'number'), 'service_label' => array('Услуга', 'text'), 'price_label' => array('Цена', 'text'), 'duration_label' => array('Срок', 'text'), 'note' => array('Примечание', 'textarea'), 'cta_label' => array('CTA', 'text')));
    echo '</section>';
  }
  private static function render_video_admin(): void
  {
    $o = self::option(self::VIDEO);
    echo '<section class="nexor-admin-section"><h2>Видео</h2>';
    self::enabled_field(self::VIDEO, $o);
    self::text_field(self::VIDEO, 'heading', 'Заголовок', $o);
    self::textarea_field(self::VIDEO, 'text', 'Описание', $o);
    self::text_field(self::VIDEO, 'attachment_id', 'ID видео в медиатеке', $o, 'number');
    self::text_field(self::VIDEO, 'url', 'Разрешённый URL', $o, 'url');
    self::text_field(self::VIDEO, 'poster_id', 'ID постера', $o, 'number');
    self::text_field(self::VIDEO, 'caption_attachment_id', 'ID файла субтитров', $o, 'number');
    self::textarea_field(self::VIDEO, 'transcript', 'Транскрипт', $o);
    echo '</section>';
  }
  private static function render_additional_admin(): void
  {
    $o = self::option(self::ADDITIONAL);
    echo '<section class="nexor-admin-section"><h2>Дополнительные услуги</h2>';
    self::enabled_field(self::ADDITIONAL, $o);
    self::text_field(self::ADDITIONAL, 'heading', 'Заголовок', $o);
    self::textarea_field(self::ADDITIONAL, 'intro', 'Описание под заголовком', $o);
    self::render_rows(self::ADDITIONAL, (array)$o['rows'], array('title' => array('Название', 'text'), 'subtitle' => array('Подзаголовок', 'text'), 'description' => array('Описание', 'textarea'), 'included_items' => array('Что входит — по строке', 'textarea'), 'benefit' => array('Выгода', 'textarea'), 'cta_label' => array('CTA, необязательно', 'text'), 'cta_mode' => array('Режим CTA', 'text'), 'cta_target' => array('Цель CTA', 'text')));
    echo '</section>';
  }
  private static function render_budget_admin(): void
  {
    $o = self::option(self::BUDGET);
    echo '<section class="nexor-admin-section"><h2>Как мы держим смету</h2>';
    self::enabled_field(self::BUDGET, $o);
    self::text_field(self::BUDGET, 'heading', 'Заголовок', $o);
    self::text_field(self::BUDGET, 'metric', 'Показатель', $o);
    self::textarea_field(self::BUDGET, 'metric_label', 'Подпись к показателю', $o);
    self::render_rows(self::BUDGET, (array)$o['rows'], array('title' => array('Заголовок', 'text'), 'description' => array('Описание', 'textarea')));
    echo '</section>';
  }
  private static function render_timeline_admin(): void
  {
    $o = self::option(self::TIMELINE);
    echo '<section class="nexor-admin-section"><h2>Таблица сроков на главной</h2>';
    self::enabled_field(self::TIMELINE, $o);
    self::text_field(self::TIMELINE, 'heading', 'Заголовок', $o);
    self::textarea_field(self::TIMELINE, 'disclaimer', 'Текст под таблицей', $o);
    self::render_rows(self::TIMELINE, (array)$o['rows'], array('area' => array('Площадь объекта', 'text'), 'new_build' => array('Новостройка', 'text'), 'capital' => array('Капитальный ремонт', 'text'), 'designer' => array('Дизайнерский ремонт', 'text')));
    echo '</section>';
  }
  private static function render_stages_admin(): void
  {
    $o = self::option(self::STAGES);
    echo '<section class="nexor-admin-section"><h2>Stages — этапы работы (новая секция)</h2>';
    self::enabled_field(self::STAGES, $o);
    self::text_field(self::STAGES, 'eyebrow', 'Надпись над заголовком', $o);
    self::text_field(self::STAGES, 'heading', 'Заголовок секции', $o);
    self::textarea_field(self::STAGES, 'intro', 'Подзаголовок', $o);
    self::render_rows(
      self::STAGES,
      (array) $o['rows'],
      array(
        'title'       => array('Название этапа', 'text'),
        'description' => array('Описание', 'textarea'),
        'image_id'    => array('Изображение', 'image'),
        'cta_label'   => array('CTA-кнопка (необязательно)', 'text'),
      )
    );
    echo '<p class="description">Секция выводится на главной под блоком «Как мы делаем ремонт предсказуемым». Загрузите изображение для каждого этапа через медиатеку WordPress.</p></section>';
  }
  private static function render_promotions_admin(): void
  {
    $o = self::option(self::PROMOTIONS);
    echo '<section class="nexor-admin-section"><h2>Бонусы для клиентов</h2>';
    self::enabled_field(self::PROMOTIONS, $o);
    self::text_field(self::PROMOTIONS, 'heading', 'Заголовок', $o);
    self::textarea_field(self::PROMOTIONS, 'disclaimer', 'Общее условие', $o);
    printf('<label><input type="checkbox" name="%s[featured_enabled]" value="1" %s> Показывать отдельный временный баннер</label>', esc_attr(self::PROMOTIONS), checked(!empty($o['featured_enabled']), true, false));
    self::text_field(self::PROMOTIONS, 'featured_id', 'ID бонуса для баннера', $o);
    self::text_field(self::PROMOTIONS, 'featured_eyebrow', 'Надпись баннера', $o);
    self::text_field(self::PROMOTIONS, 'featured_deadline', 'Дедлайн ISO 8601', $o);
    self::render_rows(self::PROMOTIONS, (array)$o['rows'], array('title' => array('Название', 'text'), 'summary' => array('Кратко', 'textarea'), 'threshold_amount' => array('Порог, ₽', 'number'), 'condition_text' => array('Условия', 'textarea'), 'cta_label' => array('CTA', 'text'), 'legal_text' => array('Юридический текст', 'textarea')));
    echo '</section>';
  }
  private static function render_popup_admin(): void
  {
    $o = self::option(self::POPUP);
    echo '<section class="nexor-admin-section"><h2>Отложенный popup</h2>';
    self::enabled_field(self::POPUP, $o);
    self::text_field(self::POPUP, 'heading', 'Заголовок', $o);
    self::textarea_field(self::POPUP, 'body', 'Текст', $o);
    self::textarea_field(self::POPUP, 'offer_text', 'Предложение и условия', $o);
    self::text_field(self::POPUP, 'cta_label', 'CTA', $o);
    self::text_field(self::POPUP, 'minimum_delay_seconds', 'Задержка показа, сек.', $o, 'number');
    self::text_field(self::POPUP, 'suppression_days', 'Не показывать после закрытия, дней', $o, 'number');
    self::text_field(self::POPUP, 'storage_version', 'Версия ключа', $o);
    echo '</section>';
  }
  private static function admin_styles(): void
  {
    echo '<style>.nexor-admin-section{margin:24px 0;padding:20px;background:#fff;border:1px solid #ccd0d4}.nexor-repeater{overflow:auto}.nexor-repeater table{min-width:1100px;margin:12px 0}.nexor-repeater td input:not([type=checkbox]),.nexor-repeater td textarea{width:100%}.nexor-repeater .nexor-id{min-width:150px}.nexor-media-field{min-width:160px}.nexor-media-preview{min-height:72px;margin:0 0 8px}.nexor-media-preview img{display:block;max-width:140px;max-height:100px;width:auto;height:auto;border-radius:6px;border:1px solid #c3c4c7;background:#f0f0f1}.nexor-media-actions{margin:0}</style>';
  }
  private static function admin_script(): void
  {
    echo '<script>(function(){let frame;document.addEventListener("click",function(e){const mediaField=e.target.closest(".nexor-media-field");if(mediaField){const input=mediaField.querySelector(".nexor-media-id");const preview=mediaField.querySelector(".nexor-media-preview");const clear=mediaField.querySelector(".nexor-media-clear");if(e.target.closest(".nexor-media-select")){e.preventDefault();if(typeof wp==="undefined"||!wp.media){window.alert("Медиатека WordPress недоступна на этой странице.");return;}if(!frame){frame=wp.media({title:"Выберите изображение этапа",button:{text:"Использовать изображение"},multiple:false,library:{type:"image"}});}frame.off("select");frame.on("select",function(){const attachment=frame.state().get("selection").first().toJSON();const url=(attachment.sizes&&attachment.sizes.medium&&attachment.sizes.medium.url)||attachment.url||"";input.value=String(attachment.id||0);preview.innerHTML=url?\'<img src="\'+url+\'" alt="">\':"";if(clear)clear.hidden=!(parseInt(input.value,10)>0);});frame.open();return;}if(e.target.closest(".nexor-media-clear")){e.preventDefault();input.value="0";preview.innerHTML="";if(clear)clear.hidden=true;return;}}const r=e.target.closest(".nexor-repeater");if(!r)return;const row=e.target.closest("tr");if(e.target.closest(".nexor-add")){const i=r.querySelectorAll("tbody tr").length,html=r.querySelector("template").innerHTML.replaceAll("__INDEX__",i);r.querySelector("tbody").insertAdjacentHTML("beforeend",html);const id=r.querySelector("tbody tr:last-child .nexor-id");id.value="item-"+Date.now().toString(36)+Math.random().toString(36).slice(2,7);}if(e.target.closest(".nexor-remove"))row.remove();if(e.target.closest(".nexor-up")&&row.previousElementSibling)row.parentNode.insertBefore(row,row.previousElementSibling);if(e.target.closest(".nexor-down")&&row.nextElementSibling)row.parentNode.insertBefore(row.nextElementSibling,row);});document.querySelector("form[action=options\\\\.php]")?.addEventListener("submit",function(){document.querySelectorAll(".nexor-repeater").forEach(r=>{const o=r.dataset.option;r.querySelectorAll("tbody tr").forEach((tr,i)=>tr.querySelectorAll("[name]").forEach(el=>el.name=el.name.replace(new RegExp(o+"\\\\[rows\\\\]\\\\[[^\\\\]]+\\\\]"),o+"[rows]["+i+"]")));});});})();</script>';
  }

  public static function add_service_meta_box(): void
  {
    global $post;
    if (!$post instanceof WP_Post || !in_array($post->post_name, self::SERVICE_SLUGS, true)) return;
    add_meta_box('nexor_service_details', 'Структура услуги и перелинковка', array(__CLASS__, 'service_meta_box'), 'page', 'normal', 'default');
  }
  public static function service_meta_box(WP_Post $post): void
  {
    if (!in_array($post->post_name, self::SERVICE_SLUGS, true)) return;
    wp_nonce_field('nexor_service_meta', 'nexor_service_meta_nonce');
    $fields = array('summary' => 'Краткое описание', 'composition' => 'Состав работ (по строке)', 'price_label' => 'Цена / безопасный статус', 'duration_label' => 'Срок / безопасный статус', 'faq' => 'FAQ (вопрос и ответ по строке)', 'related_project_ids' => 'ID связанных проектов через запятую', 'related_content_ids' => 'ID связанных материалов через запятую', 'cta_label' => 'Текст CTA');
    foreach ($fields as $key => $label) printf('<p><label><strong>%s</strong><br><textarea name="nexor_service_%s" rows="%d" style="width:100%%">%s</textarea></label></p>', esc_html($label), esc_attr($key), in_array($key, array('composition', 'faq'), true) ? 4 : 2, esc_textarea(get_post_meta($post->ID, '_nexor_service_' . $key, true)));
  }
  public static function save_service_meta(int $post_id): void
  {
    if (!isset($_POST['nexor_service_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nexor_service_meta_nonce'])), 'nexor_service_meta') || !current_user_can('edit_post', $post_id) || wp_is_post_revision($post_id)) return;
    $post = get_post($post_id);
    if (!$post || !in_array($post->post_name, self::SERVICE_SLUGS, true)) return;
    foreach (array('summary', 'composition', 'price_label', 'duration_label', 'faq', 'related_project_ids', 'related_content_ids', 'cta_label') as $key) if (isset($_POST['nexor_service_' . $key])) update_post_meta($post_id, '_nexor_service_' . $key, sanitize_textarea_field(wp_unslash($_POST['nexor_service_' . $key])));
  }

  public static function search_policy(WP_Query $query): void
  {
    if (is_admin() || !$query->is_main_query() || !$query->is_search()) return;
    $term = trim((string)$query->get('s'));
    $query->set('post_type', array('page', 'post', 'nexor_project'));
    $query->set('post_status', 'publish');
    $query->set('posts_per_page', 10);
    if ('' === $term) $query->set('post__in', array(0));
  }

  private static function insert_before(string $content, string $needle, string $html): string
  {
    $at = strpos($content, $needle);
    if (false === $at) return $content;
    $section = strrpos(substr($content, 0, $at), '<section');
    if (false === $section) $section = $at;
    return substr($content, 0, $section) . $html . substr($content, $section);
  }
  private static function pull_section(string &$content, string $id): string
  {
    $pattern = '/<section\b[^>]*\bid="' . preg_quote($id, '/') . '"[^>]*>.*?<\/section>/s';
    if (!preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE)) return '';
    $section = $match[0][0];
    $at = $match[0][1];
    $content = substr($content, 0, $at) . substr($content, $at + strlen($section));
    return $section;
  }
  private static function home_services(): string
  {
    $images = array(
      'remont-kvartir-pod-klyuch' => 'remont-kvartir-hero-BJWaRctY.webp',
      'capital-remont'             => 'capital-result-CFER7g_G.webp',
      'design-remont'              => 'design-showcase-1-_P8UcYc4.webp',
      'remont-v-novostroyke'       => 'novostroyka-hero-new-BPCkoi_t.webp',
      'remont-domov-pod-klyuch'     => 'remont-doma-142-m2-kp-pavlovy-ozera-kuhnya-gostinaya-DtSHqve7.webp',
    );
    $cards = '';
    $index = 0;
    foreach (self::SERVICE_SLUGS as $slug) {
      if ('cosmetic-remont' === $slug) continue;
      $page = get_page_by_path($slug);
      if (! $page || 'publish' !== $page->post_status) continue;
      $summary = trim((string) get_post_meta($page->ID, '_nexor_service_summary', true));
      $image = get_theme_file_uri('assets/' . $images[$slug]);
      $cards .= sprintf(
        '<article class="nexor-service-card nexor-reveal" style="--service-index:%1$d"><a href="%2$s"><span class="nexor-service-card__media"><img src="%3$s" alt="" loading="lazy" width="900" height="700"></span><span class="nexor-service-card__body"><small>%4$02d</small><h3>%5$s</h3>%6$s<span class="nexor-service-card__link">Подробнее <span aria-hidden="true">&#8599;</span></span></span></a></article>',
        $index,
        esc_url(get_permalink($page)),
        esc_url($image),
        $index + 1,
        esc_html(get_the_title($page)),
        $summary ? '<p>' . esc_html($summary) . '</p>' : ''
      );
      $index++;
    }
    if (! $cards) return '';
    return '<section id="main-services" class="nexor-services-section"><div class="container-nexor"><div class="nexor-section-heading nexor-reveal"><p>Направления работы</p><h2 class="heading-section">Основные услуги</h2></div><div class="nexor-services-editorial">' . $cards . '</div></div></section>';
  }

  private static function timeline_section(): string
  {
    $o = self::option(self::TIMELINE);
    $rows = self::enabled_rows(self::TIMELINE, array('area', 'new_build', 'capital', 'designer'));
    if (! $rows || ! trim((string) $o['heading']) || ! trim((string) $o['disclaimer'])) return '';
    $body = '';
    foreach ($rows as $row) {
      $body .= sprintf(
        '<tr><th scope="row">%s</th><td data-timeline-column="new-build" data-label="Новостройка">%s</td><td data-timeline-column="capital" data-label="Капитальный ремонт">%s</td><td data-timeline-column="designer" data-label="Дизайнерский ремонт">%s</td></tr>',
        esc_html($row['area']),
        esc_html($row['new_build']),
        esc_html($row['capital']),
        esc_html($row['designer'])
      );
    }
    $filters = '<div class="nexor-timeline__filters" aria-label="Показать сроки по типу ремонта"><button type="button" data-timeline-mode="new-build" aria-pressed="true">Новостройка</button><button type="button" data-timeline-mode="capital" aria-pressed="false">Капитальный</button><button type="button" data-timeline-mode="designer" aria-pressed="false">Дизайнерский</button></div>';
    return '<section id="repair-timeline" class="nexor-timeline-section nexor-reveal" data-timeline-active="new-build"><div class="container-nexor"><div class="nexor-section-heading"><p>Сроки по договору</p><h2 class="heading-section">' . esc_html($o['heading']) . '</h2></div>' . $filters . '<div class="nexor-timeline__wrap"><table class="nexor-timeline" aria-describedby="repair-timeline-note"><thead><tr><th scope="col">Площадь объекта</th><th scope="col">Новостройка</th><th scope="col">Капитальный ремонт</th><th scope="col">Дизайнерский ремонт</th></tr></thead><tbody>' . $body . '</tbody></table></div><p id="repair-timeline-note" class="nexor-timeline__note">' . esc_html($o['disclaimer']) . '</p></div></section>';
  }
  private static function prices_section(): string
  {
    $o = self::option(self::PRICES);
    $rows = self::enabled_rows(self::PRICES, array('service_label', 'price_label', 'duration_label'));
    if (!$rows || !trim($o['disclaimer'])) return '';
    $body = '';
    foreach ($rows as $row) {
      $url = absint($row['service_page_id'] ?? 0) && 'publish' === get_post_status(absint($row['service_page_id'])) ? get_permalink(absint($row['service_page_id'])) : '';
      $service = $url ? sprintf('<a href="%s">%s</a>', esc_url($url), esc_html($row['service_label'])) : esc_html($row['service_label']);
      $body .= sprintf('<tr><th scope="row">%s</th><td>%s</td><td>%s</td><td>%s</td><td><button type="button" data-nexor-context-type="price" data-nexor-context-id="%s">%s</button></td></tr>', $service, esc_html($row['price_label']), esc_html($row['duration_label']), esc_html($row['note'] ?? ''), esc_attr($row['id']), esc_html($row['cta_label'] ?: 'Уточнить'));
    }
    $intro = trim($o['intro']) ? '<p>' . esc_html($o['intro']) . '</p>' : '';
    return '<section id="prices" class="nexor-enhancement-section"><div class="container-nexor"><h2 class="heading-section">' . esc_html($o['heading']) . '</h2>' . $intro . '<div class="nexor-table-wrap"><table class="nexor-prices"><thead><tr><th>Услуга</th><th>Стоимость</th><th>Срок</th><th>Примечание</th><th></th></tr></thead><tbody>' . $body . '</tbody></table></div><p class="nexor-disclaimer">' . esc_html($o['disclaimer']) . '</p></div></section>';
  }
  private static function budget_section(): string
  {
    $o = self::option(self::BUDGET);
    $rows = self::enabled_rows(self::BUDGET, array('title', 'description'));
    if (! $rows || ! trim((string) $o['metric']) || ! trim((string) $o['metric_label'])) return '';
    $items = '';
    foreach ($rows as $i => $row) {
      $items .= sprintf(
        '<li class="nexor-budget__item"><button type="button" class="nexor-budget__toggle" aria-expanded="%1$s"><span class="nexor-budget__icon" aria-hidden="true">%2$02d</span><span>%3$s</span><span class="nexor-budget__plus" aria-hidden="true">+</span></button><p>%4$s</p></li>',
        0 === $i ? 'true' : 'false',
        $i + 1,
        esc_html($row['title']),
        esc_html($row['description'])
      );
    }
    return '<section id="budget-control" class="nexor-budget-section"><div class="container-nexor"><div class="nexor-section-heading nexor-reveal"><p>Фиксируем до старта</p><h2 class="heading-section">' . esc_html($o['heading']) . '</h2></div><div class="nexor-budget__grid"><div class="nexor-budget__metric nexor-reveal"><strong>' . esc_html($o['metric']) . '</strong><p>' . esc_html($o['metric_label']) . '</p></div><ol class="nexor-budget__list nexor-reveal">' . $items . '</ol></div></div></section>';
  }

  private static function stage_image_url(array $row): string
  {
    $id = absint($row['image_id'] ?? 0);
    if (! $id) {
      return '';
    }
    $url = wp_get_attachment_image_url($id, 'large');
    return $url ? $url : '';
  }

  private static function stages_section(): string
  {
    $o    = self::option(self::STAGES);
    $rows = self::enabled_rows(self::STAGES, array('title', 'description'));
    if (! $rows || ! trim((string) $o['heading'])) {
      return '';
    }
    $total = count($rows);
    $heading = '<div class="nexor-stage-card__heading">';
    if (trim((string) $o['eyebrow'])) {
      $heading .= '<p class="nexor-stage-card__eyebrow">' . esc_html($o['eyebrow']) . '</p>';
    }
    $heading .= '<h2 class="heading-section">' . esc_html($o['heading']) . '</h2>';
    if (trim((string) $o['intro'])) {
      $heading .= '<p class="nexor-stage-card__intro">' . esc_html($o['intro']) . '</p>';
    }
    $heading .= '</div>';
    $cards = '';
    foreach ($rows as $i => $row) {
      $image  = self::stage_image_url($row);
      $number = sprintf('<span class="nexor-stage-card__number" aria-hidden="true">%02d</span>', $i + 1);
      $illustration = '<div class="nexor-stage-card__illustration"></div>';
      $figure = $image
        ? sprintf(
          '<figure class="nexor-stage-card__media"><img src="%s" alt="%s" loading="lazy" width="640" height="420"></figure>',
          esc_url($image),
          esc_attr($row['title'])
        )
        : '';
      $visual = '<div class="nexor-stage-card__visual">' . $illustration . $number . $figure . '</div>';
      $cta    = trim((string) ($row['cta_label'] ?? ''))
        ? sprintf(
          '<a class="nexor-stage-card__cta" href="%s">%s <span aria-hidden="true">&#8594;</span></a>',
          esc_url(home_url('/#calculator')),
          esc_html($row['cta_label'])
        )
        : '';
      $copy = sprintf(
        '<div class="nexor-stage-card__copy"><p class="nexor-stage-card__index"><span>%02d</span> / <span>%02d</span></p><h3>%s</h3><p class="nexor-stage-card__description">%s</p>%s</div>',
        $i + 1,
        $total,
        esc_html($row['title']),
        esc_html($row['description']),
        $cta
      );
      $main = '<div class="nexor-stage-card__main">' . $visual . $copy . '</div>';
      $progress = $total > 1 ? round(($i / ($total - 1)) * 100, 2) : 100;
      $nav  = sprintf(
        '<nav class="nexor-stage-card__nav" role="tablist" aria-label="Этапы работы" data-active-index="%d" style="--stage-progress:%s%%;grid-template-columns:repeat(%d,minmax(0,1fr))">',
        $i,
        esc_attr((string) $progress),
        $total
      );
      foreach ($rows as $j => $nav_row) {
        $nav .= sprintf(
          '<button type="button" role="tab" aria-selected="%s" aria-controls="stage-%s" data-stage-index="%d"><span>%02d</span><strong>%s</strong></button>',
          $i === $j ? 'true' : 'false',
          esc_attr($nav_row['id']),
          $j,
          $j + 1,
          esc_html($nav_row['title'])
        );
      }
      $nav .= '</nav>';
      $cards .= sprintf(
        '<article class="nexor-stage-card" data-slot="slot-%2$d" id="stage-%1$s" data-stage-index="%6$d">%3$s%4$s%5$s</article>',
        esc_attr($row['id']),
        $i + 1,
        $heading,
        $main,
        $nav,
        $i
      );
    }
    return '<section id="stages" class="nexor-stages-section"><div class="container-fluid"><div class="nexor-stages-grid">' . $cards . '</div></div></section>';
  }

  private static function hero_promotion(): string
  {
    $o = self::option(self::PROMOTIONS);
    if (empty($o['featured_enabled'])) return '';
    $deadline = strtotime((string) ($o['featured_deadline'] ?? ''));
    if (! $deadline || $deadline <= time()) return '';
    $featured_id = sanitize_key($o['featured_id'] ?? '');
    $featured = null;
    foreach (self::enabled_rows(self::PROMOTIONS, array('title', 'condition_text', 'cta_label', 'legal_text')) as $row) {
      if ($featured_id === $row['id']) {
        $featured = $row;
        break;
      }
    }
    if (! $featured) return '';
    return sprintf(
      '<aside class="nexor-hero-promo nexor-reveal" data-nexor-deadline="%1$s" aria-label="%2$s"><p class="nexor-hero-promo__eyebrow">Временное предложение</p><div class="nexor-hero-promo__copy"><span>До 31 августа</span><strong>%3$s</strong></div><div class="nexor-hero-countdown" role="timer" aria-live="off"><span><strong data-days>00</strong><small>дней</small></span><span><strong data-hours>00</strong><small>часов</small></span><span><strong data-minutes>00</strong><small>минут</small></span><span><strong data-seconds>00</strong><small>секунд</small></span></div><button type="button" data-nexor-context-type="promotion" data-nexor-context-id="%4$s">%5$s</button></aside>',
      esc_attr($o['featured_deadline']),
      esc_attr($featured['title']),
      esc_html($featured['title']),
      esc_attr($featured['id']),
      esc_html($featured['cta_label'])
    );
  }
  private static function video_section(): string
  {
    $o = self::option(self::VIDEO);
    if (empty($o['enabled'])) return '';
    $src = $o['attachment_id'] ? wp_get_attachment_url(absint($o['attachment_id'])) : esc_url_raw($o['url']);
    if (!$src) return '';
    $poster = $o['poster_id'] ? wp_get_attachment_image_url(absint($o['poster_id']), 'large') : '';
    $track = $o['caption_attachment_id'] ? wp_get_attachment_url(absint($o['caption_attachment_id'])) : '';
    $media = $o['attachment_id'] ? sprintf('<video controls preload="none" playsinline %s><source src="%s">%s</video>', $poster ? 'poster="' . esc_url($poster) . '"' : '', esc_url($src), $track ? '<track kind="captions" srclang="ru" label="Русские субтитры" src="' . esc_url($track) . '">' : '') : sprintf('<button type="button" class="nexor-video-facade" data-video-url="%s"%s><span>Воспроизвести видео</span></button>', esc_url($src), $poster ? ' style="background-image:url(' . esc_url($poster) . ')"' : '');
    return '<section id="video" class="nexor-enhancement-section"><div class="container-nexor"><h2 class="heading-section">' . esc_html($o['heading']) . '</h2>' . (trim($o['text']) ? '<p>' . esc_html($o['text']) . '</p>' : '') . '<div class="nexor-video">' . $media . '</div>' . (trim($o['transcript']) ? '<details><summary>Текстовая версия</summary><p>' . nl2br(esc_html($o['transcript'])) . '</p></details>' : '') . '</div></section>';
  }
  private static function additional_section(): string
  {
    $o = self::option(self::ADDITIONAL);
    $rows = self::enabled_rows(self::ADDITIONAL, array('title', 'subtitle', 'description', 'included_items', 'benefit'));
    if (! $rows || ! trim((string) $o['heading']) || ! trim((string) $o['intro'])) return '';
    $hotspots = '';
    $panels = '';
    foreach ($rows as $i => $row) {
      $items = '';
      foreach (array_filter(array_map('trim', preg_split('/\R/u', (string) $row['included_items']))) as $item) {
        $items .= '<li>' . esc_html($item) . '</li>';
      }
      $hotspots .= sprintf(
        '<button type="button" class="nexor-service-hotspot" style="--hotspot-index:%1$d" data-service-panel="nexor-service-panel-%2$s" aria-controls="nexor-service-panel-%2$s" aria-expanded="%3$s"><span>%4$02d</span><strong>%5$s</strong></button>',
        $i,
        esc_attr($row['id']),
        0 === $i ? 'true' : 'false',
        $i + 1,
        esc_html($row['title'])
      );
      $panels .= sprintf(
        '<article id="nexor-service-panel-%1$s" class="nexor-service-panel%2$s" data-service-index="%3$d"><button type="button" class="nexor-service-panel__close" aria-label="Закрыть описание">&#215;</button><span class="nexor-service-panel__number">%4$02d</span><h3>%5$s</h3><p class="nexor-service-panel__subtitle">%6$s</p><p class="nexor-service-panel__description">%7$s</p><h4>Что входит:</h4><ul>%8$s</ul><p class="nexor-service-panel__benefit">%9$s</p><button type="button" class="nexor-service-panel__cta" data-nexor-context-type="additional" data-nexor-context-id="%1$s">Обсудить задачу</button></article>',
        esc_attr($row['id']),
        0 === $i ? ' is-active' : '',
        $i,
        $i + 1,
        esc_html($row['title']),
        esc_html($row['subtitle']),
        esc_html($row['description']),
        $items,
        esc_html($row['benefit'])
      );
    }
    $scene = esc_url(get_theme_file_uri('assets/remont-doma-142-m2-kp-pavlovy-ozera-kuhnya-gostinaya-DtSHqve7.webp'));
    return '<section id="additional-services" class="nexor-additional-section"><div class="container-nexor"><div class="nexor-section-heading nexor-reveal"><p>Сервис полного цикла</p><h2 class="heading-section">' . esc_html($o['heading']) . '</h2><div class="nexor-additional__intro">' . esc_html($o['intro']) . '</div></div><div class="nexor-service-desk nexor-reveal"><div class="nexor-service-desk__scene"><img src="' . $scene . '" alt="Готовый интерьер Nexor — интерактивная схема дополнительных услуг" loading="lazy" width="1200" height="800"><div class="nexor-service-desk__shade"></div>' . $hotspots . '<p class="nexor-service-desk__hint">Нажмите на метку, чтобы узнать подробнее</p></div><div class="nexor-service-desk__drawer" aria-live="polite">' . $panels . '</div></div></div></section>';
  }
  private static function cards_section(string $option, string $id, string $type, array $required): string
  {
    $o = self::option($option);
    $rows = self::enabled_rows($option, $required);
    if (!$rows) return '';
    $cards = '';
    $banner = '';
    $featured_id = $type === 'promotion' ? sanitize_key($o['featured_id'] ?? '') : '';
    $featured = null;
    if ($featured_id) foreach ($rows as $row) if ($featured_id === $row['id']) {
      $featured = $row;
      break;
    }
    if ($featured && !empty($o['featured_enabled'])) {
      $deadline = strtotime((string)($o['featured_deadline'] ?? ''));
      if ($deadline && $deadline > time()) {
        $banner = sprintf('<article class="nexor-bonus-banner" data-nexor-deadline="%s"><div class="nexor-bonus-banner__content"><p class="nexor-bonus-banner__eyebrow">%s</p><h3>%s</h3><button type="button" data-nexor-context-type="promotion" data-nexor-context-id="%s">%s</button></div><div class="nexor-bonus-countdown" role="timer" aria-live="off"><p>До окончания предложения</p><div><span><strong data-days>00</strong><small>дней</small></span><span><strong data-hours>00</strong><small>часов</small></span><span><strong data-minutes>00</strong><small>минут</small></span><span><strong data-seconds>00</strong><small>секунд</small></span></div></div></article>', esc_attr($o['featured_deadline']), esc_html($o['featured_eyebrow']), esc_html($featured['title']), esc_attr($featured['id']), esc_html($featured['cta_label']));
      }
    }
    foreach ($rows as $row) {
      if ($type === 'promotion' && $row['id'] === $featured_id) continue;
      $title = $row['title'];
      $description = $row['description'] ?? ($row['summary'] ?? '');
      $amount = $type === 'promotion' && absint($row['threshold_amount'] ?? 0) ? '<p class="nexor-bonus-card__amount">От ' . esc_html(number_format_i18n(absint($row['threshold_amount']), 0)) . ' ₽</p>' : '';
      $details = $type === 'promotion' ? trim(($row['condition_text'] ?? '') . ' ' . ($row['legal_text'] ?? '')) : '';
      $highlight = $type === 'promotion' && $row['id'] === 'visualization-gift-turnkey';
      $badge = $highlight ? '<p class="nexor-bonus-card__badge">Для каждого клиента</p>' : '';
      $cta = $type === 'promotion'
        ? sprintf('<button type="button" class="nexor-bonus-card__details-button" data-nexor-bonus-details data-bonus-id="%s" data-bonus-title="%s" data-bonus-details="%s" data-bonus-cta="%s">Подробнее <span aria-hidden="true">&#8599;</span></button>', esc_attr($row['id']), esc_attr($title), esc_attr($details), esc_attr($row['cta_label']))
        : sprintf('<button type="button" data-nexor-context-type="%s" data-nexor-context-id="%s">%s</button>', esc_attr($type), esc_attr($row['id']), esc_html($row['cta_label']));
      $cards .= sprintf('<article class="nexor-card nexor-bonus-card%s nexor-reveal">%s<span class="nexor-bonus-card__number" aria-hidden="true">%02d</span><h3>%s</h3>%s%s%s%s</article>', $highlight ? ' nexor-card--universal' : '', $badge, count(explode('</article>', $cards)), esc_html($title), trim($description) ? '<p>' . esc_html($description) . '</p>' : '', $amount, trim($details) ? '<p class="nexor-card__details">' . esc_html($details) . '</p>' : '', $cta);
    }
    $disclaimer = $type === 'promotion' && trim((string)($o['disclaimer'] ?? '')) ? '<p class="nexor-promotions__disclaimer">' . esc_html($o['disclaimer']) . '</p>' : '';
    return '<section id="' . esc_attr($id) . '" class="nexor-enhancement-section nexor-promotions-section"><div class="container-nexor"><div class="nexor-section-heading nexor-reveal"><p>Преимущества договора</p><h2 class="heading-section">' . esc_html($o['heading']) . '</h2></div>' . $banner . $disclaimer . '<div class="nexor-card-grid nexor-bonus-mosaic">' . $cards . '</div></div></section>';
  }
  private static function service_details(int $post_id): string
  {
    $values = array();
    foreach (array('summary', 'composition', 'price_label', 'duration_label', 'faq', 'cta_label') as $key) $values[$key] = (string)get_post_meta($post_id, '_nexor_service_' . $key, true);
    $project_ids = array_filter(array_map('absint', explode(',', (string)get_post_meta($post_id, '_nexor_service_related_project_ids', true))));
    $content_ids = array_filter(array_map('absint', explode(',', (string)get_post_meta($post_id, '_nexor_service_related_content_ids', true))));
    $projects = '';
    foreach (array_slice(array_unique(array_merge($project_ids, $content_ids)), 0, 6) as $id) if ('publish' === get_post_status($id) && in_array(get_post_type($id), array('nexor_project', 'post', 'page'), true)) $projects .= sprintf('<li><a href="%s">%s</a></li>', esc_url(get_permalink($id)), esc_html(get_the_title($id)));
    if (!array_filter($values) && !$projects) return '';
    $composition = '';
    foreach (array_filter(array_map('trim', preg_split('/\R/u', $values['composition']))) as $line) $composition .= '<li>' . esc_html($line) . '</li>';
    $faq = '';
    foreach (array_filter(array_map('trim', preg_split('/\R/u', $values['faq']))) as $line) {
      $parts = array_map('trim', explode('|', $line, 2));
      if (count($parts) === 2) $faq .= '<details><summary>' . esc_html($parts[0]) . '</summary><p>' . esc_html($parts[1]) . '</p></details>';
    }
    return '<section class="nexor-enhancement-section nexor-service-details"><div class="container-nexor"><h2 class="heading-section">Информация об услуге</h2>' . ($values['summary'] ? '<p>' . esc_html($values['summary']) . '</p>' : '') . ($values['price_label'] || $values['duration_label'] ? '<p><strong>' . esc_html($values['price_label']) . '</strong> ' . esc_html($values['duration_label']) . '</p>' : '') . ($composition ? '<h3>Состав работ</h3><ul>' . $composition . '</ul>' : '') . ($projects ? '<h3>Связанные проекты и материалы</h3><ul>' . $projects . '</ul>' : '') . ($faq ? '<h3>Вопросы и ответы</h3>' . $faq : '') . ($values['cta_label'] ? '<button type="button" data-nexor-open-form="service">' . esc_html($values['cta_label']) . '</button>' : '') . '</div></section>';
  }
  private static function service_page_shell(string $content): string
  {
    $content = preg_replace_callback(
      '/<main(?:\s+class="([^"]*)")?>/',
      static function (array $matches): string {
        $classes = trim('nexor-service-page ' . ($matches[1] ?? ''));
        return '<main class="' . esc_attr($classes) . '">';
      },
      $content,
      1
    );
    $main_position = strpos($content, '<main');
    $hero_position = false === $main_position ? false : strpos($content, '<section', $main_position);
    if (false === $hero_position) return $content;
    $content = substr_replace($content, '<section class="nexor-service-hero ', $hero_position, strlen('<section class="'));
    $hero_close = strpos($content, '</section>', $hero_position);
    if (false === $hero_close) return $content;
    $eyebrow = '<p class="nexor-service-hero__eyebrow">Nexor · системный ремонт</p>';
    $h1_position = strpos($content, '<h1', $hero_position);
    if (false !== $h1_position && $h1_position < $hero_close) {
      $content = substr_replace($content, $eyebrow, $h1_position, 0);
      $hero_close += strlen($eyebrow);
    }
    $hero_card = '<aside class="nexor-service-hero__card" aria-label="Условия работы Nexor"><p>Ответственность по договору</p><strong>Смета и сроки фиксируются до старта</strong><span>Инженер контролирует ключевые этапы, а вы принимаете и оплачиваете работы поэтапно.</span><a href="' . esc_url(home_url('/#calculator')) . '">Рассчитать бюджет <span aria-hidden="true">&#8599;</span></a></aside>';
    $content = substr_replace($content, $hero_card, $hero_close, 0);
    $hero_close += strlen($hero_card) + strlen('</section>');
    $standards = '<section class="nexor-service-standards" aria-label="Стандарты работы Nexor"><div class="container-nexor"><article><span>01</span><strong>Инженерный замер</strong><p>Фиксируем исходные данные объекта.</p></article><article><span>02</span><strong>Подробная смета</strong><p>Согласовываем стоимость до старта.</p></article><article><span>03</span><strong>Контроль этапов</strong><p>Проверяем технологии и качество.</p></article><article><span>04</span><strong>Гарантия 3 года</strong><p>Закрепляем обязательства в договоре.</p></article></div></section>';
    return substr_replace($content, $standards, $hero_close, 0);
  }
  private static function project_relations(int $project_id): string
  {
    $links = '';
    foreach (self::SERVICE_SLUGS as $slug) {
      $page = get_page_by_path($slug);
      if (!$page) continue;
      $ids = array_filter(array_map('absint', explode(',', (string)get_post_meta($page->ID, '_nexor_service_related_project_ids', true))));
      if (in_array($project_id, $ids, true)) $links .= sprintf('<li><a href="%s">%s</a></li>', esc_url(get_permalink($page)), esc_html(get_the_title($page)));
    }
    return $links ? '<section class="nexor-enhancement-section"><div class="container-nexor"><h2>Связанные услуги</h2><ul>' . $links . '</ul></div></section>' : '';
  }
  public static function inject_frontend_content(string $content): string
  {
    if (is_front_page()) {
      $content = str_replace('<main class="pt-[104px] md:pt-[124px]">', '<main class="nexor-home pt-[104px] md:pt-[124px]">', $content);
      $content = str_replace('<section class="relative min-h-[85vh] flex items-center pt-16 md:pt-20">', '<section class="nexor-home-hero relative min-h-[85vh] flex items-center pt-16 md:pt-20">', $content);
      $hero_image = esc_url(get_theme_file_uri('assets/design-fullwidth-interior-t1Ou1Olm.webp'));
      $content = preg_replace_callback('/(<section class="nexor-home-hero[^>]*>.*?<img\b[^>]*\bsrc=")[^"]+("[^>]*>)/s', static fn($matches) => $matches[1] . $hero_image . $matches[2], $content, 1);
      $content = str_replace('<div class="container-nexor relative z-10 py-28 md:py-36"><div class="max-w-3xl">', '<div class="container-nexor relative z-10 py-28 md:py-36">' . self::hero_promotion() . '<div class="max-w-3xl">', $content);
      $content = str_replace('<section id="cases" class="py-16 md:py-24 bg-card">', '<section id="cases" class="nexor-projects-section py-16 md:py-24 bg-card">', $content);
      $content = str_replace('<section class="py-[120px] md:py-[140px]" style="background-color:#FAF8F6">', '<section id="nexor-system" class="nexor-system-section py-[120px] md:py-[140px]" style="background-color:#FAF8F6">', $content);
      $content = str_replace('<section class="py-[120px] md:py-[140px] bg-card">', '<section id="work-stages" class="nexor-process-section py-[120px] md:py-[140px] bg-card">', $content);
      $content = str_replace('<section class="bg-background py-[120px] md:py-[160px]">', '<section id="before-after" class="nexor-before-after-section bg-background py-[120px] md:py-[160px]">', $content);
      $content = self::insert_before($content, 'id="before-after"', self::stages_section());
      $cases = self::pull_section($content, 'cases');
      $content = self::insert_before($content, 'id="calculator"', self::home_services() . $cases);
      $content = self::insert_before($content, 'Ремонт без неприятных сюрпризов', self::budget_section() . self::prices_section() . self::timeline_section());
      $cluster = self::video_section() . self::additional_section() . self::cards_section(self::PROMOTIONS, 'promotions', 'promotion', array('title', 'condition_text', 'cta_label', 'legal_text'));
      $content = self::insert_before($content, 'id="about-company-nexor"', $cluster);
    } elseif (is_page(self::SERVICE_SLUGS)) {
      $content = self::service_page_shell($content);
      $block = self::service_details(get_queried_object_id());
      if ($block) $content = preg_replace('/<\/main>/', $block . '</main>', $content, 1);
    } elseif (is_singular('nexor_project')) {
      $block = self::project_relations(get_queried_object_id());
      if ($block) $content = preg_replace('/<\/main>/', $block . '</main>', $content, 1);
    }
    return $content;
  }

  public static function resolve_lead_context(array $data): array|WP_Error
  {
    $map = array('additional_service_id' => array(self::ADDITIONAL, 'additional', array('title', 'description', 'benefit')), 'promotion_id' => array(self::PROMOTIONS, 'promotion', array('title', 'condition_text', 'cta_label', 'legal_text')), 'price_row_id' => array(self::PRICES, 'price', array('service_label', 'price_label', 'duration_label')));
    $resolved = array();
    foreach ($map as $key => $definition) {
      $id = sanitize_key($data[$key] ?? '');
      if (!$id) continue;
      $found = null;
      foreach (self::enabled_rows($definition[0], $definition[2]) as $row) if (hash_equals((string)$row['id'], $id)) {
        $found = $row;
        break;
      }
      if (!$found) return new WP_Error('invalid_context', 'Выбранное предложение недоступно. Обновите страницу.', array('status' => 422));
      $snapshot = array('id' => $id, 'type' => $definition[1], 'title' => $found['title'] ?? $found['service_label'], 'details' => $found['condition_text'] ?? ($found['description'] ?? ($found['price_label'] . '; ' . $found['duration_label'])), 'cta_label' => $found['cta_label'] ?? '', 'legal_text' => $found['legal_text'] ?? '', 'threshold_amount' => absint($found['threshold_amount'] ?? 0), 'timestamp' => current_time('c'));
      $resolved[$key] = $id;
      $resolved[$definition[1] . '_snapshot'] = wp_json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return $resolved;
  }
  public static function diagnostics(): array
  {
    return array('schema_version' => (string)get_option(self::VERSION_OPTION, ''), 'budget_enabled' => '' !== self::budget_section(), 'prices_enabled' => count(self::enabled_rows(self::PRICES, array('service_label', 'price_label', 'duration_label'))), 'stages_enabled' => count(self::enabled_rows(self::STAGES, array('title', 'description'))), 'additional_enabled' => count(self::enabled_rows(self::ADDITIONAL, array('title', 'description', 'included_items', 'benefit'))), 'promotions_seeded' => count((array)self::option(self::PROMOTIONS)['rows']), 'promotions_enabled' => count(self::enabled_rows(self::PROMOTIONS, array('title', 'condition_text', 'cta_label', 'legal_text'))), 'video_valid' => '' !== self::video_section(), 'popup_enabled' => !empty(self::frontend_config()['exitIntent']['enabled']));
  }
}

Nexor_Enhancements::init();
