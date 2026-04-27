📝 Catatan Penting untuk Tim 
Field	Keterangan
type	Selalu ada. Nilainya bisa: text, image, video, audio, document, sticker, extendedtext, dll. (direkomendasikan untuk normalisasi di Laravel menjadi image, video, document, text).
from & sender_number	Nomor telepon pengirim dalam format internasional tanpa + (contoh: 6281234567890). Kedua field ini memiliki nilai yang sama untuk kompatibilitas.
body & content	Isi pesan. Bisa string kosong untuk media tanpa caption.
isGroup	Boolean, true jika pesan dari grup.
timestamp	Unix timestamp (detik).
message_id	ID unik pesan dari WhatsApp. Berguna untuk mencegah duplikasi pemrosesan.
pushname	Nama kontak pengirim seperti yang tersimpan di WhatsApp mereka. Bisa berubah-ubah.
raw_message	Objek lengkap dari Baileys. Sangat berguna untuk debugging atau jika suatu saat perlu mengekstrak data tambahan (misalnya URL media untuk diunduh).
