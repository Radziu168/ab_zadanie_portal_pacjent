<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\Order;
use App\Models\Result;
use Faker\Factory as Faker;

class RandomPatients extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('pl_PL');

        $tests = [
            ['name' => 'Glukoza', 'min' => 70, 'max' => 110, 'reference' => '70-99'],
            ['name' => 'Hemoglobina', 'min' => 12, 'max' => 16, 'reference' => '12-16'],
            ['name' => 'Cholesterol', 'min' => 150, 'max' => 240, 'reference' => '150-200'],
            ['name' => 'TSH', 'min' => 0.4, 'max' => 4.0, 'reference' => '0.4-4.0'],
            ['name' => 'Kreatynina', 'min' => 0.6, 'max' => 1.3, 'reference' => '0.6-1.3'],
        ];

        for ($i = 1; $i <= 10; $i++) {
            $sex = $faker->randomElement(['m', 'f']);

            $patient = Patient::create([
                'name' => $sex === 'f' ? $faker->firstNameFemale : $faker->firstNameMale,
                'surname' => $faker->lastName,
                'sex' => $sex,
                'birth_date' => $faker->date('Y-m-d', '-18 years'),
            ]);

            // Losowanie 1–3 orderów
            for ($j = 0; $j < rand(1, 3); $j++) {
                $order = $patient->orders()->create();

                // Losowanie 2–4 wyników
                $selectedTests = $faker->randomElements($tests, rand(2, 4));
                $results = [];

                foreach ($selectedTests as $test) {
                    $value = round($faker->randomFloat(2, $test['min'], $test['max']), 2);

                    $results[] = [
                        'name' => $test['name'],
                        'value' => $value,
                        'reference' => $test['reference'],
                    ];
                }

                $order->results()->createMany($results);
            }
        }
    }
}

