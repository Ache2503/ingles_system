<?php 
// Incluir los archivos necesarios al inicio con rutas corregidas
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si ya está logueado
if (isUserLoggedIn()) {
    $user = getCurrentUser();
    // Redirigir según el rol
    if ($user['role'] === 'admin') {
        header('Location: /ingles/admin/index.php');
    } else {
        header('Location: /ingles/pages/topics.php');
    }
    exit;
}

// Procesar el formulario solo si es POST
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = loginUser($_POST['email'], $_POST['password']);
    if ($result['success']) {
        // Redirigir según el rol
        if ($result['user']['role'] === 'admin') {
            header('Location: /ingles/admin/index.php');
        } else {
            header('Location: /ingles/pages/topics.php');
        }
        exit;
    } else {
        $error = $result['message'];
    }
}

// Incluir el header después de posibles redirecciones
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <h2>Iniciar Sesión</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Correo Electrónico</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" required>
        </div>
        
        <button type="submit" class="btn">Ingresar</button>
    </form>
    
    <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>