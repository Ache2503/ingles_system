<?php
require_once __DIR__ . '/db.php';

/**
 * Registra un nuevo usuario en el sistema
 * 
 * @param string $username Nombre de usuario
 * @param string $email Correo electrónico
 * @param string $password Contraseña
 * @return array Array con 'success' (bool) y 'message' (string) o 'user_id' (int)
 */
function registerUser($username, $email, $password) {
    global $pdo;
    
    // Validaciones básicas
    if (empty($username)) {
        return array('success' => false, 'message' => 'El nombre de usuario es requerido');
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return array('success' => false, 'message' => 'El correo electrónico no es válido');
    }
    
    if (empty($password) || strlen($password) < 6) {
        return array('success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres');
    }
    
    // Verificar si el usuario ya existe
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->execute(array($username, $email));
        
        if ($stmt->rowCount() > 0) {
            return array('success' => false, 'message' => 'El nombre de usuario o correo ya están registrados');
        }
        
        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Insertar nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, created_at, role) VALUES (?, ?, ?, NOW(), 'student')");
        $stmt->execute(array($username, $email, $password_hash));
        
        $user_id = $pdo->lastInsertId();
        
        return array(
            'success' => true,
            'message' => 'Registro exitoso',
            'user_id' => $user_id
        );
        
    } catch (PDOException $e) {
        error_log("Error al registrar usuario: " . $e->getMessage());
        return array('success' => false, 'message' => 'Error al registrar el usuario. Por favor intenta nuevamente.');
    }
}

/**
 * Autentica a un usuario
 * 
 * @param string $email Correo electrónico
 * @param string $password Contraseña
 * @return array Array con 'success' (bool), 'message' (string) y datos del usuario si es exitoso
 */
function loginUser($email, $password) {
    global $pdo;
    
    try {
        // Buscar usuario por email (incluyendo el rol)
        $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, role FROM users WHERE email = ?");
        $stmt->execute(array($email));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return array('success' => false, 'message' => 'Correo electrónico o contraseña incorrectos');
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            return array('success' => false, 'message' => 'Correo electrónico o contraseña incorrectos');
        }
        
        // Iniciar sesión
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role']; // Guardar el rol en sesión
        
        return array(
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'user' => array(
                'id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            )
        );
        
    } catch (PDOException $e) {
        error_log("Error al iniciar sesión: " . $e->getMessage());
        return array('success' => false, 'message' => 'Error al iniciar sesión. Por favor intenta nuevamente.');
    }
}

/**
 * Cierra la sesión del usuario actual
 */
function logoutUser() {
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Borrar la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
}

/**
 * Verifica si el usuario está autenticado
 * 
 * @return bool True si el usuario está autenticado, false en caso contrario
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Obtiene los datos del usuario actual
 * 
 * @return array|null Datos del usuario o null si no está autenticado
 */
function getCurrentUser() {
    if (!isUserLoggedIn()) {
        return null;
    }
    
    return array(
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'] ?? 'student'
    );
}