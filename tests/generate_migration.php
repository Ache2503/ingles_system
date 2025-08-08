<?php
/**
 * Generador de Migraciones SQL
 * Crea scripts SQL para aplicar cambios a la base de datos
 */

// Generar timestamp para la migración
$migrationTimestamp = date('Y-m-d_H-i-s');
$migrationFile = __DIR__ . "/migrations/migration_$migrationTimestamp.sql";

// Crear directorio de migraciones si no existe
if (!is_dir(__DIR__ . '/migrations')) {
    mkdir(__DIR__ . '/migrations', 0755, true);
}

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>📝 Generador de Migraciones</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .success { background: #d4f6d4; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 10px 0; }
        h1, h2, h3 { color: #2c3e50; }
        .report-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3498db;
        }
        .sql-code {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
            margin: 15px 0;
        }
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
            <h1>📝 Generador de Migraciones SQL</h1>
            <p>Creando script de migración para actualización de base de datos</p>
            <p>Timestamp: $migrationTimestamp</p>
        </div>";

// Generar el contenido de la migración
$migrationContent = "-- MIGRACIÓN DE BASE DE DATOS - SISTEMA DE INGLÉS
-- Generado: " . date('Y-m-d H:i:s') . "
-- Archivo: migration_$migrationTimestamp.sql
-- Descripción: Actualización completa del sistema con nuevas funcionalidades

USE ingles_system;

-- ================================================
-- 1. SISTEMA DE GAMIFICACIÓN
-- ================================================

-- Tabla de gamificación de usuarios
CREATE TABLE IF NOT EXISTS user_gamification (
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
);

-- Tabla de logros de usuarios
CREATE TABLE IF NOT EXISTS user_achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_type VARCHAR(50) NOT NULL,
    achievement_name VARCHAR(100) NOT NULL,
    achievement_description TEXT,
    points_earned INT DEFAULT 0,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Configuración de logros
CREATE TABLE IF NOT EXISTS achievement_config (
    config_id INT AUTO_INCREMENT PRIMARY KEY,
    achievement_type VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    points_reward INT DEFAULT 0,
    condition_value INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE
);

-- ================================================
-- 2. SISTEMA DE NOTIFICACIONES
-- ================================================

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('achievement', 'reminder', 'system', 'quiz_result') NOT NULL DEFAULT 'system',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ================================================
-- 3. CONFIGURACIÓN DE USUARIOS
-- ================================================

CREATE TABLE IF NOT EXISTS user_settings (
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
);

-- ================================================
-- 4. SISTEMA DE FAVORITOS Y NAVEGACIÓN
-- ================================================

CREATE TABLE IF NOT EXISTS user_bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_type ENUM('topic', 'verb', 'question') NOT NULL,
    content_id INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_bookmark (user_id, content_type, content_id)
);

CREATE TABLE IF NOT EXISTS user_navigation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    page_type ENUM('topic', 'verb', 'question', 'practice', 'quiz') NOT NULL,
    content_id INT,
    page_title VARCHAR(255),
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration_seconds INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_configuration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    config_key VARCHAR(100) NOT NULL,
    config_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_config (user_id, config_key)
);

-- ================================================
-- 5. SISTEMA DE CATEGORÍAS Y ETIQUETAS
-- ================================================

CREATE TABLE IF NOT EXISTS content_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#3498db',
    icon VARCHAR(50),
    parent_category_id INT,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (parent_category_id) REFERENCES content_categories(category_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#6c757d',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS topic_tags (
    topic_id INT,
    tag_id INT,
    PRIMARY KEY (topic_id, tag_id),
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
);

-- ================================================
-- 6. RECURSOS MULTIMEDIA Y SESIONES
-- ================================================

CREATE TABLE IF NOT EXISTS media_resources (
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
);

CREATE TABLE IF NOT EXISTS study_sessions (
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
);

-- ================================================
-- 7. MEJORAS A TABLAS EXISTENTES
-- ================================================

-- Mejoras en tabla users
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(255),
ADD COLUMN IF NOT EXISTS bio TEXT,
ADD COLUMN IF NOT EXISTS timezone VARCHAR(50) DEFAULT 'America/Mexico_City',
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL;

-- Mejoras en tabla topics
ALTER TABLE topics 
ADD COLUMN IF NOT EXISTS difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'intermediate',
ADD COLUMN IF NOT EXISTS estimated_time INT DEFAULT 15,
ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS views_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_viewed TIMESTAMP NULL;

-- Mejoras en tabla questions
ALTER TABLE questions 
ADD COLUMN IF NOT EXISTS points_value INT DEFAULT 10,
ADD COLUMN IF NOT EXISTS time_limit_seconds INT DEFAULT 60,
ADD COLUMN IF NOT EXISTS question_type ENUM('multiple_choice', 'true_false', 'fill_blank', 'essay') DEFAULT 'multiple_choice';

-- ================================================
-- 8. ÍNDICES PARA OPTIMIZACIÓN
-- ================================================

CREATE INDEX IF NOT EXISTS idx_user_progress_score ON user_progress(score);
CREATE INDEX IF NOT EXISTS idx_quiz_history_date ON quiz_history(attempt_date);
CREATE INDEX IF NOT EXISTS idx_user_answers_correct ON user_answers(is_correct);
CREATE INDEX IF NOT EXISTS idx_notifications_unread ON notifications(user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_bookmarks_user_type ON user_bookmarks(user_id, content_type);
CREATE INDEX IF NOT EXISTS idx_gamification_points ON user_gamification(total_points);

-- ================================================
-- 9. DATOS DE CONFIGURACIÓN INICIAL
-- ================================================

-- Configuración de logros
INSERT IGNORE INTO achievement_config (achievement_type, name, description, icon, points_reward, condition_value) VALUES
('first_quiz', 'Primer Paso', 'Completa tu primer quiz', '🎯', 10, 1),
('perfect_score', 'Perfección', 'Obtén una puntuación perfecta', '🏆', 50, 100),
('study_streak', 'Constancia', 'Estudia 7 días consecutivos', '🔥', 100, 7),
('topic_master', 'Maestro del Tema', 'Domina completamente un tema', '👑', 75, 1),
('early_bird', 'Madrugador', 'Estudia antes de las 8:00 AM', '🌅', 25, 1),
('night_owl', 'Búho Nocturno', 'Estudia después de las 10:00 PM', '🦉', 25, 1);

-- Categorías de contenido
INSERT IGNORE INTO content_categories (name, description, color, icon) VALUES
('Gramática', 'Temas de gramática inglesa', '#e74c3c', 'grammar'),
('Vocabulario', 'Expansión de vocabulario', '#3498db', 'vocabulary'),
('Pronunciación', 'Ejercicios de pronunciación', '#f39c12', 'pronunciation'),
('Conversación', 'Práctica conversacional', '#2ecc71', 'conversation'),
('Lectura', 'Comprensión de textos', '#8e44ad', 'reading');

-- Etiquetas del sistema
INSERT IGNORE INTO tags (name, color) VALUES
('beginner', '#28a745'),
('intermediate', '#ffc107'),
('advanced', '#dc3545'),
('exam-prep', '#6f42c1'),
('conversation', '#20c997'),
('business', '#fd7e14'),
('academic', '#6610f2'),
('everyday', '#17a2b8');

-- ================================================
-- 10. TRIGGERS PARA GAMIFICACIÓN (OPCIONAL)
-- ================================================

DELIMITER //

-- Trigger para actualizar gamificación después de quiz
DROP TRIGGER IF EXISTS update_user_gamification_after_quiz//
CREATE TRIGGER update_user_gamification_after_quiz
AFTER INSERT ON quiz_history
FOR EACH ROW
BEGIN
    DECLARE points_earned INT DEFAULT 0;
    
    -- Calcular puntos basados en la puntuación
    SET points_earned = ROUND(NEW.score / 10);
    
    -- Insertar o actualizar gamificación del usuario
    INSERT INTO user_gamification (user_id, total_points, experience_points, last_activity_date)
    VALUES (NEW.user_id, points_earned, points_earned, CURDATE())
    ON DUPLICATE KEY UPDATE
        total_points = total_points + points_earned,
        experience_points = experience_points + points_earned,
        last_activity_date = CURDATE(),
        current_level = FLOOR(experience_points / 100) + 1;
    
    -- Verificar logro de puntuación perfecta
    IF NEW.score = 100 THEN
        INSERT IGNORE INTO user_achievements (user_id, achievement_type, achievement_name, achievement_description, points_earned)
        VALUES (NEW.user_id, 'perfect_score', 'Perfección', 'Obtuviste una puntuación perfecta', 50);
    END IF;
END//

DELIMITER ;

-- ================================================
-- FIN DE LA MIGRACIÓN
-- ================================================

-- Mensaje de confirmación
SELECT 'Migración completada exitosamente' as status;
";

// Guardar el archivo de migración
if (file_put_contents($migrationFile, $migrationContent)) {
    echo "<div class='success'>
            <h2>✅ Migración Generada Exitosamente</h2>
            <p><strong>Archivo:</strong> " . basename($migrationFile) . "</p>
            <p><strong>Tamaño:</strong> " . round(strlen($migrationContent) / 1024, 2) . " KB</p>
            <p><strong>Ubicación:</strong> <code>$migrationFile</code></p>
          </div>";
    
    echo "<div class='info'>
            <h3>📋 Contenido de la Migración</h3>
            <p>Esta migración incluye:</p>
            <ul>
                <li>🎮 Sistema completo de gamificación</li>
                <li>🔔 Sistema de notificaciones</li>
                <li>⚙️ Configuración avanzada de usuarios</li>
                <li>⭐ Sistema de favoritos y navegación</li>
                <li>🏷️ Categorías y etiquetas</li>
                <li>📚 Recursos multimedia</li>
                <li>📊 Sesiones de estudio</li>
                <li>🔧 Mejoras a tablas existentes</li>
                <li>⚡ Índices de optimización</li>
                <li>🎯 Triggers de gamificación</li>
            </ul>
          </div>";
    
    // Mostrar preview del contenido
    echo "<div class='info'>
            <h3>👀 Preview del Script SQL</h3>
            <div class='sql-code'>" . htmlspecialchars(substr($migrationContent, 0, 2000)) . "
            
            ... (archivo completo de " . strlen($migrationContent) . " caracteres)
            </div>
          </div>";
    
    // Instrucciones de uso
    echo "<div class='info'>
            <h3>🚀 Instrucciones de Uso</h3>
            <ol>
                <li><strong>Backup primero:</strong> Ejecuta <code>backup_database.php</code></li>
                <li><strong>Aplicar migración:</strong> 
                    <br><code>mysql -u root -p ingles_system < " . basename($migrationFile) . "</code>
                </li>
                <li><strong>Verificar:</strong> Ejecuta <code>database_analyzer.php</code></li>
                <li><strong>Probar sistema:</strong> Verifica que todo funcione correctamente</li>
            </ol>
            <p><strong>⚠️ Importante:</strong> Siempre haz un backup antes de aplicar la migración.</p>
          </div>";
    
} else {
    echo "<div class='error'>❌ Error al generar el archivo de migración</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>
        <h3>🔧 Acciones Disponibles</h3>
        <a href='backup_database.php' class='btn btn-warning'>💾 Hacer Backup</a>
        <a href='database_updater.php' class='btn btn-primary'>🚀 Aplicar Cambios</a>
        <a href='database_analyzer.php' class='btn btn-success'>🔍 Analizar BD</a>
      </div>";

echo "    </div>
</body>
</html>";
?>
