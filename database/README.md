# Base de Datos - Sistema de Inglés

## 📋 Información General

- **Nombre de la BD:** `ingles_system`
- **Servidor:** localhost
- **Puerto:** 3306
- **Usuario:** root
- **Contraseña:** (vacía)

## 🗄️ Estructura de Tablas

### 1. `users` - Usuarios del sistema
- **user_id** (INT, PK, AUTO_INCREMENT)
- **username** (VARCHAR(50), UNIQUE)
- **email** (VARCHAR(100), UNIQUE)
- **password_hash** (VARCHAR(255))
- **role** (ENUM: 'student', 'teacher', 'admin')
- **created_at, updated_at** (TIMESTAMP)

### 2. `topics` - Temas de estudio
- **topic_id** (INT, PK, AUTO_INCREMENT)
- **title** (VARCHAR(255))
- **description** (TEXT)
- **category** (ENUM: 'grammar', 'vocabulary', 'tips')
- **detailed_content** (TEXT)
- **created_at, updated_at** (TIMESTAMP)

### 3. `questions` - Preguntas del quiz
- **question_id** (INT, PK, AUTO_INCREMENT)
- **topic_id** (INT, FK → topics.topic_id)
- **question_text** (TEXT)
- **option_a, option_b, option_c, option_d** (VARCHAR(255))
- **correct_answer** (ENUM: 'A', 'B', 'C', 'D')
- **difficulty** (ENUM: 'easy', 'medium', 'hard')
- **explanation** (TEXT)
- **created_at** (TIMESTAMP)

### 4. `irregular_verbs` - Verbos irregulares
- **verb_id** (INT, PK, AUTO_INCREMENT)
- **base_form** (VARCHAR(100), UNIQUE)
- **past_simple** (VARCHAR(100))
- **past_participle** (VARCHAR(100))
- **meaning** (VARCHAR(255))
- **example** (TEXT)
- **created_at** (TIMESTAMP)

### 5. `user_progress` - Progreso de usuarios
- **progress_id** (INT, PK, AUTO_INCREMENT)
- **user_id** (INT, FK → users.user_id)
- **topic_id** (INT, FK → topics.topic_id)
- **score** (DECIMAL(5,2))
- **mastery_level** (ENUM: 'not_started', 'beginner', 'intermediate', 'advanced', 'mastered')
- **last_reviewed, attempt_date** (TIMESTAMP)
- **created_at, updated_at** (TIMESTAMP)

### 6. `quiz_history` - Historial de quizzes
- **history_id** (INT, PK, AUTO_INCREMENT)
- **user_id** (INT, FK → users.user_id)
- **topic_id** (INT, FK → topics.topic_id)
- **score** (DECIMAL(5,2))
- **attempt_date** (TIMESTAMP)
- **duration** (INT, segundos)

### 7. `user_answers` - Respuestas individuales
- **answer_id** (INT, PK, AUTO_INCREMENT)
- **user_id** (INT, FK → users.user_id)
- **question_id** (INT, FK → questions.question_id)
- **user_answer** (VARCHAR(255))
- **is_correct** (BOOLEAN)
- **similarity** (DECIMAL(5,2))
- **answered_at** (TIMESTAMP)
- **attempt_id** (INT, FK → quiz_history.history_id)

## 👤 Usuario Administrador por Defecto

- **Email:** admin@ingles.com
- **Contraseña:** password
- **Rol:** admin

## 📊 Datos de Ejemplo Incluidos

- **5 temas** de ejemplo (gramática y vocabulario)
- **3 preguntas** de muestra
- **10 verbos irregulares** comunes
- **1 usuario administrador**

## 🛠️ Comandos Útiles

### Conectar a MySQL desde terminal:
```bash
C:\xampp\mysql\bin\mysql.exe -u root -p
```

### Verificar base de datos:
```sql
SHOW DATABASES;
USE ingles_system;
SHOW TABLES;
```

### Ver estructura de tabla:
```sql
DESCRIBE users;
DESCRIBE topics;
```

### Verificar datos:
```sql
SELECT * FROM users;
SELECT * FROM topics;
SELECT * FROM questions LIMIT 5;
```

## 🚀 Acceso al Sistema

- **URL Principal:** http://localhost/ingles
- **Panel Admin:** http://localhost/ingles/admin/
- **phpMyAdmin:** http://localhost/phpmyadmin

## 🔧 Reinstalar Base de Datos

Si necesitas reinstalar completamente:

1. Eliminar base de datos:
```sql
DROP DATABASE ingles_system;
```

2. Ejecutar script de instalación:
```bash
C:\xampp\php\php.exe c:\xampp\htdocs\ingles\database\install.php
```

## 📝 Notas Importantes

- La base de datos usa charset UTF8MB4 para soporte completo de caracteres
- Todas las contraseñas están hasheadas con PASSWORD_BCRYPT
- Las claves foráneas tienen CASCADE para mantener integridad referencial
- Los timestamps se actualizan automáticamente
- La tabla user_progress tiene constraint UNIQUE(user_id, topic_id)

## 🔒 Seguridad

- Contraseñas siempre hasheadas
- Validación de entrada en todas las consultas
- Uso de prepared statements para prevenir SQL injection
- Control de acceso basado en roles
- Protección CSRF implementada
