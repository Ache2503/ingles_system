<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';

// Verificación de seguridad mejorada con validación adicional
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    !isset($_POST['topic_id'], $_POST['csrf_token'], $_SESSION['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token'] ||
    empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Acceso no autorizado o sesión inválida';
    header('Location: pages/topics.php');
    exit;
}

$topicId = filter_input(INPUT_POST, 'topic_id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user_id'];
$userAnswers = $_POST['answers'] ?? [];

// Validación más estricta del topic_id y respuestas
if (!$topicId || $topicId < 1 || !is_array($userAnswers)) {
    $_SESSION['error_message'] = 'Datos de quiz no válidos';
    header('Location: pages/topics.php');
    exit;
}

// Obtener información del tema con caché para reducir consultas
$cacheKey = 'topic_' . $topicId;
if (!isset($_SESSION[$cacheKey])) {
    $topicStmt = $pdo->prepare("SELECT topic_name, description FROM topics WHERE topic_id = ?");
    $topicStmt->execute([$topicId]);
    $_SESSION[$cacheKey] = $topicStmt->fetch(PDO::FETCH_ASSOC);
}
$topic = $_SESSION[$cacheKey];

if (!$topic) {
    $_SESSION['error_message'] = 'Tema no encontrado';
    header('Location: pages/topics.php');
    exit;
}

// Obtener preguntas con caché y orden aleatorio persistente
$questionsCacheKey = 'questions_' . $topicId;
if (!isset($_SESSION[$questionsCacheKey])) {
    $questionsStmt = $pdo->prepare("
        SELECT q.question_id, q.question_text, q.correct_answer, q.explanation, q.difficulty 
        FROM questions q 
        WHERE q.topic_id = ? 
        ORDER BY RAND()
    ");
    $questionsStmt->execute([$topicId]);
    $_SESSION[$questionsCacheKey] = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);
}
$questions = $_SESSION[$questionsCacheKey];

if (empty($questions)) {
    $_SESSION['error_message'] = 'No hay preguntas disponibles para este tema';
    header('Location: pages/topics.php');
    exit;
}

// Calcular puntuación con sistema de ponderación por dificultad
$correct = 0;
$totalWeight = 0;
$results = [];
$questionIds = [];

foreach ($questions as $question) {
    $questionId = $question['question_id'];
    $questionIds[] = $questionId;
    $userAnswer = $userAnswers[$questionId] ?? '';
    
    // Normalización mejorada de respuestas
    $normalizedUserAnswer = preg_replace('/\s+/', ' ', strtolower(trim($userAnswer)));
    $normalizedCorrectAnswer = preg_replace('/\s+/', ' ', strtolower(trim($question['correct_answer'])));
    
    // Comparación flexible con similar_text para respuestas parciales
    $isCorrect = ($normalizedUserAnswer === $normalizedCorrectAnswer);
    similar_text($normalizedUserAnswer, $normalizedCorrectAnswer, $similarity);
    
    // Ponderación por dificultad
    $weight = match($question['difficulty'] ?? 'medium') {
        'easy' => 1,
        'medium' => 1.5,
        'hard' => 2,
        default => 1
    };
    
    if ($isCorrect || $similarity > 85) {
        $correct += $weight;
        $isCorrect = true; // Marcar como correcta para retroalimentación
    }
    
    $totalWeight += $weight;
    
    $results[$questionId] = [
        'question_id' => $questionId,
        'question_text' => $question['question_text'],
        'user_answer' => $userAnswer,
        'correct_answer' => $question['correct_answer'],
        'explanation' => $question['explanation'],
        'is_correct' => $isCorrect,
        'difficulty' => $question['difficulty'] ?? 'medium',
        'similarity' => $similarity
    ];
}

// Calcular porcentaje ponderado
$score = $totalWeight > 0 ? min(100, round(($correct / $totalWeight) * 100)) : 0;

// Determinar nivel de dominio con umbrales ajustados
$mastery_level = match(true) {
    $score >= 90 => 'mastered',
    $score >= 75 => 'advanced',
    $score >= 60 => 'intermediate',
    $score >= 40 => 'beginner',
    default => 'not_started'
};

// Guardar progreso con transacción y manejo de errores mejorado
try {
    $pdo->beginTransaction();
    
    // Guardar progreso general con historial
    $progressStmt = $pdo->prepare("
        INSERT INTO user_progress (user_id, topic_id, score, last_reviewed, mastery_level, attempt_date)
        VALUES (:user_id, :topic_id, :score, NOW(), :mastery_level, NOW())
        ON DUPLICATE KEY UPDATE 
            score = GREATEST(score, VALUES(score)),
            last_reviewed = VALUES(last_reviewed),
            mastery_level = VALUES(mastery_level)
    ");
    
    $progressStmt->execute([
        'user_id' => $userId,
        'topic_id' => $topicId,
        'score' => $score,
        'mastery_level' => $mastery_level
    ]);
    
    // Guardar intento actual en historial
    $historyStmt = $pdo->prepare("
        INSERT INTO quiz_history (user_id, topic_id, score, attempt_date, duration)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    $historyStmt->execute([
        $userId,
        $topicId,
        $score,
        $_POST['quiz_duration'] ?? 0
    ]);
    $attemptId = $pdo->lastInsertId();
    
    // Guardar respuestas individuales con referencia al intento
    $answerStmt = $pdo->prepare("
        INSERT INTO user_answers (user_id, question_id, user_answer, is_correct, answered_at, attempt_id, similarity)
        VALUES (:user_id, :question_id, :user_answer, :is_correct, NOW(), :attempt_id, :similarity)
    ");
    
    foreach ($questionIds as $questionId) {
        $answerStmt->execute([
            'user_id' => $userId,
            'question_id' => $questionId,
            'user_answer' => $userAnswers[$questionId] ?? '',
            'is_correct' => $results[$questionId]['is_correct'],
            'attempt_id' => $attemptId,
            'similarity' => $results[$questionId]['similarity']
        ]);
    }
    
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error al guardar progreso: " . $e->getMessage());
    // No interrumpimos la experiencia del usuario por un error de base de datos
}

// Estadísticas adicionales
$timeSpent = isset($_POST['quiz_duration']) ? formatDuration($_POST['quiz_duration']) : 'N/A';
$correctCount = count(array_filter($results, fn($r) => $r['is_correct']));

// Función auxiliar para formatear duración
function formatDuration($seconds) {
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    return sprintf("%02d:%02d", $minutes, $seconds);
}
?>

<div class="quiz-result container">
    <div class="result-header">
        <h1 class="text-center">Resultados: <?php echo htmlspecialchars($topic['topic_name']); ?></h1>
        <?php if (!empty($topic['description'])): ?>
            <p class="topic-description"><?php echo htmlspecialchars($topic['description']); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="score-summary card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <div class="score-circle" data-score="<?php echo $score; ?>">
                        <svg class="circle-chart" viewBox="0 0 36 36">
                            <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <path class="circle-fill" stroke-dasharray="<?php echo $score; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <text x="18" y="20.5" class="percentage"><?php echo $score; ?>%</text>
                        </svg>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Correctas: <?php echo $correctCount; ?>/<?php echo count($questions); ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-clock text-primary"></i>
                            <span>Tiempo: <?php echo $timeSpent; ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-trophy text-warning"></i>
                            <span>Nivel: <?php echo ucfirst(translateMasteryLevel($mastery_level)); ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-chart-line text-info"></i>
                            <span>Dificultad: <?php echo calculateAverageDifficulty($results); ?></span>
                        </div>
                    </div>
                    
                    <div class="feedback alert alert-<?php 
                        echo $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
                    ?> mt-3">
                        <div class="feedback-content">
                            <?php echo getFeedbackMessage($score, $mastery_level); ?>
                        </div>
                        <?php if ($score < 70): ?>
                            <div class="suggestions mt-2">
                                <strong>Sugerencias:</strong>
                                <ul>
                                    <li>Revisa las preguntas incorrectas</li>
                                    <li>Prueba el modo de práctica enfocada</li>
                                    <li>Consulta material adicional sobre el tema</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="detailed-results card mt-4">
        <div class="card-header">
            <h3 class="mb-0"><i class="fas fa-list-ul"></i> Detalle de respuestas</h3>
            <div class="results-filter btn-group" role="group">
                <button type="button" class="btn btn-outline-primary active" data-filter="all">Todas</button>
                <button type="button" class="btn btn-outline-success" data-filter="correct">Correctas</button>
                <button type="button" class="btn btn-outline-danger" data-filter="incorrect">Incorrectas</button>
                <button type="button" class="btn btn-outline-info" data-filter="hard">Difíciles</button>
            </div>
        </div>
        
        <div class="card-body">
            <?php foreach ($results as $result): ?>
                <div class="answer card mb-3 <?php echo $result['is_correct'] ? 'border-success' : 'border-danger'; ?>" 
                     data-correct="<?php echo $result['is_correct'] ? 'true' : 'false'; ?>"
                     data-difficulty="<?php echo $result['difficulty']; ?>">
                    <div class="card-header <?php echo $result['is_correct'] ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?php echo htmlspecialchars($result['question_text']); ?></h4>
                            <span class="badge bg-<?php echo getDifficultyBadgeClass($result['difficulty']); ?>">
                                <?php echo ucfirst($result['difficulty']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="answer-details">
                            <p><strong>Tu respuesta:</strong> 
                                <span class="<?php echo $result['is_correct'] ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo htmlspecialchars($result['user_answer']); ?>
                                </span>
                                <?php if (!$result['is_correct'] && $result['similarity'] > 50): ?>
                                    <span class="similarity-badge" title="Similitud con la respuesta correcta">
                                        <?php echo round($result['similarity']); ?>% similar
                                    </span>
                                <?php endif; ?>
                            </p>
                            
                            <?php if (!$result['is_correct']): ?>
                                <p><strong>Respuesta correcta:</strong> 
                                    <span class="text-success"><?php echo htmlspecialchars($result['correct_answer']); ?></span>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($result['explanation'])): ?>
                                <div class="explanation alert alert-light mt-3">
                                    <h5><i class="fas fa-info-circle"></i> Explicación</h5>
                                    <p><?php echo nl2br(htmlspecialchars($result['explanation'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="actions card mt-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-3">
                <div class="action-buttons">
                    <a href="practice.php?topic_id=<?php echo $topicId; ?>&focus=incorrect" class="btn btn-primary">
                        <i class="fas fa-redo"></i> Repasar incorrectas
                    </a>
                    <a href="practice.php?topic_id=<?php echo $topicId; ?>" class="btn btn-secondary">
                        <i class="fas fa-redo-alt"></i> Intentar de nuevo
                    </a>
                    <a href="review.php?topic_id=<?php echo $topicId; ?>" class="btn btn-info">
                        <i class="fas fa-book"></i> Material de estudio
                    </a>
                </div>
                
                <div class="share-options btn-group">
                    <button class="btn btn-outline-success" onclick="shareResults()">
                        <i class="fas fa-share-alt"></i> Compartir
                    </button>
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <a href="progress.php?topic_id=<?php echo $topicId; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-chart-bar"></i> Ver progreso
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos de progreso -->
<div class="progress-charts row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Distribución de respuestas</h5>
            </div>
            <div class="card-body">
                <canvas id="progressChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Rendimiento por dificultad</h5>
            </div>
            <div class="card-body">
                <canvas id="difficultyChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Funciones PHP auxiliares -->
<?php
function translateMasteryLevel($level) {
    $translations = [
        'mastered' => 'Dominado',
        'advanced' => 'Avanzado',
        'intermediate' => 'Intermedio',
        'beginner' => 'Principiante',
        'not_started' => 'No iniciado'
    ];
    return $translations[$level] ?? $level;
}

function getFeedbackMessage($score, $mastery_level) {
    if ($score >= 90) {
        return '<i class="fas fa-star"></i> ¡Excelente! Dominas este tema completamente.';
    } elseif ($score >= 75) {
        return '<i class="fas fa-thumbs-up"></i> Buen trabajo, pero revisa los detalles que te faltaron.';
    } elseif ($score >= 60) {
        return '<i class="fas fa-info-circle"></i> Vas por buen camino, pero necesitas más práctica.';
    } else {
        return '<i class="fas fa-exclamation-triangle"></i> Necesitas repasar este tema con más profundidad.';
    }
}

function calculateAverageDifficulty($results) {
    $difficultyValues = [
        'easy' => 1,
        'medium' => 2,
        'hard' => 3
    ];
    
    $total = 0;
    $count = 0;
    
    foreach ($results as $result) {
        if (isset($difficultyValues[$result['difficulty']])) {
            $total += $difficultyValues[$result['difficulty']];
            $count++;
        }
    }
    
    if ($count === 0) return 'N/A';
    
    $average = $total / $count;
    
    if ($average < 1.5) return 'Fácil';
    if ($average < 2.5) return 'Media';
    return 'Difícil';
}

function getDifficultyBadgeClass($difficulty) {
    return match($difficulty) {
        'easy' => 'success',
        'medium' => 'warning',
        'hard' => 'danger',
        default => 'secondary'
    };
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Filtrado de resultados mejorado
document.querySelectorAll('[data-filter]').forEach(btn => {
    btn.addEventListener('click', function() {
        const filter = this.getAttribute('data-filter');
        document.querySelectorAll('[data-filter]').forEach(b => {
            b.classList.remove('active', 'btn-primary');
            b.classList.add('btn-outline-' + (b.classList.contains('btn-outline-danger') ? 'danger' : 
                b.classList.contains('btn-outline-success') ? 'success' : 
                b.classList.contains('btn-outline-info') ? 'info' : 'primary'));
        });
        
        this.classList.add('active', 'btn-primary');
        this.classList.remove('btn-outline-primary', 'btn-outline-success', 'btn-outline-danger', 'btn-outline-info');
        
        document.querySelectorAll('.answer').forEach(answer => {
            const isCorrect = answer.getAttribute('data-correct') === 'true';
            const difficulty = answer.getAttribute('data-difficulty');
            
            const show = 
                (filter === 'all') ||
                (filter === 'correct' && isCorrect) || 
                (filter === 'incorrect' && !isCorrect) ||
                (filter === 'hard' && difficulty === 'hard');
            
            answer.style.display = show ? 'block' : 'none';
        });
    });
});

// Compartir resultados con más opciones
function shareResults() {
    const shareData = {
        title: 'Mis resultados del quiz',
        text: `Obtuve ${<?php echo $score; ?>}% en el quiz de <?php echo htmlspecialchars($topic['topic_name']); ?>`,
        url: window.location.href
    };
    
    if (navigator.share) {
        navigator.share(shareData).catch(err => {
            console.error('Error al compartir:', err);
            showShareFallback();
        });
    } else {
        showShareFallback();
    }
}

function showShareFallback() {
    // Implementar un modal con opciones de compartir alternativas
    alert('Puedes copiar este enlace para compartir tus resultados:\n\n' + window.location.href);
}

// Gráficos de progreso
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de distribución de respuestas
    const ctx = document.getElementById('progressChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Correctas', 'Incorrectas'],
            datasets: [{
                data: [<?php echo $correctCount; ?>, <?php echo count($questions) - $correctCount; ?>],
                backgroundColor: ['#4CAF50', '#F44336'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.raw || 0;
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Gráfico de rendimiento por dificultad
    const difficultyCtx = document.getElementById('difficultyChart').getContext('2d');
    const difficultyData = {
        easy: { correct: 0, total: 0 },
        medium: { correct: 0, total: 0 },
        hard: { correct: 0, total: 0 }
    };
    
    <?php foreach ($results as $result): ?>
        difficultyData['<?php echo $result['difficulty']; ?>'].total++;
        if (<?php echo $result['is_correct'] ? 'true' : 'false'; ?>) {
            difficultyData['<?php echo $result['difficulty']; ?>'].correct++;
        }
    <?php endforeach; ?>
    
    new Chart(difficultyCtx, {
        type: 'bar',
        data: {
            labels: ['Fácil', 'Media', 'Difícil'],
            datasets: [{
                label: 'Correctas',
                data: [
                    difficultyData.easy.total ? Math.round((difficultyData.easy.correct / difficultyData.easy.total) * 100) : 0,
                    difficultyData.medium.total ? Math.round((difficultyData.medium.correct / difficultyData.medium.total) * 100) : 0,
                    difficultyData.hard.total ? Math.round((difficultyData.hard.correct / difficultyData.hard.total) * 100) : 0
                ],
                backgroundColor: '#4CAF50'
            }, {
                label: 'Incorrectas',
                data: [
                    difficultyData.easy.total ? Math.round(((difficultyData.easy.total - difficultyData.easy.correct) / difficultyData.easy.total) * 100) : 0,
                    difficultyData.medium.total ? Math.round(((difficultyData.medium.total - difficultyData.medium.correct) / difficultyData.medium.total) * 100) : 0,
                    difficultyData.hard.total ? Math.round(((difficultyData.hard.total - difficultyData.hard.correct) / difficultyData.hard.total) * 100) : 0
                ],
                backgroundColor: '#F44336'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Porcentaje'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Dificultad'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw}%`;
                        }
                    }
                }
            }
        }
    });
});

// Registrar finalización del quiz para logros y progreso
document.addEventListener('DOMContentLoaded', () => {
    // Datos del quiz completado
    const quizData = {
        topic_id: <?php echo $topicId; ?>,
        score: <?php echo round($averageScore); ?>,
        time_spent: <?php echo $_POST['quiz_duration'] ?? 300; ?>, // Duración en segundos
        correct_answers: <?php echo $correctCount; ?>,
        total_questions: <?php echo count($questionIds); ?>
    };
    
    // Actualizar progreso del usuario
    if (typeof updateUserProgress === 'function') {
        updateUserProgress('quiz_completed', quizData);
    }
    
    // Verificar logros después de un pequeño delay
    setTimeout(() => {
        if (window.gamificationManager) {
            window.gamificationManager.checkAchievements();
        }
    }, 1000);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>