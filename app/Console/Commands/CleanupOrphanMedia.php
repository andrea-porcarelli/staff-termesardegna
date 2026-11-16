<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanMedia extends Command
{
    protected $signature = 'media:cleanup-orphans
        {--dry-run : Mostra solo cosa verrebbe eliminato, senza toccare DB o filesystem}
        {--hours=1 : Soglia di età minima (in ore) per considerare un TempMedia orfano}';

    protected $description = 'Elimina record Media temporanei (mediable_type=TempMedia) rimasti orfani dopo la creazione di un ticket/rapportino, insieme ai file su disco.';

    public function handle(): int
    {
        $hours = max(0, (int) $this->option('hours'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subHours($hours);

        $query = Media::where('mediable_type', 'TempMedia')
            ->where('created_at', '<', $cutoff);

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info("Nessun media orfano più vecchio di {$hours}h.");

            return self::SUCCESS;
        }

        $this->info("Trovati {$total} record TempMedia più vecchi di {$hours}h.");
        if ($dryRun) {
            $this->warn('Dry-run: nessuna modifica verrà applicata.');
        }

        $deletedRecords = 0;
        $deletedFiles = 0;
        $missingFiles = 0;

        $query->orderBy('id')->chunkById(200, function ($batch) use ($dryRun, &$deletedRecords, &$deletedFiles, &$missingFiles) {
            foreach ($batch as $media) {
                $this->line(sprintf(
                    '  #%d  %s  (mediable_id=%s)  %s',
                    $media->id,
                    $media->file_name,
                    (string) $media->mediable_id,
                    $media->file_path,
                ));

                if ($dryRun) {
                    continue;
                }

                if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
                    Storage::disk('public')->delete($media->file_path);
                    $deletedFiles++;
                } else {
                    $missingFiles++;
                }

                $media->delete();
                $deletedRecords++;
            }
        });

        if ($dryRun) {
            $this->warn('Dry-run completato. Rilancia senza --dry-run per applicare.');
        } else {
            $this->info("Eliminati {$deletedRecords} record e {$deletedFiles} file. File già assenti: {$missingFiles}.");
        }

        return self::SUCCESS;
    }
}
