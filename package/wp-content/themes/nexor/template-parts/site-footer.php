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
$link_class    = 'text-muted-foreground hover:text-primary transition-colors duration-200';
$legal_class   = 'text-sm text-muted-foreground hover:text-primary transition-colors duration-200';
$year          = (int) gmdate( 'Y' );
?>
<footer class="bg-card border-t border-border">
	<div class="container-nexor py-16">
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
			<div class="lg:col-span-2">
				<a href="<?php echo esc_url( $home_url ); ?>" class="flex items-center gap-2.5 mb-5"><span class="text-2xl font-bold text-foreground tracking-tight">Nexor</span></a>
				<p class="text-muted-foreground max-w-md mb-6 leading-relaxed">
					Системная компания по ремонту квартир и домов в Москве и Московской области. Работаем по договору, фиксируем стоимость и сроки, даём официальную гарантию на выполненные работы.
				</p>
				<div class="space-y-3">
					<a href="<?php echo esc_url( $phone_href ); ?>" class="flex items-center gap-3 text-foreground hover:text-primary transition-colors duration-200">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-5 h-5 text-primary" aria-hidden="true">
							<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
						</svg>
						<?php echo esc_html( $phone_display ); ?>
					</a>
					<?php if ( $email ) : ?>
						<a href="<?php echo esc_url( 'mailto:' . $email ); ?>" class="flex items-center gap-3 text-foreground hover:text-primary transition-colors duration-200">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail w-5 h-5 text-primary" aria-hidden="true">
								<rect width="20" height="16" x="2" y="4" rx="2"></rect>
								<path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
							</svg>
							<?php echo esc_html( $email ); ?>
						</a>
					<?php endif; ?>
					<div class="flex items-start gap-3 text-muted-foreground">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin w-5 h-5 text-primary flex-shrink-0 mt-0.5" aria-hidden="true">
							<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
							<circle cx="12" cy="10" r="3"></circle>
						</svg>
						<span>Работаем по Москве и Московской области</span>
					</div>
					<?php if ( $hours ) : ?>
						<div class="flex items-center gap-3 text-muted-foreground">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock w-5 h-5 text-primary" aria-hidden="true">
								<circle cx="12" cy="12" r="10"></circle>
								<polyline points="12 6 12 12 16 14"></polyline>
							</svg>
							<span><?php echo esc_html( $hours ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $nav_items ) : ?>
				<nav aria-label="Навигация">
					<h2 class="font-semibold text-foreground mb-5">Навигация</h2>
					<ul class="space-y-3">
						<?php foreach ( $nav_items as $item ) : ?>
							<li><a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo esc_attr( $link_class ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>
			<?php if ( $services ) : ?>
				<nav aria-label="Услуги">
					<h2 class="font-semibold text-foreground mb-5">Услуги</h2>
					<ul class="space-y-3">
						<?php foreach ( $services as $item ) : ?>
							<li><a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo esc_attr( $link_class ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>
		</div>
		<?php if ( $inn || $ogrnip ) : ?>
			<div class="mt-10 text-sm text-muted-foreground">ИНН / ОГРН: <?php echo esc_html( $inn ); ?> / <?php echo esc_html( $ogrnip ); ?></div>
		<?php endif; ?>
		<div class="mt-6 pt-8 border-t border-border flex flex-col md:flex-row justify-between items-center gap-4">
			<p class="text-sm text-muted-foreground">© Nexor, 2017–<?php echo esc_html( (string) $year ); ?>. Все права защищены.</p>
			<div class="flex flex-wrap gap-x-6 gap-y-2">
				<a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>" class="<?php echo esc_attr( $legal_class ); ?>">Политика конфиденциальности</a>
				<a href="<?php echo esc_url( home_url( '/consent/' ) ); ?>" class="<?php echo esc_attr( $legal_class ); ?>">Согласие на обработку персональных данных</a>
			</div>
		</div>
	</div>
</footer>
