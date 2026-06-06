<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ConvertProductImagesToWebp extends Command
{
    // Kita tambahkan parameter opsi baru --resize-only agar bener-bener jelas tujuannya
    protected $signature = 'images:compress-webp
                            {--dry-run : Hanya simulasi, tidak menyimpan perubahan}';
    
    protected $description = 'Kompres ulang gambar WebP yang ukurannya masih 800px menjadi 600px untuk optimasi halaman Home';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Mode DRY RUN: Tidak ada perubahan fisik yang akan disimpan.');
        }

        // SEKARANG: Tarik data yang mime_type-nya SUDAH image/webp
        $mediaItems = Media::where('model_type', 'App\Models\Product')
            ->where('mime_type', 'image/webp')
            ->get();

        if ($mediaItems->isEmpty()) {
            $this->info('Tidak ada gambar produk berformat WebP yang ditemukan.');
            return 0;
        }

        $this->info("Ditemukan {$mediaItems->count()} gambar WebP yang akan diperiksa & dikompres ulang ke 600px.");

        $processed = 0;
        $failed = 0;
        $skipped = 0;

        $manager = new ImageManager(new Driver());
        $disk = Storage::disk('public');

        foreach ($mediaItems as $media) {
            $filePath = $media->file_path;
            $fullPath = $disk->path($filePath);

            if (!file_exists($fullPath)) {
                $this->error("File fisik tidak ditemukan di storage: {$filePath}");
                $failed++;
                continue;
            }

            if (!$dryRun) {
                try {
                    // Baca gambar WebP yang sekarang (800px)
                    $image = $manager->read($fullPath);
                    
                    // Cek lebarnya, kalau sudah 600px atau lebih kecil, skip aja biar gak kerja dua kali
                    if ($image->width() <= 600) {
                        $skipped++;
                        continue;
                    }

                    $this->line("Mengompres ulang: {$filePath} (Lebar asli: {$image->width()}px)");

                    // Resize ke ukuran target kita: 600px
                    $image->scale(width: 600);
                    
                    // Timpa file lama dengan versi yang lebih ringan (Kualitas 80%)
                    $image->toWebp(80)->save($fullPath);
                    
                    // Hitung ulang ukuran file barunya setelah dikompres
                    clearstatcache(); // Bersihkan cache PHP agar filesize() akurat
                    $newSize = filesize($fullPath);
                    $oldSize = $media->size ?? 0;
                    
                    $this->info("✓ Berhasil diperkecil ke 600px: " . round($oldSize/1024) . "KB -> " . round($newSize/1024) . "KB");
                    
                    // Update ukuran (size) terbarunya di database
                    $media->size = $newSize;
                    $media->save();
                    
                    $processed++;
                    
                } catch (\Exception $e) {
                    $this->error("Gagal memproses {$filePath}: " . $e->getMessage());
                    $failed++;
                }
            } else {
                $processed++;
            }
        }

        $this->newLine();
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Berhasil Dikompres ke 600px', $processed],
                ['Dilewati (Sudah <= 600px)', $skipped],
                ['Gagal', $failed],
            ]
        );

        return 0;
    }
}