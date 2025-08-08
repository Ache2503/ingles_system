<?php
/**
 * API para guardar resultados de quiz - Solo usuarios autenticados
 */

// Protección de sesión obligatoria para APIs
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Incluir archivos necesarios
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Validar sesión
validateSession();

// Verificar que sea una petición POST con JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Log de actividad API
logUserActivity('api_quiz_result', 'Usuario envió resultados de quiz via API');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Obtener datos JSON del cuerpo de la petición
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit;
}

// Validar datos requeridos
$requiredFields = ['topic_id', 'score', 'correct_answers', 'incorrect_answers', 'time_spent', 'total_questions', 'answers'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Campo requerido faltante: $field"]);
        exit;
    }
}

$userId = $_SESSION['user_id'];
$topicId = (int)$input['topic_id'];
$score = (int)$input['score'];
$correctAnswers = (int)$input['correct_answers'];
$incorrectAnswers = (int)$input['incorrect_answers'];
$timeSpent = (int)$input['time_spent'];
$totalQuestions = (int)$input['total_questions'];
$userAnswers = $input['answers'];

// Validaciones adicionales
if ($topicId <= 0 || $score < 0 || $score > 100 || $totalQuestions <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos de quiz inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Determinar nivel de dominio
    $masteryLevel = 'not_started';
    if ($score >= 90) {
        $masteryLevel = 'mastered';
    } elseif ($score >= 75) {
        $masteryLevel = 'proficient';
    } elseif ($score >= 60) {
        $masteryLevel = 'developing';
    } elseif ($score >= 40) {
        $masteryLevel = 'beginning';
    }
    
    // Verificar si ya existe progreso para este usuario y tema
    $existingProgressStmt = $pdo->prepare("
        SELECT score FROM user_progress 
        WHERE user_id = ? AND topic_id = ?
    ");
    $existingProgressStmt->execute([$userId, $topicId]);
    $existingProgress = $existingProgressStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingProgress) {
        // Actualizar solo si la nueva puntuación es mejor
        $bestScore = max($existingProgress['score'], $score);
        
        $updateProgressStmt = $pdo->prepare("
            UPDATE user_progress 
            SET score = ?, last_reviewed = NOW(), mastery_level = ?
            WHERE user_id = ? AND topic_id = ?
        ");
        $updateProgressStmt->execute([$bestScore, $masteryLevel, $userId, $topicId]);
    } else {
        // Crear nuevo registro de progreso
        $insertProgressStmt = $pdo->prepare("
            INSERT INTO user_progress (user_id, topic_id, score, last_reviewed, mastery_level)
            VALUES (?, ?, ?, NOW(), ?)
        ");
        $insertProgressStmt->execute([$userId, $topicId, $score, $masteryLevel]);
    }
    
    // Insertar en el historial de quizzes
    $historyStmt = $pdo->prepare("
        INSERT INTO quiz_history (user_id, topic_id, score, correct_answers, incorrect_answers, 
                                 total_questions, time_spent, completed_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $historyStmt->execute([
        $userId, $topicId, $score, $correctAnswers, 
        $incorrectAnswers, $totalQuestions, $timeSpent
    ]);
    
    $historyId = $pdo->lastInsertId();
    
    // Obtener las preguntas correctas para validar respuestas
    $questionsStmt = $pdo->prepare("
        SELECT question_id, correct_answer 
        FROM questions 
        WHERE topic_id = ?
    ");
    $questionsStmt->execute([$topicId]);
    $questions = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear un mapa de preguntas para validación
    $questionMap = [];
    foreach ($questions as $question) {
        // correct_answer ya viene como letra (A, B, C, D) de la base de datos
        $correctLetter = strtoupper(trim($question['correct_answer']));
        $questionMap[$question['question_id']] = $correctLetter;
    }
    
    // Guardar respuestas individuales
    $answerStmt = $pdo->prepare("
        INSERT INTO user_answers (user_id, question_id, user_answer, is_correct, 
                                 answered_at, quiz_history_id)
        VALUES (?, ?, ?, ?, NOW(), ?)
    ");
    
    foreach ($userAnswers as $questionId => $userAnswer) {
        $questionId = (int)$questionId;
        $isCorrect = isset($questionMap[$questionId]) && 
                    strtoupper($userAnswer) === $questionMap[$questionId];
        
        $answerStmt->execute([
            $userId, $questionId, $userAnswer, $isCorrect, $historyId
        ]);
    }
    
    // Actualizar estadísticas de gamificación
    $gamificationStmt = $pdo->prepare("
        SELECT * FROM user_gamification WHERE user_id = ?
    ");
    $gamificationStmt->execute([$userId]);
    $gamification = $gamificationStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($gamification) {
        // Calcular puntos ganados (base + bonus por puntuación)
        $basePoints = 10;
        $bonusPoints = floor($score / 10) * 2; // 2 puntos extra por cada 10% de puntuación
        $totalPointsEarned = $basePoints + $bonusPoints;
        
        // Actualizar racha si la puntuación es >= 70%
        $newStreak = $score >= 70 ? $gamification['study_streak'] + 1 : 0;
        $bestStreak = max($gamification['longest_streak'], $newStreak);
        
        $updateGamificationStmt = $pdo->prepare("
            UPDATE user_gamification 
            SET total_points = total_points + ?, 
                study_streak = ?,
                longest_streak = ?,
                last_activity_date = CURDATE(),
                updated_at = NOW()
            WHERE user_id = ?
        ");
        $updateGamificationStmt->execute([
            $totalPointsEarned, $newStreak, $bestStreak, $userId
        ]);
    } else {
        // Crear registro de gamificación
        $initialPoints = 10 + floor($score / 10) * 2;
        $initialStreak = $score >= 70 ? 1 : 0;
        
        $insertGamificationStmt = $pdo->prepare("
            INSERT INTO user_gamification 
            (user_id, total_points, study_streak, longest_streak, last_activity_date, created_at, updated_at)
            VALUES (?, ?, ?, ?, CURDATE(), NOW(), NOW())
        ");
        $insertGamificationStmt->execute([
            $userId, $initialPoints, $initialStreak, $initialStreak
        ]);
    }
    
    $pdo->commit();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Resultados guardados exitosamente',
        'data' => [
            'score' => $score,
            'mastery_level' => $masteryLevel,
            'points_earned' => $totalPointsEarned ?? $initialPoints ?? 0,
            'new_streak' => $newStreak ?? $initialStreak ?? 0,
            'quiz_history_id' => $historyId
        ]
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error al guardar resultados del quiz: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor al guardar los resultados'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error general al procesar quiz: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar los resultados del quiz'
    ]);
}
?>
