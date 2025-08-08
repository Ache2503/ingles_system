# 📚 Índice de Documentación - Sistema de Aprendizaje de Inglés

Bienvenido al centro de documentación completo del sistema de aprendizaje de inglés. Aquí encontrarás toda la información necesaria para instalar, usar, desarrollar y mantener el sistema.

## 📖 Documentación Principal

### 🚀 Para Empezar
- **[README.md](README_NEW.md)** - Guía de instalación rápida y características principales
- **[DOCUMENTATION.md](DOCUMENTATION.md)** - Documentación técnica completa del sistema
- **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)** - Documentación completa de APIs y endpoints

### 🗄️ Base de Datos
- **[database/README.md](database/README.md)** - Documentación específica de la base de datos
- **[database/create_database.sql](database/create_database.sql)** - Script principal de creación
- **[database/improvements.sql](database/improvements.sql)** - Mejoras y actualizaciones

## 🎯 Guías por Rol

### 👨‍🎓 Para Estudiantes
**Páginas Principales:**
- `index.php` - Página principal del sistema
- `topics.php` - Lista de temas disponibles
- `topic_detail.php` - Detalle y estudio de temas
- `practice.php` - Sistema de práctica y quizzes
- `progress.php` - Seguimiento de progreso personal

**Características:**
- ✅ Sistema de quizzes interactivos
- ✅ Seguimiento de progreso automático
- ✅ Gamificación con puntos y logros
- ✅ Verbos irregulares con ejemplos
- ✅ Recomendaciones personalizadas

### 👨‍🏫 Para Profesores
**Acceso:** Mismo sistema que estudiantes + permisos de contenido

**Funcionalidades adicionales:**
- ✅ Creación y edición de temas
- ✅ Gestión de preguntas de quiz
- ✅ Seguimiento de estudiantes
- ✅ Reportes de progreso

### 👨‍💼 Para Administradores
**Panel de Administración:** `/admin/`

**Documentación específica:**
- **[admin/index.php](admin/index.php)** - Dashboard principal
- **[admin/analytics.php](admin/analytics.php)** - Analytics y métricas
- **[admin/system_health.php](admin/system_health.php)** - Monitoreo del sistema
- **[admin/backup.php](admin/backup.php)** - Sistema de respaldos

## 🛠️ Guías Técnicas

### 🔧 Instalación y Configuración

#### Instalación Automática
```bash
# 1. Configurar archivos
cp -r ingles/ /var/www/html/

# 2. Instalar base de datos
php database/setup_database.php

# 3. Verificar instalación
http://localhost/ingles/system_test.php
```

#### Configuración Manual
1. **Base de Datos**: Editar `includes/db.php`
2. **URL Base**: Configurar `includes/config.php`
3. **Permisos**: Configurar carpetas `backups/`, `logs/`, `temp/`

### 🏗️ Arquitectura del Sistema

```
📊 Arquitectura en Capas:
├── Presentación      → Páginas PHP con HTML/CSS/JS
├── Lógica de Negocio → Funciones en includes/
├── APIs             → Endpoints RESTful en api/
├── Acceso a Datos   → PDO + MySQL
└── Configuración    → includes/config.php
```

### 🗄️ Estructura de Base de Datos

**Tablas Core:**
- `users` - Usuarios del sistema
- `topics` - Temas de estudio  
- `questions` - Preguntas de quiz
- `irregular_verbs` - Verbos irregulares
- `user_progress` - Progreso de usuarios
- `quiz_history` - Historial de quizzes

**Tablas de Gamificación:**
- `user_achievements` - Logros obtenidos
- `user_gamification` - Puntos y niveles
- `achievement_config` - Configuración de logros

**Tablas del Sistema:**
- `notifications` - Notificaciones
- `user_settings` - Configuraciones
- `study_sessions` - Sesiones de estudio
- `[20+ tablas adicionales]`

## 🔌 APIs y Desarrollo

### Endpoints Principales

#### APIs de Usuario
- `GET /api/get_user_stats.php` - Estadísticas del usuario
- `POST /api/update_progress.php` - Actualizar progreso
- `GET /api/get_notifications.php` - Obtener notificaciones

#### APIs de Quiz
- `POST /api/quiz-result-api.php` - Procesar resultados
- `GET /api/check_achievements.php` - Verificar logros
- `GET /api/get_questions.php` - Obtener preguntas

#### APIs de Contenido
- `GET /api/get_topics.php` - Lista de temas
- `GET /api/get_topic_detail.php` - Detalle de tema
- `GET /api/get_leaderboard.php` - Tabla de clasificación

### Ejemplos de Uso

```javascript
// Obtener estadísticas
const stats = await fetch('/ingles/api/get_user_stats.php')
    .then(r => r.json());

// Enviar quiz
const result = await fetch('/ingles/api/quiz-result-api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(quizData)
}).then(r => r.json());
```

## 🔒 Seguridad

### Sistema de Autenticación
- **Protección por sesiones PHP**
- **Hash bcrypt para contraseñas**
- **Validación de roles por página**
- **Protección CSRF**

### Niveles de Acceso
- **Público**: index.php, login.php, register.php
- **Usuario**: topics.php, practice.php, progress.php
- **Admin**: admin/* (panel completo)

### Implementación
```php
// Proteger página de usuario
require_once 'includes/session_protection.php';
requireLogin();

// Proteger página de admin
require_once 'includes/session_protection.php';
requireAdmin();
```

## 🧪 Testing y Verificación

### Herramientas de Diagnóstico

#### Tests Automáticos
- **[system_test.php](system_test.php)** - Test general del sistema
- **[tests/system_verifier.php](tests/system_verifier.php)** - Verificador completo
- **[tests/comprehensive_test.php](tests/comprehensive_test.php)** - Suite de pruebas

#### Monitoreo en Tiempo Real
- **[admin/system_health.php](admin/system_health.php)** - Estado del sistema
- **[tests/database_control_center.php](tests/database_control_center.php)** - Centro de control BD

### Comandos de Verificación
```bash
# Verificar estado general
php system_test.php

# Verificar base de datos
php tests/database_updater.php

# Verificar integridad
php tests/system_verifier.php
```

## 📈 Mantenimiento

### Respaldos Automáticos
- **Panel**: admin/backup.php
- **Automático**: Programar con crontab
- **Restauración**: Desde panel de admin

### Monitoreo del Sistema
- **Salud**: admin/system_health.php
- **Analytics**: admin/analytics.php
- **Logs**: carpeta logs/

### Actualizaciones
```bash
php tests/database_updater.php  # Actualizar BD
php tests/system_verifier.php   # Verificar integridad
```

## 🚨 Solución de Problemas

### Problemas Comunes

#### Error de Conexión BD
1. Verificar credenciales en `includes/db.php`
2. Asegurar que MySQL esté ejecutándose
3. Verificar permisos de usuario de BD

#### Error de Permisos
```bash
# Linux/Mac
chmod 755 -R ingles/
chmod 777 ingles/backups/
chmod 777 ingles/logs/

# Windows: Verificar permisos de Apache
```

#### Error de Sesiones
1. Verificar configuración PHP de sesiones
2. Comprobar permisos de directorio de sesiones
3. Revisar `includes/session_protection.php`

### Logs del Sistema
- `logs/system.log` - Logs generales
- `logs/auth.log` - Logs de autenticación  
- `logs/error.log` - Logs de errores PHP
- `logs/admin.log` - Logs de actividad admin

## 📞 Recursos Adicionales

### Archivos de Configuración
- `includes/config.php` - Configuración principal
- `includes/db.php` - Conexión a base de datos
- `includes/auth.php` - Funciones de autenticación

### Scripts de Utilidad
- `database/setup_database.php` - Instalador automático
- `database/create_improvements.php` - Aplicar mejoras
- `temp/fix_includes.php` - Reparar rutas de includes

### Documentación Técnica Extendida
- **Arquitectura MVC**: Patrón de diseño implementado
- **APIs RESTful**: Diseño de endpoints
- **Seguridad**: Implementación de protecciones
- **Optimización**: Técnicas de rendimiento aplicadas

## 🎯 Roadmap y Futuras Mejoras

### Implementado ✅
- [x] Sistema de autenticación completo
- [x] Panel de administración
- [x] Sistema de quizzes
- [x] Gamificación
- [x] Analytics
- [x] Respaldos automáticos
- [x] Monitoreo del sistema

### En Desarrollo 🚧
- [ ] API REST para aplicaciones móviles
- [ ] Sistema de chat en tiempo real
- [ ] Integración con redes sociales
- [ ] Reportes PDF avanzados
- [ ] Sistema de certificados

### Futuro 📋
- [ ] Aplicación móvil nativa
- [ ] Inteligencia artificial para recomendaciones
- [ ] Sistema de videoconferencias
- [ ] Marketplace de contenido
- [ ] Integración con LMS externos

## 📖 Convenciones de Desarrollo

### Estándares de Código
- **PHP**: PSR-12 coding standard
- **SQL**: Nomenclatura snake_case
- **JavaScript**: Estándar ES6+
- **CSS**: Metodología BEM

### Estructura de Archivos
```php
<?php
// Encabezado de archivo estándar
/**
 * [Nombre del archivo] - [Descripción]
 * [Funcionalidad principal]
 */

// Includes en orden
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Lógica principal
// ... código ...

// Inclusión de template
require_once __DIR__ . '/includes/header.php';
?>
<!-- HTML content -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>
```

### Commits y Versionado
- **Formato**: `tipo(scope): descripción`
- **Tipos**: feat, fix, docs, style, refactor, test
- **Versionado**: Semantic Versioning (SemVer)

---

## 📞 Contacto y Soporte

### Canales de Soporte
- **Documentación**: Esta misma documentación
- **Tests automáticos**: system_test.php
- **Logs del sistema**: carpeta logs/
- **Herramientas de diagnóstico**: tests/

### Reportar Problemas
1. Ejecutar `system_test.php` para diagnóstico
2. Revisar logs en carpeta `logs/`
3. Verificar con `admin/system_health.php`
4. Documentar pasos para reproducir el problema

---

**Documentación Completa del Sistema de Aprendizaje de Inglés v2.0**  
*Índice creado el 8 de agosto de 2025*  
*Sistema desarrollado con PHP, MySQL y dedicación ❤️*
