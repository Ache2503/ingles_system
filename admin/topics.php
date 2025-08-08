<?php
/**
 * admin/topics.php - Solo administradores
 */

// Protección de sesión y rol de administrador
require_once __DIR__ . '/../includes/session_protection.php';
requireAdmin();

// Validar sesión
validateSession();

// Log de actividad
logUserActivity('admin_topics', 'Admin accedió a topics.php');


require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ingles/login.php');
    exit;
}

// Acciones: Crear, Editar, Eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM topics WHERE topic_id = ?");
        $stmt->execute([$_POST['topic_id']]);
        $_SESSION['message'] = 'Tema eliminado correctamente';
    } else {
        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'category' => $_POST['category'],
            'detailed_content' => $_POST['detailed_content']
        ];
        
        if (isset($_POST['topic_id']) && !empty($_POST['topic_id'])) {
            // Actualizar tema existente
            $data['topic_id'] = $_POST['topic_id'];
            $stmt = $pdo->prepare("
                UPDATE topics SET 
                title = :title, 
                description = :description, 
                category = :category,
                detailed_content = :detailed_content
                WHERE topic_id = :topic_id
            ");
            $_SESSION['message'] = 'Tema actualizado correctamente';
        } else {
            // Crear nuevo tema
            $stmt = $pdo->prepare("
                INSERT INTO topics (title, description, category, detailed_content)
                VALUES (:title, :description, :category, :detailed_content)
            ");
            $_SESSION['message'] = 'Tema creado correctamente';
        }
        
        $stmt->execute($data);
    }
    
    header('Location: topics.php');
    exit;
}

// Obtener todos los temas
$stmt = $pdo->query("SELECT * FROM topics ORDER BY category, title");
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener tema para editar
$editTopic = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM topics WHERE topic_id = ?");
    $stmt->execute([$_GET['edit']]);
    $editTopic = $stmt->fetch(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-container">
    <h1>Gestión de Temas</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <div class="admin-form">
        <h2><?= $editTopic ? 'Editar' : 'Agregar' ?> Tema</h2>
        <form method="POST">
            <?php if ($editTopic): ?>
                <input type="hidden" name="topic_id" value="<?= $editTopic['topic_id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Título</label>
                <input type="text" name="title" value="<?= $editTopic['title'] ?? '' ?>" required>
            </div>
            
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="description" required><?= $editTopic['description'] ?? '' ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Categoría</label>
                <select name="category" required>
                    <option value="grammar" <?= ($editTopic['category'] ?? '') === 'grammar' ? 'selected' : '' ?>>Gramática</option>
                    <option value="vocabulary" <?= ($editTopic['category'] ?? '') === 'vocabulary' ? 'selected' : '' ?>>Vocabulario</option>
                    <option value="tips" <?= ($editTopic['category'] ?? '') === 'tips' ? 'selected' : '' ?>>Consejos</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Contenido Detallado (HTML permitido)</label>
                <textarea name="detailed_content" rows="10"><?= $editTopic['detailed_content'] ?? '' ?></textarea>
                <small>Puedes usar HTML para formatear el contenido. Ejemplos: &lt;h3&gt;, &lt;ul&gt;, &lt;li&gt;, etc.</small>
            </div>
            
            <button type="submit" class="btn btn-primary"><?= $editTopic ? 'Actualizar' : 'Guardar' ?> Tema</button>
            
            <?php if ($editTopic): ?>
                <a href="topics.php" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="admin-list">
        <h2>Lista de Temas</h2>
        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Categoría</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topics as $topic): ?>
                <tr>
                    <td><?= htmlspecialchars($topic['title']) ?></td>
                    <td><?= htmlspecialchars($topic['description']) ?></td>
                    <td><?= ucfirst($topic['category']) ?></td>
                    <td class="actions">
                        <a href="topics.php?edit=<?= $topic['topic_id'] ?>" class="btn btn-small">Editar</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="topic_id" value="<?= $topic['topic_id'] ?>">
                            <button type="submit" name="delete" class="btn btn-small btn-danger" 
                                    onclick="return confirm('¿Estás seguro de eliminar este tema?')">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>