<?php
/**
 * Global site header (single source of truth).
 *
 * @package Nexor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$contacts = nexor_contact_settings();
$phone_display = $contacts['phone_display'];
$phone_href    = 'tel:' . preg_replace( '/[^\d+]/', '', (string) $contacts['phone_link'] );
$telegram_url  = $contacts['telegram_url'];
$vk_url        = $contacts['vk_url'];
$nav           = nexor_navigation_payload();
$primary_items = $nav['primary']['items'] ?? array();
$home_url      = home_url( '/' );
$link_class    = 'relative text-sm font-medium text-muted-foreground hover:text-foreground transition-colors duration-200 group';
$cta_desktop   = 'inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm ring-offset-background transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-terracotta-dark rounded-[10px] h-10 px-5 py-2 font-medium ml-5';
$cta_mobile    = 'inline-flex items-center justify-center gap-2 whitespace-nowrap ring-offset-background transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-terracotta-dark rounded-[10px] font-medium h-9 px-3 text-xs ml-1';
?>
<header class="fixed top-0 left-0 right-0 z-50">
	<a href="<?php echo esc_url( $vk_url ); ?>" target="_blank" rel="noopener noreferrer" class="block bg-[#F0EFED] border-b border-[#E9E6E2]">
		<div class="container-nexor h-10 md:h-11 flex items-center gap-2.5 overflow-hidden">
			<svg class="text-[#4A76A8] shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<path d="M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 15.684 0zm3.692 17.123h-1.744c-.66 0-.864-.525-2.05-1.727-1.033-1-1.49-1.135-1.744-1.135-.356 0-.458.102-.458.593v1.575c0 .424-.135.678-1.253.678-1.846 0-3.896-1.118-5.335-3.202C4.624 10.857 4 8.673 4 8.231c0-.254.102-.491.593-.491h1.744c.44 0 .61.203.78.678.863 2.49 2.303 4.675 2.896 4.675.22 0 .322-.102.322-.66V9.721c-.068-1.186-.695-1.287-.695-1.71 0-.203.17-.407.44-.407h2.744c.373 0 .508.203.508.643v3.473c0 .372.17.508.271.508.22 0 .407-.136.813-.542 1.254-1.406 2.151-3.574 2.151-3.574.119-.254.322-.491.763-.491h1.744c.525 0 .644.27.525.643-.22 1.017-2.354 4.031-2.354 4.031-.186.305-.254.44 0 .78.186.254.796.779 1.203 1.253.745.847 1.32 1.558 1.473 2.05.17.49-.085.744-.576.744z"></path>
			</svg>
			<span class="flex-1 min-w-0 text-xs md:text-sm text-[#6B6B6B] whitespace-nowrap overflow-hidden text-ellipsis transition-opacity duration-500 ease-in-out opacity-100">Реальные объекты, ошибки в ремонте и полезные советы →</span>
		</div>
	</a>
	<div class="bg-[#FAFAF8] border-b border-[#E9E6E2]">
		<div class="container-nexor">
			<div class="flex items-center justify-between h-20 md:h-[88px]">
				<a href="<?php echo esc_url( $home_url ); ?>" class="flex items-center gap-2.5"><span class="text-[1.7rem] font-black text-foreground tracking-tight">Nexor</span></a>
				<nav class="hidden lg:flex items-center gap-12">
					<nav aria-label="Main" data-orientation="horizontal" dir="ltr" class="relative z-10 flex max-w-max flex-1 items-center justify-center">
						<div style="position:relative">
							<ul data-orientation="horizontal" class="group flex flex-1 list-none items-center justify-center space-x-1" dir="ltr">
								<li>
									<button type="button" data-state="closed" aria-expanded="false" class="group inline-flex w-max items-center justify-center rounded-md focus:text-accent-foreground focus:outline-none disabled:pointer-events-none disabled:opacity-50 bg-transparent hover:bg-transparent focus:bg-transparent data-[state=open]:bg-transparent text-sm font-medium text-muted-foreground hover:text-foreground transition-colors duration-200 h-auto p-0 gap-1.5">
										Услуги
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down relative top-[1px] ml-1 h-3 w-3 transition duration-200 group-data-[state=open]:rotate-180" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
									</button>
								</li>
							</ul>
						</div>
						<div class="absolute left-0 top-full flex justify-center"></div>
					</nav>
					<?php foreach ( $primary_items as $item ) : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo esc_attr( $link_class ); ?>"><?php echo esc_html( $item['label'] ); ?><span class="absolute left-0 -bottom-1 w-0 h-[1.5px] bg-primary transition-all duration-200 group-hover:w-full"></span></a>
					<?php endforeach; ?>
				</nav>
				<div class="hidden md:flex items-center">
					<a href="<?php echo esc_url( $phone_href ); ?>" class="flex items-center gap-2 text-sm font-normal text-muted-foreground hover:text-foreground transition-colors duration-200">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-4 h-4" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
						<?php echo esc_html( $phone_display ); ?>
					</a>
					<a href="<?php echo esc_url( $telegram_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Написать в Telegram" class="ml-5 inline-flex items-center justify-center transition-transform duration-200 hover:scale-110">
						<svg viewBox="0 0 24 24" class="w-6 h-6" aria-hidden="true"><circle cx="12" cy="12" r="12" fill="#229ED9"></circle><path fill="#fff" d="M5.5 11.6l11.6-4.47c.54-.2 1.01.13.84.94l-1.97 9.3c-.14.66-.54.82-1.1.51l-3.04-2.24-1.47 1.41c-.16.16-.3.3-.6.3l.21-3.04 5.55-5.02c.24-.21-.05-.33-.37-.12l-6.86 4.32-2.96-.92c-.64-.2-.66-.64.13-.97z"></path></svg>
					</a>
					<button type="button" class="<?php echo esc_attr( $cta_desktop ); ?>">Записаться на замер</button>
				</div>
				<div class="flex md:hidden items-center gap-0.5">
					<a href="<?php echo esc_url( $phone_href ); ?>" aria-label="Позвонить" class="inline-flex items-center justify-center p-1.5 text-foreground">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-5 h-5" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
					</a>
					<a href="<?php echo esc_url( $telegram_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Написать в Telegram" class="inline-flex items-center justify-center p-1.5">
						<svg viewBox="0 0 24 24" class="w-6 h-6" aria-hidden="true"><circle cx="12" cy="12" r="12" fill="#229ED9"></circle><path fill="#fff" d="M5.5 11.6l11.6-4.47c.54-.2 1.01.13.84.94l-1.97 9.3c-.14.66-.54.82-1.1.51l-3.04-2.24-1.47 1.41c-.16.16-.3.3-.6.3l.21-3.04 5.55-5.02c.24-.21-.05-.33-.37-.12l-6.86 4.32-2.96-.92c-.64-.2-.66-.64.13-.97z"></path></svg>
					</a>
					<button type="button" class="<?php echo esc_attr( $cta_mobile ); ?>">Записаться</button>
				</div>
				<button type="button" class="nexor-mobile-trigger p-2 text-foreground" aria-label="Открыть меню">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu w-6 h-6" aria-hidden="true">
						<line x1="4" x2="20" y1="12" y2="12"></line>
						<line x1="4" x2="20" y1="6" y2="6"></line>
						<line x1="4" x2="20" y1="18" y2="18"></line>
					</svg>
				</button>
			</div>
		</div>
	</div>
</header>
