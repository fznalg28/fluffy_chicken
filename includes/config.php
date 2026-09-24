<?php
declare(strict_types=1);

/*
 * Pengaturan aplikasi.
 * Sesuaikan dengan MySQL/MariaDB di komputermu.
 * (Default XAMPP/Laragon: user "root", password kosong.)
 */
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'fluffy_chicken';
const DB_USER = 'root';
const DB_PASS = '';

// Lama sesi login kalau "Ingat saya di perangkat ini" dicentang (30 hari).
const SESSION_REMEMBER_SECONDS = 2592000;

date_default_timezone_set('Asia/Jakarta');
