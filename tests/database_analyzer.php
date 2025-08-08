<?php
/**
 * Analizador Completo del Sistema - Base de Datos
 * Identifica tablas y columnas faltantes, analiza inconsistencias
 */

require_once __DIR__ . '/../includes/db.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>🔍 Análisis Completo del Sistema</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .analysis-section {
            margin: 30px 0;
            padding: 20px;
            border-left: 5px solid #3498db;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .missing { background: #fff3cd; border-left-color: #ffc107; }
        .found { background: #d4f6d4; border-left-color: #28a745; }
        .error { background: #f8d7da; border-left-color: #dc3545; }
        .table-info {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .column-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
            margin: 10px 0;
        }
        .column-item {
            padding: 8px 12px;
            background: #e9ecef;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        .missing-column { background: #ffe6e6; color: #721c24; }
        .found-column { background: #e6ffe6; color: #155724; }
        h1, h2, h3 { color: #2c3e50; }
        .report-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3498db;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .recommendations {
            background: #e8f4f8;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
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
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='report-header'>
            <h1>🔍 Análisis Completo del Sistema</h1>
            <p>Identificación de tablas y columnas faltantes • Reporte generado: " . date('Y-m-d H:i:s') . "</p>
        </div>";

// Obtener lista de todas las tablas actuales
try {
    $tablesStmt = $pdo->query("SHOW TABLES");
    $currentTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='analysis-section found'>
            <h2>✅ Tablas Existentes en la Base de Datos</h2>
            <div class='column-list'>";
    
    foreach ($currentTables as $table) {
        echo "<div class='found-column'>$table</div>";
    }
    
    echo "  </div>
          </div>";
    
    // Definir todas las tablas que DEBERÍAN existir según el análisis del código
    $expectedTables = [
        // Tablas básicas del sistema
        'users' => [
            'user_id', 'username', 'email', 'password_hash', 'role', 'created_at', 'updated_at',
            'avatar_url', 'bio', 'timezone', 'last_login' // Columnas adicionales encontradas en el código
        ],
        'topics' => [
            'topic_id', 'title', 'description', 'category', 'detailed_content', 'created_at', 'updated_at',
            'difficulty_level', 'estimated_time', 'is_featured', 'views_count', 'last_viewed' // Mejoras encontradas
        ],
        'questions' => [
            'question_id', 'topic_id', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d',
            'correct_answer', 'difficulty', 'explanation', 'created_at',
            'points_value', 'time_limit_seconds', 'question_type' // Columnas adicionales
        ],
        'irregular_verbs' => [
            'verb_id', 'base_form', 'past_simple', 'past_participle', 'meaning', 'example', 'created_at'
        ],
        'user_progress' => [
            'progress_id', 'user_id', 'topic_id', 'score', 'mastery_level', 'last_reviewed', 
            'attempt_date', 'created_at', 'updated_at'
        ],
        'quiz_history' => [
            'history_id', 'user_id', 'topic_id', 'score', 'attempt_date', 'duration'
        ],
        'user_answers' => [
            'answer_id', 'user_id', 'question_id', 'user_answer', 'is_correct', 'similarity',
            'answered_at', 'attempt_id'
        ],
        
        // Tablas de gamificación y logros
        'user_gamification' => [
            'user_id', 'total_points', 'current_level', 'experience_points', 'study_streak',
            'longest_streak', 'last_activity_date', 'total_study_time', 'favorite_topic_id',
            'created_at', 'updated_at', 'study_streak_date'
        ],
        'user_achievements' => [
            'achievement_id', 'user_id', 'achievement_type', 'achievement_name', 
            'achievement_description', 'points_earned', 'earned_at'
        ],
        'achievement_config' => [
            'config_id', 'achievement_type', 'name', 'description', 'icon', 'points_reward',
            'condition_value', 'is_active'
        ],
        
        // Tablas de notificaciones y configuración
        'notifications' => [
            'notification_id', 'user_id', 'type', 'title', 'message', 'is_read', 'created_at'
        ],
        'user_settings' => [
            'user_id', 'notifications_enabled', 'email_reminders', 'study_reminder_time',
            'preferred_language', 'theme', 'difficulty_preference', 'created_at', 'updated_at'
        ],
        
        // Tablas de favoritos y navegación
        'user_bookmarks' => [
            'id', 'user_id', 'content_type', 'content_id', 'notes', 'created_at'
        ],
        'user_navigation_history' => [
            'id', 'user_id', 'page_type', 'content_id', 'page_title', 'visit_time', 'duration_seconds'
        ],
        'user_configuration' => [
            'id', 'user_id', 'config_key', 'config_value', 'updated_at'
        ],
        
        // Tablas de contenido avanzado
        'content_categories' => [
            'category_id', 'name', 'description', 'color', 'icon', 'parent_category_id',
            'sort_order', 'is_active'
        ],
        'tags' => [
            'tag_id', 'name', 'color', 'created_at'
        ],
        'topic_tags' => [
            'topic_id', 'tag_id'
        ],
        'media_resources' => [
            'resource_id', 'topic_id', 'question_id', 'type', 'filename', 'original_name',
            'file_path', 'file_size', 'mime_type', 'uploaded_by', 'created_at'
        ],
        'study_sessions' => [
            'session_id', 'user_id', 'topic_id', 'start_time', 'end_time', 'duration_minutes',
            'questions_answered', 'correct_answers', 'session_type'
        ]
    ];
    
    // Analizar tablas faltantes
    $missingTables = array_diff(array_keys($expectedTables), $currentTables);
    $existingTables = array_intersect(array_keys($expectedTables), $currentTables);
    
    if (!empty($missingTables)) {
        echo "<div class='analysis-section missing'>
                <h2>⚠️ Tablas Faltantes</h2>
                <div class='column-list'>";
        
        foreach ($missingTables as $table) {
            echo "<div class='missing-column'>$table</div>";
        }
        
        echo "  </div>
              </div>";
    }
    
    // Analizar columnas faltantes en tablas existentes
    $missingColumns = [];
    $totalMissingColumns = 0;
    
    foreach ($existingTables as $table) {
        $columnsStmt = $pdo->query("DESCRIBE $table");
        $currentColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $expectedColumns = $expectedTables[$table];
        $missing = array_diff($expectedColumns, $currentColumns);
        $existing = array_intersect($expectedColumns, $currentColumns);
        
        if (!empty($missing) || !empty($existing)) {
            $missingColumns[$table] = [
                'missing' => $missing,
                'existing' => $existing,
                'total_expected' => count($expectedColumns),
                'total_current' => count($currentColumns)
            ];
            $totalMissingColumns += count($missing);
        }
    }
    
    // Estadísticas generales
    echo "<div class='stats-grid'>
            <div class='stat-card'>
                <h3>" . count($currentTables) . "</h3>
                <p>Tablas Existentes</p>
            </div>
            <div class='stat-card'>
                <h3>" . count($missingTables) . "</h3>
                <p>Tablas Faltantes</p>
            </div>
            <div class='stat-card'>
                <h3>$totalMissingColumns</h3>
                <p>Columnas Faltantes</p>
            </div>
            <div class='stat-card'>
                <h3>" . count($expectedTables) . "</h3>
                <p>Tablas Esperadas</p>
            </div>
          </div>";
    
    // Detalles de columnas por tabla
    foreach ($missingColumns as $table => $info) {
        $statusClass = empty($info['missing']) ? 'found' : 'missing';
        
        echo "<div class='analysis-section $statusClass'>
                <h3>📋 Tabla: $table</h3>
                <p>Columnas esperadas: {$info['total_expected']} | Columnas actuales: {$info['total_current']}</p>";
        
        if (!empty($info['existing'])) {
            echo "<h4>✅ Columnas Existentes</h4>
                  <div class='column-list'>";
            foreach ($info['existing'] as $col) {
                echo "<div class='found-column'>$col</div>";
            }
            echo "</div>";
        }
        
        if (!empty($info['missing'])) {
            echo "<h4>❌ Columnas Faltantes</h4>
                  <div class='column-list'>";
            foreach ($info['missing'] as $col) {
                echo "<div class='missing-column'>$col</div>";
            }
            echo "</div>";
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='analysis-section error'>
            <h2>❌ Error de Conexión</h2>
            <p>{$e->getMessage()}</p>
          </div>";
}

// Generar recomendaciones
echo "<div class='recommendations'>
        <h2>💡 Recomendaciones de Actualización</h2>
        <ol>
            <li><strong>Prioridad Alta:</strong> Crear tablas de gamificación (user_gamification, user_achievements)</li>
            <li><strong>Prioridad Alta:</strong> Implementar sistema de notificaciones</li>
            <li><strong>Prioridad Media:</strong> Añadir sistema de favoritos y configuración de usuario</li>
            <li><strong>Prioridad Media:</strong> Mejorar tablas existentes con columnas adicionales</li>
            <li><strong>Prioridad Baja:</strong> Implementar sistema de etiquetas y recursos multimedia</li>
        </ol>
      </div>";

echo "<div style='text-align: center; margin-top: 30px;'>
        <h3>🔧 Acciones Disponibles</h3>
        <a href='database_updater.php' class='btn btn-primary'>📊 Actualizar Base de Datos</a>
        <a href='generate_migration.php' class='btn btn-success'>📝 Generar Migración</a>
        <a href='backup_database.php' class='btn btn-warning'>💾 Backup de BD</a>
        <a href='../index.php' class='btn btn-danger'>🏠 Ir al Sistema</a>
      </div>";

echo "    </div>
</body>
</html>";
?>
