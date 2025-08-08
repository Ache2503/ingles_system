<?php
/**
 * pages/bookmarks.php - Solo usuarios autenticados
 */

// Protección de sesión obligatoria
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Validar sesión
validateSession();

// Log de actividad
logUserActivity('bookmarks', 'Usuario accedió a bookmarks.php');


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Manejar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'check_bookmark':
            $contentType = $_POST['content_type'];
            $contentId = (int)$_POST['content_id'];
            
            try {
                $checkStmt = $pdo->prepare("SELECT id FROM user_bookmarks WHERE user_id = ? AND content_type = ? AND content_id = ?");
                $checkStmt->execute([$userId, $contentType, $contentId]);
                $exists = $checkStmt->fetch();
                
                echo json_encode(['bookmarked' => (bool)$exists]);
            } catch (Exception $e) {
                echo json_encode(['bookmarked' => false]);
            }
            exit;
            
        case 'add_bookmark':
            $contentType = $_POST['content_type'];
            $contentId = (int)$_POST['content_id'];
            $notes = $_POST['notes'] ?? '';
            
            try {
                $checkStmt = $pdo->prepare("SELECT id FROM user_bookmarks WHERE user_id = ? AND content_type = ? AND content_id = ?");
                $checkStmt->execute([$userId, $contentType, $contentId]);
                
                if (!$checkStmt->fetch()) {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO user_bookmarks (user_id, content_type, content_id, notes, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $insertStmt->execute([$userId, $contentType, $contentId, $notes]);
                    echo json_encode(['success' => true, 'message' => 'Añadido a favoritos']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ya está en favoritos']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error al añadir']);
            }
            exit;
            
        case 'remove_bookmark':
            $bookmarkId = (int)$_POST['bookmark_id'];
            
            try {
                $deleteStmt = $pdo->prepare("DELETE FROM user_bookmarks WHERE id = ? AND user_id = ?");
                $deleteStmt->execute([$bookmarkId, $userId]);
                echo json_encode(['success' => true, 'message' => 'Eliminado de favoritos']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
            }
            exit;
            
        case 'update_notes':
            $bookmarkId = (int)$_POST['bookmark_id'];
            $notes = $_POST['notes'] ?? '';
            
            try {
                $updateStmt = $pdo->prepare("UPDATE user_bookmarks SET notes = ? WHERE id = ? AND user_id = ?");
                $updateStmt->execute([$notes, $bookmarkId, $userId]);
                echo json_encode(['success' => true, 'message' => 'Notas actualizadas']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
            }
            exit;
    }
}

// Obtener filtros
$filterType = $_GET['type'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'date_desc';

// Obtener favoritos
$bookmarkQuery = "
    SELECT 
        ub.*,
        CASE 
            WHEN ub.content_type = 'topic' THEN t.title
            WHEN ub.content_type = 'verb' THEN v.verb_form
            WHEN ub.content_type = 'question' THEN CONCAT('Pregunta: ', LEFT(q.question_text, 50), '...')
        END as title,
        CASE 
            WHEN ub.content_type = 'topic' THEN t.description
            WHEN ub.content_type = 'verb' THEN v.translation
            WHEN ub.content_type = 'question' THEN q.question_text
        END as description,
        CASE 
            WHEN ub.content_type = 'topic' THEN t.difficulty_level
            WHEN ub.content_type = 'question' THEN t2.difficulty_level
            ELSE NULL
        END as difficulty_level,
        CASE 
            WHEN ub.content_type = 'verb' THEN v.verb_form
            ELSE NULL
        END as verb_form,
        CASE 
            WHEN ub.content_type = 'verb' THEN v.translation
            ELSE NULL
        END as translation
    FROM user_bookmarks ub
    LEFT JOIN topics t ON ub.content_type = 'topic' AND ub.content_id = t.topic_id
    LEFT JOIN verbs v ON ub.content_type = 'verb' AND ub.content_id = v.verb_id
    LEFT JOIN questions q ON ub.content_type = 'question' AND ub.content_id = q.question_id
    LEFT JOIN topics t2 ON ub.content_type = 'question' AND q.topic_id = t2.topic_id
    WHERE ub.user_id = ?
";

$params = [$userId];

if ($filterType !== 'all') {
    $bookmarkQuery .= " AND ub.content_type = ?";
    $params[] = $filterType;
}

// Ordenamiento
switch ($sortBy) {
    case 'date_desc':
        $bookmarkQuery .= " ORDER BY ub.created_at DESC";
        break;
    case 'date_asc':
        $bookmarkQuery .= " ORDER BY ub.created_at ASC";
        break;
    case 'title_asc':
        $bookmarkQuery .= " ORDER BY title ASC";
        break;
    case 'title_desc':
        $bookmarkQuery .= " ORDER BY title DESC";
        break;
    case 'type':
        $bookmarkQuery .= " ORDER BY ub.content_type, title ASC";
        break;
}

$bookmarkStmt = $pdo->prepare($bookmarkQuery);
$bookmarkStmt->execute($params);
$bookmarks = $bookmarkStmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$statsStmt = $pdo->prepare("
    SELECT 
        content_type,
        COUNT(*) as count
    FROM user_bookmarks 
    WHERE user_id = ?
    GROUP BY content_type
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$totalBookmarks = array_sum($stats);
?>

<style>
    .bookmarks-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .bookmarks-header {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .header-content h1 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
    }
    
    .header-stats {
        text-align: right;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    
    .controls-section {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .filter-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .filter-label {
        font-weight: 500;
        color: #495057;
    }
    
    .filter-select {
        padding: 0.5rem 1rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background: white;
    }
    
    .stats-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .stat-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .bookmarks-grid {
        display: grid;
        gap: 1rem;
    }
    
    .bookmark-item {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        position: relative;
        border-left: 4px solid transparent;
    }
    
    .bookmark-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .bookmark-item.topic { border-left-color: #007bff; }
    .bookmark-item.verb { border-left-color: #28a745; }
    .bookmark-item.question { border-left-color: #ffc107; }
    
    .bookmark-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .bookmark-title {
        font-size: 1.2rem;
        font-weight: bold;
        color: #2c3e50;
        margin: 0 0 0.5rem 0;
        cursor: pointer;
    }
    
    .bookmark-title:hover {
        color: #007bff;
    }
    
    .bookmark-type {
        background: #e9ecef;
        color: #495057;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .bookmark-type.topic { background: #e3f2fd; color: #1976d2; }
    .bookmark-type.verb { background: #e8f5e8; color: #2e7d32; }
    .bookmark-type.question { background: #fff3e0; color: #f57c00; }
    
    .bookmark-description {
        color: #6c757d;
        margin-bottom: 1rem;
        line-height: 1.5;
    }
    
    .bookmark-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .bookmark-date {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .difficulty-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .difficulty-beginner { background: #d4edda; color: #155724; }
    .difficulty-intermediate { background: #fff3cd; color: #856404; }
    .difficulty-advanced { background: #f8d7da; color: #721c24; }
    
    .bookmark-notes {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .notes-textarea {
        width: 100%;
        border: none;
        background: transparent;
        resize: vertical;
        min-height: 60px;
        font-family: inherit;
    }
    
    .notes-textarea:focus {
        outline: none;
    }
    
    .bookmark-actions {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }
    
    .action-btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: #007bff;
        color: white;
    }
    
    .btn-primary:hover {
        background: #0056b3;
    }
    
    .btn-success {
        background: #28a745;
        color: white;
    }
    
    .btn-success:hover {
        background: #1e7e34;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger:hover {
        background: #c82333;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #545b62;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6c757d;
    }
    
    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }
    
    .bookmark-item.removing {
        opacity: 0.5;
        transform: scale(0.95);
    }
    
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification.success { background: #28a745; }
    .notification.error { background: #dc3545; }
    
    .verb-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    
    .verb-detail {
        background: #e3f2fd;
        padding: 0.75rem;
        border-radius: 8px;
        text-align: center;
    }
    
    .verb-label {
        font-weight: bold;
        color: #1976d2;
        margin-bottom: 0.25rem;
    }
    
    .verb-value {
        color: #2c3e50;
    }
</style>

<div class="bookmarks-container">
    <!-- Header -->
    <div class="bookmarks-header">
        <div class="header-content">
            <h1>⭐ Mis Favoritos</h1>
            <p>Contenido guardado para revisar más tarde</p>
        </div>
        <div class="header-stats">
            <div class="stat-number"><?= $totalBookmarks ?></div>
            <div>Total guardados</div>
        </div>
    </div>
    
    <!-- Estadísticas -->
    <div class="stats-overview">
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-value"><?= $stats['topic'] ?? 0 ?></div>
            <div class="stat-label">Temas</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔤</div>
            <div class="stat-value"><?= $stats['verb'] ?? 0 ?></div>
            <div class="stat-label">Verbos</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">❓</div>
            <div class="stat-value"><?= $stats['question'] ?? 0 ?></div>
            <div class="stat-label">Preguntas</div>
        </div>
    </div>
    
    <!-- Controles -->
    <div class="controls-section">
        <div class="filter-group">
            <label class="filter-label">Filtrar:</label>
            <select class="filter-select" onchange="updateFilters()" id="typeFilter">
                <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Todos</option>
                <option value="topic" <?= $filterType === 'topic' ? 'selected' : '' ?>>Temas</option>
                <option value="verb" <?= $filterType === 'verb' ? 'selected' : '' ?>>Verbos</option>
                <option value="question" <?= $filterType === 'question' ? 'selected' : '' ?>>Preguntas</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label class="filter-label">Ordenar:</label>
            <select class="filter-select" onchange="updateFilters()" id="sortFilter">
                <option value="date_desc" <?= $sortBy === 'date_desc' ? 'selected' : '' ?>>Más recientes</option>
                <option value="date_asc" <?= $sortBy === 'date_asc' ? 'selected' : '' ?>>Más antiguos</option>
                <option value="title_asc" <?= $sortBy === 'title_asc' ? 'selected' : '' ?>>A-Z</option>
                <option value="title_desc" <?= $sortBy === 'title_desc' ? 'selected' : '' ?>>Z-A</option>
                <option value="type" <?= $sortBy === 'type' ? 'selected' : '' ?>>Por tipo</option>
            </select>
        </div>
    </div>
    
    <!-- Favoritos -->
    <?php if (empty($bookmarks)): ?>
        <div class="empty-state">
            <div class="empty-icon">📝</div>
            <h3>No tienes favoritos guardados</h3>
            <p>Empieza a guardar contenido que te interese para encontrarlo fácilmente después</p>
            <a href="pages/topics.php" class="action-btn btn-primary" style="text-decoration: none; display: inline-block; margin-top: 1rem;">
                Explorar Contenido
            </a>
        </div>
    <?php else: ?>
        <div class="bookmarks-grid">
            <?php foreach ($bookmarks as $bookmark): ?>
                <div class="bookmark-item <?= $bookmark['content_type'] ?>" data-bookmark-id="<?= $bookmark['id'] ?>">
                    <div class="bookmark-header">
                        <div>
                            <h3 class="bookmark-title" onclick="openContent('<?= $bookmark['content_type'] ?>', <?= $bookmark['content_id'] ?>)">
                                <?= htmlspecialchars($bookmark['title']) ?>
                            </h3>
                            <span class="bookmark-type <?= $bookmark['content_type'] ?>">
                                <?php
                                switch($bookmark['content_type']) {
                                    case 'topic': echo '📚 Tema'; break;
                                    case 'verb': echo '🔤 Verbo'; break;
                                    case 'question': echo '❓ Pregunta'; break;
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="bookmark-description">
                        <?= htmlspecialchars($bookmark['description']) ?>
                    </div>
                    
                    <?php if ($bookmark['content_type'] === 'verb'): ?>
                        <div class="verb-details">
                            <div class="verb-detail">
                                <div class="verb-label">Inglés</div>
                                <div class="verb-value"><?= htmlspecialchars($bookmark['verb_form']) ?></div>
                            </div>
                            <div class="verb-detail">
                                <div class="verb-label">Español</div>
                                <div class="verb-value"><?= htmlspecialchars($bookmark['translation']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="bookmark-meta">
                        <div class="bookmark-date">
                            <span>📅</span>
                            <span><?= date('d/m/Y H:i', strtotime($bookmark['created_at'])) ?></span>
                        </div>
                        
                        <?php if ($bookmark['difficulty_level']): ?>
                            <span class="difficulty-badge difficulty-<?= $bookmark['difficulty_level'] ?>">
                                <?= ucfirst($bookmark['difficulty_level']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bookmark-notes">
                        <textarea class="notes-textarea" 
                                  placeholder="Añadir notas personales..." 
                                  data-bookmark-id="<?= $bookmark['id'] ?>"
                                  onblur="updateNotes(this)"><?= htmlspecialchars($bookmark['notes']) ?></textarea>
                    </div>
                    
                    <div class="bookmark-actions">
                        <button class="action-btn btn-primary" onclick="openContent('<?= $bookmark['content_type'] ?>', <?= $bookmark['content_id'] ?>)">
                            Abrir
                        </button>
                        <button class="action-btn btn-success" onclick="practiceContent('<?= $bookmark['content_type'] ?>', <?= $bookmark['content_id'] ?>)">
                            Practicar
                        </button>
                        <button class="action-btn btn-danger" onclick="removeBookmark(<?= $bookmark['id'] ?>)">
                            Eliminar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function updateFilters() {
    const type = document.getElementById('typeFilter').value;
    const sort = document.getElementById('sortFilter').value;
    
    const url = new URL(window.location);
    url.searchParams.set('type', type);
    url.searchParams.set('sort', sort);
    
    window.location = url;
}

function openContent(type, id) {
    switch(type) {
        case 'topic':
            window.location.href = `topic_detail.php?id=${id}`;
            break;
        case 'verb':
            window.location.href = `search.php?q=${encodeURIComponent('verb')}&type=verbs`;
            break;
        case 'question':
            window.location.href = `practice.php?question_id=${id}`;
            break;
    }
}

function practiceContent(type, id) {
    switch(type) {
        case 'topic':
            window.location.href = `practice.php?topic_id=${id}`;
            break;
        case 'verb':
            window.location.href = `practice.php?verb_id=${id}`;
            break;
        case 'question':
            window.location.href = `practice.php?question_id=${id}`;
            break;
    }
}

async function removeBookmark(bookmarkId) {
    if (!confirm('¿Estás seguro de que quieres eliminar este favorito?')) {
        return;
    }
    
    const bookmarkItem = document.querySelector(`[data-bookmark-id="${bookmarkId}"]`);
    bookmarkItem.classList.add('removing');
    
    try {
        const response = await fetch('bookmarks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=remove_bookmark&bookmark_id=${bookmarkId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            setTimeout(() => {
                bookmarkItem.remove();
                showNotification(result.message, 'success');
                updateStats();
            }, 300);
        } else {
            bookmarkItem.classList.remove('removing');
            showNotification(result.message, 'error');
        }
    } catch (error) {
        bookmarkItem.classList.remove('removing');
        showNotification('Error de conexión', 'error');
    }
}

async function updateNotes(textarea) {
    const bookmarkId = textarea.dataset.bookmarkId;
    const notes = textarea.value;
    
    try {
        const response = await fetch('bookmarks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_notes&bookmark_id=${bookmarkId}&notes=${encodeURIComponent(notes)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Notas guardadas', 'success');
        } else {
            showNotification('Error al guardar notas', 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function updateStats() {
    // Recalcular estadísticas después de eliminar
    const bookmarkItems = document.querySelectorAll('.bookmark-item');
    const stats = { topic: 0, verb: 0, question: 0 };
    
    bookmarkItems.forEach(item => {
        if (item.classList.contains('topic')) stats.topic++;
        if (item.classList.contains('verb')) stats.verb++;
        if (item.classList.contains('question')) stats.question++;
    });
    
    document.querySelector('.stat-card:nth-child(1) .stat-value').textContent = stats.topic;
    document.querySelector('.stat-card:nth-child(2) .stat-value').textContent = stats.verb;
    document.querySelector('.stat-card:nth-child(3) .stat-value').textContent = stats.question;
    
    const total = stats.topic + stats.verb + stats.question;
    document.querySelector('.stat-number').textContent = total;
    
    // Mostrar estado vacío si no hay favoritos
    if (total === 0) {
        document.querySelector('.bookmarks-grid').innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📝</div>
                <h3>No tienes favoritos guardados</h3>
                <p>Empieza a guardar contenido que te interese para encontrarlo fácilmente después</p>
                <a href="pages/topics.php" class="action-btn btn-primary" style="text-decoration: none; display: inline-block; margin-top: 1rem;">
                    Explorar Contenido
                </a>
            </div>
        `;
    }
}

// Función global para añadir favoritos (puede ser llamada desde otras páginas)
window.addBookmark = async function(contentType, contentId, notes = '') {
    try {
        const response = await fetch('bookmarks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add_bookmark&content_type=${contentType}&content_id=${contentId}&notes=${encodeURIComponent(notes)}`
        });
        
        const result = await response.json();
        showNotification(result.message, result.success ? 'success' : 'error');
        return result.success;
    } catch (error) {
        showNotification('Error de conexión', 'error');
        return false;
    }
};

// Animaciones de entrada
document.addEventListener('DOMContentLoaded', () => {
    const bookmarkItems = document.querySelectorAll('.bookmark-item');
    bookmarkItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Auto-guardar notas con debounce
let notesTimeout;
document.addEventListener('input', (e) => {
    if (e.target.classList.contains('notes-textarea')) {
        clearTimeout(notesTimeout);
        notesTimeout = setTimeout(() => {
            updateNotes(e.target);
        }, 1000);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
