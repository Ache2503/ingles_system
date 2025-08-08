<?php
require_once __DIR__ . '/includes/db.php';

try {
    $pdo->exec('ALTER TABLE user_gamification ADD COLUMN IF NOT EXISTS study_streak_date DATE');
    echo "✅ Columna study_streak_date añadida correctamente\n";
} catch (Exception $e) {
    echo "ℹ️ Columna ya existe o error: " . $e->getMessage() . "\n";
}
?>
