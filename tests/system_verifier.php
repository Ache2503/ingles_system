<?php
/**
 * Sistema de Verificación y Corrección Automática
 * Analiza el sistema y corrige problemas automáticamente
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

class SystemVerifier {
    private $issues = [];
    private $fixes = [];
    private $basePath;
    
    public function __construct() {
        $this->basePath = dirname(__DIR__) . '/';
    }
    
    /**
     * Verificar y corregir toda la estructura del sistema
     */
    public function verifyAndFix() {
        echo "<h2>🔧 Sistema de Verificación y Corrección Automática</h2>";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
        
        $this->checkFileStructure();
        $this->checkDatabaseTables();
        $this->checkFilePermissions();
        $this->checkConfigurationFiles();
        $this->checkURLRedirections();
        $this->checkIncludePaths();
        
        echo "</div>";
        
        $this->displaySummary();
        
        return [
            'issues' => $this->issues,
            'fixes' => $this->fixes,
            'total_issues' => count($this->issues),
            'total_fixes' => count($this->fixes)
        ];
    }
    
    /**
     * Verificar estructura de archivos
     */
    private function checkFileStructure() {
        echo "<h3>📁 Verificando Estructura de Archivos</h3>";
        
        $requiredStructure = [
            'auth/' => [
                'login.php',
                'register.php',
                'logout.php'
            ],
            'pages/' => [
                'topics.php',
                'practice.php',
                'profile.php',
                'progress.php'
            ],
            'api/' => [
                'quiz-result-api.php'
            ],
            'includes/' => [
                'config.php',
                'db.php',
                'auth.php',
                'header.php',
                'footer.php'
            ],
            'admin/' => [
                'index.php'
            ]
        ];
        
        foreach ($requiredStructure as $dir => $files) {
            $dirPath = $this->basePath . $dir;
            
            if (!is_dir($dirPath)) {
                $this->issues[] = "Directorio faltante: {$dir}";
                if (mkdir($dirPath, 0755, true)) {
                    $this->fixes[] = "Creado directorio: {$dir}";
                    echo "<span style='color: green;'>✅ Creado: {$dir}</span><br>";
                }
            } else {
                echo "<span style='color: blue;'>📁 Verificado: {$dir}</span><br>";
            }
            
            foreach ($files as $file) {
                $filePath = $dirPath . $file;
                if (!file_exists($filePath)) {
                    $this->issues[] = "Archivo faltante: {$dir}{$file}";
                    echo "<span style='color: orange;'>⚠️ Faltante: {$dir}{$file}</span><br>";
                } else {
                    echo "<span style='color: green;'>✅ Verificado: {$dir}{$file}</span><br>";
                }
            }
        }
    }
    
    /**
     * Verificar tablas de la base de datos
     */
    private function checkDatabaseTables() {
        echo "<h3>🗄️ Verificando Tablas de Base de Datos</h3>";
        
        try {
            // Verificar si la función getDBConnection existe
            if (!function_exists('getDBConnection')) {
                echo "<span style='color: red;'>❌ Función getDBConnection() no encontrada</span><br>";
                $this->issues[] = "Función getDBConnection() no está disponible";
                return;
            }
            
            $pdo = getDBConnection();
            
            $requiredTables = [
                'users' => [
                    'id', 'name', 'email', 'password', 'role', 'created_at'
                ],
                'topics' => [
                    'id', 'name', 'description', 'difficulty_level'
                ],
                'verbs' => [
                    'id', 'base_form', 'past_simple', 'past_participle', 'translation'
                ],
                'user_progress' => [
                    'id', 'user_id', 'topic_id', 'completed'
                ],
                'user_gamification' => [
                    'user_id', 'points', 'streak', 'last_activity'
                ]
            ];
            
            foreach ($requiredTables as $table => $columns) {
                $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($stmt->rowCount() === 0) {
                    $this->issues[] = "Tabla faltante: {$table}";
                    echo "<span style='color: red;'>❌ Faltante: tabla {$table}</span><br>";
                } else {
                    echo "<span style='color: green;'>✅ Verificada: tabla {$table}</span><br>";
                    
                    // Verificar columnas
                    $stmt = $pdo->query("DESCRIBE {$table}");
                    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                    
                    foreach ($columns as $column) {
                        if (!in_array($column, $existingColumns)) {
                            $this->issues[] = "Columna faltante: {$table}.{$column}";
                            echo "<span style='color: orange;'>⚠️ Columna faltante: {$table}.{$column}</span><br>";
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            $this->issues[] = "Error de conexión a BD: " . $e->getMessage();
            echo "<span style='color: red;'>❌ Error de conexión: {$e->getMessage()}</span><br>";
        }
    }
    
    /**
     * Verificar permisos de archivos
     */
    private function checkFilePermissions() {
        echo "<h3>🔒 Verificando Permisos de Archivos</h3>";
        
        $writableDirs = [
            'temp/',
            'tests/',
            'tests/reports/'
        ];
        
        foreach ($writableDirs as $dir) {
            $dirPath = $this->basePath . $dir;
            
            if (!is_dir($dirPath)) {
                if (mkdir($dirPath, 0755, true)) {
                    $this->fixes[] = "Creado directorio escribible: {$dir}";
                    echo "<span style='color: green;'>✅ Creado: {$dir}</span><br>";
                }
            }
            
            if (is_writable($dirPath)) {
                echo "<span style='color: green;'>✅ Escribible: {$dir}</span><br>";
            } else {
                $this->issues[] = "Directorio no escribible: {$dir}";
                echo "<span style='color: red;'>❌ No escribible: {$dir}</span><br>";
            }
        }
    }
    
    /**
     * Verificar archivos de configuración
     */
    private function checkConfigurationFiles() {
        echo "<h3>⚙️ Verificando Configuración</h3>";
        
        $configFile = $this->basePath . 'includes/config.php';
        
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            
            $requiredConstants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
            
            foreach ($requiredConstants as $constant) {
                if (strpos($content, $constant) !== false) {
                    echo "<span style='color: green;'>✅ Definido: {$constant}</span><br>";
                } else {
                    $this->issues[] = "Constante faltante en config.php: {$constant}";
                    echo "<span style='color: red;'>❌ Faltante: {$constant}</span><br>";
                }
            }
        } else {
            $this->issues[] = "Archivo config.php no encontrado";
            echo "<span style='color: red;'>❌ config.php no encontrado</span><br>";
        }
    }
    
    /**
     * Verificar y corregir redirecciones URL
     */
    private function checkURLRedirections() {
        echo "<h3>🔗 Verificando Redirecciones URL</h3>";
        
        $filesToCheck = [
            'auth/login.php',
            'auth/register.php',
            'auth/logout.php'
        ];
        
        foreach ($filesToCheck as $file) {
            $filePath = $this->basePath . $file;
            
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                
                // Buscar redirecciones problemáticas
                $problematicPatterns = [
                    '/Location:\s*\/ingles\/login\.php/' => 'Location: /ingles/auth/login.php',
                    '/Location:\s*\/ingles\/register\.php/' => 'Location: /ingles/auth/register.php',
                    '/Location:\s*\/ingles\/topics\.php/' => 'Location: /ingles/pages/topics.php'
                ];
                
                $hasChanges = false;
                foreach ($problematicPatterns as $pattern => $replacement) {
                    if (preg_match($pattern, $content)) {
                        $content = preg_replace($pattern, $replacement, $content);
                        $hasChanges = true;
                        $this->fixes[] = "Corregida redirección en {$file}";
                    }
                }
                
                if ($hasChanges) {
                    file_put_contents($filePath, $content);
                    echo "<span style='color: green;'>✅ Corregidas redirecciones en: {$file}</span><br>";
                } else {
                    echo "<span style='color: blue;'>📁 Verificado: {$file}</span><br>";
                }
            }
        }
    }
    
    /**
     * Verificar rutas de includes
     */
    private function checkIncludePaths() {
        echo "<h3>📋 Verificando Rutas de Includes</h3>";
        
        $filesToCheck = glob($this->basePath . '*/*.php');
        
        foreach ($filesToCheck as $filePath) {
            $content = file_get_contents($filePath);
            $relativePath = str_replace($this->basePath, '', $filePath);
            
            // Verificar includes problemáticos
            if (preg_match('/require_once.*includes\//', $content)) {
                $includeCount = preg_match_all('/require_once.*includes\//', $content);
                echo "<span style='color: green;'>✅ Includes verificados en: {$relativePath}</span><br>";
            } else if (preg_match('/require_once/', $content)) {
                echo "<span style='color: orange;'>⚠️ Verificar includes en: {$relativePath}</span><br>";
            }
        }
    }
    
    /**
     * Mostrar resumen de verificación
     */
    private function displaySummary() {
        echo "<h3>📊 Resumen de Verificación</h3>";
        echo "<div style='background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Total de Problemas Encontrados:</strong> " . count($this->issues) . "<br>";
        echo "<strong>Total de Correcciones Aplicadas:</strong> " . count($this->fixes) . "<br>";
        
        if (count($this->issues) === 0) {
            echo "<strong style='color: green;'>✅ SISTEMA EN PERFECTO ESTADO</strong><br>";
        } else {
            echo "<strong style='color: orange;'>⚠️ REQUIERE ATENCIÓN</strong><br>";
        }
        echo "</div>";
        
        if (!empty($this->issues)) {
            echo "<h4>❌ Problemas Encontrados:</h4>";
            echo "<ul>";
            foreach ($this->issues as $issue) {
                echo "<li style='color: red;'>{$issue}</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($this->fixes)) {
            echo "<h4>✅ Correcciones Aplicadas:</h4>";
            echo "<ul>";
            foreach ($this->fixes as $fix) {
                echo "<li style='color: green;'>{$fix}</li>";
            }
            echo "</ul>";
        }
    }
}

// Ejecutar si se accede directamente
if (basename($_SERVER['PHP_SELF']) === 'system_verifier.php') {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verificador del Sistema</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
            h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
            h3 { color: #495057; margin-top: 25px; }
        </style>
    </head>
    <body>
        <div class="container">
            <?php
            $verifier = new SystemVerifier();
            $results = $verifier->verifyAndFix();
            ?>
            
            <div style="margin-top: 30px; text-align: center;">
                <a href="comprehensive_test.php" style="display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">
                    🧪 Ejecutar Pruebas Completas
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
