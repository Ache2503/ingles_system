<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Crear tabla de favoritos/bookmarks
    $createBookmarksTable = "
    CREATE TABLE IF NOT EXISTS user_bookmarks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content_type ENUM('topic', 'verb', 'question') NOT NULL,
        content_id INT NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_bookmark (user_id, content_type, content_id),
        INDEX idx_user_content (user_id, content_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createBookmarksTable);
    echo "✅ Tabla user_bookmarks creada correctamente\n";
    
    // Añadir índice al header de navigation
    $updateHeaderQuery = "
    ALTER TABLE topics 
    ADD COLUMN IF NOT EXISTS views_count INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS last_viewed TIMESTAMP NULL;
    ";
    
    try {
        $pdo->exec($updateHeaderQuery);
        echo "✅ Columnas de vistas añadidas a topics\n";
    } catch (Exception $e) {
        echo "ℹ️ Las columnas de vistas ya existen en topics\n";
    }
    
    // Crear tabla de historial de navegación
    $createNavigationHistory = "
    CREATE TABLE IF NOT EXISTS user_navigation_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        page_type ENUM('topic', 'verb', 'question', 'practice', 'quiz') NOT NULL,
        content_id INT,
        page_title VARCHAR(255),
        visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        duration_seconds INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_user_time (user_id, visit_time),
        INDEX idx_page_type (page_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createNavigationHistory);
    echo "✅ Tabla user_navigation_history creada correctamente\n";
    
    // Crear tabla de configuración de usuario
    $createUserConfig = "
    CREATE TABLE IF NOT EXISTS user_configuration (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        config_key VARCHAR(100) NOT NULL,
        config_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_config (user_id, config_key),
        INDEX idx_config_key (config_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createUserConfig);
    echo "✅ Tabla user_configuration creada correctamente\n";
    
    // Insertar datos de ejemplo en bookmarks
    $sampleBookmarks = [
        [1, 'topic', 1, 'Este tema me parece muy útil para repasar'],
        [1, 'verb', 1, 'Verbo irregular importante'],
        [2, 'topic', 2, 'Quiero practicar más este tema'],
        [2, 'question', 1, 'Pregunta difícil que quiero revisar']
    ];
    
    $insertBookmark = $pdo->prepare("
        INSERT IGNORE INTO user_bookmarks (user_id, content_type, content_id, notes) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($sampleBookmarks as $bookmark) {
        $insertBookmark->execute($bookmark);
    }
    echo "✅ Datos de ejemplo insertados en user_bookmarks\n";
    
    // Insertar configuraciones de ejemplo
    $sampleConfigs = [
        [1, 'theme', 'dark'],
        [1, 'notifications_email', 'true'],
        [1, 'difficulty_preference', 'intermediate'],
        [1, 'auto_advance', 'false'],
        [2, 'theme', 'light'],
        [2, 'notifications_email', 'false'],
        [2, 'difficulty_preference', 'beginner']
    ];
    
    $insertConfig = $pdo->prepare("
        INSERT IGNORE INTO user_configuration (user_id, config_key, config_value) 
        VALUES (?, ?, ?)
    ");
    
    foreach ($sampleConfigs as $config) {
        $insertConfig->execute($config);
    }
    echo "✅ Configuraciones de ejemplo insertadas\n";
    
    // Actualizar vistas de algunos temas
    $updateViews = $pdo->prepare("UPDATE topics SET views_count = ?, last_viewed = NOW() WHERE topic_id = ?");
    $updateViews->execute([15, 1]);
    $updateViews->execute([8, 2]);
    $updateViews->execute([23, 3]);
    echo "✅ Vistas de temas actualizadas\n";
    
    echo "\n🎉 Todas las tablas y datos para el sistema de favoritos han sido creados exitosamente!\n";
    echo "\nTablas creadas:\n";
    echo "- user_bookmarks: Sistema de favoritos\n";
    echo "- user_navigation_history: Historial de navegación\n";
    echo "- user_configuration: Configuración personalizada\n";
    echo "\nColumnas añadidas:\n";
    echo "- topics.views_count: Contador de vistas\n";
    echo "- topics.last_viewed: Última vista\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
