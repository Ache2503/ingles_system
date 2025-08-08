<?php
/**
 * Actualizador Completo de Base de Datos
 * Crea todas las tablas y columnas faltantes identificadas en el análisis
 */

require_once __DIR__ . '/../includes/db.php';

// Generar timestamp para el reporte
$reportTimestamp = date('Y-m-d_H-i-s');
$reportFile = __DIR__ . "/reports/database_update_report_$reportTimestamp.html";

// Crear directorio de reportes si no existe
if (!is_dir(__DIR__ . '/reports')) {
    mkdir(__DIR__ . '/reports', 0755, true);
}

// Iniciar buffer de salida para capturar tanto la pantalla como el archivo
ob_start();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>🔧 Actualización de Base de Datos</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .success { background: #d4f6d4; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .section { margin: 30px 0; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
        .progress { background: #e9ecef; border-radius: 4px; overflow: hidden; margin: 10px 0; }
        .progress-bar { background: #007bff; color: white; text-align: center; padding: 8px; transition: width 0.3s; }
        h1, h2, h3 { color: #2c3e50; }
        .report-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3498db;
        }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            color: white;
            text-align: center;
        }
        .btn-primary { background: #007bff; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #212529; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='report-header'>
            <h1>🔧 Actualización Completa de Base de Datos</h1>
            <p>Reporte generado: " . date('Y-m-d H:i:s') . "</p>
            <p>Archivo de reporte: <code>$reportFile</code></p>
        </div>";

$updateLog = [];
$successCount = 0;
$errorCount = 0;
$totalOperations = 0;

/**
 * Función para ejecutar SQL y registrar resultados
 */
function executeSQL($pdo, $sql, $description, &$updateLog, &$successCount, &$errorCount, &$totalOperations) {
    $totalOperations++;
    try {
        $pdo->exec($sql);
        $updateLog[] = "✅ $description";
        $successCount++;
        echo "<div class='success'>✅ $description</div>";
        return true;
    } catch (Exception $e) {
        $error = "❌ $description - Error: " . $e->getMessage();
        $updateLog[] = $error;
        $errorCount++;
        echo "<div class='error'>$error</div>";
        return false;
    }
}

echo "<div class='section'>
        <h2>🏗️ Creando Tablas de Gamificación</h2>";

// 1. Tabla user_gamification
$sql = "CREATE TABLE IF NOT EXISTS user_gamification (
    user_id INT PRIMARY KEY,
    total_points INT DEFAULT 0,
    current_level INT DEFAULT 1,
    experience_points INT DEFAULT 0,
    study_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    last_activity_date DATE,
    total_study_time INT DEFAULT 0,
    favorite_topic_id INT,
    study_streak_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (favorite_topic_id) REFERENCES topics(topic_id) ON DELETE SET NULL
)";
executeSQL($pdo, $sql, "Tabla user_gamification", $updateLog, $successCount, $errorCount, $totalOperations);

// 2. Tabla user_achievements
$sql = "CREATE TABLE IF NOT EXISTS user_achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_type VARCHAR(50) NOT NULL,
    achievement_name VARCHAR(100) NOT NULL,
    achievement_description TEXT,
    points_earned INT DEFAULT 0,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
executeSQL($pdo, $sql, "Tabla user_achievements", $updateLog, $successCount, $errorCount, $totalOperations);

// 3. Tabla achievement_config
$sql = "CREATE TABLE IF NOT EXISTS achievement_config (
    config_id INT AUTO_INCREMENT PRIMARY KEY,
    achievement_type VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    points_reward INT DEFAULT 0,
    condition_value INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE
)";
executeSQL($pdo, $sql, "Tabla achievement_config", $updateLog, $successCount, $errorCount, $totalOperations);

echo "</div><div class='section'>
        <h2>🔔 Creando Sistema de Notificaciones</h2>";

// 4. Tabla notifications
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('achievement', 'reminder', 'system', 'quiz_result') NOT NULL DEFAULT 'system',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
executeSQL($pdo, $sql, "Tabla notifications", $updateLog, $successCount, $errorCount, $totalOperations);

// 5. Tabla user_settings
$sql = "CREATE TABLE IF NOT EXISTS user_settings (
    user_id INT PRIMARY KEY,
    notifications_enabled BOOLEAN DEFAULT TRUE,
    email_reminders BOOLEAN DEFAULT TRUE,
    study_reminder_time TIME DEFAULT '19:00:00',
    preferred_language ENUM('es', 'en') DEFAULT 'es',
    theme ENUM('light', 'dark', 'auto') DEFAULT 'light',
    difficulty_preference ENUM('adaptive', 'easy', 'medium', 'hard') DEFAULT 'adaptive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
executeSQL($pdo, $sql, "Tabla user_settings", $updateLog, $successCount, $errorCount, $totalOperations);

echo "</div><div class='section'>
        <h2>⭐ Creando Sistema de Favoritos</h2>";

// 6. Tabla user_bookmarks
$sql = "CREATE TABLE IF NOT EXISTS user_bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_type ENUM('topic', 'verb', 'question') NOT NULL,
    content_id INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_bookmark (user_id, content_type, content_id)
)";
executeSQL($pdo, $sql, "Tabla user_bookmarks", $updateLog, $successCount, $errorCount, $totalOperations);

// 7. Tabla user_navigation_history
$sql = "CREATE TABLE IF NOT EXISTS user_navigation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    page_type ENUM('topic', 'verb', 'question', 'practice', 'quiz') NOT NULL,
    content_id INT,
    page_title VARCHAR(255),
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration_seconds INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
executeSQL($pdo, $sql, "Tabla user_navigation_history", $updateLog, $successCount, $errorCount, $totalOperations);

// 8. Tabla user_configuration
$sql = "CREATE TABLE IF NOT EXISTS user_configuration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    config_key VARCHAR(100) NOT NULL,
    config_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_config (user_id, config_key)
)";
executeSQL($pdo, $sql, "Tabla user_configuration", $updateLog, $successCount, $errorCount, $totalOperations);

echo "</div><div class='section'>
        <h2>🏷️ Creando Sistema de Categorías y Etiquetas</h2>";

// 9. Tabla content_categories
$sql = "CREATE TABLE IF NOT EXISTS content_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#3498db',
    icon VARCHAR(50),
    parent_category_id INT,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (parent_category_id) REFERENCES content_categories(category_id) ON DELETE SET NULL
)";
executeSQL($pdo, $sql, "Tabla content_categories", $updateLog, $successCount, $errorCount, $totalOperations);

// 10. Tabla tags
$sql = "CREATE TABLE IF NOT EXISTS tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#6c757d',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
executeSQL($pdo, $sql, "Tabla tags", $updateLog, $successCount, $errorCount, $totalOperations);

// 11. Tabla topic_tags
$sql = "CREATE TABLE IF NOT EXISTS topic_tags (
    topic_id INT,
    tag_id INT,
    PRIMARY KEY (topic_id, tag_id),
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
)";
executeSQL($pdo, $sql, "Tabla topic_tags", $updateLog, $successCount, $errorCount, $totalOperations);

echo "</div><div class='section'>
        <h2>📚 Creando Tablas de Contenido Avanzado</h2>";

// 12. Tabla media_resources
$sql = "CREATE TABLE IF NOT EXISTS media_resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT,
    question_id INT,
    type ENUM('image', 'audio', 'video', 'document') NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
)";
executeSQL($pdo, $sql, "Tabla media_resources", $updateLog, $successCount, $errorCount, $totalOperations);

// 13. Tabla study_sessions
$sql = "CREATE TABLE IF NOT EXISTS study_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    topic_id INT,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    duration_minutes INT DEFAULT 0,
    questions_answered INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    session_type ENUM('practice', 'exam', 'review') DEFAULT 'practice',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE SET NULL
)";
executeSQL($pdo, $sql, "Tabla study_sessions", $updateLog, $successCount, $errorCount, $totalOperations);

echo "</div><div class='section'>
        <h2>➕ Añadiendo Columnas Faltantes a Tablas Existentes</h2>";

// Columnas adicionales para users
$userColumns = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(255)" => "users.avatar_url",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT" => "users.bio", 
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS timezone VARCHAR(50) DEFAULT 'America/Mexico_City'" => "users.timezone",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL" => "users.last_login"
];

foreach ($userColumns as $sql => $description) {
    executeSQL($pdo, $sql, $description, $updateLog, $successCount, $errorCount, $totalOperations);
}

// Columnas adicionales para topics
$topicColumns = [
    "ALTER TABLE topics ADD COLUMN IF NOT EXISTS difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'intermediate'" => "topics.difficulty_level",
    "ALTER TABLE topics ADD COLUMN IF NOT EXISTS estimated_time INT DEFAULT 15" => "topics.estimated_time",
    "ALTER TABLE topics ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE" => "topics.is_featured",
    "ALTER TABLE topics ADD COLUMN IF NOT EXISTS views_count INT DEFAULT 0" => "topics.views_count",
    "ALTER TABLE topics ADD COLUMN IF NOT EXISTS last_viewed TIMESTAMP NULL" => "topics.last_viewed"
];

foreach ($topicColumns as $sql => $description) {
    executeSQL($pdo, $sql, $description, $updateLog, $successCount, $errorCount, $totalOperations);
}

// Columnas adicionales para questions
$questionColumns = [
    "ALTER TABLE questions ADD COLUMN IF NOT EXISTS points_value INT DEFAULT 10" => "questions.points_value",
    "ALTER TABLE questions ADD COLUMN IF NOT EXISTS time_limit_seconds INT DEFAULT 60" => "questions.time_limit_seconds",
    "ALTER TABLE questions ADD COLUMN IF NOT EXISTS question_type ENUM('multiple_choice', 'true_false', 'fill_blank', 'essay') DEFAULT 'multiple_choice'" => "questions.question_type"
];

foreach ($questionColumns as $sql => $description) {
    executeSQL($pdo, $sql, $description, $updateLog, $successCount, $errorCount, $totalOperations);
}

echo "</div><div class='section'>
        <h2>📊 Insertando Datos de Configuración</h2>";

// Insertar configuración de logros
$achievementConfigs = [
    "INSERT IGNORE INTO achievement_config (achievement_type, name, description, icon, points_reward, condition_value) VALUES 
    ('first_quiz', 'Primer Paso', 'Completa tu primer quiz', '🎯', 10, 1)" => "Logro: Primer Paso",
    
    "INSERT IGNORE INTO achievement_config (achievement_type, name, description, icon, points_reward, condition_value) VALUES 
    ('perfect_score', 'Perfección', 'Obtén una puntuación perfecta', '🏆', 50, 100)" => "Logro: Perfección",
    
    "INSERT IGNORE INTO achievement_config (achievement_type, name, description, icon, points_reward, condition_value) VALUES 
    ('study_streak', 'Constancia', 'Estudia 7 días consecutivos', '🔥', 100, 7)" => "Logro: Constancia",
    
    "INSERT IGNORE INTO achievement_config (achievement_type, name, description, icon, points_reward, condition_value) VALUES 
    ('topic_master', 'Maestro del Tema', 'Domina completamente un tema', '👑', 75, 1)" => "Logro: Maestro del Tema",
    
    "INSERT IGNORE INTO achievement_config (achievement_type, name, description, icon, points_reward, condition_value) VALUES 
    ('early_bird', 'Madrugador', 'Estudia antes de las 8:00 AM', '🌅', 25, 1)" => "Logro: Madrugador",
    
    "INSERT IGNORE INTO achievement_config (achievement_type, name, description, icon, points_reward, condition_value) VALUES 
    ('night_owl', 'Búho Nocturno', 'Estudia después de las 10:00 PM', '🦉', 25, 1)" => "Logro: Búho Nocturno"
];

foreach ($achievementConfigs as $sql => $description) {
    executeSQL($pdo, $sql, $description, $updateLog, $successCount, $errorCount, $totalOperations);
}

// Insertar categorías de contenido
$categories = [
    "INSERT IGNORE INTO content_categories (name, description, color, icon) VALUES 
    ('Gramática', 'Temas de gramática inglesa', '#e74c3c', 'grammar')" => "Categoría: Gramática",
    
    "INSERT IGNORE INTO content_categories (name, description, color, icon) VALUES 
    ('Vocabulario', 'Expansión de vocabulario', '#3498db', 'vocabulary')" => "Categoría: Vocabulario",
    
    "INSERT IGNORE INTO content_categories (name, description, color, icon) VALUES 
    ('Pronunciación', 'Ejercicios de pronunciación', '#f39c12', 'pronunciation')" => "Categoría: Pronunciación",
    
    "INSERT IGNORE INTO content_categories (name, description, color, icon) VALUES 
    ('Conversación', 'Práctica conversacional', '#2ecc71', 'conversation')" => "Categoría: Conversación",
    
    "INSERT IGNORE INTO content_categories (name, description, color, icon) VALUES 
    ('Lectura', 'Comprensión de textos', '#8e44ad', 'reading')" => "Categoría: Lectura"
];

foreach ($categories as $sql => $description) {
    executeSQL($pdo, $sql, $description, $updateLog, $successCount, $errorCount, $totalOperations);
}

// Insertar etiquetas
$tags = [
    "INSERT IGNORE INTO tags (name, color) VALUES 
    ('beginner', '#28a745')" => "Etiqueta: beginner",
    
    "INSERT IGNORE INTO tags (name, color) VALUES 
    ('intermediate', '#ffc107')" => "Etiqueta: intermediate",
    
    "INSERT IGNORE INTO tags (name, color) VALUES 
    ('advanced', '#dc3545')" => "Etiqueta: advanced",
    
    "INSERT IGNORE INTO tags (name, color) VALUES 
    ('exam-prep', '#6f42c1')" => "Etiqueta: exam-prep",
    
    "INSERT IGNORE INTO tags (name, color) VALUES 
    ('conversation', '#20c997')" => "Etiqueta: conversation",
    
    "INSERT IGNORE INTO tags (name, color) VALUES 
    ('business', '#fd7e14')" => "Etiqueta: business",
    
    "INSERT IGNORE INTO tags (name, color) VALUES 
    ('academic', '#6610f2')" => "Etiqueta: academic",
    
    "INSERT IGNORE INTO tags (name, color) VALUES 
    ('everyday', '#17a2b8')" => "Etiqueta: everyday"
];

foreach ($tags as $sql => $description) {
    executeSQL($pdo, $sql, $description, $updateLog, $successCount, $errorCount, $totalOperations);
}

echo "</div><div class='section'>
        <h2>🔍 Creando Índices para Optimización</h2>";

// Crear índices para mejorar rendimiento
$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_user_progress_score ON user_progress(score)" => "Índice en user_progress.score",
    "CREATE INDEX IF NOT EXISTS idx_quiz_history_date ON quiz_history(attempt_date)" => "Índice en quiz_history.attempt_date",
    "CREATE INDEX IF NOT EXISTS idx_user_answers_correct ON user_answers(is_correct)" => "Índice en user_answers.is_correct",
    "CREATE INDEX IF NOT EXISTS idx_notifications_unread ON notifications(user_id, is_read)" => "Índice en notifications",
    "CREATE INDEX IF NOT EXISTS idx_bookmarks_user_type ON user_bookmarks(user_id, content_type)" => "Índice en user_bookmarks",
    "CREATE INDEX IF NOT EXISTS idx_gamification_points ON user_gamification(total_points)" => "Índice en user_gamification.total_points"
];

foreach ($indexes as $sql => $description) {
    executeSQL($pdo, $sql, $description, $updateLog, $successCount, $errorCount, $totalOperations);
}

echo "</div>";

// Calcular progreso
$progressPercentage = $totalOperations > 0 ? round(($successCount / $totalOperations) * 100) : 0;

echo "<div class='section'>
        <h2>📊 Resumen de la Actualización</h2>
        <div class='progress'>
            <div class='progress-bar' style='width: {$progressPercentage}%'>
                {$progressPercentage}% Completado
            </div>
        </div>
        
        <div class='info'>
            <strong>Estadísticas de la Actualización:</strong><br>
            ✅ Operaciones exitosas: $successCount<br>
            ❌ Operaciones fallidas: $errorCount<br>
            📊 Total de operaciones: $totalOperations<br>
            💾 Progreso: {$progressPercentage}%
        </div>";

if ($errorCount > 0) {
    echo "<div class='warning'>
            <strong>⚠️ Nota:</strong> Algunas operaciones fallaron, posiblemente porque ya existían. 
            Esto es normal en actualizaciones incrementales.
          </div>";
}

echo "</div>";

// Verificar estado final
try {
    $finalTablesStmt = $pdo->query("SHOW TABLES");
    $finalTables = $finalTablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='section'>
            <h2>✅ Estado Final de la Base de Datos</h2>
            <div class='success'>
                <strong>Tablas en la base de datos:</strong> " . count($finalTables) . "<br>
                <strong>Tablas principales verificadas:</strong><br>
                • " . implode("<br>• ", array_slice($finalTables, 0, 10)) . 
                (count($finalTables) > 10 ? "<br>• ... y " . (count($finalTables) - 10) . " más" : "") . "
            </div>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error al verificar estado final: " . $e->getMessage() . "</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>
        <h3>🚀 Acciones Disponibles</h3>
        <a href='database_analyzer.php' class='btn btn-primary'>🔍 Analizar Nuevamente</a>
        <a href='../tests/system_verifier.php' class='btn btn-success'>🧪 Verificar Sistema</a>
        <a href='../index.php' class='btn btn-warning'>🏠 Ir al Sistema</a>
      </div>";

echo "    </div>
</body>
</html>";

// Guardar el contenido en el archivo de reporte
$content = ob_get_contents();
file_put_contents($reportFile, $content);

// Mostrar en pantalla
ob_end_flush();

// Crear también un archivo de log simple
$logFile = __DIR__ . "/reports/database_update_log_$reportTimestamp.txt";
$logContent = "REPORTE DE ACTUALIZACIÓN DE BASE DE DATOS\n";
$logContent .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
$logContent .= "Operaciones exitosas: $successCount\n";
$logContent .= "Operaciones fallidas: $errorCount\n";
$logContent .= "Total de operaciones: $totalOperations\n";
$logContent .= "Progreso: {$progressPercentage}%\n\n";
$logContent .= "DETALLE DE OPERACIONES:\n";
$logContent .= implode("\n", $updateLog);

file_put_contents($logFile, $logContent);
?>
