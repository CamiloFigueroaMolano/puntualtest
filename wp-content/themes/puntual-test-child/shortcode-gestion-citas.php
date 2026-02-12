<?php

// ====================================================================================
// PASO 1: CARGAR EL SCRIPT DEL PANEL DE FORMA SEGURA
// Se asegura de que el archivo panel-citas.js se cargue solo en la página correcta.
// ====================================================================================
function enqueue_citas_panel_scripts() {
    global $post;
    
    // Solo carga nuestro script si la página actual contiene el shortcode [gestion_citas]
    if ( is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'gestion_citas') ) {
        
        // Carga el archivo JavaScript que controla el panel
        wp_enqueue_script(
            'panel-citas-script', // Un nombre único para el script
            get_stylesheet_directory_uri() . '/js/panel-citas.js', // Ruta a tu archivo JS
            ['jquery'], // Dependencia: Carga jQuery primero
            '1.0.1',    // Versión (cambiada para refrescar la caché)
            true        // Cárgalo en el footer
        );

        // Pasa variables de PHP a JavaScript de forma segura
        wp_localize_script(
            'panel-citas-script', // El script al que le pasamos los datos
            'citas_ajax',         // El nombre del objeto que usaremos en JS
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('citas_nonce_seguridad') // Clave de seguridad
            ]
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_citas_panel_scripts');


// ====================================================================================
// PASO 2: CREAR EL SHORTCODE QUE MUESTRA LOS DOS PANELES
// Esta función construye el HTML del panel de citas y el de leads rápidos.
// ====================================================================================
function display_citas_panel_shortcode() {
    // Seguridad: solo usuarios con permisos pueden ver esto
    if ( !current_user_can('manage_options') && !current_user_can('um_gestor-citas') ) {
        return '<p>No tienes permisos para ver esta sección.</p>';
    }

    global $wpdb;

    // --- CONSULTA PARA LOS LEADS DEL FORMULARIO RÁPIDO ---
    $tabla_leads = $wpdb->prefix . 'contacto_whatsapp'; // Tabla de los leads rápidos
    $leads = $wpdb->get_results("SELECT * FROM {$tabla_leads} ORDER BY fecha_registro DESC");

    // --- CONSULTA PARA LAS CITAS COMPLETAS ---
    $tabla_citas = 'citas'; // Tabla de las citas completas
    $citas = $wpdb->get_results("SELECT * FROM {$tabla_citas} ORDER BY FIELD(estado_cita, 'proxima', 'confirmada', 'cancelada', 'pasada'), fecha_cita DESC, hora_cita DESC");

    ob_start();
    ?>
    <div class="citas-panel-wrap">
        
        <div class="citas-panel-header">
            <h1>Leads del Formulario Rápido</h1>
        </div>
        <div class="leads-list-wrap" style="overflow-x: auto;">
            <?php if (!empty($leads)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Nombre Completo</th>
                            <th style="width: 30%;">Teléfono</th>
                            <th>Fecha de Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($lead->nombre_completo); ?></strong></td>
                                <td><a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $lead->telefono)); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($lead->telefono); ?></a></td>
                                <td>
                                    <?php
                                    // ==================================
                                    // INICIO DE LA CORRECCIÓN
                                    // ==================================
                                    // Añadimos 5 horas (18000 segundos) porque WP asume que la fecha de la BD
                                    // está en UTC y le resta 5 horas al mostrarla. Esto lo compensa.
                                    $timestamp_corregido = strtotime($lead->fecha_registro) + 18000;
                                    echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $timestamp_corregido));
                                    // ==================================
                                    // FIN DE LA CORRECCIÓN
                                    // ==================================
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="citas-no-results"><p>No hay leads capturados desde el formulario rápido.</p></div>
            <?php endif; ?>
        </div>

        <hr style="margin: 40px 0; border: 1px solid #ddd;">

        <div class="citas-panel-header">
            <h1>Panel de Citas Agendadas</h1>
            <div class="citas-filters">
                <input type="text" id="citas-search" placeholder="Buscar por nombre, email...">
            </div>
        </div>
        <div id="citas-list" class="citas-list-wrap">
            <?php if (!empty($citas)) : ?>
                <?php foreach ($citas as $cita) :
                    // Lógica para mostrar las tarjetas de cita (se mantiene intacta)
                    $estado_manual = $cita->estado_cita;
                    $estado_calculado = 'error';
                    if (!empty($cita->fecha_cita) && !empty($cita->hora_cita)) {
                        try {
                            $fecha_cita_obj = new DateTime($cita->fecha_cita . ' ' . $cita->hora_cita, wp_timezone());
                            $ahora = new DateTime('now', wp_timezone());
                            $estado_calculado = ($fecha_cita_obj > $ahora) ? 'proxima' : 'pasada';
                        } catch (Exception $e) {}
                    }
                    if ($estado_manual === 'proxima' && $estado_calculado === 'pasada') {
                        $estado_final = 'pasada';
                    } else {
                        $estado_final = $estado_manual;
                    }
                    switch ($estado_final) {
                        case 'confirmada': $texto_estado = 'Confirmada'; break;
                        case 'cancelada': $texto_estado = 'Cancelada'; break;
                        case 'pasada': $texto_estado = 'Pasada'; break;
                        default: $texto_estado = 'Próxima'; $estado_final = 'proxima'; break;
                    }
                    $fecha_mostrada = !empty($fecha_cita_obj) ? esc_html(wp_date(get_option('date_format'), $fecha_cita_obj->getTimestamp())) : 'N/D';
                    $hora_mostrada = !empty($fecha_cita_obj) ? esc_html(wp_date(get_option('time_format'), $fecha_cita_obj->getTimestamp())) : 'N/D';
                    $fecha_raw = !empty($fecha_cita_obj) ? $fecha_cita_obj->format('Y-m-d') : '';
                    $hora_raw = !empty($fecha_cita_obj) ? $fecha_cita_obj->format('H:i') : '';
                ?>
                    <div class="cita-card" data-id="<?php echo esc_attr($cita->id); ?>">
                        <div class="cita-card-header">
                            <span class="cita-status status-<?php echo esc_attr($estado_final); ?>"><?php echo esc_html($texto_estado); ?></span>
                            <div class="cita-actions">
                                <button class="btn-action btn-details" title="Ver Detalles">🔍</button>
                                <button class="btn-action btn-delete" title="Eliminar Cita">🗑️</button>
                            </div>
                        </div>
                        <div class="cita-card-body">
                            <h3 class="cita-nombre"><?php echo esc_html($cita->nombre_apellido); ?></h3>
                            <p class="cita-email"><?php echo esc_html($cita->email); ?></p>
                            <div class="cita-info">
                                <span>🗓️ <?php echo $fecha_mostrada; ?></span>
                                <span>🕒 <?php echo $hora_mostrada; ?></span>
                            </div>
                        </div>
                        <div class="cita-full-details" data-fecha-raw="<?php echo esc_attr($fecha_raw); ?>" data-hora-raw="<?php echo esc_attr($hora_raw); ?>" style="display:none;">
                           <p><strong>Teléfono:</strong> <?php echo esc_html($cita->telefono); ?></p>
                           <p><strong>Trámite 1:</strong> <?php echo esc_html($cita->tramite1 . ' / ' . $cita->categoria1); ?></p>
                           <?php if(!empty($cita->tramite2)): ?>
                           <p><strong>Trámite 2:</strong> <?php echo esc_html($cita->tramite2 . ' / ' . $cita->categoria2); ?></p>
                           <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="citas-no-results"><p>No hay citas agendadas.</p></div>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="cita-modal" class="cita-modal-overlay" style="display:none;">
        <div class="cita-modal-content">
            <span class="cita-modal-close">&times;</span>
            <div id="cita-modal-body"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('gestion_citas', 'display_citas_panel_shortcode');

