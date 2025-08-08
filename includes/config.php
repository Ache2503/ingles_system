<?php
// includes/config.php

// Inicia la sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define la URL base solo si no está definida
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/ingles');
}