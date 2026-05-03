
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'bunga plastik', 'bunga-plastik', 'terbuat dari plastik', '2026-03-08 05:10:37', '2026-03-08 05:10:37'),
(2, 'bunga uang', 'bunga-uang', 'wdjijwsdowkpd', '2026-03-08 06:28:04', '2026-03-08 06:28:04'),
(3, 'bungga kertas', 'bungga-kertas', 'terbuata dari kertas', '2026-03-12 23:14:02', '2026-03-12 23:14:02'),
(4, 'Buket Mawar', 'buket-mawar', 'Koleksi buket bunga mawar berbagai warna', '2026-04-09 17:05:06', '2026-04-09 17:05:06'),
(5, 'Snack Bouquet', 'snack-bouquet', 'Bouquet dengan kombinasi bunga dan snack', '2026-04-09 17:05:06', '2026-04-09 17:05:06'),
(6, 'Hampers', 'hampers', 'Paket lengkap bunga dan hadiah', '2026-04-09 17:05:06', '2026-04-09 17:05:06'),
(7, 'Bouquet Wisuda', 'bouquet-wisuda', 'Bouquet spesial untuk wisuda', '2026-04-09 17:05:06', '2026-04-09 17:05:06');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `message` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `current_context` varchar(255) DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `last_question` text DEFAULT NULL,
  `is_admin_handled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `address`, `current_context`, `retry_count`, `last_question`, `is_admin_handled`, `created_at`, `updated_at`) VALUES
(24, 'Warnadi Ganteng', '62881023926516', NULL, 'collecting_product', 0, '📦 *Katalog Produk Buket Cute*\n\n1. buket  mawar merah putih - Rp 120.000\n2. buket kinder joy - Rp 160.000\n3. buket wisuda premium - Rp 200.000\n4. bunga jajan - Rp 200.000\n5. bunga mawar hitam - Rp 100.000\n6. bunga putih v2 - Rp 100.000\n7. hamper wisuda - Rp 100.000\n8. mawar hitam - Rp 120.000\n9. uang 5000an - Rp 100.000\n\nSilakan ketik nomor atau nama produk yang ingin dipesan. Contoh: ketik \'1\' atau \'buket mawar\'.', 0, '2026-04-21 06:37:43', '2026-04-21 06:39:57'),
(25, 'Rama Aditya Indra Pratama', '6287729860204', 'waled, Jawa Barat', NULL, 0, NULL, 0, '2026-04-21 06:43:09', '2026-04-21 06:47:41');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fuzzy_rules`
--

CREATE TABLE `fuzzy_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `intent` varchar(100) NOT NULL,
  `pattern` text NOT NULL,
  `confidence_threshold` double NOT NULL,
  `action` varchar(100) NOT NULL,
  `response_template` text DEFAULT NULL,
  `context_slug` varchar(255) DEFAULT NULL,
  `next_context` varchar(255) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fuzzy_rules`
--

INSERT INTO `fuzzy_rules` (`id`, `intent`, `pattern`, `confidence_threshold`, `action`, `response_template`, `context_slug`, `next_context`, `priority`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'order_start', 'pesan|order|beli|buatkan|pesen|mau beli|mau pesan|mohon pesan|order dong|order buket|bikin buket|request buket|booking|pesan buket|beli buket|mau order', 0.7, 'order', NULL, NULL, 'collecting_order', 100, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(2, 'confirm_yes', 'iya|ya|ok|oke|okee|siap|benar|betul|lanjut|confirm|jadi|yup|yoi|oke siap|udah bener|deal|sip|iya deh|ya deh|oke deh', 0.8, 'confirm_order', NULL, 'confirming', 'order_completed', 90, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(3, 'confirm_no', 'tidak|nggak|ngga|gak|batal|cancel|salah|ubah|ganti|jangan|nope|tidak jadi|ulang|ga jadi|ga mau', 0.8, 'reply', 'Siap ka, pesanan dibatalkan. Mari kita mulai ulang ya. Boleh tau nama Kakak siapa?', 'confirming', 'collecting_name', 90, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(4, 'show_catalog', 'katalog|lihat produk|daftar produk|pilihan buket|menu|ready apa aja|contoh buket|pilihan bunga|liat liat|produk apa|ada apa|apa saja|lihat katalog|cek produk|produknya apa', 0.6, 'show_product', NULL, NULL, NULL, 70, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(5, 'product_mawar', 'buket mawar|mawar|buket bunga mawar|rose bouquet|mawar merah|mawar putih|mawar pink|mawar kuning', 0.7, 'reply', 'Buket Mawar kami ada berbagai warna: Merah, Putih, Pink, Kuning. Harga mulai Rp 75rb. Mau yang mana ka?', NULL, 'collecting_order', 65, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(6, 'product_snack', 'snack bouquet|bouquet snack|buket snack|snack|makanan|kue|chocolate|permen|snack box', 0.7, 'reply', 'Snack Bouquet kami berisi berbagai camilan dan bunga cantik. Harga mulai Rp 100rb. Cocok untuk surprise!', NULL, 'collecting_order', 65, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(7, 'product_hamper', 'hamper|parcel|paket|gift box|kado|hadiah|surprise box|hamper ulang tahun', 0.7, 'reply', 'Hampers kami berisi bunga + snack premium. Harga mulai Rp 150rb. Perfect untuk ulang tahun atau anniversary!', NULL, 'collecting_order', 65, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(8, 'product_wisuda', 'wisuda|graduation|bouquet wisuda|buket wisuda|selamat wisuda|tamatan|kuliah selesai', 0.7, 'reply', 'Bouquet Wisuda kami spesial dengan bunga dan ucapan selamat. Harga mulai Rp 125rb. Ada berbagai ukuran!', NULL, 'collecting_order', 65, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(9, 'price_inquiry', 'harga|berapa|harganya|pricelist|biaya|berapaan|ongkir|cek harga|mahal ga|price|murah ga|range harga', 0.7, 'reply', 'Harga buket kami bervariasi mulai dari Rp 50rb - 500rb tergantung ukuran dan jenis bunga. Untuk detailnya kakak bisa cek di Katalog ya!', NULL, NULL, 60, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(10, 'provide_name', 'nama saya|nma|aku|gue|gue adalah|saya adalah|namaku|nama ku', 0.8, 'collect_name', NULL, 'collecting_name', 'collecting_product', 85, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(11, 'provide_address', 'alamat|tinggal di|di|rumah|lokasi|tempat tinggal|kirim ke|antar ke', 0.7, 'collect_address', NULL, 'collecting_address', 'collecting_quantity', 85, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(12, 'provide_quantity', 'biji|buket|pcs|buah|tangkai|ikat|set|piece|qty|jumlah', 0.6, 'collect_quantity', NULL, 'collecting_quantity', 'confirming', 85, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(13, 'greeting', 'halo|hai|hay|hello|hey|hi|assalamualaikum|asalamualaikum|salam|pagi|siang|sore|malam|p|punten|spada|selamat datang', 0.7, 'reply', 'Halo ka! 👋 Selamat datang di Buket Cute Indramayu. Mau cari buket untuk wisuda, ultah, atau nikahan? Ketik \"Pesan\" untuk order ya!', NULL, NULL, 40, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(14, 'help', 'bantuan|tolong|cara|gimana|help|panduan|info|tanya|bingung|confused|tidak paham|ga paham', 0.6, 'reply', 'Butuh bantuan? 😊 Ini cara ordernya:\n1️⃣ Ketik \'Katalog\' untuk lihat produk\n2️⃣ Ketik \'Pesan\' untuk mulai order\n3️⃣ Ikuti instruksi bot sampai selesai\n4️⃣ Konfirmasi pembayaran\n\nMau mulai sekarang?', NULL, NULL, 30, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(15, 'escalate_admin', 'admin|operator|manusia|komplain|masalah|rusak|operator|panggil admin|hubungi admin|bicara admin|bicara orang', 0.7, 'escalate', 'Baik ka, pesan Kakak segera diteruskan ke Admin kami. Mohon tunggu sebentar ya.', NULL, NULL, 80, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(16, 'closing', 'terima kasih|thanks|makasih|nuhun|syukron|ok tks|thank you|dah|bye|bye bye|sampai jumpa|dadah', 0.7, 'reply', 'Sama-sama ka! Senang bisa melayani Kakak. Ditunggu kabar baiknya! ✨', NULL, NULL, 20, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15'),
(17, 'default_fallback', 'bot_internal_fallback_logic', 1, 'manual_review', 'Maaf ka, saya belum paham maksudnya. Bisa ketik \"Bantuan\" untuk melihat instruksi?', NULL, NULL, 1, 1, '2026-04-09 17:05:15', '2026-04-09 17:05:15');

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `min_stock` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`id`, `name`, `description`, `stock`, `unit`, `min_stock`, `created_at`, `updated_at`) VALUES
(1, 'plastik bening', 'plasti buat pembungkus', 1000, 'pcs', 10, '2026-03-12 23:13:18', '2026-03-12 23:13:18');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  `collection` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`id`, `message_id`, `model_type`, `model_id`, `collection`, `file_path`, `file_name`, `mime_type`, `file_type`, `size`, `file_size`, `created_at`, `updated_at`, `is_featured`) VALUES
(1, NULL, 'App\\Models\\Product', 1, NULL, 'products/1772972008_69ad67e8b972d.png', '1772972008_69ad67e8b972d.png', 'image/png', NULL, 474334, NULL, '2026-03-07 22:13:28', '2026-03-07 22:13:28', 0),
(2, NULL, 'App\\Models\\Product', 2, NULL, 'products/1772972590_69ad6a2edc28f.png', '1772972590_69ad6a2edc28f.png', 'image/png', NULL, 450952, NULL, '2026-03-07 22:23:10', '2026-03-07 22:23:10', 0),
(3, NULL, 'App\\Models\\Product', 3, NULL, 'products/1772973138_69ad6c52a5ef1.png', '1772973138_69ad6c52a5ef1.png', 'image/png', NULL, 427361, NULL, '2026-03-07 22:32:18', '2026-03-07 22:32:18', 0),
(5, NULL, 'App\\Models\\Product', 5, NULL, 'products/1774962547_69cbc773e0242.png', '1774962547_69cbc773e0242.png', 'image/png', NULL, 498068, NULL, '2026-03-30 23:09:07', '2026-03-30 23:09:07', 0),
(6, NULL, 'App\\Models\\Product', 16, NULL, 'products/1775755224_69d7dfd8aba05.png', '1775755224_69d7dfd8aba05.png', 'image/png', NULL, 514083, NULL, '2026-04-09 10:20:24', '2026-04-09 10:20:24', 0),
(7, NULL, 'App\\Models\\Product', 23, NULL, 'products/1775755922_69d7e292724a8.png', '1775755922_69d7e292724a8.png', 'image/png', NULL, 274218, NULL, '2026-04-09 10:32:02', '2026-04-09 10:32:02', 0),
(8, NULL, 'App\\Models\\Product', 24, NULL, 'products/1775755972_69d7e2c4b9b24.png', '1775755972_69d7e2c4b9b24.png', 'image/png', NULL, 514083, NULL, '2026-04-09 10:32:52', '2026-04-09 10:32:52', 0),
(9, NULL, 'App\\Models\\Product', 25, NULL, 'products/1775756084_69d7e334d995c.png', '1775756084_69d7e334d995c.png', 'image/png', NULL, 206000, NULL, '2026-04-09 10:34:44', '2026-04-09 10:34:44', 0),
(10, NULL, 'App\\Models\\Product', 26, NULL, 'products/1775756129_69d7e361f199d.png', '1775756129_69d7e361f199d.png', 'image/png', NULL, 268277, NULL, '2026-04-09 10:35:30', '2026-04-09 10:35:30', 0);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `message_id` varchar(100) NOT NULL,
  `from` varchar(20) NOT NULL,
  `to` varchar(20) NOT NULL,
  `body` text NOT NULL,
  `media_path` varchar(255) DEFAULT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `chat_status` enum('active','archived','pending','closed') NOT NULL DEFAULT 'active',
  `is_incoming` tinyint(1) NOT NULL,
  `parsed` tinyint(1) NOT NULL DEFAULT 0,
  `parsed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `customer_id`, `order_id`, `message_id`, `from`, `to`, `body`, `media_path`, `media_url`, `file_name`, `type`, `status`, `chat_status`, `is_incoming`, `parsed`, `parsed_at`, `created_at`, `updated_at`) VALUES
(265, 24, NULL, 'ACFC26683CE9DBDAB72B94E5F54D8BA3', '62881023926516', '+62xxxxxxx', 'Halo kak', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:37:43', '2026-04-21 06:37:43', '2026-04-21 06:37:43'),
(266, 24, NULL, 'reply_69e77daf5e706', 'system', '62881023926516', 'Halo, ╰(*°▽°*)╯! Selamat datang di Buket Cute. Ada yang bisa saya bantu? 😊', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:37:51', '2026-04-21 06:37:51'),
(267, 24, NULL, 'AC5F56F19495461CC19319DB3E32A68B', '62881023926516', '+62xxxxxxx', 'Saya mau pesan buket kak', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:38:13', '2026-04-21 06:38:13', '2026-04-21 06:38:13'),
(268, 24, NULL, 'reply_69e77dce388a8', 'system', '62881023926516', 'Siapa nama lengkap Kakak?', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:38:22', '2026-04-21 06:38:22'),
(269, 24, NULL, 'ACBBEE001A220CCFDB95443E3574C9EF', '62881023926516', '+62xxxxxxx', 'Warnadi ganteng', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:38:43', '2026-04-21 06:38:43', '2026-04-21 06:38:43'),
(270, 24, NULL, 'reply_69e77de61eac4', 'system', '62881023926516', 'Terima kasih, Kak Warnadi Ganteng. Produk apa yang ingin dipesan?', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:38:46', '2026-04-21 06:38:46'),
(271, 24, NULL, 'AC93023FC0C15361367E83EBE25A4FEA', '62881023926516', '+62xxxxxxx', 'Saya mau pesan buket katalog nya mana', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:39:51', '2026-04-21 06:39:51', '2026-04-21 06:39:51'),
(272, 24, NULL, 'reply_69e77e2d45a7f', 'system', '62881023926516', '📦 *Katalog Produk Buket Cute*\n\n1. buket  mawar merah putih - Rp 120.000\n2. buket kinder joy - Rp 160.000\n3. buket wisuda premium - Rp 200.000\n4. bunga jajan - Rp 200.000\n5. bunga mawar hitam - Rp 100.000\n6. bunga putih v2 - Rp 100.000\n7. hamper wisuda - Rp 100.000\n8. mawar hitam - Rp 120.000\n9. uang 5000an - Rp 100.000\n\nSilakan ketik nomor atau nama produk yang ingin dipesan. Contoh: ketik \'1\' atau \'buket mawar\'.', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:39:57', '2026-04-21 06:39:57'),
(273, 25, NULL, 'AC28CDB3B5775ABA833DF31E7FEB3C29', '6287729860204', '+62xxxxxxx', 'halo kak', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:43:09', '2026-04-21 06:43:09', '2026-04-21 06:43:09'),
(274, 25, NULL, 'reply_69e77ef428795', 'system', '6287729860204', 'Halo, Rama! Selamat datang di Buket Cute. Ada yang bisa saya bantu? 😊', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:43:16', '2026-04-21 06:43:16'),
(275, 25, NULL, 'ACC23516D394D2BE9C7CE08BF6BA9961', '6287729860204', '+62xxxxxxx', 'mau pesen buket', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:43:39', '2026-04-21 06:43:39', '2026-04-21 06:43:39'),
(276, 25, NULL, 'reply_69e77f11df3d3', 'system', '6287729860204', 'Siapa nama lengkap Kakak?', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:43:45', '2026-04-21 06:43:45'),
(277, 25, NULL, 'ACC37A274402D649F40948C309B62C61', '6287729860204', '+62xxxxxxx', 'rama Aditya indra Pratama', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:43:59', '2026-04-21 06:43:59', '2026-04-21 06:43:59'),
(278, 25, NULL, 'reply_69e77f281fb90', 'system', '6287729860204', 'Terima kasih, Kak Rama Aditya Indra Pratama. Produk apa yang ingin dipesan?', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:44:08', '2026-04-21 06:44:08'),
(279, 25, NULL, 'ACB34860AEC5C0CCAB928648AA4C05B4', '6287729860204', '+62xxxxxxx', 'ada produk apa saja kak', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:44:49', '2026-04-21 06:44:49', '2026-04-21 06:44:49'),
(280, 25, NULL, 'reply_69e77f5977da4', 'system', '6287729860204', '📦 *Katalog Produk Buket Cute*\n\n1. buket  mawar merah putih - Rp 120.000\n2. buket kinder joy - Rp 160.000\n3. buket wisuda premium - Rp 200.000\n4. bunga jajan - Rp 200.000\n5. bunga mawar hitam - Rp 100.000\n6. bunga putih v2 - Rp 100.000\n7. hamper wisuda - Rp 100.000\n8. mawar hitam - Rp 120.000\n9. uang 5000an - Rp 100.000\n\nSilakan ketik nomor atau nama produk yang ingin dipesan. Contoh: ketik \'1\' atau \'buket mawar\'.', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:44:57', '2026-04-21 06:44:57'),
(281, 25, NULL, 'AC2913A6ADE5FD9D8BAF14D4402463E7', '6287729860204', '+62xxxxxxx', '5', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:45:14', '2026-04-21 06:45:14', '2026-04-21 06:45:14'),
(282, 25, NULL, 'reply_69e77f7113042', 'system', '6287729860204', 'Maaf, produk yang Kakak maksud tidak ditemukan. Coba sebutkan nama produk lain, atau ketik \'katalog\' untuk lihat daftar produk.', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:45:21', '2026-04-21 06:45:21'),
(283, 25, NULL, 'AC5A508963EED523DA0682CB18309CC8', '6287729860204', '+62xxxxxxx', 'bunga jajan', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:45:44', '2026-04-21 06:45:44', '2026-04-21 06:45:44'),
(284, 25, NULL, 'reply_69e77f90a8143', 'system', '6287729860204', 'bunga jajan ya. Berapa jumlah yang mau dipesan? (dalam biji/buket)', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:45:52', '2026-04-21 06:45:52'),
(285, 25, NULL, 'AC8629F8D617B70725B5AA99D6ECDC9E', '6287729860204', '+62xxxxxxx', '10', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:46:04', '2026-04-21 06:46:04', '2026-04-21 06:46:04'),
(286, 25, NULL, 'reply_69e77fa49dde9', 'system', '6287729860204', 'Saya tidak bisa menangkap jumlahnya. Bisa sebutkan angka, misal: 2', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:46:12', '2026-04-21 06:46:12'),
(287, 25, NULL, 'ACB498177AD5631D21ED17DEBF8FDFBA', '6287729860204', '+62xxxxxxx', '10 biji', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:46:30', '2026-04-21 06:46:30', '2026-04-21 06:46:30'),
(288, 25, NULL, 'reply_69e77fbd8165a', 'system', '6287729860204', 'Baik, jumlahnya 10. Sekarang tulis alamat lengkap pengiriman ya.', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:46:37', '2026-04-21 06:46:37'),
(289, 25, NULL, 'AC84A23DB719B0A41D66EDDE4E46CFA6', '6287729860204', '+62xxxxxxx', 'waled, Jawa Barat', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:46:55', '2026-04-21 06:46:55', '2026-04-21 06:46:55'),
(290, 25, NULL, 'reply_69e77fd738f36', 'system', '6287729860204', 'Baik, alamat sudah tercatat. Sekarang untuk metode pembayaran bagaimana:\n\n1. 💰 COD (Bayar di tempat)\n2. 🏦 Transfer Bank\n\nKetik \'1\' untuk COD atau \'2\' untuk Transfer Bank.', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:47:03', '2026-04-21 06:47:03'),
(291, 25, NULL, 'AC86C972DBC828D885AF04B10B524C68', '6287729860204', '+62xxxxxxx', '2', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:47:10', '2026-04-21 06:47:10', '2026-04-21 06:47:10'),
(292, 25, NULL, 'reply_69e77fe50e5a3', 'system', '6287729860204', 'Baik, metode pembayaran: Transfer Bank\n\nIni ringkasan lengkap pesanan Kakak:\n\nNama: Rama Aditya Indra Pratama\nProduk: bunga jajan\nJumlah: 10 biji\nHarga: Rp 2.000.000\nAlamat: waled, Jawa Barat\nPembayaran: Transfer Bank\n\nApakah sudah benar? Ketik \'iya\' untuk konfirmasi, atau \'ubah\' jika ada yang salah.', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:47:17', '2026-04-21 06:47:17'),
(293, 25, NULL, 'ACD5D5A5F2BFB7AB60D304E45330E4CB', '6287729860204', '+62xxxxxxx', 'iya', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:47:35', '2026-04-21 06:47:35', '2026-04-21 06:47:35'),
(294, 25, NULL, 'reply_69e77ffd36702', 'system', '6287729860204', 'Terima kasih! Pesanan Kakak sudah kami terima dengan nomor #7. Admin akan segera memproses. 😊', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:47:41', '2026-04-21 06:47:41'),
(295, 25, NULL, 'AC64EEA4261640EB6B5A2A98AA50C3BF', '6287729860204', '+62xxxxxxx', 'cek status', NULL, NULL, NULL, 'conversation', 'pending', 'active', 1, 1, '2026-04-21 06:49:52', '2026-04-21 06:49:52', '2026-04-21 06:49:52'),
(296, 25, NULL, 'reply_69e7808981084', 'system', '6287729860204', '📦 *Status Pesanan #7*\n\n- bunga jajan x10 = Rp 2.000.000\n\nStatus: completed\n\nTanggal: 21/04/2026 13:47\n\n✅ *Pesanan selesai!*\nTerima kasih sudah berbelanja di Buket Cute. Sampai jumpa lagi! 🌸', NULL, NULL, NULL, 'text', 'sent', 'active', 0, 1, NULL, '2026-04-21 06:50:01', '2026-04-21 06:50:01');

-- --------------------------------------------------------

--
-- Table structure for table `message_parses`
--

CREATE TABLE `message_parses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `intent` varchar(100) NOT NULL,
  `confidence` double NOT NULL,
  `extracted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`extracted_data`)),
  `is_processed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_03_01_094106_create_customers_table', 1),
(5, '2026_03_01_094803_create_categories', 1),
(6, '2026_03_01_094857_create_products', 1),
(7, '2026_03_01_094940_create_ingredients_tabel', 1),
(8, '2026_03_01_095302_create_product_ingredient', 1),
(9, '2026_03_01_095448_create_orders-tabel', 1),
(10, '2026_03_01_095507_create_order_items', 1),
(11, '2026_03_01_095817_create_order_item_ingredients', 1),
(12, '2026_03_01_095854_create_conversations', 1),
(13, '2026_03_01_095956_create_messege', 1),
(14, '2026_03_01_100056_create_fuzzy_rule', 1),
(15, '2026_03_01_100132_create_masage_parse', 1),
(16, '2026_03_01_100339_create_order_drafts', 1),
(17, '2026_03_01_100414_create_media', 1),
(18, '2026_03_01_100440_create_stock_movements', 1),
(19, '2026_03_01_100741_create_purchases', 1),
(20, '2026_03_01_100816_create_purchase_items', 1),
(21, '2026_03_04_120000_add_whatsapp_media_columns', 1),
(22, '2026_03_05_000001_add_unique_constraint_conversations', 1),
(23, '2026_03_05_add_chat_status_to_messages', 1),
(24, '2026_03_05_drop_conversations_table', 1),
(25, '2026_03_13_000000_add_media_columns_to_messages', 2),
(26, '2026_03_31_124637_fix_fuzzy_rules_columns', 3),
(27, '2026_04_06_120000_add_current_context_to_conversations', 4),
(28, '2026_04_06_060255_add_current_context_to_customers_table', 5),
(29, '2026_04_09_204813_change_message_id_to_string_in_messages_table', 6),
(30, '2026_04_14_134032_add_chatbot_fields_to_customers_table', 7),
(31, '2026_04_15_150309_change_last_question_type_in_customers_table', 8),
(32, '2026_04_20_000000_add_payment_fields_to_orders_table', 9),
(33, '2026_04_20_061919_add_payment_fields_to_orders_table', 9),
(34, '2026_04_20_061940_add_payment_fields_to_orders_table', 9),
(35, '2026_04_20_143451_create_complaints_table', 10);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','processed','completed','cancelled') NOT NULL,
  `payment_method` enum('cod','bank_transfer','midtrans') NOT NULL DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_proof` text DEFAULT NULL,
  `payment_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_data`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `total_price`, `status`, `payment_method`, `payment_status`, `payment_proof`, `payment_data`, `notes`, `created_at`, `updated_at`) VALUES
(7, 25, 2000000.00, 'completed', 'bank_transfer', 'pending', NULL, NULL, NULL, '2026-04-21 06:47:35', '2026-04-21 06:48:18');

-- --------------------------------------------------------

--
-- Table structure for table `order_drafts`
--

CREATE TABLE `order_drafts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `step` varchar(50) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_drafts`
--

INSERT INTO `order_drafts` (`id`, `customer_id`, `data`, `step`, `expires_at`, `created_at`, `updated_at`) VALUES
(28, 24, '{\"customer_id\":24,\"customer_phone\":\"62881023926516\",\"customer_name\":\"Warnadi Ganteng\",\"customer_address\":null,\"product_id\":2,\"product_name\":\"buket  mawar merah putih\",\"quantity\":null,\"price\":\"120000.00\",\"total_price\":null,\"category\":null,\"raw_message\":null,\"product_similarity\":null,\"created_at\":\"2026-04-21T13:38:13+00:00\",\"product_candidates\":[2,5,16,24,1,3,26,25,23]}', 'collecting_product', '2026-04-22 06:38:13', '2026-04-21 06:38:13', '2026-04-21 06:39:51');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `custom_description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `custom_description`, `quantity`, `price`, `subtotal`, `created_at`, `updated_at`) VALUES
(24, 7, 24, NULL, 10, 200000.00, 2000000.00, '2026-04-21 06:48:18', '2026-04-21 06:48:18');

-- --------------------------------------------------------

--
-- Table structure for table `order_item_ingredients`
--

CREATE TABLE `order_item_ingredients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_item_id` bigint(20) UNSIGNED NOT NULL,
  `ingredient_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `price`, `stock`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'bunga mawar hitam', 'bunga-mawar-hitam', 'ini contoh untuk pengujian', 100000.00, 100, 1, '2026-03-08 05:13:28', '2026-03-08 05:13:28'),
(2, 1, 'buket  mawar merah putih', 'buket-mawar-merah-putih', 'buket warna mawar merah putih', 120000.00, 100, 1, '2026-03-08 05:23:10', '2026-03-08 05:23:10'),
(3, 1, 'bunga putih v2', 'bunga-putih', 'ini deskripsi sementara', 100000.00, 15, 1, '2026-03-08 05:32:18', '2026-03-08 05:38:32'),
(5, 1, 'buket kinder joy', 'buket-kinder-joy', 'ini buket berhias kinder joy', 160000.00, 10, 1, '2026-03-31 06:09:07', '2026-03-31 06:09:07'),
(16, 7, 'buket wisuda premium', 'buket-wisuda', 'gwsyqgsyqgy', 200000.00, 20, 1, '2026-04-09 17:20:24', '2026-04-09 17:24:18'),
(23, 2, 'uang 5000an', 'uang-5000an', 'mdnjj', 100000.00, 10, 1, '2026-04-09 17:32:02', '2026-04-09 17:32:02'),
(24, 5, 'bunga jajan', 'bunga-jajan', 'jajan apa saja', 200000.00, 10, 1, '2026-04-09 17:32:52', '2026-04-09 17:32:52'),
(25, 4, 'mawar hitam', 'mawar-hitam', 'hitam', 120000.00, 10, 1, '2026-04-09 17:34:44', '2026-04-09 17:34:44'),
(26, 6, 'hamper wisuda', 'hamper-wisuda', 'hamper', 100000.00, 10, 1, '2026-04-09 17:35:29', '2026-04-09 17:35:29');

-- --------------------------------------------------------

--
-- Table structure for table `product_ingredient`
--

CREATE TABLE `product_ingredient` (
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `ingredient_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier` varchar(255) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_id` bigint(20) UNSIGNED NOT NULL,
  `ingredient_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('l61SfmfnTf4dIOacUM2NwniCq5u9lQumvAoUWDtq', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZkROYkpIQVhpNm9ZaEdodjFyRHlCNUFTVmJhRHpJQ2tqM0tMV1ZtOSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9vcmRlcnMiO3M6NToicm91dGUiO3M6MTg6ImFkbWluLm9yZGVycy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1776779303);

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ingredient_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('in','out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `ingredient_id`, `type`, `quantity`, `description`, `reference_type`, `reference_id`, `created_at`, `updated_at`) VALUES
(1, 1, 'in', 1000, 'Stok awal saat pembuatan bahan', NULL, NULL, '2026-03-12 23:13:18', '2026-03-12 23:13:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin Toko', 'admin@toko.com', '2026-03-04 12:21:51', '$2y$12$VRfGTyMu85E5OIDojtznkexoO0bsMyRMTZ1vy6aUOkvkKqEzu2PJ2', 'v4wgHqLYnP1xNLzlcy43WKmDaBTvArLMZfIFffMq1RARvbtCVfnPEeclzkRU', '2026-03-04 12:21:52', '2026-03-04 12:21:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `complaints_customer_id_foreign` (`customer_id`),
  ADD KEY `complaints_order_id_foreign` (`order_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_phone_unique` (`phone`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `fuzzy_rules`
--
ALTER TABLE `fuzzy_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fuzzy_rules_context_slug_index` (`context_slug`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  ADD KEY `media_message_id_foreign` (`message_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_order_id_foreign` (`order_id`),
  ADD KEY `messages_customer_id_index` (`customer_id`);

--
-- Indexes for table `message_parses`
--
ALTER TABLE `message_parses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_parses_message_id_foreign` (`message_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orders_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `order_drafts`
--
ALTER TABLE `order_drafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_drafts_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_foreign` (`order_id`),
  ADD KEY `order_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `order_item_ingredients`
--
ALTER TABLE `order_item_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_item_ingredients_order_item_id_foreign` (`order_item_id`),
  ADD KEY `order_item_ingredients_ingredient_id_foreign` (`ingredient_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_slug_unique` (`slug`),
  ADD KEY `products_category_id_foreign` (`category_id`);

--
-- Indexes for table `product_ingredient`
--
ALTER TABLE `product_ingredient`
  ADD PRIMARY KEY (`product_id`,`ingredient_id`),
  ADD KEY `product_ingredient_ingredient_id_foreign` (`ingredient_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  ADD KEY `purchase_items_ingredient_id_foreign` (`ingredient_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_ingredient_id_foreign` (`ingredient_id`),
  ADD KEY `stock_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuzzy_rules`
--
ALTER TABLE `fuzzy_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=297;

--
-- AUTO_INCREMENT for table `message_parses`
--
ALTER TABLE `message_parses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_drafts`
--
ALTER TABLE `order_drafts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `order_item_ingredients`
--
ALTER TABLE `order_item_ingredients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `complaints_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `messages_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `message_parses`
--
ALTER TABLE `message_parses`
  ADD CONSTRAINT `message_parses_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `order_drafts`
--
ALTER TABLE `order_drafts`
  ADD CONSTRAINT `order_drafts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_item_ingredients`
--
ALTER TABLE `order_item_ingredients`
  ADD CONSTRAINT `order_item_ingredients_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`),
  ADD CONSTRAINT `order_item_ingredients_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_ingredient`
--
ALTER TABLE `product_ingredient`
  ADD CONSTRAINT `product_ingredient_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_ingredient_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`),
  ADD CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
