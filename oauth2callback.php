<?php
session_start();

if (isset($_GET['code'])) {
    $_SESSION['auth_code'] = $_GET['code'];
    echo "✅ Código recibido correctamente. Ahora vuelve a la consola y ejecuta get-token.php.";
} else {
    echo "❌ No se recibió ningún código.";
}
?>
