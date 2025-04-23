<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Patient;
use App\Models\Order;
use App\Models\Result;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Exception;
use Throwable;

class ImportResultsCsv extends Command
{
    protected $signature = 'import:results {path=storage/app/results.csv}';
    protected $description = 'Importuje wyniki badań z pliku CSV';

    public function handle()
    {
        $path = $this->argument('path');
    
        $logFile = storage_path('logs/import.log');
        file_put_contents($logFile, "=== START IMPORTU ===\n", FILE_APPEND);
    
        if (!file_exists($path)) {
            $msg = "Plik nie istnieje: $path";
            file_put_contents($logFile, $msg . "\n", FILE_APPEND);
            $this->error($msg);
            return Command::FAILURE;
        }
    
        try {
            $raw = file($path);
            $firstLine = $raw[0] ?? '';
            // wykrycie separatora
            $delimiter = str_contains($firstLine, ';') ? ';' : ',';
            $this->info("Użyty separator: " . ($delimiter === ';' ? 'średnik (;)' : 'przecinek (,)'));
            
            $csv = \League\Csv\Reader::createFromPath($path, 'r');
            $csv->setDelimiter($delimiter);
            $csv->setHeaderOffset(0);
    
            foreach ($csv as $index => $record) {
                try {
                    if (empty($record['patientName']) || empty($record['orderId']) || empty($record['testName'])) {
                        throw new \Exception("Brakuje wymaganych danych (patientName, orderId, testName)");
                    }
                    
                    //'male' -> 'm', 'female' → 'f'
                    $sex = strtolower(trim($record['patientSex']));
                    $sex = match ($sex) {
                        'male' => 'm',
                        'female' => 'f',
                        default => null,
                    };
                    if (!$sex) {
                        throw new \Exception("Nieprawidłowa wartość płci: " . $record['patientSex']);
                    }

                    $patient = Patient::firstOrCreate([
                        'id' => $record['patientId'],
                    ], [
                        'name' => $record['patientName'],
                        'surname' => $record['patientSurname'],
                        'sex' => $sex,
                        'birth_date' => $record['patientBirthDate'],
                    ]);

                    $order = Order::firstOrCreate([
                        'id' => $record['orderId'],
                        'patient_id' => $patient->id,
                    ]);
    
                    Result::create([
                        'order_id' => $order->id,
                        'name' => $record['testName'],
                        'value' => $record['testValue'],
                        'reference' => $record['testReference'],
                    ]);
    
                    $msg = "✔️ [$index] Dodano wynik '{$record['testName']}' dla pacjenta #{$patient->id}";
                    file_put_contents($logFile, $msg . "\n", FILE_APPEND);
                    $this->info($msg);
    
                } catch (\Throwable $e) {
                    $msg = "❌ [$index] Błąd przy imporcie: " . json_encode($record) . "\n" . $e->getMessage();
                    file_put_contents($logFile, $msg . "\n", FILE_APPEND);
                    $this->warn("Błąd przy wierszu $index — zapisano do import.log");
                }
            }
    
            file_put_contents($logFile, "=== KONIEC IMPORTU ===\n", FILE_APPEND);
            $this->info("Import zakończony.");
            return Command::SUCCESS;
    
        } catch (\Exception $e) {
            $this->error("Nie udało się otworzyć pliku CSV.");
            file_put_contents($logFile, "Błąd otwarcia CSV: " . $e->getMessage() . "\n", FILE_APPEND);
            return Command::FAILURE;
        }
    }
}
