<?php
// shortcode-detalle-noticia.php

function mostrar_detalle_noticia_shortcode() {
    ob_start();
    
    $noticia_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$noticia_id) {
        echo '<p>No se ha especificado una noticia válida.</p>';
        return ob_get_clean();
    }

    global $wpdb;
    $tabla_noticias = 'noticias';
    $noticia = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla_noticias} WHERE id = %d AND estado = 'publicada'", $noticia_id));

    if ($noticia) {
        // Obtener otras noticias para la barra lateral
        $otras_noticias = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo FROM {$tabla_noticias} WHERE estado = 'publicada' AND id != %d ORDER BY fecha_creacion DESC LIMIT 3",
            $noticia_id
        ));

        // URL para compartir en redes sociales
        $current_url = get_permalink() . '?id=' . $noticia_id;
        ?>
        <div class="detalle-noticia-body">
            <div class="detalle-noticia-container">
                <main class="noticia-main-content">
                    <header class="noticia-header">
                        <div class="categoria"><?php echo esc_html($noticia->categoria); ?></div>
                        <h1 class="titulo"><?php echo esc_html($noticia->titulo); ?></h1>
                        <div class="fecha">Publicado el <?php echo date_i18n(get_option('date_format'), strtotime($noticia->fecha_creacion)); ?></div>
                    </header>
                    
                    <?php if (!empty($noticia->imagen_url)): ?>
                        <img src="<?php echo esc_url($noticia->imagen_url); ?>" alt="<?php echo esc_attr($noticia->titulo); ?>" class="noticia-imagen-destacada">
                    <?php endif; ?>

                    <div class="noticia-contenido-principal">
                        <?php echo wpautop($noticia->contenido); ?>
                    </div>

                    <?php if (!empty($noticia->video_url)): 
                        // Convertir URL de YouTube a formato embed
                        $video_embed_url = str_replace('watch?v=', 'embed/', $noticia->video_url);
                    ?>
                        <div class="noticia-video-container">
                            <iframe src="<?php echo esc_url($video_embed_url); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    <?php endif; ?>
                </main>

                <aside class="noticia-sidebar">
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Compartir</h3>
                        <div class="social-share-links">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_url); ?>" target="_blank" class="share-facebook" title="Compartir en Facebook"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 2.04C6.5 2.04 2 6.53 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.85C10.44 7.32 11.93 5.96 14.22 5.96C15.31 5.96 16.45 6.15 16.45 6.15V8.62H15.19C13.95 8.62 13.56 9.39 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96A10,10 0 0,0 12 2.04Z" /></svg></a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($current_url); ?>&text=<?php echo urlencode($noticia->titulo); ?>" target="_blank" class="share-twitter" title="Compartir en X"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H4.68l4.71 6.209L14.444 2.25h3.8zm-1.161 17.52h1.833L7.084 4.126H5.117z" /></svg></a>
                            <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($noticia->titulo . ' - ' . $current_url); ?>" target="_blank" class="share-whatsapp" title="Compartir en WhatsApp"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M12.04 2C6.58 2 2.13 6.45 2.13 12c0 1.77.46 3.45 1.28 4.95L2 22l5.13-1.35c1.45.77 3.09.95 4.77.95h.1c5.46 0 9.91-4.45 9.91-9.91c0-5.56-4.45-9.9-9.91-9.9M12.04 20.15h-.1c-1.47 0-2.92-.4-4.2-1.15l-.3-.18l-3.12.82l.83-3.04l-.2-.31c-.82-1.32-1.25-2.83-1.25-4.38c0-4.54 3.7-8.24 8.24-8.24c4.54 0 8.24 3.7 8.24 8.24c0 4.55-3.7 8.24-8.24 8.24m4.52-6.2c-.25-.12-1.47-.72-1.7-.82c-.22-.09-.38-.12-.54.12c-.16.25-.64.82-.79.99c-.15.16-.3.18-.54.06c-.25-.12-1.05-.39-2-1.23c-.74-.66-1.23-1.47-1.38-1.72c-.15-.25-.02-.38.1-.51c.11-.11.25-.29.38-.43c.12-.15.16-.25.25-.41c.09-.17.04-.31-.02-.43c-.06-.12-.54-1.3-  .74-1.78c-.2-.48-.4-.42-.54-.42h-.48c-.16,0-.43.06-.66.31c-.22.25-.86.85-.86,2.07c0,1.22.88,2.4,1,2.56c.12.17,1.75,2.67,4.24,3.75c.58.25,1.03.4,1.38.52c.6.2,1.1.16,1.5.1c.46-.06,1.47-.6,1.68-1.18c.2-.58.2-1.08.15-1.18c-.05-.1-.2-.16-.43-.28Z" /></svg></a>
                        </div>
                    </div>
                    
                    <?php if ($otras_noticias): ?>
                    <div class="sidebar-widget otras-noticias-list">
                        <h3 class="widget-title">Otras Noticias</h3>
                        <ul>
                            <?php foreach ($otras_noticias as $otra_noticia): ?>
                                <li><a href="/detalle-noticia?id=<?php echo $otra_noticia->id; ?>"><?php echo esc_html($otra_noticia->titulo); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <div class="sidebar-widget cta-widget">
                        <h3 class="widget-title">¿Listo para tu Trámite?</h3>
                        <p>Realiza tu examen médico para la licencia de forma ágil y confiable. ¡Asegura tu cita hoy mismo!</p>
                        <a href="/#agenda" class="button">Agendar Cita</a>
                    </div>
                </aside>
            </div>
        </div>
        <?php
    } else {
        echo '<p>Noticia no encontrada o no disponible.</p>';
    }
    
    return ob_get_clean();
}

// ⭐ LÍNEA FALTANTE AÑADIDA AQUÍ ⭐
add_shortcode('detalle_noticia', 'mostrar_detalle_noticia_shortcode');