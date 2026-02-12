<?php
// shortcode-blog.php

/**
 * Shortcode para mostrar las últimas 3 entradas del blog.
 * Uso: [ultimas_noticias_blog]
 */
function display_ultimas_noticias_blog_shortcode() {
    ob_start();
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => 3,
        'post_status' => 'publish',
        'ignore_sticky_posts' => 1,
    );
    $blog_query = new WP_Query($args);
    ?>
    <div class="blog-section-container">
        <div class="section-title-wrapper">
            <h2 class="section-main-title">Novedades y Guías</h2>
            <p class="section-subtitle-text">Mantente informado con nuestros últimos artículos sobre trámites, seguridad vial y consejos para conductores en Bogotá.</p>
        </div>
        <div class="blog-posts-grid">
            <?php
            if ($blog_query->have_posts()) :
                while ($blog_query->have_posts()) : $blog_query->the_post();
            ?>
                <article class="blog-post-card">
                    <a href="<?php the_permalink(); ?>" class="card-image-link">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium_large', array('class' => 'card-image', 'loading' => 'lazy')); ?>
                        <?php else : ?>
                            <img src="https://puntualtest.com/wp-content/uploads/2025/07/car-7614510_1280-1.webp" alt="Imagen por defecto" class="card-image" loading="lazy">
                        <?php endif; ?>
                    </a>
                    <div class="card-content">
                        <div class="card-meta">
                            <span class="card-category"><?php the_category(', '); ?></span>
                        </div>
                        <h3 class="card-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <div class="card-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="card-read-more">Leer Más →</a>
                    </div>
                </article>
            <?php
                endwhile;
                wp_reset_postdata();
            else :
            ?>
                <p class="no-posts-message">Próximamente publicaremos nuevas guías y artículos.</p>
            <?php
            endif;
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ultimas_noticias_blog', 'display_ultimas_noticias_blog_shortcode');