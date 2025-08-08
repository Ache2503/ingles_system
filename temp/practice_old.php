<?php
include 'includes/header.php';
require 'includes/db.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['topic_id'])) {
    header('Location: topics.php');
    exit;
}

$topicId = $_GET['topic_id'];
$userId = $_SESSION['user_id'];
$mode = $_GET['mode'] ?? 'practice'; // 'practice' o 'exam'

// Obtener información del tema
$topicStmt = $pdo->prepare("SELECT * FROM topics WHERE topic_id = ?");
$topicStmt->execute([$topicId]);
$topic = $topicStmt->fetch(PDO::FETCH_ASSOC);

if (!$topic) {
    header('Location: topics.php');
    exit;
}

// Configuración según el modo
if ($mode === 'exam') {
    $questionLimit = 20;
    $practiceTime = 30 * 60; // 30 minutos
    $shuffleQuestions = true;
} else {
    $questionLimit = 10;
    $practiceTime = 20 * 60; // 20 minutos
    $shuffleQuestions = true;
}

// Obtener preguntas con todas las columnas necesarias
$sql = "SELECT question_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty FROM questions WHERE topic_id = ? " . 
       ($shuffleQuestions ? "ORDER BY RAND() " : "") . 
       "LIMIT " . (int)$questionLimit;

$questionsStmt = $pdo->prepare($sql);
$questionsStmt->execute([$topicId]);
$questions = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);

if (count($questions) === 0) {
    echo "<div class='alert alert-info'>No hay preguntas disponibles para este tema.</div>";
    include 'includes/footer.php';
    exit;
}
?>

<!-- El resto de tu código HTML/JS permanece igual -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Práctica: <?php echo htmlspecialchars($topic['title']); ?></title>
    <style>
        .question-card { display: none; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .question-card.active { display: block; }
        .options { margin: 10px 0; }
        .option { margin: 5px 0; padding: 8px; background: #f9f9f9; border-radius: 4px; }
        .nav-btn { margin: 0 5px 5px 0; min-width: 40px; }
        .nav-btn.active { background: #007bff; color: white; }
        .nav-btn.btn-success { background: #28a745 !important; color: white; }
        .nav-btn.btn-danger { background: #dc3545 !important; color: white; }
        .nav-btn.btn-warning { background: #ffc107 !important; color: #212529; }
        .timer-container { background: #333; color: white; padding: 10px; border-radius: 5px; display: inline-block; }
        #countdown { font-size: 24px; font-weight: bold; display: inline-block; margin-right: 15px; }
        .hint-container { background: #fffde7; padding: 10px; margin: 10px 0; border-left: 4px solid #ffd600; }
        .summary-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; display: flex; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 5px; max-width: 600px; max-height: 80vh; overflow-y: auto; }
        .form-actions { margin-top: 20px; }
        .fill-blank input { width: 100%; padding: 8px; margin: 5px 0; }
        .mode-indicator { position: absolute; top: 20px; right: 20px; background: #6c757d; color: white; padding: 5px 10px; border-radius: 4px; }
        .feedback { margin-top: 10px; padding: 8px; border-radius: 4px; font-weight: bold; }
        .explanation { margin-top: 8px; padding: 8px; background: #e7f3ff; border-left: 4px solid #007bff; font-style: italic; }
        #progress-display { position: fixed; top: 10px; left: 50%; transform: translateX(-50%); background: #fff; padding: 10px 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 1000; border: 2px solid #007bff; }
    </style>
</head>
<body>
<div class="practice-container">
    <div class="mode-indicator">
        Modo: <?php echo strtoupper($mode); ?>
    </div>
    
    <h1>Práctica: <?php echo htmlspecialchars($topic['title']); ?></h1>
    
    <div class="timer-container">
        <div id="countdown"><?php echo gmdate("i:s", $practiceTime); ?></div>
        <button id="pauseBtn" class="btn btn-warning">Pausar</button>
    </div>
    
    <div class="question-navigation">
        <?php foreach($questions as $index => $question): ?>
            <button type="button" class="nav-btn btn btn-outline-primary" onclick="showQuestion(<?php echo $index; ?>)">
                <?php echo $index + 1; ?>
            </button>
        <?php endforeach; ?>
    </div>
    
    <div class="quiz-form">
        <form id="quizForm" action="quiz-result.php" method="POST">
            <input type="hidden" name="topic_id" value="<?php echo $topicId; ?>">
            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
            <input type="hidden" name="mode" value="<?php echo $mode; ?>">
            
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-card" id="question-<?php echo $question['question_id']; ?>">
                    <h3>Pregunta <?php echo $index + 1; ?></h3>
                    <p><?php echo htmlspecialchars($question['question_text']); ?></p>
                    
                    <?php 
                    // Construir array de opciones desde las columnas individuales
                    $options = [];
                    if (isset($question['option_a']) && !empty($question['option_a'])) $options['a'] = $question['option_a'];
                    if (isset($question['option_b']) && !empty($question['option_b'])) $options['b'] = $question['option_b'];
                    if (isset($question['option_c']) && !empty($question['option_c'])) $options['c'] = $question['option_c'];
                    if (isset($question['option_d']) && !empty($question['option_d'])) $options['d'] = $question['option_d'];
                    
                    // Si no hay opciones, mostrar solo el texto de la pregunta
                    if (!empty($options)): ?>
                        <div class="options">
                            <?php foreach ($options as $key => $value): ?>
                                <div class="option">
                                    <input type="radio" 
                                           id="q<?php echo $question['question_id']; ?>-opt<?php echo $key; ?>"
                                           name="answers[<?php echo $question['question_id']; ?>]" 
                                           value="<?php echo htmlspecialchars($key); ?>">
                                    <label for="q<?php echo $question['question_id']; ?>-opt<?php echo $key; ?>">
                                        <?php echo htmlspecialchars($value); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="fill-blank">
                            <input type="text" 
                                   id="q<?php echo $question['question_id']; ?>"
                                   name="answers[<?php echo $question['question_id']; ?>]" 
                                   placeholder="Escribe tu respuesta aquí"
                                   class="form-control"
                                   required>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($question['hint'])): ?>
                        <div class="hint-container" style="display:none;">
                            <p><strong>Pista:</strong> <?php echo htmlspecialchars($question['hint']); ?></p>
                        </div>
                        <button type="button" class="btn btn-sm btn-info show-hint">Mostrar pista</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="form-actions">
                <button type="button" class="btn btn-info" id="showSummary">Ver resumen</button>
                <button type="submit" class="btn btn-primary">Enviar Respuestas</button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='topics.php'">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div class="summary-modal" id="summaryModal" style="display:none;">
    <div class="modal-content">
        <h3>Resumen de tus respuestas</h3>
        <div id="summaryContent"></div>
        <div style="margin-top: 20px;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('summaryModal').style.display='none'">Volver</button>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('quizForm').submit()">Confirmar envío</button>
        </div>
    </div>
</div>

<script>
// Variables globales
let currentQuestion = 0;
let timeLeft = <?php echo $practiceTime; ?>;
let timer;
const questions = document.querySelectorAll('.question-card');
const navButtons = document.querySelectorAll('.nav-btn');

// Array con las respuestas correctas
const correctAnswers = {
    <?php foreach ($questions as $index => $question): ?>
    <?php echo $question['question_id']; ?>: '<?php echo addslashes($question['correct_answer']); ?>',
    <?php endforeach; ?>
};

// Array para rastrear el progreso
let userAnswers = {};
let correctCount = 0;
let totalQuestions = <?php echo count($questions); ?>;

// Mostrar pregunta específica
function showQuestion(index) {
    questions[currentQuestion].classList.remove('active');
    navButtons[currentQuestion].classList.remove('active');
    
    currentQuestion = index;
    questions[currentQuestion].classList.add('active');
    navButtons[currentQuestion].classList.add('active');
    
    // Actualizar indicador de progreso
    updateProgress();
}

// Verificar respuesta y actualizar progreso
function checkAnswer(questionId, userAnswer) {
    const isCorrect = correctAnswers[questionId] === userAnswer;
    userAnswers[questionId] = {
        answer: userAnswer,
        correct: isCorrect
    };
    
    // Actualizar estilo del botón de navegación
    const questionIndex = Array.from(questions).findIndex(q => 
        q.id === `question-${questionId}`
    );
    
    if (questionIndex !== -1) {
        const navBtn = navButtons[questionIndex];
        navBtn.classList.remove('btn-success', 'btn-danger', 'btn-warning');
        
        if (isCorrect) {
            navBtn.classList.add('btn-success');
            navBtn.title = 'Respuesta correcta';
        } else {
            navBtn.classList.add('btn-danger');
            navBtn.title = 'Respuesta incorrecta';
        }
    }
    
    updateProgress();
    return isCorrect;
}

// Actualizar indicador de progreso
function updateProgress() {
    correctCount = Object.values(userAnswers).filter(a => a.correct).length;
    const answeredCount = Object.keys(userAnswers).length;
    
    // Actualizar display de progreso
    let progressDisplay = document.getElementById('progress-display');
    if (!progressDisplay) {
        progressDisplay = document.createElement('div');
        progressDisplay.id = 'progress-display';
        progressDisplay.style.cssText = `
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            border: 2px solid #007bff;
        `;
        document.body.appendChild(progressDisplay);
    }
    
    progressDisplay.innerHTML = `
        <strong>Progreso:</strong> ${answeredCount}/${totalQuestions} respondidas | 
        <span style="color: green;">✓ ${correctCount} correctas</span> | 
        <span style="color: red;">✗ ${answeredCount - correctCount} incorrectas</span> | 
        <strong>${totalQuestions > 0 ? Math.round((correctCount/totalQuestions)*100) : 0}% de acierto</strong>
    `;
}

// Temporizador
function startTimer() {
    timer = setInterval(() => {
        timeLeft--;
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        document.getElementById('countdown').textContent = 
            `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            
        if(timeLeft <= 0) {
            clearInterval(timer);
            alert('¡Tiempo terminado! Se enviarán tus respuestas.');
            document.getElementById('quizForm').submit();
        }
    }, 1000);
}

// Pausar/Reanudar temporizador
document.getElementById('pauseBtn').addEventListener('click', function() {
    if(timer) {
        clearInterval(timer);
        timer = null;
        this.textContent = 'Continuar';
    } else {
        startTimer();
        this.textContent = 'Pausar';
    }
});

// Mostrar/ocultar pistas
document.querySelectorAll('.show-hint').forEach(btn => {
    btn.addEventListener('click', function() {
        const hint = this.previousElementSibling;
        hint.style.display = hint.style.display === 'none' ? 'block' : 'none';
        this.textContent = hint.style.display === 'none' ? 'Mostrar pista' : 'Ocultar pista';
    });
});

// Resumen de respuestas
document.getElementById('showSummary').addEventListener('click', () => {
    let summary = '';
    let answeredCount = 0;
    
    document.querySelectorAll('.question-card').forEach((card, index) => {
        const questionText = card.querySelector('p').textContent;
        let answer = 'Sin responder';
        
        // Para preguntas de opción múltiple
        const selectedRadio = card.querySelector('input[type="radio"]:checked');
        if (selectedRadio) {
            answer = selectedRadio.nextElementSibling.textContent.trim();
            answeredCount++;
        }
        
        // Para preguntas de texto
        const textInput = card.querySelector('input[type="text"]');
        if (textInput && textInput.value) {
            answer = textInput.value;
            answeredCount++;
        }
        
        summary += `<p><strong>Pregunta ${index + 1}:</strong> ${questionText}<br>`;
        summary += `<em>Respuesta:</em> ${answer}</p><hr>`;
    });
    
    summary = `<p>Has respondido ${answeredCount} de ${questions.length} preguntas.</p>` + summary;
    document.getElementById('summaryContent').innerHTML = summary;
    document.getElementById('summaryModal').style.display = 'flex';
});

// Guardado automático
function saveProgress() {
    const formData = new FormData(document.getElementById('quizForm'));
    localStorage.setItem(`quizAutoSave_${<?php echo $topicId; ?>}_${<?php echo $userId; ?>}`, 
                       JSON.stringify(Object.fromEntries(formData)));
}

// Cargar respuestas guardadas
function loadSavedProgress() {
    const savedData = localStorage.getItem(`quizAutoSave_${<?php echo $topicId; ?>}_${<?php echo $userId; ?>}`);
    if(savedData) {
        const answers = JSON.parse(savedData);
        for(const [name, value] of Object.entries(answers.answers || {})) {
            const input = document.querySelector(`[name="${name}"]`);
            if(input) {
                if(input.type === 'radio') {
                    const radioToCheck = document.querySelector(`[name="${name}"][value="${value}"]`);
                    if(radioToCheck) radioToCheck.checked = true;
                } else if(input.type === 'text') {
                    input.value = value;
                }
            }
        }
    }
}

// Event listeners para guardar automáticamente y verificar respuestas
document.querySelectorAll('input').forEach(input => {
    input.addEventListener('change', function() {
        saveProgress();
        
        // Verificar respuesta cuando cambia
        if (this.type === 'radio' || this.type === 'text') {
            const questionCard = this.closest('.question-card');
            const questionId = questionCard.id.replace('question-', '');
            
            let userAnswer = '';
            if (this.type === 'radio') {
                userAnswer = this.value;
            } else if (this.type === 'text') {
                userAnswer = this.value.trim().toLowerCase();
            }
            
            if (userAnswer) {
                const isCorrect = checkAnswer(parseInt(questionId), userAnswer);
                
                // Mostrar feedback visual inmediato
                const feedbackDiv = questionCard.querySelector('.feedback') || 
                    (() => {
                        const div = document.createElement('div');
                        div.className = 'feedback';
                        div.style.cssText = 'margin-top: 10px; padding: 8px; border-radius: 4px; font-weight: bold;';
                        questionCard.appendChild(div);
                        return div;
                    })();
                
                if (isCorrect) {
                    feedbackDiv.innerHTML = '✅ ¡Correcto!';
                    feedbackDiv.style.backgroundColor = '#d4edda';
                    feedbackDiv.style.color = '#155724';
                    feedbackDiv.style.border = '1px solid #c3e6cb';
                } else {
                    feedbackDiv.innerHTML = `❌ Incorrecto. La respuesta correcta es: <strong>${correctAnswers[questionId]}</strong>`;
                    feedbackDiv.style.backgroundColor = '#f8d7da';
                    feedbackDiv.style.color = '#721c24';
                    feedbackDiv.style.border = '1px solid #f5c6cb';
                }
                
                // Mostrar explicación si existe
                <?php foreach ($questions as $question): ?>
                if (parseInt(questionId) === <?php echo $question['question_id']; ?> && '<?php echo addslashes($question['explanation'] ?? ''); ?>') {
                    const explanationDiv = questionCard.querySelector('.explanation') || 
                        (() => {
                            const div = document.createElement('div');
                            div.className = 'explanation';
                            div.style.cssText = 'margin-top: 8px; padding: 8px; background: #e7f3ff; border-left: 4px solid #007bff; font-style: italic;';
                            questionCard.appendChild(div);
                            return div;
                        })();
                    explanationDiv.innerHTML = `<strong>Explicación:</strong> <?php echo addslashes($question['explanation'] ?? ''); ?>`;
                }
                <?php endforeach; ?>
            }
        }
    });
});

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    // Mostrar primera pregunta
    showQuestion(0);
    
    // Inicializar progreso
    updateProgress();
    
    // Iniciar temporizador
    startTimer();
    
    // Cargar progreso guardado
    loadSavedProgress();
    
    // Limpiar almacenamiento al enviar el formulario
    document.getElementById('quizForm').addEventListener('submit', () => {
        localStorage.removeItem(`quizAutoSave_${<?php echo $topicId; ?>}_${<?php echo $userId; ?>}`);
        
        // Registrar actividad de estudio del tema
        if (window.updateUserProgress) {
            updateUserProgress('topic_studied', { topic_id: <?php echo $topicId; ?> });
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>