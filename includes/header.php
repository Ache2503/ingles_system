<?php
// Inicia la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define la URL base si no está definida
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/ingles');
}

// Incluir sistema de navegación
require_once __DIR__ . '/navigation.php';

// Obtener rol del usuario
$userRole = null;
if (isset($_SESSION['user_id'])) {
    $userRole = $_SESSION['role'] ?? 'user';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repaso de Inglés - Evaluación Extraordinaria</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <script src="<?php echo BASE_URL; ?>/assets/js/notifications.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/bookmarks.js"></script>
</head>
<body <?php if (isset($_SESSION['user_id'])): ?>data-user-id="<?php echo $_SESSION['user_id']; ?>"<?php endif; ?>>
    <header>
        <div class="container">
            <h1>Repaso de Inglés</h1>
            <nav>
                <ul>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="<?php echo nav_url('topics'); ?>">📚 Temas</a></li>
                        <li><a href="<?php echo nav_url('progress'); ?>">� Progreso</a></li>
                        <li><a href="<?php echo nav_url('profile'); ?>">👤 Perfil</a></li>
                        <li><a href="<?php echo nav_url('notifications'); ?>">
                            🔔 <span id="notification-badge" class="notification-badge" style="display:none;">0</span>
                        </a></li>
                        <?php if ($userRole === 'admin'): ?>
                            <li><a href="<?php echo nav_url('admin_dashboard'); ?>">⚙️ Admin</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo nav_url('logout'); ?>">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo nav_url('login'); ?>">Iniciar Sesión</a></li>
                        <li><a href="<?php echo nav_url('register'); ?>">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">