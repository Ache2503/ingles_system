# 📚 Sistema de Aprendizaje de Inglés - Documentación Completa

## 📋 Índice
1. [Información General](#información-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Base de Datos](#base-de-datos)
4. [Estructura de Archivos](#estructura-de-archivos)
5. [Funcionalidades](#funcionalidades)
6. [Panel de Administración](#panel-de-administración)
7. [Sistema de Autenticación](#sistema-de-autenticación)
8. [APIs y Endpoints](#apis-y-endpoints)
9. [Configuración](#configuración)
10. [Despliegue](#despliegue)
11. [Mantenimiento](#mantenimiento)

---

## 📄 Información General

### Descripción
Sistema web completo para el aprendizaje de inglés que incluye:
- Gestión de usuarios con roles
- Temas de estudio organizados por categorías
- Sistema de quizzes interactivos
- Verbos irregulares con ejemplos
- Panel de administración completo
- Sistema de gamificación
- Notificaciones y logros
- Monitoreo de salud del sistema

### Tecnologías
- **Backend**: PHP 7.4+ con PDO
- **Base de Datos**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Servidor**: Apache (XAMPP)
- **Dependencias**: Ninguna (sistema autocontenido)

### Requisitos del Sistema
- PHP 7.4 o superior
- MySQL 8.0 o MariaDB 10.4+
- Apache 2.4+
- Extensiones PHP: PDO, PDO_MySQL, mbstring, json
- Espacio en disco: ~100MB
- RAM: 512MB mínimo

---

## 🏗️ Arquitectura del Sistema

### Patrón de Diseño
El sistema sigue un patrón **MVC simplificado** con separación clara de responsabilidades:

```
├── Presentación (Views)     → Archivos PHP con HTML/CSS/JS
├── Lógica de Negocio       → Funciones en includes/
├── Acceso a Datos          → PDO + MySQL
└── Configuración           → includes/config.php
```

### Componentes Principales

#### 1. **Sistema de Autenticación**
- Protección por sesiones PHP
- Roles de usuario (student, teacher, admin)
- Validación de permisos por página
- Hash seguro de contraseñas (bcrypt)

#### 2. **Gestión de Contenido**
- Temas categorizados (gramática, vocabulario, tips)
- Preguntas de opción múltiple
- Verbos irregulares con ejemplos
- Sistema de etiquetas y categorías

#### 3. **Sistema de Evaluación**
- Quizzes interactivos
- Seguimiento de progreso
- Historial de respuestas
- Cálculo automático de puntuaciones

#### 4. **Panel de Administración**
- Gestión completa de usuarios
- CRUD de contenido educativo
- Analytics y estadísticas
- Sistema de respaldos
- Monitoreo de salud del sistema

---

## 🗄️ Base de Datos

### Esquema Principal

#### **Tablas Core del Sistema**

##### `users` - Usuarios del Sistema
```sql
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'admin') DEFAULT 'student',
    avatar_url VARCHAR(255),
    bio TEXT,
    timezone VARCHAR(50) DEFAULT 'America/Mexico_City',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

##### `topics` - Temas de Estudio
```sql
CREATE TABLE topics (
    topic_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('grammar', 'vocabulary', 'tips') DEFAULT 'grammar',
    detailed_content TEXT,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'intermediate',
    estimated_time INT DEFAULT 15,
    is_featured BOOLEAN DEFAULT FALSE,
    views_count INT DEFAULT 0,
    last_viewed TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

##### `questions` - Preguntas del Quiz
```sql
CREATE TABLE questions (
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
    points_value INT DEFAULT 10,
    time_limit_seconds INT DEFAULT 60,
    question_type ENUM('multiple_choice', 'true_false', 'fill_blank', 'essay') DEFAULT 'multiple_choice',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE
);
```

##### `irregular_verbs` - Verbos Irregulares
```sql
CREATE TABLE irregular_verbs (
    verb_id INT AUTO_INCREMENT PRIMARY KEY,
    base_form VARCHAR(100) NOT NULL,
    past_simple VARCHAR(100) NOT NULL,
    past_participle VARCHAR(100) NOT NULL,
    meaning VARCHAR(255),
    example TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_verb (base_form)
);
```

#### **Tablas de Progreso y Evaluación**

##### `user_progress` - Progreso de Usuarios
```sql
CREATE TABLE user_progress (
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
);
```

##### `quiz_history` - Historial de Quizzes
```sql
CREATE TABLE quiz_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    topic_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    attempt_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE
);
```

##### `user_answers` - Respuestas Individuales
```sql
CREATE TABLE user_answers (
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
);
```

#### **Sistema de Gamificación**

##### `user_gamification` - Puntos y Niveles
```sql
CREATE TABLE user_gamification (
    user_id INT PRIMARY KEY,
    total_points INT DEFAULT 0,
    current_level INT DEFAULT 1,
    experience_points INT DEFAULT 0,
    study_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    last_activity_date DATE,
    total_study_time INT DEFAULT 0,
    favorite_topic_id INT,
    study_streak_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (favorite_topic_id) REFERENCES topics(topic_id) ON DELETE SET NULL
);
```

##### `user_achievements` - Logros de Usuarios
```sql
CREATE TABLE user_achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_type VARCHAR(50) NOT NULL,
    achievement_name VARCHAR(100) NOT NULL,
    achievement_description TEXT,
    points_earned INT DEFAULT 0,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

##### `achievement_config` - Configuración de Logros
```sql
CREATE TABLE achievement_config (
    config_id INT AUTO_INCREMENT PRIMARY KEY,
    achievement_type VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    points_reward INT DEFAULT 0,
    condition_value INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE
);
```

#### **Sistema de Notificaciones**

##### `notifications` - Notificaciones
```sql
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('achievement', 'reminder', 'system', 'quiz_result') NOT NULL DEFAULT 'system',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

#### **Sistema de Configuración**

##### `user_settings` - Configuraciones de Usuario
```sql
CREATE TABLE user_settings (
    user_id INT PRIMARY KEY,
    notifications_enabled BOOLEAN DEFAULT TRUE,
    email_reminders BOOLEAN DEFAULT TRUE,
    study_reminder_time TIME DEFAULT '19:00:00',
    preferred_language ENUM('es', 'en') DEFAULT 'es',
    theme ENUM('light', 'dark', 'auto') DEFAULT 'light',
    difficulty_preference ENUM('adaptive', 'easy', 'medium', 'hard') DEFAULT 'adaptive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

#### **Sistema de Contenido Avanzado**

##### `content_categories` - Categorías de Contenido
```sql
CREATE TABLE content_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#3498db',
    icon VARCHAR(50),
    parent_category_id INT,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (parent_category_id) REFERENCES content_categories(category_id) ON DELETE SET NULL
);
```

##### `tags` - Etiquetas
```sql
CREATE TABLE tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#6c757d',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

##### `topic_tags` - Relación Temas-Etiquetas
```sql
CREATE TABLE topic_tags (
    topic_id INT,
    tag_id INT,
    PRIMARY KEY (topic_id, tag_id),
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
);
```

#### **Sistema de Recursos Multimedia**

##### `media_resources` - Recursos Multimedia
```sql
CREATE TABLE media_resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT,
    question_id INT,
    type ENUM('image', 'audio', 'video', 'document') NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
);
```

#### **Sistema de Seguimiento**

##### `study_sessions` - Sesiones de Estudio
```sql
CREATE TABLE study_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    topic_id INT,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    duration_minutes INT DEFAULT 0,
    questions_answered INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    session_type ENUM('practice', 'exam', 'review') DEFAULT 'practice',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE SET NULL
);
```

##### `user_bookmarks` - Marcadores de Usuario
```sql
CREATE TABLE user_bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_type ENUM('topic', 'verb', 'question') NOT NULL,
    content_id INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_bookmark (user_id, content_type, content_id)
);
```

##### `user_navigation_history` - Historial de Navegación
```sql
CREATE TABLE user_navigation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    page_type ENUM('topic', 'verb', 'question', 'practice', 'quiz') NOT NULL,
    content_id INT,
    page_title VARCHAR(255),
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration_seconds INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

##### `user_configuration` - Configuración Extendida
```sql
CREATE TABLE user_configuration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    config_key VARCHAR(100) NOT NULL,
    config_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_config (user_id, config_key)
);
```

### Índices de Optimización
```sql
-- Índices para mejorar rendimiento
CREATE INDEX idx_user_progress_score ON user_progress(score);
CREATE INDEX idx_quiz_history_date ON quiz_history(attempt_date);
CREATE INDEX idx_user_answers_correct ON user_answers(is_correct);
CREATE INDEX idx_questions_difficulty ON questions(difficulty);
CREATE INDEX idx_topics_category ON topics(category);
CREATE INDEX idx_topics_featured ON topics(is_featured);
CREATE INDEX idx_achievements_type ON user_achievements(achievement_type);
CREATE INDEX idx_notifications_unread ON notifications(user_id, is_read);
CREATE INDEX idx_bookmarks_user_type ON user_bookmarks(user_id, content_type);
CREATE INDEX idx_gamification_points ON user_gamification(total_points);
```

---

## 📁 Estructura de Archivos

```
ingles/
│
├── 📁 admin/                          # Panel de Administración
│   ├── index.php                      # Dashboard principal
│   ├── users.php                      # Gestión de usuarios
│   ├── topics.php                     # Gestión de temas
│   ├── questions.php                  # Gestión de preguntas
│   ├── verbs.php                      # Gestión de verbos
│   ├── analytics.php                  # Analytics y estadísticas
│   ├── backup.php                     # Sistema de respaldos
│   ├── send_notifications.php         # Sistema de notificaciones
│   └── system_health.php              # Monitoreo de salud
│
├── 📁 assets/                         # Recursos estáticos
│   ├── 📁 css/
│   │   ├── style.css                  # Estilos principales
│   │   └── admin.css                  # Estilos del admin
│   └── 📁 js/
│       └── script.js                  # JavaScript principal
│
├── 📁 includes/                       # Archivos de inclusión
│   ├── config.php                     # Configuración global
│   ├── db.php                         # Conexión a base de datos
│   ├── auth.php                       # Funciones de autenticación
│   ├── session_protection.php         # Protección de sesiones
│   ├── navigation.php                 # Sistema de navegación
│   ├── header.php                     # Header del sitio
│   ├── footer.php                     # Footer del sitio
│   ├── admin_header.php               # Header del admin
│   └── admin_footer.php               # Footer del admin
│
├── 📁 database/                       # Scripts de base de datos
│   ├── create_database.sql            # Script de creación principal
│   ├── improvements.sql               # Mejoras adicionales
│   ├── setup_database.php             # Instalador automático
│   ├── create_improvements.php        # Aplicar mejoras
│   ├── install.php                    # Instalación completa
│   └── README.md                      # Documentación de BD
│
├── 📁 tests/                          # Herramientas de testing
│   ├── system_verifier.php            # Verificador del sistema
│   ├── database_updater.php           # Actualizador de BD
│   ├── database_analyzer.php          # Analizador de BD
│   ├── database_control_center.php    # Centro de control
│   ├── comprehensive_test.php         # Test completo
│   ├── backup_database.php            # Respaldo de BD
│   ├── auth_verifier.php              # Verificador de auth
│   └── [otros archivos de testing]
│
├── 📁 temp/                           # Archivos temporales
│   └── [archivos temporales de desarrollo]
│
├── 📁 user/                           # Panel de usuario
│   └── index.php                      # Dashboard de usuario
│
├── 📁 backups/                        # Respaldos automáticos
│   └── [archivos de backup]
│
├── 📁 logs/                           # Logs del sistema
│   └── [archivos de log]
│
├── index.php                          # Página principal
├── login.php                          # Página de login
├── register.php                       # Página de registro
├── logout.php                         # Script de logout
├── topics.php                         # Lista de temas
├── topic_detail.php                   # Detalle de tema
├── practice.php                       # Página de práctica
├── quiz-result.php                    # Resultados de quiz
├── progress.php                       # Progreso del usuario
└── system_test.php                    # Test del sistema
```

---

## ⚙️ Funcionalidades

### 👤 Sistema de Usuarios

#### **Registro y Autenticación**
- Registro con validación de email único
- Login con username/email
- Hash seguro de contraseñas (bcrypt)
- Gestión de sesiones PHP
- Logout seguro

#### **Roles de Usuario**
- **Student**: Acceso a contenido educativo
- **Teacher**: Gestión de contenido educativo
- **Admin**: Acceso completo al sistema

#### **Perfil de Usuario**
- Información personal (bio, avatar, timezone)
- Configuraciones personalizadas
- Historial de actividad
- Estadísticas de progreso

### 📚 Sistema Educativo

#### **Gestión de Contenido**
- **Temas**: Organizados por categorías (gramática, vocabulario, tips)
- **Preguntas**: Opción múltiple con explicaciones
- **Verbos Irregulares**: Base de datos completa
- **Categorías**: Sistema flexible de clasificación
- **Etiquetas**: Organización granular

#### **Sistema de Evaluación**
- Quizzes interactivos por tema
- Cálculo automático de puntuaciones
- Feedback inmediato
- Tracking de progreso individual
- Historial completo de intentos

#### **Niveles de Dificultad**
- Beginner, Intermediate, Advanced
- Adaptación automática según rendimiento
- Progresión guiada

### 🎯 Sistema de Gamificación

#### **Puntos y Niveles**
- Sistema de puntos por actividad
- Niveles automáticos basados en experiencia
- Ranking de usuarios
- Streaks de estudio consecutivo

#### **Logros (Achievements)**
- Primer quiz completado
- Puntuación perfecta
- Racha de estudio
- Maestría de temas
- Logros por horario (madrugador, nocturno)

#### **Seguimiento de Progreso**
- Tiempo total de estudio
- Temas favoritos
- Estadísticas detalladas
- Progreso visual

### 🔔 Sistema de Notificaciones

#### **Tipos de Notificaciones**
- Logros obtenidos
- Recordatorios de estudio
- Mensajes del sistema
- Resultados de quiz

#### **Gestión Administrativa**
- Notificaciones masivas
- Plantillas predefinidas
- Segmentación de usuarios
- Historial de envíos

### 🛠️ Panel de Administración

#### **Dashboard Principal**
- Estadísticas en tiempo real
- Actividad reciente
- Métricas clave
- Accesos rápidos

#### **Gestión de Usuarios**
- CRUD completo de usuarios
- Asignación de roles
- Estadísticas por usuario
- Exportación de datos

#### **Gestión de Contenido**
- CRUD de temas, preguntas y verbos
- Organización por categorías
- Preview de contenido
- Importación masiva

#### **Sistema de Respaldos**
- Backup automático de MySQL
- Gestión de archivos de backup
- Programación de respaldos
- Restauración de datos

#### **Monitoreo del Sistema**
- Estado de la base de datos
- Uso de recursos del servidor
- Logs de errores
- Verificaciones de seguridad

### 📊 Analytics y Reportes

#### **Métricas de Usuario**
- Usuarios activos diarios/semanales
- Tiempo promedio de sesión
- Temas más populares
- Rendimiento por usuario

#### **Métricas de Contenido**
- Temas más estudiados
- Preguntas más difíciles
- Rendimiento por categoría
- Análisis de dificultad

#### **Métricas del Sistema**
- Carga del servidor
- Tiempo de respuesta
- Errores del sistema
- Uso de base de datos

---

## 🔐 Sistema de Autenticación

### Protección de Sesiones

#### **session_protection.php**
```php
// Funciones principales de protección
function requireLogin()          // Requiere usuario logueado
function requireAdmin()          // Requiere rol de admin  
function requireTeacher()        // Requiere rol de teacher o admin
function validateSession()       // Valida sesión activa
function getCurrentUserInfo()    // Obtiene info del usuario actual
function logUserActivity()       // Registra actividad del usuario
```

#### **auth.php**
```php
// Funciones de autenticación
function authenticateUser($username, $password)  // Autentica credenciales
function registerUser($data)                     // Registra nuevo usuario
function updateUserProfile($userId, $data)       // Actualiza perfil
function changePassword($userId, $newPassword)   // Cambia contraseña
function getUserById($userId)                     // Obtiene usuario por ID
function getUserByUsername($username)             // Obtiene usuario por username
```

### Niveles de Acceso

#### **Páginas Públicas**
- index.php (página principal)
- login.php (formulario de login)
- register.php (formulario de registro)

#### **Páginas de Usuario** (requireLogin)
- topics.php (lista de temas)
- topic_detail.php (detalle de tema)
- practice.php (práctica)
- progress.php (progreso)
- quiz-result.php (resultados)

#### **Páginas de Administrador** (requireAdmin)
- admin/* (todo el panel de administración)
- system_health.php (monitoreo)
- backup.php (respaldos)

### Flujo de Autenticación

1. **Login**:
   ```php
   POST /login.php
   → Validar credenciales
   → Crear sesión PHP
   → Redireccionar según rol
   ```

2. **Verificación por Página**:
   ```php
   require_once 'includes/session_protection.php';
   requireLogin(); // o requireAdmin()
   ```

3. **Logout**:
   ```php
   GET /logout.php
   → Destruir sesión
   → Limpiar cookies
   → Redireccionar a login
   ```

---

## 🔧 Configuración

### Configuración de Base de Datos

#### **includes/db.php**
```php
$host = 'localhost';
$dbname = 'ingles_system';
$username = 'root';
$password = '';
```

#### **includes/config.php**
```php
// URL base del sistema
define('BASE_URL', 'http://localhost/ingles');

// Configuración de sesiones
session_start();
```

### Variables de Entorno

#### **Configuración Recomendada para Producción**
```php
// En production, usar variables de entorno
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'ingles_system';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

// Configuración de seguridad
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('display_errors', 0);
```

### Configuración de Apache

#### **.htaccess** (recomendado)
```apache
# Seguridad
<Files "*.php">
    Require all granted
</Files>

<Files "includes/*">
    Require all denied
</Files>

<Files "database/*">
    Require all denied
</Files>

# Redirecciones amigables
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^/]+)/?$ topic_detail.php?slug=$1 [L,QSA]
```

---

## 🚀 Despliegue

### Instalación Automática

#### **1. Clonar Archivos**
```bash
# Copiar archivos al directorio web
cp -r ingles/ /var/www/html/
# o en XAMPP: C:/xampp/htdocs/
```

#### **2. Configurar Base de Datos**
```bash
# Opción 1: Script automático
php database/setup_database.php

# Opción 2: MySQL manual
mysql -u root -p < database/create_database.sql
```

#### **3. Configurar Permisos**
```bash
# Linux/Mac
chmod 755 -R ingles/
chmod 777 ingles/backups/
chmod 777 ingles/logs/
chmod 777 ingles/temp/

# Windows (XAMPP)
# Verificar que Apache tenga permisos de escritura
```

#### **4. Verificar Instalación**
```
http://localhost/ingles/system_test.php
```

### Usuario Administrador por Defecto
- **Username**: admin
- **Email**: admin@ingles.com
- **Password**: password
- **Rol**: admin

### Verificaciones Post-Instalación

#### **database/setup_database.php**
- ✅ Crea la base de datos
- ✅ Ejecuta todos los scripts SQL
- ✅ Inserta datos de ejemplo
- ✅ Verifica la instalación

#### **tests/system_verifier.php**
- ✅ Verifica estructura de BD
- ✅ Comprueba permisos de archivos
- ✅ Valida configuración PHP
- ✅ Testa conectividad

#### **admin/system_health.php**
- ✅ Monitoreo en tiempo real
- ✅ Estado de todos los componentes
- ✅ Métricas de rendimiento
- ✅ Alertas de problemas

---

## 🔧 Mantenimiento

### Respaldos Automáticos

#### **admin/backup.php**
```php
// Características del sistema de backup
- Backup completo de MySQL con mysqldump
- Gestión de archivos de backup (crear/descargar/eliminar)
- Programación de backups automáticos
- Monitoreo de espacio en disco
- Compresión opcional de archivos
- Verificación de integridad
```

#### **Programación de Backups**
```bash
# Crontab para backup diario (Linux)
0 2 * * * /usr/bin/php /var/www/html/ingles/admin/backup.php

# Windows Task Scheduler
# Programar: php.exe C:\xampp\htdocs\ingles\admin\backup.php
```

### Monitoreo del Sistema

#### **admin/system_health.php - Verificaciones**

1. **Conexión a Base de Datos**
   - Velocidad de conexión
   - Estado de las tablas
   - Integridad de datos

2. **Sistema Operativo**
   - Espacio en disco disponible
   - Uso de memoria PHP
   - Configuración PHP crítica

3. **Seguridad**
   - Configuraciones de PHP
   - Archivos expuestos
   - Permisos de directorios

4. **Rendimiento**
   - Tiempo de respuesta de BD
   - Logs de errores recientes
   - Actividad de usuarios

5. **Backups**
   - Estado de backups recientes
   - Espacio disponible
   - Programación de respaldos

### Logs del Sistema

#### **Ubicaciones de Logs**
```
logs/
├── system.log          # Logs generales del sistema
├── auth.log            # Logs de autenticación
├── backup.log          # Logs de respaldos
├── error.log           # Logs de errores PHP
└── admin.log           # Logs de actividad admin
```

#### **Rotación de Logs**
```php
// Configuración automática de limpieza
- Logs mayores a 30 días se archivan
- Compresión automática de logs antiguos
- Límite de tamaño por archivo: 10MB
- Retención máxima: 90 días
```

### Actualizaciones

#### **tests/database_updater.php**
```php
// Sistema de migración automática
- Detecta cambios en la estructura de BD
- Aplica actualizaciones de forma segura
- Mantiene compatibilidad hacia atrás
- Registro detallado de cambios
- Rollback automático en caso de error
```

#### **Proceso de Actualización**
1. **Backup Automático** antes de cualquier cambio
2. **Verificación de Requisitos** del sistema
3. **Aplicación de Migraciones** paso a paso
4. **Validación Post-Actualización**
5. **Rollback Automático** si hay errores

### Optimización de Rendimiento

#### **Índices de Base de Datos**
```sql
-- Optimizaciones aplicadas automáticamente
CREATE INDEX idx_user_progress_score ON user_progress(score);
CREATE INDEX idx_quiz_history_date ON quiz_history(attempt_date);
CREATE INDEX idx_topics_category ON topics(category);
CREATE INDEX idx_questions_difficulty ON questions(difficulty);
```

#### **Caché de Consultas**
```php
// Sistema de caché implementado en:
- Estadísticas del dashboard
- Listados de temas frecuentes
- Datos de usuario activo
- Resultados de analytics
```

#### **Limpieza Automática**
```php
// Tareas de mantenimiento automático:
- Limpieza de sesiones expiradas
- Purga de logs antiguos
- Optimización de tablas MySQL
- Limpieza de archivos temporales
```

---

## 📋 Checklist de Mantenimiento

### Diario ✅
- [ ] Verificar system_health.php
- [ ] Revisar logs de errores
- [ ] Monitorear actividad de usuarios
- [ ] Verificar espacio en disco

### Semanal ✅
- [ ] Ejecutar backup manual
- [ ] Revisar rendimiento de BD
- [ ] Limpiar archivos temporales
- [ ] Verificar actualizaciones de seguridad

### Mensual ✅
- [ ] Análisis completo de analytics
- [ ] Optimización de tablas MySQL
- [ ] Revisión de logs de seguridad
- [ ] Actualización de documentación

### Trimestral ✅
- [ ] Audit completo de seguridad
- [ ] Pruebas de restauración de backup
- [ ] Revisión de permisos de usuarios
- [ ] Planificación de mejoras

---

## 📞 Soporte y Recursos

### Archivos de Ayuda
- `database/README.md` - Documentación específica de BD
- `admin/system_health.php` - Diagnóstico en tiempo real
- `tests/system_verifier.php` - Verificador completo
- `tests/comprehensive_test.php` - Suite de pruebas

### Comandos Útiles
```bash
# Verificar estado del sistema
php system_test.php

# Backup manual
php admin/backup.php

# Actualizar base de datos
php tests/database_updater.php

# Verificar integridad
php tests/system_verifier.php
```

### Solución de Problemas Comunes

#### **Error de Conexión a BD**
1. Verificar credenciales en `includes/db.php`
2. Asegurar que MySQL esté ejecutándose
3. Verificar permisos de usuario de BD

#### **Errores de Sesión**
1. Verificar permisos de directorio de sesiones PHP
2. Comprobar configuración de cookies
3. Revisar `includes/session_protection.php`

#### **Problemas de Permisos**
1. Verificar permisos de carpetas `backups/`, `logs/`, `temp/`
2. Asegurar que Apache/PHP tenga acceso de escritura
3. Verificar SELinux/AppArmor si están activos

---

*Documentación generada automáticamente el 8 de agosto de 2025*
*Sistema de Aprendizaje de Inglés v2.0*
