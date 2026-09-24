-- =====================================================================
--  Fluffy Chicken — skema database + data contoh
--  Cara pakai: import file ini lewat phpMyAdmin (tab Import),
--  atau jalankan:  mysql -u root -p < database/fluffy_chicken.sql
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS fluffy_chicken
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fluffy_chicken;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS vouchers;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
--  users: akun pelanggan (nama, email, kata sandi — sesuai form daftar)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  password   VARCHAR(255) NOT NULL,              -- hasil password_hash(), bukan teks asli
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  products: daftar menu
-- ---------------------------------------------------------------------
CREATE TABLE products (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  category    ENUM('ayam','paket','pelengkap','minuman') NOT NULL,
  price       INT UNSIGNED NOT NULL,
  emoji       VARCHAR(16)  NOT NULL,
  rating      DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  sold        INT UNSIGNED NOT NULL DEFAULT 0,
  badge       VARCHAR(30)  NOT NULL DEFAULT '',
  spicy_level TINYINT UNSIGNED NOT NULL DEFAULT 0,  -- 0 = tidak pedas, 1..3 = level pedas
  serving     VARCHAR(40)  NOT NULL DEFAULT '',
  description TEXT         NOT NULL,
  PRIMARY KEY (id),
  KEY idx_products_category (category)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  vouchers
-- ---------------------------------------------------------------------
CREATE TABLE vouchers (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code         VARCHAR(30)  NOT NULL,
  title        VARCHAR(60)  NOT NULL,
  description  VARCHAR(160) NOT NULL,
  type         ENUM('pct','flat','ship') NOT NULL,   -- persen, potongan tetap, potongan ongkir
  value        INT UNSIGNED NOT NULL DEFAULT 0,
  max_discount INT UNSIGNED DEFAULT NULL,
  min_purchase INT UNSIGNED NOT NULL DEFAULT 0,
  valid_until  DATE DEFAULT NULL,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_vouchers_code (code)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  orders + order_items: riwayat pesanan pelanggan
-- ---------------------------------------------------------------------
CREATE TABLE orders (
  id            VARCHAR(20)  NOT NULL,               -- contoh: FC-260921-0412
  user_id       INT UNSIGNED NOT NULL,
  status        ENUM('Menunggu pembayaran','Diproses','Diantar','Selesai','Dibatalkan') NOT NULL,
  ship_method   VARCHAR(20)  NOT NULL DEFAULT 'own',
  pay_method    VARCHAR(20)  NOT NULL DEFAULT 'qris',
  subtotal      INT UNSIGNED NOT NULL,
  shipping_cost INT UNSIGNED NOT NULL DEFAULT 0,
  service_fee   INT UNSIGNED NOT NULL DEFAULT 2000,
  discount      INT UNSIGNED NOT NULL DEFAULT 0,
  total         INT UNSIGNED NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_orders_user_date (user_id, created_at),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id     VARCHAR(20)  NOT NULL,
  product_id   INT UNSIGNED DEFAULT NULL,
  product_name VARCHAR(100) NOT NULL,                -- disalin saat pesan, supaya riwayat tidak berubah
  emoji        VARCHAR(16)  NOT NULL,
  qty          INT UNSIGNED NOT NULL,
  unit_price   INT UNSIGNED NOT NULL,
  option_label VARCHAR(50)  NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  KEY idx_items_order (order_id),
  CONSTRAINT fk_items_order   FOREIGN KEY (order_id)   REFERENCES orders (id)   ON DELETE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
--  DATA CONTOH
-- =====================================================================

-- Akun demo:  rina.amelia@email.com  /  123456
INSERT INTO users (id, name, email, password) VALUES
(1, 'Rina Amelia', 'rina.amelia@email.com', '$2y$10$PxNVMbSTInNz9pZIoRp/R.rqOQ75201zkv.qWPD4GOBAiT.45qYcy');

INSERT INTO products (id, name, category, price, emoji, rating, sold, badge, spicy_level, serving, description) VALUES
(1,  'Fluffy Crispy Original',  'ayam',      18000, '🍗', 4.9, 2400, 'Terlaris',  0, '1 potong',      'Ayam berbumbu rempah dengan balutan tepung renyah. Dagingnya juicy dan lembut di dalam, digoreng saat kamu memesan.'),
(2,  'Fire Chicken',            'ayam',      20000, '🍖', 4.8, 1800, 'Pedas',     3, '1 potong',      'Tepung renyah dengan bumbu cabai bakar. Pilih tingkat pedasmu, dari sedang sampai Extra Fire.'),
(3,  'Paket Hemat Fluffy',      'paket',     29000, '🍱', 4.9, 3100, 'Favorit',   1, '1 orang',       'Satu potong ayam, nasi putih hangat, dan es teh manis. Pas untuk makan siang.'),
(4,  'Paket Keluarga 8 Potong', 'paket',    159000, '🍗', 4.9,  960, 'Hemat 20%', 1, '4 orang',       'Delapan potong ayam, empat nasi, satu French fries large, dan empat es teh manis.'),
(5,  'Crispy Wings 6 Pcs',      'ayam',      34000, '🍗', 4.7, 1100, '',          2, '6 potong',      'Sayap ayam mungil yang garing di luar dan empuk di dalam. Cocok untuk berbagi atau dimakan sendiri.'),
(6,  'Chicken Katsu Rice Bowl', 'paket',     32000, '🍛', 4.8, 1300, '',          1, '1 orang',       'Chicken katsu renyah di atas nasi hangat dengan saus katsu gurih dan irisan kubis segar.'),
(7,  'Fluffy Chicken Burger',   'paket',     28000, '🍔', 4.6,  420, 'Baru',      1, '1 orang',       'Roti empuk, fillet ayam crispy, selada, tomat, dan saus spesial Fluffy.'),
(8,  'French Fries Large',      'pelengkap', 16000, '🍟', 4.7, 2000, '',          0, 'Untuk berbagi', 'Kentang goreng tipis, garing, dan ditabur garam laut. Enak dicocol saus apa pun.'),
(9,  'Chicken Pop Bites',       'pelengkap', 25000, '🥡', 4.8,  870, '',          0, '12 potong',     'Potongan ayam kecil berbalut tepung crispy. Sekali gigit langsung habis.'),
(10, 'Es Teh Manis',            'minuman',    7000, '🥤', 4.8, 5600, '',          0, '360 ml',        'Teh seduh segar dengan gula secukupnya, disajikan dingin.'),
(11, 'Lemon Tea Segar',         'minuman',   10000, '🍋', 4.7, 1600, '',          0, '360 ml',        'Teh dengan perasan lemon asli. Asam-manisnya menyegarkan setelah makan pedas.'),
(12, 'Nasi Putih Hangat',       'pelengkap',  6000, '🍚', 4.8, 4200, '',          0, '1 porsi',       'Nasi putih pulen yang selalu hangat, teman terbaik untuk ayam goreng.');

INSERT INTO vouchers (code, title, description, type, value, max_discount, min_purchase, valid_until, is_active) VALUES
('FLUFFY20',    'Diskon 20%',        'Maks. Rp25.000 · min. belanja Rp50.000',              'pct',  20, 25000, 50000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1),
('ONGKIRHEMAT', 'Gratis ongkir',     'Potongan ongkir maks. Rp10.000 · min. belanja Rp40.000', 'ship',  0, 10000, 40000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1),
('HEMAT10',     'Potongan Rp10.000', 'Min. belanja Rp60.000',                               'flat', 10000, NULL, 60000, DATE_ADD(CURDATE(), INTERVAL 45 DAY), 1);

-- Pesanan contoh untuk akun demo (tanggal dibuat relatif terhadap waktu import,
-- supaya beranda selalu terlihat "hidup").
INSERT INTO orders (id, user_id, status, ship_method, pay_method, subtotal, shipping_cost, service_fee, discount, total, created_at) VALUES
('FC-260921-0412', 1, 'Diantar',     'own',    'gopay',  74000, 8000,  2000,     0,  84000, DATE_SUB(NOW(), INTERVAL 35 MINUTE)),
('FC-260918-0377', 1, 'Selesai',     'own',    'qris',  179000,    0,  2000, 25000, 156000, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('FC-260913-0301', 1, 'Selesai',     'gosend', 'va-bca', 74000, 10000, 2000,     0,  86000, DATE_SUB(NOW(), INTERVAL 8 DAY)),
('FC-260907-0244', 1, 'Dibatalkan',  'own',    'ovo',    34000, 8000,  2000,     0,  44000, DATE_SUB(NOW(), INTERVAL 14 DAY)),
('FC-260830-0189', 1, 'Selesai',     'own',    'dana',   72000, 8000,  2000,     0,  82000, DATE_SUB(NOW(), INTERVAL 1 MONTH)),
('FC-260710-0222', 1, 'Selesai',     'own',    'qris',  179000,    0,  2000,     0, 181000, DATE_SUB(NOW(), INTERVAL 2 MONTH)),
('FC-260614-0199', 1, 'Selesai',     'own',    'gopay',  92000,    0,  2000,     0,  94000, DATE_SUB(NOW(), INTERVAL 3 MONTH)),
('FC-260520-0150', 1, 'Selesai',     'own',    'qris',  101000,    0,  2000,     0, 103000, DATE_SUB(NOW(), INTERVAL 4 MONTH)),
('FC-260412-0101', 1, 'Selesai',     'own',    'dana',  159000,    0,  2000,     0, 161000, DATE_SUB(NOW(), INTERVAL 5 MONTH));

INSERT INTO order_items (order_id, product_id, product_name, emoji, qty, unit_price, option_label) VALUES
('FC-260921-0412', 3,  'Paket Hemat Fluffy',      '🍱', 2,  29000, 'Pedas'),
('FC-260921-0412', 8,  'French Fries Large',      '🍟', 1,  16000, 'Saus keju'),
('FC-260918-0377', 4,  'Paket Keluarga 8 Potong', '🍗', 1, 159000, 'Sedang'),
('FC-260918-0377', 11, 'Lemon Tea Segar',         '🍋', 2,  10000, 'Es normal'),
('FC-260913-0301', 6,  'Chicken Katsu Rice Bowl', '🍛', 1,  32000, 'Sedang'),
('FC-260913-0301', 7,  'Fluffy Chicken Burger',   '🍔', 1,  28000, 'Original'),
('FC-260913-0301', 10, 'Es Teh Manis',            '🥤', 2,   7000, 'Es normal'),
('FC-260907-0244', 5,  'Crispy Wings 6 Pcs',      '🍗', 1,  34000, 'Pedas'),
('FC-260830-0189', 1,  'Fluffy Crispy Original',  '🍗', 3,  18000, 'Original'),
('FC-260830-0189', 12, 'Nasi Putih Hangat',       '🍚', 3,   6000, 'Porsi biasa'),
('FC-260710-0222', 4,  'Paket Keluarga 8 Potong', '🍗', 1, 159000, 'Sedang'),
('FC-260710-0222', 11, 'Lemon Tea Segar',         '🍋', 2,  10000, 'Es normal'),
('FC-260614-0199', 6,  'Chicken Katsu Rice Bowl', '🍛', 2,  32000, 'Sedang'),
('FC-260614-0199', 7,  'Fluffy Chicken Burger',   '🍔', 1,  28000, 'Original'),
('FC-260520-0150', 3,  'Paket Hemat Fluffy',      '🍱', 3,  29000, 'Original'),
('FC-260520-0150', 10, 'Es Teh Manis',            '🥤', 2,   7000, 'Es normal'),
('FC-260412-0101', 4,  'Paket Keluarga 8 Potong', '🍗', 1, 159000, 'Pedas');
