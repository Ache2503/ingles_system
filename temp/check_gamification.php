<?php
require_once 'includes/db.php';
$stmt = $pdo->query('DESCRIBE user_gamification');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Columnas en user_gamification:\n";
foreach ($columns as $col) {
    echo "- {$col['Field']} ({$col['Type']})\n";
}
?>
