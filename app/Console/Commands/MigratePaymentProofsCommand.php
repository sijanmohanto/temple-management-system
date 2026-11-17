<?php

namespace App\Console\Commands;

use App\Models\CollectionPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigratePaymentProofsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'portal:migrate-proofs
                            {--delete-original : Delete original public disk files after successful migration}
                            {--dry-run : Simulate the migration without copying or deleting files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely copy payment proof files from the public disk to the private local disk with checksum verification.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleteOriginal = (bool) $this->option('delete-original');
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Starting Payment Proofs Migration...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE: No files will be copied or deleted.');
        }

        $payments = CollectionPayment::whereNotNull('payment_proof_path')->get();

        $migratedCount = 0;
        $alreadyLocalCount = 0;
        $missingCount = 0;
        $hashMismatchCount = 0;

        foreach ($payments as $payment) {
            $path = str_replace('\\', '/', $payment->payment_proof_path);

            $existsPublic = Storage::disk('public')->exists($path);
            $existsLocal = Storage::disk('local')->exists($path);

            if (! $existsPublic && ! $existsLocal) {
                $this->error("Missing file for {$payment->payment_code}: {$path}");
                $missingCount++;

                continue;
            }

            if ($existsLocal && ! $existsPublic) {
                $alreadyLocalCount++;

                continue;
            }

            // Exists on public
            $publicContent = Storage::disk('public')->get($path);
            $publicHash = hash('sha256', $publicContent);

            if ($existsLocal) {
                $localContent = Storage::disk('local')->get($path);
                $localHash = hash('sha256', $localContent);

                if ($publicHash === $localHash) {
                    $alreadyLocalCount++;
                    if ($deleteOriginal && ! $dryRun) {
                        Storage::disk('public')->delete($path);
                    }

                    continue;
                }

                $this->warn("Hash mismatch for {$payment->payment_code}: {$path}");
                $hashMismatchCount++;

                continue;
            }

            // Exists on public, not yet on local: copy and verify checksum
            if (! $dryRun) {
                Storage::disk('local')->put($path, $publicContent);

                $writtenContent = Storage::disk('local')->get($path);
                $writtenHash = hash('sha256', $writtenContent);

                if ($writtenHash !== $publicHash) {
                    $this->error("Failed checksum verification after copying {$path} for {$payment->payment_code}");
                    $hashMismatchCount++;

                    continue;
                }

                if ($deleteOriginal) {
                    Storage::disk('public')->delete($path);
                }
            }

            $migratedCount++;
            $this->line("Migrated: {$path} ({$payment->payment_code})");
        }

        $this->newLine();
        $this->info('Migration Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Payment Records with Proof', $payments->count()],
                ['Newly Migrated to Private Local Disk', $migratedCount],
                ['Already Present in Private Local Disk', $alreadyLocalCount],
                ['Missing on Disk', $missingCount],
                ['Checksum Mismatches', $hashMismatchCount],
            ]
        );

        return Command::SUCCESS;
    }
}
