-- Mejoras a la base de datos para funcionalidades avanzadas

USE ingles_system;

-- Tabla para sistema de gamificación
CREATE TABLE IF NOT EXISTS user_achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_type ENUM('first_quiz', 'perfect_score', 'study_streak', 'topic_master', 'early_bird', 'night_owl') NOT NULL,
    achievement_name VARCHAR(100) NOT NULL,
    achievement_description TEXT,
    points_earned INT DEFAULT 0,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Tabla para puntos y niveles de usuario
CREATE TABLE IF NOT EXISTS user_gamification (
    user_id INT PRIMARY KEY,
    total_points INT DEFAULT 0,
    current_level INT DEFAULT 1,
    experience_points INT DEFAULT 0,
    study_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    last_activity_date DATE,
    total_study_time INT DEFAULT 0, -- en minutos
    favorite_topic_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (favorite_topic_id) REFERENCES topics(topic_id) ON DELETE SET NULL
);

-- Tabla para configuración de logros
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

-- Tabla para notificaciones
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('achievement', 'reminder', 'system', 'quiz_result') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Tabla para configuraciones de usuario
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

-- Tabla para categorías de contenido más flexible
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

-- Tabla para etiquetas
CREATE TABLE IF NOT EXISTS tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#6c757d',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para relacionar temas con etiquetas
CREATE TABLE IF NOT EXISTS topic_tags (
    topic_id INT,
    tag_id INT,
    PRIMARY KEY (topic_id, tag_id),
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
);

-- Tabla para recursos multimedia
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

-- Tabla para sesiones de estudio
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

-- Insertar configuración de logros por defecto
INSERT IGNORE INTO achievement_config (achievement_type, name, description, icon, points_reward, condition_value) VALUES
('first_quiz', 'Primer Paso', 'Completa tu primer quiz', '🎯', 10, 1),
('perfect_score', 'Perfección', 'Obtén una puntuación perfecta', '🏆', 50, 100),
('study_streak', 'Constancia', 'Estudia 7 días consecutivos', '🔥', 100, 7),
('topic_master', 'Maestro del Tema', 'Domina completamente un tema', '👑', 75, 1),
('early_bird', 'Madrugador', 'Estudia antes de las 8:00 AM', '🌅', 25, 1),
('night_owl', 'Búho Nocturno', 'Estudia después de las 10:00 PM', '🦉', 25, 1);

-- Insertar categorías de contenido por defecto
INSERT IGNORE INTO content_categories (name, description, color, icon) VALUES
('Gramática Básica', 'Fundamentos gramaticales del inglés', '#e74c3c', 'grammar'),
('Gramática Avanzada', 'Estructuras gramaticales complejas', '#c0392b', 'advanced'),
('Vocabulario General', 'Palabras y expresiones de uso común', '#3498db', 'vocabulary'),
('Vocabulario Especializado', 'Terminología específica por área', '#2980b9', 'specialist'),
('Pronunciación', 'Ejercicios de fonética y pronunciación', '#f39c12', 'pronunciation'),
('Comprensión Auditiva', 'Ejercicios de listening', '#e67e22', 'listening'),
('Escritura', 'Redacción y composición en inglés', '#9b59b6', 'writing'),
('Lectura', 'Comprensión de textos', '#8e44ad', 'reading');

-- Insertar etiquetas por defecto
INSERT IGNORE INTO tags (name, color) VALUES
('beginner', '#28a745'),
('intermediate', '#ffc107'),
('advanced', '#dc3545'),
('exam-prep', '#6f42c1'),
('conversation', '#20c997'),
('business', '#fd7e14'),
('academic', '#6610f2'),
('everyday', '#17a2b8');

-- Agregar columnas adicionales a tablas existentes
ALTER TABLE topics 
ADD COLUMN IF NOT EXISTS estimated_time INT DEFAULT 15 COMMENT 'Tiempo estimado en minutos',
ADD COLUMN IF NOT EXISTS difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'intermediate',
ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0;

ALTER TABLE questions 
ADD COLUMN IF NOT EXISTS points_value INT DEFAULT 10,
ADD COLUMN IF NOT EXISTS time_limit_seconds INT DEFAULT 60,
ADD COLUMN IF NOT EXISTS question_type ENUM('multiple_choice', 'true_false', 'fill_blank', 'essay') DEFAULT 'multiple_choice';

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(255),
ADD COLUMN IF NOT EXISTS bio TEXT,
ADD COLUMN IF NOT EXISTS timezone VARCHAR(50) DEFAULT 'America/Mexico_City',
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL;

-- Índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_user_progress_score ON user_progress(score);
CREATE INDEX IF NOT EXISTS idx_quiz_history_date ON quiz_history(attempt_date);
CREATE INDEX IF NOT EXISTS idx_user_answers_correct ON user_answers(is_correct);
CREATE INDEX IF NOT EXISTS idx_notifications_unread ON notifications(user_id, is_read);

-- Triggers para gamificación automática
DELIMITER //

CREATE TRIGGER IF NOT EXISTS update_user_gamification_after_quiz
AFTER INSERT ON quiz_history
FOR EACH ROW
BEGIN
    DECLARE points_earned INT DEFAULT 0;
    DECLARE current_streak INT DEFAULT 0;
    
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

-- Vista para el ranking de usuarios
CREATE OR REPLACE VIEW user_ranking AS
SELECT 
    u.user_id,
    u.username,
    u.avatar_url,
    ug.total_points,
    ug.current_level,
    ug.study_streak,
    ug.longest_streak,
    COUNT(DISTINCT up.topic_id) as topics_mastered,
    AVG(up.score) as avg_score,
    RANK() OVER (ORDER BY ug.total_points DESC) as ranking
FROM users u
LEFT JOIN user_gamification ug ON u.user_id = ug.user_id
LEFT JOIN user_progress up ON u.user_id = up.user_id AND up.mastery_level = 'mastered'
WHERE u.role = 'student'
GROUP BY u.user_id
ORDER BY ug.total_points DESC;

-- Vista para estadísticas de temas
CREATE OR REPLACE VIEW topic_statistics AS
SELECT 
    t.topic_id,
    t.title,
    t.category,
    t.difficulty_level,
    t.view_count,
    COUNT(DISTINCT q.question_id) as total_questions,
    COUNT(DISTINCT qh.user_id) as users_attempted,
    AVG(qh.score) as avg_score,
    COUNT(qh.history_id) as total_attempts
FROM topics t
LEFT JOIN questions q ON t.topic_id = q.topic_id
LEFT JOIN quiz_history qh ON t.topic_id = qh.topic_id
GROUP BY t.topic_id;

COMMIT;
