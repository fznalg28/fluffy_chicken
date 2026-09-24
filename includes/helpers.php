<?php
declare(strict_types=1);

/* Potongan tampilan yang diterjemahkan dari desain (pcard, mini, orow, timeline, dst). */

const KATEGORI = ['ayam' => 'Ayam', 'paket' => 'Paket', 'pelengkap' => 'Pelengkap', 'minuman' => 'Minuman'];

const NADA_KATEGORI = [
    'ayam'      => ['#FFC629', '#F26A1B'],
    'paket'     => ['#E5252B', '#F26A1B'],
    'pelengkap' => ['#FFD84D', '#FF9F1C'],
    'minuman'   => ['#FFB347', '#D8202A'],
];

const BULAN_PENDEK = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

const LANGKAH_PESANAN = ['Pesanan diterima', 'Sedang dimasak', 'Diantar kurir', 'Tiba di tujuan'];

function tanggal_indo(string $waktu): string
{
    $t = strtotime($waktu);
    if ($t === false) {
        return $waktu;
    }
    if (date('Y-m-d', $t) === date('Y-m-d')) {
        return 'Hari ini, ' . date('H.i', $t);
    }
    return date('j', $t) . ' ' . BULAN_PENDEK[(int) date('n', $t) - 1] . ' ' . date('Y, H.i', $t);
}

function label_terjual(int $jumlah): string
{
    return $jumlah >= 1000 ? number_format($jumlah / 1000, 1, ',', '') . 'rb' : (string) $jumlah;
}

function api_pedas(int $level): string
{
    if ($level < 1) {
        return '';
    }
    return '<span class="flames" title="Level pedas" aria-label="Level pedas ' . $level . ' dari 3">'
        . str_repeat(ic('flame', 14), min($level, 3)) . '</span>';
}

function gaya_nada(string $kategori): string
{
    $n = NADA_KATEGORI[$kategori] ?? NADA_KATEGORI['ayam'];
    return 'style="--t1:' . $n[0] . ';--t2:' . $n[1] . '"';
}

function lencana_status(string $status): string
{
    $peta = [
        'Menunggu pembayaran' => ['badge red',    'Menunggu pembayaran'],
        'Diproses'            => ['badge',        'Sedang dimasak'],
        'Diantar'             => ['badge orange', 'Sedang diantar'],
        'Selesai'             => ['badge green',  'Selesai'],
        'Dibatalkan'          => ['badge grey',   'Dibatalkan'],
    ];
    [$kelas, $label] = $peta[$status] ?? ['badge grey', $status];
    return '<span class="' . $kelas . '"><i class="status-dot"></i>' . e($label) . '</span>';
}

function pesanan_aktif(string $status): bool
{
    return $status === 'Diproses' || $status === 'Diantar';
}

function timeline_pesanan(string $status): string
{
    $idx = ['Menunggu pembayaran' => -1, 'Diproses' => 1, 'Diantar' => 2, 'Selesai' => 4, 'Dibatalkan' => -1][$status] ?? -1;
    $html = '<div class="tl">';
    foreach (LANGKAH_PESANAN as $i => $langkah) {
        $kelas = ($i < $idx ? 'done' : '') . ' ' . ($i === $idx ? 'now' : '');
        $html .= '<div class="st ' . trim($kelas) . '">' . e($langkah) . '</div>';
    }
    return $html . '</div>';
}

/** Ambil item untuk banyak pesanan sekaligus, dikelompokkan per id pesanan. */
function ambil_item_pesanan(array $idPesanan): array
{
    if (!$idPesanan) {
        return [];
    }
    $in = implode(',', array_fill(0, count($idPesanan), '?'));
    $stmt = db()->prepare(
        "SELECT order_id, product_name, emoji, qty, unit_price, option_label
           FROM order_items WHERE order_id IN ($in) ORDER BY id"
    );
    $stmt->execute(array_values($idPesanan));

    $hasil = [];
    foreach ($stmt as $baris) {
        $hasil[$baris['order_id']][] = $baris;
    }
    return $hasil;
}

function ringkasan_item(array $items): string
{
    return implode(', ', array_map(fn($i) => $i['qty'] . '× ' . $i['product_name'], $items));
}

function kartu_produk(array $p): string
{
    $lencana = $p['badge'] !== '' ? '<span class="pbadge">' . e($p['badge']) . '</span>' : '';
    return '<article class="pcard" aria-label="' . e($p['name']) . ', ' . e(rupiah((int) $p['price'])) . '">'
        . '<div class="pvis" ' . gaya_nada($p['category']) . '>' . $lencana
        . '<span class="prate">' . ic('star', 12) . number_format((float) $p['rating'], 1) . '</span>'
        . '<span class="pemo">' . e($p['emoji']) . '</span></div>'
        . '<div class="pbody"><h3>' . e($p['name']) . '</h3><p>' . e($p['description']) . '</p>'
        . '<div class="prow"><div><strong>' . e(rupiah((int) $p['price'])) . '</strong>'
        . '<small>' . api_pedas((int) $p['spicy_level']) . ' ' . e(label_terjual((int) $p['sold'])) . ' terjual</small></div></div>'
        . '</div></article>';
}

function kartu_mini(array $p): string
{
    return '<a class="mini" href="produk.php?q=' . rawurlencode($p['name']) . '">'
        . '<div class="pvis" ' . gaya_nada($p['category']) . '><span class="pemo">' . e($p['emoji']) . '</span></div>'
        . '<div style="flex:1;min-width:0"><b>' . e($p['name']) . '</b>'
        . '<span class="muted small">' . e(rupiah((int) $p['price'])) . '</span></div></a>';
}

function baris_pesanan(array $o, array $items): string
{
    $tumpuk = '';
    foreach (array_slice($items, 0, 3) as $i) {
        $tumpuk .= '<span>' . e($i['emoji']) . '</span>';
    }
    return '<div class="orow"><div class="emo-stack">' . $tumpuk . '</div>'
        . '<div style="flex:1;min-width:0"><b class="oid">' . e($o['id']) . '</b>'
        . '<div class="muted small" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'
        . e(ringkasan_item($items)) . '</div></div>'
        . '<div style="text-align:right"><b>' . e(rupiah((int) $o['total'])) . '</b>'
        . '<div style="margin-top:4px">' . lencana_status($o['status']) . '</div></div></div>';
}
