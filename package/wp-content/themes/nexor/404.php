<?php
status_header( 404 );
get_header();
?>
<main class="min-h-screen bg-background flex items-center justify-center px-5">
  <div class="max-w-xl text-center py-32">
    <p class="text-primary text-7xl font-black mb-4">404</p>
    <h1 class="heading-section mb-5">Страница не найдена</h1>
    <p class="text-muted-foreground mb-8">Возможно, адрес изменился или страница была удалена.</p>
    <a class="inline-flex h-12 items-center rounded-[10px] bg-primary px-7 text-primary-foreground" href="<?php echo esc_url( home_url( '/' ) ); ?>">Вернуться на главную</a>
  </div>
</main>
<?php get_footer(); ?>
