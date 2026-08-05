<?php
if ( PHP_SAPI !== 'cli' ) exit( 1 );
require '/var/www/html/wp-load.php';

$timeline = array(
	'enabled' => 1,
	'heading' => 'Реальные сроки ремонта без обещаний «за 30 дней»',
	'disclaimer' => 'Точные сроки фиксируем в договоре после замера, составления сметы и согласования объема работ. Они могут измениться только при изменении объема работ или по инициативе заказчика.',
	'rows' => array(
		array( 'id'=>'up-to-50', 'enabled'=>1, 'order'=>10, 'area'=>'До 50 м²', 'new_build'=>'от 45 дней', 'capital'=>'60–90 дней', 'designer'=>'90–120 дней' ),
		array( 'id'=>'50-to-70', 'enabled'=>1, 'order'=>20, 'area'=>'50–70 м²', 'new_build'=>'от 50 дней', 'capital'=>'90–105 дней', 'designer'=>'105–135 дней' ),
		array( 'id'=>'70-to-100', 'enabled'=>1, 'order'=>30, 'area'=>'70–100 м²', 'new_build'=>'от 60 дней', 'capital'=>'от 105 дней', 'designer'=>'от 135 дней' ),
		array( 'id'=>'over-100', 'enabled'=>1, 'order'=>40, 'area'=>'Более 100 м²', 'new_build'=>'Индивидуально', 'capital'=>'Индивидуально', 'designer'=>'Индивидуально' ),
	),
);
update_option( 'nexor_home_timeline', $timeline, false );
update_option( 'nexor_enhancements_schema_version', '1.3.0', false );
echo wp_json_encode( array( 'enabled'=>(int)get_option( 'nexor_home_timeline' )['enabled'], 'rows'=>count( get_option( 'nexor_home_timeline' )['rows'] ) ) );
