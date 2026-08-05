<?php
/** Accessible native WordPress search form. */
$nexor_search_id = wp_unique_id( 'nexor-search-' );
?>
<form role="search" method="get" class="nexor-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="<?php echo esc_attr( $nexor_search_id ); ?>">
		<span class="nexor-sr-only">Поиск по сайту</span>
		<input id="<?php echo esc_attr( $nexor_search_id ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Поиск" autocomplete="off">
	</label>
	<button class="nexor-search__submit" type="submit" aria-label="Найти">
		<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
	</button>
</form>
