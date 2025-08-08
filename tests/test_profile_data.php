<?php
session_start();

// Simular usuario logueado para prueba
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'UsuarioPrueba';
}

echo "<h1>🧪 Prueba de Profile.php</h1>";
echo "<p>Usuario simulado: " . $_SESSION['username'] . " (ID: " . $_SESSION['user_id'] . ")</p>";

// Verificar conexión a base de datos
$host = 'localhost';
$dbname = 'ingles_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión a base de datos exitosa<br>";
    
    // Verificar tabla user_gamification
    $gameData = $pdo->prepare("SELECT * FROM user_gamification WHERE user_id = ?");
    $gameData->execute([$_SESSION['user_id']]);
    $data = $gameData->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        echo "✅ Datos de gamificación encontrados<br>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    } else {
        echo "⚠️ No se encontraron datos de gamificación, creando...<br>";
        $pdo->prepare("INSERT INTO user_gamification (user_id) VALUES (?)")->execute([$_SESSION['user_id']]);
        echo "✅ Datos de gamificación creados<br>";
    }
    
    // Verificar tabla user_achievements
    $achievements = $pdo->prepare("SELECT * FROM user_achievements WHERE user_id = ?");
    $achievements->execute([$_SESSION['user_id']]);
    $achData = $achievements->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Logros encontrados: " . count($achData) . "<br>";
    
    echo "<br><a href='profile.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>🎮 Ir a Profile</a>";
    echo " <a href='logout.php' style='padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;'>🚪 Logout</a>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
