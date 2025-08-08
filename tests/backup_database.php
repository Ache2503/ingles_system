<?php
/**
 * Generador de Backup de Base de Datos
 * Crea respaldos completos de la base de datos antes de actualizaciones
 */

require_once __DIR__ . '/../includes/config.php';

// Configuración de la base de datos
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ingles_system';

// Generar timestamp para el backup
$backupTimestamp = date('Y-m-d_H-i-s');
$backupDir = __DIR__ . '/backups';
$backupFile = "$backupDir/backup_ingles_system_$backupTimestamp.sql";

// Crear directorio de backups si no existe
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>💾 Backup de Base de Datos</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .success { background: #d4f6d4; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0; }
        h1, h2, h3 { color: #2c3e50; }
        .report-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3498db;
        }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            color: white;
            text-align: center;
        }
        .btn-primary { background: #007bff; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #212529; }
        .progress { background: #e9ecef; border-radius: 4px; overflow: hidden; margin: 10px 0; }
        .progress-bar { background: #007bff; color: white; text-align: center; padding: 8px; }
        .backup-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='report-header'>
            <h1>💾 Backup de Base de Datos</h1>
            <p>Generando respaldo completo del sistema</p>
            <p>Fecha: " . date('Y-m-d H:i:s') . "</p>
        </div>";

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='info'>✅ Conexión a la base de datos establecida</div>";
    
    // Obtener lista de tablas
    $tablesStmt = $pdo->query("SHOW TABLES");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='info'>📊 Encontradas " . count($tables) . " tablas para respaldar</div>";
    
    // Crear el archivo de backup
    $backupContent = "-- Backup de Base de Datos: $database\n";
    $backupContent .= "-- Generado: " . date('Y-m-d H:i:s') . "\n";
    $backupContent .= "-- Archivo: " . basename($backupFile) . "\n\n";
    $backupContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    $totalRows = 0;
    $processedTables = 0;
    
    foreach ($tables as $table) {
        echo "<div class='info'>📋 Procesando tabla: <code>$table</code></div>";
        
        // Obtener estructura de la tabla
        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $createResult = $createStmt->fetch(PDO::FETCH_ASSOC);
        
        $backupContent .= "-- Estructura de la tabla `$table`\n";
        $backupContent .= "DROP TABLE IF EXISTS `$table`;\n";
        $backupContent .= $createResult['Create Table'] . ";\n\n";
        
        // Obtener datos de la tabla
        $dataStmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $backupContent .= "-- Datos de la tabla `$table`\n";
            
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_values($row);
                
                // Escapar valores
                $escapedValues = array_map(function($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, $values);
                
                $backupContent .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (";
                $backupContent .= implode(', ', $escapedValues) . ");\n";
                $totalRows++;
            }
            $backupContent .= "\n";
        }
        
        $processedTables++;
        $progress = round(($processedTables / count($tables)) * 100);
        echo "<div class='progress'>
                <div class='progress-bar' style='width: {$progress}%'>
                    {$progress}% - " . count($rows) . " registros
                </div>
              </div>";
    }
    
    $backupContent .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $backupContent .= "-- Fin del backup\n";
    
    // Guardar archivo de backup
    if (file_put_contents($backupFile, $backupContent)) {
        $fileSize = filesize($backupFile);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);
        
        echo "<div class='success'>
                <h3>✅ Backup Completado Exitosamente</h3>
                <strong>Archivo:</strong> <code>" . basename($backupFile) . "</code><br>
                <strong>Tamaño:</strong> {$fileSizeMB} MB<br>
                <strong>Tablas respaldadas:</strong> " . count($tables) . "<br>
                <strong>Total de registros:</strong> $totalRows<br>
                <strong>Ubicación:</strong> <code>$backupFile</code>
              </div>";
        
        // Información adicional del backup
        echo "<div class='backup-info'>
                <h3>📋 Detalles del Backup</h3>
                <ul>
                    <li><strong>Base de datos:</strong> $database</li>
                    <li><strong>Servidor:</strong> $host</li>
                    <li><strong>Timestamp:</strong> $backupTimestamp</li>
                    <li><strong>Formato:</strong> SQL Completo</li>
                    <li><strong>Incluye:</strong> Estructura + Datos</li>
                    <li><strong>Codificación:</strong> UTF-8</li>
                </ul>
              </div>";
        
        // Generar lista de backups existentes
        $backupFiles = glob($backupDir . '/backup_ingles_system_*.sql');
        if (!empty($backupFiles)) {
            echo "<div class='info'>
                    <h3>📚 Backups Disponibles</h3>";
            
            // Ordenar por fecha (más reciente primero)
            usort($backupFiles, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            echo "<ul>";
            foreach (array_slice($backupFiles, 0, 10) as $file) { // Mostrar solo los 10 más recientes
                $fileName = basename($file);
                $fileTime = date('Y-m-d H:i:s', filemtime($file));
                $fileSize = round(filesize($file) / 1024 / 1024, 2);
                
                echo "<li><code>$fileName</code> - $fileTime ({$fileSize} MB)</li>";
            }
            echo "</ul>";
            
            if (count($backupFiles) > 10) {
                $remaining = count($backupFiles) - 10;
                echo "<p><em>... y $remaining backups más</em></p>";
            }
            
            echo "</div>";
        }
        
        // Instrucciones de restauración
        echo "<div class='warning'>
                <h3>🔄 Instrucciones de Restauración</h3>
                <p>Para restaurar este backup, ejecuta el siguiente comando:</p>
                <code>mysql -u root -p $database < " . basename($backupFile) . "</code>
                <p><strong>⚠️ Advertencia:</strong> La restauración eliminará todos los datos actuales y los reemplazará con los del backup.</p>
              </div>";
        
    } else {
        echo "<div class='error'>❌ Error al guardar el archivo de backup</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>
            <h3>❌ Error durante el Backup</h3>
            <p><strong>Error:</strong> " . $e->getMessage() . "</p>
            <p><strong>Archivo:</strong> " . $e->getFile() . "</p>
            <p><strong>Línea:</strong> " . $e->getLine() . "</p>
          </div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>
        <h3>🔧 Acciones Disponibles</h3>
        <a href='database_updater.php' class='btn btn-primary'>🚀 Actualizar BD</a>
        <a href='database_analyzer.php' class='btn btn-success'>🔍 Analizar BD</a>
        <a href='../index.php' class='btn btn-warning'>🏠 Ir al Sistema</a>
      </div>";

echo "    </div>
</body>
</html>";
?>
