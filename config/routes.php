<?php
/**
 * Configuración de rutas para la nueva estructura de carpetas
 * Este archivo define todas las rutas del sistema
 */

// URL base del sistema
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/ingles');
}

// Definir rutas principales
define('AUTH_PATH', BASE_URL . '/auth');
define('PAGES_PATH', BASE_URL . '/pages');
define('API_PATH', BASE_URL . '/api');
define('ASSETS_PATH', BASE_URL . '/assets');
define('INCLUDES_PATH', BASE_URL . '/includes');

// Rutas específicas de autenticación
define('LOGIN_URL', AUTH_PATH . '/login.php');
define('REGISTER_URL', AUTH_PATH . '/register.php');
define('LOGOUT_URL', AUTH_PATH . '/logout.php');

// Rutas de páginas principales
define('TOPICS_URL', PAGES_PATH . '/topics.php');
define('PRACTICE_URL', PAGES_PATH . '/practice.php');
define('PROFILE_URL', PAGES_PATH . '/profile.php');
define('PROGRESS_URL', PAGES_PATH . '/progress.php');
define('DASHBOARD_URL', PAGES_PATH . '/dashboard.php');
define('TOPIC_DETAIL_URL', PAGES_PATH . '/topic_detail.php');

// Rutas de APIs
define('QUIZ_RESULT_API', API_PATH . '/quiz-result-api.php');

/**
 * Función helper para redireccionar con la nueva estructura
 */
function redirectTo($route) {
    header("Location: $route");
    exit;
}

/**
 * Función para obtener la ruta correcta basada en el archivo actual
 */
function getCorrectPath($relativePath) {
    // Determinar la profundidad actual basada en la URL
    $currentPath = $_SERVER['REQUEST_URI'];
    $depth = substr_count($currentPath, '/') - substr_count(BASE_URL, '/');
    
    // Ajustar el path relativo
    $prefix = str_repeat('../', max(0, $depth - 1));
    return $prefix . $relativePath;
}

/**
 * Función para incluir archivos con la ruta correcta
 */
function includeFile($file) {
    $basePath = dirname($_SERVER['SCRIPT_FILENAME']);
    $rootPath = dirname(dirname($basePath)) . '/ingles'; // Ajustar según tu estructura
    
    // Intentar diferentes rutas posibles
    $possiblePaths = [
        $basePath . '/' . $file,
        $rootPath . '/' . $file,
        dirname($basePath) . '/' . $file,
        '../' . $file,
        '../../' . $file
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    // Si no se encuentra, devolver la ruta original
    return $file;
}
?>
