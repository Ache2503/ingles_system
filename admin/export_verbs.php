<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar permisos de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ingles/login.php');
    exit;
}

// Obtener todos los verbos
$stmt = $pdo->query("SELECT * FROM irregular_verbs ORDER BY base_form");
$verbs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configurar headers para descarga
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="verbos_irregulares_' . date('Y-m-d') . '.csv"');

// Crear archivo CSV
$output = fopen('php://output', 'w');

// Escribir cabeceras
fputcsv($output, ['Infinitivo', 'Pasado Simple', 'Participio Pasado', 'Significado', 'Ejemplo']);

// Escribir datos
foreach ($verbs as $verb) {
    fputcsv($output, [
        $verb['base_form'],
        $verb['past_simple'],
        $verb['past_participle'],
        $verb['meaning'],
        $verb['example'] ?? ''
    ]);
}

fclose($output);
exit;