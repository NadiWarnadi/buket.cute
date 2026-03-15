<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $baseUrl;

    private string $apiKey;

    private string $timeout = '30';

    public function __construct()
    {
        $this->baseUrl = rtrim(env('WHATSAPP_SERVICE_URL', 'http://localhost:3000'), '/');
        $this->apiKey = env('WHATSAPP_API_KEY', '');

        if (empty($this->apiKey)) {
            throw new Exception('WHATSAPP_API_KEY is not configured in .env');
        }
    }

    /**
     * Cek status koneksi WhatsApp
     */
    public function getStatus(): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($this->timeout)->get("{$this->baseUrl}/api/status");

            if ($response->successful()) {
                Log::channel('whatsapp')->info('WhatsApp service status check successful', $response->json());

                return [
                    'success' => true,
                    'status' => $response->json('status'),
                    'service' => $response->json('service'),
                ];
            }

            Log::channel('whatsapp')->warning('WhatsApp service returned error', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get status',
                'status_code' => $response->status(),
            ];
        } catch (Exception $e) {
            Log::channel('whatsapp')->error('Error checking WhatsApp service', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim pesan teks ke WhatsApp
     */
    public function sendText(string $phoneNumber, string $message): array
    {
        try {
            // Validasi nomor telepon
            if (empty($phoneNumber)) {
                throw new Exception('Phone number is required');
            }

            // Format nomor jika belum punya @ symbol
            $to = $this->formatPhoneNumber($phoneNumber);

            $payload = [
                'to' => $to,
                'text' => $message,
            ];

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($this->timeout)->post("{$this->baseUrl}/api/send-text", $payload);

            if ($response->successful()) {
                Log::channel('whatsapp')->info('Text message sent successfully', [
                    'to' => $phoneNumber,
                    'message_id' => $response->json('message_id'),
                ]);

                return [
                    'success' => true,
                    'message_id' => $response->json('message_id'),
                    'timestamp' => now(),
                ];
            }

            $errorMsg = $response->json('error') ?? 'Unknown error';
            Log::channel('whatsapp')->warning('Failed to send text message', [
                'to' => $phoneNumber,
                'error' => $errorMsg,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        } catch (Exception $e) {
            Log::channel('whatsapp')->error('Exception while sending text message', [
                'to' => $phoneNumber,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim media ke WhatsApp
     */
    public function sendMedia(string $phoneNumber, string $filePath, $caption = null, $mediaType = null): array
    {
        try {
            if (empty($phoneNumber)) {
                throw new Exception('Phone number is required');
            }

            if (empty($filePath) || ! file_exists($filePath)) {
                throw new Exception('File not found: '.$filePath);
            }

            $to = $this->formatPhoneNumber($phoneNumber);

            // Tentukan media type jika tidak disediakan
            if (! $mediaType) {
                $mimeType = mime_content_type($filePath);
                $mediaType = $this->getMimeTypeCategory($mimeType);
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->timeout($this->timeout)->attach(
                'file',
                fopen($filePath, 'r'),
                basename($filePath)
            )->post("{$this->baseUrl}/api/send-media", [
                'to' => $to,
                'caption' => $caption,
                'type' => $mediaType,
            ]);

            if ($response->successful()) {
                Log::channel('whatsapp')->info('Media sent successfully', [
                    'to' => $phoneNumber,
                    'type' => $mediaType,
                    'message_id' => $response->json('message_id'),
                ]);

                return [
                    'success' => true,
                    'message_id' => $response->json('message_id'),
                    'timestamp' => now(),
                ];
            }

            $errorMsg = $response->json('error') ?? 'Unknown error';
            Log::channel('whatsapp')->warning('Failed to send media', [
                'to' => $phoneNumber,
                'type' => $mediaType,
                'error' => $errorMsg,
            ]);

            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        } catch (Exception $e) {
            Log::channel('whatsapp')->error('Exception while sending media', [
                'to' => $phoneNumber,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format nomor telepon ke format WhatsApp
     * Format input: 62xxxxxxxx atau +62xxxxxxxx
     * Format output: 62xxxxxxxx@s.whatsapp.net
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Hapus semua karakter non-numeric kecuali leading +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Jika dimulai dengan +, hapus
        if (substr($phone, 0, 1) === '+') {
            $phone = substr($phone, 1);
        }

        // Jika dimulai dengan 0, ganti dengan 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62'.substr($phone, 1);
        }

        // Pastikan dimulai dengan country code 62
        if (! substr($phone, 0, 2) === '62') {
            if (! substr($phone, 0, 1) === '6') {
                $phone = '62'.$phone;
            }
        }

        return $phone.'@s.whatsapp.net';
    }

    /**
     * Tentukan kategori media berdasarkan MIME type
     */
    private function getMimeTypeCategory(string $mimeType): string
    {
        if (strpos($mimeType, 'image') !== false) {
            return 'image';
        } elseif (strpos($mimeType, 'video') !== false) {
            return 'video';
        } elseif (strpos($mimeType, 'audio') !== false) {
            return 'audio';
        }

        return 'document';
    }
}
