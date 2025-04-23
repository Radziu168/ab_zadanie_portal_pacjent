<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\Order;
use App\Models\Result;

class PatientWithOrdersSeeder extends Seeder
{
    public function run(): void
    {
        $patient = Patient::create([
            'name' => 'Małgorzata',
            'surname' => 'Nazwiskowa',
            'sex' => 'f',
            'birth_date' => '1998-04-20',
        ]);

        for ($i = 1; $i <= 2; $i++) {
            $order = $patient->orders()->create();

            $order->results()->createMany([
                ['name' => 'Glukoza', 'value' => rand(70, 110), 'reference' => '70-99'],
                ['name' => 'Hemoglobina', 'value' => rand(12, 16), 'reference' => '12-16'],
            ]);
        }
    }
}
