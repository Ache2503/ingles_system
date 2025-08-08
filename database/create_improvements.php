<?php
require_once __DIR__ . '/../includes/db.php';

echo "🚀 Creando nuevas tablas para las mejoras...\n";

try {
    // Tabla para logros de usuario
    echo "Creando tabla user_achievements...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_achievements (
        achievement_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        achievement_type VARCHAR(50) NOT NULL,
        achievement_name VARCHAR(100) NOT NULL,
        achievement_description TEXT,
        points_earned INT DEFAULT 0,
        earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    
    // Tabla para gamificación
    echo "Creando tabla user_gamification...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_gamification (
        user_id INT PRIMARY KEY,
        total_points INT DEFAULT 0,
        current_level INT DEFAULT 1,
        experience_points INT DEFAULT 0,
        study_streak INT DEFAULT 0,
        longest_streak INT DEFAULT 0,
        last_activity_date DATE,
        total_study_time INT DEFAULT 0,
        favorite_topic_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (favorite_topic_id) REFERENCES topics(topic_id) ON DELETE SET NULL
    )");
    
    // Tabla para configuración de logros
    echo "Creando tabla achievement_config...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS achievement_config (
        config_id INT AUTO_INCREMENT PRIMARY KEY,
        achievement_type VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        icon VARCHAR(50),
        points_reward INT DEFAULT 0,
        condition_value INT DEFAULT 1,
        is_active BOOLEAN DEFAULT TRUE
    )");
    
    // Tabla para notificaciones
    echo "Creando tabla notifications...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('achievement', 'reminder', 'system', 'quiz_result') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    
    // Tabla para configuraciones de usuario
    echo "Creando tabla user_settings...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_settings (
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
    )");
    
    // Tabla para categorías de contenido
    echo "Creando tabla content_categories...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        color VARCHAR(7) DEFAULT '#3498db',
        icon VARCHAR(50),
        parent_category_id INT,
        sort_order INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (parent_category_id) REFERENCES content_categories(category_id) ON DELETE SET NULL
    )");
    
    // Tabla para etiquetas
    echo "Creando tabla tags...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS tags (
        tag_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        color VARCHAR(7) DEFAULT '#6c757d',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Tabla para relación tema-etiquetas
    echo "Creando tabla topic_tags...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS topic_tags (
        topic_id INT,
        tag_id INT,
        PRIMARY KEY (topic_id, tag_id),
        FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
    )");
    
    // Tabla para recursos multimedia
    echo "Creando tabla media_resources...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS media_resources (
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
    )");
    
    // Tabla para sesiones de estudio
    echo "Creando tabla study_sessions...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS study_sessions (
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
    )");
    
    echo "\n📊 Insertando datos de configuración...\n";
    
    // Insertar configuración de logros
    echo "Configurando logros...\n";
    $pdo->exec("INSERT IGNORE INTO achievement_config (achievement_type, name, description, icon, points_reward, condition_value) VALUES
        ('first_quiz', 'Primer Paso', 'Completa tu primer quiz', '🎯', 10, 1),
        ('perfect_score', 'Perfección', 'Obtén una puntuación perfecta', '🏆', 50, 100),
        ('study_streak', 'Constancia', 'Estudia 7 días consecutivos', '🔥', 100, 7),
        ('topic_master', 'Maestro del Tema', 'Domina completamente un tema', '👑', 75, 1),
        ('early_bird', 'Madrugador', 'Estudia antes de las 8:00 AM', '🌅', 25, 1),
        ('night_owl', 'Búho Nocturno', 'Estudia después de las 10:00 PM', '🦉', 25, 1)
    ");
    
    // Insertar categorías de contenido
    echo "Configurando categorías...\n";
    $pdo->exec("INSERT IGNORE INTO content_categories (name, description, color, icon) VALUES
        ('Gramática Básica', 'Fundamentos gramaticales del inglés', '#e74c3c', 'grammar'),
        ('Gramática Avanzada', 'Estructuras gramaticales complejas', '#c0392b', 'advanced'),
        ('Vocabulario General', 'Palabras y expresiones de uso común', '#3498db', 'vocabulary'),
        ('Vocabulario Especializado', 'Terminología específica por área', '#2980b9', 'specialist'),
        ('Pronunciación', 'Ejercicios de fonética y pronunciación', '#f39c12', 'pronunciation'),
        ('Comprensión Auditiva', 'Ejercicios de listening', '#e67e22', 'listening'),
        ('Escritura', 'Redacción y composición en inglés', '#9b59b6', 'writing'),
        ('Lectura', 'Comprensión de textos', '#8e44ad', 'reading')
    ");
    
    // Insertar etiquetas
    echo "Configurando etiquetas...\n";
    $pdo->exec("INSERT IGNORE INTO tags (name, color) VALUES
        ('beginner', '#28a745'),
        ('intermediate', '#ffc107'),
        ('advanced', '#dc3545'),
        ('exam-prep', '#6f42c1'),
        ('conversation', '#20c997'),
        ('business', '#fd7e14'),
        ('academic', '#6610f2'),
        ('everyday', '#17a2b8')
    ");
    
    // Agregar columnas a tablas existentes
    echo "Agregando columnas adicionales...\n";
    
    try {
        $pdo->exec("ALTER TABLE topics ADD COLUMN estimated_time INT DEFAULT 15");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE topics ADD COLUMN difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'intermediate'");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE topics ADD COLUMN is_featured BOOLEAN DEFAULT FALSE");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE topics ADD COLUMN view_count INT DEFAULT 0");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN points_value INT DEFAULT 10");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN time_limit_seconds INT DEFAULT 60");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN question_type ENUM('multiple_choice', 'true_false', 'fill_blank', 'essay') DEFAULT 'multiple_choice'");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255)");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN timezone VARCHAR(50) DEFAULT 'America/Mexico_City'");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL");
    } catch (Exception $e) {}
    
    // Crear vistas
    echo "Creando vistas...\n";
    $pdo->exec("CREATE OR REPLACE VIEW user_ranking AS
        SELECT 
            u.user_id,
            u.username,
            u.avatar_url,
            COALESCE(ug.total_points, 0) as total_points,
            COALESCE(ug.current_level, 1) as current_level,
            COALESCE(ug.study_streak, 0) as study_streak,
            COALESCE(ug.longest_streak, 0) as longest_streak,
            COUNT(DISTINCT CASE WHEN up.mastery_level = 'mastered' THEN up.topic_id END) as topics_mastered,
            COALESCE(AVG(up.score), 0) as avg_score,
            RANK() OVER (ORDER BY COALESCE(ug.total_points, 0) DESC) as ranking
        FROM users u
        LEFT JOIN user_gamification ug ON u.user_id = ug.user_id
        LEFT JOIN user_progress up ON u.user_id = up.user_id
        WHERE u.role = 'student'
        GROUP BY u.user_id
        ORDER BY COALESCE(ug.total_points, 0) DESC
    ");
    
    $pdo->exec("CREATE OR REPLACE VIEW topic_statistics AS
        SELECT 
            t.topic_id,
            t.title,
            t.category,
            t.difficulty_level,
            COALESCE(t.view_count, 0) as view_count,
            COUNT(DISTINCT q.question_id) as total_questions,
            COUNT(DISTINCT qh.user_id) as users_attempted,
            COALESCE(AVG(qh.score), 0) as avg_score,
            COUNT(qh.history_id) as total_attempts
        FROM topics t
        LEFT JOIN questions q ON t.topic_id = q.topic_id
        LEFT JOIN quiz_history qh ON t.topic_id = qh.topic_id
        GROUP BY t.topic_id
    ");
    
    echo "\n✅ ¡Todas las mejoras aplicadas exitosamente!\n";
    
    // Verificar tablas creadas
    $tables = [
        'user_achievements', 'user_gamification', 'achievement_config', 
        'notifications', 'user_settings', 'content_categories', 
        'tags', 'topic_tags', 'media_resources', 'study_sessions'
    ];
    
    echo "\n📋 Verificando tablas creadas:\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        echo $stmt->rowCount() > 0 ? "✅ $table\n" : "❌ $table\n";
    }
    
    // Mostrar estadísticas
    echo "\n📊 Estadísticas:\n";
    echo "- Configuración de logros: " . $pdo->query("SELECT COUNT(*) FROM achievement_config")->fetchColumn() . " registros\n";
    echo "- Categorías: " . $pdo->query("SELECT COUNT(*) FROM content_categories")->fetchColumn() . " registros\n";
    echo "- Etiquetas: " . $pdo->query("SELECT COUNT(*) FROM tags")->fetchColumn() . " registros\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
