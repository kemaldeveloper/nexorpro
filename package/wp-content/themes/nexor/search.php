<?php
/** Native, noindex search results. */
get_header();
?>
<main class="nexor-search-page">
	<div class="container-nexor">
		<?php $query = trim( get_search_query() ); ?>
		<h1 class="heading-section">Поиск по сайту</h1>
		<?php if ( '' === $query ) : ?>
			<p>Введите название услуги, проекта или интересующую тему.</p>
		<?php elseif ( have_posts() ) : ?>
			<p>Результаты по запросу «<?php echo esc_html( $query ); ?>»</p>
			<div class="nexor-search-results">
				<?php while ( have_posts() ) : the_post(); ?>
					<article class="nexor-card">
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php if ( has_excerpt() ) : ?><p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p><?php endif; ?>
						<a class="nexor-card__link" href="<?php the_permalink(); ?>">Открыть →</a>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination( array( 'mid_size'=>1, 'prev_text'=>'← Назад', 'next_text'=>'Далее →' ) ); ?>
		<?php else : ?>
			<p>По запросу «<?php echo esc_html( $query ); ?>» ничего не найдено.</p>
			<p><a href="<?php echo esc_url( home_url( '/#main-services' ) ); ?>">Посмотреть основные услуги</a> · <a href="<?php echo esc_url( home_url( '/projects/' ) ); ?>">Открыть проекты</a></p>
		<?php endif; ?>
	</div>
</main>
<?php get_footer(); ?>
