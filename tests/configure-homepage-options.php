<?php
if ( PHP_SAPI !== 'cli' ) exit( 1 );
require '/var/www/html/wp-load.php';

$budget = array(
	'enabled' => 1,
	'heading' => 'Как мы держим смету',
	'metric' => '0%',
	'metric_label' => 'отклонение итоговой сметы от первоначальной',
	'rows' => array(
		array( 'id'=>'detailed-measurement', 'enabled'=>1, 'order'=>10, 'title'=>'Считаем детально на замере', 'description'=>'Закладываем работы, которые другие забывают и потом выставляют дополнительно' ),
		array( 'id'=>'fixed-contract', 'enabled'=>1, 'order'=>20, 'title'=>'Фиксируем стоимость и объём', 'description'=>'В договоре до старта работ' ),
		array( 'id'=>'written-approval', 'enabled'=>1, 'order'=>30, 'title'=>'Любые изменения', 'description'=>'Только по вашему письменному согласию' ),
	),
);
$promotions = array(
	'enabled' => 1,
	'heading' => 'Бонусы для клиентов',
	'disclaimer' => 'Бонусы не суммируются и не комбинируются.',
	'featured_enabled' => 1,
	'featured_id' => 'full-design-project-from-5000000',
	'featured_eyebrow' => 'Временное предложение до 31 августа',
	'featured_deadline' => '2026-08-31T23:59:59+03:00',
	'rows' => array(
		array( 'id'=>'visualization-gift-turnkey', 'enabled'=>1, 'order'=>10, 'title'=>'Визуализация в подарок', 'summary'=>'', 'threshold_amount'=>'', 'condition_text'=>'При заключении договора на ремонт под ключ', 'cta_label'=>'Узнать условия', 'legal_text'=>'Бонус действует постоянно.' ),
		array( 'id'=>'works-discount-5-five-days', 'enabled'=>1, 'order'=>20, 'title'=>'Скидка 5% на работы', 'summary'=>'', 'threshold_amount'=>'', 'condition_text'=>'При заключении договора на ремонт под ключ в течение пяти дней после получения сметы', 'cta_label'=>'Узнать условия', 'legal_text'=>'Бонус действует постоянно.' ),
		array( 'id'=>'air-conditioner-from-2000000', 'enabled'=>1, 'order'=>30, 'title'=>'Кондиционер в подарок', 'summary'=>'', 'threshold_amount'=>'2000000', 'condition_text'=>'При заключении договора на ремонт под ключ', 'cta_label'=>'Узнать условия', 'legal_text'=>'Бонус действует постоянно.' ),
		array( 'id'=>'tv-from-3000000', 'enabled'=>1, 'order'=>40, 'title'=>'Телевизор в подарок', 'summary'=>'', 'threshold_amount'=>'3000000', 'condition_text'=>'При заключении договора на ремонт под ключ', 'cta_label'=>'Узнать условия', 'legal_text'=>'Бонус действует постоянно.' ),
		array( 'id'=>'full-design-project-from-5000000', 'enabled'=>1, 'order'=>50, 'title'=>'Дизайн-проект в подарок', 'summary'=>'', 'threshold_amount'=>'5000000', 'condition_text'=>'При заключении договора на ремонт под ключ', 'cta_label'=>'Получить дизайн-проект', 'legal_text'=>'Предложение действует до 31 августа 2026 года.' ),
	),
);

update_option( 'nexor_budget_control', $budget, false );
update_option( 'nexor_promotions', $promotions, false );
update_option( 'nexor_enhancements_schema_version', '1.3.0', false );
echo wp_json_encode( array( 'budget'=>get_option( 'nexor_budget_control' )['enabled'] ?? 0, 'promotions'=>count( array_filter( get_option( 'nexor_promotions' )['rows'] ?? array(), static fn( $row ) => ! empty( $row['enabled'] ) ) ) ) );
