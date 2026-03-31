#!/usr/bin/env node
/**
 * Debug Script untuk test koneksi Node.js ↔ Laravel
 * Run: node debug-connection.js
 */

require('dotenv').config();
const axios = require('axios');

const PORT = process.env.PORT || 3000;
const API_KEY = process.env.API_KEY;
const LARAVEL_WEBHOOK_URL = process.env.LARAVEL_WEBHOOK_URL;
const SESSION_NAME = process.env.SESSION_NAME;

console.log("\n╔════════════════════════════════════════════════════════════╗");
console.log("║        🔍 DEBUG CONNECTION: Node.js ↔ Laravel           ║");
console.log("╚════════════════════════════════════════════════════════════╝\n");

// 1. Check Environment
console.log("📋 Environment Check:");
console.log(`   PORT: ${PORT}`);
console.log(`   API_KEY: ${API_KEY ? API_KEY.substring(0, 10) + "..." : "NOT SET"}`);
console.log(`   LARAVEL_WEBHOOK_URL: ${LARAVEL_WEBHOOK_URL}`);
console.log(`   SESSION_NAME: ${SESSION_NAME}\n`);

// 2. Check if Laravel is running locally
console.log("🔗 Testing Laravel /health endpoint (no auth required):");
axios.get('http://127.0.0.1:8000/health', { timeout: 3000 })
    .then(() => {
        console.log("   ✅ Laravel is responding on port 8000\n");
        testWebhookEndpoint();
    })
    .catch((err) => {
        console.log("   ❌ Error: " + err.message);
        console.log("   ❌ Make sure Laravel is running on port 8000\n");
    });

// 3. Test Webhook Endpoint (with auth)
async function testWebhookEndpoint() {
    console.log("🔐 Testing Laravel webhook endpoint with authentication:");
    try {
        const testPayload = {
            sender_number: '6283824665074',
            message_type: 'text',
            body: 'Test message dari Node.js debug script',
            timestamp: Math.floor(Date.now() / 1000),
            message_id: 'test_' + Date.now(),
            type: 'text'
        };

        const response = await axios.post(LARAVEL_WEBHOOK_URL, testPayload, {
            headers: {
                'x-api-key': API_KEY,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            timeout: 5000
        });

        console.log(`   Status: ${response.status}`);
        console.log(`   Response: ${JSON.stringify(response.data, null, 2)}`);
        console.log("   ✅ Webhook endpoint working!\n");

    } catch (error) {
        if (error.response) {
            if (error.response.status === 401) {
                console.log("   ❌ Error: 401 Unauthorized");
                console.log("   Check: API_KEY di Node.js .env");
                console.log("   Check: WHATSAPP_WEBHOOK_KEY di Laravel .env");
            } else {
                console.log(`   ❌ Error: ${error.response.status} ${error.response.statusText}`);
                console.log(`   Response: ${JSON.stringify(error.response.data)}`);
            }
        } else if (error.request) {
            console.log("   ❌ No response from Laravel");
            console.log("   Make sure Laravel is running and webhook URL is correct");
        } else {
            console.log("   ❌ Error: " + error.message);
        }
    }
}

console.log("\n╔════════════════════════════════════════════════════════════╗");
console.log("║                    Debug Complete                         ║");
console.log("╚════════════════════════════════════════════════════════════╝\n");
