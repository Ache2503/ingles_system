<?php
/**
 * Sistema de Pruebas Completo para el Sistema de Inglés
 * Genera reportes detallados en PDF
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

class SystemTester {
    private $results = [];
    private $startTime;
    private $testCount = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    
    public function __construct() {
        $this->startTime = microtime(true);
        $this->initializeSession();
    }
    
    private function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Ejecutar una prueba individual
     */
    public function runTest($testName, $testFunction, $expectedResult = true) {
        $this->testCount++;
        $startTime = microtime(true);
        
        try {
            $result = call_user_func($testFunction);
            $passed = ($result === $expectedResult);
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            if ($passed) {
                $this->passedTests++;
                $status = 'PASS';
                $color = 'green';
            } else {
                $this->failedTests++;
                $status = 'FAIL';
                $color = 'red';
            }
            
            $this->results[] = [
                'test' => $testName,
                'status' => $status,
                'result' => $result,
                'expected' => $expectedResult,
                'duration' => $duration,
                'color' => $color,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            echo "<div style='color: {$color}; margin: 5px 0;'>";
            echo "[{$status}] {$testName} - {$duration}ms";
            if (!$passed) {
                echo " (Expected: " . json_encode($expectedResult) . ", Got: " . json_encode($result) . ")";
            }
            echo "</div>";
            
        } catch (Exception $e) {
            $this->failedTests++;
            $this->results[] = [
                'test' => $testName,
                'status' => 'ERROR',
                'result' => $e->getMessage(),
                'expected' => $expectedResult,
                'duration' => 0,
                'color' => 'orange',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            echo "<div style='color: orange; margin: 5px 0;'>";
            echo "[ERROR] {$testName} - {$e->getMessage()}";
            echo "</div>";
        }
        
        flush();
        ob_flush();
    }
    
    /**
     * Prueba de conectividad a la base de datos
     */
    public function testDatabaseConnection() {
        try {
            // Verificar si la función existe
            if (!function_exists('getDBConnection')) {
                return false;
            }
            
            $pdo = getDBConnection();
            $stmt = $pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Prueba de estructura de archivos
     */
    public function testFileStructure() {
        $requiredFiles = [
            'auth/login.php',
            'auth/register.php',
            'auth/logout.php',
            'pages/topics.php',
            'pages/practice.php',
            'pages/profile.php',
            'api/quiz-result-api.php',
            'includes/config.php',
            'includes/db.php',
            'includes/auth.php'
        ];
        
        $basePath = __DIR__ . '/../';
        foreach ($requiredFiles as $file) {
            if (!file_exists($basePath . $file)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Prueba de tablas de la base de datos
     */
    public function testDatabaseTables() {
        try {
            // Verificar si la función existe
            if (!function_exists('getDBConnection')) {
                return false;
            }
            
            $pdo = getDBConnection();
            $requiredTables = ['users', 'topics', 'verbs', 'user_progress', 'user_gamification'];
            
            foreach ($requiredTables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() === 0) {
                    return false;
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Prueba de funciones de autenticación
     */
    public function testAuthFunctions() {
        if (!function_exists('loginUser') || 
            !function_exists('registerUser') || 
            !function_exists('isUserLoggedIn') || 
            !function_exists('getCurrentUser')) {
            return false;
        }
        return true;
    }
    
    /**
     * Prueba de registro de usuario (usando datos de prueba)
     */
    public function testUserRegistration() {
        // Verificar si las funciones necesarias existen
        if (!function_exists('registerUser') || !function_exists('getDBConnection')) {
            return false;
        }
        
        $testEmail = 'test_' . time() . '@test.com';
        $testPassword = 'Test123!';
        $testName = 'Test User';
        
        try {
            $result = registerUser($testEmail, $testPassword, $testName);
            
            // Limpiar datos de prueba
            if ($result['success']) {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
                $stmt->execute([$testEmail]);
            }
            
            return $result['success'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Prueba de login con credenciales válidas
     */
    public function testUserLogin() {
        // Verificar si las funciones necesarias existen
        if (!function_exists('registerUser') || !function_exists('loginUser') || !function_exists('getDBConnection')) {
            return false;
        }
        
        // Crear usuario de prueba primero
        $testEmail = 'logintest_' . time() . '@test.com';
        $testPassword = 'Test123!';
        $testName = 'Login Test User';
        
        try {
            // Registrar usuario
            $registerResult = registerUser($testEmail, $testPassword, $testName);
            if (!$registerResult['success']) {
                return false;
            }
            
            // Intentar login
            $loginResult = loginUser($testEmail, $testPassword);
            
            // Limpiar datos de prueba
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
            $stmt->execute([$testEmail]);
            
            return $loginResult['success'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Prueba de páginas principales (verificar que no den error 500)
     */
    public function testPageAccessibility() {
        $pages = [
            'auth/login.php',
            'auth/register.php',
            'pages/topics.php'
        ];
        
        $basePath = 'http://localhost/ingles/';
        
        foreach ($pages as $page) {
            $url = $basePath . $page;
            $headers = @get_headers($url);
            
            if (!$headers || strpos($headers[0], '500') !== false) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Prueba de API endpoints
     */
    public function testAPIEndpoints() {
        $endpoints = [
            'api/quiz-result-api.php'
        ];
        
        $basePath = 'http://localhost/ingles/';
        
        foreach ($endpoints as $endpoint) {
            $url = $basePath . $endpoint;
            $headers = @get_headers($url);
            
            if (!$headers || strpos($headers[0], '500') !== false) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Prueba de configuración de sesiones
     */
    public function testSessionConfiguration() {
        return (session_status() !== PHP_SESSION_DISABLED);
    }
    
    /**
     * Prueba de permisos de escritura
     */
    public function testWritePermissions() {
        $testFile = __DIR__ . '/temp_write_test.txt';
        
        try {
            file_put_contents($testFile, 'test');
            $canWrite = file_exists($testFile);
            
            if ($canWrite) {
                unlink($testFile);
            }
            
            return $canWrite;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Ejecutar todas las pruebas
     */
    public function runAllTests() {
        echo "<h2>🧪 Ejecutando Pruebas del Sistema</h2>";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        
        $this->runTest('Conexión a Base de Datos', [$this, 'testDatabaseConnection']);
        $this->runTest('Estructura de Archivos', [$this, 'testFileStructure']);
        $this->runTest('Tablas de Base de Datos', [$this, 'testDatabaseTables']);
        $this->runTest('Funciones de Autenticación', [$this, 'testAuthFunctions']);
        $this->runTest('Registro de Usuario', [$this, 'testUserRegistration']);
        $this->runTest('Login de Usuario', [$this, 'testUserLogin']);
        $this->runTest('Accesibilidad de Páginas', [$this, 'testPageAccessibility']);
        $this->runTest('Endpoints de API', [$this, 'testAPIEndpoints']);
        $this->runTest('Configuración de Sesiones', [$this, 'testSessionConfiguration']);
        $this->runTest('Permisos de Escritura', [$this, 'testWritePermissions']);
        
        echo "</div>";
        
        $this->displaySummary();
    }
    
    /**
     * Mostrar resumen de pruebas
     */
    public function displaySummary() {
        $totalTime = round((microtime(true) - $this->startTime) * 1000, 2);
        $successRate = round(($this->passedTests / $this->testCount) * 100, 1);
        
        echo "<h3>📊 Resumen de Pruebas</h3>";
        echo "<div style='background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Total de Pruebas:</strong> {$this->testCount}<br>";
        echo "<strong style='color: green;'>Exitosas:</strong> {$this->passedTests}<br>";
        echo "<strong style='color: red;'>Fallidas:</strong> {$this->failedTests}<br>";
        echo "<strong>Tasa de Éxito:</strong> {$successRate}%<br>";
        echo "<strong>Tiempo Total:</strong> {$totalTime}ms<br>";
        echo "<strong>Estado General:</strong> " . ($this->failedTests === 0 ? 
            "<span style='color: green; font-weight: bold;'>✅ SISTEMA FUNCIONAL</span>" : 
            "<span style='color: red; font-weight: bold;'>❌ REQUIERE ATENCIÓN</span>") . "<br>";
        echo "</div>";
    }
    
    /**
     * Obtener resultados para generar PDF
     */
    public function getResults() {
        $totalTime = round((microtime(true) - $this->startTime) * 1000, 2);
        $successRate = round(($this->passedTests / $this->testCount) * 100, 1);
        
        return [
            'summary' => [
                'total_tests' => $this->testCount,
                'passed_tests' => $this->passedTests,
                'failed_tests' => $this->failedTests,
                'success_rate' => $successRate,
                'total_time' => $totalTime,
                'status' => $this->failedTests === 0 ? 'FUNCTIONAL' : 'NEEDS_ATTENTION',
                'timestamp' => date('Y-m-d H:i:s')
            ],
            'details' => $this->results
        ];
    }
}
?>
