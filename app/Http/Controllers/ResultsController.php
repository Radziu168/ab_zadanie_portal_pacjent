<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ResultsController extends Controller
{
    public function getResults(Request $request)
    {
        $patient = JWTAuth::parseToken()->authenticate();

        $patientData = [
            'id' => $patient->id,
            'name' => $patient->name,
            'surname' => $patient->surname,
            'sex' => $patient->sex,
            'birthDate' => $patient->birth_date,
        ];

        $orders = $patient->orders()->with('results')->get()->map(function ($order) {
            return [
                'orderId' => (string) $order->id,
                'results' => $order->results->map(function ($result) {
                    return [
                        'name' => $result->name,
                        'value' => $result->value,
                        'reference' => $result->reference,
                    ];
                }),
            ];
        });

        return response()->json([
            'patient' => $patientData,
            'orders' => $orders,
        ]);

    }
}
