<?php
/**
 * admin/questions.php - Solo administradores
 */

// Protección de sesión y rol de administrador
require_once __DIR__ . '/../includes/session_protection.php';
requireAdmin();

// Validar sesión
validateSession();

// Log de actividad
logUserActivity('admin_questions', 'Admin accedió a questions.php');


require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar si es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ingles/login.php');
    exit;
}

// Inicializar variables
$error = '';
$success = '';
$questions = [];
$topics = [];

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $question_id = (int)($_POST['question_id'] ?? 0);

    try {
        // Validación de campos para crear/actualizar
        if (in_array($action, ['create', 'update'])) {
            $required = ['topic_id', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'difficulty'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("El campo '".str_replace('_', ' ', $field)."' es requerido");
                }
            }
        }

        switch ($action) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO questions (topic_id, question_text, option_a, option_b, option_c, option_d, correct_answer, difficulty) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['topic_id'],
                    $_POST['question_text'],
                    $_POST['option_a'],
                    $_POST['option_b'],
                    $_POST['option_c'],
                    $_POST['option_d'],
                    $_POST['correct_answer'],
                    $_POST['difficulty']
                ]);
                $success = "Pregunta creada exitosamente!";
                break;
                
            case 'update':
                $stmt = $pdo->prepare("UPDATE questions SET 
                    topic_id = ?, question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, 
                    correct_answer = ?, difficulty = ? WHERE question_id = ?");
                $stmt->execute([
                    $_POST['topic_id'],
                    $_POST['question_text'],
                    $_POST['option_a'],
                    $_POST['option_b'],
                    $_POST['option_c'],
                    $_POST['option_d'],
                    $_POST['correct_answer'],
                    $_POST['difficulty'],
                    $question_id
                ]);
                $success = "Pregunta actualizada exitosamente!";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM questions WHERE question_id = ?");
                $stmt->execute([$question_id]);
                $success = "Pregunta eliminada exitosamente!";
                break;
        }
    } catch (PDOException $e) {
        error_log("Error en questions.php: " . $e->getMessage());
        $error = "Error de base de datos: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener datos para mostrar
try {
    $questions = $pdo->query("
        SELECT q.*, t.title as topic_title 
        FROM questions q
        JOIN topics t ON q.topic_id = t.topic_id
        ORDER BY q.question_id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $topics = $pdo->query("SELECT topic_id, title FROM topics ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar datos: " . $e->getMessage());
    $error = "Error al cargar los datos. Por favor intente más tarde.";
}

require_once __DIR__ . '/../includes/admin_header.php';
?>

<style>
    :root {
        --primary-color: #3498db;
        --secondary-color: #2c3e50;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --warning-color: #ffc107;
        --light-color: #f8f9fa;
        --dark-color: #343a40;
        --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
    
    .questions-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .questions-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .question-card {
        background: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
        border-left: 4px solid var(--primary-color);
    }
    
    .question-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .question-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .question-topic {
        font-weight: bold;
        color: var(--primary-color);
    }
    
    .question-difficulty {
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .difficulty-easy { background-color: #d4edda; color: #155724; }
    .difficulty-medium { background-color: #fff3cd; color: #856404; }
    .difficulty-hard { background-color: #f8d7da; color: #721c24; }
    
    .question-text {
        font-size: 1.1rem;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }
    
    .options-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .option {
        padding: 1rem;
        border-radius: 0.5rem;
        background: var(--light-color);
        position: relative;
    }
    
    .correct-option {
        background-color: #d4edda;
        border-left: 4px solid var(--success-color);
    }
    
    .option-letter {
        font-weight: bold;
        margin-right: 0.5rem;
    }
    
    .correct-badge {
        position: absolute;
        top: -10px;
        right: -10px;
        background: var(--success-color);
        color: white;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: bold;
    }
    
    .question-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    
    .btn-primary {
        background: var(--primary-color);
        color: white;
    }
    
    .btn-danger {
        background: var(--danger-color);
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }
    
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        overflow-y: auto;
    }
    
    .modal-content {
        background: white;
        margin: 2rem auto;
        padding: 2rem;
        border-radius: 0.5rem;
        width: 90%;
        max-width: 700px;
        box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.2);
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 0.25rem;
        font-size: 1rem;
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 2rem;
    }
</style>

<div class="questions-container">
    <div class="questions-header">
        <h1>Gestión de Preguntas</h1>
        <button class="btn btn-primary" onclick="openModal('create')">+ Nueva Pregunta</button>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <div class="questions-list">
        <?php if (empty($questions)): ?>
            <div class="alert alert-info">No hay preguntas registradas.</div>
        <?php else: ?>
            <?php foreach ($questions as $question): ?>
                <div class="question-card">
                    <div class="question-header">
                        <div class="question-meta">
                            <span class="question-topic"><?= htmlspecialchars($question['topic_title']) ?></span>
                            <span class="question-difficulty difficulty-<?= strtolower($question['difficulty']) ?>">
                                <?= htmlspecialchars($question['difficulty']) ?>
                            </span>
                        </div>
                        <div class="question-actions">
                            <button class="btn btn-primary" onclick="openModal('edit', <?= $question['question_id'] ?>)">
                                Editar
                            </button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="question_id" value="<?= $question['question_id'] ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar esta pregunta?')">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="question-text">
                        <?= htmlspecialchars($question['question_text']) ?>
                    </div>
                    
                    <div class="options-container">
                        <?php 
                        $options = [
                            'A' => $question['option_a'],
                            'B' => $question['option_b'],
                            'C' => $question['option_c'],
                            'D' => $question['option_d']
                        ];
                        
                        foreach ($options as $letter => $text): ?>
                            <div class="option <?= $question['correct_answer'] === $letter ? 'correct-option' : '' ?>">
                                <span class="option-letter"><?= $letter ?>.</span>
                                <?= htmlspecialchars($text) ?>
                                <?php if ($question['correct_answer'] === $letter): ?>
                                    <span class="correct-badge">✓</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para crear/editar preguntas -->
<div id="questionModal" class="modal">
    <div class="modal-content">
        <span style="float:right; cursor:pointer; font-size:1.5rem;" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Nueva Pregunta</h2>
        
        <form id="questionForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="question_id" id="questionId" value="0">
            
            <div class="form-group">
                <label for="topic_id">Tema</label>
                <select class="form-control" name="topic_id" id="topic_id" required>
                    <option value="">Seleccione un tema</option>
                    <?php foreach ($topics as $topic): ?>
                        <option value="<?= $topic['topic_id'] ?>"><?= htmlspecialchars($topic['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="question_text">Pregunta</label>
                <textarea class="form-control" name="question_text" id="question_text" rows="4" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="difficulty">Dificultad</label>
                <select class="form-control" name="difficulty" id="difficulty" required>
                    <option value="Easy">Fácil</option>
                    <option value="Medium">Media</option>
                    <option value="Hard">Difícil</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Opciones</label>
                <?php foreach (['A', 'B', 'C', 'D'] as $letter): ?>
                    <div class="form-group">
                        <label for="option_<?= strtolower($letter) ?>">Opción <?= $letter ?></label>
                        <input type="text" class="form-control" name="option_<?= strtolower($letter) ?>" 
                               id="option_<?= strtolower($letter) ?>" required>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="form-group">
                <label for="correct_answer">Respuesta Correcta</label>
                <select class="form-control" name="correct_answer" id="correct_answer" required>
                    <?php foreach (['A', 'B', 'C', 'D'] as $letter): ?>
                        <option value="<?= $letter ?>">Opción <?= $letter ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(action, questionId = 0) {
        const modal = document.getElementById('questionModal');
        const modalTitle = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const questionIdInput = document.getElementById('questionId');
        
        if (action === 'create') {
            modalTitle.textContent = 'Nueva Pregunta';
            formAction.value = 'create';
            questionIdInput.value = '0';
            document.getElementById('questionForm').reset();
        } else if (action === 'edit' && questionId > 0) {
            modalTitle.textContent = 'Editar Pregunta';
            formAction.value = 'update';
            questionIdInput.value = questionId;
            
            const question = questionsData.find(q => q.question_id == questionId);
            if (question) {
                document.getElementById('topic_id').value = question.topic_id;
                document.getElementById('question_text').value = question.question_text;
                document.getElementById('difficulty').value = question.difficulty;
                document.getElementById('option_a').value = question.option_a;
                document.getElementById('option_b').value = question.option_b;
                document.getElementById('option_c').value = question.option_c;
                document.getElementById('option_d').value = question.option_d;
                document.getElementById('correct_answer').value = question.correct_answer;
            }
        }
        
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        document.getElementById('questionModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Datos de preguntas para edición
    const questionsData = <?= json_encode($questions) ?>;
    
    // Cerrar modal al hacer clic fuera del contenido
    window.onclick = function(event) {
        const modal = document.getElementById('questionModal');
        if (event.target === modal) {
            closeModal();
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>