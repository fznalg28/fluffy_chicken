<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

/**
 * Kerangka halaman setelah login (sidebar + topbar), sama seperti desain.
 * Pakai:  layout_start('Beranda', 'beranda');  ...isi halaman...  layout_end();
 */
function layout_start(string $judul, string $aktif = 'beranda', string $cari = ''): void
{
    $user = current_user() ?? ['name' => '?'];
    $menu = [
        ['beranda', 'beranda.php', 'Beranda', 'home'],
        ['produk',  'produk.php',  'Produk',  'grid'],
    ];
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($judul) ?> — Fluffy Chicken</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <aside class="side" id="side" aria-label="Navigasi utama">
    <div class="brand"><img src="assets/img/logo.png" alt="Fluffy Chicken — Crispy, Juicy dan Fluffy"></div>
    <nav class="nav">
      <?php foreach ($menu as [$kode, $url, $label, $ikon]): ?>
        <a href="<?= e($url) ?>"<?= $kode === $aktif ? ' aria-current="page"' : '' ?>><?= ic($ikon, 20) ?><span><?= e($label) ?></span></a>
      <?php endforeach; ?>
      <form method="post" action="logout.php">
        <?= csrf_field() ?>
        <button type="submit"><?= ic('logout', 20) ?><span>Keluar</span></button>
      </form>
    </nav>
    <div class="side-foot">
      <div class="help-box"><b>Butuh bantuan?</b>Tim kami siap membalas setiap hari, 09.00–22.00.</div>
    </div>
  </aside>
  <div class="scrim-nav" id="scrimNav"></div>
  <div class="main">
    <header class="top">
      <button class="burger" id="burger" type="button" aria-label="Buka menu"><?= ic('menu', 22) ?></button>
      <form class="search" role="search" action="produk.php" method="get">
        <span><?= ic('search', 18) ?></span>
        <input type="search" name="q" value="<?= e($cari) ?>" placeholder="Cari menu: ayam, paket, minuman…" aria-label="Cari menu" maxlength="60">
      </form>
      <div class="top-r">
        <span class="avatar" title="<?= e($user['name']) ?>"><?= e(initials($user['name'])) ?></span>
      </div>
    </header>
    <main class="page">
<?php
}

function layout_end(): void
{
    $flash = take_flash();
    ?>
      <div class="foot">Fluffy Chicken · Crispy, Juicy dan Fluffy</div>
    </main>
  </div>
</div>
<div id="toast" role="status" aria-live="polite"></div>
<script>
(function () {
  var body = document.body;
  document.getElementById('burger').addEventListener('click', function () { body.classList.toggle('nav-open'); });
  document.getElementById('scrimNav').addEventListener('click', function () { body.classList.remove('nav-open'); });

  var toast = document.getElementById('toast'), timer;
  var CEKLIS = <?= json_encode(ic('check', 18)) ?>;
  function tampilkan(pesan) {
    toast.innerHTML = CEKLIS + '<span></span>';
    toast.lastChild.textContent = pesan;
    toast.classList.add('show');
    clearTimeout(timer);
    timer = setTimeout(function () { toast.classList.remove('show'); }, 2400);
  }

  document.querySelectorAll('[data-toast]').forEach(function (el) {
    el.addEventListener('click', function () { tampilkan(el.dataset.toast); });
  });
  document.querySelectorAll('[data-copy]').forEach(function (el) {
    el.addEventListener('click', function () {
      var kode = el.dataset.copy;
      if (navigator.clipboard) {
        navigator.clipboard.writeText(kode).then(
          function () { tampilkan('Kode ' + kode + ' disalin'); },
          function () { tampilkan('Salin kode: ' + kode); }
        );
      } else { tampilkan('Salin kode: ' + kode); }
    });
  });
<?php if ($flash !== null): ?>
  tampilkan(<?= json_encode($flash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
<?php endif; ?>
})();
</script>
</body>
</html>
<?php
}
