<?php
/**
 * Script para crear e inicializar la base de datos del sistema de inglés
 * Ejecutar este archivo una sola vez para configurar la base de datos
 */

// Configuración de la base de datos
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ingles_system';

try {
    echo "🚀 Iniciando configuración de la base de datos...\n";
    
    // Conectar a MySQL sin especificar base de datos
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conexión a MySQL establecida\n";
    
    // Crear la base de datos
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de datos '$database' creada o verificada\n";
    
    // Conectar a la base de datos específica
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Leer y ejecutar el archivo SQL
    $sqlFile = __DIR__ . '/create_database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("No se encontró el archivo SQL: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir el SQL en declaraciones individuales
    $statements = explode(';', $sql);
    
    $executedStatements = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
                $executedStatements++;
            } catch (PDOException $e) {
                // Ignorar errores de tablas que ya existen
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠️  Error en declaración: " . substr($statement, 0, 50) . "...\n";
                    echo "   " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✅ $executedStatements declaraciones SQL ejecutadas\n";
    
    // Verificar que las tablas se crearon correctamente
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Tablas creadas: " . implode(', ', $tables) . "\n";
    
    // Verificar datos de ejemplo
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $topicCount = $pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn();
    $questionCount = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
    $verbCount = $pdo->query("SELECT COUNT(*) FROM irregular_verbs")->fetchColumn();
    
    echo "\n📊 Datos iniciales:\n";
    echo "   - Usuarios: $userCount\n";
    echo "   - Temas: $topicCount\n";
    echo "   - Preguntas: $questionCount\n";
    echo "   - Verbos irregulares: $verbCount\n";
    
    echo "\n🎉 ¡Base de datos configurada exitosamente!\n";
    echo "\n👤 Usuario administrador creado:\n";
    echo "   - Email: admin@ingles.com\n";
    echo "   - Contraseña: password\n";
    echo "\n🌐 Puedes acceder al sistema en: http://localhost/ingles\n";
    
} catch (PDOException $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
