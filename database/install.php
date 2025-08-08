<?php
/**
 * Script simplificado para crear la base de datos
 */

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ingles_system';

try {
    echo "Conectando a MySQL...\n";
    
    // Conectar sin especificar base de datos
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear base de datos
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Base de datos creada\n";
    
    // Usar la base de datos
    $pdo->exec("USE $database");
    
    // Crear tablas una por una
    echo "Creando tabla users...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('student', 'teacher', 'admin') DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "Creando tabla topics...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS topics (
        topic_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        category ENUM('grammar', 'vocabulary', 'tips') DEFAULT 'grammar',
        detailed_content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "Creando tabla questions...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS questions (
        question_id INT AUTO_INCREMENT PRIMARY KEY,
        topic_id INT NOT NULL,
        question_text TEXT NOT NULL,
        option_a VARCHAR(255) NOT NULL,
        option_b VARCHAR(255) NOT NULL,
        option_c VARCHAR(255) NOT NULL,
        option_d VARCHAR(255) NOT NULL,
        correct_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
        difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
        explanation TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE
    )");
    
    echo "Creando tabla irregular_verbs...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS irregular_verbs (
        verb_id INT AUTO_INCREMENT PRIMARY KEY,
        base_form VARCHAR(100) NOT NULL,
        past_simple VARCHAR(100) NOT NULL,
        past_participle VARCHAR(100) NOT NULL,
        meaning VARCHAR(255),
        example TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_verb (base_form)
    )");
    
    echo "Creando tabla user_progress...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_progress (
        progress_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        topic_id INT NOT NULL,
        score DECIMAL(5,2) DEFAULT 0.00,
        mastery_level ENUM('not_started', 'beginner', 'intermediate', 'advanced', 'mastered') DEFAULT 'not_started',
        last_reviewed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        attempt_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_topic (user_id, topic_id)
    )");
    
    echo "Creando tabla quiz_history...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS quiz_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        topic_id INT NOT NULL,
        score DECIMAL(5,2) NOT NULL,
        attempt_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        duration INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE
    )");
    
    echo "Creando tabla user_answers...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_answers (
        answer_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        question_id INT NOT NULL,
        user_answer VARCHAR(255),
        is_correct BOOLEAN DEFAULT FALSE,
        similarity DECIMAL(5,2) DEFAULT 0.00,
        answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        attempt_id INT,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,
        FOREIGN KEY (attempt_id) REFERENCES quiz_history(history_id) ON DELETE SET NULL
    )");
    
    // Insertar datos de ejemplo solo si no existen
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        echo "Insertando usuario administrador...\n";
        $adminHash = password_hash('password', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)")
            ->execute(['admin', 'admin@ingles.com', $adminHash, 'admin']);
    }
    
    $topicCount = $pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn();
    if ($topicCount == 0) {
        echo "Insertando temas de ejemplo...\n";
        $topics = [
            ['Question Forms', 'Formación de preguntas en inglés', 'grammar', '<h3>Formación de Preguntas</h3><p>Aprende a formar preguntas correctamente en inglés usando diferentes estructuras.</p>'],
            ['Present Perfect', 'Uso del Present Perfect', 'grammar', '<h3>Present Perfect</h3><p>El Present Perfect se usa para acciones que comenzaron en el pasado y continúan en el presente.</p>'],
            ['Personality Adjectives', 'Adjetivos para describir personalidad', 'vocabulary', '<h3>Adjetivos de Personalidad</h3><p>Vocabulario esencial para describir características de personalidad.</p>'],
            ['Life Events', 'Eventos importantes de la vida', 'vocabulary', '<h3>Eventos de la Vida</h3><p>Vocabulario relacionado con eventos importantes en la vida de las personas.</p>'],
            ['Prepositions', 'Preposiciones de tiempo y lugar', 'grammar', '<h3>Preposiciones</h3><p>Uso correcto de preposiciones en inglés.</p>']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO topics (title, description, category, detailed_content) VALUES (?, ?, ?, ?)");
        foreach ($topics as $topic) {
            $stmt->execute($topic);
        }
    }
    
    $questionCount = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
    if ($questionCount == 0) {
        echo "Insertando preguntas de ejemplo...\n";
        $questions = [
            [1, '¿Cuál es la forma correcta de hacer una pregunta en presente simple con "do"?', 'Do you like pizza?', 'You do like pizza?', 'Like you pizza?', 'You like pizza do?', 'A', 'easy', 'En presente simple, se usa "do" + sujeto + verbo base para formar preguntas.'],
            [1, 'How ___ your name?', 'do you spell', 'you spell', 'spell you', 'you do spell', 'A', 'medium', 'Para preguntar sobre cómo deletrear algo, usamos "How do you spell..."'],
            [2, 'I ___ never ___ to Japan.', 'have / been', 'has / been', 'had / been', 'have / be', 'A', 'medium', 'Present Perfect se forma con have/has + participio pasado.']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO questions (topic_id, question_text, option_a, option_b, option_c, option_d, correct_answer, difficulty, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($questions as $question) {
            $stmt->execute($question);
        }
    }
    
    $verbCount = $pdo->query("SELECT COUNT(*) FROM irregular_verbs")->fetchColumn();
    if ($verbCount == 0) {
        echo "Insertando verbos irregulares de ejemplo...\n";
        $verbs = [
            ['be', 'was/were', 'been', 'ser/estar', 'I have been to London twice.'],
            ['go', 'went', 'gone', 'ir', 'She has gone to the store.'],
            ['do', 'did', 'done', 'hacer', 'Have you done your homework?'],
            ['have', 'had', 'had', 'tener', 'I had breakfast this morning.'],
            ['say', 'said', 'said', 'decir', 'He said he would come.'],
            ['get', 'got', 'got/gotten', 'obtener/conseguir', 'I have got a new car.'],
            ['make', 'made', 'made', 'hacer/crear', 'She made a delicious cake.'],
            ['know', 'knew', 'known', 'conocer/saber', 'I have known him for years.'],
            ['think', 'thought', 'thought', 'pensar', 'I thought about you yesterday.'],
            ['take', 'took', 'taken', 'tomar/llevar', 'He has taken the bus to work.']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO irregular_verbs (base_form, past_simple, past_participle, meaning, example) VALUES (?, ?, ?, ?, ?)");
        foreach ($verbs as $verb) {
            $stmt->execute($verb);
        }
    }
    
    echo "\n🎉 ¡Base de datos configurada exitosamente!\n";
    echo "\n📊 Resumen:\n";
    echo "- Usuarios: " . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n";
    echo "- Temas: " . $pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn() . "\n";
    echo "- Preguntas: " . $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn() . "\n";
    echo "- Verbos: " . $pdo->query("SELECT COUNT(*) FROM irregular_verbs")->fetchColumn() . "\n";
    echo "\n👤 Usuario administrador:\n";
    echo "- Email: admin@ingles.com\n";
    echo "- Contraseña: password\n";
    echo "\n🌐 Accede en: http://localhost/ingles\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
