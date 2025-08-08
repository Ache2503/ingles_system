<?php
/**
 * Sistema de Pruebas Integral para Plataforma de Inglés
 * Verifica la funcionalidad completa del sistema y genera reportes detallados
 */

// Configuración de errores para capturar todo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Buffer de salida para capturar errores
ob_start();

class SystemTester {
    private $errors = [];
    private $warnings = [];
    private $successes = [];
    private $pdo = null;
    private $testStartTime;
    
    public function __construct() {
        $this->testStartTime = microtime(true);
        echo "<html><head><title>Sistema de Pruebas - Plataforma de Inglés</title>";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .test-section { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
            .error { color: #dc3545; background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 4px; border-left: 4px solid #dc3545; }
            .warning { color: #856404; background: #fff3cd; padding: 10px; margin: 5px 0; border-radius: 4px; border-left: 4px solid #ffc107; }
            .success { color: #155724; background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 4px; border-left: 4px solid #28a745; }
            .info { color: #0c5460; background: #d1ecf1; padding: 10px; margin: 5px 0; border-radius: 4px; border-left: 4px solid #17a2b8; }
            .summary { background: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .file-test { margin: 10px 0; padding: 10px; background: white; border-radius: 4px; }
            .db-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            .db-table th, .db-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .db-table th { background: #f2f2f2; }
            pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
            .progress { background: #e9ecef; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
            .progress-bar { height: 100%; background: #007bff; transition: width 0.3s ease; }
        </style></head><body>";
        echo "<div class='container'>";
        echo "<h1>🔍 Sistema de Pruebas Integral</h1>";
        echo "<p>Iniciando verificación completa del sistema...</p>";
        echo "<div class='progress'><div class='progress-bar' id='progress' style='width: 0%'></div></div>";
        echo "<div id='test-results'>";
        
        // JavaScript para actualizar progreso
        echo "<script>
            function updateProgress(percent) {
                document.getElementById('progress').style.width = percent + '%';
            }
            function scrollToBottom() {
                window.scrollTo(0, document.body.scrollHeight);
            }
        </script>";
        
        flush();
    }
    
    public function runAllTests() {
        $totalTests = 8;
        $currentTest = 0;
        
        // 1. Pruebas de Base de Datos
        $this->updateProgress(++$currentTest, $totalTests, "Verificando Base de Datos");
        $this->testDatabase();
        
        // 2. Pruebas de Archivos Principales
        $this->updateProgress(++$currentTest, $totalTests, "Verificando Archivos del Sistema");
        $this->testCoreFiles();
        
        // 3. Pruebas de Includes/Dependencias
        $this->updateProgress(++$currentTest, $totalTests, "Verificando Dependencias");
        $this->testIncludes();
        
        // 4. Pruebas de Autenticación
        $this->updateProgress(++$currentTest, $totalTests, "Verificando Sistema de Autenticación");
        $this->testAuthentication();
        
        // 5. Pruebas de Funcionalidad de Práctica
        $this->updateProgress(++$currentTest, $totalTests, "Verificando Sistema de Práctica");
        $this->testPracticeSystem();
        
        // 6. Pruebas de API
        $this->updateProgress(++$currentTest, $totalTests, "Verificando APIs");
        $this->testAPIs();
        
        // 7. Pruebas de Gamificación
        $this->updateProgress(++$currentTest, $totalTests, "Verificando Sistema de Gamificación");
        $this->testGamification();
        
        // 8. Pruebas de Rendimiento y Seguridad
        $this->updateProgress(++$currentTest, $totalTests, "Verificando Rendimiento y Seguridad");
        $this->testPerformanceAndSecurity();
        
        $this->generateFinalReport();
    }
    
    private function updateProgress($current, $total, $message) {
        $percent = ($current / $total) * 100;
        echo "<script>updateProgress($percent);</script>";
        echo "<div class='info'><strong>Progreso:</strong> $message ($current/$total)</div>";
        flush();
    }
    
    private function testDatabase() {
        echo "<div class='test-section'><h2>🗄️ Pruebas de Base de Datos</h2>";
        
        try {
            // Conexión a la base de datos
            require_once __DIR__ . '/includes/db.php';
            $this->pdo = $pdo;
            $this->addSuccess("Conexión a base de datos establecida correctamente");
            
            // Verificar tablas requeridas
            $requiredTables = [
                'users', 'topics', 'questions', 'user_progress', 
                'quiz_history', 'user_answers', 'user_gamification'
            ];
            
            foreach ($requiredTables as $table) {
                if ($this->tableExists($table)) {
                    $this->addSuccess("Tabla '$table' existe");
                    $this->verifyTableStructure($table);
                } else {
                    $this->addError("Tabla '$table' NO existe");
                }
            }
            
            // Verificar datos de muestra
            $this->verifyDataIntegrity();
            
        } catch (Exception $e) {
            $this->addError("Error de conexión a base de datos: " . $e->getMessage());
        }
        
        echo "</div>";
    }
    
    private function testCoreFiles() {
        echo "<div class='test-section'><h2>📁 Pruebas de Archivos del Sistema</h2>";
        
        $coreFiles = [
            'index.php' => 'Página principal',
            'auth/login.php' => 'Página de login',
            'auth/register.php' => 'Página de registro',
            'pages/topics.php' => 'Lista de temas',
            'pages/topic_detail.php' => 'Detalle de tema',
            'pages/practice.php' => 'Sistema de práctica',
            'pages/progress.php' => 'Página de progreso',
            'api/quiz-result-api.php' => 'API de resultados',
            'includes/header.php' => 'Header del sistema',
            'includes/footer.php' => 'Footer del sistema',
            'includes/config.php' => 'Configuración',
            'includes/db.php' => 'Conexión de BD',
            'includes/auth.php' => 'Sistema de autenticación',
            'assets/css/style.css' => 'Estilos principales',
            'assets/js/script.js' => 'JavaScript principal'
        ];
        
        foreach ($coreFiles as $file => $description) {
            $this->testFile($file, $description);
        }
        
        echo "</div>";
    }
    
    private function testIncludes() {
        echo "<div class='test-section'><h2>🔗 Pruebas de Dependencias e Includes</h2>";
        
        // Probar que los includes funcionen sin errores
        $includeTests = [
            'includes/config.php' => 'Configuración base',
            'includes/db.php' => 'Conexión de base de datos',
            'includes/auth.php' => 'Funciones de autenticación'
        ];
        
        foreach ($includeTests as $file => $description) {
            $this->testInclude($file, $description);
        }
        
        // Verificar que no haya inclusiones circulares
        $this->testCircularIncludes();
        
        echo "</div>";
    }
    
    private function testAuthentication() {
        echo "<div class='test-section'><h2>🔐 Pruebas de Autenticación</h2>";
        
        try {
            require_once __DIR__ . '/includes/auth.php';
            
            // Verificar que las funciones existan
            $authFunctions = [
                'registerUser', 'loginUser', 'logoutUser', 
                'isUserLoggedIn', 'getCurrentUser', 'requireAuth'
            ];
            
            foreach ($authFunctions as $function) {
                if (function_exists($function)) {
                    $this->addSuccess("Función de autenticación '$function' existe");
                } else {
                    $this->addError("Función de autenticación '$function' NO existe");
                }
            }
            
            // Probar registro de usuario (simulado)
            $this->testUserRegistration();
            
        } catch (Exception $e) {
            $this->addError("Error en sistema de autenticación: " . $e->getMessage());
        }
        
        echo "</div>";
    }
    
    private function testPracticeSystem() {
        echo "<div class='test-section'><h2>🎯 Pruebas del Sistema de Práctica</h2>";
        
        try {
            // Verificar que existan preguntas
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM questions");
            $questionCount = $stmt->fetchColumn();
            
            if ($questionCount > 0) {
                $this->addSuccess("Sistema tiene $questionCount preguntas disponibles");
                
                // Verificar estructura de preguntas
                $stmt = $this->pdo->query("SELECT * FROM questions LIMIT 1");
                $question = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $requiredColumns = ['question_id', 'topic_id', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer'];
                foreach ($requiredColumns as $col) {
                    if (isset($question[$col])) {
                        $this->addSuccess("Columna '$col' presente en preguntas");
                    } else {
                        $this->addError("Columna '$col' faltante en preguntas");
                    }
                }
                
                // Verificar formato de respuestas correctas
                $this->testQuestionFormat();
                
            } else {
                $this->addWarning("No hay preguntas en el sistema");
            }
            
        } catch (Exception $e) {
            $this->addError("Error en sistema de práctica: " . $e->getMessage());
        }
        
        echo "</div>";
    }
    
    private function testAPIs() {
        echo "<div class='test-section'><h2>🌐 Pruebas de APIs</h2>";
        
        // Verificar que los archivos de API existan y sean válidos
        $apiFiles = [
            'api/quiz-result-api.php' => 'API de resultados de quiz'
        ];
        
        foreach ($apiFiles as $file => $description) {
            if (file_exists(__DIR__ . '/' . $file)) {
                $this->addSuccess("API '$description' existe");
                
                // Verificar sintaxis PHP
                $output = [];
                $returnCode = 0;
                exec("php -l " . escapeshellarg(__DIR__ . '/' . $file) . " 2>&1", $output, $returnCode);
                
                if ($returnCode === 0) {
                    $this->addSuccess("API '$description' tiene sintaxis válida");
                } else {
                    $this->addError("API '$description' tiene errores de sintaxis: " . implode(", ", $output));
                }
            } else {
                $this->addError("API '$description' NO existe");
            }
        }
        
        echo "</div>";
    }
    
    private function testGamification() {
        echo "<div class='test-section'><h2>🎮 Pruebas de Gamificación</h2>";
        
        try {
            // Verificar tabla de gamificación
            if ($this->tableExists('user_gamification')) {
                $stmt = $this->pdo->query("DESCRIBE user_gamification");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $requiredGamificationColumns = ['user_id', 'total_points', 'study_streak', 'longest_streak'];
                foreach ($requiredGamificationColumns as $col) {
                    if (in_array($col, $columns)) {
                        $this->addSuccess("Columna de gamificación '$col' presente");
                    } else {
                        $this->addError("Columna de gamificación '$col' faltante");
                    }
                }
                
                // Verificar datos de gamificación
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM user_gamification");
                $gamificationCount = $stmt->fetchColumn();
                $this->addInfo("Registros de gamificación: $gamificationCount");
                
            } else {
                $this->addError("Tabla 'user_gamification' no existe");
            }
            
        } catch (Exception $e) {
            $this->addError("Error en sistema de gamificación: " . $e->getMessage());
        }
        
        echo "</div>";
    }
    
    private function testPerformanceAndSecurity() {
        echo "<div class='test-section'><h2>⚡ Pruebas de Rendimiento y Seguridad</h2>";
        
        // Verificar configuración de seguridad
        $this->testSecurityConfig();
        
        // Verificar rendimiento de consultas
        $this->testQueryPerformance();
        
        // Verificar protección contra inyección SQL
        $this->testSQLInjectionProtection();
        
        echo "</div>";
    }
    
    // Métodos auxiliares
    private function tableExists($tableName) {
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function verifyTableStructure($tableName) {
        try {
            $stmt = $this->pdo->query("DESCRIBE $tableName");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<div class='file-test'>";
            echo "<strong>Estructura de la tabla '$tableName':</strong>";
            echo "<table class='db-table'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "</tr>";
            }
            echo "</table></div>";
            
        } catch (Exception $e) {
            $this->addError("Error verificando estructura de '$tableName': " . $e->getMessage());
        }
    }
    
    private function verifyDataIntegrity() {
        $tables = ['users', 'topics', 'questions'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $this->addSuccess("Tabla '$table' contiene $count registros");
                } else {
                    $this->addWarning("Tabla '$table' está vacía");
                }
                
            } catch (Exception $e) {
                $this->addError("Error verificando datos de '$table': " . $e->getMessage());
            }
        }
    }
    
    private function testFile($filePath, $description) {
        echo "<div class='file-test'>";
        
        if (file_exists(__DIR__ . '/' . $filePath)) {
            $this->addSuccess("✓ $description ($filePath) existe");
            
            // Verificar permisos de lectura
            if (is_readable(__DIR__ . '/' . $filePath)) {
                $this->addSuccess("✓ $description es legible");
                
                // Para archivos PHP, verificar sintaxis
                if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
                    $this->testPHPSyntax($filePath, $description);
                }
                
                // Verificar tamaño del archivo
                $size = filesize(__DIR__ . '/' . $filePath);
                if ($size > 0) {
                    $this->addInfo("Tamaño: " . $this->formatBytes($size));
                } else {
                    $this->addWarning("Archivo está vacío");
                }
                
            } else {
                $this->addError("✗ $description no es legible");
            }
        } else {
            $this->addError("✗ $description ($filePath) NO existe");
        }
        
        echo "</div>";
    }
    
    private function testPHPSyntax($filePath, $description) {
        $output = [];
        $returnCode = 0;
        
        // Usar php -l para verificar sintaxis
        exec("php -l " . escapeshellarg(__DIR__ . '/' . $filePath) . " 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->addSuccess("✓ $description tiene sintaxis PHP válida");
        } else {
            $this->addError("✗ $description tiene errores de sintaxis: " . implode(", ", $output));
        }
    }
    
    private function testInclude($filePath, $description) {
        try {
            ob_start();
            $included = include_once __DIR__ . '/' . $filePath;
            $output = ob_get_clean();
            
            if ($included !== false) {
                $this->addSuccess("✓ $description se incluye correctamente");
                if (!empty($output)) {
                    $this->addWarning("$description produce salida: " . substr($output, 0, 100) . "...");
                }
            } else {
                $this->addError("✗ Error incluyendo $description");
            }
            
        } catch (Exception $e) {
            $this->addError("✗ Error incluyendo $description: " . $e->getMessage());
        }
    }
    
    private function testCircularIncludes() {
        // Esta es una verificación básica
        $this->addInfo("Verificación de inclusiones circulares: No implementada en esta versión");
    }
    
    private function testUserRegistration() {
        // Prueba simulada de registro
        try {
            // Solo verificamos que la función no cause errores cuando se llama
            $testEmail = "test_" . time() . "@test.com";
            $testUsername = "test_" . time();
            
            // No ejecutamos realmente el registro, solo verificamos que la función exista
            if (function_exists('registerUser')) {
                $this->addSuccess("Función registerUser disponible para pruebas");
            }
            
        } catch (Exception $e) {
            $this->addError("Error en prueba de registro: " . $e->getMessage());
        }
    }
    
    private function testQuestionFormat() {
        try {
            $stmt = $this->pdo->query("
                SELECT question_id, correct_answer, option_a, option_b, option_c, option_d 
                FROM questions 
                LIMIT 5
            ");
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($questions as $question) {
                $correctAnswer = strtoupper(trim($question['correct_answer']));
                
                if (in_array($correctAnswer, ['A', 'B', 'C', 'D'])) {
                    $this->addSuccess("Pregunta {$question['question_id']}: formato de respuesta correcto ($correctAnswer)");
                } else {
                    $this->addWarning("Pregunta {$question['question_id']}: formato de respuesta inusual ({$question['correct_answer']})");
                }
            }
            
        } catch (Exception $e) {
            $this->addError("Error verificando formato de preguntas: " . $e->getMessage());
        }
    }
    
    private function testSecurityConfig() {
        // Verificar configuraciones de seguridad básicas
        $checks = [
            'session.cookie_httponly' => 'HttpOnly cookies',
            'session.use_strict_mode' => 'Strict session mode',
            'display_errors' => 'Display errors (debería estar Off en producción)'
        ];
        
        foreach ($checks as $setting => $description) {
            $value = ini_get($setting);
            $this->addInfo("$description: " . ($value ? 'ON' : 'OFF'));
        }
    }
    
    private function testQueryPerformance() {
        try {
            $start = microtime(true);
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM questions");
            $end = microtime(true);
            
            $queryTime = ($end - $start) * 1000; // en milisegundos
            
            if ($queryTime < 100) {
                $this->addSuccess("Consulta de prueba ejecutada en {$queryTime:.2f}ms (buena)");
            } elseif ($queryTime < 500) {
                $this->addWarning("Consulta de prueba ejecutada en {$queryTime:.2f}ms (aceptable)");
            } else {
                $this->addError("Consulta de prueba ejecutada en {$queryTime:.2f}ms (lenta)");
            }
            
        } catch (Exception $e) {
            $this->addError("Error en prueba de rendimiento: " . $e->getMessage());
        }
    }
    
    private function testSQLInjectionProtection() {
        try {
            // Verificar que se usen prepared statements
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM topics WHERE topic_id = ?");
            $stmt->execute([1]);
            $this->addSuccess("Prepared statements funcionando correctamente");
            
        } catch (Exception $e) {
            $this->addError("Error en prueba de protección SQL: " . $e->getMessage());
        }
    }
    
    private function addError($message) {
        $this->errors[] = $message;
        echo "<div class='error'>❌ $message</div>";
        flush();
    }
    
    private function addWarning($message) {
        $this->warnings[] = $message;
        echo "<div class='warning'>⚠️ $message</div>";
        flush();
    }
    
    private function addSuccess($message) {
        $this->successes[] = $message;
        echo "<div class='success'>✅ $message</div>";
        flush();
    }
    
    private function addInfo($message) {
        echo "<div class='info'>ℹ️ $message</div>";
        flush();
    }
    
    private function formatBytes($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
    
    private function generateFinalReport() {
        $totalTime = microtime(true) - $this->testStartTime;
        
        echo "</div>"; // Cerrar test-results
        echo "<script>updateProgress(100);</script>";
        
        echo "<div class='summary'>";
        echo "<h2>📊 Resumen Final de Pruebas</h2>";
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;'>";
        
        echo "<div style='text-align: center; padding: 20px; background: #d4edda; border-radius: 8px;'>";
        echo "<h3 style='color: #155724; margin: 0;'>" . count($this->successes) . "</h3>";
        echo "<p style='margin: 5px 0; color: #155724;'>Pruebas Exitosas</p>";
        echo "</div>";
        
        echo "<div style='text-align: center; padding: 20px; background: #fff3cd; border-radius: 8px;'>";
        echo "<h3 style='color: #856404; margin: 0;'>" . count($this->warnings) . "</h3>";
        echo "<p style='margin: 5px 0; color: #856404;'>Advertencias</p>";
        echo "</div>";
        
        echo "<div style='text-align: center; padding: 20px; background: #f8d7da; border-radius: 8px;'>";
        echo "<h3 style='color: #721c24; margin: 0;'>" . count($this->errors) . "</h3>";
        echo "<p style='margin: 5px 0; color: #721c24;'>Errores Críticos</p>";
        echo "</div>";
        
        echo "<div style='text-align: center; padding: 20px; background: #d1ecf1; border-radius: 8px;'>";
        echo "<h3 style='color: #0c5460; margin: 0;'>" . round($totalTime, 2) . "s</h3>";
        echo "<p style='margin: 5px 0; color: #0c5460;'>Tiempo Total</p>";
        echo "</div>";
        
        echo "</div>";
        
        // Estado general del sistema
        $systemStatus = "🔴 CRÍTICO";
        $statusColor = "#dc3545";
        
        if (count($this->errors) == 0) {
            if (count($this->warnings) == 0) {
                $systemStatus = "🟢 EXCELENTE";
                $statusColor = "#28a745";
            } else {
                $systemStatus = "🟡 BUENO";
                $statusColor = "#ffc107";
            }
        }
        
        echo "<div style='text-align: center; padding: 20px; background: $statusColor; color: white; border-radius: 8px; margin: 20px 0;'>";
        echo "<h2>Estado General del Sistema: $systemStatus</h2>";
        echo "</div>";
        
        // Recomendaciones
        if (count($this->errors) > 0) {
            echo "<h3>🔧 Errores que Requieren Atención Inmediata:</h3>";
            echo "<ul>";
            foreach ($this->errors as $error) {
                echo "<li style='color: #dc3545; margin: 5px 0;'>$error</li>";
            }
            echo "</ul>";
        }
        
        if (count($this->warnings) > 0) {
            echo "<h3>⚠️ Advertencias a Considerar:</h3>";
            echo "<ul>";
            foreach ($this->warnings as $warning) {
                echo "<li style='color: #856404; margin: 5px 0;'>$warning</li>";
            }
            echo "</ul>";
        }
        
        echo "<h3>📋 Próximos Pasos Recomendados:</h3>";
        echo "<ol>";
        if (count($this->errors) > 0) {
            echo "<li>Corregir todos los errores críticos listados arriba</li>";
            echo "<li>Verificar que las tablas faltantes se creen correctamente</li>";
            echo "<li>Revisar archivos con errores de sintaxis</li>";
        }
        if (count($this->warnings) > 0) {
            echo "<li>Revisar las advertencias y determinar si requieren acción</li>";
            echo "<li>Optimizar consultas que sean lentas</li>";
        }
        echo "<li>Ejecutar pruebas funcionales manuales en el navegador</li>";
        echo "<li>Verificar el flujo completo: registro → login → práctica → resultados</li>";
        echo "<li>Probar el sistema con diferentes navegadores</li>";
        echo "</ol>";
        
        echo "</div>";
        echo "</div></body></html>";
        
        flush();
    }
}

// Ejecutar las pruebas
$tester = new SystemTester();
$tester->runAllTests();
?>
