<?php
require_once 'includes/db.php';

echo "Verificando tablas de la base de datos...\n\n";

// Verificar tablas existentes
$tables = ['user_progress', 'quiz_history', 'user_answers', 'user_gamification'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        echo "Tabla $table: ✓ OK\n";
        
        // Mostrar algunas columnas importantes
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "  Columnas: " . implode(', ', array_slice($columns, 0, 5)) . "\n";
        
        // Contar registros
        $countStmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $countStmt->fetchColumn();
        echo "  Registros: $count\n\n";
        
    } catch (PDOException $e) {
        echo "Tabla $table: ✗ NO EXISTE\n";
        echo "  Error: " . $e->getMessage() . "\n\n";
    }
}

// Verificar estructura específica de la tabla questions
echo "Verificando estructura de la tabla questions:\n";
try {
    $stmt = $pdo->query("DESCRIBE questions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    
    // Mostrar una pregunta de ejemplo
    echo "\nEjemplo de pregunta:\n";
    $exampleStmt = $pdo->query("SELECT * FROM questions LIMIT 1");
    $example = $exampleStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($example) {
        echo "  ID: {$example['question_id']}\n";
        echo "  Pregunta: {$example['question_text']}\n";
        echo "  Opción A: {$example['option_a']}\n";
        echo "  Opción B: {$example['option_b']}\n";
        echo "  Opción C: {$example['option_c']}\n";
        echo "  Opción D: {$example['option_d']}\n";
        echo "  Respuesta correcta: {$example['correct_answer']}\n";
    }
    
} catch (PDOException $e) {
    echo "Error al verificar questions: " . $e->getMessage() . "\n";
}
?>
