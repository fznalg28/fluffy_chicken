<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_login();
$pdo = db();

/* ---------- Filter dari URL ---------- */
$q = trim((string) ($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 60);

$kategori = (string) ($_GET['kategori'] ?? 'semua');
if ($kategori !== 'semua' && !isset(KATEGORI[$kategori])) {
    $kategori = 'semua';
}

$urutan = [
    'pop'  => ['Terpopuler',        'sold DESC'],
    'rate' => ['Rating tertinggi',  'rating DESC, sold DESC'],
    'low'  => ['Harga terendah',    'price ASC'],
    'high' => ['Harga tertinggi',   'price DESC'],
];
$urut = (string) ($_GET['urut'] ?? 'pop');
if (!isset($urutan[$urut])) {
    $urut = 'pop';
}

/* ---------- Query ---------- */
$where  = [];
$params = [];
if ($kategori !== 'semua') {
    $where[]  = 'category = ?';
    $params[] = $kategori;
}
if ($q !== '') {
    $where[]  = "CONCAT(name, ' ', description) LIKE ?";
    $params[] = '%' . addcslashes($q, '%_\\') . '%';
}
$sql = 'SELECT * FROM products'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY ' . $urutan[$urut][1]; // nilai berasal dari daftar tetap di atas, bukan input user
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produk = $stmt->fetchAll();

$hitung = $pdo->query('SELECT category, COUNT(*) AS jumlah FROM products GROUP BY category')->fetchAll(PDO::FETCH_KEY_PAIR);
$semua  = array_sum($hitung);

function url_produk(string $kategori, string $q, string $urut): string
{
    return 'produk.php?' . http_build_query(array_filter([
        'kategori' => $kategori !== 'semua' ? $kategori : '',
        'q'        => $q,
        'urut'     => $urut !== 'pop' ? $urut : '',
    ], fn($v) => $v !== ''));
}

/* ---------- Tampilan ---------- */
layout_start('Menu', 'produk', $q);
?>
  <div class="ph">
    <div>
      <h1>Menu Fluffy Chicken</h1>
      <p>Digoreng saat dipesan. Pilih favoritmu dan nikmati selagi renyah.</p>
    </div>
  </div>

  <div class="toolbar">
    <div class="chips" role="group" aria-label="Kategori">
      <a class="chip" href="<?= e(url_produk('semua', $q, $urut)) ?>" aria-pressed="<?= $kategori === 'semua' ? 'true' : 'false' ?>">Semua <i><?= (int) $semua ?></i></a>
      <?php foreach (KATEGORI as $kode => $label): ?>
        <a class="chip" href="<?= e(url_produk($kode, $q, $urut)) ?>" aria-pressed="<?= $kategori === $kode ? 'true' : 'false' ?>"><?= e($label) ?> <i><?= (int) ($hitung[$kode] ?? 0) ?></i></a>
      <?php endforeach; ?>
    </div>
    <form class="row" method="get" action="produk.php">
      <?php if ($kategori !== 'semua'): ?><input type="hidden" name="kategori" value="<?= e($kategori) ?>"><?php endif; ?>
      <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
      <label class="muted small" for="sort">Urutkan</label>
      <select class="select" id="sort" name="urut" style="height:42px" onchange="this.form.submit()">
        <?php foreach ($urutan as $kode => [$label]): ?>
          <option value="<?= e($kode) ?>"<?= $urut === $kode ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <div class="pgrid">
    <?php if ($produk): foreach ($produk as $p) { echo kartu_produk($p); }
    else: ?>
      <div class="empty card" style="grid-column:1/-1">
        <div class="big">🔎</div>
        <h3>Menu tidak ditemukan</h3>
        <p>Coba kata kunci lain, atau tampilkan semua menu.</p>
        <a class="btn" href="produk.php">Tampilkan semua menu</a>
      </div>
    <?php endif; ?>
  </div>
<?php layout_end(); ?>
