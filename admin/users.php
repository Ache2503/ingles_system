<?php
/**
 * admin/users.php - Solo administradores
 */

// Protección de sesión y rol de administrador
require_once __DIR__ . '/../includes/session_protection.php';
requireAdmin();

// Validar sesión
validateSession();

// Log de actividad
logUserActivity('admin_users', 'Admin accedió a users.php');


require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ingles/login.php');
    exit;
}

// Acciones: Editar rol, Eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        // No permitir eliminarse a sí mismo
        if ($_POST['user_id'] != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$_POST['user_id']]);
            $_SESSION['message'] = 'Usuario eliminado correctamente';
        } else {
            $_SESSION['error'] = 'No puedes eliminarte a ti mismo';
        }
    } elseif (isset($_POST['role'])) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->execute([$_POST['role'], $_POST['user_id']]);
        $_SESSION['message'] = 'Rol de usuario actualizado';
    }
    
    header('Location: users.php');
    exit;
}

// Obtener todos los usuarios
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-container">
    <h1>Gestión de Usuarios</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <div class="admin-list">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['user_id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                            <select name="role" onchange="this.form.submit()">
                                <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Estudiante</option>
                                <option value="teacher" <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>Profesor</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                        </form>
                    </td>
                    <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                    <td class="actions">
                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                            <button type="submit" name="delete" class="btn btn-small btn-danger" 
                                    onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                                Eliminar
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>