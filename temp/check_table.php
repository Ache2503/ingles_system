<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query('DESCRIBE user_progress');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columnas en user_progress:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
