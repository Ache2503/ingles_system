<?php
require_once __DIR__ . '/../includes/db.php';

echo "Aplicando mejoras a la base de datos...\n";

try {
    $sql = file_get_contents(__DIR__ . '/improvements.sql');
    
    // Dividir en declaraciones individuales
    $statements = explode(';', $sql);
    $executed = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement) && !preg_match('/^\/\*/', $statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                // Ignorar errores de elementos que ya existen
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false &&
                    strpos($e->getMessage(), 'Unknown column') === false) {
                    echo "⚠️  Error: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✅ Mejoras aplicadas: $executed declaraciones ejecutadas\n";
    
    // Verificar nuevas tablas
    $newTables = [
        'user_achievements',
        'user_gamification',
        'achievement_config',
        'notifications',
        'user_settings',
        'content_categories',
        'tags',
        'topic_tags',
        'media_resources',
        'study_sessions'
    ];
    
    echo "\n📋 Verificando nuevas tablas:\n";
    foreach ($newTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ $table - OK\n";
        } else {
            echo "❌ $table - NO ENCONTRADA\n";
        }
    }
    
    // Verificar datos de configuración
    $configCount = $pdo->query("SELECT COUNT(*) FROM achievement_config")->fetchColumn();
    echo "\n📊 Configuración de logros: $configCount registros\n";
    
    $categoryCount = $pdo->query("SELECT COUNT(*) FROM content_categories")->fetchColumn();
    echo "📊 Categorías de contenido: $categoryCount registros\n";
    
    $tagCount = $pdo->query("SELECT COUNT(*) FROM tags")->fetchColumn();
    echo "📊 Etiquetas: $tagCount registros\n";
    
    echo "\n🎉 ¡Mejoras aplicadas exitosamente!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
