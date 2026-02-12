<?php
/**
 * Handler para el formulario de citas de Puntual Test.
 * Versión final: Integra reCAPTCHA, guarda en BD, envía correos HTML, guarda en sesión y redirige.
 */

// =========================================================================
// PASO 1: CONFIGURACIÓN Y CARGA
// =========================================================================

// Inicia la sesión para poder pasar datos a la página de gracias.
if ( session_status() == PHP_SESSION_NONE ) {
    session_start();
}

// Define tu Clave Secreta de reCAPTCHA.
define('RECAPTCHA_SECRET_KEY', '6LenCJIrAAAAANAwlK-9KfH75TUy6Rtk09hHBE3P');

// Carga el entorno de WordPress de forma segura.
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

// Comprueba que el método sea POST, si no, redirige.
if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    wp_redirect( home_url() );
    exit;
}

// =========================================================================
// PASO 2: RECOGER Y VALIDAR DATOS (INCLUYENDO RECAPTCHA)
// =========================================================================

// Recoge el token de reCAPTCHA
$recaptcha_response = isset($_POST['g-recaptcha-response']) ? sanitize_text_field($_POST['g-recaptcha-response']) : '';

// Prepara la solicitud a la API de Google
$verify_url = 'https://www.google.com/recaptcha/api/siteverify';
$verify_data = http_build_query([
    'secret'   => RECAPTCHA_SECRET_KEY,
    'response' => $recaptcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR']
]);
$options = ['http' => ['method' => 'POST', 'header' => 'Content-type: application/x-www-form-urlencoded', 'content' => $verify_data]];
$context = stream_context_create($options);
$result = @file_get_contents($verify_url, false, $context);
$response_data = json_decode($result);

// =========================================================================
// PASO 3: LÓGICA PRINCIPAL BASADA EN EL RESULTADO DE RECAPTCHA
// =========================================================================

if ( $response_data && $response_data->success && $response_data->score >= 0.7 ) {
    
    // --- VERIFICACIÓN EXITOSA (PROCEDER CON LA CITA) ---

    // 3.1 Limpiar el resto de los datos del formulario
    $nombre_apellido    = sanitize_text_field( $_POST['nombre_apellido'] );
    $email              = sanitize_email( $_POST['email'] );
    $telefono           = sanitize_text_field( $_POST['telefono'] );
    $tramite1           = sanitize_text_field( $_POST['tramite1'] );
    $categoria1         = sanitize_text_field( $_POST['categoria1'] );
    $fecha_cita         = sanitize_text_field( $_POST['fecha_cita'] );
    $hora_cita          = sanitize_text_field( $_POST['hora_cita'] );
    $comentarios        = sanitize_textarea_field( $_POST['comentarios'] );
    $costo_total_str    = sanitize_text_field( $_POST['costo_total'] );
    $quiere_extra       = isset( $_POST['extra'] ) && $_POST['extra'] === 'si';
    $tramite2           = $quiere_extra && isset( $_POST['tramite2'] ) ? sanitize_text_field( $_POST['tramite2'] ) : '';
    $categoria2         = $quiere_extra && isset( $_POST['categoria2'] ) ? sanitize_text_field( $_POST['categoria2'] ) : '';

    // 3.2 Guardar en la base de datos
    global $wpdb;
    $nombre_tabla = 'citas';
    $wpdb->insert(
        $nombre_tabla,
        [
            'nombre_apellido' => $nombre_apellido, 'email' => $email, 'telefono' => $telefono,
            'tramite1' => $tramite1, 'categoria1' => $categoria1, 'tramite2' => $tramite2,
            'categoria2' => $categoria2, 'fecha_cita' => $fecha_cita, 'hora_cita' => $hora_cita,
            'comentarios' => $comentarios, 'created_at' => current_time('mysql', 1), 'estado_cita' => 'proxima'
        ]
    );

    // 3.3 Enviar correos de notificación
    $nombre_sitio = get_bloginfo( 'name' );
    $url_sitio = parse_url( home_url(), PHP_URL_HOST );

    // Correo para el administrador (Texto Plano)
    $to_admin      = 'puntualtest69@gmail.com'; // **¡CAMBIA ESTO A TU CORREO!**
    $subject_admin = "📩 Nueva Cita Agendada Examen Medico: $nombre_apellido";
    $body_admin    = "Se ha agendado una nueva cita:\n\nNombre: $nombre_apellido\nCorreo: $email\nTeléfono: $telefono\n\n--- Detalles ---\nFecha y Hora: $fecha_cita a las $hora_cita\nTrámite 1: $tramite1 - $categoria1\n";
    if (!empty($tramite2)) { $body_admin .= "Trámite 2: $tramite2 - $categoria2\n"; }
    $body_admin .= "Costo Estimado: $costo_total_str\nComentarios: " . (empty($comentarios) ? 'Ninguno' : $comentarios);
    $headers_admin = ["From: $nombre_sitio <no-responder@$url_sitio>", "Reply-To: $nombre_apellido <$email>", "Content-Type: text/plain; charset=UTF-8"];
    wp_mail( $to_admin, $subject_admin, $body_admin, $headers_admin );

    // Correo de confirmación para el usuario (HTML)
    $fecha_obj = new DateTime($fecha_cita);
    $fecha_formateada = date_i18n('l, j \d\e F \d\e Y', $fecha_obj->getTimestamp());
    $hora_formateada = date_i18n(get_option('time_format'), strtotime($hora_cita));
    
    $to_user      = $email;
    $subject_user = "✅ Tu solicitud de cita en $nombre_sitio ha sido recibida";
    $body_user = '<div style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 12px; overflow: hidden;">';
    $body_user .= '<div style="background-color: #0073aa; color: white; padding: 20px; text-align: center;"><h1 style="margin: 0; font-size: 24px;">Confirmación de Cita</h1></div>';
    $body_user .= '<div style="padding: 25px; line-height: 1.6;">';
    $body_user .= '<h2 style="font-size: 18px; color: #333;">Hola ' . esc_html($nombre_apellido) . ',</h2>';
    $body_user .= '<p>Gracias por agendar tu cita con nosotros. Hemos recibido tu solicitud y hemos reservado tu espacio. Un asesor se comunicará contigo a la brevedad para confirmar todos los detalles.</p>';
    $body_user .= '<div style="background-color: #f9f9f9; border-left: 5px solid #2ecc71; padding: 15px 20px; margin: 25px 0;">';
    $body_user .= '<h3 style="margin-top: 0; color: #2c3e50;">Resumen de tu Cita</h3>';
    $body_user .= '<ul style="list-style: none; padding: 0;">';
    $body_user .= '<li style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Fecha y Hora:</strong> ' . esc_html($fecha_formateada) . ' a las ' . esc_html($hora_formateada) . '</li>';
    $body_user .= '<li style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Trámite Principal:</strong> ' . esc_html($tramite1) . ' (' . esc_html($categoria1) . ')</li>';
    if (!empty($tramite2)) {
        $body_user .= '<li style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Segundo Trámite:</strong> ' . esc_html($tramite2) . ' (' . esc_html($categoria2) . ')</li>';
    }
    $body_user .= '<li style="padding: 8px 0;"><strong>Costo Total Estimado:</strong> ' . esc_html($costo_total_str) . '</li>';
    $body_user .= '</ul></div>';
    $body_user .= '<p style="font-weight: bold; color: #d9534f;">Importante: Si no recibes nuestra llamada o un correo de seguimiento, por favor revisa tu bandeja de correo no deseado (spam).</p>';
    $body_user .= '<p>¡Estamos para servirte!</p>';
    $body_user .= '<p>Saludos cordiales,<br><strong>El equipo de ' . esc_html($nombre_sitio) . '</strong></p>';
    $body_user .= '</div>';
    $body_user .= '<div style="background-color: #f2f2f2; text-align: center; padding: 15px; font-size: 12px; color: #777;">';
    $body_user .= '<p style="margin:0;">' . esc_html($nombre_sitio) . ' | Dirección Calle 4 #4 - 41 - Cogua, Cundinamarca. | Whatsapp 311 5740126</p>';
    $body_user .= '</div></div>';

    $headers_user = ["From: $nombre_sitio <no-responder@$url_sitio>", "Content-Type: text/html; charset=UTF-8"];
    wp_mail( $to_user, $subject_user, $body_user, $headers_user );

    // 3.4 Guardar datos en la sesión para la página de "Gracias"
    $_SESSION['datos_ultima_cita'] = [
        'nombre' => $nombre_apellido, 'email' => $email, 'fecha' => $fecha_cita, 'hora' => $hora_cita,
        'tramite1' => $tramite1, 'categoria1' => $categoria1, 'tramite2' => $tramite2,
        'categoria2' => $categoria2, 'costo' => $costo_total_str
    ];

    // 3.5 Redirigir a la página de "Gracias"
    wp_redirect( home_url('/gracias') );
    exit;

} else {
    // --- VERIFICACIÓN FALLIDA (POSIBLE BOT) ---
    
    // Registrar el intento fallido para análisis
    $error_message = "Fallo de reCAPTCHA para IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . 
                     " - Score: " . ($response_data->score ?? 'N/A');
    error_log("RECAPTCHA_ERROR: " . $error_message);

    // Redirigir de vuelta al formulario con un mensaje de error
    // **¡IMPORTANTE!** Cambia '/contacto/' por el slug correcto de tu página de formulario.
    wp_redirect( home_url('/contacto/?recaptcha_error=1') ); 
    exit;
}
?>