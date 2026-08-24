<?php
/** Render the production homepage composition with deterministic WordPress stubs. */

define( 'ABSPATH', __DIR__ );

function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function esc_url_raw( $value ) { return (string) $value; }
function absint( $value ) { return abs( (int) $value ); }
function wp_generate_uuid4() { return '12345678-1234-1234-1234-123456789abc'; }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function home_url( $path = '/' ) { return 'https://nexorpro.ru' . $path; }
function current_time( $format ) { return '2026-07-30T20:00:00+03:00'; }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function is_admin() { return false; }
function is_front_page() { return true; }
function is_page( $value = null ) { return false; }
function is_singular( $value = '' ) { return false; }
function get_queried_object_id() { return 0; }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES ); }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES ); }
function esc_url( $value ) { return esc_attr( $value ); }
function number_format_i18n( $value, $decimals = 0 ) { return number_format( $value, $decimals, '.', ' ' ); }
function wp_get_attachment_url( $id ) { return ''; }
function wp_get_attachment_image_url( $id, $size ) { return ''; }
function get_post_status( $id ) { return 'publish'; }
function get_post_type( $id ) { return 'nexor_project'; }
function get_the_title( $post ) { return is_object( $post ) ? $post->post_title : 'Проект ' . $post; }
function get_permalink( $post ) { return 'https://nexorpro.ru/' . ( is_object( $post ) ? $post->post_name : 'project-' . $post ) . '/'; }
function get_post_meta( $id, $key, $single = true ) {
	$summaries = array(
		10 => 'Полный цикл работ: от подготовки основания до финишной отделки и сдачи объекта.',
		11 => 'Комплексное обновление инженерии, планировки и отделки с контролем каждого этапа.',
		12 => 'Реализация индивидуального интерьера по проекту с вниманием к каждой детали.',
		13 => 'Ремонт новой квартиры с продуманной инженерией, понятной сметой и фиксированными сроками.',
		15 => 'Ремонт частного дома с единым управлением отделочными и инженерными работами.',
	);
	return '_nexor_service_summary' === $key ? ( $summaries[ $id ] ?? '' ) : '';
}
function get_page_by_path( $slug ) {
	$pages = array(
		'remont-kvartir-pod-klyuch' => array( 10, 'Ремонт квартир под ключ' ),
		'capital-remont'             => array( 11, 'Капитальный ремонт' ),
		'design-remont'              => array( 12, 'Дизайнерский ремонт' ),
		'remont-v-novostroyke'       => array( 13, 'Ремонт в новостройке' ),
		'cosmetic-remont'             => array( 14, 'Косметический ремонт' ),
		'remont-domov-pod-klyuch'     => array( 15, 'Ремонт домов под ключ' ),
	);
	if ( ! isset( $pages[ $slug ] ) ) return null;
	return (object) array( 'ID' => $pages[ $slug ][0], 'post_status' => 'publish', 'post_title' => $pages[ $slug ][1], 'post_name' => $slug );
}

$root = dirname( __DIR__ );
$theme_path = $root . '/package/wp-content/themes/nexor';
function get_theme_file_uri( $path = '' ) {
	global $theme_path;
	return 'file://' . $theme_path . '/' . ltrim( $path, '/' );
}
function get_template_directory_uri() {
	global $theme_path;
	return 'file://' . $theme_path;
}
function nexor_render_home_hero_section( array $copy = array() ): string {
	global $theme_path;
	$args = $copy;
	ob_start();
	include $theme_path . '/template-parts/home-hero-section.php';
	return (string) ob_get_clean();
}
function nexor_render_home_about_section( array $copy = array() ): string {
	global $theme_path;
	$args = $copy;
	ob_start();
	include $theme_path . '/template-parts/home-about-section.php';
	return (string) ob_get_clean();
}

$additional = array(
	array( 'id'=>'material-selection', 'enabled'=>1, 'order'=>10, 'title'=>'Подбор материалов', 'subtitle'=>'Поможем выбрать материалы без переплат', 'description'=>'Подберем материалы с учетом вашего бюджета, подскажем, где действительно стоит вложиться, а где можно сэкономить без потери качества.', 'included_items'=>"Подбор материалов по бюджету.\nПомощь с выбором цветов и фактур.\nКонсультация по напольным покрытиям, дверям, сантехнике и другим материалам.\nПомощь с выбором проверенных производителей.", 'benefit'=>'Экономите время и избегаете лишних расходов.' ),
	array( 'id'=>'designer-consultation', 'enabled'=>1, 'order'=>20, 'title'=>'Консультация дизайнера', 'subtitle'=>'Поможем создать интерьер, в котором будет комфортно жить', 'description'=>'Дизайнер поможет определиться со стилем, планировкой и цветовыми решениями.', 'included_items'=>"Консультация по интерьеру.\nПодбор цветовых решений.\nПланировка и зонирование.\nРекомендации по освещению и эргономике.", 'benefit'=>'Получаете продуманные решения еще до начала ремонта.' ),
	array( 'id'=>'interior-design-project', 'enabled'=>1, 'order'=>30, 'title'=>'Дизайн-проект', 'subtitle'=>'Разработаем дизайн-проект для вашего ремонта', 'description'=>'Подготовим необходимую документацию для реализации без лишних вопросов на стройке.', 'included_items'=>"Обмерный план.\nПланировочные решения.\nКомплект рабочих чертежей.\nРазвертки стен, пола и потолка.\n3D-визуализация будущего интерьера.", 'benefit'=>'Ремонт проходит без лишних переделок и неожиданностей.' ),
	array( 'id'=>'furniture-completion', 'enabled'=>1, 'order'=>40, 'title'=>'Комплектация мебелью', 'subtitle'=>'Поможем подобрать мебель и двери', 'description'=>'Работаем с проверенными партнерами и помогаем подобрать мебель и двери по выгодным условиям.', 'included_items'=>"Подбор кухни.\nКорпусная мебель.\nМежкомнатные и входные двери.\nКонтроль доставки и установки.\nПартнерские скидки.", 'benefit'=>'Не тратите время на поиск поставщиков и организацию доставки.' ),
	array( 'id'=>'own-materials-warehouse', 'enabled'=>1, 'order'=>50, 'title'=>'Собственный склад материалов', 'subtitle'=>'Не придется искать материалы самостоятельно', 'description'=>'Используем проверенные материалы и организуем поставку прямо на объект.', 'included_items'=>"Быстрая доставка материалов.\nПроверенные поставщики.\nЦены производителей.\nВыкуп неиспользованных материалов после окончания ремонта.", 'benefit'=>'Материалы приезжают вовремя, без задержек ремонта.' ),
	array( 'id'=>'photo-video-reports', 'enabled'=>1, 'order'=>60, 'title'=>'Фото- и видеоотчеты', 'subtitle'=>'Всегда знаете, что происходит на объекте', 'description'=>'Контролируйте ход ремонта дистанционно, даже если нет возможности приезжать на объект.', 'included_items'=>"Регулярные фотоотчеты.\nВидео выполненных этапов.\nИнформация о ходе работ.\nКонтроль каждого этапа ремонта.", 'benefit'=>'Контролируете ремонт из любой точки, даже если не можете приехать на объект.' ),
);
$promotions = array(
	array( 'id'=>'visualization-gift-turnkey', 'enabled'=>1, 'order'=>10, 'title'=>'Визуализация в подарок', 'summary'=>'', 'threshold_amount'=>'', 'condition_text'=>'При заключении договора на ремонт под ключ', 'cta_label'=>'Узнать условия', 'legal_text'=>'Бонус действует постоянно.' ),
	array( 'id'=>'works-discount-5-five-days', 'enabled'=>1, 'order'=>20, 'title'=>'Скидка 5% на работы', 'summary'=>'', 'threshold_amount'=>'', 'condition_text'=>'При заключении договора на ремонт под ключ в течение пяти дней после получения сметы', 'cta_label'=>'Узнать условия', 'legal_text'=>'Бонус действует постоянно.' ),
	array( 'id'=>'air-conditioner-from-2000000', 'enabled'=>1, 'order'=>30, 'title'=>'Кондиционер в подарок', 'summary'=>'', 'threshold_amount'=>'2000000', 'condition_text'=>'При заключении договора на ремонт под ключ', 'cta_label'=>'Узнать условия', 'legal_text'=>'Бонус действует постоянно.' ),
	array( 'id'=>'tv-from-3000000', 'enabled'=>1, 'order'=>40, 'title'=>'Телевизор в подарок', 'summary'=>'', 'threshold_amount'=>'3000000', 'condition_text'=>'При заключении договора на ремонт под ключ', 'cta_label'=>'Узнать условия', 'legal_text'=>'Бонус действует постоянно.' ),
	array( 'id'=>'full-design-project-from-5000000', 'enabled'=>1, 'order'=>50, 'title'=>'Дизайн-проект в подарок', 'summary'=>'', 'threshold_amount'=>'5000000', 'condition_text'=>'При заключении договора на ремонт под ключ', 'cta_label'=>'Получить дизайн-проект', 'legal_text'=>'Предложение действует до 31 августа 2026 года.' ),
);
$GLOBALS['options'] = array(
	'nexor_home_prices' => array( 'enabled'=>0, 'rows'=>array() ),
	'nexor_home_video' => array( 'enabled'=>0 ),
	'nexor_additional_services' => array( 'enabled'=>1, 'heading'=>'Дополнительная помощь, которая экономит ваше время', 'intro'=>'Не ограничиваемся только ремонтом. При необходимости поможем с подбором материалов, дизайном, мебелью и другими вопросами, чтобы вам не пришлось искать отдельных специалистов.', 'rows'=>$additional ),
	'nexor_promotions' => array( 'enabled'=>1, 'heading'=>'Бонусы для клиентов', 'disclaimer'=>'Бонусы не суммируются и не комбинируются.', 'featured_enabled'=>1, 'featured_id'=>'full-design-project-from-5000000', 'featured_eyebrow'=>'Временное предложение до 31 августа', 'featured_deadline'=>'2026-08-31T23:59:59+03:00', 'rows'=>$promotions ),
	'nexor_budget_control' => array( 'enabled'=>1, 'heading'=>'Как нам это удаётся?', 'metric'=>'0%', 'metric_label'=>'отклонение итоговой сметы от первоначальной', 'metric_note'=>'За последние реализованные проекты', 'rows'=>array(
		array( 'id'=>'detailed-measurement', 'enabled'=>1, 'order'=>10, 'title'=>'Считаем детально', 'description'=>'Закладываем работы, которые другие забывают и потом выставляют дополнительно.' ),
		array( 'id'=>'fixed-contract', 'enabled'=>1, 'order'=>20, 'title'=>'Фиксируем стоимость и объём', 'description'=>'В договоре до старта работ — никаких устных договорённостей.' ),
		array( 'id'=>'written-approval', 'enabled'=>1, 'order'=>30, 'title'=>'Любые изменения — только с согласия', 'description'=>'Только по вашему письменному согласию. Вы контролируете бюджет.' ),
	) ),
	'nexor_home_timeline' => array( 'enabled'=>1, 'heading'=>'Реальные сроки ремонта без обещаний «за 30 дней»', 'disclaimer'=>'Точные сроки фиксируем в договоре после замера, составления сметы и согласования объема работ. Они могут измениться только при изменении объема работ или по инициативе заказчика.', 'rows'=>array(
		array( 'id'=>'up-to-50', 'enabled'=>1, 'order'=>10, 'area'=>'До 50 м²', 'new_build'=>'от 45 дней', 'capital'=>'60–90 дней', 'designer'=>'90–120 дней' ),
		array( 'id'=>'50-to-70', 'enabled'=>1, 'order'=>20, 'area'=>'50–70 м²', 'new_build'=>'от 50 дней', 'capital'=>'90–105 дней', 'designer'=>'105–135 дней' ),
		array( 'id'=>'70-to-100', 'enabled'=>1, 'order'=>30, 'area'=>'70–100 м²', 'new_build'=>'от 60 дней', 'capital'=>'от 105 дней', 'designer'=>'от 135 дней' ),
		array( 'id'=>'over-100', 'enabled'=>1, 'order'=>40, 'area'=>'Более 100 м²', 'new_build'=>'Индивидуально', 'capital'=>'Индивидуально', 'designer'=>'Индивидуально' ),
	) ),
	'nexor_exit_intent' => array( 'enabled'=>0 ),
);
function get_option( $key, $default = array() ) { return $GLOBALS['options'][ $key ] ?? $default; }

require $root . '/package/wp-content/plugins/nexor-core/class-nexor-enhancements.php';
$content = file_get_contents( $theme_path . '/content/home.html' );
$content = Nexor_Enhancements::inject_frontend_content( $content );
$content = str_replace( '{{THEME_URI}}', 'file://' . $theme_path, $content );
$css_base = 'file://' . $theme_path . '/assets/index-DfWs8OlI.css';
$css_theme = 'file://' . $theme_path . '/assets/nexor.css';
$js_theme = 'file://' . $theme_path . '/assets/nexor.js';
?><!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Nexor homepage redesign fixture</title><link rel="stylesheet" href="<?php echo esc_attr( $css_base ); ?>"><link rel="stylesheet" href="<?php echo esc_attr( $css_theme ); ?>"></head><body>
<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<script>
window.NexorSettings={restUrl:'https://fixture.test/nexor/v1/',nonce:'fixture',thankYou:'#thank-you',privacy:'#privacy',consent:'#consent',navigation:{},enhancements:{exitIntent:{enabled:false}}};
const fixtureFetch=window.fetch.bind(window);window.fetch=(url,options)=>String(url).includes('/calculate')?Promise.resolve(new Response(JSON.stringify({formatted:'2 350 000–2 950 000 ₽'}),{status:200,headers:{'Content-Type':'application/json'}})):fixtureFetch(url,options);
</script><script src="<?php echo esc_attr( $js_theme ); ?>"></script></body></html>
