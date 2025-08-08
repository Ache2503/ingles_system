<?php
/**
 * pages/search.php - Solo usuarios autenticados
 */

// Protección de sesión obligatoria
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Validar sesión
validateSession();

// Log de actividad
logUserActivity('search', 'Usuario accedió a search.php');


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$searchQuery = $_GET['q'] ?? '';
$searchType = $_GET['type'] ?? 'all';
$difficulty = $_GET['difficulty'] ?? '';
$category = $_GET['category'] ?? '';

$results = [];
$suggestions = [];

if ($searchQuery) {
    // Buscar en temas
    if ($searchType === 'all' || $searchType === 'topics') {
        $topicStmt = $pdo->prepare("
            SELECT 'topic' as type, topic_id as id, title, description, difficulty_level, 
                   NULL as verb_form, NULL as translation, NULL as question_text
            FROM topics 
            WHERE (title LIKE ? OR description LIKE ?)
            " . ($difficulty ? "AND difficulty_level = ?" : "") . "
            ORDER BY 
                CASE WHEN title LIKE ? THEN 1 ELSE 2 END,
                title
            LIMIT 20
        ");
        
        $params = ["%$searchQuery%", "%$searchQuery%"];
        if ($difficulty) $params[] = $difficulty;
        $params[] = "$searchQuery%";
        
        $topicStmt->execute($params);
        $topicResults = $topicStmt->fetchAll(PDO::FETCH_ASSOC);
        $results = array_merge($results, $topicResults);
    }
    
    // Buscar en verbos
    if ($searchType === 'all' || $searchType === 'verbs') {
        $verbStmt = $pdo->prepare("
            SELECT 'verb' as type, verb_id as id, verb_form as title, translation as description,
                   NULL as difficulty_level, verb_form, translation, NULL as question_text
            FROM verbs 
            WHERE verb_form LIKE ? OR translation LIKE ?
            ORDER BY 
                CASE WHEN verb_form LIKE ? THEN 1 ELSE 2 END,
                verb_form
            LIMIT 20
        ");
        $verbStmt->execute(["%$searchQuery%", "%$searchQuery%", "$searchQuery%"]);
        $verbResults = $verbStmt->fetchAll(PDO::FETCH_ASSOC);
        $results = array_merge($results, $verbResults);
    }
    
    // Buscar en preguntas
    if ($searchType === 'all' || $searchType === 'questions') {
        $questionStmt = $pdo->prepare("
            SELECT 'question' as type, q.question_id as id, 
                   CONCAT('Pregunta: ', LEFT(q.question_text, 50), '...') as title,
                   t.title as description, t.difficulty_level, 
                   NULL as verb_form, NULL as translation, q.question_text
            FROM questions q
            JOIN topics t ON q.topic_id = t.topic_id
            WHERE q.question_text LIKE ? OR q.option_a LIKE ? OR q.option_b LIKE ? OR q.option_c LIKE ? OR q.option_d LIKE ?
            " . ($difficulty ? "AND t.difficulty_level = ?" : "") . "
            ORDER BY t.title, q.question_id
            LIMIT 20
        ");
        
        $params = ["%$searchQuery%", "%$searchQuery%", "%$searchQuery%", "%$searchQuery%", "%$searchQuery%"];
        if ($difficulty) $params[] = $difficulty;
        
        $questionStmt->execute($params);
        $questionResults = $questionStmt->fetchAll(PDO::FETCH_ASSOC);
        $results = array_merge($results, $questionResults);
    }
    
    // Generar sugerencias
    if (empty($results)) {
        $suggestionStmt = $pdo->prepare("
            SELECT DISTINCT title as suggestion, 'topic' as type
            FROM topics 
            WHERE title LIKE ?
            UNION
            SELECT DISTINCT verb_form as suggestion, 'verb' as type
            FROM verbs 
            WHERE verb_form LIKE ?
            UNION
            SELECT DISTINCT translation as suggestion, 'verb' as type
            FROM verbs 
            WHERE translation LIKE ?
            LIMIT 10
        ");
        $suggestionStmt->execute(["%$searchQuery%", "%$searchQuery%", "%$searchQuery%"]);
        $suggestions = $suggestionStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Obtener filtros disponibles
$categoriesStmt = $pdo->query("SELECT DISTINCT category FROM topics WHERE category IS NOT NULL ORDER BY category");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

$difficultiesStmt = $pdo->query("SELECT DISTINCT difficulty_level FROM topics ORDER BY 
    CASE difficulty_level 
        WHEN 'beginner' THEN 1 
        WHEN 'intermediate' THEN 2 
        WHEN 'advanced' THEN 3 
        ELSE 4 
    END");
$difficulties = $difficultiesStmt->fetchAll(PDO::FETCH_COLUMN);

// Búsquedas populares
$popularSearchesStmt = $pdo->query("
    SELECT title, COUNT(*) as access_count
    FROM topics t
    LEFT JOIN user_progress up ON t.topic_id = up.topic_id
    GROUP BY t.topic_id, t.title
    ORDER BY access_count DESC
    LIMIT 6
");
$popularSearches = $popularSearchesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .search-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .search-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 3rem 2rem;
        border-radius: 15px;
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .search-header h1 {
        margin: 0 0 1rem 0;
        font-size: 2.5rem;
    }
    
    .search-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .search-input-group {
        display: flex;
        gap: 0.5rem;
    }
    
    .search-input {
        flex: 1;
        padding: 1rem;
        font-size: 1.1rem;
        border: none;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .search-btn {
        padding: 1rem 2rem;
        background: #ff6b6b;
        color: white;
        border: none;
        border-radius: 50px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .search-btn:hover {
        background: #ff5252;
        transform: translateY(-2px);
    }
    
    .search-filters {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .filter-select {
        padding: 0.5rem 1rem;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 25px;
        background: rgba(255,255,255,0.1);
        color: white;
        backdrop-filter: blur(10px);
    }
    
    .filter-select option {
        background: #333;
        color: white;
    }
    
    .search-stats {
        text-align: center;
        margin: 2rem 0;
        color: #6c757d;
    }
    
    .results-container {
        display: grid;
        gap: 1rem;
    }
    
    .result-item {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        cursor: pointer;
        border-left: 4px solid transparent;
    }
    
    .result-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .result-item.topic {
        border-left-color: #007bff;
    }
    
    .result-item.verb {
        border-left-color: #28a745;
    }
    
    .result-item.question {
        border-left-color: #ffc107;
    }
    
    .result-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .result-title {
        font-size: 1.2rem;
        font-weight: bold;
        color: #2c3e50;
        margin: 0;
    }
    
    .result-type {
        background: #e9ecef;
        color: #495057;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .result-type.topic { background: #e3f2fd; color: #1976d2; }
    .result-type.verb { background: #e8f5e8; color: #2e7d32; }
    .result-type.question { background: #fff3e0; color: #f57c00; }
    
    .result-description {
        color: #6c757d;
        margin-bottom: 1rem;
    }
    
    .result-meta {
        display: flex;
        gap: 1rem;
        font-size: 0.9rem;
        color: #6c757d;
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
    
    .suggestions-section {
        margin-top: 2rem;
        text-align: center;
    }
    
    .suggestions-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: center;
        margin-top: 1rem;
    }
    
    .suggestion-item {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        text-decoration: none;
        color: #495057;
        transition: all 0.3s ease;
    }
    
    .suggestion-item:hover {
        background: #007bff;
        color: white;
        text-decoration: none;
    }
    
    .popular-searches {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .popular-searches h3 {
        margin: 0 0 1rem 0;
        color: #2c3e50;
    }
    
    .popular-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.5rem;
    }
    
    .popular-item {
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 8px;
        text-decoration: none;
        color: #495057;
        transition: all 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .popular-item:hover {
        background: #e3f2fd;
        color: #1976d2;
        text-decoration: none;
    }
    
    .access-count {
        background: #007bff;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 15px;
        font-size: 0.8rem;
    }
    
    .no-results {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }
    
    .no-results-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }
    
    .search-tips {
        background: #e3f2fd;
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 2rem;
    }
    
    .search-tips h4 {
        color: #1976d2;
        margin: 0 0 1rem 0;
    }
    
    .tips-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .tips-list li {
        padding: 0.25rem 0;
        color: #495057;
    }
    
    .tips-list li::before {
        content: "💡 ";
        margin-right: 0.5rem;
    }
</style>

<div class="search-container">
    <!-- Header de búsqueda -->
    <div class="search-header">
        <h1>🔍 Búsqueda Inteligente</h1>
        <p>Encuentra temas, verbos, preguntas y más</p>
        
        <form class="search-form" method="GET">
            <div class="search-input-group">
                <input type="text" name="q" class="search-input" 
                       placeholder="¿Qué quieres aprender hoy?" 
                       value="<?= htmlspecialchars($searchQuery) ?>"
                       autocomplete="off">
                <button type="submit" class="search-btn">Buscar</button>
            </div>
            
            <div class="search-filters">
                <select name="type" class="filter-select">
                    <option value="all" <?= $searchType === 'all' ? 'selected' : '' ?>>Todo</option>
                    <option value="topics" <?= $searchType === 'topics' ? 'selected' : '' ?>>Temas</option>
                    <option value="verbs" <?= $searchType === 'verbs' ? 'selected' : '' ?>>Verbos</option>
                    <option value="questions" <?= $searchType === 'questions' ? 'selected' : '' ?>>Preguntas</option>
                </select>
                
                <select name="difficulty" class="filter-select">
                    <option value="">Cualquier nivel</option>
                    <?php foreach ($difficulties as $diff): ?>
                        <option value="<?= $diff ?>" <?= $difficulty === $diff ? 'selected' : '' ?>>
                            <?= ucfirst($diff) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <?php if (!empty($categories)): ?>
                <select name="category" class="filter-select">
                    <option value="">Cualquier categoría</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>" <?= $category === $cat ? 'selected' : '' ?>>
                            <?= ucfirst($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if (!$searchQuery): ?>
        <!-- Búsquedas populares -->
        <div class="popular-searches">
            <h3>🔥 Búsquedas Populares</h3>
            <div class="popular-list">
                <?php foreach ($popularSearches as $popular): ?>
                    <a href="?q=<?= urlencode($popular['title']) ?>" class="popular-item">
                        <span><?= htmlspecialchars($popular['title']) ?></span>
                        <span class="access-count"><?= $popular['access_count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Tips de búsqueda -->
        <div class="search-tips">
            <h4>Consejos de Búsqueda</h4>
            <ul class="tips-list">
                <li>Usa palabras clave específicas como "present simple" o "irregular verbs"</li>
                <li>Combina filtros para encontrar exactamente lo que necesitas</li>
                <li>Busca tanto en inglés como en español</li>
                <li>Usa comillas para buscar frases exactas</li>
            </ul>
        </div>
    <?php else: ?>
        <!-- Estadísticas de resultados -->
        <div class="search-stats">
            <?php if (!empty($results)): ?>
                <p>Se encontraron <strong><?= count($results) ?></strong> resultados para "<strong><?= htmlspecialchars($searchQuery) ?></strong>"</p>
            <?php else: ?>
                <p>No se encontraron resultados para "<strong><?= htmlspecialchars($searchQuery) ?></strong>"</p>
            <?php endif; ?>
        </div>
        
        <!-- Resultados -->
        <?php if (!empty($results)): ?>
            <div class="results-container">
                <?php foreach ($results as $result): ?>
                    <div class="result-item <?= $result['type'] ?>" 
                         onclick="handleResultClick('<?= $result['type'] ?>', <?= $result['id'] ?>)">
                        <div class="result-header">
                            <h3 class="result-title"><?= htmlspecialchars($result['title']) ?></h3>
                            <span class="result-type <?= $result['type'] ?>">
                                <?php
                                switch($result['type']) {
                                    case 'topic': echo '📚 Tema'; break;
                                    case 'verb': echo '🔤 Verbo'; break;
                                    case 'question': echo '❓ Pregunta'; break;
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div class="result-description">
                            <?= htmlspecialchars($result['description']) ?>
                        </div>
                        
                        <div class="result-meta">
                            <?php if ($result['difficulty_level']): ?>
                                <span class="difficulty-badge difficulty-<?= $result['difficulty_level'] ?>">
                                    <?= ucfirst($result['difficulty_level']) ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($result['type'] === 'verb'): ?>
                                <span>📝 <?= htmlspecialchars($result['verb_form']) ?></span>
                                <span>🌍 <?= htmlspecialchars($result['translation']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($suggestions)): ?>
            <!-- Sugerencias -->
            <div class="suggestions-section">
                <div class="no-results">
                    <div class="no-results-icon">🤔</div>
                    <h3>No encontramos resultados exactos</h3>
                    <p>Pero aquí tienes algunas sugerencias:</p>
                </div>
                
                <div class="suggestions-list">
                    <?php foreach ($suggestions as $suggestion): ?>
                        <a href="?q=<?= urlencode($suggestion['suggestion']) ?>" class="suggestion-item">
                            <?= htmlspecialchars($suggestion['suggestion']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Sin resultados -->
            <div class="no-results">
                <div class="no-results-icon">😔</div>
                <h3>No se encontraron resultados</h3>
                <p>Intenta con otros términos de búsqueda o usa los filtros</p>
            </div>
        <?php endif; ?>
        
        <!-- Tips de búsqueda cuando hay resultados -->
        <?php if (!empty($results)): ?>
            <div class="search-tips">
                <h4>Refina tu búsqueda</h4>
                <ul class="tips-list">
                    <li>Usa los filtros para encontrar contenido más específico</li>
                    <li>Prueba términos relacionados para descubrir más contenido</li>
                    <li>Combina diferentes tipos de contenido en tu búsqueda</li>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function handleResultClick(type, id) {
    switch(type) {
        case 'topic':
            window.location.href = `topic_detail.php?id=${id}`;
            break;
        case 'verb':
            // Buscar temas que contengan este verbo
            const verbElement = event.currentTarget;
            const verbForm = verbElement.querySelector('.result-meta span').textContent.replace('📝 ', '');
            window.location.href = `?q=${encodeURIComponent(verbForm)}&type=topics`;
            break;
        case 'question':
            // Ir a práctica del tema de esta pregunta
            window.location.href = `practice.php?question_id=${id}`;
            break;
    }
}

// Autocompletado en tiempo real
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.querySelector('.search-input');
    let timeout;
    
    searchInput.addEventListener('input', (e) => {
        clearTimeout(timeout);
        const query = e.target.value.trim();
        
        if (query.length >= 2) {
            timeout = setTimeout(() => {
                fetchSuggestions(query);
            }, 300);
        }
    });
    
    // Enfocar automáticamente el campo de búsqueda
    searchInput.focus();
    
    // Animaciones de entrada
    const resultItems = document.querySelectorAll('.result-item');
    resultItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

function fetchSuggestions(query) {
    // Implementar autocompletado AJAX si es necesario
    // fetch(`api/suggestions.php?q=${encodeURIComponent(query)}`)...
}

// Atajos de teclado
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + K para enfocar búsqueda
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.querySelector('.search-input').focus();
    }
    
    // Escape para limpiar búsqueda
    if (e.key === 'Escape') {
        const searchInput = document.querySelector('.search-input');
        if (searchInput === document.activeElement) {
            searchInput.blur();
        }
    }
});

// Highlight de términos de búsqueda
function highlightSearchTerm() {
    const searchTerm = '<?= addslashes($searchQuery) ?>';
    if (!searchTerm) return;
    
    const resultTitles = document.querySelectorAll('.result-title, .result-description');
    resultTitles.forEach(element => {
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        element.innerHTML = element.innerHTML.replace(regex, '<mark>$1</mark>');
    });
}

// Aplicar highlight después de cargar
if ('<?= $searchQuery ?>') {
    setTimeout(highlightSearchTerm, 100);
}
</script>

<style>
mark {
    background: #ffeb3b;
    padding: 0.1rem 0.2rem;
    border-radius: 3px;
    font-weight: bold;
}

@media (max-width: 768px) {
    .search-container {
        padding: 1rem;
    }
    
    .search-header {
        padding: 2rem 1rem;
    }
    
    .search-header h1 {
        font-size: 2rem;
    }
    
    .search-input-group {
        flex-direction: column;
    }
    
    .search-filters {
        flex-direction: column;
    }
    
    .popular-list {
        grid-template-columns: 1fr;
    }
    
    .result-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .result-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
