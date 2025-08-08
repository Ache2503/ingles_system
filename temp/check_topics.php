<?php
require_once 'includes/db.php';
$stmt = $pdo->query('DESCRIBE topics');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Columnas en topics:\n";
foreach ($columns as $col) {
    echo "- {$col['Field']} ({$col['Type']})\n";
}

// Ver un ejemplo de los datos
echo "\nEjemplo de topic:\n";
$example = $pdo->query('SELECT * FROM topics LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if ($example) {
    foreach ($example as $key => $value) {
        echo "- $key: $value\n";
    }
}
?>
