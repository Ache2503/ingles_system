<?php
// Protección de sesión para administradores
require_once __DIR__ . '/session_protection.php';
requireAdmin();

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir sistema de navegación
require_once __DIR__ . '/navigation.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema de Inglés</title>
    <link rel="stylesheet" href="/ingles/assets/css/style.css">
    <link rel="stylesheet" href="/ingles/assets/css/admin.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .admin-nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .admin-nav a:hover {
            background: rgba(255,255,255,0.2);
        }
        .logout-btn {
            background: #dc3545 !important;
            margin-left: auto;
        }
        .logout-btn:hover {
            background: #c82333 !important;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
            margin-right: 20px;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>🛡️ Panel de Administración</h1>
                
                <nav class="admin-nav">
                    <a href="<?= nav_url('admin_dashboard') ?>">📊 Dashboard</a>
                    <a href="<?= nav_url('admin_topics') ?>">📚 Temas</a>
                    <a href="<?= nav_url('admin_questions') ?>">❓ Preguntas</a>
                    <a href="<?= nav_url('admin_users') ?>">👥 Usuarios</a>
                    <a href="/ingles/admin/verbs.php">📝 Verbos</a>
                    
                    <div class="user-info">
                        <span>👤 <?= $_SESSION['username'] ?? 'Admin' ?></span>
                    </div>
                    
                    <a href="<?= nav_url('logout') ?>" class="logout-btn" 
                       onclick="return confirm('¿Estás seguro de que quieres cerrar sesión?')">
                        🚪 Cerrar Sesión
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container" style="margin-top: 20px;">