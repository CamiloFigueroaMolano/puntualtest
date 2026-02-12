<?php
// ====================================================================================
// SHORTCODE [resumen_cita]
// ====================================================================================
function mostrar_resumen_cita_shortcode() {
    if ( session_status() == PHP_SESSION_NONE ) { session_start(); }

    if ( isset( $_SESSION['datos_ultima_cita'] ) ) {
        $datos = $_SESSION['datos_ultima_cita'];
        unset( $_SESSION['datos_ultima_cita'] );
        $fecha_obj = new DateTime( $datos['fecha'] );
        $fecha_formateada = date_i18n( 'l, j \d\e F \d\e Y', $fecha_obj->getTimestamp() );

        $output = '<div class="cita-resumen-box">';
        $output .= '<h4>¡Hola, ' . esc_html( $datos['nombre'] ) . '!</h4>';
        $output .= '<p>Este es un resumen de la cita que has agendado:</p>';
        $output .= '<ul>';
        $output .= '<li><strong>Fecha y Hora:</strong> ' . esc_html( $fecha_formateada ) . ' a las ' . esc_html( $datos['hora'] ) . '</li>';
        $output .= '<li><strong>Trámite Principal:</strong> ' . esc_html( $datos['tramite1'] ) . ' (' . esc_html( $datos['categoria1'] ) . ')</li>';
        if ( ! empty( $datos['tramite2'] ) ) {
            $output .= '<li><strong>Segundo Trámite:</strong> ' . esc_html( $datos['tramite2'] ) . ' (' . esc_html( $datos['categoria2'] ) . ')</li>';
        }
        $output .= '<li><strong>Costo Total Estimado:</strong> ' . esc_html( $datos['costo'] ) . '</li>';
        $output .= '</ul>';
        $output .= '</div>';
        return $output;
    } else {
        return '<p>Gracias por tu interés en nuestros servicios.</p>';
    }
}
add_shortcode( 'resumen_cita', 'mostrar_resumen_cita_shortcode' );