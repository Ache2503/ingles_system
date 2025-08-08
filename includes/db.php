<?php
// includes/db.php
$host = 'localhost';
$dbname = 'ingles_system';
$username = 'root';
$password = '';

// Variable global para la conexión PDO
$pdo = null;

/**
 * Función para obtener la conexión a la base de datos
 * @return PDO
 */
function getDBConnection() {
    global $pdo;
    
    if ($pdo === null) {
        $host = 'localhost';
        $dbname = 'ingles_system';
        $username = 'root';
        $password = '';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("No se pudo conectar a la base de datos: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Mantener compatibilidad con código existente
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}