<?php
/**
 * API api/check_achievements.php - Solo usuarios autenticados
 */

// Protección de sesión para API
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Validar sesión
validateSession();

// Headers para API
header('Content-Type: application/json');

// Log de actividad API
logUserActivity('api_check_achievements', 'Usuario accedió a API check_achievements.php');


session_start();
ob_start(); // Iniciar buffer
header('Content-Type: application/json');

// Conexión a base de datos simplificada
$host = 'localhost';
$dbname = 'ingles_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Verificar logros de primer quiz
    $firstQuizStmt = $pdo->prepare("
        SELECT COUNT(*) as quiz_count 
        FROM quiz_history 
        WHERE user_id = ?
    ");
    $firstQuizStmt->execute([$userId]);
    $quizCount = $firstQuizStmt->fetchColumn();
    
    if ($quizCount == 1) {
        checkAndAwardAchievement($pdo, $userId, 'first_quiz', 'Primer Quiz', 'Completaste tu primer quiz', 10);
    }
    
    // Verificar logros de rachas
    $streakStmt = $pdo->prepare("
        SELECT study_streak 
        FROM user_gamification 
        WHERE user_id = ?
    ");
    $streakStmt->execute([$userId]);
    $streak = $streakStmt->fetchColumn() ?: 0;
    
    if ($streak >= 7) {
        checkAndAwardAchievement($pdo, $userId, 'week_streak', 'Racha Semanal', 'Estudiaste 7 días consecutivos', 50);
    }
    
    if ($streak >= 30) {
        checkAndAwardAchievement($pdo, $userId, 'month_streak', 'Racha Mensual', 'Estudiaste 30 días consecutivos', 200);
    }
    
    // Verificar logros de puntuación
    $scoreStmt = $pdo->prepare("
        SELECT AVG(score) as avg_score, COUNT(*) as total_quizzes
        FROM quiz_history 
        WHERE user_id = ?
    ");
    $scoreStmt->execute([$userId]);
    $scoreData = $scoreStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($scoreData['avg_score'] >= 90 && $scoreData['total_quizzes'] >= 5) {
        checkAndAwardAchievement($pdo, $userId, 'perfectionist', 'Perfeccionista', 'Mantén un promedio de 90% en 5 quizzes', 100);
    }
    
    // Verificar logros de temas completados
    $topicsStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT topic_id) as completed_topics
        FROM user_progress 
        WHERE user_id = ? AND mastery_level = 'mastered'
    ");
    $topicsStmt->execute([$userId]);
    $completedTopics = $topicsStmt->fetchColumn();
    
    if ($completedTopics >= 5) {
        checkAndAwardAchievement($pdo, $userId, 'topic_master', 'Maestro de Temas', 'Domina 5 temas completamente', 150);
    }
    
    // Verificar logros de puntos totales
    $pointsStmt = $pdo->prepare("
        SELECT total_points 
        FROM user_gamification 
        WHERE user_id = ?
    ");
    $pointsStmt->execute([$userId]);
    $totalPoints = $pointsStmt->fetchColumn() ?: 0;
    
    if ($totalPoints >= 1000) {
        checkAndAwardAchievement($pdo, $userId, 'point_collector', 'Coleccionista de Puntos', 'Acumula 1000 puntos', 100);
    }
    
    // Obtener logros recientes (últimos 5 minutos)
    $recentAchievementsStmt = $pdo->prepare("
        SELECT * FROM user_achievements 
        WHERE user_id = ? 
        AND earned_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY earned_at DESC
    ");
    $recentAchievementsStmt->execute([$userId]);
    $recentAchievements = $recentAchievementsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'new_achievements' => $recentAchievements,
        'total_achievements' => count($recentAchievements)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar logros: ' . $e->getMessage()
    ]);
}

function checkAndAwardAchievement($pdo, $userId, $achievementType, $name, $description, $points) {
    // Verificar si ya tiene este logro
    $checkStmt = $pdo->prepare("
        SELECT id FROM user_achievements 
        WHERE user_id = ? AND achievement_type = ?
    ");
    $checkStmt->execute([$userId, $achievementType]);
    
    if (!$checkStmt->fetch()) {
        // Otorgar logro
        $insertStmt = $pdo->prepare("
            INSERT INTO user_achievements 
            (user_id, achievement_type, achievement_name, achievement_description, points_earned, earned_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $insertStmt->execute([$userId, $achievementType, $name, $description, $points]);
        
        // Actualizar puntos del usuario
        $updatePointsStmt = $pdo->prepare("
            UPDATE user_gamification 
            SET total_points = total_points + ?, experience_points = experience_points + ?
            WHERE user_id = ?
        ");
        $updatePointsStmt->execute([$points, $points, $userId]);
        
        // Crear notificación
        $notificationStmt = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, title, message, type, created_at) 
            VALUES (?, ?, ?, 'achievement', NOW())
        ");
        $notificationStmt->execute([
            $userId, 
            '🏆 ¡Nuevo Logro!', 
            "Has desbloqueado: {$name} - {$description} (+{$points} puntos)"
        ]);
        
        return true;
    }
    
    return false;
}
?>
