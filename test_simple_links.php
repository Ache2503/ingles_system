<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba Simple de Enlaces</title>
</head>
<body>
    <h1>Prueba de Enlaces Simple</h1>
    
    <?php
    require_once(__DIR__ . '/includes/navigation.php');
    $topicsUrl = nav_url('topics');
    ?>
    
    <p>URL generada por PHP: <code><?php echo $topicsUrl; ?></code></p>
    
    <p>Enlaces de prueba:</p>
    <ul>
        <li><a href="<?php echo $topicsUrl; ?>">Enlace a Topics (generado por PHP)</a></li>
        <li><a href="http://localhost/ingles/pages/topics.php">Enlace directo hardcodeado</a></li>
        <li><a href="/ingles/pages/topics.php">Enlace relativo absoluto</a></li>
        <li><a href="pages/topics.php">Enlace relativo</a></li>
    </ul>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a');
            links.forEach((link, index) => {
                console.log(`Enlace ${index + 1}:`, link.href);
                
                link.addEventListener('click', function(e) {
                    console.log('Clic en enlace:', link.href);
                    console.log('URL actual antes del clic:', window.location.href);
                });
            });
        });
    </script>
</body>
</html>
