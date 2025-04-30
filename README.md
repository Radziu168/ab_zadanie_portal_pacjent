# Panel pacjenta (Laravel + Vue)

Projekt aplikacji webowej dla pacjentów laboratorium medycznego.  
Umożliwia logowanie pacjenta i wgląd w wyniki badań laboratoryjnych.

---

## Stack technologiczny

-   **Backend:** Laravel 12 + PostgreSQL + jwt-auth
-   **Frontend:** Vue 3 + Vuetify 3
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

-   Frontend: [http://localhost:5173](http://localhost:5173/)
-   Backend: [http://localhost:8000](http://localhost:8000/)

---

## Dostęp do aplikacji

Aby móc się zalogować, należy utwórzyć pacjenta w bazie. \
_Uwaga: Projekt w środowisku deweloperskim. Jeśli aplikacja uruchamiana jest z zewnątrz kontenera (np. w terminalu VSCode), powienien używany być przedrostek `docker exec -it alab-backend...` z przykładu poniżej. Jeśli jest to lokalny terminal (np. w środowisku Herd) i Laravel ma dostęp do bazy, to wystarczy `php artisan...`._

### 1. Ręczne utworzenie pacjenta w Artisan Tinker

```bash
docker exec -it alab-backend php artisan tinker
```

```php
App\Models\Patient::create(['name' => 'Radek', 'surname' => 'Znazwiskiem', 'sex' => 'mężczyzna', 'birth_date' => '1990-03-03',]);
```

-   **Login:** `RadekZnazwiskiem`
-   **Hasło:** `1990-03-03`

### 2. Import pacjentów i wyników z CSV

Przykładowy plik CSV znajduje się katalogu `/storage/app/`.

Domyślne polecenie:

```bash
docker exec -it alab-backend php artisan import:results
```

Import z innego pliku (wrzuconego do `/storage/app/`):

```bash
docker exec -it alab-backend php artisan import:results storage/app/nazwa_pliku.csv
```

Struktura CSV:

```csv
patientId;patientName;patientSurname;patientSex;patientBirthDate;orderId;testName;testValue;testReference1;Piotr;Kowalski;m;1983-04-12;1;Glukoza;91;70-99
```

Niepoprawne wiersze są pomijane i logowane w `storage/logs/import.log`.

---

## Endpointy API

| Metoda | Endpoint       | Opis                                                |
| ------ | -------------- | --------------------------------------------------- |
| POST   | `/api/login`   | Zwraca token JWT na podstawie loginu i hasła        |
| GET    | `/api/results` | Zwraca dane pacjenta i wyniki badań (wymaga tokenu) |

---

## Przydatne komendy

### 1. Import pacjentów z pliku CSV

-   **Domyślny:**
    ```bash
    docker exec -it alab-backend php artisan import:results
    ```
-   **Inny plik:**
    ```bash
    docker exec -it alab-backend php artisan import:results storage/app/nazwa_pliku.csv
    ```

### 2. Seeder

-   **Sugeruje się użycie domyślnego seeder'a, który wygeneruje 10 pacjentów z różnymi wynikami:**
    ```bash
    docker exec -it alab-backend php artisan db:seed
    ```

### 3. Artisan Tinker

-   **Tinker w kontenerze:**
    ```bash
    docker exec -it alab-backend php artisan tinker
    ```
-   **Lista pacjentów:**
    ```php
    \App\Models\Patient::all();
    ```
-   **Wyniki dla pacjenta nr 1:**
    ```php
    \App\Models\Patient::find(1)->orders()->with('results')->get();
    ```

### 4. Reset / rollback

-   **Usuń rekordy:**
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

## ![CI Pipeline](https://gitlab.com/Radek168/ab_zadanie_portal_pacjent/badges/main/pipeline.svg)

## Token JWT

Po zalogowaniu token zapisuje się w `localStorage`.

Czas życia tokenu: `60 minut`  
(Można zmienić w `.env`: `JWT_TTL=60`)
