<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$user = require_login();
$pdo  = db();
$uid  = (int) $user['id'];

/* ---------- Data dari database ---------- */

// Pesanan aktif (sedang dimasak / diantar)
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? AND status IN ('Diproses','Diantar') ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$uid]);
$aktif = $stmt->fetch() ?: null;

// 3 pesanan terakhir
$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
$stmt->execute([$uid]);
$terakhir = $stmt->fetchAll();

$idPesanan = array_column($terakhir, 'id');
if ($aktif) {
    $idPesanan[] = $aktif['id'];
}
$itemPesanan = ambil_item_pesanan(array_unique($idPesanan));

// Ringkasan bulan ini
$stmt = $pdo->prepare("SELECT COUNT(*) AS jumlah, COALESCE(SUM(total), 0) AS belanja
                         FROM orders
                        WHERE user_id = ? AND status <> 'Dibatalkan'
                          AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$stmt->execute([$uid]);
$bulanIni = $stmt->fetch();

// Fluffy Points: setiap belanja Rp1.000 (pesanan selesai) = 1 poin
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = ? AND status = 'Selesai'");
$stmt->execute([$uid]);
$poin       = intdiv((int) $stmt->fetchColumn(), 1000);
$targetPoin = 2000; // tier Golden Wing
$persen     = min(100, (int) round($poin / $targetPoin * 100));

// Voucher yang masih berlaku
$vouchers = $pdo->query('SELECT * FROM vouchers WHERE is_active = 1 AND (valid_until IS NULL OR valid_until >= CURDATE()) ORDER BY id')->fetchAll();
$promo    = $vouchers[0] ?? null;

// Grafik belanja 6 bulan terakhir
$seri = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime(date('Y-m-01') . " -$i month");
    $seri[date('Y-m', $ts)] = ['label' => BULAN_PENDEK[(int) date('n', $ts) - 1], 'total' => 0];
}
$stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS bulan, SUM(total) AS total
                         FROM orders
                        WHERE user_id = ? AND status <> 'Dibatalkan' AND created_at >= ?
                        GROUP BY bulan");
$stmt->execute([$uid, array_key_first($seri) . '-01']);
foreach ($stmt as $baris) {
    if (isset($seri[$baris['bulan']])) {
        $seri[$baris['bulan']]['total'] = (int) $baris['total'];
    }
}
$maks = max(1, ...array_column($seri, 'total'));

// Menu favorit: yang paling sering dipesan, sisanya diisi menu terlaris
$stmt = $pdo->prepare("SELECT p.* FROM products p
                         JOIN (SELECT oi.product_id, SUM(oi.qty) AS jumlah
                                 FROM order_items oi JOIN orders o ON o.id = oi.order_id
                                WHERE o.user_id = ? AND o.status <> 'Dibatalkan' AND oi.product_id IS NOT NULL
                                GROUP BY oi.product_id) f ON f.product_id = p.id
                        ORDER BY f.jumlah DESC, p.sold DESC LIMIT 4");
$stmt->execute([$uid]);
$favorit = $stmt->fetchAll();
if (count($favorit) < 4) {
    $sudah = array_column($favorit, 'id') ?: [0];
    $in    = implode(',', array_fill(0, count($sudah), '?'));
    $stmt  = $pdo->prepare("SELECT * FROM products WHERE id NOT IN ($in) ORDER BY sold DESC LIMIT " . (4 - count($favorit)));
    $stmt->execute($sudah);
    $favorit = array_merge($favorit, $stmt->fetchAll());
}

$namaDepan = explode(' ', trim($user['name']))[0];

/* ---------- Tampilan ---------- */
layout_start('Beranda', 'beranda');
?>
  <section class="hero" aria-label="Sambutan">
    <div>
      <span class="open"><i></i>Outlet buka hari ini, 10.00–22.00</span>
      <h2>Halo, <?= e($namaDepan) ?>. Ayam panasmu sudah ditunggu.</h2>
      <p>Renyah di luar, juicy di dalam, dan selalu digoreng saat kamu memesan.</p>
      <div class="cta">
        <a class="btn gold" href="produk.php"><?= ic('bag', 18) ?>Pesan sekarang</a>
      </div>
    </div>
    <img class="logo" src="assets/img/logo.png" alt="">
    <?php include __DIR__ . '/includes/hero_wave.php'; ?>
  </section>

  <div class="card strip" style="margin-top:20px">
    <div><span class="ico"><?= ic('receipt', 22) ?></span><div><b><?= (int) $bulanIni['jumlah'] ?></b><span>Pesanan bulan ini</span></div></div>
    <div><span class="ico"><?= ic('wallet', 22) ?></span><div><b><?= e(rupiah((int) $bulanIni['belanja'])) ?></b><span>Belanja bulan ini</span></div></div>
    <div><span class="ico"><?= ic('flame', 22) ?></span><div><b><?= e(number_format($poin, 0, ',', '.')) ?></b><span>Fluffy Points</span></div></div>
    <div><span class="ico"><?= ic('tag', 22) ?></span><div><b><?= count($vouchers) ?></b><span>Voucher aktif</span></div></div>
  </div>

  <?php if ($promo): ?>
  <div class="promo" style="margin-top:20px">
    <?= ic('gift', 26) ?>
    <p><?= e($promo['title']) ?> — <?= e($promo['description']) ?>.</p>
    <span class="code"><?= e($promo['code']) ?></span>
    <button class="btn sm" type="button" data-copy="<?= e($promo['code']) ?>"><?= ic('copy', 16) ?>Salin kode</button>
  </div>
  <?php endif; ?>

  <div class="grid g2" style="margin-top:20px">
    <section class="card">
      <div class="card-h"><h2>Pesanan aktif</h2></div>
      <?php if ($aktif): ?>
        <div class="row between wrap">
          <div><b><?= e($aktif['id']) ?></b><div class="muted small"><?= e(ringkasan_item($itemPesanan[$aktif['id']] ?? [])) ?></div></div>
          <?= lencana_status($aktif['status']) ?>
        </div>
        <div style="margin-top:22px"><?= timeline_pesanan($aktif['status']) ?></div>
        <div class="hr"></div>
        <div class="row between wrap">
          <span class="muted small"><?= ic('clock', 15) ?> Estimasi tiba <?= $aktif['status'] === 'Diantar' ? 'sekitar 12 menit lagi' : 'sekitar 30 menit lagi' ?></span>
        </div>
      <?php else: ?>
        <div class="empty" style="padding:24px">
          <div class="big">🍗</div>
          <h3>Belum ada pesanan aktif</h3>
          <p>Pesanan yang sedang dimasak atau diantar akan muncul di sini.</p>
          <a class="btn" href="produk.php">Lihat menu</a>
        </div>
      <?php endif; ?>
    </section>

    <section class="card">
      <div class="card-h"><h2>Fluffy Points</h2><span class="badge"><?= $poin >= $targetPoin ? 'Golden Wing' : 'Crispy Member' ?></span></div>
      <div class="row" style="gap:18px">
        <div class="ring" style="--p:<?= $persen ?>"><div><span><b><?= e(number_format($poin, 0, ',', '.')) ?></b><span>poin</span></span></div></div>
        <div>
          <?php if ($poin < $targetPoin): ?>
            <b><?= e(number_format($targetPoin - $poin, 0, ',', '.')) ?> poin lagi</b>
            <p class="muted small" style="margin-top:2px">untuk naik ke tier Golden Wing dan dapat gratis ongkir tiap Jumat.</p>
          <?php else: ?>
            <b>Kamu sudah di tier Golden Wing</b>
            <p class="muted small" style="margin-top:2px">Nikmati gratis ongkir tiap Jumat.</p>
          <?php endif; ?>
        </div>
      </div>
      <button class="btn gold sm block" type="button" style="margin-top:16px" data-toast="Penukaran poin segera hadir">Tukar poin</button>
    </section>
  </div>

  <div class="grid g2" style="margin-top:20px">
    <section class="card">
      <div class="card-h"><h2>Menu favoritmu</h2><a class="link" href="produk.php">Semua menu</a></div>
      <div class="pm-grid"><?php foreach ($favorit as $p) { echo kartu_mini($p); } ?></div>
    </section>

    <section class="card">
      <div class="card-h"><h2>Belanja 6 bulan</h2></div>
      <div class="bars" role="img" aria-label="Grafik belanja enam bulan terakhir">
        <?php $terakhirKey = array_key_last($seri); foreach ($seri as $kunci => $b): ?>
          <div class="b<?= $kunci === $terakhirKey ? ' cur' : '' ?>">
            <em><?= (int) round($b['total'] / 1000) ?>rb</em>
            <i style="height:<?= max(6, (int) round($b['total'] / $maks * 100)) ?>%"></i><?= e($b['label']) ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <section class="card" style="margin-top:20px">
    <div class="card-h"><h2>Pesanan terakhir</h2></div>
    <div class="olist">
      <?php if ($terakhir): foreach ($terakhir as $o) { echo baris_pesanan($o, $itemPesanan[$o['id']] ?? []); }
      else: ?>
        <p class="muted">Belum ada pesanan. Yuk pilih menu favoritmu!</p>
      <?php endif; ?>
    </div>
  </section>
<?php layout_end(); ?>
