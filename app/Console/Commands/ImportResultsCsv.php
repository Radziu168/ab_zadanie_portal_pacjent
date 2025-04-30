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
    protected $signature = 'import:results {path=storage/app/results.csv} {--auto-update}';
    protected $description = 'Importuje wyniki badań z pliku CSV';

    public function handle()
    {
        $path = base_path($this->argument('path'));
    
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
                    
                    $sex = strtolower(trim($record['patientSex']));
                    $sex = match ($sex) {
                        'male' => 'mężczyzna',
                        'female' => 'kobieta',
                        default => null,
                    };
                    if (!$sex) {
                        throw new \Exception("Nieprawidłowa wartość płci: " . $record['patientSex']);
                    }
                    // Tworzenie lub modyfikacja pacjenta
                    $patient = Patient::find($record['patientId']);

                    if (!$patient) {
                        $patient = Patient::create([
                            'id' => $record['patientId'],
                            'name' => $record['patientName'],
                            'surname' => $record['patientSurname'],
                            'sex' => $sex,
                            'birth_date' => $record['patientBirthDate'],
                        ]);
                        $this->info("Utworzono nowego pacjenta #{$patient->id}");
                    } else {
                        $differences = [];

                        if ($patient->name !== $record['patientName']) {
                            $differences['name'] = [$patient->name, $record['patientName']];
                        }
                        if ($patient->surname !== $record['patientSurname']) {
                            $differences['surname'] = [$patient->surname, $record['patientSurname']];
                        }
                        if ($patient->sex !== $sex) {
                            $differences['sex'] = [$patient->sex, $sex];
                        }
                        if ($patient->birth_date != $record['patientBirthDate']) {
                            $differences['birth_date'] = [$patient->birth_date, $record['patientBirthDate']];
                        }

                        if (!empty($differences)) {
                            $this->warn("Uwaga! Dane pacjenta #{$patient->id} różnią się:");
                            foreach ($differences as $field => [$old, $new]) {
                                $this->line(" - $field: '$old' -> '$new'");
                            }

                            if ($this->option('auto-update')) {
                                $patient->update([
                                    'name' => $record['patientName'],
                                    'surname' => $record['patientSurname'],
                                    'sex' => $sex,
                                    'birth_date' => $record['patientBirthDate'],
                                ]);
                                $this->info("Dane pacjenta #{$patient->id} zostały automatycznie zaktualizowane");
                            } else {
                                if ($this->confirm('Uwaga! Czy chcesz nadpisać dane pacjenta?', false)) {
                                    $patient->update([
                                        'name' => $record['patientName'],
                                        'surname' => $record['patientSurname'],
                                        'sex' => $sex,
                                        'birth_date' => $record['patientBirthDate'],
                                    ]);
                                    $this->info("Dane pacjenta #{$patient->id} zostały zaktualizowane");
                                } else {
                                    $this->info("Dane pacjenta #{$patient->id} zostały zachowane bez zmian.");
                                }
                            }
                        }
                    }
                    // Zlecenia
                    $order = Order::find($record['orderId']);

                    if (!$order) {
                        $order = Order::create([
                            'id' => $record['orderId'],
                            'patient_id' => $patient->id,
                        ]);
                    } else {
                        if ($order->patient_id !== $patient->id) {
                            $order->update([
                                'patient_id' => $patient->id,
                            ]);
                        }
                    }
                    // Wyniki
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
