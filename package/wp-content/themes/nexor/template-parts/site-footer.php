<?php
/**
 * Global site footer (single source of truth).
 *
 * @package Nexor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$contacts      = nexor_contact_settings();
$phone_display = $contacts['phone_display'];
$phone_href    = 'tel:' . preg_replace( '/[^\d+]/', '', (string) $contacts['phone_link'] );
$email         = $contacts['email'];
$hours         = $contacts['hours'];
$inn           = $contacts['inn'];
$ogrnip        = $contacts['ogrnip'];
$nav           = nexor_navigation_payload();
$footer_menu   = nexor_menu_payload( 'footer' );
$nav_items     = $footer_menu['items'] ?: ( $nav['primary']['items'] ?? array() );
$services      = $footer_menu['services'] ?: ( $nav['primary']['services'] ?? array() );
$home_url      = home_url( '/' );
$year          = (int) gmdate( 'Y' );

$icon_phone = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>';
$icon_mail  = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>';
$icon_pin   = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path><circle cx="12" cy="10" r="3"></circle></svg>';
$icon_clock = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';
?>
<footer class="nexor-footer">
	<div class="container-nexor">
		<div class="nexor-footer__inner">
			<div class="nexor-footer__main">
				<div class="nexor-footer__brand">
					<a href="<?php echo esc_url( $home_url ); ?>" class="nexor-footer__logo">Nexor</a>
					<p class="nexor-footer__about">
						Системная компания по ремонту квартир и домов в Москве и Московской области. Работаем по договору, фиксируем стоимость и сроки, даём официальную гарантию на выполненные работы.
					</p>
					<ul class="nexor-footer__contacts">
						<li>
							<a href="<?php echo esc_url( $phone_href ); ?>">
								<?php echo $icon_phone; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?>
								<?php echo esc_html( $phone_display ); ?>
							</a>
						</li>
						<?php if ( $email ) : ?>
							<li>
								<a href="<?php echo esc_url( 'mailto:' . $email ); ?>">
									<?php echo $icon_mail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?>
									<?php echo esc_html( $email ); ?>
								</a>
							</li>
						<?php endif; ?>
						<li>
							<span>
								<?php echo $icon_pin; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?>
								Работаем по Москве и Московской области
							</span>
						</li>
						<?php if ( $hours ) : ?>
							<li>
								<span>
									<?php echo $icon_clock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?>
									<?php echo esc_html( $hours ); ?>
								</span>
							</li>
						<?php endif; ?>
					</ul>
				</div>
				<?php if ( $nav_items || $services ) : ?>
					<div class="nexor-footer__menus">
						<?php if ( $nav_items ) : ?>
							<nav class="nexor-footer__nav" aria-label="Навигация">
								<h2>Навигация</h2>
								<ul>
									<?php foreach ( $nav_items as $item ) : ?>
										<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</nav>
						<?php endif; ?>
						<?php if ( $services ) : ?>
							<nav class="nexor-footer__nav" aria-label="Услуги">
								<h2>Услуги</h2>
								<ul>
									<?php foreach ( $services as $item ) : ?>
										<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</nav>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="nexor-footer__bottom">
				<?php if ( $inn || $ogrnip ) : ?>
					<p class="nexor-footer__requisites">ИНН / ОГРН: <?php echo esc_html( $inn ); ?> / <?php echo esc_html( $ogrnip ); ?></p>
				<?php endif; ?>
				<div class="nexor-footer__legal">
					<p>© Nexor, 2017–<?php echo esc_html( (string) $year ); ?>. Все права защищены.</p>
					<a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">Политика конфиденциальности</a>
					<a href="<?php echo esc_url( home_url( '/consent/' ) ); ?>">Согласие на обработку персональных данных</a>
				</div>
			</div>
		</div>
	</div>
</footer>
