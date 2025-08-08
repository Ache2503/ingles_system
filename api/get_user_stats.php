<?php
/**
 * API api/get_user_stats.php - Solo usuarios autenticados
 */

// Protección de sesión para API
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Validar sesión
validateSession();

// Headers para API
header('Content-Type: application/json');

// Log de actividad API
logUserActivity('api_get_user_stats', 'Usuario accedió a API get_user_stats.php');


header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Obtener estadísticas del usuario
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_topics,
            SUM(CASE WHEN up.mastery_level = 'mastered' THEN 1 ELSE 0 END) as mastered_topics,
            AVG(COALESCE(up.score, 0)) as average_score,
            COUNT(CASE WHEN up.mastery_level != 'not_started' AND up.mastery_level IS NOT NULL THEN 1 END) as in_progress_topics
        FROM topics t
        LEFT JOIN user_progress up ON t.topic_id = up.topic_id AND up.user_id = ?
    ");
    $statsStmt->execute([$userId]);
    $basicStats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener datos de gamificación
    $gamificationStmt = $pdo->prepare("
        SELECT total_points, experience_points, study_streak
        FROM user_gamification 
        WHERE user_id = ?
    ");
    $gamificationStmt->execute([$userId]);
    $gamificationData = $gamificationStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gamificationData) {
        $gamificationData = [
            'total_points' => 0,
            'experience_points' => 0,
            'study_streak' => 0
        ];
    }
    
    // Obtener actividad reciente
    $recentActivityStmt = $pdo->prepare("
        SELECT topic_id, score, last_reviewed
        FROM user_progress 
        WHERE user_id = ? 
        ORDER BY last_reviewed DESC 
        LIMIT 5
    ");
    $recentActivityStmt->execute([$userId]);
    $recentActivity = $recentActivityStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_topics' => (int)$basicStats['total_topics'],
            'mastered_topics' => (int)$basicStats['mastered_topics'],
            'average_score' => round($basicStats['average_score']),
            'in_progress_topics' => (int)$basicStats['in_progress_topics'],
            'completion_percentage' => $basicStats['total_topics'] > 0 ? 
                round(($basicStats['mastered_topics'] / $basicStats['total_topics']) * 100) : 0
        ],
        'gamification' => $gamificationData,
        'recent_activity' => $recentActivity,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
    ]);
}
?>
