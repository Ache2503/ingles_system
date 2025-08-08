<?php
// Script para crear las tablas necesarias
$host = 'localhost';
$dbname = 'ingles_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear tabla notifications si no existe
    $sql = "
    CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('achievement', 'reminder', 'system', 'quiz_result') NOT NULL DEFAULT 'system',
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "✅ Tabla notifications creada/verificada<br>";
    
    // Verificar si existe la columna study_streak_date en user_gamification
    $result = $pdo->query("DESCRIBE user_gamification");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('study_streak_date', $columns)) {
        $pdo->exec("ALTER TABLE user_gamification ADD COLUMN study_streak_date DATE NULL");
        echo "✅ Columna study_streak_date agregada<br>";
    } else {
        echo "✅ Columna study_streak_date ya existe<br>";
    }
    
    // Insertar notificación de prueba
    $testUserId = 1; // Asumiendo que existe un usuario con ID 1
    $checkUser = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $checkUser->execute([$testUserId]);
    
    if ($checkUser->fetch()) {
        $insertNotif = $pdo->prepare("
            INSERT IGNORE INTO notifications (user_id, type, title, message) 
            VALUES (?, 'system', 'Bienvenido', 'Sistema de notificaciones funcionando correctamente')
        ");
        $insertNotif->execute([$testUserId]);
        echo "✅ Notificación de prueba insertada<br>";
    } else {
        echo "⚠️ No se encontró usuario con ID 1<br>";
    }
    
    echo "<br>🎉 ¡Configuración completada exitosamente!";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
