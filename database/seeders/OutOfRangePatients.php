<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\Order;
use App\Models\Result;
use Faker\Factory as Faker;

class OutOfRangePatients extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('pl_PL');

        $tests = [
            ['name' => 'Glukoza', 'min' => 70, 'max' => 99, 'reference' => '70-99', 'out_low' => 50, 'out_high' => 150],
            ['name' => 'Hemoglobina', 'min' => 12, 'max' => 16, 'reference' => '12-16', 'out_low' => 9, 'out_high' => 18],
            ['name' => 'Cholesterol', 'min' => 150, 'max' => 200, 'reference' => '150-200', 'out_low' => 120, 'out_high' => 260],
            ['name' => 'TSH', 'min' => 0.4, 'max' => 4.0, 'reference' => '0.4-4.0', 'out_low' => 0.1, 'out_high' => 7.5],
            ['name' => 'Kreatynina', 'min' => 0.6, 'max' => 1.3, 'reference' => '0.6-1.3', 'out_low' => 0.3, 'out_high' => 2.0],
        ];

        for ($i = 1; $i <= 5; $i++) {
            $sex = $faker->randomElement(['mężczyzna', 'kobieta']);

            $patient = Patient::create([
                'name' => $sex === 'kobieta' ? $faker->firstNameFemale : $faker->firstNameMale,
                'surname' => $faker->lastName,
                'sex' => $sex,
                'birth_date' => $faker->date('Y-m-d', '-18 years'),
            ]);

            for ($j = 0; $j < rand(1, 3); $j++) {
                $order = $patient->orders()->create();

                // Losowanie 3–5 wyników
                $selectedTests = $faker->randomElements($tests, rand(3, 5));
                $results = [];

                foreach ($selectedTests as $test) {
                    $value = $faker->boolean(50)
                        ? round($faker->randomFloat(2, $test['out_low'], $test['min'] - 0.1), 2)
                        : round($faker->randomFloat(2, $test['max'] + 0.1, $test['out_high']), 2);

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
