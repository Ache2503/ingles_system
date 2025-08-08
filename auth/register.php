<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Inicializar variables y errores
$errors = [];
$username = '';
$email = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y limpiar los datos del formulario
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validaciones
    if (empty($username)) {
        $errors['username'] = 'El nombre de usuario es requerido';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'El nombre de usuario debe tener al menos 4 caracteres';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'El nombre de usuario solo puede contener letras, números y guiones bajos';
    } else {
        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors['username'] = 'Este nombre de usuario ya está en uso';
        }
    }

    if (empty($email)) {
        $errors['email'] = 'El correo electrónico es requerido';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Por favor ingresa un correo electrónico válido';
    } else {
        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Este correo electrónico ya está registrado';
        }
    }

    if (empty($password)) {
        $errors['password'] = 'La contraseña es requerida';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Las contraseñas no coinciden';
    }

    // Si no hay errores, registrar al usuario
    if (empty($errors)) {
        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Insertar en la base de datos
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash]);

            // Obtener el ID del nuevo usuario
            $user_id = $pdo->lastInsertId();

            // Iniciar sesión automáticamente
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;

            // Redirigir al dashboard
            header('Location: pages/topics.php');
            exit;
        } catch (PDOException $e) {
            $errors['database'] = 'Error al registrar: ' . $e->getMessage();
        }
    }
}

// Incluir el header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <h2>Crear una cuenta</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" novalidate>
        <div class="form-group">
            <label for="username">Nombre de usuario</label>
            <input type="text" id="username" name="username" 
                   value="<?php echo htmlspecialchars($username); ?>"
                   class="<?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" required>
            <?php if (isset($errors['username'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['username']); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($email); ?>"
                   class="<?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" required>
            <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" 
                   class="<?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" required>
            <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['password']); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirmar contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" 
                   class="<?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" required>
            <?php if (isset($errors['confirm_password'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-block">Registrarse</button>
    </form>
    
    <div class="auth-footer">
        ¿Ya tienes una cuenta? <a href="auth/login.php">Inicia sesión aquí</a>
    </div>
</div>

<?php 
// Incluir el footer
require_once __DIR__ . '/../includes/footer.php';
?>