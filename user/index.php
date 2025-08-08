<?php include 'includes/header.php'; ?>

<div class="hero">
    <h1>Repaso para Evaluación Extraordinaria de Inglés</h1>
    <p>Sistema completo de repaso para todos los temas gramaticales y de vocabulario</p>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="auth-buttons">
            <a href="auth/login.php" class="btn">Iniciar Sesión</a>
            <a href="auth/register.php" class="btn btn-secondary">Registrarse</a>
        </div>
    <?php else: ?>
        <a href="pages/topics.php" class="btn">Comenzar Repaso</a>
    <?php endif; ?>
</div>

<div class="features">
    <div class="feature-card">
        <h3>Gramática Completa</h3>
        <p>Todos los temas desde Question Forms hasta Future Predictions</p>
    </div>
    
    <div class="feature-card">
        <h3>Vocabulario Esencial</h3>
        <p>Personality adjectives, life events, prepositions y más</p>
    </div>
    
    <div class="feature-card">
        <h3>Seguimiento de Progreso</h3>
        <p>Registro de tus avances y temas que necesitan más práctica</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>