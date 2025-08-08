<?php
/**
 * Sistema de Navegación y Enlaces del Sistema
 * Centraliza todas las rutas y enlaces del sistema
 */

class NavigationSystem {
    private static $baseURL;
    private static $routes;
    
    public static function init($baseURL = null) {
        // Auto-detectar URL base si no se proporciona
        if ($baseURL === null) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseURL = $protocol . '://' . $host . '/ingles/';
        }
        self::$baseURL = $baseURL;
        self::$routes = [
            // Páginas de autenticación
            'login' => 'auth/login.php',
            'register' => 'auth/register.php',
            'logout' => 'auth/logout.php',
            
            // Páginas principales
            'home' => 'index.php',
            'topics' => 'pages/topics.php',
            'practice' => 'pages/practice.php',
            'profile' => 'pages/profile.php',
            'progress' => 'pages/progress.php',
            'dashboard' => 'pages/dashboard.php',
            'notifications' => 'pages/notifications.php',
            
            // APIs
            'quiz_result_api' => 'api/quiz-result-api.php',
            'check_achievements' => 'api/check_achievements.php',
            'user_stats' => 'api/get_user_stats.php',
            
            // Admin
            'admin_dashboard' => 'admin/index.php',
            'admin_users' => 'admin/users.php',
            'admin_topics' => 'admin/topics.php',
            'admin_questions' => 'admin/questions.php',
            
            // Testing y desarrollo
            'system_test' => 'tests/comprehensive_test.php',
            'system_verifier' => 'tests/system_verifier.php'
        ];
    }
    
    /**
     * Obtener URL completa para una ruta
     */
    public static function url($route, $params = []) {
        if (!isset(self::$routes[$route])) {
            return self::$baseURL;
        }
        
        $url = self::$baseURL . self::$routes[$route];
        
        // Asegurar que la URL es absoluta
        if (!preg_match('/^https?:\/\//', $url)) {
            // Auto-detectar protocolo y host si la URL no es absoluta
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $url = $protocol . '://' . $host . '/ingles/' . self::$routes[$route];
        }
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    /**
     * Obtener ruta relativa
     */
    public static function path($route) {
        return self::$routes[$route] ?? '';
    }
    
    /**
     * Redireccionar a una ruta
     */
    public static function redirect($route, $params = []) {
        $url = self::url($route, $params);
        header("Location: $url");
        exit;
    }
    
    /**
     * Verificar si la ruta actual coincide
     */
    public static function isCurrentRoute($route) {
        $currentPath = $_SERVER['REQUEST_URI'];
        $routePath = self::path($route);
        
        return strpos($currentPath, $routePath) !== false;
    }
    
    /**
     * Generar menú de navegación
     */
    public static function generateMenu($userRole = null) {
        $menu = [];
        
        if ($userRole === 'admin') {
            $menu = [
                'Dashboard Admin' => 'admin_dashboard',
                'Usuarios' => 'admin_users',
                'Temas' => 'admin_topics',
                'Preguntas' => 'admin_questions',
                'Sistema' => 'system_test',
                'Cerrar Sesión' => 'logout'
            ];
        } elseif ($userRole === 'user' || $userRole === 'student') {
            $menu = [
                'Inicio' => 'home',
                'Temas' => 'topics',
                'Mi Progreso' => 'progress',
                'Perfil' => 'profile',
                'Notificaciones' => 'notifications',
                'Cerrar Sesión' => 'logout'
            ];
        } else {
            $menu = [
                'Inicio' => 'home',
                'Iniciar Sesión' => 'login',
                'Registrarse' => 'register'
            ];
        }
        
        return $menu;
    }
    
    /**
     * Generar HTML del menú
     */
    public static function renderMenu($userRole = null, $cssClass = 'nav-menu') {
        $menu = self::generateMenu($userRole);
        $html = "<nav class='{$cssClass}'>";
        
        foreach ($menu as $title => $route) {
            $url = self::url($route);
            $activeClass = self::isCurrentRoute($route) ? ' active' : '';
            $html .= "<a href='{$url}' class='nav-item{$activeClass}'>{$title}</a>";
        }
        
        $html .= "</nav>";
        return $html;
    }
    
    /**
     * Obtener breadcrumbs
     */
    public static function getBreadcrumbs() {
        $currentPath = $_SERVER['REQUEST_URI'];
        $breadcrumbs = ['Inicio' => self::url('home')];
        
        // Detectar sección actual
        if (strpos($currentPath, '/auth/') !== false) {
            $breadcrumbs['Autenticación'] = '#';
        } elseif (strpos($currentPath, '/pages/') !== false) {
            $breadcrumbs['Páginas'] = '#';
        } elseif (strpos($currentPath, '/admin/') !== false) {
            $breadcrumbs['Administración'] = self::url('admin_dashboard');
        } elseif (strpos($currentPath, '/tests/') !== false) {
            $breadcrumbs['Pruebas del Sistema'] = '#';
        }
        
        return $breadcrumbs;
    }
}

// Inicializar el sistema de navegación
NavigationSystem::init();

/**
 * Funciones helper para usar en las vistas
 */
function nav_url($route, $params = []) {
    return NavigationSystem::url($route, $params);
}

function nav_redirect($route, $params = []) {
    NavigationSystem::redirect($route, $params);
}

function nav_menu($userRole = null) {
    return NavigationSystem::renderMenu($userRole);
}

function is_current_route($route) {
    return NavigationSystem::isCurrentRoute($route);
}
?>
