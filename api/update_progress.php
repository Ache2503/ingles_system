<?php
/**
 * API api/update_progress.php - Solo usuarios autenticados
 */

// Protección de sesión para API
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Validar sesión
validateSession();

// Headers para API
header('Content-Type: application/json');

// Log de actividad API
logUserActivity('api_update_progress', 'Usuario accedió a API update_progress.php');


session_start();
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
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$activityType = $input['type'] ?? '';
$activityData = $input['data'] ?? [];

try {
    switch ($activityType) {
        case 'quiz_completed':
            handleQuizCompleted($pdo, $userId, $activityData);
            break;
            
        case 'topic_studied':
            handleTopicStudied($pdo, $userId, $activityData);
            break;
            
        case 'daily_login':
            handleDailyLogin($pdo, $userId);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Tipo de actividad no reconocido']);
            exit;
    }
    
    echo json_encode(['success' => true, 'message' => 'Progreso actualizado']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function handleQuizCompleted($pdo, $userId, $data) {
    $score = $data['score'] ?? 0;
    $topicId = $data['topic_id'] ?? 0;
    $timeSpent = $data['time_spent'] ?? 0;
    
    // Actualizar gamificación
    $points = calculatePoints($score, $timeSpent);
    $updateGamificationStmt = $pdo->prepare("
        UPDATE user_gamification 
        SET total_points = total_points + ?, 
            experience_points = experience_points + ?,
            total_study_time = total_study_time + ?
        WHERE user_id = ?
    ");
    $updateGamificationStmt->execute([$points, $points, $timeSpent, $userId]);
    
    // Actualizar racha si es necesario
    updateStudyStreak($pdo, $userId);
    
    // Crear notificación de progreso
    if ($score >= 80) {
        $notificationStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, created_at) 
            VALUES (?, ?, ?, 'progress', NOW())
        ");
        $notificationStmt->execute([
            $userId,
            '🎉 ¡Excelente puntuación!',
            "Has obtenido {$score}% en el quiz. ¡Sigue así! (+{$points} puntos)"
        ]);
    }
}

function handleTopicStudied($pdo, $userId, $data) {
    $topicId = $data['topic_id'] ?? 0;
    
    // Actualizar vistas del tema
    $updateViewsStmt = $pdo->prepare("
        UPDATE topics 
        SET views_count = views_count + 1, last_viewed = NOW() 
        WHERE topic_id = ?
    ");
    $updateViewsStmt->execute([$topicId]);
    
    // Registrar en historial de navegación
    $historyStmt = $pdo->prepare("
        INSERT INTO user_navigation_history (user_id, page_type, content_id, page_title, visit_time) 
        VALUES (?, 'topic', ?, (SELECT title FROM topics WHERE topic_id = ?), NOW())
    ");
    $historyStmt->execute([$userId, $topicId, $topicId]);
}

function handleDailyLogin($pdo, $userId) {
    // Verificar si ya se registró hoy
    $todayLoginStmt = $pdo->prepare("
        SELECT id FROM user_navigation_history 
        WHERE user_id = ? AND page_type = 'login' AND DATE(visit_time) = CURDATE()
    ");
    $todayLoginStmt->execute([$userId]);
    
    if (!$todayLoginStmt->fetch()) {
        // Registrar login de hoy
        $loginStmt = $pdo->prepare("
            INSERT INTO user_navigation_history (user_id, page_type, page_title, visit_time) 
            VALUES (?, 'login', 'Inicio de sesión diario', NOW())
        ");
        $loginStmt->execute([$userId]);
        
        // Actualizar racha
        updateStudyStreak($pdo, $userId);
        
        // Crear notificación de bienvenida
        $hour = date('H');
        $greeting = $hour < 12 ? 'Buenos días' : ($hour < 18 ? 'Buenas tardes' : 'Buenas noches');
        
        $notificationStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, created_at) 
            VALUES (?, ?, ?, 'welcome', NOW())
        ");
        $notificationStmt->execute([
            $userId,
            "👋 {$greeting}",
            '¡Bienvenido de vuelta! ¿Listo para seguir aprendiendo?'
        ]);
    }
}

function calculatePoints($score, $timeSpent) {
    $basePoints = 10;
    $scoreBonus = max(0, ($score - 50) / 10) * 5; // Bonus por score > 50%
    $timeBonus = min(10, $timeSpent / 60); // Bonus por tiempo dedicado
    
    return round($basePoints + $scoreBonus + $timeBonus);
}

function updateStudyStreak($pdo, $userId) {
    // Verificar si estudió ayer
    $yesterdayStmt = $pdo->prepare("
        SELECT id FROM user_navigation_history 
        WHERE user_id = ? AND DATE(visit_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    ");
    $yesterdayStmt->execute([$userId]);
    
    $studiedYesterday = $yesterdayStmt->fetch();
    
    // Verificar si estudió hoy
    $todayStmt = $pdo->prepare("
        SELECT id FROM user_navigation_history 
        WHERE user_id = ? AND DATE(visit_time) = CURDATE()
    ");
    $todayStmt->execute([$userId]);
    
    $studiedToday = $todayStmt->fetch();
    
    if ($studiedToday) {
        if ($studiedYesterday) {
            // Continuar racha
            $updateStreakStmt = $pdo->prepare("
                UPDATE user_gamification 
                SET study_streak = study_streak + 1,
                    longest_streak = GREATEST(longest_streak, study_streak + 1)
                WHERE user_id = ? AND study_streak_date != CURDATE()
            ");
            $updateStreakStmt->execute([$userId]);
        } else {
            // Reiniciar racha
            $resetStreakStmt = $pdo->prepare("
                UPDATE user_gamification 
                SET study_streak = 1,
                    study_streak_date = CURDATE(),
                    longest_streak = GREATEST(longest_streak, 1)
                WHERE user_id = ?
            ");
            $resetStreakStmt->execute([$userId]);
        }
        
        // Actualizar fecha de racha
        $updateDateStmt = $pdo->prepare("
            UPDATE user_gamification 
            SET study_streak_date = CURDATE()
            WHERE user_id = ?
        ");
        $updateDateStmt->execute([$userId]);
    }
}
?>
