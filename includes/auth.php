<?php
// Authentication helper functions

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['supervisor_id'])) {
        header('Location: ../login.php');
        exit();
    }
}

function getSupervisorName() {
    return $_SESSION['supervisor_name'] ?? 'Supervisor';
}

function getSupervisorId() {
    return $_SESSION['supervisor_id'] ?? null;
}

function getSupervisorEmail() {
    return $_SESSION['supervisor_email'] ?? '';
}

function isLoggedIn() {
    return isset($_SESSION['supervisor_id']);
}
