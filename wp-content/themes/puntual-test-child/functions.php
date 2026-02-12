<?php
/**
 * Puntual Test Child Theme functions and definitions
 *
 * @package Puntual Test Child
 * @since 1.0.2
 */

define( 'CHILD_THEME_PUNTUAL_TEST_CHILD_VERSION', '1.0.2' );

// ====================================================================================
// INCLUIR ARCHIVOS DE FUNCIONES ADICIONALES (DESDE LA RAÍZ)
// ====================================================================================
require_once( get_stylesheet_directory() . '/shortcode-gestion-citas.php' );
require_once( get_stylesheet_directory() . '/shortcode-resumen-cita.php' );
require_once( get_stylesheet_directory() . '/panel-gestion-noticias.php' ); 
require_once( get_stylesheet_directory() . '/shortcode-ultimas-noticias.php' );
require_once( get_stylesheet_directory() . '/shortcode-pagina-noticias.php' );
require_once( get_stylesheet_directory() . '/shortcode-detalle-noticia.php' );

/**
 * Carga los estilos y scripts del tema.
 */
function puntual_test_child_enqueue_assets() {
    
    $parent_style = 'astra-theme-css'; // El "handle" del CSS del tema padre.

    // Carga el CSS del tema hijo, y le dice a WordPress que DEPENDE del tema padre.
    wp_enqueue_style(
        'puntual-test-child-main-css',
        get_stylesheet_uri(),
        array( $parent_style    ),
        CHILD_THEME_PUNTUAL_TEST_CHILD_VERSION
    );
    
    // Carga tus estilos personalizados consolidados para TODOS los dispositivos
    wp_enqueue_style(
        'custom-components-css',
        get_stylesheet_directory_uri() . '/style.min.css',
        array('puntual-test-child-main-css'),
        '1.0.3' 
    );
    
    // Carga jQuery y tus scripts personalizados consolidados para TODOS los dispositivos
    wp_enqueue_script(
        'custom-consolidated-scripts',
        get_stylesheet_directory_uri() . '/custom-scripts.js',
        array('jquery'),
        '1.0.3', 
        true // Cargar en el footer
    );
    
    // Pasa las variables de AJAX al script
    wp_localize_script(
        'custom-consolidated-scripts',
        'citas_ajax',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('citas_panel_nonce')
        )
    );
}
add_action('wp_enqueue_scripts', 'puntual_test_child_enqueue_assets', 15);

// Función para agregar el tag de Google Ads en el <head>
function agregar_google_ads_tag() {
    ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-17633471334"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'AW-17633471334');
    </script>
    <?php
}
add_action('wp_head', 'agregar_google_ads_tag');


// ====================================================================================
// FUNCIONES DE BACKEND Y AJAX PARA EL PANEL DE GESTIÓN DE CITAS
// ====================================================================================

function agregar_observacion($observaciones_actuales, $nueva_observacion) {
    if (empty($nueva_observacion)) return $observaciones_actuales;
    $timestamp = wp_date('d/m/Y H:i');
    $user = wp_get_current_user();
    $autor = $user->display_name;
    $observacion_formateada = "[$timestamp - $autor] $nueva_observacion";
    return empty($observaciones_actuales) ? $observacion_formateada : $observaciones_actuales . '|' . $observacion_formateada;
}

add_action('wp_ajax_delete_cita', 'handle_delete_cita_ajax');
function handle_delete_cita_ajax() {
    check_ajax_referer('citas_panel_nonce', 'nonce');
    if (isset($_POST['cita_id']) && is_numeric($_POST['cita_id'])) {
        global $wpdb;
        $table_name = 'citas';
        $cita_id = intval($_POST['cita_id']);
        $deleted = $wpdb->delete($table_name, array('id' => $cita_id), array('%d'));
        if ($deleted) { wp_send_json_success(array('message' => 'Cita eliminada correctamente.')); } 
        else { wp_send_json_error(array('message' => 'Error al eliminar la cita.')); }
    }
    wp_die();
}

add_action('wp_ajax_edit_cita_fecha_admin', 'handle_edit_cita_fecha_admin_ajax');
function handle_edit_cita_fecha_admin_ajax() {
    check_ajax_referer('citas_panel_nonce', 'nonce');
    if (!isset($_POST['cita_id']) || !is_numeric($_POST['cita_id']) || empty($_POST['nueva_fecha']) || empty($_POST['nueva_hora'])) {
        wp_send_json_error(array('message' => 'Faltan datos para actualizar.'), 400);
    }
    global $wpdb;
    $table_name = 'citas';
    $cita_id = intval($_POST['cita_id']);
    $nueva_fecha = sanitize_text_field($_POST['nueva_fecha']);
    $nueva_hora = sanitize_text_field($_POST['nueva_hora']);
    $observacion_texto = isset($_POST['observacion']) ? sanitize_textarea_field($_POST['observacion']) : '';
    $observaciones_actuales = $wpdb->get_var($wpdb->prepare("SELECT observaciones FROM {$table_name} WHERE id = %d", $cita_id));
    $observacion_final = agregar_observacion($observaciones_actuales, "Reprogramada. " . $observacion_texto);
    $wpdb->update($table_name, array('fecha_cita' => $nueva_fecha, 'hora_cita' => $nueva_hora, 'observaciones' => $observacion_final), array('id' => $cita_id), array('%s', '%s', '%s'), array('%d'));
    wp_send_json_success(array('message' => 'Cita actualizada.'));
}

add_action('wp_ajax_update_cita_status', 'handle_update_cita_status_ajax');
function handle_update_cita_status_ajax() {
    check_ajax_referer('citas_panel_nonce', 'nonce');
    if (!isset($_POST['cita_id']) || !is_numeric($_POST['cita_id']) || empty($_POST['nuevo_estado'])) {
        wp_send_json_error(array('message' => 'Faltan datos para actualizar.'), 400);
    }
    global $wpdb;
    $table_name = 'citas';
    $cita_id = intval($_POST['cita_id']);
    $nuevo_estado = sanitize_key($_POST['nuevo_estado']);
    $observacion_texto = isset($_POST['observacion']) ? sanitize_textarea_field($_POST['observacion']) : '';
    $observaciones_actuales = $wpdb->get_var($wpdb->prepare("SELECT observaciones FROM {$table_name} WHERE id = %d", $cita_id));
    $observacion_final = agregar_observacion($observaciones_actuales, ucfirst($nuevo_estado) . ". " . $observacion_texto);
    $wpdb->update($table_name, array('estado_cita' => $nuevo_estado, 'observaciones' => $observacion_final), array('id' => $cita_id), array('%s', '%s'), array('%d'));
    wp_send_json_success(array('message' => 'Estado actualizado.'));
}