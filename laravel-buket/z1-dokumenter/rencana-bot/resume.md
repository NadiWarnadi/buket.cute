## Arsitektur Komunikasi (Ringkasan)

[WhatsApp User] <--> [Node.js Baileys Gateway] <--> [Laravel 12 REST API] <--> [Database]


Pseudocode 1: Node.js Baileys Gateway

PROGRAM WhatsAppGateway

  // Inisialisasi koneksi Baileys dengan Multi-Device
  sock = Baileys.makeWASocket(authState)
  sock.ev.on('messages.upsert', Panggil handleIncomingMessage)

  FUNCTION handleIncomingMessage(m)
    msg = m.messages[0]
    IF msg.key.fromMe ATAU msg.message == null THEN RETURN

    from = msg.key.remoteJid          // format: 628xxx@s.whatsapp.net
    body = dapatkanTeksPesan(msg)     // ekstrak teks, tombol, atau list reply

    // Kirim data ke Laravel
    response = HTTP.POST('https://laravel-app/api/webhook/wa', 
                         HEADER: Authorization=Bearer SECRET_KEY,
                         BODY: {
                           'session_id': from,
                           'message': body,
                           'timestamp': now()
                         })

    IF response.status == 200 THEN
      reply = response.data.reply     // array pesan yang akan dikirim
      FOR EACH pesan IN reply
        sock.sendMessage(from, { text: pesan })
      END FOR
    END IF
  END FUNCTION

  FUNCTION dapatkanTeksPesan(msg)
    IF msg.message.conversation THEN RETURN msg.message.conversation
    ELSE IF msg.message.buttonsResponse THEN RETURN msg.message.buttonsResponse.selectedDisplayText
    ELSE IF msg.message.listResponse THEN RETURN msg.message.listResponse.title
    ELSE RETURN "[Media/Tidak Didukung]"
  END FUNCTION
END PROGRAM
Pseudocode 2: Laravel Webhook Handler (Controller)
text
CONTROLLER ChatController

  FUNCTION webhook(Request request)
    // Validasi Secret Key
    IF request.header('Authorization') != 'Bearer ' + env('WA_WEBHOOK_SECRET') THEN
      RETURN response(401, 'Unauthorized')
    END IF

    session_id  = request.input('session_id')
    user_message = request.input('message')

    // Panggil Service Utama
    chatbotService = new ChatbotService()
    response = chatbotService.processMessage(session_id, user_message)

    RETURN response.json({
      'reply': response
    })
  END FUNCTION
Pseudocode 3: ChatbotService - Otak Utama
text
CLASS ChatbotService

  FUNCTION processMessage(session_id, message)
    customer = CariAtauBuatCustomer(session_id)      // Tabel customers berdasarkan chat_session_id
    draft    = DapatkanDraftAktif(customer.id)        // order_draft yang belum completed

    // === 1. RESUME HANDLER: Cek apakah user baru kembali setelah lama ===
    IF customer.last_activity > 30 MENIT YANG LALU THEN
      IF draft != null THEN
        resumeState = draft.last_state_id
        IF resolveDependencies(customer, draft, resumeState) THEN
          // Data lengkap, bisa resume
          customer.current_state_id = resumeState
          simpan(customer)
          RETURN generateResumeMessage(draft) + [dapatkanPromptState(resumeState)]
        ELSE
          // Data tidak lengkap, arahkan ke state yang diperlukan
          redirectState = cariStatePemenuhDependensi(customer, draft, resumeState)
          customer.current_state_id = redirectState
          simpan(customer)
          RETURN ["Sebelumnya kita ada kendala data. " + dapatkanPromptState(redirectState)]
        END IF
      END IF
    END IF

    // === 2. Jika user baru (belum ada current_state_id), mulai dari State 1 ===
    IF customer.current_state_id IS NULL THEN
      customer.current_state_id = 1  // Sapaan
      simpan(customer)
      RETURN [dapatkanPromptState(1)]
    END IF

    currentState = AmbilStateDariMaster(customer.current_state_id) // Tabel master_states

    // === 3. GLOBAL INTENT DETECTION (Fuzzy) ===
    globalIntent = FuzzyProcessor.deteksiIntentGlobal(message)
    IF globalIntent.confidence > 0.8 THEN
      IF globalIntent.name == 'PESAN_BARU' DAN currentState.id != 3 THEN
        // User ingin pesan baru, paksa pindah ke State 3
        customer.current_state_id = 3
        simpan(customer)
        RETURN ["Baik, kita ganti ke pesanan baru ya. " + dapatkanPromptState(3)]
      ELSE IF globalIntent.name == 'CEK_STATUS' DAN currentState.id != 5 DAN draft != null THEN
        customer.current_state_id = 5
        simpan(customer)
        RETURN ["Langsung kita cek statusnya. " + dapatkanPromptState(5)]
      ELSE IF globalIntent.name == 'KOMPLAIN' DAN currentState.id != 6 THEN
        customer.current_state_id = 6
        simpan(customer)
        RETURN ["Saya bantu tampung keluhannya. " + dapatkanPromptState(6)]
      END IF
    END IF

    // === 4. PROSES STATE SAAT INI SESUAI TIPENYA ===
    response = prosesState(customer, draft, currentState, message)
    RETURN response
  END FUNCTION
Pseudocode 4: State Processor (Inti Alur)
text
FUNCTION prosesState(customer, draft, currentState, message)

  SWITCH currentState.type

    CASE 'greeting':
      // State 1: hanya menyapa, langsung pindah
      customer.current_state_id = currentState.next_state_id  // ke State 2
      simpan(customer)
      RETURN [currentState.prompt_text, dapatkanPromptState(customer.current_state_id)]

    CASE 'input':
      // State 2, 4 (input terstruktur)
      // Validasi input sesuai aturan currentState.validation_rules
      IF validasi(message, currentState.validation_rules) THEN
        simpanKeDatabase(customer, draft, currentState.input_key, message)

        // Kustom sub-state untuk State 3 (custom produk)
        IF currentState.id == 3 DAN draft.is_custom == true THEN
          // Masuk sub-state machine (lihat Pseudocode 6)
          RETURN CustomProductMachine.process(customer, draft, message)
        ELSE
          // Lanjut ke state berikutnya
          customer.current_state_id = currentState.next_state_id
          simpan(customer)
          RETURN [dapatkanPromptState(customer.current_state_id)]
        END IF
      ELSE
        RETURN ["Maaf, formatnya kurang tepat. " + currentState.prompt_text]
      END IF

    CASE 'fuzzy_inquiry':
      // State 5, 6 (butuh inferensi)
      fuzzyResult = FuzzyProcessor.evaluate(currentState.fuzzy_context, message, customer, draft)

      // Tentukan state selanjutnya berdasarkan hasil fuzzy
      nextStateId = fuzzyResult.next_state_id
      IF nextStateId != null THEN
        customer.current_state_id = nextStateId
      END IF
      simpan(customer)
      RETURN [fuzzyResult.reply]

    CASE 'decision':
      // Percabangan eksplisit (misal: pilihan ya/tidak)
      IF message == 'ya' THEN
        customer.current_state_id = currentState.next_state_id
      ELSE IF message == 'tidak' THEN
        customer.current_state_id = currentState.fallback_state_id
      END IF
      simpan(customer)
      RETURN [dapatkanPromptState(customer.current_state_id)]

    DEFAULT:
      RETURN ["Saya tidak mengerti. " + currentState.prompt_text]
  END SWITCH
END FUNCTION
Pseudocode 5: Dependency Resolver & Resume Logic
text
FUNCTION resolveDependencies(customer, draft, targetStateId)
  state = AmbilStateDariMaster(targetStateId)
  requiredKeys = state.prerequisite_keys   // array ['customer_name', 'order_id']

  FOR EACH key IN requiredKeys
    IF key == 'customer_name' DAN customer.name == null THEN RETURN false
    IF key == 'address' DAN customer.address == null THEN RETURN false
    IF key == 'product' DAN draft.product_name == null THEN RETURN false
    IF key == 'order_id' DAN draft.id == null THEN RETURN false
    // ... tambahan untuk custom
  END FOR
  RETURN true
END FUNCTION

FUNCTION cariStatePemenuhDependensi(customer, draft, failedStateId)
  // Kembalikan state yang paling awal yang datanya belum terisi
  IF customer.name == null THEN RETURN 2
  IF draft.product_name == null THEN RETURN 3
  // ... dsb
END FUNCTION

FUNCTION generateResumeMessage(draft)
  RETURN "Halo lagi! Sebelumnya kita sedang memproses pesanan " + draft.product_name + ". Lanjutkan ya?"
END FUNCTION
Pseudocode 6: Fuzzy Logic Processor (Contoh Kasus State 5)
text
CLASS FuzzyProcessor

  FUNCTION evaluate(context, user_input, customer, draft)
    SWITCH context.variable
      CASE 'tingkat_keparahan_status':
        // Fuzzifikasi dari input user (kata-kata keluhan)
        urgensi = hitungUrgensi(user_input)   // 0 s.d 1
        lama_tunggu = hitungLamaTunggu(draft) // dalam hari, dari database

        // Inferensi aturan fuzzy (diambil dari tabel fuzzy_rules)
        rules = AmbilAturanFuzzy('status_produk')
        hasil_inferensi = []
        FOR EACH rule IN rules
          derajat = MIN( fuzzyfikasi(urgensi, rule.input1), fuzzyfikasi(lama_tunggu, rule.input2) )
          hasil_inferensi.push({ output: rule.output, degree: derajat })
        END FOR

        // Defuzzifikasi (misal metode Sugeno)
        aksi = defuzzifikasi(hasil_inferensi)

        // Mapping aksi ke respon dan next state
        IF aksi == 'HIBUR_PROMO' THEN
          RETURN { reply: "Wah maaf ya kak ... (promo)", next_state_id: null }
        ELSE IF aksi == 'CEK_KURIR' THEN
          RETURN { reply: "Saya cek ke kurir dulu ya ...", next_state_id: 5 } // tetap di state 5
        END IF
      BREAK

      CASE 'deteksi_intent':
        // Fuzzy matching intent menggunakan keyword
        ... // (serupa)
      BREAK
    END SWITCH
  END FUNCTION
END CLASS
Pseudocode 7: Custom Product Sub-State Machine (di dalam State 3)
text
CLASS CustomProductMachine

  FUNCTION process(customer, draft, message)
    subState = draft.custom_sub_state   // disimpan di order_draft

    SWITCH subState
      CASE 3.0: // Pilih dasar
        draft.base_product_id = cariProdukDasar(message)
        draft.custom_sub_state = 3.1
        simpan(draft)
        RETURN ["Mau tambah topping? (ketik cukup jika sudah)"]

      CASE 3.1: // Tambah topping
        IF message == 'cukup' THEN
          draft.custom_sub_state = 3.2
          simpan(draft)
          RETURN ["Pilih ukuran: Regular / Jumbo?"]
        ELSE
          topping = cariTopping(message)
          IF topping != null THEN
            TambahCustomItem(draft.id, 'topping', topping.name, topping.price)
            RETURN ["Topping " + topping.name + " ditambahkan. Lagi?"]
          ELSE
            RETURN ["Topping tidak tersedia. Coba lagi ya."]
          END IF
        END IF

      CASE 3.2: // Ukuran
        IF message IN ['regular','jumbo'] THEN
          TambahCustomItem(draft.id, 'size', message, hargaUkuran(message))
          draft.custom_sub_state = 3.3
          simpan(draft)
          RETURN ["Ada permintaan khusus? Misal: tidak pedas."]
        ELSE
          RETURN ["Pilih regular atau jumbo aja ya."]
        END IF

      CASE 3.3: // Special request
        draft.special_request = message
        draft.custom_sub_state = 3.4
        simpan(draft)
        // Tampilkan ringkasan
        ringkasan = buildRingkasanCustom(draft)
        RETURN [ringkasan + "\nLanjut ke pembayaran? (ya/tidak)"]

      CASE 3.4: // Konfirmasi
        IF message == 'ya' THEN
          // Selesai custom, pindah ke State 4
          customer.current_state_id = 4
          draft.custom_sub_state = null
          simpan(customer, draft)
          RETURN [dapatkanPromptState(4)]
        ELSE
          // Kembali edit? Untuk sederhana, ulangi dari 3.0
          draft.custom_sub_state = 3.0
          simpan(draft)
          RETURN ["Oke, kita ulang racikannya. Mau pilih dasar apa?"]
        END IF
    END SWITCH
  END FUNCTION
END CLASS
Skema Database (Konseptual)
Tabel customers

id (PK)

chat_session_id (unik, dari WA number)

name

address

last_activity_at

current_state_id (FK ke master_states.id)

Tabel order_draft

id (PK)

customer_id (FK)

product_name (string, bisa null saat custom)

greeting_card (text)

payment_method (enum: COD/TRANSFER)

last_state_id (FK)

is_custom (boolean)

custom_sub_state (decimal)

special_request (text)

Tabel custom_order_items

id

draft_id (FK)

option_group (topping, size, dll)

option_value

price_adjustment

Tabel master_states

id

name

type (greeting/input/fuzzy_inquiry/decision)

prompt_text

input_key

validation_rules (json)

fuzzy_context (json)

next_state_id

prerequisite_keys (json)

resume_message

Tabel fuzzy_rules

id

context (status_produk, custom_recommend)

input1, operator1, value1

input2, operator2, value2

output

