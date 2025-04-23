# Panel pacjenta (Laravel + Vue)

Projekt aplikacji webowej dla pacjentów laboratorium medycznego.  
Umożliwia logowanie pacjenta i wgląd w wyniki badań laboratoryjnych.

---

## Stack technologiczny

-   **Backend:** Laravel 12 + PostgreSQL + JWT Auth
-   **Frontend:** Vue 3 + Vite
-   **CI/CD:** GitLab CI
-   **Docker:** Compose (multi-container)
-   **Import CSV:** obsługa i walidacja plików CSV

---

## Uruchomienie projektu lokalnie w konsoli

1. Uruchom aplikację

```bash
docker-compose up --build
```

2. Aplikacja uruchamia się pod:

-   Frontend: [http://localhost:5173](http://localhost:5173)
-   Backend: [http://localhost:8000/](http://localhost:8000/)

---

## Dostęp do aplikacji

Aby móc się zalogować, należy utwórzyć pacjenta w bazie.
_Uwaga: Jeśli aplikacja uruchamiana jest z zewnątrz kontenera (np. w terminalu VSCode), powienien używany być przedrostek `docker exec -it alab-backend...` z przykładu poniżej. Jeśli jest to lokalny terminal (np. w środowisku Herd) i Laravel ma dostęp do bazy, to wystarczy `php artisan...`._

### 1. Ręczne utworzenie pacjenta w Artisan Tinker

```bash
docker exec -it alab-backend php artisan tinker
```

```php
\App\Models\Patient::create([
    'name'       => 'Radek',
    'surname'    => 'Znazwiskiem',
    'sex'        => 'm',
    'birth_date' => '1990-03-03',
]);
```

-   **Login:** `RadekZnazwiskiem`
-   **Hasło:** `1990-03-03`

### 2. Import pacjentów i wyników z CSV

Przykładowy plik CSV znajduje się katalogu `/storage/app/`.

Domyślne polecenie:

```bash
docker exec -it alab-backend php artisan import:results
```
Import z innego pliku:

```bash
docker exec -it alab-backend php artisan import:results path=public/wyniki.csv
```

Struktura CSV:

```csv
patientId;patientName;patientSurname;patientSex;patientBirthDate;orderId;testName;testValue;testReference1;Piotr;Kowalski;m;1983-04-12;1;Glukoza;91;70-99
```

Niepoprawne wiersze są pomijane i logowane w `storage/logs/laravel.log`.

---

## Endpointy API

| Metoda | Endpoint       | Opis                                                |
| ------ | -------------- | --------------------------------------------------- |
| POST   | `/api/login`   | Zwraca token JWT na podstawie loginu i hasła        |
| GET    | `/api/results` | Zwraca dane pacjenta i wyniki badań (wymaga tokenu) |

---

## Przydatne komendy

### 1. Artisan Tinker

-   **Tinker w kontenerze:**  
    `docker exec -it alab-backend php artisan tinker`
-   **Lista pacjentów:**
    ```php
    \App\Models\Patient::all();
    ```
-   **Dodaj pacjenta ręcznie:**
    ```php
    \App\Models\Patient::create([
        'name'       => 'Mariusz',
        'surname'    => 'Kowalik',
        'sex'        => 'm',
        'birth_date' => '1995-05-15',
    ]);
    ```

### 2. Import CSV

-   **Domyślny:**  
    `docker exec -it alab-backend php artisan import:results`
-   **Inna ścieżka pliku:**  
    `docker exec -it alab-backend php artisan import:results path=public/wyniki.csv`

### 3. Podgląd danych

-   **Zlecenia i wyniki dla pacjenta nr 1:**
    ```php
    \App\Models\Patient::find(1)->orders()->with('results')->get();
    ```

### 4. Reset / rollback

-   **Usuń wszystkie rekordy:**
    ```php
    \App\Models\Result::truncate();
    \App\Models\Order::truncate();
    \App\Models\Patient::truncate();
    ```
-   **Rollback migracji:**  
    ```bash
    docker exec -it alab-backend php artisan migrate:reset
    ```
-   **Rollback i ponowny seed:**  
    ```bash
    docker exec -it alab-backend php artisan migrate:refresh --seed
    ```

### 5. Restart kontenerów

-   **Pełny reset (z usuwaniem wolumenów):**
    ```bash
    docker-compose down -v --remove-orphans
    docker-compose up --build
    ```
-   **Restart backendu:**
    ```bash
    docker-compose restart app
    ```
---

## CI/CD (GitLab)

W repozytorium znajduje się plik `.gitlab-ci.yml`, który:

-   Uruchamia testy backendu
-   Buduje aplikację frontendową
-   Buduje obraz Docker

---

## Token JWT

Po zalogowaniu frontend zapisuje token w `localStorage`:

```
Authorization: Bearer <token>
```

Czas życia tokenu: `60 minut`  
(Można zmienić w `.env`: `JWT_TTL=60`)
