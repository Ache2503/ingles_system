<?php
/**
 * Generador de Reportes PDF para el Sistema de Pruebas
 * Requiere la librería TCPDF o similar
 */

class PDFReportGenerator {
    private $results;
    private $outputDir;
    
    public function __construct($results, $outputDir = null) {
        $this->results = $results;
        $this->outputDir = $outputDir ?: __DIR__ . '/../temp/reports/';
        
        // Crear directorio si no existe
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    /**
     * Generar reporte en HTML (como alternativa a PDF)
     */
    public function generateHTMLReport() {
        $filename = 'test_report_' . date('Y-m-d_H-i-s') . '.html';
        $filepath = $this->outputDir . $filename;
        
        $html = $this->buildHTMLContent();
        
        file_put_contents($filepath, $html);
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $filepath)
        ];
    }
    
    /**
     * Construir contenido HTML del reporte
     */
    private function buildHTMLContent() {
        $summary = $this->results['summary'];
        $details = $this->results['details'];
        
        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Pruebas del Sistema - ' . $summary['timestamp'] . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
        }
        .summary {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 5px solid #007bff;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .summary-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .summary-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .summary-item .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .status-functional {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-attention {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .tests-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .tests-table th,
        .tests-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .tests-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        .tests-table tr:hover {
            background: #f8f9fa;
        }
        .status-pass {
            color: #28a745;
            font-weight: bold;
        }
        .status-fail {
            color: #dc3545;
            font-weight: bold;
        }
        .status-error {
            color: #fd7e14;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .chart-container {
            margin: 20px 0;
            text-align: center;
        }
        .chart {
            display: inline-block;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            position: relative;
            margin: 20px;
        }
        .chart-success {
            background: conic-gradient(#28a745 0deg ' . ($summary['success_rate'] * 3.6) . 'deg, #e9ecef ' . ($summary['success_rate'] * 3.6) . 'deg 360deg);
        }
        .chart-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            font-weight: bold;
        }
        .print-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px;
        }
        @media print {
            .print-button {
                display: none;
            }
            body {
                background: white;
            }
            .container {
                box-shadow: none;
                margin: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Reporte de Pruebas del Sistema</h1>
            <p><strong>Sistema de Inglés - Análisis Completo</strong></p>
            <p>Generado el: ' . $summary['timestamp'] . '</p>
            <button class="print-button" onclick="window.print()">🖨️ Imprimir Reporte</button>
        </div>
        
        <div class="summary">
            <h2>📊 Resumen Ejecutivo</h2>
            <div class="chart-container">
                <div class="chart chart-success">
                    <div class="chart-center">
                        <div style="font-size: 20px; color: #28a745;">' . $summary['success_rate'] . '%</div>
                        <div style="font-size: 12px; color: #666;">Éxito</div>
                    </div>
                </div>
            </div>
            
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="value">' . $summary['total_tests'] . '</div>
                    <div class="label">Total Pruebas</div>
                </div>
                <div class="summary-item">
                    <div class="value" style="color: #28a745;">' . $summary['passed_tests'] . '</div>
                    <div class="label">Exitosas</div>
                </div>
                <div class="summary-item">
                    <div class="value" style="color: #dc3545;">' . $summary['failed_tests'] . '</div>
                    <div class="label">Fallidas</div>
                </div>
                <div class="summary-item">
                    <div class="value">' . $summary['total_time'] . 'ms</div>
                    <div class="label">Tiempo Total</div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <span class="status-badge ' . ($summary['status'] === 'FUNCTIONAL' ? 'status-functional' : 'status-attention') . '">
                    ' . ($summary['status'] === 'FUNCTIONAL' ? '✅ SISTEMA FUNCIONAL' : '❌ REQUIERE ATENCIÓN') . '
                </span>
            </div>
        </div>
        
        <div>
            <h2>🔍 Detalles de Pruebas</h2>
            <table class="tests-table">
                <thead>
                    <tr>
                        <th>Prueba</th>
                        <th>Estado</th>
                        <th>Tiempo</th>
                        <th>Resultado</th>
                        <th>Hora</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($details as $test) {
            $statusClass = 'status-' . strtolower($test['status']);
            $statusIcon = '';
            switch ($test['status']) {
                case 'PASS':
                    $statusIcon = '✅';
                    break;
                case 'FAIL':
                    $statusIcon = '❌';
                    break;
                case 'ERROR':
                    $statusIcon = '⚠️';
                    break;
            }
            
            $html .= '<tr>
                        <td>' . htmlspecialchars($test['test']) . '</td>
                        <td class="' . $statusClass . '">' . $statusIcon . ' ' . $test['status'] . '</td>
                        <td>' . $test['duration'] . 'ms</td>
                        <td>' . (is_bool($test['result']) ? ($test['result'] ? 'true' : 'false') : htmlspecialchars($test['result'])) . '</td>
                        <td>' . $test['timestamp'] . '</td>
                    </tr>';
        }
        
        $html .= '</tbody>
            </table>
        </div>
        
        <div>
            <h2>📋 Recomendaciones</h2>
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 5px solid #ffc107;">';
        
        if ($summary['failed_tests'] === 0) {
            $html .= '<p><strong>✅ Excelente:</strong> Todas las pruebas han pasado exitosamente.</p>
                     <ul>
                         <li>El sistema está completamente funcional</li>
                         <li>Se recomienda ejecutar estas pruebas regularmente</li>
                         <li>Considera implementar pruebas automatizadas en CI/CD</li>
                     </ul>';
        } else {
            $html .= '<p><strong>⚠️ Atención Requerida:</strong> Se encontraron ' . $summary['failed_tests'] . ' problema(s).</p>
                     <ul>
                         <li>Revisar las pruebas fallidas en detalle</li>
                         <li>Corregir los problemas identificados</li>
                         <li>Volver a ejecutar las pruebas después de las correcciones</li>
                         <li>Verificar logs del servidor para más detalles</li>
                     </ul>';
        }
        
        $html .= '</div>
        </div>
        
        <div class="footer">
            <p>Sistema de Pruebas Automáticas - Generado automáticamente</p>
            <p>Para soporte técnico, revisar la documentación del sistema</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Generar reporte en formato simple de texto para logs
     */
    public function generateTextReport() {
        $filename = 'test_report_' . date('Y-m-d_H-i-s') . '.txt';
        $filepath = $this->outputDir . $filename;
        
        $summary = $this->results['summary'];
        $details = $this->results['details'];
        
        $content = "REPORTE DE PRUEBAS DEL SISTEMA\n";
        $content .= "====================================\n";
        $content .= "Fecha: " . $summary['timestamp'] . "\n";
        $content .= "Total de Pruebas: " . $summary['total_tests'] . "\n";
        $content .= "Exitosas: " . $summary['passed_tests'] . "\n";
        $content .= "Fallidas: " . $summary['failed_tests'] . "\n";
        $content .= "Tasa de Éxito: " . $summary['success_rate'] . "%\n";
        $content .= "Tiempo Total: " . $summary['total_time'] . "ms\n";
        $content .= "Estado: " . ($summary['status'] === 'FUNCTIONAL' ? 'FUNCIONAL' : 'REQUIERE ATENCIÓN') . "\n\n";
        
        $content .= "DETALLES DE PRUEBAS\n";
        $content .= "==================\n";
        
        foreach ($details as $test) {
            $content .= sprintf("%-30s [%s] %sms - %s\n", 
                $test['test'], 
                $test['status'], 
                $test['duration'],
                is_bool($test['result']) ? ($test['result'] ? 'true' : 'false') : $test['result']
            );
        }
        
        file_put_contents($filepath, $content);
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath
        ];
    }
}
?>
