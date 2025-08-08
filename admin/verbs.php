<?php
/**
 * admin/verbs.php - Solo administradores
 */

// Protección de sesión y rol de administrador
require_once __DIR__ . '/../includes/session_protection.php';
requireAdmin();

// Validar sesión
validateSession();

// Log de actividad
logUserActivity('admin_verbs', 'Admin accedió a verbs.php');


require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar permisos de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ingles/login.php');
    exit;
}

// Procesar formularios (Agregar/Editar/Eliminar/Importar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        // Eliminar verbo
        $stmt = $pdo->prepare("DELETE FROM irregular_verbs WHERE verb_id = ?");
        $stmt->execute([$_POST['verb_id']]);
        $_SESSION['message'] = 'Verbo eliminado correctamente';
    } 
    elseif (isset($_FILES['csv_file'])) {
        // Importar desde CSV
        try {
            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, "r");
            $imported = 0;
            
            // Saltar la primera línea (cabeceras)
            fgetcsv($handle, 1000, ",");
            
            $pdo->beginTransaction();
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) == 4) {
                    $stmt = $pdo->prepare("
                        INSERT INTO irregular_verbs 
                        (base_form, past_simple, past_participle, meaning)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        past_simple = VALUES(past_simple),
                        past_participle = VALUES(past_participle),
                        meaning = VALUES(meaning)
                    ");
                    $stmt->execute([$data[0], $data[1], $data[2], $data[3]]);
                    $imported++;
                }
            }
            
            $pdo->commit();
            fclose($handle);
            $_SESSION['message'] = "Se importaron $imported verbos correctamente";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error al importar: " . $e->getMessage();
        }
    }
    else {
        // Validar datos para agregar/editar
        $data = [
            'base_form' => trim($_POST['base_form']),
            'past_simple' => trim($_POST['past_simple']),
            'past_participle' => trim($_POST['past_participle']),
            'meaning' => trim($_POST['meaning']),
            'example' => trim($_POST['example'] ?? '')
        ];

        if (empty($data['base_form']) || empty($data['past_simple']) || empty($data['past_participle'])) {
            $_SESSION['error'] = 'Las tres formas del verbo son obligatorias';
        } else {
            if (isset($_POST['verb_id']) && !empty($_POST['verb_id'])) {
                // Actualizar verbo existente
                $data['verb_id'] = $_POST['verb_id'];
                $stmt = $pdo->prepare("
                    UPDATE irregular_verbs SET 
                    base_form = :base_form, 
                    past_simple = :past_simple, 
                    past_participle = :past_participle,
                    meaning = :meaning,
                    example = :example
                    WHERE verb_id = :verb_id
                ");
                $_SESSION['message'] = 'Verbo actualizado correctamente';
            } else {
                // Crear nuevo verbo
                $stmt = $pdo->prepare("
                    INSERT INTO irregular_verbs 
                    (base_form, past_simple, past_participle, meaning, example)
                    VALUES (:base_form, :past_simple, :past_participle, :meaning, :example)
                ");
                $_SESSION['message'] = 'Verbo agregado correctamente';
            }
            
            $stmt->execute($data);
        }
    }
    
    header('Location: verbs.php');
    exit;
}

// Configuración de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Búsqueda y filtrado
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];

if (!empty($search)) {
    $where = "WHERE base_form LIKE :search OR past_simple LIKE :search OR past_participle LIKE :search OR meaning LIKE :search";
    $params[':search'] = "%$search%";
}

// Obtener total de verbos para paginación
$stmt = $pdo->prepare("SELECT COUNT(*) FROM irregular_verbs $where");
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$totalVerbs = $stmt->fetchColumn();
$totalPages = ceil($totalVerbs / $perPage);

// Obtener verbos para mostrar
$sql = "SELECT * FROM irregular_verbs $where ORDER BY base_form LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$verbs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener verbo para editar (si existe)
$editVerb = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM irregular_verbs WHERE verb_id = ?");
    $stmt->execute([$_GET['edit']]);
    $editVerb = $stmt->fetch(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-container">
    <h1>Gestión de Verbos Irregulares</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <!-- Formulario de importación (oculto por defecto) -->
    <div id="import-form" class="import-form" style="display: none;">
        <h3>Importar Verbos desde CSV</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Archivo CSV (formato: infinitivo,pasado,participio,significado,ejemplo)</label>
                <input type="file" name="csv_file" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-primary">Importar</button>
            <button type="button" id="cancel-import" class="btn btn-secondary">Cancelar</button>
        </form>
    </div>
    
    <!-- Formulario de agregar/editar -->
    <div class="admin-form">
        <h2><?= $editVerb ? 'Editar' : 'Agregar' ?> Verbo</h2>
        <form method="POST">
            <?php if ($editVerb): ?>
                <input type="hidden" name="verb_id" value="<?= $editVerb['verb_id'] ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Forma Base (Infinitivo)</label>
                    <input type="text" name="base_form" value="<?= htmlspecialchars($editVerb['base_form'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Pasado Simple</label>
                    <input type="text" name="past_simple" value="<?= htmlspecialchars($editVerb['past_simple'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Participio Pasado</label>
                    <input type="text" name="past_participle" value="<?= htmlspecialchars($editVerb['past_participle'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Significado en Español</label>
                    <input type="text" name="meaning" value="<?= htmlspecialchars($editVerb['meaning'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Ejemplo de Uso</label>
                <textarea name="example" rows="2"><?= htmlspecialchars($editVerb['example'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <?= $editVerb ? 'Actualizar' : 'Guardar' ?> Verbo
            </button>
            
            <?php if ($editVerb): ?>
                <a href="verbs.php" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Lista de verbos -->
    <div class="admin-list">
        <h2>Lista de Verbos Irregulares (<?= $totalVerbs ?> verbos)</h2>
        <!-- Barra de herramientas -->
        <div class="verbs-toolbar">
            <div class="search-box">
                <form method="GET" action="verbs.php">
                    <input type="text" name="search" placeholder="Buscar verbos..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn">Buscar</button>
                    <?php if (!empty($search)): ?>
                        <a href="verbs.php" class="btn btn-secondary">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="import-export">
                <button id="toggle-import" class="btn">Importar CSV</button>
                <a href="export_verbs.php" class="btn btn-secondary">Exportar CSV</a>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="verbs-table">
                <thead>
                    <tr>
                        <th>Infinitivo</th>
                        <th>Pasado Simple</th>
                        <th>Participio</th>
                        <th>Significado</th>
                        <th>Ejemplo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($verbs as $verb): ?>
                    <tr>
                        <td><?= htmlspecialchars($verb['base_form']) ?></td>
                        <td><?= htmlspecialchars($verb['past_simple']) ?></td>
                        <td><?= htmlspecialchars($verb['past_participle']) ?></td>
                        <td><?= htmlspecialchars($verb['meaning']) ?></td>
                        <td><?= !empty($verb['example']) ? htmlspecialchars($verb['example']) : '-' ?></td>
                        <td class="actions">
                            <a href="verbs.php?edit=<?= $verb['verb_id'] ?>" class="btn btn-small">Editar</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="verb_id" value="<?= $verb['verb_id'] ?>">
                                <button type="submit" name="delete" class="btn btn-small btn-danger" 
                                        onclick="return confirm('¿Estás seguro de eliminar este verbo?')">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="verbs.php?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" class="btn">Anterior</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="verbs.php?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                   class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="verbs.php?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" class="btn">Siguiente</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Mostrar/ocultar formulario de importación
document.getElementById('toggle-import').addEventListener('click', function() {
    document.getElementById('import-form').style.display = 'block';
});

document.getElementById('cancel-import').addEventListener('click', function() {
    document.getElementById('import-form').style.display = 'none';
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>