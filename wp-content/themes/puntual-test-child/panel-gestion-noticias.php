<?php
// panel-gestion-noticias.php

// -----------------------------------------------------------------------------
// SHORTCODE PRINCIPAL DEL PANEL DE GESTIÓN DE NOTICIAS
// Uso: [panel_gestion_noticias]
// -----------------------------------------------------------------------------
add_shortcode('panel_gestion_noticias', 'render_news_management_panel');

function render_news_management_panel() {
    // Comprobación de permisos para ver el panel
    if (!current_user_can('edit_posts')) {
        return '<p>No tienes permisos para acceder a esta sección.</p>';
    }

    $action = isset($_GET['action']) ? $_GET['action'] : 'list';
    $noticia_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    ob_start();

    // Decide qué vista mostrar
    switch ($action) {
        case 'edit':
            echo render_news_form('edit', $noticia_id);
            break;
        case 'add':
            echo render_news_form('add');
            break;
        default:
            echo render_news_list();
            break;
    }

    return ob_get_clean();
}

// -----------------------------------------------------------------------------
// FUNCIÓN PARA MOSTRAR LA LISTA DE NOTICIAS
// -----------------------------------------------------------------------------
function render_news_list() {
    global $wpdb;
    $tabla_noticias = 'noticias';
    $noticias = $wpdb->get_results("SELECT id, titulo, categoria, estado, fecha_creacion FROM {$tabla_noticias} ORDER BY fecha_creacion DESC");
    $page_url = get_permalink();
    ?>
    <div class="admin-property-list-page">
        <div class="admin-list-header">
            <h1>Gestión de Noticias</h1>
            <a href="<?php echo esc_url(add_query_arg(['action' => 'add'], $page_url)); ?>" class="add-new-button">Agregar Nueva Noticia</a>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 40%;">Título</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th style="width: 15%;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($noticias): foreach ($noticias as $noticia): ?>
                    <tr>
                        <td><strong><?php echo esc_html($noticia->titulo); ?></strong></td>
                        <td><?php echo esc_html($noticia->categoria); ?></td>
                        <td><?php echo esc_html($noticia->estado); ?></td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($noticia->fecha_creacion)); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg(['action' => 'edit', 'id' => $noticia->id], $page_url)); ?>" class="button button-primary">Editar</a>
                            <button class="button delete-btn" data-id="<?php echo $noticia->id; ?>" data-nonce="<?php echo wp_create_nonce('news_nonce_delete'); ?>">Eliminar</button>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5">No hay noticias. ¡Crea la primera!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// -----------------------------------------------------------------------------
// FUNCIÓN PARA MOSTRAR EL FORMULARIO DE AGREGAR/EDITAR NOTICIA
// -----------------------------------------------------------------------------
function render_news_form($mode = 'add', $noticia_id = 0) {
    $noticia = null;
    $is_edit_mode = ($mode === 'edit');
    
    if ($is_edit_mode && $noticia_id) {
        global $wpdb;
        $tabla_noticias = 'noticias';
        $noticia = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla_noticias} WHERE id = %d", $noticia_id));
        if (!$noticia) {
            return '<p>Error: Noticia no encontrada.</p>';
        }
    }
    
    wp_enqueue_media(); // Necesario para el uploader de WordPress
    ?>
    <div class="admin-property-page">
        <header class="admin-header">
            <h1><?php echo $is_edit_mode ? 'Modificar Noticia' : 'Agregar Nueva Noticia'; ?></h1>
        </header>
        <form id="news-form" enctype="multipart/form-data">
            <div id="property-form"> <?php if ($is_edit_mode): ?>
                    <input type="hidden" name="noticia_id" value="<?php echo esc_attr($noticia->id); ?>">
                <?php endif; ?>
                
                <div class="form-main-column">
                    <div class="form-section">
                        <div class="form-field">
                            <label for="titulo">Título</label>
                            <input type="text" id="titulo" name="titulo" value="<?php echo $is_edit_mode ? esc_attr($noticia->titulo) : ''; ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="contenido">Contenido Completo</label>
                            <?php
                            $content = $is_edit_mode ? $noticia->contenido : '';
                            wp_editor($content, 'contenido', ['textarea_name' => 'contenido', 'media_buttons' => true, 'textarea_rows' => 15]);
                            ?>
                        </div>
                        <div class="form-field">
                            <label for="resumen">Resumen (para las tarjetas de la página de inicio)</label>
                            <textarea id="resumen" name="resumen" rows="4" placeholder="Unas 2-3 frases cortas que inviten a leer más."><?php echo $is_edit_mode ? esc_textarea($noticia->resumen) : ''; ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-sidebar-column">
                    <div class="form-section">
                        <h2>Publicar</h2>
                        <div class="form-field">
                            <label for="categoria">Categoría</label>
                            <input type="text" id="categoria" name="categoria" value="<?php echo $is_edit_mode ? esc_attr($noticia->categoria) : 'General'; ?>">
                        </div>
                        <div class="form-field">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado">
                                <option value="publicada" <?php if($is_edit_mode) selected($noticia->estado, 'publicada'); ?>>Publicada</option>
                                <option value="borrador" <?php if($is_edit_mode) selected($noticia->estado, 'borrador'); ?>>Borrador</option>
                            </select>
                        </div>
                        <button type="submit" id="save-news-btn" class="button button-primary button-large"><?php echo $is_edit_mode ? 'Guardar Cambios' : 'Guardar Noticia'; ?></button>
                    </div>
                    <div class="form-section">
                        <h2>Multimedia</h2>
                        <div class="form-field">
                            <label for="imagen_url">URL de Imagen Destacada</label>
                            <input type="text" name="imagen_url" id="imagen_url" value="<?php echo $is_edit_mode ? esc_attr($noticia->imagen_url) : ''; ?>" placeholder="Pega la URL o sube una imagen">
                            <button type="button" id="upload-image-btn" class="button">Subir Imagen</button>
                            <div id="image-preview-wrapper" style="margin-top:1rem;">
                                <?php if ($is_edit_mode && !empty($noticia->imagen_url)): ?>
                                    <img src="<?php echo esc_url($noticia->imagen_url); ?>" style="max-width:100%;">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-field">
                            <label for="video_url">URL del Video (Opcional)</label>
                            <input type="text" id="video_url" name="video_url" value="<?php echo $is_edit_mode ? esc_attr($noticia->video_url) : ''; ?>" placeholder="Pega la URL de YouTube, Vimeo, etc.">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
}

// -----------------------------------------------------------------------------
// FUNCIONES AJAX PARA GUARDAR Y ELIMINAR
// -----------------------------------------------------------------------------
add_action('wp_ajax_save_news_data', 'handle_save_news_ajax');
function handle_save_news_ajax() {
    check_ajax_referer('news_nonce_save', '_ajax_nonce');
    $noticia_id = isset($_POST['noticia_id']) ? intval($_POST['noticia_id']) : 0;
    
    global $wpdb;
    $table_name = 'noticias';
    $data = [
        'titulo' => sanitize_text_field($_POST['titulo']),
        'contenido' => wp_kses_post($_POST['contenido']),
        'resumen' => sanitize_textarea_field($_POST['resumen']),
        'imagen_url' => esc_url_raw($_POST['imagen_url']),
        'video_url' => esc_url_raw($_POST['video_url']), // Campo de video añadido
        'categoria' => sanitize_text_field($_POST['categoria']),
        'estado' => sanitize_text_field($_POST['estado']),
    ];

    if ($noticia_id) {
        $wpdb->update($table_name, $data, ['id' => $noticia_id]);
        $message = 'Noticia actualizada con éxito.';
    } else {
        $wpdb->insert($table_name, $data);
        $message = 'Noticia agregada con éxito.';
    }
    
    wp_send_json_success(['message' => $message]);
    wp_die();
}

add_action('wp_ajax_delete_news_ajax', 'handle_delete_news_ajax');
function handle_delete_news_ajax() {
    check_ajax_referer('news_nonce_delete', '_ajax_nonce');
    $noticia_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    global $wpdb;
    $wpdb->delete('noticias', ['id' => $noticia_id]);
    wp_send_json_success(['message' => 'Noticia eliminada.']);
    wp_die();
}

// -----------------------------------------------------------------------------
// SCRIPT DE JAVASCRIPT PARA EL PANEL
// -----------------------------------------------------------------------------
function enqueue_news_panel_scripts() {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'panel_gestion_noticias')) {
        add_action('wp_footer', 'print_news_panel_script');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_news_panel_scripts');

function print_news_panel_script() {
    ?>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lógica para el uploader de imagen del formulario
        const uploadBtn = document.getElementById('upload-image-btn');
        if(uploadBtn) {
            uploadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const imageFrame = wp.media({ title: 'Seleccionar Imagen', multiple: false });
                imageFrame.on('select', function() {
                    const attachment = imageFrame.state().get('selection').first().toJSON();
                    document.getElementById('imagen_url').value = attachment.url;
                    document.getElementById('image-preview-wrapper').innerHTML = `<img src="${attachment.url}" style="max-width:100%;">`;
                });
                imageFrame.open();
            });
        }

        // Lógica para guardar el formulario
        const newsForm = document.getElementById('news-form');
        if (newsForm) {
            newsForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Actualizar el contenido del editor TinyMCE antes de enviar
                if (typeof tinymce !== 'undefined' && tinymce.get('contenido')) {
                    document.getElementById('contenido').value = tinymce.get('contenido').getContent();
                }

                const formData = new FormData(newsForm);
                formData.append('action', 'save_news_data');
                formData.append('_ajax_nonce', '<?php echo wp_create_nonce("news_nonce_save"); ?>');

                fetch('<?php echo admin_url("admin-ajax.php"); ?>', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ title: '¡Éxito!', text: data.data.message, icon: 'success', timer: 2000, showConfirmButton: false })
                        .then(() => { 
                            // Asegúrate de que el slug 'gestion-noticias' sea correcto
                            window.location.href = '<?php echo get_permalink(get_page_by_path("gestion-noticias")); ?>'; 
                        });
                    } else {
                        Swal.fire('Error', 'Ocurrió un problema al guardar.', 'error');
                    }
                });
            });
        }

        // Lógica para eliminar una noticia
        document.body.addEventListener('click', function(e) {
            if (e.target.matches('.delete-btn')) {
                e.preventDefault();
                const btn = e.target;
                const noticiaId = btn.dataset.id;
                const nonce = btn.dataset.nonce;

                Swal.fire({
                    title: '¿Estás seguro?', text: "¡No podrás revertir esta acción!", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonText: 'Cancelar', confirmButtonText: 'Sí, ¡eliminar!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('action', 'delete_news_ajax');
                        formData.append('_ajax_nonce', nonce);
                        formData.append('id', noticiaId);
                        
                        fetch('<?php echo admin_url("admin-ajax.php"); ?>', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('¡Eliminada!', data.data.message, 'success');
                                btn.closest('tr').remove();
                            } else {
                                Swal.fire('Error', 'Ocurrió un problema.', 'error');
                            }
                        });
                    }
                });
            }
        });
    });
    </script>
    <?php
}