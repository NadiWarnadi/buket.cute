<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FuzzyRule;
use App\Models\MasterState;
use App\Models\Order;
use App\Models\OrderDraft;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\FuzzyBotService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class ChatbotService
{
    protected FuzzyBotService $fuzzy;
    protected ParameterExtractionService $extractor;
    protected OrderDraftService $draftService;
    protected WhatsAppService $wa;

    public function __construct(
        FuzzyBotService $fuzzy,
        ParameterExtractionService $extractor,
        OrderDraftService $draftService,
        WhatsAppService $wa
    ) {
        $this->fuzzy = $fuzzy;
        $this->extractor = $extractor;
        $this->draftService = $draftService;
        $this->wa = $wa;
    }

    /**
     * Proses pesan masuk, kembalikan array string balasan.
     */
    public function processMessage(string $sessionId, string $message): array
    {
        $customer = Customer::firstOrCreate(
            ['phone' => $sessionId],
            ['phone' => $sessionId]
        );

        $oldLastActivity = $customer->last_activity_at;
        $customer->update(['last_activity_at' => now()]);

        $draft = $this->draftService->getCustomerActiveDraft($customer);

        if (trim($message) === '') {
            return ["Maaf, saya tidak menerima pesan. Silakan ketik nama buket, *katalog*, atau *bantuan*.\n"];
        }

        // 1. RESUME jika lama tidak aktif & ada draft
        if ($oldLastActivity && Carbon::parse($oldLastActivity)->lt(now()->subMinutes(30)) && $draft) {
            return $this->handleResume($customer, $draft);
        }

        // 2. Jika belum punya state, mulai dari 1
        if (!$customer->current_state_id) {
            $customer->update(['current_state_id' => 1]);
            return $this->executeState($customer, $draft, MasterState::find(1), $message);
        }

        $state = MasterState::find($customer->current_state_id);
        if (!$state) {
            $customer->update(['current_state_id' => 1]);
            $state = MasterState::find(1);
            if (!$state) {
                $state = new MasterState();
                $state->id = 1;
            }
        }

        // 3. Cek global intent dulu
        $globalReply = $this->handleGlobalIntent($customer, $state, $message);
        if ($globalReply) {
            return $globalReply;
        }

        // 4. Proses state saat ini
        return $this->executeState($customer, $draft, $state, $message);
    }

    // ===================== STATE EXECUTOR =====================

    private function executeState(Customer $customer, ?OrderDraft $draft, MasterState $state, string $message): array
    {
        switch ($state->id) {
            case 1:
                return $this->stateGreeting($customer, $state);
            case 2:
                return $this->stateCollectNameAddress($customer, $draft, $state, $message);
            case 3:
                if ($draft && !empty($draft->data['suggestions']) && preg_match('/^\d+$/', trim($message))) {
                    $index = (int) trim($message) - 1;
                    $suggestions = $draft->data['suggestions'];
                    if (isset($suggestions[$index])) {
                        $product = Product::find($suggestions[$index]);
                        if ($product) {
                            return $this->confirmProductSelection($customer, $draft, $product);
                        }
                    }
                    return ["Maaf, pilihan tidak cocok. Ketik nomor yang benar dari daftar atau ketik nama produk lain."];
                }
                return $this->stateChooseProduct($customer, $draft, $state, $message);
            case 4:
                return $this->stateCollectQuantity($customer, $draft, $state, $message);
            case 5:
                return $this->stateCollectPayment($customer, $draft, $state, $message);
            case 6:
                return $this->stateConfirmOrder($customer, $draft, $state, $message);
            case 7:
                return $this->stateOrderCreated($customer, $draft, $state);
            case 8:
                return $this->stateTracking($customer, $draft, $state, $message);
            case 9:
                return $this->stateComplaint($customer, $draft, $state, $message);
            case 10:
                return $this->stateCollectCardMessage($customer, $draft, $state, $message);
            default:
                return ["Maaf, saya bingung. Ketik *bantuan* ya."];
        }
    }

    // ===================== STATE 1: GREETING =====================
    private function stateGreeting(Customer $customer, MasterState $state): array
    {
        $name = $customer->name ?? 'Kak';
        $greeting = "Halo {$name}! Selamat datang di Buket Cute. 🌸\n";
        
        // Tentukan state berikutnya berdasarkan data yang ada
        $nextState = $this->getNextStateBasedOnData($customer, $this->draftService->getCustomerActiveDraft($customer));
        
        if ($nextState == 3) {
            $greeting .= "Mau buket seperti apa hari ini?";
            $customer->update(['current_state_id' => 3]);
        } elseif ($nextState == 2) {
            $greeting .= "Boleh tau nama dan alamat lengkapnya dulu ya?";
            $customer->update(['current_state_id' => 2]);
        } else {
            // Jika data sudah lengkap, langsung ke konfirmasi atau state sesuai
            $customer->update(['current_state_id' => $nextState]);
            return $this->executeState($customer, $this->draftService->getCustomerActiveDraft($customer), MasterState::find($nextState), '');
        }
        
        return [$greeting];
    }

    // ===================== STATE 2: NAMA & ALAMAT =====================
   private function stateCollectNameAddress(Customer $customer, ?OrderDraft $draft, MasterState $state, string $message): array
{
    // 1. Ekstrak nama & alamat
    $extracted = $this->extractor->extractParameters($message);
    if (!empty($extracted['customer_name'])) $customer->update(['name' => $extracted['customer_name']]);
    if (!empty($extracted['address'])) $customer->update(['address' => $extracted['address']]);

    // 2. Jika salah satu masih kosong, minta lagi
    if (empty(trim($customer->name))) return ["Nama lengkapnya siapa ya, Kak?"];
    if (empty(trim($customer->address))) return ["Alamat pengiriman ke mana ya?"];

    // 3. Keduanya sudah lengkap. Tentukan langkah berikutnya berdasarkan data
    $nextState = $this->getNextStateBasedOnData($customer, $draft);
    $customer->update(['current_state_id' => $nextState]);

    if ($nextState == 3) {
        return ["Terima kasih, {$customer->name}! Sekarang mau buket seperti apa? Bisa sebutkan nama atau ketik *katalog*."];
    } elseif ($nextState == 4) {
        return ["Baik, sekarang mau pesan berapa banyak *{$draft->data['product_name']}*?"];
    } elseif ($nextState == 5) {
        return ["Pilih metode pembayaran:\n1️⃣ COD\n2️⃣ Transfer\n3️⃣ QRIS"];
    } elseif ($nextState == 6) {
        return $this->stateConfirmOrder($customer, $draft, MasterState::find(6), '');
    }

    return ["Data lengkap. Mari lanjutkan pesanan ya."];
}

    // ===================== STATE 3: PILIH PRODUK =====================
 private function stateChooseProduct(Customer $customer, ?OrderDraft $draft, MasterState $state, string $message): array
{
    // Deteksi permintaan katalog (tampilkan semua)
    if (stripos($message, 'katalog') !== false) {
        $catalog = $this->fuzzy->generateCatalogResponse();
        return [$catalog];
    }

    // Bersihkan input: spasi ganda → tunggal, trim
    $keyword = preg_replace('/\s+/', ' ', trim($message));

    // 1. Cari kecocokan eksak (LIKE %keyword%)
    $exactProducts = Product::where('is_active', true)
        ->where(function ($q) use ($keyword) {
            $q->where('name', 'like', '%' . $keyword . '%')
              ->orWhere('slug', 'like', '%' . $keyword . '%');
        })
        ->orderByRaw('LENGTH(name) ASC')
        ->limit(5)
        ->get();

    // Jika hanya satu yang cocok, langsung pilih
    if ($exactProducts->count() === 1) {
        $product = $exactProducts->first();
        return $this->confirmProductSelection($customer, $draft, $product);
    }

    // Jika beberapa cocok (2 atau lebih), tampilkan rekomendasi
    if ($exactProducts->count() > 1) {
        $list = "Ada beberapa produk yang sesuai:\n";
        foreach ($exactProducts as $i => $p) {
            $list .= ($i + 1) . ". {$p->name} – Rp " . number_format($p->price, 0, ',', '.') . "\n";
        }
        $list .= "Ketik nama produk yang kamu maksud, atau pilih nomor (1-{$exactProducts->count()}).";

        // Simpan daftar rekomendasi di session/draft untuk diproses nanti
        if ($draft) {
            $data = $draft->data ?? [];
            $data['suggestions'] = $exactProducts->pluck('id')->toArray();
            $draft->update(['data' => $data]);
        }

        return [$list];
    }

    // 2. Jika tidak ada kecocokan eksak, coba pecah kata kunci (lebih longgar)
    $words = explode(' ', $keyword);
    $looseProducts = Product::where('is_active', true)
        ->where(function ($q) use ($words) {
            foreach ($words as $word) {
                if (strlen($word) > 2) {
                    $q->orWhere('name', 'like', '%' . $word . '%');
                }
            }
        })
        ->orderByRaw('LENGTH(name) ASC')
        ->limit(5)
        ->get();

    // Jika tidak ada hasil sama sekali
    if ($looseProducts->isEmpty()) {
        return ["Maaf, tidak ada produk yang cocok dengan kata kunci \"$keyword\". Coba ketik *katalog* untuk lihat semua produk."];
    }

    // Tampilkan saran dari pencarian longgar
    $list = "Mungkin produk yang kamu maksud salah satu dari ini:\n";
    foreach ($looseProducts as $i => $p) {
        $list .= ($i + 1) . ". {$p->name} – Rp " . number_format($p->price, 0, ',', '.') . "\n";
    }
    $list .= "Ketik nama atau nomor pilihanmu.";

    // Simpan saran di draft
    if ($draft) {
        $data = $draft->data ?? [];
        $data['suggestions'] = $looseProducts->pluck('id')->toArray();
        $draft->update(['data' => $data]);
    }

    return [$list];
}

    private function confirmProductSelection(Customer $customer, ?OrderDraft $draft, Product $product): array
{
    $data = $draft?->data ?? [];
    $data['product_name'] = $product->name;
    $data['product_id'] = $product->id;
    $data['price'] = $product->price;
    if ($draft) {
        $draft->update(['data' => $data, 'step' => 'choose_product']);
    } else {
        $draft = $this->draftService->getOrCreateDraft($customer);
        $draft->update(['data' => $data, 'step' => 'choose_product']);
    }

    // Set state ke 10 untuk kartu ucapan
    $customer->update(['current_state_id' => 10]);
    return ["Produk *{$product->name}* (Rp " . number_format($product->price, 0, ',', '.') . ") dipilih. Mau tulis kartu ucapan apa? (Opsional, ketik *skip* jika tidak perlu)"];
}

    // ===================== STATE 4: JUMLAH =====================
    private function stateCollectQuantity(Customer $customer, ?OrderDraft $draft, MasterState $state, string $message): array
    {
        $qty = intval(preg_replace('/[^0-9]/', '', $message));
        if ($qty < 1) {
            return ["Jumlah harus angka minimal 1 ya, Kak."];
        }

        if ($draft) {
            $data = $draft->data ?? [];
            $data['quantity'] = $qty;
            $draft->update(['data' => $data, 'step' => 'collect_quantity']);
        }

        // Tentukan state berikutnya
        $nextState = $this->getNextStateBasedOnData($customer, $draft);
        $customer->update(['current_state_id' => $nextState]);

        if ($nextState == 5) {
            return ["Baik, jumlah {$qty}. Pilih metode pembayaran:\n1️⃣ *COD*\n2️⃣ *Transfer*\n3️⃣ *QRIS*"];
        } elseif ($nextState == 6) {
            return $this->stateConfirmOrder($customer, $draft, MasterState::find(6), '');
        }

        return ["Jumlah disimpan. Mari lanjutkan ya."];
    }

    // ===================== STATE 5: PEMBAYARAN =====================
    private function stateCollectPayment(Customer $customer, ?OrderDraft $draft, MasterState $state, string $message): array
    {
        $lower = strtolower(trim($message));
        $methods = ['cod', 'transfer', 'qris'];
        $method = null;

        if (in_array($lower, $methods)) {
            $method = $lower;
        } elseif ($lower === '1' || $lower === 'cod') $method = 'cod';
        elseif ($lower === '2' || $lower === 'transfer') $method = 'transfer';
        elseif ($lower === '3' || $lower === 'qris') $method = 'qris';

        if (!$method) {
            return ["Maaf, pilih salah satu: *COD*, *Transfer*, atau *QRIS*."];
        }

        if ($draft) {
            $data = $draft->data ?? [];
            $data['payment_method'] = $method;
            $draft->update(['data' => $data, 'step' => 'collect_payment']);
        }

        // Semua data lengkap, ke konfirmasi
        $customer->update(['current_state_id' => 6]);
        return $this->stateConfirmOrder($customer, $draft, MasterState::find(6), '');
    }

    // ===================== STATE 10: KARTU UCAPAN =====================
    private function stateCollectCardMessage(Customer $customer, ?OrderDraft $draft, MasterState $state, string $message): array
    {
        $lower = strtolower(trim($message));

        if (empty($message)) {
            return ["Mau tulis kartu ucapan apa? (Opsional, ketik *skip* jika tidak perlu)"];
        }

        $cardMessage = '';
        if ($lower !== 'skip' && !empty(trim($message))) {
            $cardMessage = trim($message);
        }

        if ($draft) {
            $data = $draft->data ?? [];
            $data['card_message'] = $cardMessage;
            $draft->update(['data' => $data, 'step' => 'collect_card_message']);
        }

        // Lanjut ke quantity
        $customer->update(['current_state_id' => 4]);
        return ["Baik, kartu ucapan disimpan. Sekarang mau pesan berapa banyak *{$draft->data['product_name']}*?"];
    }

    // ===================== STATE 6: KONFIRMASI =====================
    private function stateConfirmOrder(Customer $customer, ?OrderDraft $draft, MasterState $state, string $message): array
    {
        // Jika baru pertama masuk state ini, tampilkan ringkasan
        if (empty($message) || $customer->current_state_id != 6) {
            if (!$draft) {
                return ["Pesanan tidak ditemukan. Mulai dari awal ya."];
            }
            return $this->buildConfirmationSummary($customer, $draft);
        }

        // Proses jawaban ya/tidak
        $lower = strtolower(trim($message));
        if (in_array($lower, ['ya', 'iya', 'ok', 'oke', 'setuju', 'lanjut', 'confirm'])) {
            return $this->completeDraftToOrder($customer, $draft);
        } elseif (in_array($lower, ['tidak', 'nggak', 'batal', 'ubah', 'ganti'])) {
            $customer->update(['current_state_id' => 3]);
            return ["Baik, mari pilih produk lagi. Mau buket apa?"];
        }
        return ["Ketik *ya* untuk lanjut atau *tidak* untuk ubah pesanan."];
    }

    // ===================== STATE 7: ORDER CREATED =====================
    private function stateOrderCreated(Customer $customer, ?OrderDraft $draft, MasterState $state): array
    {
        $orderId = $draft->data['order_id'] ?? 'terbaru';
        $method = $draft->data['payment_method'] ?? 'cod';
        
        $reply = "Pesanan #{$orderId} berhasil dibuat! 🎉\n";
        if ($method === 'cod') {
            $reply .= "Pembayaran *COD* saat barang diterima ya.";
        } elseif ($method === 'transfer') {
            $reply .= "Silakan transfer ke rekening BCA 123456789 a.n. Buket Cute. Kirim bukti ke admin ya.";
        } elseif ($method === 'qris') {
            $reply .= "Ini QRIS untuk pembayaran:\n[Gambar QRIS akan dikirim admin]";
        }
        $reply .= "\n\nKetik *cek status* untuk lacak pesanan.";
        
        return [$reply];
    }

    // ===================== STATE 8: TRACKING =====================
    private function stateTracking(Customer $customer, ?OrderDraft $draft, MasterState $state, string $message): array
    {
        $latest = $customer->orders()->latest()->first();
        if (!$latest) {
            return ["Belum ada pesanan. Ketik *pesan* untuk mulai."];
        }
        $statusText = ['pending' => 'Menunggu diproses', 'processed' => 'Sedang dikerjakan', 'completed' => 'Selesai / Dikirim', 'cancelled' => 'Dibatalkan'];
        $text = "📦 Pesanan #{$latest->id}\nStatus: {$statusText[$latest->status]}\nMetode: ".strtoupper($latest->payment_method);
        return [$text];
    }

    // ===================== STATE 9: COMPLAINT =====================
    private function stateComplaint(Customer $customer, ?OrderDraft $draft, MasterState $state, string $message): array
    {
        return ["Keluhan kamu sudah dicatat. Admin kami akan segera menghubungi. 🙏"];
    }

    // ===================== HELPER =====================

    private function handleResume(Customer $customer, OrderDraft $draft): array
    {
        $stateId = $customer->current_state_id ?? 1;
        $customer->update(['current_state_id' => $stateId]);
        $state = MasterState::find($stateId);
        $prompt = $state?->prompt_text ?: 'Saya bantu lanjutkan pesananmu.';
        return ["Halo lagi! Lanjutkan pesanan sebelumnya ya. " . $prompt];
    }

private function handleGlobalIntent(Customer $customer, MasterState $currentState, string $message): ?array
{
    $msg = strtolower(trim($message));

    // 1. Deep link dari website (fleksibel)
    //    Mencocokkan pola "saya ingin pesan", "pesan:", "produk:", dll.
    if (preg_match('/saya\s+ingin\s+pesan|pesan\s*:|produk\s*:/i', $msg)) {
        return $this->handleWebOrder($customer, $message);
    }

    // 2. Pesan baru
    if (preg_match('/pesan\s*(baru|lain)/i', $msg) || str_contains($msg, 'order baru')) {
        $customer->update(['current_state_id' => 3]);
        return ["Baik, kita mulai pesanan baru ya. Mau buket apa?"];
    }

    // 3. Cek status
    if (preg_match('/cek\s*status|lacak|tracking|status\s*pesanan|dimana\s*pesananku/i', $msg)) {
        $customer->update(['current_state_id' => 8]);
        $latest = $customer->orders()->latest()->first();
        if ($latest) {
            $statusText = [
                'pending' => 'Menunggu diproses',
                'processed' => 'Sedang dikerjakan',
                'completed' => 'Selesai / Dikirim',
                'cancelled' => 'Dibatalkan'
            ][$latest->status] ?? $latest->status;
            return ["📦 Pesanan #{$latest->id} – Status: {$statusText}"];
        }
        return ["Belum ada pesanan. Ketik *pesan* untuk mulai."];
    }

    // 4. Komplain
    if (preg_match('/komplain|keluhan|kecewa|tidak\s*sesuai|rusak/i', $msg)) {
        $customer->update(['current_state_id' => 9]);
        return ["Keluhan kamu sudah dicatat. Admin kami akan segera menghubungi. 🙏"];
    }

    return null; // bukan global intent
}

    private function handleWebOrder(Customer $customer, string $message): array
    {
        // Ekstrak produk: setelah "pesan:" sampai "Jumlah:"
        preg_match('/pesan:\s*"([^"]+)"/i', $message, $productMatch);
        $productName = $productMatch[1] ?? null;

        // Ekstrak jumlah: setelah "Jumlah: "
        preg_match('/Jumlah:\s*(\d+)/i', $message, $qtyMatch);
        $quantity = intval($qtyMatch[1] ?? 1);

        // Ekstrak total jika ada, tapi tidak wajib
        preg_match('/Total:\s*Rp\s*([\d,]+)/i', $message, $totalMatch);
        $total = $totalMatch[1] ? str_replace(',', '', $totalMatch[1]) : null;

        if ($productName) {
            $product = $this->findProduct($productName);
            $draft = $this->draftService->getOrCreateDraft($customer);
            $data = $draft->data ?? [];
            $data['product_name'] = $productName;
            $data['product_id'] = $product?->id;
            $data['price'] = $product?->price ?? 0;
            $data['quantity'] = $quantity;
            if ($total) {
                $data['total_price'] = $total; // Jika dari web, pakai total yang dikirim
            } else {
                $data['total_price'] = ($product?->price ?? 0) * $quantity;
            }
            $draft->update(['data' => $data, 'step' => 'choose_product']);
        }

        // Tentukan state berikutnya berdasarkan data yang ada
        $nextState = $this->getNextStateBasedOnData($customer, $draft);
        $customer->update(['current_state_id' => $nextState]);

        if ($nextState == 2) {
            return ["Halo! Saya terima pesananmu dari website. Nama dan alamat lengkap dulu ya?"];
        } elseif ($nextState == 5) {
            return ["Baik, saya langsung proses. Pilih metode pembayaran:\n1️⃣ COD\n2️⃣ Transfer\n3️⃣ QRIS"];
        } elseif ($nextState == 6) {
            return $this->stateConfirmOrder($customer, $draft, MasterState::find(6), '');
        }

        return ["Pesanan diterima. Mari lanjutkan ya."];
    }

    /**
     * Tentukan state berikutnya berdasarkan data yang sudah ada.
     * Prioritas: nama -> alamat -> produk -> quantity -> payment -> konfirmasi
     */
    private function getNextStateBasedOnData(Customer $customer, ?OrderDraft $draft): int
    {
        $data = $draft?->data ?? [];

        // Jika belum ada nama, ke collect name address
        if (empty(trim($customer->name ?? ''))) {
            return 2;
        }

        // Jika belum ada alamat, ke collect name address
        if (empty(trim($customer->address ?? ''))) {
            return 2;
        }

        // Jika belum ada produk, ke choose product
        if (empty($data['product_name'])) {
            return 3;
        }

        // Jika belum ada quantity, ke collect quantity
        if (empty($data['quantity'])) {
            return 4;
        }

        // Jika belum ada payment, ke collect payment
        if (empty($data['payment_method'])) {
            return 5;
        }

        // Semua lengkap, ke konfirmasi
        return 6;
    }

  private function findProduct(string $name): ?Product
{
    $name = trim($name);
    $products = Product::where('is_active', true)
        ->where(function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%')
              ->orWhere('slug', 'like', '%' . $name . '%')
              ->orWhere('description', 'like', '%' . $name . '%');
        })
        ->get();

    if ($products->isEmpty()) {
        return null;
    }

    // Jika ada beberapa hasil, prioritaskan yang namanya paling mirip
    if ($products->count() > 1) {
        foreach ($products as $product) {
            if (stripos($product->name, $name) !== false) {
                return $product; // langsung kembalikan yang mengandung nama persis
            }
        }
    }

    // Jika tidak ada yang pas, kembalikan produk pertama
    return $products->first();
}

    private function buildConfirmationSummary(Customer $customer, OrderDraft $draft): array
    {
        $data = $draft->data;
        $qty = $data['quantity'] ?? 1;
        $price = $data['price'] ?? 0;
        $total = $price * $qty;
        $payment = strtoupper($data['payment_method'] ?? 'COD');

        $summary = "📋 *KONFIRMASI PESANAN*\n"
                 . "Nama: {$customer->name}\n"
                 . "Alamat: {$customer->address}\n"
                 . "Produk: {$data['product_name']}\n"
                 . "Jumlah: {$qty}\n"
                 . "Kartu Ucapan: " . (!empty($data['card_message']) ? $data['card_message'] : "Tidak ada") . "\n"
                 . "Total: Rp ".number_format($total,0,',','.')."\n"
                 . "Pembayaran: {$payment}\n\n"
                 . "Ketik *ya* untuk lanjut atau *tidak* untuk ubah.";
        return [$summary];
    }

    private function completeDraftToOrder(Customer $customer, OrderDraft $draft): array
    {
        $data = $draft->data;
        $qty = max(1, intval($data['quantity'] ?? 1));
        $price = $data['price'] ?? 0;
        $total = $price * $qty;

        $notes = $data['special_request'] ?? '';
        if (!empty($data['card_message'])) {
            $notes .= ($notes ? "\n" : '') . "Kartu Ucapan: " . $data['card_message'];
        }

        $order = Order::create([
            'customer_id' => $customer->id,
            'total_price' => $total,
            'status' => 'pending',
            'notes' => $notes,
            'payment_method' => $data['payment_method'] ?? 'cod',
            'payment_status' => 'pending',
            'payment_due_date' => now()->addDay(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $data['product_id'] ?? null,
            'custom_description' => empty($data['product_id']) ? $data['product_name'] : null,
            'quantity' => $qty,
            'price' => $price,
            'subtotal' => $total,
        ]);

        $draft->update(['step' => 'completed', 'data' => array_merge($data, ['order_id' => $order->id])]);
        $customer->update(['current_state_id' => 7]);

        return $this->stateOrderCreated($customer, $draft, MasterState::find(7));
    }
}