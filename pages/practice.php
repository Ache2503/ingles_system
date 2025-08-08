<?php
/**
 * Página de Práctica - Solo usuarios autenticados
 */

// Protección de sesión obligatoria
require_once __DIR__ . '/../includes/session_protection.php';
requireLogin();

// Incluir archivos necesarios
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Obtener información del usuario
$userInfo = getCurrentUserInfo();

// Validar sesión (no expirada)
validateSession();

// Log de actividad
logUserActivity('start_practice', 'Usuario inició práctica');

if (!isset($_GET['topic_id'])) {
    header('Location: pages/topics.php');
    exit;
}

$topicId = $_GET['topic_id'];
$userId = $_SESSION['user_id'];

// Obtener información del tema
$topicStmt = $pdo->prepare("SELECT * FROM topics WHERE topic_id = ?");
$topicStmt->execute([$topicId]);
$topic = $topicStmt->fetch(PDO::FETCH_ASSOC);

if (!$topic) {
    header('Location: pages/topics.php');
    exit;
}

// Obtener preguntas del tema
$questionsStmt = $pdo->prepare("
    SELECT question_id, question_text, option_a, option_b, option_c, option_d, correct_answer 
    FROM questions 
    WHERE topic_id = ? 
    ORDER BY RAND()
");
$questionsStmt->execute([$topicId]);
$questions = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($questions)) {
    header('Location: topic_detail.php?topic_id=' . $topicId);
    exit;
}

// Preparar las preguntas con opciones aleatorizadas
$randomizedQuestions = [];
foreach ($questions as $question) {
    $options = [
        'A' => $question['option_a'],
        'B' => $question['option_b'],
        'C' => $question['option_c'],
        'D' => $question['option_d']
    ];
    
    // El correct_answer ya viene como letra (A, B, C, D) de la base de datos
    $correctLetter = strtoupper(trim($question['correct_answer']));
    
    // Crear array de opciones con sus textos
    $optionsList = [];
    foreach ($options as $letter => $text) {
        $optionsList[] = [
            'letter' => $letter,
            'text' => $text,
            'isCorrect' => ($letter === $correctLetter)
        ];
    }
    
    // Aleatorizar el orden de las opciones
    shuffle($optionsList);
    
    $randomizedQuestions[] = [
        'question_id' => $question['question_id'],
        'question_text' => $question['question_text'],
        'options' => $optionsList,
        'correct_letter' => $correctLetter,
        'correct_text' => $options[$correctLetter] ?? 'N/A'
    ];
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Práctica: <?php echo htmlspecialchars($topic['title']); ?></h3>
                    <div class="quiz-progress">
                        <span id="current-question">1</span> / <span id="total-questions"><?php echo count($randomizedQuestions); ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="quiz-container">
                        <!-- Las preguntas se cargarán aquí via JavaScript -->
                    </div>
                    
                    <div id="quiz-navigation" class="mt-4">
                        <button id="prev-btn" class="btn btn-outline-secondary" disabled>
                            <i class="fas fa-chevron-left"></i> Anterior
                        </button>
                        <button id="next-btn" class="btn btn-primary float-right">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </button>
                        <button id="finish-btn" class="btn btn-success float-right" style="display: none;">
                            <i class="fas fa-check"></i> Finalizar Quiz
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Progreso del Quiz</h5>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height: 25px;">
                        <div id="progress-bar" class="progress-bar bg-primary" style="width: 0%">
                            0%
                        </div>
                    </div>
                    
                    <div class="quiz-stats">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center">
                                    <h4 id="correct-count" class="text-success">0</h4>
                                    <small class="text-muted">Correctas</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <h4 id="incorrect-count" class="text-danger">0</h4>
                                    <small class="text-muted">Incorrectas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Navegación Rápida</h6>
                        <div id="question-navigator" class="question-grid">
                            <!-- Los botones de navegación se generarán aquí -->
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body text-center">
                    <h6>¿Necesitas ayuda?</h6>
                    <p class="text-muted small">
                        Responde todas las preguntas a tu ritmo. Puedes navegar entre ellas y cambiar tus respuestas antes de finalizar.
                    </p>
                    <a href="topic_detail.php?topic_id=<?php echo $topicId; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver al Tema
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Resultados -->
<div class="modal fade" id="resultsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar"></i> Resultados del Quiz
                </h5>
            </div>
            <div class="modal-body">
                <div id="results-content">
                    <!-- Los resultados se cargarán aquí -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="location.href='topic_detail.php?topic_id=<?php echo $topicId; ?>'">
                    Volver al Tema
                </button>
                <button type="button" class="btn btn-primary" onclick="location.reload()">
                    Intentar de Nuevo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Datos del quiz
const quizData = <?php echo json_encode($randomizedQuestions); ?>;
const topicId = <?php echo $topicId; ?>;
const userId = <?php echo $userId; ?>;

// Estado del quiz
let currentQuestion = 0;
let userAnswers = {};
let quizStartTime = new Date();

// Inicializar el quiz
document.addEventListener('DOMContentLoaded', function() {
    initializeQuiz();
});

function initializeQuiz() {
    generateQuestionNavigator();
    loadQuestion(0);
    updateProgress();
    
    // Event listeners
    document.getElementById('next-btn').addEventListener('click', nextQuestion);
    document.getElementById('prev-btn').addEventListener('click', prevQuestion);
    document.getElementById('finish-btn').addEventListener('click', finishQuiz);
}

function generateQuestionNavigator() {
    const navigator = document.getElementById('question-navigator');
    navigator.innerHTML = '';
    
    quizData.forEach((_, index) => {
        const btn = document.createElement('button');
        btn.className = 'btn btn-outline-secondary btn-sm question-nav-btn';
        btn.textContent = index + 1;
        btn.onclick = () => loadQuestion(index);
        btn.setAttribute('data-question', index);
        navigator.appendChild(btn);
    });
}

function loadQuestion(questionIndex) {
    if (questionIndex < 0 || questionIndex >= quizData.length) return;
    
    currentQuestion = questionIndex;
    const question = quizData[questionIndex];
    
    // Actualizar contenido de la pregunta
    const container = document.getElementById('quiz-container');
    container.innerHTML = `
        <div class="question-content">
            <h4 class="mb-4">Pregunta ${questionIndex + 1}</h4>
            <p class="lead mb-4">${question.question_text}</p>
            
            <div class="options-container">
                ${question.options.map((option, idx) => `
                    <div class="form-check option-item mb-3">
                        <input class="form-check-input" type="radio" 
                               name="question_${question.question_id}" 
                               id="option_${questionIndex}_${idx}" 
                               value="${option.letter}"
                               data-is-correct="${option.isCorrect}"
                               ${userAnswers[question.question_id] === option.letter ? 'checked' : ''}>
                        <label class="form-check-label" for="option_${questionIndex}_${idx}">
                            <span class="option-letter">${option.letter})</span>
                            ${option.text}
                        </label>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    
    // Agregar event listeners a las opciones
    const options = container.querySelectorAll('input[type="radio"]');
    options.forEach(option => {
        option.addEventListener('change', function() {
            userAnswers[question.question_id] = this.value;
            updateNavigatorButton(questionIndex);
            updateProgress();
        });
    });
    
    // Actualizar navegación
    updateNavigationButtons();
    updateQuestionIndicator();
    updateNavigatorButton(questionIndex);
}

function updateNavigationButtons() {
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const finishBtn = document.getElementById('finish-btn');
    
    prevBtn.disabled = currentQuestion === 0;
    
    if (currentQuestion === quizData.length - 1) {
        nextBtn.style.display = 'none';
        finishBtn.style.display = 'inline-block';
    } else {
        nextBtn.style.display = 'inline-block';
        finishBtn.style.display = 'none';
    }
}

function updateQuestionIndicator() {
    document.getElementById('current-question').textContent = currentQuestion + 1;
}

function updateNavigatorButton(questionIndex) {
    const btn = document.querySelector(`[data-question="${questionIndex}"]`);
    if (btn) {
        const questionId = quizData[questionIndex].question_id;
        if (userAnswers[questionId]) {
            btn.className = 'btn btn-primary btn-sm question-nav-btn';
        } else {
            btn.className = 'btn btn-outline-secondary btn-sm question-nav-btn';
        }
    }
}

function updateProgress() {
    const answeredCount = Object.keys(userAnswers).length;
    const totalQuestions = quizData.length;
    const progressPercent = Math.round((answeredCount / totalQuestions) * 100);
    
    const progressBar = document.getElementById('progress-bar');
    progressBar.style.width = progressPercent + '%';
    progressBar.textContent = progressPercent + '%';
    
    // Actualizar contadores (temporalmente, se actualizarán al finalizar)
    document.getElementById('correct-count').textContent = answeredCount;
    document.getElementById('incorrect-count').textContent = totalQuestions - answeredCount;
}

function nextQuestion() {
    if (currentQuestion < quizData.length - 1) {
        loadQuestion(currentQuestion + 1);
    }
}

function prevQuestion() {
    if (currentQuestion > 0) {
        loadQuestion(currentQuestion - 1);
    }
}

function finishQuiz() {
    if (Object.keys(userAnswers).length < quizData.length) {
        if (!confirm('No has respondido todas las preguntas. ¿Estás seguro de que quieres finalizar?')) {
            return;
        }
    }
    
    calculateResults();
}

function calculateResults() {
    let correctCount = 0;
    let incorrectCount = 0;
    const results = [];
    
    quizData.forEach(question => {
        const userAnswer = userAnswers[question.question_id];
        const isCorrect = question.options.some(option => 
            option.letter === userAnswer && option.isCorrect
        );
        
        if (isCorrect) {
            correctCount++;
        } else {
            incorrectCount++;
        }
        
        results.push({
            question: question.question_text,
            userAnswer: userAnswer || 'Sin respuesta',
            correctAnswer: question.correct_letter,
            isCorrect: isCorrect,
            options: question.options
        });
    });
    
    const score = Math.round((correctCount / quizData.length) * 100);
    const timeSpent = Math.round((new Date() - quizStartTime) / 1000);
    
    // Actualizar contadores finales
    document.getElementById('correct-count').textContent = correctCount;
    document.getElementById('incorrect-count').textContent = incorrectCount;
    
    // Mostrar resultados
    showResults(score, correctCount, incorrectCount, timeSpent, results);
    
    // Guardar resultados en la base de datos
    saveQuizResults(score, correctCount, incorrectCount, timeSpent);
}

function showResults(score, correct, incorrect, timeSpent, detailedResults) {
    const resultsContent = document.getElementById('results-content');
    
    let scoreColor = 'danger';
    let scoreIcon = 'fa-times-circle';
    let message = '¡Sigue practicando!';
    
    if (score >= 80) {
        scoreColor = 'success';
        scoreIcon = 'fa-trophy';
        message = '¡Excelente trabajo!';
    } else if (score >= 60) {
        scoreColor = 'warning';
        scoreIcon = 'fa-medal';
        message = '¡Buen trabajo!';
    }
    
    resultsContent.innerHTML = `
        <div class="text-center mb-4">
            <i class="fas ${scoreIcon} fa-3x text-${scoreColor} mb-3"></i>
            <h3 class="text-${scoreColor}">${message}</h3>
            <h1 class="display-4 text-${scoreColor}">${score}%</h1>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="text-center">
                    <h4 class="text-success">${correct}</h4>
                    <small class="text-muted">Correctas</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h4 class="text-danger">${incorrect}</h4>
                    <small class="text-muted">Incorrectas</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h4 class="text-primary">${quizData.length}</h4>
                    <small class="text-muted">Total</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <h4 class="text-info">${Math.floor(timeSpent / 60)}:${(timeSpent % 60).toString().padStart(2, '0')}</h4>
                    <small class="text-muted">Tiempo</small>
                </div>
            </div>
        </div>
        
        <div class="detailed-results">
            <h5>Revisión Detallada</h5>
            <div class="accordion" id="resultsAccordion">
                ${detailedResults.map((result, index) => `
                    <div class="card">
                        <div class="card-header p-2" id="heading${index}">
                            <button class="btn btn-link btn-sm text-left w-100 d-flex justify-content-between align-items-center" 
                                    type="button" data-toggle="collapse" data-target="#collapse${index}">
                                <span>
                                    <i class="fas ${result.isCorrect ? 'fa-check text-success' : 'fa-times text-danger'}"></i>
                                    Pregunta ${index + 1}
                                </span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        <div id="collapse${index}" class="collapse" data-parent="#resultsAccordion">
                            <div class="card-body p-3">
                                <p><strong>Pregunta:</strong> ${result.question}</p>
                                <p><strong>Tu respuesta:</strong> 
                                    <span class="badge badge-${result.isCorrect ? 'success' : 'danger'}">
                                        ${result.userAnswer}
                                    </span>
                                </p>
                                <p><strong>Respuesta correcta:</strong> 
                                    <span class="badge badge-success">${result.correctAnswer}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    
    $('#resultsModal').modal('show');
}

function saveQuizResults(score, correct, incorrect, timeSpent) {
    fetch('../api/quiz-result-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            topic_id: topicId,
            user_id: userId,
            score: score,
            correct_answers: correct,
            incorrect_answers: incorrect,
            time_spent: timeSpent,
            total_questions: quizData.length,
            answers: userAnswers
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Resultados guardados exitosamente');
            // Mostrar puntos ganados si están disponibles
            if (data.data && data.data.points_earned) {
                showPointsEarned(data.data.points_earned, data.data.new_streak);
            }
        } else {
            console.error('Error al guardar resultados:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function showPointsEarned(points, streak) {
    // Crear notificación de puntos ganados
    const pointsNotification = document.createElement('div');
    pointsNotification.className = 'alert alert-success points-notification';
    pointsNotification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-star text-warning fa-2x mr-3"></i>
            <div>
                <h5 class="mb-1">¡Puntos Ganados!</h5>
                <p class="mb-1">+${points} puntos</p>
                ${streak > 0 ? `<small>Racha actual: ${streak} quizzes</small>` : ''}
            </div>
        </div>
    `;
    
    // Insertar en el modal de resultados
    const resultsContent = document.getElementById('results-content');
    if (resultsContent) {
        resultsContent.insertBefore(pointsNotification, resultsContent.firstChild);
    }
}
</script>

<style>
.question-nav-btn {
    width: 35px;
    height: 35px;
    margin: 2px;
    font-size: 12px;
}

.question-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.option-item {
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.option-item:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.option-item input[type="radio"]:checked + label {
    font-weight: bold;
}

.option-item:has(input[type="radio"]:checked) {
    border-color: #007bff;
    background-color: #e7f3ff;
}

.option-letter {
    font-weight: bold;
    color: #007bff;
    margin-right: 8px;
}

.quiz-progress {
    font-size: 1.1em;
    font-weight: bold;
}

.progress {
    height: 25px;
}

.quiz-stats h4 {
    font-size: 1.5rem;
}

.modal-lg {
    max-width: 800px;
}

.detailed-results .card {
    border: none;
    border-bottom: 1px solid #dee2e6;
}

.detailed-results .card:last-child {
    border-bottom: none;
}

@media (max-width: 768px) {
    .question-nav-btn {
        width: 30px;
        height: 30px;
        font-size: 11px;
    }
    
    .quiz-stats .col-6 {
        margin-bottom: 15px;
    }
}

.points-notification {
    border-left: 4px solid #ffc107;
    animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.option-item:has(input[type="radio"]:checked):hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
