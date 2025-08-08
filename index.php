<?php
/**
 * Página Principal del Sistema de Inglés
 * Punto de entrada principal con navegación inteligente
 * ACCESO RESTRINGIDO - Solo usuarios autenticados
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado - OBLIGATORIO
$isLoggedIn = isset($_SESSION['user_id']);

// Si no está logueado, redirigir al login
if (!$isLoggedIn) {
    header('Location: auth/login.php');
    exit;
}

// Incluir archivos necesarios
require_once __DIR__ . '/includes/header.php';

// Variables de usuario autenticado
$userRole = $_SESSION['role'] ?? 'user';
$userName = $_SESSION['name'] ?? 'Usuario';
$userId = $_SESSION['user_id'];
?>

<div class="hero">
    <div class="hero-content">
        <h1>🎓 Bienvenido, <?php echo htmlspecialchars($userName); ?>!</h1>
        <h2>Sistema de Inglés - Dashboard Personal</h2>
        <p class="hero-description">
            Tu centro de aprendizaje personalizado. Accede a todos los temas, 
            revisa tu progreso y continúa mejorando tu inglés.
        </p>
        
        <div class="welcome-user">
            <div class="user-actions">
                <a href="pages/topics.php" class="btn btn-primary">
                    <span class="btn-icon">�</span>
                    Ver Temas
                </a>
                <a href="pages/practice.php" class="btn btn-primary">
                    <span class="btn-icon">🎯</span>
                    Practicar Ahora
                </a>
                <a href="pages/progress.php" class="btn btn-outline">
                    <span class="btn-icon">📊</span>
                    Mi Progreso
                </a>
                <?php if ($userRole === 'admin'): ?>
                    <a href="admin/index.php" class="btn btn-admin">
                        <span class="btn-icon">⚙️</span>
                        Panel Admin
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="hero-image">
        <div class="floating-elements">
            <div class="floating-book">📖</div>
            <div class="floating-star">⭐</div>
            <div class="floating-trophy">🏆</div>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <h2>📊 Tu Dashboard Personal</h2>
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-icon">📚</div>
            <h3>Temas Disponibles</h3>
            <p>Explora todos los temas de gramática y vocabulario</p>
            <a href="pages/topics.php" class="card-btn">Ver Temas</a>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon">🎯</div>
            <h3>Practicar</h3>
            <p>Ejercicios interactivos para mejorar tu inglés</p>
            <a href="pages/practice.php" class="card-btn">Empezar Práctica</a>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon">📈</div>
            <h3>Mi Progreso</h3>
            <p>Revisa tus estadísticas y avances</p>
            <a href="pages/progress.php" class="card-btn">Ver Progreso</a>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon">�</div>
            <h3>Mi Perfil</h3>
            <p>Configuración y datos personales</p>
            <a href="pages/profile.php" class="card-btn">Ver Perfil</a>
        </div>
    </div>
</div>

<div class="features-section">
    <h2>🚀 Características del Sistema</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">📝</div>
            <h3>Gramática Completa</h3>
            <p>Todos los temas desde Question Forms hasta Future Predictions con ejercicios interactivos</p>
            <ul class="feature-list">
                <li>Question Forms</li>
                <li>Present Perfect vs Past Simple</li>
                <li>Modal Verbs</li>
                <li>Future Predictions</li>
            </ul>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">💬</div>
            <h3>Vocabulario Esencial</h3>
            <p>Personality adjectives, life events, prepositions y mucho más vocabulario práctico</p>
            <ul class="feature-list">
                <li>Personality Adjectives</li>
                <li>Life Events</li>
                <li>Prepositions</li>
                <li>Travel Vocabulary</li>
            </ul>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Seguimiento de Progreso</h3>
            <p>Registro detallado de tus avances y temas que necesitan más práctica</p>
            <ul class="feature-list">
                <li>Estadísticas personales</li>
                <li>Temas dominados</li>
                <li>Áreas de mejora</li>
                <li>Logros desbloqueados</li>
            </ul>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🎯</div>
            <h3>Práctica Interactiva</h3>
            <p>Ejercicios adaptativos que se ajustan a tu nivel y ritmo de aprendizaje</p>
            <ul class="feature-list">
                <li>Quiz interactivos</li>
                <li>Corrección inmediata</li>
                <li>Explicaciones detalladas</li>
                <li>Niveles de dificultad</li>
            </ul>
        </div>
    </div>
</div>

<?php if (isset($_GET['demo'])): ?>
<div class="demo-section">
    <h2>🎬 Demostración del Sistema</h2>
    <div class="demo-content">
        <div class="demo-steps">
            <div class="demo-step">
                <div class="step-number">1</div>
                <h3>Selecciona un Tema</h3>
                <p>Elige entre gramática, vocabulario o temas específicos</p>
            </div>
            <div class="demo-step">
                <div class="step-number">2</div>
                <h3>Practica Interactiva</h3>
                <p>Responde preguntas con retroalimentación inmediata</p>
            </div>
            <div class="demo-step">
                <div class="step-number">3</div>
                <h3>Ve tu Progreso</h3>
                <p>Sigue tus estadísticas y mejora continua</p>
            </div>
        </div>
        <div class="demo-cta">
            <a href="auth/register.php" class="btn btn-primary">¡Empezar Ahora Gratis!</a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="stats-section">
    <h2>� Estadísticas del Sistema</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">25+</div>
            <div class="stat-label">Temas de Gramática</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">500+</div>
            <div class="stat-label">Ejercicios</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">100%</div>
            <div class="stat-label">Interactivo</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">24/7</div>
            <div class="stat-label">Disponible</div>
        </div>
    </div>
</div>

<!-- Panel de herramientas de desarrollo -->
<?php if (isset($_GET['debug']) || $userRole === 'admin'): ?>
<div class="dev-tools-section">
    <h2>🛠️ Herramientas de Desarrollo</h2>
    <div class="dev-tools-grid">
        <a href="tests/quick_test.php" class="dev-tool-card">
            <div class="tool-icon">🧪</div>
            <h3>Prueba Rápida</h3>
            <p>Verificación básica del sistema</p>
        </a>
        <a href="tests/system_verifier.php" class="dev-tool-card">
            <div class="tool-icon">🔧</div>
            <h3>Verificador</h3>
            <p>Diagnóstico completo</p>
        </a>
        <a href="tests/comprehensive_test.php" class="dev-tool-card">
            <div class="tool-icon">📊</div>
            <h3>Pruebas Completas</h3>
            <p>Suite completa con reportes</p>
        </a>
        <a href="tests/final_validation.php" class="dev-tool-card">
            <div class="tool-icon">🎯</div>
            <h3>Validación Final</h3>
            <p>Estado general del sistema</p>
        </a>
    </div>
</div>
<?php endif; ?>

<div class="cta-section">
    <div class="cta-content">
        <h2>🎯 ¿Listo para Mejorar tu Inglés?</h2>
        <p>Únete a nuestro sistema de aprendizaje y domina el inglés paso a paso</p>
        
        <?php if (!$isLoggedIn): ?>
            <div class="cta-buttons">
                <a href="auth/register.php" class="btn btn-primary btn-large">
                    Empezar Gratis
                </a>
                <a href="auth/login.php" class="btn btn-outline btn-large">
                    Ya tengo cuenta
                </a>
            </div>
        <?php else: ?>
            <div class="cta-buttons">
                <a href="pages/topics.php" class="btn btn-primary btn-large">
                    Continuar Aprendiendo
                </a>
                <a href="pages/progress.php" class="btn btn-outline btn-large">
                    Ver Mi Progreso
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

    </div>
</div>

<style>
/* Estilos específicos para la página principal */
.hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 20px;
    text-align: center;
    border-radius: 0 0 50px 50px;
    margin-bottom: 60px;
    position: relative;
    overflow: hidden;
}

.hero-content h1 {
    font-size: 3.5em;
    margin-bottom: 10px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.hero-content h2 {
    font-size: 1.8em;
    margin-bottom: 20px;
    opacity: 0.9;
}

.hero-description {
    font-size: 1.2em;
    max-width: 600px;
    margin: 0 auto 40px;
    opacity: 0.95;
    line-height: 1.6;
}

.auth-buttons {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 15px 30px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.btn-primary {
    background: white;
    color: #667eea;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.btn-secondary {
    background: transparent;
    color: white;
    border-color: white;
}

.btn-secondary:hover {
    background: white;
    color: #667eea;
}

.btn-outline {
    background: transparent;
    color: #667eea;
    border-color: #667eea;
}

.btn-admin {
    background: #dc3545;
    color: white;
}

.welcome-user {
    background: rgba(255,255,255,0.1);
    padding: 30px;
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

.user-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.dashboard-section {
    padding: 60px 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.dashboard-section h2 {
    text-align: center;
    font-size: 2.5em;
    margin-bottom: 50px;
    color: #333;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
}

.dashboard-card {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s ease;
    border: 2px solid transparent;
}

.dashboard-card:hover {
    transform: translateY(-10px);
    border-color: #667eea;
}

.card-icon {
    font-size: 3em;
    margin-bottom: 20px;
}

.card-btn {
    display: inline-block;
    margin-top: 15px;
    padding: 10px 20px;
    background: #667eea;
    color: white;
    text-decoration: none;
    border-radius: 25px;
    transition: all 0.3s ease;
}

.card-btn:hover {
    background: #5a6fd8;
    transform: translateY(-2px);
}

.features-section {
    padding: 60px 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.features-section h2 {
    text-align: center;
    font-size: 2.5em;
    margin-bottom: 50px;
    color: #333;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
}

.feature-card {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-10px);
}

.feature-icon {
    font-size: 3em;
    margin-bottom: 20px;
}

.feature-list {
    text-align: left;
    margin-top: 15px;
    padding-left: 20px;
}

.feature-list li {
    margin-bottom: 5px;
    color: #666;
}

.stats-section {
    background: #f8f9fa;
    padding: 60px 20px;
    text-align: center;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    max-width: 800px;
    margin: 0 auto;
}

.stat-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 3em;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 10px;
}

.stat-label {
    color: #666;
    font-weight: 500;
}

.demo-section {
    background: #667eea;
    color: white;
    padding: 60px 20px;
    margin: 60px 0;
}

.demo-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    max-width: 900px;
    margin: 0 auto 40px;
}

.demo-step {
    text-align: center;
    padding: 20px;
}

.step-number {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: white;
    color: #667eea;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
    margin: 0 auto 20px;
}

.dev-tools-section {
    background: #f8f9fa;
    padding: 40px 20px;
    border: 2px dashed #dee2e6;
    margin: 40px 20px;
    border-radius: 15px;
}

.dev-tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    max-width: 800px;
    margin: 0 auto;
}

.dev-tool-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    text-align: center;
    transition: transform 0.3s ease;
    border: 2px solid transparent;
}

.dev-tool-card:hover {
    transform: translateY(-5px);
    border-color: #007bff;
}

.tool-icon {
    font-size: 2em;
    margin-bottom: 10px;
}

.cta-section {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    color: white;
    padding: 80px 20px;
    text-align: center;
}

.cta-buttons {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.btn-large {
    padding: 18px 40px;
    font-size: 1.1em;
}

.floating-elements {
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 100%;
    pointer-events: none;
}

.floating-book, .floating-star, .floating-trophy {
    position: absolute;
    font-size: 2em;
    animation: float 3s ease-in-out infinite;
}

.floating-book {
    top: 20%;
    right: 10%;
    animation-delay: 0s;
}

.floating-star {
    top: 50%;
    right: 20%;
    animation-delay: 1s;
}

.floating-trophy {
    top: 70%;
    right: 5%;
    animation-delay: 2s;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

@media (max-width: 768px) {
    .hero-content h1 { font-size: 2.5em; }
    .hero-content h2 { font-size: 1.4em; }
    .auth-buttons { flex-direction: column; align-items: center; }
    .user-actions { flex-direction: column; }
    .cta-buttons { flex-direction: column; align-items: center; }
    .floating-elements { display: none; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>