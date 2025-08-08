<?php
/**
 * Sistema de Pruebas Completo con Generación de Reportes
 * Ejecuta todas las pruebas y genera un reporte detallado
 */

// Configurar para mostrar errores en tiempo real
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

require_once __DIR__ . '/SystemTester.php';
require_once __DIR__ . '/PDFReportGenerator.php';

// Configurar headers para HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Pruebas Completo</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .action-buttons {
            text-align: center;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .test-output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            max-height: 400px;
            overflow-y: auto;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .report-links {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .report-links h3 {
            margin-top: 0;
            color: #0c5460;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .feature-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .feature-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Sistema de Pruebas Completo</h1>
            <p>Análisis integral del Sistema de Inglés con generación de reportes</p>
        </div>
        
        <div class="content">
            <?php if (!isset($_GET['action'])): ?>
                
                <h2>🚀 Características del Sistema de Pruebas</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🔍</div>
                        <h3>Análisis Completo</h3>
                        <p>Verifica base de datos, archivos, APIs y funcionalidades</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h3>Reportes Detallados</h3>
                        <p>Genera reportes en HTML y texto con análisis visual</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <h3>Pruebas Rápidas</h3>
                        <p>Ejecución optimizada con medición de tiempos</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🎯</div>
                        <h3>Diagnóstico Preciso</h3>
                        <p>Identifica problemas específicos con recomendaciones</p>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="?action=run_tests" class="btn btn-success">🧪 Ejecutar Todas las Pruebas</a>
                    <a href="?action=quick_test" class="btn">⚡ Prueba Rápida</a>
                    <a href="?action=view_reports" class="btn btn-warning">📋 Ver Reportes Anteriores</a>
                </div>
                
                <h2>📋 Pruebas Incluidas</h2>
                <ul style="line-height: 2;">
                    <li><strong>🔌 Conectividad:</strong> Base de datos y configuración</li>
                    <li><strong>📁 Estructura:</strong> Archivos y directorios requeridos</li>
                    <li><strong>🗄️ Base de Datos:</strong> Tablas y esquema</li>
                    <li><strong>🔐 Autenticación:</strong> Login, registro y sesiones</li>
                    <li><strong>🌐 APIs:</strong> Endpoints y respuestas</li>
                    <li><strong>📱 Páginas:</strong> Accesibilidad y errores</li>
                    <li><strong>⚙️ Permisos:</strong> Escritura y configuración</li>
                </ul>
                
            <?php elseif ($_GET['action'] === 'run_tests'): ?>
                
                <h2>🧪 Ejecutando Pruebas Completas</h2>
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Analizando el sistema...</p>
                </div>
                
                <div class="test-output" id="test-output">
                    <?php
                    // Ejecutar las pruebas
                    $tester = new SystemTester();
                    $tester->runAllTests();
                    
                    // Obtener resultados
                    $results = $tester->getResults();
                    
                    // Generar reportes
                    $reportGenerator = new PDFReportGenerator($results);
                    $htmlReport = $reportGenerator->generateHTMLReport();
                    $textReport = $reportGenerator->generateTextReport();
                    
                    echo "<script>document.querySelector('.loading').style.display = 'none';</script>";
                    ?>
                </div>
                
                <?php if (isset($htmlReport) && $htmlReport['success']): ?>
                <div class="report-links">
                    <h3>📊 Reportes Generados</h3>
                    <p><strong>Reporte completo generado exitosamente:</strong></p>
                    <div class="action-buttons">
                        <a href="reports/<?php echo $htmlReport['filename']; ?>" target="_blank" class="btn btn-success">
                            📊 Ver Reporte HTML Completo
                        </a>
                        <a href="reports/<?php echo $textReport['filename']; ?>" target="_blank" class="btn">
                            📄 Ver Reporte de Texto
                        </a>
                        <a href="?" class="btn btn-warning">🔙 Volver al Inicio</a>
                    </div>
                    <p><small>💡 El reporte HTML incluye gráficos, análisis detallado y es apto para imprimir</small></p>
                </div>
                <?php endif; ?>
                
            <?php elseif ($_GET['action'] === 'quick_test'): ?>
                
                <h2>⚡ Prueba Rápida del Sistema</h2>
                <div class="test-output">
                    <?php
                    $tester = new SystemTester();
                    
                    echo "<h3>🔌 Verificación Básica</h3>";
                    $tester->runTest('Conexión a Base de Datos', [$tester, 'testDatabaseConnection']);
                    $tester->runTest('Estructura de Archivos', [$tester, 'testFileStructure']);
                    $tester->runTest('Funciones de Autenticación', [$tester, 'testAuthFunctions']);
                    
                    $tester->displaySummary();
                    ?>
                </div>
                
                <div class="action-buttons">
                    <a href="?action=run_tests" class="btn btn-success">🧪 Ejecutar Pruebas Completas</a>
                    <a href="?" class="btn">🔙 Volver al Inicio</a>
                </div>
                
            <?php elseif ($_GET['action'] === 'view_reports'): ?>
                
                <h2>📋 Reportes Anteriores</h2>
                <?php
                $reportsDir = __DIR__ . '/reports/';
                if (is_dir($reportsDir)) {
                    $reports = glob($reportsDir . 'test_report_*.html');
                    if (!empty($reports)) {
                        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px;'>";
                        echo "<h3>📊 Reportes HTML Disponibles:</h3>";
                        foreach ($reports as $report) {
                            $filename = basename($report);
                            $date = filemtime($report);
                            echo "<div style='margin: 10px 0; padding: 10px; background: white; border-radius: 5px;'>";
                            echo "<strong>📄 {$filename}</strong><br>";
                            echo "<small>Generado: " . date('Y-m-d H:i:s', $date) . "</small><br>";
                            echo "<a href='reports/{$filename}' target='_blank' class='btn' style='margin-top: 5px; padding: 5px 15px; font-size: 14px;'>Ver Reporte</a>";
                            echo "</div>";
                        }
                        echo "</div>";
                    } else {
                        echo "<p>📭 No hay reportes disponibles. <a href='?action=run_tests'>Generar nuevo reporte</a></p>";
                    }
                } else {
                    echo "<p>📁 Directorio de reportes no encontrado.</p>";
                }
                ?>
                
                <div class="action-buttons">
                    <a href="?action=run_tests" class="btn btn-success">🧪 Generar Nuevo Reporte</a>
                    <a href="?" class="btn">🔙 Volver al Inicio</a>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-scroll del output de pruebas
        const testOutput = document.getElementById('test-output');
        if (testOutput) {
            testOutput.scrollTop = testOutput.scrollHeight;
        }
        
        // Actualizar cada segundo durante las pruebas
        if (window.location.search.includes('run_tests')) {
            setTimeout(() => {
                if (testOutput) {
                    testOutput.scrollTop = testOutput.scrollHeight;
                }
            }, 1000);
        }
    </script>
</body>
</html>
