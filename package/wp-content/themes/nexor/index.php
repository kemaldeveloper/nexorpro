<?php
get_header();
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		nexor_render_migrated_content();
	}
}
get_footer();

