// Test script untuk call send-media endpoint
const axios = require('axios');
const FormData = require('form-data');
const fs = require('fs');

async function testSendMediaEndpoint() {
    try {
        const form = new FormData();
        form.append('file', fs.createReadStream('./temp/1776435749628-JmsmJcElyCAhPwBKKdRPtNeU1aF3udfjvg5YX843.png'), 'test.png');
        form.append('to', '62881023926516');
        form.append('type', 'image');
        form.append('caption', 'Test image from script');

        const response = await axios.post('http://localhost:3000/api/send-media', form, {
            headers: {
                ...form.getHeaders(),
                'x-api-key': 'your-super-secret-api-key-change-me-in-production', // dari .env
            },
            timeout: 60000,
        });

        console.log('Response:', response.data);
    } catch (error) {
        console.error('Error:', error.response ? error.response.data : error.message);
    }
}

testSendMediaEndpoint();