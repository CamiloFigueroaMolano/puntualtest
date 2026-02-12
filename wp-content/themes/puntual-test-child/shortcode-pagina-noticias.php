<?php
// shortcode-pagina-noticias.php

function pagina_noticias_completa_shortcode() {
    ob_start();

    global $wpdb;
    $tabla_noticias = 'noticias';
    
    // Paginación
    $items_per_page = 9;
    $paged = get_query_var('paged') ? get_query_var('paged') : 1;
    $offset = ($paged - 1) * $items_per_page;
    
    // Lógica de Filtros (simple, por categoría)
    $filtro_categoria = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
    $where_clause = '';
    $params = ['publicada'];
    if (!empty($filtro_categoria)) {
        $where_clause = " AND categoria = %s";
        $params[] = $filtro_categoria;
    }

    $noticias = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$tabla_noticias} WHERE estado = %s" . $where_clause . " ORDER BY fecha_creacion DESC LIMIT %d OFFSET %d",
            array_merge($params, [$items_per_page, $offset])
        )
    );
    $total_items = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$tabla_noticias} WHERE estado = %s" . $where_clause, $params));
    $total_pages = ceil($total_items / $items_per_page);
    $categorias_disponibles = $wpdb->get_col("SELECT DISTINCT categoria FROM {$tabla_noticias} WHERE estado = 'publicada' ORDER BY categoria ASC");
    ?>

    <div class="archive-page-container">
        <div class="section-title-wrapper">
            <h1 class="section-main-title">Nuestro Blog</h1>
            <p class="section-subtitle-text">Todas nuestras guías, noticias y consejos para conductores.</p>
        </div>
        
        <div class="archive-filters-container">
            <form action="" method="GET" class="archive-filters-form">
                <div class="archive-filter-item">
                    <label for="categoria-filtro">Filtrar por Categoría</label>
                    <select name="categoria" id="categoria-filtro" onchange="this.form.submit()">
                        <option value="">Todas las Categorías</option>
                        <?php foreach ($categorias_disponibles as $cat) : ?>
                            <option value="<?php echo esc_attr($cat); ?>" <?php selected($filtro_categoria, $cat); ?>><?php echo esc_html(ucfirst($cat)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
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
                <p class="no-posts-message">No hay noticias que coincidan con tu búsqueda.</p>
            <?php endif; ?>
        </div>
        
        <div class="archive-pagination">
            <?php
            // Lógica de paginación
            $base_url = strtok($_SERVER["REQUEST_URI"], '?');
            $query_args = [];
            if (!empty($filtro_categoria)) {
                $query_args['categoria'] = $filtro_categoria;
            }
            
            echo paginate_links( array(
                'base' => $base_url . '%_%',
                'format' => 'page/%#%/',
                'current' => $paged,
                'total' => $total_pages,
                'add_args' => $query_args,
                'prev_text' => __('&laquo; Anterior'),
                'next_text' => __('Siguiente &raquo;'),
            ));
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('pagina_noticias_completa', 'pagina_noticias_completa_shortcode');