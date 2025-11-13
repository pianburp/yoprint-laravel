<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Upload;
use App\Traits\Utf8Cleaner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;

class ProcessCsvUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $upload;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param Upload $upload
     */
    public function __construct(Upload $upload)
    {
        $this->upload = $upload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $filePath = storage_path('app/uploads/' . $this->upload->file_name);

        if (!file_exists($filePath)) {
            $this->upload->update(['status' => 'failed']);
            Log::error('CSV file not found: ' . $filePath);
            return;
        }

        try {
            // Update status to processing
            $this->upload->update(['status' => 'processing']);

            // Read cleaned content from storage
            $cleanedContent = Utf8Cleaner::cleanUtf8(file_get_contents($filePath));
            $csv = Reader::createFromString($cleanedContent);
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
            $processedCount = 0;
            $errorCount = 0;

            // Process records in chunks 
            DB::beginTransaction();
            
            try {
                foreach ($records as $offset => $record) {
                    try {
                        // Validate that UNIQUE_KEY exists
                        if (empty($record['UNIQUE_KEY'])) {
                            Log::warning('Skipping row ' . ($offset + 2) . ': Missing UNIQUE_KEY');
                            $errorCount++;
                            continue;
                        }

                        // Clean all string values
                        $cleanedRecord = array_map(function ($value) {
                            return is_string($value) ? Utf8Cleaner::cleanUtf8($value) : $value;
                        }, $record);

                        // UPSERT 
                        Product::updateOrCreate(
                            ['unique_key' => trim($cleanedRecord['UNIQUE_KEY'])],
                            [
                                'product_title' => trim($cleanedRecord['PRODUCT_TITLE'] ?? ''),
                                'product_description' => trim($cleanedRecord['PRODUCT_DESCRIPTION'] ?? ''),
                                'style_number' => trim($cleanedRecord['STYLE#'] ?? ''),
                                'sanmar_mainframe_color' => trim($cleanedRecord['SANMAR_MAINFRAME_COLOR'] ?? ''),
                                'size' => trim($cleanedRecord['SIZE'] ?? ''),
                                'color_name' => trim($cleanedRecord['COLOR_NAME'] ?? ''),
                                'piece_price' => $this->parsePrice($cleanedRecord['PIECE_PRICE'] ?? '0'),
                            ]
                        );

                        $processedCount++;
                    } catch (\Exception $e) {
                        Log::error('Error processing row ' . ($offset + 2) . ': ' . $e->getMessage());
                        $errorCount++;
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }


            // Update status to completed
            $this->upload->update(['status' => 'completed']);
            
            Log::info("CSV processing completed. Processed: {$processedCount}, Errors: {$errorCount}");
        } catch (\Exception $e) {
            $this->upload->update(['status' => 'failed']);
            Log::error('CSV processing failed: ' . $e->getMessage());
            
            // Clean up temp file if it exists
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Clean non-UTF-8 characters from string
     *
     * @param string $string
     * @return string
     */
    // Cleaning logic moved to App\Traits\Utf8Cleaner to avoid duplication

    /**
     * Parse price from string
     *
     * @param string $price
     * @return float
     */
    protected function parsePrice($price)
    {
        // Remove any non-numeric characters except decimal point
        $price = preg_replace('/[^0-9.]/', '', $price);
        
        return floatval($price);
    }
}