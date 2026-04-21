<?php

namespace App\Services\Chatbot;

use App\Models\Customer;
use App\Models\OrderDraft;
use App\Services\OrderDraftService;
use App\Services\ParameterExtractionService;
use App\Services\ParameterValidationService;
use Illuminate\Support\Facades\Log;

class OrderFlowManager
{
    protected ConversationManager $conv;
    protected OrderDraftService $draftService;
    protected ProductMatcher $productMatcher;
    protected ParameterExtractionService $paramExtractor;
    protected ParameterValidationService $paramValidator;
    protected ReplySender $replySender;
    protected Customer $customer;

    // Batas maksimum retry per state sebelum eskalasi
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

    /**
     * Entry point untuk memproses pesan dalam konteks pemesanan.
     */
    public function process(string $message, Customer $customer): void
    {
        $state = $this->conv->getState();

        // Jika tidak ada state, berarti baru mulai pesan
        if ($state === null || $state === ConversationManager::STATE_IDLE) {
            $this->startOrder($customer, $message); // <-- perbaikan: tambahkan $message
            return;
        }

        if ($this->isCancellationCommand($message)) {
        $this->cancelOrder($customer);
        return;
    }

        // Route berdasarkan state saat ini
        switch ($state) {
            case ConversationManager::STATE_ORDERING_NAME:
                $this->collectName($message, $customer);
                break;

            case ConversationManager::STATE_ORDERING_PRODUCT:
                $this->collectProduct($message, $customer);
                break;

            case ConversationManager::STATE_ORDERING_QUANTITY:
                $this->collectQuantity($message, $customer);
                break;

            case ConversationManager::STATE_ORDERING_ADDRESS:
                $this->collectAddress($message, $customer);
                break;

            case ConversationManager::STATE_ORDERING_PAYMENT:
                $this->collectPaymentMethod($message, $customer);
                break;

            case ConversationManager::STATE_ORDERING_CONFIRMING:
                $this->handleConfirmation($message, $customer);
                break;

            default:
                Log::warning('Unknown order state, resetting', ['state' => $state]);
                $this->conv->reset();
                $this->startOrder($customer, $message);
                break;
        }
    }

    /**
     * Mulai alur pemesanan: minta nama.
     */
    protected function startOrder(Customer $customer, string $firstMessage): void
    {
        // Hapus draft lama
        $oldDraft = $this->draftService->getCustomerActiveDraft($customer);
        if ($oldDraft) {
            $oldDraft->delete();
        }

        // Buat draft baru
        $draft = $this->draftService->getOrCreateDraft($customer);
        
        // Coba ekstrak semua parameter dari pesan pertama
        $extracted = $this->paramExtractor->extractParameters($firstMessage);
        $data = $draft->data ?? [];
        
        // Masukkan hasil ekstraksi ke data draft
        if (!empty($extracted['product_data'])) {
            $data['product_id'] = $extracted['product_data']['product_id'];
            $data['product_name'] = $extracted['product_data']['product_name'];
            $data['price'] = $extracted['product_data']['price'];
        }
        if (!empty($extracted['quantity'])) {
            $data['quantity'] = $extracted['quantity'];
        }
        if (!empty($extracted['address'])) {
            $data['customer_address'] = $extracted['address'];
        }
        // Nama dari customer->name tidak diekstrak ulang
        
        $draft->data = $data;
        
        // Tentukan state awal berdasarkan kelengkapan data
        $missing = [];
        if (empty($customer->name)) $missing[] = 'name';
        if (empty($data['product_id'])) $missing[] = 'product';
        if (empty($data['quantity'])) $missing[] = 'quantity';
        if (empty($data['customer_address'])) $missing[] = 'address';
        
        if (empty($missing)) {
            // Semua lengkap? Langsung konfirmasi
            $draft->step = ConversationManager::STATE_ORDERING_CONFIRMING;
            $draft->save();
            $this->conv->setState(ConversationManager::STATE_ORDERING_CONFIRMING);
            $this->askConfirmation($customer, $draft);
            return;
        }
        
        // Jika ada yang kurang, mulai dari yang pertama hilang
        $firstMissing = $missing[0];
        $nextState = match ($firstMissing) {
            'name'    => ConversationManager::STATE_ORDERING_NAME,
            'product' => ConversationManager::STATE_ORDERING_PRODUCT,
            'quantity'=> ConversationManager::STATE_ORDERING_QUANTITY,
            'address' => ConversationManager::STATE_ORDERING_ADDRESS,
            default   => ConversationManager::STATE_ORDERING_NAME,
        };
        
        $draft->step = $nextState;
        $draft->save();
        $this->conv->setState($nextState);
        
        // Ajukan pertanyaan sesuai state
        $this->askQuestionForState($nextState, $customer);
    }


protected function askQuestionForState(string $state, Customer $customer): void
{
    $question = match ($state) {
        ConversationManager::STATE_ORDERING_NAME => "Siapa nama lengkap Kakak?",
        ConversationManager::STATE_ORDERING_PRODUCT => "Produk apa yang ingin dipesan?",
        ConversationManager::STATE_ORDERING_QUANTITY => "Berapa jumlah yang mau dipesan?",
        ConversationManager::STATE_ORDERING_ADDRESS => "Kalau boleh tau alamat kaka di mana ya?.",
        default => "Ada yang bisa saya bantu?"
    };
    $this->replySender->sendAndRecordQuestion($this->conv, $question);
}

protected function askConfirmation(Customer $customer, OrderDraft $draft): void
    {
        $summary = $this->paramValidator->formatOrderSummary($draft->data);
        $msg = "Baik, ini ringkasan pesanan Kakak:\n\n{$summary}\n\nApakah sudah benar? Ketik 'iya' untuk lanjut, atau 'ubah' jika ada yang salah.";
        $this->replySender->sendAndRecordQuestion($this->conv, $msg);
    }


    /**
     * Kumpulkan nama pelanggan.
     */
  protected function collectName(string $message, Customer $customer): void
    {
        // 1. Coba ekstrak nama menggunakan ParameterExtractionService
        $extracted = $this->paramExtractor->extractParameters($message);
        $name = $extracted['customer_name'] ?? null;

        // 2. Jika ekstraksi gagal, coba fallback: ambil kata pertama yang bukan blacklist
        if (!$name) {
            $words = preg_split('/\s+/', trim($message));
            $blacklist = [
                'halo', 'hai', 'pagi', 'siang', 'sore', 'malam', 'permisi',
                'tanya', 'mau', 'order', 'pesan', 'berapa', 'price', 'list',
                'min', 'admin', 'kak', 'kakak', 'gan', 'sis', 'bro', 'ready',
                'saya', 'nama', 'aku', 'dengan', 'dari', 'ini', 'itu',
            ];


            $filteredWords = [];
            foreach ($words as $word) {
            $clean = preg_replace('/[^a-zA-Z]/', '', $word);
            $clean = strtolower(trim($clean));
            if (strlen($clean) > 2 && !in_array($clean, $blacklist)) {
                $filteredWords[] = ucwords($clean);
            }
        }

             if (!empty($filteredWords)) {
            // Gabungkan semua kata yang lolos menjadi nama lengkap
            $name = implode(' ', $filteredWords);
            }

            // Jika masih tidak dapat, coba ambil seluruh kalimat asalkan pendek dan tanpa angka
            if (!$name && strlen($message) <= 30 && !preg_match('/\d/', $message)) {
                $clean = preg_replace('/[^a-zA-Z\s]/', '', $message);
                if (!empty($clean) && !in_array(strtolower($clean), $blacklist)) {
                    $name = ucwords(strtolower($clean));
                }
            }
        }

        // 3. Validasi nama tidak boleh kosong dan tidak mengandung kata blacklist dominan
        if ($name) {
            $lowerName = strtolower($name);
            foreach (['halo', 'hai', 'pesan', 'order', 'admin', 'kak'] as $bad) {
                if ($lowerName === $bad || strpos($lowerName, $bad) === 0) {
                    $name = null;
                    break;
                }
            }
        }

        // 4. Jika nama valid, simpan dan lanjutkan
        if ($name) {
            $customer->name = $name;
            $customer->save();

            $draft = $this->draftService->getOrCreateDraft($customer);
            $data = $draft->data ?? [];
            $data['customer_name'] = $name;
            $draft->data = $data;
            $draft->step = ConversationManager::STATE_ORDERING_PRODUCT;
            $draft->save();

            $this->conv->setState(ConversationManager::STATE_ORDERING_PRODUCT);

            $question = "Terima kasih, Kak {$name}. Produk apa yang ingin dipesan?";
            $this->replySender->sendAndRecordQuestion($this->conv, $question);
            return;
        }

        // 5. Gagal mendapatkan nama → retry
        $this->handleExtractionFailure(
            "Maaf, saya tidak bisa menangkap nama Kakak. Bisa sebutkan nama lengkapnya? Contoh: Budi Santoso",
            ConversationManager::STATE_ORDERING_NAME
        );
    }

    /**
     * Kumpulkan produk yang dipesan.
     */
    protected function collectProduct(string $message, Customer $customer): void
    {
        // Gunakan ProductMatcher untuk mencari produk
        $matchResult = $this->productMatcher->match($message);

        // Jika deteksi sebagai pertanyaan katalog, tampilkan daftar produk
        if ($matchResult['is_catalog_question'] ?? false) {
            $this->sendProductCatalog($customer);
            return; // Tetap di state collecting_product
        }

        if ($this->isCatalogRequest($message)) {
            $this->sendProductCatalog($customer);
            return; // tetap di state collecting_product
        }

        if ($matchResult['matched']) {
            // Produk ditemukan (single match dengan confidence tinggi)
            $product = $matchResult['product'];
            $draft = $this->draftService->getOrCreateDraft($customer);
            $data = $draft->data ?? [];
            $data['product_id'] = $product->id;
            $data['product_name'] = $product->name;
            $data['price'] = $product->price;
            $draft->data = $data;
            $draft->step = ConversationManager::STATE_ORDERING_QUANTITY;
            $draft->save();

            $this->conv->setState(ConversationManager::STATE_ORDERING_QUANTITY);

            $question = "{$product->name} ya. Berapa jumlah yang mau dipesan? (dalam biji/buket)";
            $this->replySender->sendAndRecordQuestion($this->conv, $question);
            return;
        }

        // Jika ada beberapa kandidat, tawarkan pilihan
        if (!empty($matchResult['candidates']) && count($matchResult['candidates']) > 1) {
            $candidates = $matchResult['candidates'];
            $options = [];
            foreach ($candidates as $i => $prod) {
                $options[] = ($i + 1) . ". {$prod->name} (Rp " . number_format($prod->price, 0, ',', '.') . ")";
            }
            $reply = "Kami punya beberapa produk yang mirip:\n" . implode("\n", $options) . "\n\nSilakan ketik nomor pilihan Kakak.";
            $this->replySender->send($customer, $reply);
            // Simpan kandidat ke draft untuk referensi nanti
            $draft = $this->draftService->getOrCreateDraft($customer);
            $data = $draft->data ?? [];
            $data['product_candidates'] = $candidates->pluck('id')->toArray();
            $draft->data = $data;
            $draft->save();
            // Tidak mengubah state, tetap di collecting_product
            $this->conv->setLastQuestion($reply);
            return;
        }

        // Tidak ada produk cocok
        $this->handleExtractionFailure(
            "Maaf, produk yang Kakak maksud tidak ditemukan. Coba sebutkan nama produk lain, atau ketik 'katalog' untuk lihat daftar produk.",
            ConversationManager::STATE_ORDERING_PRODUCT
        );
    }

    /**
     * Kumpulkan jumlah pesanan.
     */
    protected function collectQuantity(string $message, Customer $customer): void
    {
        $extracted = $this->paramExtractor->extractParameters($message);
        $quantity = $extracted['quantity'] ?? null;

        // Cek apakah user memilih dari kandidat (jika sebelumnya ada multiple candidates)
        $draft = $this->draftService->getOrCreateDraft($customer);
        if (!$quantity && !empty($draft->data['product_candidates'])) {
            $choice = intval($message);
            if ($choice > 0 && $choice <= count($draft->data['product_candidates'])) {
                $productId = $draft->data['product_candidates'][$choice - 1];
                $product = \App\Models\Product::find($productId);
                if ($product) {
                    $data = $draft->data;
                    $data['product_id'] = $product->id;
                    $data['product_name'] = $product->name;
                    $data['price'] = $product->price;
                    unset($data['product_candidates']);
                    $draft->data = $data;
                    $draft->save();
                    // Sekarang minta jumlah
                    $question = "Baik, {$product->name} ya. Berapa jumlah yang mau dipesan?";
                    $this->replySender->sendAndRecordQuestion($this->conv, $question);
                    return;
                }
            }
        }

        if ($quantity && $quantity > 0) {
            $data = $draft->data ?? [];
            $data['quantity'] = $quantity;
            $draft->data = $data;
            $draft->step = ConversationManager::STATE_ORDERING_ADDRESS;
            $draft->save();

            $this->conv->setState(ConversationManager::STATE_ORDERING_ADDRESS);

            $question = "Baik, jumlahnya {$quantity}. Sekarang tulis alamat lengkap pengiriman ya.";
            $this->replySender->sendAndRecordQuestion($this->conv, $question);
            return;
        }

        $this->handleExtractionFailure(
            "Saya tidak bisa menangkap jumlahnya. Bisa sebutkan angka, misal: 2",
            ConversationManager::STATE_ORDERING_QUANTITY
        );
    }

    /**
     * Kumpulkan alamat pengiriman.
     */
    protected function collectAddress(string $message, Customer $customer): void
{
    // 1. Coba ekstrak alamat via service
    $extracted = $this->paramExtractor->extractParameters($message);
    $address = $extracted['address'] ?? null;

    // 2. Jika hasil ekstraksi tidak valid (null atau terlalu pendek), gunakan pesan asli
    if (!$address || strlen($address) < 10) {
        // Hapus kata "alamat" di awal (case insensitive) jika ada, lalu trim
        $address = preg_replace('/^alamat\s+/i', '', trim($message));
        // Jika setelah dihapus masih kosong, kembalikan ke pesan asli
        if (strlen($address) < 5) {
            $address = trim($message);
        }
    }

    // 3. Validasi panjang minimal 5 karakter
    if (strlen($address) >= 5) {
        $draft = $this->draftService->getOrCreateDraft($customer);
        $data = $draft->data ?? [];
        $data['customer_address'] = $address;
        $draft->data = $data;
        $draft->step = ConversationManager::STATE_ORDERING_PAYMENT;
        $draft->save();

        $this->conv->setState(ConversationManager::STATE_ORDERING_PAYMENT);

        $paymentMsg = "Baik, alamat sudah tercatat. Sekarang untuk metode pembayaran bagaimana:\n\n" .
                     "1. 💰 COD (Bayar di tempat)\n" .
                     "2. 🏦 Transfer Bank\n\n" .
                     "Ketik '1' untuk COD atau '2' untuk Transfer Bank.";
        $this->replySender->sendAndRecordQuestion($this->conv, $paymentMsg);
        return;
    }

    // 4. Gagal total, minta ulang
    $this->handleExtractionFailure(
        "Alamatnya kurang jelas. Bisa tulis alamat lengkap? Contoh: Jl. Merdeka No. 10, Indramayu, Desa Sukamulya RT 02 RW 03",
        ConversationManager::STATE_ORDERING_ADDRESS
    );
}

    /**
     * Kumpulkan metode pembayaran
     */
    protected function collectPaymentMethod(string $message, Customer $customer): void
    {
        $message = strtolower(trim($message));

        $paymentMethod = null;
        if ($message === '1' || strpos($message, 'cod') !== false || strpos($message, 'bayar di tempat') !== false) {
            $paymentMethod = 'cod';
        } elseif ($message === '2' || strpos($message, 'transfer') !== false || strpos($message, 'bank') !== false) {
            $paymentMethod = 'bank_transfer';
        }

        if ($paymentMethod) {
            $draft = $this->draftService->getOrCreateDraft($customer);
            $data = $draft->data ?? [];
            $data['payment_method'] = $paymentMethod;
            $draft->data = $data;
            $draft->step = ConversationManager::STATE_ORDERING_CONFIRMING;
            $draft->save();

            $this->conv->setState(ConversationManager::STATE_ORDERING_CONFIRMING);

            // Generate ringkasan pesanan dengan metode pembayaran
            $summary = $this->paramValidator->formatOrderSummary($draft->data);
            $paymentName = $paymentMethod === 'cod' ? 'COD (Bayar di tempat)' : 'Transfer Bank';
            $confirmMsg = "Baik, metode pembayaran: {$paymentName}\n\nIni ringkasan lengkap pesanan Kakak:\n\n{$summary}\n\nApakah sudah benar? Ketik 'iya' untuk konfirmasi, atau 'ubah' jika ada yang salah.";
            $this->replySender->sendAndRecordQuestion($this->conv, $confirmMsg);
            return;
        }

        // Metode pembayaran tidak valid
        $retryMsg = "Metode pembayaran tidak valid. Silakan pilih:\n\n" .
                   "1. 💰 COD (Bayar di tempat)\n" .
                   "2. 🏦 Transfer Bank\n\n" .
                   "Ketik '1' atau '2'.";
        $this->replySender->sendAndRecordQuestion($this->conv, $retryMsg);
    }

    /**
     * Tangani konfirmasi atau pembatalan.
     */
    protected function handleConfirmation(string $message, Customer $customer): void
    {
        $intentClassifier = new IntentClassifier();
        $intent = $intentClassifier->classify($message);

        if ($intent === 'confirm') {
            $this->finalizeOrder($customer);
        } elseif ($intent === 'cancel') {
            $this->cancelOrder($customer);
        } else {
            // Tidak jelas, tanya ulang
            $this->replySender->send($customer, "Ketik 'iya' untuk konfirmasi atau 'ubah' untuk mengubah data.");
        }
    }

    /**
     * Selesaikan pesanan: buat order dari draft.
     */
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

            $reply = "Terima kasih! Pesanan Kakak sudah kami terima dengan nomor #{$order->id}. Admin akan segera memproses. 😊";
            $this->replySender->send($customer, $reply);

            $this->conv->reset();
        } catch (\Exception $e) {
            Log::error('Gagal menyelesaikan pesanan', ['error' => $e->getMessage()]);
            $this->replySender->send($customer, "Maaf, terjadi kesalahan saat membuat pesanan. Admin akan segera membantu.");
            $this->conv->setAdminHandled(true);
        }
    }

    /**
     * Batalkan pesanan dan reset.
     */
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
/**
 * Deteksi apakah pesan meminta katalog produk.
 */
protected function isCatalogRequest(string $message): bool
{
    $keywords = [
        'katalog', 'list produk', 'daftar produk', 'produk apa saja', 'lihat produk',
        'apa saja', 'ada apa', 'apa aja', 'ada buket apa', 'punya apa',
        'menu', 'pilihan', 'opsi', 'ada buket apa aja',
        'lihat daftar', 'tampilkan produk', 'list buket'
    ];
    $lower = strtolower(trim($message));
    foreach ($keywords as $kw) {
        if (strpos($lower, $kw) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Kirim daftar produk aktif ke customer.
 */
protected function sendProductCatalog(Customer $customer): void
{
    $products = \App\Models\Product::where('is_active', true)
        ->orderBy('name')
        ->get();

    if ($products->isEmpty()) {
        $this->replySender->send($customer, "Maaf, katalog produk sedang kosong.");
        return;
    }

    $list = [];
    foreach ($products as $i => $p) {
        $list[] = ($i + 1) . ". {$p->name} - Rp " . number_format($p->price, 0, ',', '.');
    }

    // Simpan product candidates ke draft agar user bisa memilih dengan nomor
    $draft = $this->draftService->getOrCreateDraft($customer);
    $data = $draft->data ?? [];
    $data['product_candidates'] = $products->pluck('id')->toArray();
    $draft->data = $data;
    $draft->save();

    $msg = "📦 *Katalog Produk Buket Cute*\n\n" . implode("\n", $list) . "\n\nSilakan ketik nomor atau nama produk yang ingin dipesan. Contoh: ketik '1' atau 'buket mawar'.";
    $this->replySender->send($customer, $msg);
    $this->conv->setLastQuestion($msg);
}
    /**
     * Tangani kegagalan ekstraksi parameter.
     * Naikkan retry counter, jika mencapai batas -> eskalasi.
     */
    protected function handleExtractionFailure(string $retryMessage, string $expectedState): void
    {
        $retryCount = $this->conv->incrementRetry();

        if ($retryCount >= self::MAX_RETRY) {
            // Eskalasi ke admin
            $this->conv->setAdminHandled(true);
            $this->replySender->send(
                 $this->customer,
                "Sepertinya ada kesulitan. Admin kami akan segera membantu. Mohon tunggu sebentar ya. 🙏"
            );
            // TODO: Notifikasi admin
            return;
        }

        // Kirim pertanyaan ulang dengan hint
        $this->replySender->sendAndRecordQuestion($this->conv, $retryMessage);
        // State tetap (tidak berubah)
    }

  
}