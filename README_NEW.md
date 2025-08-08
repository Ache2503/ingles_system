# 📚 Sistema de Aprendizaje de Inglés

Sistema web completo para el aprendizaje de inglés con panel de administración, gamificación y analytics.

## 🚀 Instalación Rápida

### 1. Requisitos
- PHP 7.4+
- MySQL 8.0+
- Apache (XAMPP recomendado)

### 2. Configurar
```bash
# Clonar archivos en directorio web
cp -r ingles/ /var/www/html/

# O en XAMPP Windows
# Copiar a: C:/xampp/htdocs/
```

### 3. Instalar Base de Datos
```bash
# Instalación automática
php database/setup_database.php

# O manual con phpMyAdmin
# Importar: database/create_database.sql
```

### 4. Acceder al Sistema
```
http://localhost/ingles
```

**Usuario Admin por defecto:**
- Username: `admin`
- Password: `password`

## ✨ Características

### 👨‍🎓 Para Estudiantes
- ✅ Temas de gramática y vocabulario
- ✅ Quizzes interactivos con feedback
- ✅ Verbos irregulares con ejemplos
- ✅ Sistema de progreso y logros
- ✅ Gamificación con puntos y niveles

### 👨‍💼 Para Administradores
- ✅ Panel de administración completo
- ✅ Gestión de usuarios y contenido
- ✅ Analytics y estadísticas en tiempo real
- ✅ Sistema de respaldos automáticos
- ✅ Monitoreo de salud del sistema
- ✅ Notificaciones masivas

### 🛠️ Técnico
- ✅ PHP 7.4+ con PDO
- ✅ MySQL con 25+ tablas
- ✅ Autenticación por roles
- ✅ Responsive design
- ✅ Sin dependencias externas

## 📋 Estructura del Proyecto

```
ingles/
├── 📁 admin/                  # Panel de administración
│   ├── index.php              # Dashboard
│   ├── analytics.php          # Analytics y métricas
│   ├── backup.php             # Sistema de respaldos
│   ├── system_health.php      # Monitoreo del sistema
│   └── [gestión de contenido]
├── 📁 database/               # Scripts de BD
│   ├── setup_database.php     # Instalador automático
│   └── create_database.sql    # Script SQL principal
├── 📁 includes/               # Archivos core
│   ├── config.php             # Configuración
│   ├── db.php                 # Conexión BD
│   └── auth.php               # Autenticación
├── 📁 tests/                  # Herramientas de testing
└── [páginas del sistema]
```

## 🔧 Configuración

### Base de Datos (includes/db.php)
```php
$host = 'localhost';
$dbname = 'ingles_system';
$username = 'root';
$password = '';
```

### URL Base (includes/config.php)
```php
define('BASE_URL', 'http://localhost/ingles');
```

## 🧪 Verificación del Sistema

### Verificar Instalación
```
http://localhost/ingles/system_test.php
```

### Verificar Salud del Sistema
```
http://localhost/ingles/admin/system_health.php
```

### Centro de Control de BD
```
http://localhost/ingles/tests/database_control_center.php
```

## 📊 Panel de Administración

Acceder con credenciales de admin:
```
http://localhost/ingles/admin/
```

### Funcionalidades Admin:
- **Dashboard**: Estadísticas en tiempo real
- **Usuarios**: Gestión completa de usuarios
- **Contenido**: CRUD de temas, preguntas y verbos
- **Analytics**: Métricas detalladas del sistema
- **Backup**: Respaldos automáticos de MySQL
- **Salud**: Monitoreo de componentes del sistema
- **Notificaciones**: Sistema de mensajería masiva

## 🗄️ Base de Datos

### Tablas Principales:
- **users**: Usuarios del sistema
- **topics**: Temas de estudio
- **questions**: Preguntas de quiz
- **irregular_verbs**: Verbos irregulares
- **user_progress**: Progreso de usuarios
- **quiz_history**: Historial de quizzes

### Tablas de Gamificación:
- **user_achievements**: Logros obtenidos
- **user_gamification**: Puntos y niveles
- **achievement_config**: Configuración de logros

### Tablas del Sistema:
- **notifications**: Notificaciones
- **user_settings**: Configuraciones de usuario
- **study_sessions**: Sesiones de estudio
- **[20+ tablas adicionales]**

## 🔒 Seguridad

### Niveles de Acceso:
- **Público**: index.php, login.php, register.php
- **Usuario**: topics.php, practice.php, progress.php
- **Admin**: admin/* (panel completo)

### Protección Implementada:
- Hash bcrypt para contraseñas
- Validación de sesiones PHP
- Protección CSRF
- Sanitización de inputs
- Roles de usuario granulares

## 🚨 Solución de Problemas

### Error de Conexión BD:
1. Verificar credenciales en `includes/db.php`
2. Asegurar que MySQL esté ejecutándose
3. Verificar permisos de usuario de BD

### Error de Permisos:
```bash
# Linux/Mac
chmod 755 -R ingles/
chmod 777 ingles/backups/
chmod 777 ingles/logs/

# Windows: Verificar permisos de Apache
```

### Verificar Estado:
```bash
php system_test.php
php tests/system_verifier.php
```

## 📈 Mantenimiento

### Backup Automático:
- Panel admin → Backup → Crear Backup
- Programar con crontab: `0 2 * * * php admin/backup.php`

### Monitoreo:
- **system_health.php**: Estado en tiempo real
- **analytics.php**: Métricas de uso
- **logs/**: Archivos de log del sistema

### Actualizaciones:
```bash
php tests/database_updater.php  # Actualizar BD
php tests/system_verifier.php   # Verificar integridad
```

## 📖 Documentación Completa

Para documentación detallada, ver:
- [DOCUMENTATION.md](DOCUMENTATION.md) - Documentación completa del sistema
- [database/README.md](database/README.md) - Documentación específica de BD

## 🎯 Roadmap

### Funcionalidades Implementadas ✅
- [x] Sistema de autenticación completo
- [x] Panel de administración
- [x] Gestión de contenido educativo
- [x] Sistema de quizzes
- [x] Gamificación con logros
- [x] Analytics y estadísticas
- [x] Sistema de respaldos
- [x] Monitoreo de salud del sistema

### Próximas Mejoras 📋
- [ ] API REST para móviles
- [ ] Sistema de chat en tiempo real
- [ ] Integración con redes sociales
- [ ] Reportes PDF avanzados
- [ ] Sistema de certificados

## 📞 Soporte

### Archivos de Diagnóstico:
- `system_test.php` - Test general del sistema
- `admin/system_health.php` - Monitoreo en tiempo real
- `tests/comprehensive_test.php` - Suite completa de pruebas

### Logs del Sistema:
- `logs/system.log` - Logs generales
- `logs/auth.log` - Logs de autenticación
- `logs/error.log` - Logs de errores

---

**Sistema de Aprendizaje de Inglés v2.0**  
*Desarrollado con PHP, MySQL y mucho ❤️*
