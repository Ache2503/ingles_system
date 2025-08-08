<?php
// Script para verificar y crear las columnas necesarias en user_gamification
$host = 'localhost';
$dbname = 'ingles_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar estructura de la tabla user_gamification
    $result = $pdo->query("DESCRIBE user_gamification");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Columnas actuales en user_gamification:</h2>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
    
    // Agregar columnas faltantes
    $columnsToAdd = [
        'longest_streak' => 'INT DEFAULT 0',
        'total_study_time' => 'INT DEFAULT 0',
        'current_level' => 'INT DEFAULT 1'
    ];
    
    echo "<h2>Verificando columnas necesarias:</h2>";
    foreach ($columnsToAdd as $columnName => $columnDef) {
        if (!in_array($columnName, $columns)) {
            try {
                $pdo->exec("ALTER TABLE user_gamification ADD COLUMN $columnName $columnDef");
                echo "✅ Columna '$columnName' agregada<br>";
            } catch (Exception $e) {
                echo "❌ Error agregando '$columnName': " . $e->getMessage() . "<br>";
            }
        } else {
            echo "✅ Columna '$columnName' ya existe<br>";
        }
    }
    
    // Verificar si hay usuarios sin registro en user_gamification
    $usersWithoutGameData = $pdo->query("
        SELECT u.user_id, u.username 
        FROM users u 
        LEFT JOIN user_gamification ug ON u.user_id = ug.user_id 
        WHERE ug.user_id IS NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Usuarios sin datos de gamificación:</h2>";
    if (empty($usersWithoutGameData)) {
        echo "✅ Todos los usuarios tienen datos de gamificación<br>";
    } else {
        foreach ($usersWithoutGameData as $user) {
            try {
                $pdo->prepare("INSERT INTO user_gamification (user_id) VALUES (?)")
                    ->execute([$user['user_id']]);
                echo "✅ Datos de gamificación creados para: " . htmlspecialchars($user['username']) . "<br>";
            } catch (Exception $e) {
                echo "❌ Error creando datos para " . htmlspecialchars($user['username']) . ": " . $e->getMessage() . "<br>";
            }
        }
    }
    
    echo "<br><h2>🎉 Verificación completada</h2>";
    echo "<a href='profile.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Probar Profile</a>";
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
}
?>
