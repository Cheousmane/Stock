<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a backup of the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Starting database backup...');

        $dbConnection = config('database.default', 'mysql');
        $dumpFile = 'backups/database_' . now()->format('Y_m_d_His') . '.sql';

        try {
            switch ($dbConnection) {
                case 'mysql':
                    $this->backupMySQL($dumpFile);
                    break;
                case 'pgsql':
                    $this->backupPostgreSQL($dumpFile);
                    break;
                case 'sqlite':
                    $this->backupSQLite($dumpFile);
                    break;
                default:
                    $this->error("Backup not supported for database driver: {$dbConnection}");
                    return 1;
            }

            $this->info("Database backup created: {$dumpFile}");

            // Upload to remote storage if configured
            $this->maybeUploadToStorage($dumpFile);

            return 0;
        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            return 1;
        }
    }

    private function backupMySQL(string $dumpFile): void
    {
        $host = env('DB_HOST', '127.0.0.1');
        $database = env('DB_DATABASE', 'facturation');
        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');

        $command = "mysqldump -h {$host} -u {$username} -p{$password} {$database}";

        $process = new Process([$command]);
        $process->run();

        if (!$process->successful()) {
            throw new \RuntimeException('MySQL dump failed: ' . $process->getErrorOutput());
        }

        Storage::disk('local')->put($dumpFile, $process->getOutput());
    }

    private function backupPostgreSQL(string $dumpFile): void
    {
        $host = env('DB_HOST', 'localhost');
        $database = env('DB_DATABASE', 'facturation');
        $username = env('DB_USERNAME', 'postgres');
        $password = env('DB_PASSWORD', '');

        $command = "pg_dump -h {$host} -U {$username} -d {$database}";

        $process = new Process([$command], env: [
            'PGPASSWORD' => $password,
        ]);

        $process->run();

        if (!$process->successful()) {
            throw new \RuntimeException('PostgreSQL dump failed: ' . $process->getErrorOutput());
        }

        Storage::disk('local')->put($dumpFile, $process->getOutput());
    }

    private function backupSQLite(string $dumpFile): void
    {
        $dbPath = database_path('database.sqlite');

        if (!file_exists($dbPath)) {
            throw new \RuntimeException("SQLite database not found at {$dbPath}");
        }

        $content = file_get_contents($dbPath);
        Storage::disk('local')->put($dumpFile, $content);
    }

    private function maybeUploadToStorage(string $dumpFile): void
    {
        // Upload to S3/R2 if configured
        $s3Key = env('BACKUP_S3_KEY');
        $s3Secret = env('BACKUP_S3_SECRET');
        $s3Bucket = env('BACKUP_S3_BUCKET');

        if ($s3Key && $s3Secret && $s3Bucket) {
            // Use AWS SDK to upload
            $this->log("Would upload {$dumpFile} to s3://{$s3Bucket}/{$dumpFile}");
        }

        // Keep only last 7 backups
        $this->rotateBackups(7);
    }

    private function rotateBackups(int $keepCount): void
    {
        $backups = Storage::disk('local')->files('backups/');
        usort($backups, function (string $a, string $b) {
            return filemtime(Storage::disk('local')->path($b)) <=> filemtime(Storage::disk('local')->path($a));
        });

        $toDelete = array_slice($backups, $keepCount);
        foreach ($toDelete as $fileToDelete) {
            unlink(Storage::disk('local')->path($fileToDelete));
            $this->info("Old backup removed: {$fileToDelete}");
        }
    }
}