<?php

namespace App\Console\Commands;
use App\Models\Media;
use Illuminate\Console\Command;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MigrateImagesToWebp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:convert-to-webp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ubah semua gambar produk (bukan video) ke WebP 800px';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $manager = new ImageManager(new Driver());

        $media = Media::where('file_type', 'image')
                      ->where('mime_type', '!=', 'image/webp')
                      ->get();

        $this->info('Ditemukan ' . $media->count() . ' gambar yang akan dikonversi.');

        foreach ($media as $item) {
            $oldPath = storage_path('app/public/' . $item->file_path);

            if (!file_exists($oldPath)) {
                $this->warn("File tidak ditemukan: {$item->file_path}");
                continue;
            }

            try {
                // Buat path WebP baru (ganti ekstensi)
                $newPath = preg_replace('/\.[^.]+$/', '.webp', $item->file_path);

                $image = $manager->read($oldPath);
                $image->scale(width: 800);
                $image->toWebp(80)->save(storage_path('app/public/' . $newPath));

                // Update record database
                $item->update([
                    'file_path' => $newPath,
                    'mime_type' => 'image/webp',
                ]);

                // Hapus file lama (opsional, hati-hati!)
                if ($oldPath !== storage_path('app/public/' . $newPath)) {
                    unlink($oldPath);
                }

                $this->line("✓ {$item->file_name} → " . basename($newPath));

            } catch (\Exception $e) {
                $this->error("Gagal konversi {$item->file_path}: " . $e->getMessage());
            }
        }

        $this->info('Selesai!');
    }
}