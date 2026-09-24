<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Keluar hanya lewat POST + token CSRF, supaya tidak bisa dipicu dari link sembarang.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    logout_user();
    redirect('login.php');
}

redirect(current_user() !== null ? 'beranda.php' : 'login.php');
