<?php
session_start();
$_SESSION['user_id'] = 1; // Simular usuario logueado
$_SESSION['role'] = 'user';

require_once __DIR__ . '/includes/navigation.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Prueba de URLs</title>
</head>
<body>
    <h1>Prueba de URLs del Sistema de Navegación</h1>
    
    <h2>URLs desde PHP:</h2>
    <ul>
        <li>Topics: <a href="<?php echo nav_url('topics'); ?>"><?php echo nav_url('topics'); ?></a></li>
        <li>Practice: <a href="<?php echo nav_url('practice'); ?>"><?php echo nav_url('practice'); ?></a></li>
        <li>Progress: <a href="<?php echo nav_url('progress'); ?>"><?php echo nav_url('progress'); ?></a></li>
        <li>Practice con ID: <a href="<?php echo nav_url('practice', ['topic_id' => 1]); ?>"><?php echo nav_url('practice', ['topic_id' => 1]); ?></a></li>
    </ul>
    
    <h2>Información de la página actual:</h2>
    <ul>
        <li>URL actual: <?php echo $_SERVER['REQUEST_URI'] ?? 'No disponible'; ?></li>
        <li>Script actual: <?php echo $_SERVER['SCRIPT_NAME'] ?? 'No disponible'; ?></li>
        <li>Host: <?php echo $_SERVER['HTTP_HOST'] ?? 'No disponible'; ?></li>
    </ul>
    
    <script>
        console.log('URLs generadas:');
        console.log('Topics:', '<?php echo nav_url('topics'); ?>');
        console.log('Practice:', '<?php echo nav_url('practice'); ?>');
        console.log('Current location:', window.location.href);
    </script>
</body>
</html>
