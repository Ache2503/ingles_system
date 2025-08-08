<?php
/**
 * API api/test.php - Solo usuarios autenticados
 */

// Protección de sesión para API
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Validar sesión
validateSession();

// Headers para API
header('Content-Type: application/json');

// Log de actividad API
logUserActivity('api_test', 'Usuario accedió a API test.php');


session_start();
header('Content-Type: application/json');

// Simular usuario logueado para prueba
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Simular usuario
}

echo json_encode([
    'success' => true,
    'message' => 'Test API funcionando correctamente',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
