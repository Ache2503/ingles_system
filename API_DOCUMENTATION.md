# 🔌 API Documentation - Sistema de Aprendizaje de Inglés

## 📖 Introducción

Esta documentación describe todas las APIs y endpoints disponibles en el sistema de aprendizaje de inglés. Las APIs están diseñadas para ser RESTful y retornan datos en formato JSON.

## 🔐 Autenticación

Todas las APIs requieren autenticación mediante sesiones PHP. El usuario debe estar logueado antes de acceder a cualquier endpoint.

```php
// Verificación automática en cada API
require_once '../includes/session_protection.php';
requireLogin(); // o requireAdmin() para endpoints administrativos
```

## 📊 APIs de Usuario

### GET `/api/get_user_stats.php`

Obtiene estadísticas detalladas del usuario actual.

**Respuesta:**
```json
{
    "user_id": 1,
    "username": "estudiante1",
    "total_points": 150,
    "current_level": 3,
    "study_streak": 7,
    "topics_completed": 5,
    "quizzes_taken": 12,
    "average_score": 85.5,
    "achievements": [
        {
            "achievement_type": "first_quiz",
            "name": "Primer Paso",
            "points_earned": 10,
            "earned_at": "2025-08-08 10:30:00"
        }
    ]
}
```

### POST `/api/update_progress.php`

Actualiza el progreso del usuario en un tema específico.

**Parámetros:**
```json
{
    "topic_id": 1,
    "score": 85.5,
    "time_spent": 300,
    "questions_answered": 10,
    "correct_answers": 8
}
```

**Respuesta:**
```json
{
    "success": true,
    "message": "Progreso actualizado correctamente",
    "new_level": "intermediate",
    "points_earned": 15,
    "achievements_unlocked": [
        {
            "type": "perfect_score",
            "name": "Perfección",
            "points": 50
        }
    ]
}
```

## 🧪 APIs de Quiz

### POST `/api/quiz-result-api.php`

Procesa los resultados de un quiz y actualiza estadísticas.

**Parámetros:**
```json
{
    "topic_id": 1,
    "answers": [
        {
            "question_id": 1,
            "user_answer": "A",
            "correct_answer": "A",
            "is_correct": true,
            "time_taken": 15
        },
        {
            "question_id": 2,
            "user_answer": "B",
            "correct_answer": "C",
            "is_correct": false,
            "time_taken": 20
        }
    ],
    "total_time": 180,
    "start_time": "2025-08-08 14:30:00"
}
```

**Respuesta:**
```json
{
    "success": true,
    "quiz_result": {
        "score": 75.5,
        "total_questions": 10,
        "correct_answers": 8,
        "incorrect_answers": 2,
        "time_taken": 180,
        "mastery_level": "intermediate",
        "points_earned": 25
    },
    "achievements": [
        {
            "type": "quiz_master",
            "name": "Maestro del Quiz",
            "description": "Completa 10 quizzes",
            "points": 50
        }
    ],
    "next_recommendations": [
        {
            "topic_id": 2,
            "title": "Present Perfect",
            "reason": "Tema relacionado con gramática"
        }
    ]
}
```

### GET `/api/check_achievements.php`

Verifica y retorna nuevos logros desbloqueados.

**Parámetros opcionales:**
- `user_id`: ID del usuario (solo para admins)
- `type`: Tipo específico de logro a verificar

**Respuesta:**
```json
{
    "new_achievements": [
        {
            "achievement_id": 15,
            "type": "study_streak",
            "name": "Constancia",
            "description": "Estudia 7 días consecutivos",
            "icon": "🔥",
            "points_earned": 100,
            "earned_at": "2025-08-08 15:45:00"
        }
    ],
    "total_new_points": 100,
    "level_up": {
        "previous_level": 3,
        "new_level": 4,
        "points_needed_for_next": 200
    }
}
```

## 📚 APIs de Contenido

### GET `/api/get_topics.php`

Obtiene lista de temas con filtros opcionales.

**Parámetros:**
- `category`: Filtrar por categoría (grammar, vocabulary, tips)
- `difficulty`: Filtrar por dificultad (beginner, intermediate, advanced)
- `featured`: Solo temas destacados (true/false)
- `limit`: Número máximo de resultados
- `offset`: Offset para paginación

**Respuesta:**
```json
{
    "topics": [
        {
            "topic_id": 1,
            "title": "Question Forms",
            "description": "Formación de preguntas en inglés",
            "category": "grammar",
            "difficulty_level": "intermediate",
            "estimated_time": 15,
            "is_featured": true,
            "views_count": 245,
            "user_progress": {
                "completed": true,
                "score": 85.5,
                "mastery_level": "advanced",
                "last_reviewed": "2025-08-07 16:30:00"
            }
        }
    ],
    "total_count": 25,
    "has_next_page": true
}
```

### GET `/api/get_topic_detail.php`

Obtiene detalles completos de un tema específico.

**Parámetros:**
- `topic_id`: ID del tema

**Respuesta:**
```json
{
    "topic": {
        "topic_id": 1,
        "title": "Question Forms",
        "description": "Formación de preguntas en inglés",
        "detailed_content": "<h3>Contenido detallado...</h3>",
        "category": "grammar",
        "difficulty_level": "intermediate",
        "estimated_time": 15,
        "questions_count": 10,
        "created_at": "2025-01-15 10:00:00"
    },
    "user_progress": {
        "score": 85.5,
        "mastery_level": "advanced",
        "attempts": 3,
        "best_score": 90.0,
        "time_spent": 45,
        "last_reviewed": "2025-08-07 16:30:00"
    },
    "related_topics": [
        {
            "topic_id": 2,
            "title": "Present Perfect",
            "similarity_score": 0.75
        }
    ]
}
```

### GET `/api/get_questions.php`

Obtiene preguntas para un tema específico.

**Parámetros:**
- `topic_id`: ID del tema
- `difficulty`: Filtrar por dificultad
- `limit`: Número de preguntas (default: 10)
- `random`: Aleatorizar orden (true/false)

**Respuesta:**
```json
{
    "questions": [
        {
            "question_id": 1,
            "question_text": "¿Cuál es la forma correcta de formar una pregunta?",
            "option_a": "Do you like coffee?",
            "option_b": "You like coffee?",
            "option_c": "Like you coffee?",
            "option_d": "Coffee you like?",
            "correct_answer": "A",
            "difficulty": "medium",
            "points_value": 10,
            "time_limit_seconds": 60,
            "explanation": "Las preguntas en inglés requieren auxiliar 'do'..."
        }
    ],
    "total_questions": 10,
    "estimated_time": 600
}
```

## 🏆 APIs de Gamificación

### GET `/api/get_leaderboard.php`

Obtiene tabla de clasificación de usuarios.

**Parámetros:**
- `type`: Tipo de ranking (points, streak, level)
- `period`: Período (daily, weekly, monthly, all_time)
- `limit`: Número de usuarios (default: 10)

**Respuesta:**
```json
{
    "leaderboard": [
        {
            "rank": 1,
            "user_id": 5,
            "username": "topstudent",
            "total_points": 2500,
            "current_level": 15,
            "study_streak": 25,
            "avatar_url": "/uploads/avatars/user5.jpg"
        }
    ],
    "user_rank": {
        "current_user_rank": 8,
        "total_users": 150,
        "percentile": 95
    },
    "period_info": {
        "type": "weekly",
        "start_date": "2025-08-04",
        "end_date": "2025-08-10"
    }
}
```

### POST `/api/claim_achievement.php`

Reclama un logro específico (si es aplicable).

**Parámetros:**
```json
{
    "achievement_type": "study_streak",
    "verification_data": {
        "streak_days": 7,
        "last_study_date": "2025-08-08"
    }
}
```

**Respuesta:**
```json
{
    "success": true,
    "achievement": {
        "achievement_id": 25,
        "type": "study_streak",
        "name": "Constancia",
        "points_earned": 100,
        "claimed_at": "2025-08-08 16:00:00"
    },
    "level_up": false,
    "total_points": 1150
}
```

## 🔔 APIs de Notificaciones

### GET `/api/get_notifications.php`

Obtiene notificaciones del usuario.

**Parámetros:**
- `unread_only`: Solo no leídas (true/false)
- `type`: Filtrar por tipo (achievement, reminder, system, quiz_result)
- `limit`: Número máximo de notificaciones
- `offset`: Offset para paginación

**Respuesta:**
```json
{
    "notifications": [
        {
            "notification_id": 1,
            "type": "achievement",
            "title": "¡Nuevo logro desbloqueado!",
            "message": "Has obtenido el logro 'Primer Paso'",
            "is_read": false,
            "created_at": "2025-08-08 10:30:00",
            "data": {
                "achievement_type": "first_quiz",
                "points_earned": 10
            }
        }
    ],
    "unread_count": 3,
    "total_count": 15
}
```

### POST `/api/mark_notification_read.php`

Marca notificaciones como leídas.

**Parámetros:**
```json
{
    "notification_ids": [1, 2, 3]
}
```

**Respuesta:**
```json
{
    "success": true,
    "marked_count": 3,
    "remaining_unread": 0
}
```

## 👨‍💼 APIs Administrativas

### GET `/admin/api/get_system_stats.php`

Obtiene estadísticas completas del sistema (solo admins).

**Respuesta:**
```json
{
    "users": {
        "total": 150,
        "active_today": 25,
        "active_week": 89,
        "new_this_month": 12
    },
    "content": {
        "topics": 25,
        "questions": 250,
        "verbs": 150
    },
    "activity": {
        "quizzes_today": 45,
        "total_study_time": 2850,
        "average_score": 78.5
    },
    "system": {
        "database_size": "15.2 MB",
        "disk_usage": "68%",
        "response_time": "120ms",
        "uptime": "15 days"
    }
}
```

### POST `/admin/api/send_notification.php`

Envía notificaciones masivas (solo admins).

**Parámetros:**
```json
{
    "target": "all", // "all", "active", "specific"
    "user_ids": [1, 2, 3], // Solo si target es "specific"
    "title": "Mantenimiento programado",
    "message": "El sistema estará en mantenimiento mañana...",
    "type": "system",
    "priority": "high"
}
```

**Respuesta:**
```json
{
    "success": true,
    "notifications_sent": 150,
    "failed_sends": 0,
    "notification_id": 1001
}
```

## 📝 Códigos de Error

### Errores Comunes

```json
{
    "success": false,
    "error": {
        "code": "AUTH_REQUIRED",
        "message": "Usuario no autenticado",
        "http_status": 401
    }
}
```

### Códigos de Error Disponibles:

- **AUTH_REQUIRED** (401): Usuario no autenticado
- **INSUFFICIENT_PERMISSIONS** (403): Permisos insuficientes
- **INVALID_PARAMETERS** (400): Parámetros inválidos
- **RESOURCE_NOT_FOUND** (404): Recurso no encontrado
- **RATE_LIMIT_EXCEEDED** (429): Límite de rate exceeded
- **INTERNAL_ERROR** (500): Error interno del servidor
- **DATABASE_ERROR** (500): Error de base de datos
- **VALIDATION_FAILED** (422): Validación de datos falló

### Formato de Error Detallado:

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_FAILED",
        "message": "Los datos proporcionados no son válidos",
        "details": {
            "field": "topic_id",
            "reason": "El topic_id es requerido y debe ser un número entero"
        },
        "http_status": 422,
        "timestamp": "2025-08-08T15:30:00Z",
        "request_id": "req_123456"
    }
}
```

## 🔒 Límites de Rate

Para prevenir abuso, algunas APIs tienen límites de rate:

- **APIs de Usuario**: 100 requests/minuto
- **APIs de Quiz**: 20 requests/minuto
- **APIs Administrativas**: 200 requests/minuto

Cuando se excede el límite:

```json
{
    "success": false,
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "message": "Límite de requests excedido",
        "retry_after": 60,
        "limit": 100,
        "remaining": 0,
        "reset_time": "2025-08-08T15:31:00Z"
    }
}
```

## 📊 Webhooks (Futuro)

### Eventos Disponibles:

- `user.achievement.earned`: Cuando un usuario obtiene un logro
- `quiz.completed`: Cuando se completa un quiz
- `user.level.up`: Cuando un usuario sube de nivel
- `system.maintenance`: Eventos de mantenimiento

### Formato de Webhook:

```json
{
    "event": "user.achievement.earned",
    "timestamp": "2025-08-08T15:30:00Z",
    "data": {
        "user_id": 1,
        "achievement_type": "study_streak",
        "points_earned": 100
    },
    "signature": "sha256=..."
}
```

## 🧪 Testing

### Endpoint de Testing:

```bash
GET /api/test.php
```

Retorna el estado de todas las APIs:

```json
{
    "status": "healthy",
    "version": "2.0",
    "apis": {
        "user_stats": "operational",
        "quiz_results": "operational",
        "notifications": "operational",
        "achievements": "operational"
    },
    "database": "connected",
    "response_time": "25ms"
}
```

### Ejemplos de Uso con JavaScript:

```javascript
// Obtener estadísticas del usuario
async function getUserStats() {
    try {
        const response = await fetch('/ingles/api/get_user_stats.php');
        const data = await response.json();
        
        if (data.success) {
            console.log('Estadísticas:', data);
        } else {
            console.error('Error:', data.error);
        }
    } catch (error) {
        console.error('Error de red:', error);
    }
}

// Enviar resultados de quiz
async function submitQuizResults(quizData) {
    try {
        const response = await fetch('/ingles/api/quiz-result-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(quizData)
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error enviando quiz:', error);
        return { success: false, error: error.message };
    }
}
```

---

**API Documentation v2.0**  
*Última actualización: 8 de agosto de 2025*
