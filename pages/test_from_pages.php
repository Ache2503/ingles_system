<?php
// Simular sesión
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';

require_once __DIR__ . '/../includes/header.php';
?>

<h1>Página de Prueba DESDE PAGES/ - Verificar Header</h1>

<p>Esta página está en la carpeta /pages/ para verificar que las URLs del header se generen correctamente desde aquí.</p>

<div>
    <h2>Enlaces de prueba:</h2>
    <ul>
        <li><a href="<?php echo nav_url('topics'); ?>">Ir a Topics (desde nav_url)</a></li>
        <li><a href="<?php echo nav_url('practice'); ?>">Ir a Practice (desde nav_url)</a></li>
        <li><a href="<?php echo nav_url('progress'); ?>">Ir a Progress (desde nav_url)</a></li>
    </ul>
    
    <h2>Información de debug:</h2>
    <ul>
        <li>nav_url('topics'): <?php echo nav_url('topics'); ?></li>
        <li>nav_url('practice'): <?php echo nav_url('practice'); ?></li>
        <li>nav_url('progress'): <?php echo nav_url('progress'); ?></li>
    </ul>
</div>

<script>
console.log('Ubicación actual:', window.location.href);
console.log('Enlaces en el header:');
document.querySelectorAll('header nav a').forEach(link => {
    console.log(link.textContent + ':', link.href);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
