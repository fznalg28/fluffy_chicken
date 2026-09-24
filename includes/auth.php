<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

/* ---------- Sesi ---------- */
ini_set('session.gc_maxlifetime', (string) SESSION_REMEMBER_SECONDS);
ini_set('session.use_strict_mode', '1');
session_name('fluffy_sid');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

/* ---------- Helper tampilan ---------- */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rupiah(int|float $angka): string
{
    return 'Rp' . number_format((float) $angka, 0, ',', '.');
}

function redirect(string $tujuan): void
{
    header('Location: ' . $tujuan);
    exit;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && is_string($_POST['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/* ---------- Pesan sekali tampil (toast) ---------- */
function flash(string $pesan): void
{
    $_SESSION['flash'] = $pesan;
}

function take_flash(): ?string
{
    $pesan = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_string($pesan) ? $pesan : null;
}

/* ---------- Login / logout ---------- */
function current_user(): ?array
{
    static $user = false;

    if ($user !== false) {
        return $user;
    }

    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id)) {
        return $user = null;
    }

    $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $user = ($row ?: null);
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('login.php');
    }
    return $user;
}

function login_user(int $userId, bool $ingat = false): void
{
    session_regenerate_id(true); // cegah session fixation
    $_SESSION['user_id'] = $userId;

    if ($ingat) {
        setcookie(session_name(), session_id(), [
            'expires'  => time() + SESSION_REMEMBER_SECONDS,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}

function initials(string $nama): string
{
    $huruf = '';
    foreach (array_slice(preg_split('/\s+/', trim($nama)) ?: [], 0, 2) as $kata) {
        $huruf .= mb_strtoupper(mb_substr($kata, 0, 1));
    }
    return $huruf !== '' ? $huruf : '?';
}
