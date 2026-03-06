const extractPhoneNumber = (jid) => {
    if (!jid) return null;
    // Menghilangkan @s.whatsapp.net atau @lid dan mengambil nomor murni
    return jid.split('@')[0].split(':')[0].replace(/\D/g, '');
};

const parseIncomingMessage = async (sock, msg) => {
    const key = msg.key;
    const jid = key.remoteJid;
    const isGroup = jid.endsWith('@g.us');
    
    // 1. Mencari nomor asli (PN)
    let phoneNumber = msg.senderPn; 

    if (!phoneNumber && !isGroup) {
        if (jid.endsWith('@s.whatsapp.net')) {
            phoneNumber = extractPhoneNumber(jid);
        } else if (jid.endsWith('@lid')) {
            // Mencari mapping LID ke PN di memori Baileys
            const mapped = await sock.newsletterStore?.['lid-mapping']?.get(jid) 
                           || await sock.signalRepository?.lidMapping?.getPNForLID(jid);
            phoneNumber = mapped ? extractPhoneNumber(mapped) : extractPhoneNumber(jid);
        }
    } else if (isGroup) {
        phoneNumber = extractPhoneNumber(key.participant || msg.participant || jid);
    }

    if (!phoneNumber) phoneNumber = extractPhoneNumber(jid);

    // 2. Ambil Konten Pesan (agar masuk ke 'body' atau 'content' di Laravel)
    const messageContent = msg.message?.conversation 
                           || msg.message?.extendedTextMessage?.text 
                           || msg.message?.imageMessage?.caption 
                           || msg.message?.videoMessage?.caption 
                           || "";

    // 3. Tentukan Tipe (Agar lolos validasi 'type' => 'required' di Laravel)
    const getMessageType = (m) => {
        if (!m) return 'unknown';
        const keys = Object.keys(m);
        const type = keys.find(k => k.endsWith('Message') || k === 'conversation');
        return type ? type.replace('Message', '').toLowerCase() : 'text';
    };

    // Payload yang dikirim ke Laravel (disesuaikan dengan validasi Laravel kalian)
    return {
        type: getMessageType(msg.message), // WAJIB ada untuk Laravel
        from: phoneNumber,                 // Nomor asli
        sender_number: phoneNumber,        // Nomor asli (backup field)
        body: messageContent,              // Isi pesan
        content: messageContent,           // Isi pesan (backup field)
        isGroup: isGroup,
        timestamp: msg.messageTimestamp,
        message_id: key.id,
        pushname: msg.pushName || 'User'
    };
};

module.exports = { extractPhoneNumber, parseIncomingMessage };