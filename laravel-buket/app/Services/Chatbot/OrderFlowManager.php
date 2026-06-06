<?php

namespace App\Services\Chatbot;

use App\Models\Customer;
use App\Models\OrderDraft;
use App\Models\Order;
use App\Services\OrderDraftService;
use App\Services\ParameterExtractionService;
use App\Services\ParameterValidationService;
use Illuminate\Support\Facades\Log;
use App\Services\MidtransService;
use App\Services\Chatbot\IntentClassifier;

class OrderFlowManager
{
    protected ConversationManager $conv;
    protected OrderDraftService $draftService;
    protected ProductMatcher $productMatcher;
    protected ParameterExtractionService $paramExtractor;
    protected ParameterValidationService $paramValidator;
    protected ReplySender $replySender;
    protected Customer $customer;

    protected const MAX_RETRY = 3;

    public function __construct(
        ConversationManager $conv,
        OrderDraftService $draftService,
        ProductMatcher $productMatcher,
        ParameterExtractionService $paramExtractor,
        ParameterValidationService $paramValidator,
        ReplySender $replySender
    ) {
        $this->conv = $conv;
        $this->customer = $conv->getCustomer();
        $this->draftService = $draftService;
        $this->productMatcher = $productMatcher;
        $this->paramExtractor = $paramExtractor;
        $this->paramValidator = $paramValidator;
        $this->replySender = $replySender;
    }

    public function process(string $message, Customer $customer): void
    {
        $state = $this->conv->getState();

        if ($state === null || $state === ConversationManager::STATE_IDLE) {
            $this->startOrder($customer, $message);
            return;
        }

        if ($this->isCancellationCommand($message)) {
            $this->cancelOrder($customer);
            return;
        }

        switch ($state) {
            case ConversationManager::STATE_ORDERING_NAME:
                $this->collectName($message, $customer);
                break;
            case ConversationManager::STATE_ORDERING_ADDRESS:
                $this->collectAddress($message, $customer);
                break;
            case ConversationManager::STATE_ORDERING_PRODUCT:
                $this->collectProduct($message, $customer);
                break;
            case ConversationManager::STATE_ORDERING_QUANTITY:
                $this->collectQuantity($message, $customer);
                break;
            case ConversationManager::STATE_ORDERING_CONFIRMING:
                $this->handleConfirmation($message, $customer);
                break;
            case ConversationManager::STATE_ORDERING_PAYMENT:
                $this->collectPaymentMethod($message, $customer);
                break;
            default:
                Log::warning('Unknown order state, resetting', ['state' => $state]);
                $this->conv->reset();
                $this->startOrder($customer, $message);
                break;
        }
    }

    protected function startOrder(Customer $customer, string $firstMessage): void
    {
        $oldDraft = $this->draftService->getCustomerActiveDraft($customer);
        if ($oldDraft) {
            $oldDraft->delete();
        }

        $draft = $this->draftService->getOrCreateDraft($customer);
        $data = $draft->data ?? [];

        // --- TAMBAHKAN LOGIKA PRE-PARSER CHAT WEBSITE DI SINI ---
        // Deteksi jika chat mengandung format khas dari redirect website Anda
        if (strpos($firstMessage, 'Produk:') !== false && strpos($firstMessage, 'Jumlah:') !== false) {
            
            // Ekstrak nama produk di antara *Produk:* dan teks berikutnya (atau baris baru)
            if (preg_match('/(?:Produk:)\s*\*?([^\*\n\r]+)/i', $firstMessage, $productMatches)) {
                $rawProductName = trim($productMatches[1]);
                
                // Cari produk ke database menggunakan ProductMatcher bawaan Anda agar akurat
                $matchResult = $this->productMatcher->match($rawProductName);
                if ($matchResult['matched']) {
                    $product = $matchResult['product'];
                    $data['product_id'] = $product->id;
                    $data['product_name'] = $product->name;
                    $data['price'] = floatval($product->price);
                }
            }

            // Ekstrak jumlah (quantity) dari teks setelah *Jumlah:*
            if (preg_match('/(?:Jumlah:)\s*\*?(\d+)/i', $firstMessage, $qtyMatches)) {
                $data['quantity'] = intval($qtyMatches[1]);
            }
            
            // Ekstrak address/alamat jika di format web Anda nantinya ada input alamat
            if (preg_match('/(?:Alamat:)\s*\*?([^\*\n\r]+)/i', $firstMessage, $addrMatches)) {
                $data['customer_address'] = trim($addrMatches[1]);
            }
            
        } else {
            // --- JALUR NORMAL (Ketik Manual Chat WA Biasa) ---
            $extracted = $this->paramExtractor->extractParameters($firstMessage);

            if (!empty($extracted['product_data'])) {
                $data['product_id'] = $extracted['product_data']['product_id'];
                $data['product_name'] = $extracted['product_data']['product_name'];
                $data['price'] = floatval($extracted['product_data']['price']);
            }
            if (!empty($extracted['quantity'])) {
                $data['quantity'] = intval($extracted['quantity']);
            }
            if (!empty($extracted['address'])) {
                $addr = trim($extracted['address']);
                if (strlen($addr) >= 10) {
                    $data['customer_address'] = $addr;
                } else {
                    $data['customer_address'] = $this->normalizeAddress($firstMessage);
                }
            }
        }
        // --- AKHIR LOGIKA PRE-PARSER ---

        // Masukkan data yang berhasil diekstrak ke dalam draf
        $draft->data = $data;

        // Cek parameter apa saja yang masih kurang
        $missing = [];
        if (empty($data['customer_name'])) $missing[] = 'name';
        if (empty($data['customer_address'])) $missing[] = 'address';
        if (empty($data['product_id'])) $missing[] = 'product';
        if (empty($data['quantity'])) $missing[] = 'quantity';

        // Jika semua parameter langsung lengkap dari web (Termasuk Nama & Alamat)
        if (empty($missing)) {
            if (empty($data['total_price']) && !empty($data['price']) && !empty($data['quantity'])) {
                $data['total_price'] = $data['price'] * $data['quantity'];
                $draft->data = $data;
            }
            $draft->step = ConversationManager::STATE_ORDERING_CONFIRMING;
            $draft->save();
            $this->conv->setState(ConversationManager::STATE_ORDERING_CONFIRMING);
            $this->askConfirmation($customer, $draft);
            return;
        }

        // Jika ada yang kurang (misal nama & alamat belum diisi di web), tanyakan step yang kosong pertama
        $firstMissing = $missing[0];
        $nextState = match ($firstMissing) {
            'name'    => ConversationManager::STATE_ORDERING_NAME,
            'address' => ConversationManager::STATE_ORDERING_ADDRESS,
            'product' => ConversationManager::STATE_ORDERING_PRODUCT,
            'quantity'=> ConversationManager::STATE_ORDERING_QUANTITY,
            default   => ConversationManager::STATE_ORDERING_NAME,
        };

        $draft->step = $nextState;
        $draft->save();
        $this->conv->setState($nextState);
        $this->askQuestionForState($nextState, $customer);
    }

    protected function askQuestionForState(string $state, Customer $customer): void
    {
        $question = match ($state) {
            ConversationManager::STATE_ORDERING_NAME => "Siapa nama lengkap Kakak?",
            ConversationManager::STATE_ORDERING_ADDRESS => "Di mana alamat lengkap Kakak?",
            ConversationManager::STATE_ORDERING_PRODUCT => "Produk apa yang ingin dipesan? (Ketik 'katalog' untuk lihat daftar)",
            ConversationManager::STATE_ORDERING_QUANTITY => "Berapa jumlah yang mau dipesan?",
            default => "Ada yang bisa saya bantu?"
        };
        $this->replySender->sendAndRecordQuestion($this->conv, $question);
    }

    protected function askConfirmation(Customer $customer, OrderDraft $draft): void
    {
        $data = $draft->data;
        if (empty($data['total_price']) && !empty($data['price']) && !empty($data['quantity'])) {
            $data['total_price'] = floatval($data['price']) * intval($data['quantity']);
            $draft->data = $data;
            $draft->save();
        }

        $summary = "Nama: " . ($data['customer_name'] ?? '-') . "\n"
                 . "Produk: " . ($data['product_name'] ?? '-') . "\n"
                 . "Jumlah: " . ($data['quantity'] ?? '0') . " biji\n"
                 . "Harga satuan: Rp " . number_format(floatval($data['price'] ?? 0), 0, ',', '.') . "\n"
                 . "Total: Rp " . number_format(floatval($data['total_price'] ?? 0), 0, ',', '.') . "\n"
                 . "Alamat: " . ($data['customer_address'] ?? 'Belum diisi');

        $msg = "Baik, ini ringkasan pesanan Kakak:\n\n{$summary}\n\nKetik 'iya' untuk lanjut ke pembayaran, 'ubah nama' atau 'ubah produk' jika ada yang salah.";
        $this->replySender->sendAndRecordQuestion($this->conv, $msg);
    }

    // ==================== COLLECT NAME ====================
    protected function collectName(string $message, Customer $customer): void
    {
        $extracted = $this->paramExtractor->extractParameters($message);
        $name = $extracted['customer_name'] ?? null;

        if (!$name) {
            $words = preg_split('/\s+/', trim($message));
            $blacklist = ['halo', 'hai', 'pagi', 'siang', 'sore', 'malam', 'permisi',
                'tanya', 'mau', 'order', 'pesan', 'berapa', 'price', 'list',
                'min', 'admin', 'kak', 'kakak', 'gan', 'sis', 'bro', 'ready',
                'saya', 'nama', 'aku', 'dengan', 'dari', 'ini', 'itu'];
            $filteredWords = [];
            foreach ($words as $word) {
                $clean = preg_replace('/[^a-zA-Z]/', '', $word);
                $clean = strtolower(trim($clean));
                if (strlen($clean) > 2 && !in_array($clean, $blacklist)) {
                    $filteredWords[] = ucwords($clean);
                }
            }
            if (!empty($filteredWords)) {
                $name = implode(' ', $filteredWords);
            }
            if (!$name && strlen($message) <= 30 && !preg_match('/\d/', $message)) {
                $clean = preg_replace('/[^a-zA-Z\s]/', '', $message);
                if (!empty($clean) && !in_array(strtolower($clean), $blacklist)) {
                    $name = ucwords(strtolower($clean));
                }
            }
        }

        if ($name) {
            $lowerName = strtolower($name);
            foreach (['halo', 'hai', 'pesan', 'order', 'admin', 'kak'] as $bad) {
                if ($lowerName === $bad || strpos($lowerName, $bad) === 0) {
                    $name = null;
                    break;
                }
            }
        }

        if ($name) {
            $customer->name = $name;
            $customer->save();

            $draft = $this->draftService->getOrCreateDraft($customer);
            $data = $draft->data ?? [];
            $data['customer_name'] = $name;
            $draft->data = $data;
            $draft->step = ConversationManager::STATE_ORDERING_ADDRESS;
            $draft->save();

            $this->conv->setState(ConversationManager::STATE_ORDERING_ADDRESS);
            $question = "Terima kasih, Kak {$name}. Di mana alamat lengkap Kakak?";
            $this->replySender->sendAndRecordQuestion($this->conv, $question);
            return;
        }

        $this->handleExtractionFailure(
            "Maaf, saya tidak bisa menangkap nama Kakak. Bisa sebutkan nama lengkapnya? Contoh: Budi Santoso",
            ConversationManager::STATE_ORDERING_NAME
        );
    }

    // ==================== COLLECT ADDRESS ====================
    protected function collectAddress(string $message, Customer $customer): void
    {
        $lower = strtolower(trim($message));
        if (strpos($lower, 'ongkir') !== false || strpos($lower, 'area') !== false || strpos($lower, 'biaya kirim') !== false) {
            $this->replySender->send($customer, "Untuk ongkir tergantung lokasi Kak. Kami melayani area Jabodetabek. Biaya kirim mulai Rp 15.000. Silakan tetap masukkan alamat lengkap Kakak ya.");
            return;
        }

        $address = $this->normalizeAddress($message);

        if (strlen($address) >= 5) {
            $draft = $this->draftService->getOrCreateDraft($customer);
            $data = $draft->data ?? [];
            $data['customer_address'] = $address;
            $draft->data = $data;
            $draft->step = ConversationManager::STATE_ORDERING_PRODUCT;
            $draft->save();

            $this->conv->setState(ConversationManager::STATE_ORDERING_PRODUCT);
            $question = "Baik, alamat sudah dicatat. Sekarang Kakak mau pesan produk apa? (ketik 'katalog' untuk lihat daftar)";
            $this->replySender->sendAndRecordQuestion($this->conv, $question);
            return;
        }

        $this->handleExtractionFailure(
            "Alamatnya kurang jelas. Bisa tulis alamat lengkap? Contoh: Jl. Merdeka No. 10, Indramayu, Desa Sukamulya RT 02 RW 03",
            ConversationManager::STATE_ORDERING_ADDRESS
        );
    }

    // ==================== COLLECT PRODUCT ====================
   // ==================== COLLECT PRODUCT ====================
    protected function collectProduct(string $message, Customer $customer): void
    {   
        // --- PERBAIKAN BUG PILIH NOMOR (Ditambahkan di awal) ---
        $draft = $this->draftService->getOrCreateDraft($customer);
        if (is_numeric($message) && !empty($draft->data['product_candidates'])) {
            $choice = intval($message);
            $candidates = $draft->data['product_candidates'];
            if ($choice > 0 && $choice <= count($candidates)) {
                $productId = $candidates[$choice - 1];
                $product = \App\Models\Product::find($productId);
                if ($product) {
                    $data = $draft->data;
                    $data['product_id'] = $product->id;
                    $data['product_name'] = $product->name;
                    $data['price'] = floatval($product->price);
                    unset($data['product_candidates']);
                    $draft->data = $data;
                    $draft->save();
                    $this->conv->setState(ConversationManager::STATE_ORDERING_QUANTITY);
                    $question = "{$product->name} ya. Berapa jumlah yang mau dipesan?";
                    $this->replySender->sendAndRecordQuestion($this->conv, $question);
                    return;
                }
            }
        }
        // --- AKHIR PERBAIKAN ---

        $matchResult = $this->productMatcher->match($message);

        if ($matchResult['is_catalog_question'] ?? false) {
            $this->sendProductCatalog($customer);
            return;
        }

        if ($this->isCatalogRequest($message)) {
            $this->sendProductCatalog($customer);
            return;
        }

        if ($matchResult['matched']) {
            $product = $matchResult['product'];
            $price = floatval($product->price);
            if ($price <= 0) {
                $freshProduct = \App\Models\Product::find($product->id);
                if ($freshProduct) {
                    $price = floatval($freshProduct->price);
                }
            }
            if ($price <= 0) {
                $this->replySender->send($customer, "Maaf, harga produk ini sedang tidak valid. Silakan pilih produk lain.");
                return;
            }

            $draft = $this->draftService->getOrCreateDraft($customer);
            $data = $draft->data ?? [];
            $data['product_id'] = $product->id;
            $data['product_name'] = $product->name;
            $data['price'] = $price;
            $draft->data = $data;
            $draft->step = ConversationManager::STATE_ORDERING_QUANTITY;
            $draft->save();

            $this->conv->setState(ConversationManager::STATE_ORDERING_QUANTITY);
            $question = "{$product->name} ya. Berapa jumlah yang mau dipesan? (dalam biji/buket)";
            $this->replySender->sendAndRecordQuestion($this->conv, $question);
            return;
        }

        if (!empty($matchResult['candidates']) && count($matchResult['candidates']) > 1) {
            $candidates = $matchResult['candidates'];
            $options = [];
            foreach ($candidates as $i => $prod) {
                $options[] = ($i + 1) . ". {$prod->name} (Rp " . number_format(floatval($prod->price), 0, ',', '.') . ")";
            }
            $reply = "Kami punya beberapa produk yang mirip:\n" . implode("\n", $options) . "\n\nSilakan ketik nomor pilihan Kakak.";
            $this->replySender->send($customer, $reply);
            $draft = $this->draftService->getOrCreateDraft($customer);
            $data = $draft->data ?? [];
            $data['product_candidates'] = $candidates->pluck('id')->toArray();
            $draft->data = $data;
            $draft->save();
            $this->conv->setLastQuestion($reply);
            return;
        }

        $this->handleExtractionFailure(
            "Maaf, produk yang Kakak maksud tidak ditemukan. Coba sebutkan nama produk lain, atau ketik 'katalog' untuk lihat daftar produk.",
            ConversationManager::STATE_ORDERING_PRODUCT
        );
    }

    // ==================== COLLECT QUANTITY ====================
protected function collectQuantity(string $message, Customer $customer): void 
{
    $extracted = $this->paramExtractor->extractParameters($message);
    $quantity = $extracted['quantity'] ?? null;
    $draft = $this->draftService->getOrCreateDraft($customer);

    // OPTIMASI 1: Cek apakah user input angka murni yang bisa dianggap quantity
    if (is_numeric($message) && intval($message) > 10) { 
        // Contoh: Jika input > 10, hampir pasti itu quantity, bukan pilihan menu (1, 2, 3)
        $quantity = intval($message);
    }

    // Bagian pemilihan kandidat produk (hanya jalan jika quantity belum ketemu)
    if (!$quantity && !empty($draft->data['product_candidates'])) {
        $choice = intval($message);
        if ($choice > 0 && $choice <= count($draft->data['product_candidates'])) {
            // ... (Logika pemilihan produk kamu sudah benar)
            return;
        }
    }

    // Validasi Quantity
    $quantity = intval($quantity ?: $message); // Fallback ke message jika extractor gagal tapi isinya angka
    
    if ($quantity > 0) {
        $data = $draft->data ?? [];
        
        // OPTIMASI 2: Pastikan product_id sudah ada sebelum lanjut
        if (empty($data['product_id'])) {
            $this->replySender->send($customer, "Produknya belum terpilih. Silakan pilih produk dulu ya.");
            $this->conv->setState(ConversationManager::STATE_ORDERING_PRODUCT);
            return;
        }

        $data['quantity'] = $quantity;
        $price = floatval($data['price'] ?? 0);

        if ($price <= 0) {
            $this->replySender->send($customer, "Terjadi kesalahan harga. Silakan ulangi pesan produk.");
            $this->conv->setState(ConversationManager::STATE_ORDERING_PRODUCT);
            return;
        }

        $data['total_price'] = $price * $quantity;
        $draft->data = $data;
        $draft->step = ConversationManager::STATE_ORDERING_CONFIRMING;
        $draft->save();

        $this->conv->setState(ConversationManager::STATE_ORDERING_CONFIRMING);
        $this->askConfirmation($customer, $draft);
        return;
    }

    $this->handleExtractionFailure(
        "Maaf, berapa jumlah yang ingin dipesan? Masukkan angka saja, contoh: 2", 
        ConversationManager::STATE_ORDERING_QUANTITY
    );
}

    // ==================== HANDLE CONFIRMATION ====================
    protected function handleConfirmation(string $message, Customer $customer): void
    {
        $lower = strtolower(trim($message));
        $draft = $this->draftService->getCustomerActiveDraft($customer);

        if (!$draft) {
            $this->cancelOrder($customer);
            return;
        }

        if (strpos($lower, 'ubah nama') !== false) {
            $this->conv->setState(ConversationManager::STATE_ORDERING_NAME);
            $this->replySender->send($customer, "Baik, kita ulangi nama. Siapa nama lengkap Kakak?");
            return;
        }

        if (strpos($lower, 'ubah produk') !== false) {
            $this->conv->setState(ConversationManager::STATE_ORDERING_PRODUCT);
            $this->replySender->send($customer, "Baik, silakan pilih produk lain. Ketik nama produk atau 'katalog'.");
            return;
        }

        if (in_array($lower, ['iya', 'ya', 'setuju', 'ok', 'lanjut', 'betul', 'benar'])) {
            if (empty($draft->data['total_price'])) {
                $price = floatval($draft->data['price'] ?? 0);
                $qty = intval($draft->data['quantity'] ?? 1);
                $draft->data['total_price'] = $price * $qty;
            }
            $draft->step = ConversationManager::STATE_ORDERING_PAYMENT;
            $draft->save();
            $this->conv->setState(ConversationManager::STATE_ORDERING_PAYMENT);
            $paymentMsg = "Baik, ringkasan sudah benar. Sekarang pilih metode pembayaran:\n\n" .
                         "1. 💰 COD (Bayar di tempat)\n" .
                         "2. 🏦 Transfer Bank\n" .
                         "3. 📱 QRIS\n\n" .
                         "Ketik '1', '2', atau '3' ya.";
            $this->replySender->sendAndRecordQuestion($this->conv, $paymentMsg);
            return;
        }

        $intentClassifier = new IntentClassifier();
        if ($intentClassifier->classify($message) === 'cancel') {
            $this->cancelOrder($customer);
            return;
        }

        $retries = $this->conv->incrementRetry();
        if ($retries >= self::MAX_RETRY) {
            $this->replySender->send($customer, "Tampaknya bingung. Ketik 'bantuan' untuk admin.");
        } else {
            $this->replySender->send($customer, "Ketik 'iya' untuk lanjut, 'ubah nama' atau 'ubah produk' untuk ganti.");
        }
    }

    // ==================== COLLECT PAYMENT METHOD ====================
    protected function collectPaymentMethod(string $message, Customer $customer): void
    {
        $message = strtolower(trim($message));

        $paymentMethod = null;
        if ($message === '1' || strpos($message, 'cod') !== false || strpos($message, 'bayar di tempat') !== false) {
            $paymentMethod = 'cod';
        } elseif ($message === '2' || strpos($message, 'transfer') !== false || strpos($message, 'bank') !== false) {
            $paymentMethod = 'bank_transfer';
        } elseif ($message === '3' || strpos($message, 'qris') !== false) {
            $paymentMethod = 'qris';
        }

        if (!$paymentMethod) {
            $retries = $this->conv->incrementRetry();
            if ($retries >= self::MAX_RETRY) {
                $this->replySender->send($customer, "Sepertinya ada kesulitan memilih metode. Ketik 'bantuan' untuk admin.");
            } else {
                $this->replySender->send($customer, "Metode tidak dikenali. Pilih 1 (COD), 2 (Transfer), atau 3 (QRIS) ya.");
            }
            return;
        }

        $draft = $this->draftService->getCustomerActiveDraft($customer);
        if (!$draft) {
            $this->replySender->send($customer, "Pesanan tidak ditemukan. Silakan mulai ulang dengan ketik 'pesan'.");
            $this->conv->reset();
            return;
        }

        $data = $draft->data;
        if (empty($data['product_id']) || empty($data['quantity']) || empty($data['customer_name']) || empty($data['customer_address'])) {
            $this->replySender->send($customer, "Data pesanan tidak lengkap. Silakan mulai ulang.");
            $this->conv->reset();
            return;
        }

        if (empty($data['total_price']) || floatval($data['total_price']) <= 0) {
            $price = floatval($data['price'] ?? 0);
            $qty = intval($data['quantity'] ?? 0);
            if ($price > 0 && $qty > 0) {
                $data['total_price'] = $price * $qty;
                $draft->data = $data;
                $draft->save();
            } else {
                $this->replySender->send($customer, "Terjadi kesalahan pada perhitungan harga. Silakan ulangi pesan produk.");
                $this->conv->setState(ConversationManager::STATE_ORDERING_PRODUCT);
                return;
            }
        }

        try {
            if ($paymentMethod === 'cod') {
                $data['payment_method'] = 'cod';
                $draft->data = $data;
                $draft->save();
                $order = $this->draftService->completeDraft($draft);
                $this->conv->reset();
                $this->replySender->send($customer, "Pesanan #{$order->id} dengan COD sudah tercatat. Admin akan segera memproses. Terima kasih! ✅");
                return;
            }

            $order = Order::create([
                'customer_id' => $customer->id,
                'total_price' => $data['total_price'],
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'notes' => 'Draft ID: ' . $draft->id,
            ]);

            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'price' => $data['price'],
                'subtotal' => $data['total_price'],
            ]);

            $customer->update([
                'name' => $data['customer_name'],
                'address' => $data['customer_address'],
            ]);

            $draft->delete();

            $midtransService = app(MidtransService::class);

            if ($paymentMethod === 'bank_transfer') {
                $result = $midtransService->createBankTransfer($order, 'bca');
                if ($result['success']) {
                    $order->update([
                        'payment_status' => 'pending',
                        'payment_data' => $result
                    ]);
                    $va = $result['va_numbers'][0]['va_number'] ?? 'Tidak tersedia';
                    $bankName = strtoupper($result['va_numbers'][0]['bank'] ?? 'BCA');
                    $total = number_format($order->total_price, 0, ',', '.');
                    $msg = "💳 *Pembayaran Transfer Bank*\n\n"
                         . "Total: Rp {$total}\n"
                         . "Metode: Transfer {$bankName}\n"
                         . "Nomor Virtual Account: *{$va}*\n\n"
                         . "Silakan lakukan pembayaran sebelum batas waktu. Kirim bukti transfer jika sudah ya.";
                    $this->replySender->send($customer, $msg);
                    $this->conv->reset();
                } else {
                    $this->replySender->send($customer, "Maaf, gagal membuat transaksi. Silakan coba lagi nanti.");
                    $this->conv->reset();
                }
            } elseif ($paymentMethod === 'qris') {
                $result = $midtransService->createQRISPayment($order);
                if ($result['success']) {
                    $order->update([
                        'payment_status' => 'pending',
                        'payment_data' => $result
                    ]);
                   if ($result['qr_code_url']) {
    // Kirim gambar QR code langsung (best practice)
    try {
        $this->replySender->sendImage($customer, $result['qr_code_url'], 'Scan QRIS berikut untuk pembayaran');
        Log::info('QRIS image sent', ['order_id' => $order->id]);
    } catch (\Exception $e) {
        Log::warning('Failed to send QR image, fallback to URL', ['error' => $e->getMessage()]);
        // Fallback: kirim URL sebagai teks
        $this->replySender->send($customer, 
            "📱 *Pembayaran QRIS*\nSilakan scan QR code berikut:\n" . 
            $result['qr_code_url']
        );
    }
    
    // Selalu kirim total + URL QR untuk memudahkan testing
    $this->replySender->send($customer, 
        "Total: Rp " . number_format($order->total_price, 0, ',', '.') . "\n" .
        "URL QR: " . $result['qr_code_url'] . "\n\n" .
        "Silakan lakukan pembayaran dan kirim bukti."
    );
} else {
    $this->replySender->send($customer, 
        "QRIS berhasil dibuat, tapi QR code belum tersedia. Silakan coba lagi nanti."
    );
    $this->replySender->send($customer, 
        "Total: Rp " . number_format($order->total_price, 0, ',', '.') . "\nSilakan lakukan pembayaran dan kirim bukti."
    );
}
                    $this->replySender->send($customer, "Total: Rp " . number_format($order->total_price, 0, ',', '.') . "\nSilakan lakukan pembayaran dan kirim bukti.");
                    $this->conv->reset();
                } else {
                    $this->replySender->send($customer, "Gagal membuat transaksi QRIS: " . $result['error']);
                    $this->conv->reset();
                }
            }
        } catch (\Exception $e) {
            Log::error('Error saat proses pembayaran: ' . $e->getMessage());
            $this->replySender->send($customer, "Terjadi kesalahan. Silakan coba lagi atau hubungi admin.");
            $this->conv->reset();
        }
    }


    protected function finalizeOrder(Customer $customer): void
    {
        $draft = $this->draftService->getCustomerActiveDraft($customer);
        if (!$draft) {
            $this->replySender->send($customer, "Maaf, pesanan tidak ditemukan. Silakan mulai ulang dengan ketik 'pesan'.");
            $this->conv->reset();
            return;
        }
        try {
            $order = $this->draftService->completeDraft($draft);
            $this->replySender->send($customer, "Terima kasih! Pesanan Kakak sudah kami terima dengan nomor #{$order->id}. Admin akan segera memproses. 😊");
            $this->conv->reset();
        } catch (\Exception $e) {
            Log::error('Gagal menyelesaikan pesanan', ['error' => $e->getMessage()]);
            $this->replySender->send($customer, "Maaf, terjadi kesalahan saat membuat pesanan. Admin akan segera membantu.");
            $this->conv->setAdminHandled(true);
        }
    }

    protected function cancelOrder(Customer $customer): void
    {
        $draft = $this->draftService->getCustomerActiveDraft($customer);
        if ($draft) {
            $draft->delete();
        }
        $this->conv->reset();
        $this->replySender->send($customer, "Pesanan dibatalkan. Jika ingin memesan lagi, ketik 'pesan' ya.");
    }

    protected function isCancellationCommand(string $message): bool
    {
        $cancelWords = ['batal', 'cancel', 'batalkan', 'tidak jadi'];
        $lower = strtolower(trim($message));
        foreach ($cancelWords as $word) {
            if ($lower === $word || strpos($lower, $word) === 0) {
                return true;
            }
        }
        return false;
    }

    protected function isCatalogRequest(string $message): bool
    {
        $keywords = [
            'katalog', 'list produk', 'daftar produk', 'produk apa saja', 'lihat produk',
            'apa saja', 'ada apa', 'apa aja', 'ada buket apa', 'punya apa',
            'menu', 'pilihan', 'opsi', 'ada buket apa aja', 'lihat daftar', 'tampilkan produk'
        ];
        $lower = strtolower(trim($message));
        foreach ($keywords as $kw) {
            if (strpos($lower, $kw) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function sendProductCatalog(Customer $customer): void
    {
        $products = \App\Models\Product::where('is_active', true)->orderBy('name')->get();
        if ($products->isEmpty()) {
            $this->replySender->send($customer, "Maaf, katalog produk sedang kosong.");
            return;
        }
        $list = [];
        foreach ($products as $i => $p) {
            $list[] = ($i + 1) . ". {$p->name} - Rp " . number_format($p->price, 0, ',', '.');
        }
        $draft = $this->draftService->getOrCreateDraft($customer);
        $data = $draft->data ?? [];
        $data['product_candidates'] = $products->pluck('id')->toArray();
        $draft->data = $data;
        $draft->save();
        $msg = "📦 *Katalog Produk Buket Cute*\n\n" . implode("\n", $list) . "\n\nSilakan ketik nomor atau nama produk yang ingin dipesan. Contoh: ketik '1' atau 'buket mawar'.";
        $this->replySender->send($customer, $msg);
        $this->conv->setLastQuestion($msg);
    }

    protected function handleExtractionFailure(string $retryMessage, string $expectedState): void
    {
        $retryCount = $this->conv->incrementRetry();
        if ($retryCount >= self::MAX_RETRY) {
            $this->conv->setAdminHandled(true);
            $this->replySender->send($this->customer, "Sepertinya ada kesulitan. Admin kami akan segera membantu. Mohon tunggu sebentar ya. 🙏");
            return;
        }
        $this->replySender->sendAndRecordQuestion($this->conv, $retryMessage);
    }

    private function normalizeAddress(string $rawMessage): string
    {
        $msg = trim($rawMessage);
        $msg = preg_replace('/^alamat\s+/i', '', $msg);
        return $msg;
    }
}