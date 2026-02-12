<?php
// shortcode-ultimas-noticias.php

function mostrar_ultimas_noticias_shortcode() {
    ob_start();

    global $wpdb;
    $tabla_noticias = 'noticias';
    $noticias = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$tabla_noticias} WHERE estado = %s ORDER BY fecha_creacion DESC LIMIT 3",
            'publicada'
        )
    );
    ?>
    <div class="blog-section-container">
        <div class="section-title-wrapper">
            <h2 class="section-main-title">Novedades y Guías</h2>
            <p class="section-subtitle-text">Mantente informado con nuestros últimos artículos sobre trámites, seguridad vial y consejos para conductores en Bogotá.</p>
        </div>
        <div class="blog-posts-grid">
            <?php if ($noticias): foreach ($noticias as $noticia): ?>
                <article class="blog-post-card">
                    <a href="/detalle-noticia?id=<?php echo $noticia->id; ?>" class="card-image-link">
                        <?php if (!empty($noticia->imagen_url)): ?>
                            <img src="<?php echo esc_url($noticia->imagen_url); ?>" alt="<?php echo esc_attr($noticia->titulo); ?>" class="card-image" loading="lazy">
                        <?php else: ?>
                            <img src="https://puntualtest.com/wp-content/uploads/2025/07/car-7614510_1280-1.webp" alt="Imagen por defecto" class="card-image" loading="lazy">
                        <?php endif; ?>
                    </a>
                    <div class="card-content">
                        <div class="card-meta">
                            <span class="card-category"><a><?php echo esc_html($noticia->categoria); ?></a></span>
                        </div>
                        <h3 class="card-title">
                            <a href="/detalle-noticia?id=<?php echo $noticia->id; ?>"><?php echo esc_html($noticia->titulo); ?></a>
                        </h3>
                        <div class="card-excerpt">
                            <p><?php echo esc_html($noticia->resumen); ?></p>
                        </div>
                        <a href="/detalle-noticia?id=<?php echo $noticia->id; ?>" class="card-read-more">Leer Más →</a>
                    </div>
                </article>
            <?php endforeach; else: ?>
                <p class="no-posts-message">Próximamente publicaremos nuevas guías y artículos.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ultimas_noticias', 'mostrar_ultimas_noticias_shortcode');