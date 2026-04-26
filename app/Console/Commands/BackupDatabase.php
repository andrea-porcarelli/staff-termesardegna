<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup
        {--keep=10 : Giorni di retention per i backup precedenti}';

    protected $description = 'Crea un dump compresso del database MySQL e ripulisce i dump più vecchi.';

    public function handle(): int
    {
        $connection = config('database.default');
        if ($connection !== 'mysql') {
            $this->error("Connessione DB non supportata: {$connection}. Atteso 'mysql'.");

            return self::FAILURE;
        }

        $cfg = config('database.connections.mysql');
        $dir = storage_path('private/backups');
        File::ensureDirectoryExists($dir, 0755, true);

        $timestamp = CarbonImmutable::now()->format('Y-m-d_His');
        $file = $dir.'/'.$cfg['database'].'_'.$timestamp.'.sql.gz';

        $this->info("Backup → {$file}");

        $command = [
            'mysqldump',
            '--host='.$cfg['host'],
            '--port='.$cfg['port'],
            '--user='.$cfg['username'],
            '--password='.$cfg['password'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            '--default-character-set=utf8mb4',
            $cfg['database'],
        ];

        // mysqldump → gzip → file. Niente shell: usiamo pipe via Process.
        $dump = new Process($command);
        $dump->setTimeout(1800); // 30 minuti
        $dump->start();

        $gz = new Process(['gzip', '-c']);
        $gz->setTimeout(1800);
        $gz->setInput($dump);
        $gz->start();

        $handle = fopen($file, 'wb');
        if ($handle === false) {
            $this->error("Impossibile aprire {$file} in scrittura.");

            return self::FAILURE;
        }

        foreach ($gz as $chunk) {
            fwrite($handle, $chunk);
        }
        fclose($handle);

        if (! $dump->isSuccessful()) {
            @unlink($file);
            $this->error('mysqldump fallito: '.trim($dump->getErrorOutput()));

            return self::FAILURE;
        }
        if (! $gz->isSuccessful()) {
            @unlink($file);
            $this->error('gzip fallito: '.trim($gz->getErrorOutput()));

            return self::FAILURE;
        }

        $size = filesize($file) ?: 0;
        $this->info('Backup completato ('.number_format($size / 1024 / 1024, 2).' MB)');

        $this->prune($dir, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    /**
     * Cancella i dump precedenti più vecchi di $days giorni.
     */
    private function prune(string $dir, int $days): void
    {
        if ($days <= 0) {
            return;
        }

        $cutoff = CarbonImmutable::now()->subDays($days)->timestamp;
        $deleted = 0;

        foreach (glob($dir.'/*.sql.gz') ?: [] as $path) {
            $mtime = filemtime($path);
            if ($mtime !== false && $mtime < $cutoff) {
                if (@unlink($path)) {
                    $deleted++;
                    $this->line('  – rimosso '.basename($path));
                }
            }
        }

        if ($deleted > 0) {
            $this->info("Pulizia: {$deleted} backup precedenti rimossi (>{$days}gg).");
        }
    }
}
