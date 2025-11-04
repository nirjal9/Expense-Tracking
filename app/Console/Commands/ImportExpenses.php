<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Expense;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ImportExpenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-expenses {filename=six_month_expense_dataset.csv} {--user-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import expenses from a CSV file into the expenses table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = $this->argument('filename');
        $overrideUserId = $this->option('user-id');
        $path = storage_path('app/' . $filename);
        if (!file_exists($path)) {
            $this->error("File not found: $path");
            return 1;
        }

        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle);
            $rowCount = 0;
            $skippedCount = 0;
            
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                
                // If user_id override is specified, only import rows that match that user_id in CSV
                if ($overrideUserId !== null && isset($data['user_id']) && (int)$data['user_id'] != (int)$overrideUserId) {
                    $skippedCount++;
                    continue;
                }
                
                try {
                    Expense::updateOrCreate(
                        [
                            'id' => $data['id'],
                        ],
                        [
                            'user_id' => $overrideUserId !== null ? (int)$overrideUserId : $data['user_id'],
                            'category_id' => $data['category_id'],
                            'description' => $data['description'],
                            'amount' => $data['amount'],
                            'date' => $data['date'],
                            'created_at' => $data['created_at'],
                            'deleted_at' => $data['deleted_at'] ?: null,
                        ]
                    );
                    $rowCount++;
                } catch (\Exception $e) {
                    $this->error('Error importing row: ' . json_encode($data) . ' - ' . $e->getMessage());
                }
            }
            fclose($handle);
            $this->info("Imported $rowCount expenses from $filename");
            if ($skippedCount > 0) {
                $this->info("Skipped $skippedCount expenses (not for specified user)");
            }
        } else {
            $this->error("Could not open file: $path");
            return 1;
        }
        return 0;
    }
}
