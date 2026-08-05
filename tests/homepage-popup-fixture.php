<?php
declare(strict_types=1);

ob_start();
require __DIR__ . '/render-homepage-redesign-fixture.php';
$html = (string) ob_get_clean();
$settings = "exitIntent:{enabled:true,heading:'Хотите получить скидку сегодня?',body:'Оставьте контакты',offer_text:'Условия уточнит специалист',cta_label:'Получить консультацию',minimum_delay_seconds:5,suppression_days:7,storage_version:'full-page'}";
$html = str_replace( 'exitIntent:{enabled:false}', $settings, $html );
$project_root = dirname( __DIR__ );
echo str_replace( 'file://' . $project_root . '/', '/', $html );
