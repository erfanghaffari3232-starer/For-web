<?php get_header(); ?>
<main class="container section">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article class="course"><div class="course-body"><h2><?php the_title(); ?></h2><?php the_content(); ?></div></article>
<?php endwhile; endif; ?>
</main>
<?php get_footer(); ?>