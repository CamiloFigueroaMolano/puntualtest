<?php
/**
 * Handler para el formulario de contacto rápido (header).
 * Guarda el nombre y teléfono en la base de datos.
 */

// Carga el entorno de WordPress para tener acceso a sus funciones.
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

// Verifica que la solicitud sea por el método POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si no es POST, no hagas nada.
    wp_send_json_error(['message' => 'Método no permitido.']);
    exit;
}

// Recoge y limpia los datos enviados desde el formulario.
$nombre = isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '';
$telefono = isset($_POST['telefono']) ? sanitize_text_field($_POST['telefono']) : '';

// Valida que los campos no estén vacíos.
if (empty($nombre) || empty($telefono)) {
    wp_send_json_error(['message' => 'El nombre y el teléfono son obligatorios.']);
    exit;
}

// Accede a la base de datos de WordPress.
global $wpdb;

// Define el nombre de tu nueva tabla (con el prefijo de WordPress).
$nombre_tabla = $wpdb->prefix . 'contacto_whatsapp';

// Inserta los datos en la tabla.
$resultado = $wpdb->insert(
    $nombre_tabla,
    [
        'nombre_completo' => $nombre,
        'telefono'        => $telefono,
        'fecha_registro'  => current_time('mysql'),
    ]
);

// Responde al JavaScript para que sepa si la operación fue exitosa.
if ($resultado) {
    wp_send_json_success(['message' => 'Datos guardados correctamente.']);
} else {
    wp_send_json_error(['message' => 'Error al guardar los datos.']);
}

exit; // Termina la ejecución.