<?php
/**
 * Sistema de Protección de Sesión
 * Incluir este archivo en todas las páginas que requieren autenticación
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verificar si el usuario está autenticado
 */
function requireLogin($redirectTo = '/ingles/auth/login.php') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Verificar si el usuario tiene un rol específico
 */
function requireRole($requiredRole, $redirectTo = '/ingles/auth/login.php') {
    requireLogin($redirectTo);
    
    $userRole = $_SESSION['role'] ?? 'user';
    
    if ($userRole !== $requiredRole && $userRole !== 'admin') {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Verificar si el usuario es administrador
 */
function requireAdmin($redirectTo = '/ingles/auth/login.php') {
    requireRole('admin', $redirectTo);
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUserInfo() {
    requireLogin();
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['name'] ?? 'Usuario',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'user'
    ];
}

/**
 * Verificar si la sesión es válida (no expirada)
 */
function validateSession($maxInactiveTime = 3600) { // 1 hora por defecto
    if (isset($_SESSION['last_activity'])) {
        $inactiveTime = time() - $_SESSION['last_activity'];
        
        if ($inactiveTime > $maxInactiveTime) {
            session_destroy();
            header('Location: /ingles/auth/login.php?expired=1');
            exit;
        }
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Logging de actividad de usuario
 */
function logUserActivity($action, $details = '') {
    if (isset($_SESSION['user_id'])) {
        $logData = [
            'user_id' => $_SESSION['user_id'],
            'action' => $action,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        // Aquí se podría guardar en base de datos o archivo de log
        error_log("User Activity: " . json_encode($logData));
    }
}
?>
