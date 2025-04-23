<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Walidacja formatu
        $validator = Validator::make($request->all(), [
            'login'    => ['required','string','not_regex:/\s/'],
            'password' => ['required','regex:/^\d{4}-\d{2}-\d{2}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Nieprawidłowy format logowania. Użyj ImięNazwisko'
            ], 422);
        }

        // Parsowanie z pomocą Unicode property
        preg_match('/^(\p{Lu}\p{Ll}+)(\p{Lu}\p{Ll}+)\z/u', $request->login, $matches);

        if (count($matches) !== 3) {
            return response()->json([
                'error' => 'Nieprawidłowy format logowania. Wpisz ImięNazwisko (bez spacji)'
            ], 401);
        }

        $rawName    = $matches[1];
        $rawSurname = $matches[2];

        // ASCII i lowercase
        $name    = Str::lower(Str::ascii($rawName));
        $surname = Str::lower(Str::ascii($rawSurname));

        // Wyszukiwanie pacjenta uodpornione na diakrytyki
        $patient = Patient::all()->first(function ($p) use ($name, $surname) {
            return Str::lower(Str::ascii($p->name)) === $name
                && Str::lower(Str::ascii($p->surname)) === $surname;
        });

        if (! $patient) {
            return response()->json(['error' => 'Pacjent nie istnieje'], 404);
        }

        // Weryfikacja hasła
        if ($patient->birth_date !== $request->password) {
            return response()->json([
                'error' => 'Nieprawidłowe hasło. Użyj daty urodzenia w formacie RRRR-MM-DD'
            ], 401);
        }

        // Generowanie tokena
        $token = JWTAuth::fromUser($patient);

        return response()->json(['token' => $token]);
    }
}
