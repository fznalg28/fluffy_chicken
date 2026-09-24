<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Sudah login? Langsung ke beranda.
if (current_user() !== null) {
    redirect('beranda.php');
}

// Hash palsu supaya waktu proses sama, baik email ada maupun tidak (cegah user enumeration).
const HASH_PALSU = '$2y$10$jX/Q8Lpj/6nuc0k59107D.2XsK9plsHfDztRBWrnGfyc1Ygc.6jSm';

$mode  = (($_POST['aksi'] ?? $_GET['mode'] ?? '') === 'daftar') ? 'daftar' : 'masuk';
$daftar = $mode === 'daftar';
$error = '';
$nama  = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = trim((string) ($_POST['nama'] ?? ''));
    $email      = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password   = (string) ($_POST['password'] ?? '');
    $konfirmasi = (string) ($_POST['konfirmasi'] ?? '');
    $ingat      = isset($_POST['ingat']);

    if (!csrf_valid()) {
        $error = 'Sesi habis. Muat ulang halaman lalu coba lagi.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = 'Masukkan email yang valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal 6 karakter.';
    } elseif (strlen($password) > 72) {
        $error = 'Kata sandi maksimal 72 karakter.';
    } elseif ($daftar) {
        if ($nama === '' || mb_strlen($nama) > 100) {
            $error = $nama === '' ? 'Nama lengkap belum diisi.' : 'Nama terlalu panjang (maksimal 100 karakter).';
        } elseif ($password !== $konfirmasi) {
            $error = 'Konfirmasi kata sandi tidak sama.';
        } else {
            try {
                $stmt = db()->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
                $stmt->execute([$nama, $email, password_hash($password, PASSWORD_DEFAULT)]);
                login_user((int) db()->lastInsertId());
                flash('Akun berhasil dibuat. Selamat datang!');
                redirect('beranda.php');
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') { // email sudah dipakai (UNIQUE)
                    $error = 'Email sudah terdaftar. Silakan masuk.';
                } else {
                    error_log('Daftar gagal: ' . $ex->getMessage());
                    $error = 'Terjadi kesalahan di server. Coba lagi sebentar.';
                }
            }
        }
    } else {
        $stmt = db()->prepare('SELECT id, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $cocok = password_verify($password, $user['password'] ?? HASH_PALSU);
        if ($user && $cocok) {
            login_user((int) $user['id'], $ingat);
            redirect('beranda.php');
        }
        $error = 'Email atau kata sandi salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= $daftar ? 'Daftar' : 'Masuk' ?> — Fluffy Chicken</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div id="authScreen" class="auth-screen">
  <section class="auth-box">
    <div class="auth-brand">
      <img src="assets/img/logo.png" alt="Fluffy Chicken">
      <h1>Fluffy Chicken</h1>
      <p>Crispy, juicy dan fluffy. Masuk untuk melanjutkan pesanan favoritmu.</p>
    </div>
    <div class="auth-form">
      <div class="auth-tabs">
        <a href="login.php" class="<?= $daftar ? '' : 'active' ?>">Masuk</a>
        <a href="login.php?mode=daftar" class="<?= $daftar ? 'active' : '' ?>">Daftar</a>
      </div>

      <h2><?= $daftar ? 'Buat akun baru ✨' : 'Selamat datang 👋' ?></h2>
      <p class="auth-sub"><?= $daftar
          ? 'Daftar dulu supaya kamu bisa lanjut pesan di Fluffy Chicken.'
          : 'Masuk ke akunmu untuk mulai pesan ayam favorit.' ?></p>
      <?php if ($error !== ''): ?>
        <div class="auth-error show" role="alert"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="login.php<?= $daftar ? '?mode=daftar' : '' ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="<?= $daftar ? 'daftar' : 'masuk' ?>">

        <?php if ($daftar): ?>
        <div class="field">
          <label for="authName">Nama lengkap</label>
          <input id="authName" name="nama" type="text" maxlength="100" autocomplete="name"
                 placeholder="Masukkan nama lengkap" value="<?= e($nama) ?>">
        </div>
        <?php endif; ?>

        <div class="field">
          <label for="authEmail">Email</label>
          <input id="authEmail" name="email" type="email" maxlength="150" autocomplete="email"
                 placeholder="contoh@email.com" value="<?= e($email) ?>" required>
        </div>

        <div class="field auth-pass">
          <label for="authPassword">Kata sandi</label>
          <input id="authPassword" name="password" type="password" maxlength="72"
                 autocomplete="<?= $daftar ? 'new-password' : 'current-password' ?>"
                 placeholder="Minimal 6 karakter" required>
          <button class="auth-eye" type="button" id="authEye" aria-label="Tampilkan kata sandi">◉</button>
        </div>

        <?php if ($daftar): ?>
        <div class="field">
          <label for="authConfirm">Konfirmasi kata sandi</label>
          <input id="authConfirm" name="konfirmasi" type="password" maxlength="72"
                 autocomplete="new-password" placeholder="Ulangi kata sandi">
        </div>
        <?php else: ?>
        <label class="auth-check">
          <input type="checkbox" name="ingat" checked>
          Ingat saya di perangkat ini
        </label>
        <?php endif; ?>

        <button class="btn auth-submit" type="submit"><?= $daftar ? 'Daftar sekarang' : 'Masuk' ?></button>
      </form>

      <p class="auth-note">
        <?php if ($daftar): ?>
          Sudah punya akun? <a href="login.php">Masuk</a>
        <?php else: ?>
          Belum punya akun? <a href="login.php?mode=daftar">Daftar sekarang</a>
        <?php endif; ?>
      </p>
    </div>
  </section>
</div>
<script>
document.getElementById('authEye').addEventListener('click', function () {
  var input = document.getElementById('authPassword');
  var tampil = input.type === 'password';
  input.type = tampil ? 'text' : 'password';
  this.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
});
</script>
</body>
</html>
