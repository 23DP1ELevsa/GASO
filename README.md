# GASO - Gāzes balonu uzskaites sistēma

GASO projekts sastāv no divām atsevišķām daļām:

- `Backend` - Laravel 13 API ar datubāzi un biznesa loģiku.
- `Frontend` - Vue 3 + Vite lietotāja saskarne.

## Funkcionalitāte

- pieslēgšanās ar lomām: administrators, darbinieks, klients;
- balonu reģistrs ar statusiem, filtrēšanu un rediģēšanu;
- balonu izsniegšana un atgriešana;
- klientu un darbinieku pārvaldība administratoram;
- atskaišu ģenerēšana;
- demo dati ātrai pārbaudei.

## Prasības

Lai projektu palaistu lokāli, nepieciešams:

- PHP 8.3 vai jaunāka versija;
- Composer;
- Node.js 18+ un npm;
- MySQL serveris, piemēram, XAMPP.

## Kā palaist visu projektu

Projekts jāpalaiž divās atsevišķās termināļa cilnēs: viena backendam, otra frontendam.

### 1. Palaist backend

Atver termināli mapē `GASO/Backend` un izpildi:

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Backend pēc noklusējuma būs pieejams šeit:

```text
http://127.0.0.1:8000
```

### 2. Palaist frontend

Atver otru termināli mapē `GASO/Frontend` un izpildi:

```powershell
npm install
copy .env.example .env
npm run dev
```

Frontend pēc noklusējuma būs pieejams šeit:

```text
http://127.0.0.1:5173
```

### 3. Atvērt projektu pārlūkā

Kad abas daļas ir palaistas, atver pārlūkā:

```text
http://127.0.0.1:5173
```

Frontend automātiski sazināsies ar backend API adresi:

```text
http://127.0.0.1:8000/api
```

Administratora reģistrācijas sadaļa atrodas login lapā. Tā ļauj publiski izveidot tikai pirmo administratoru; kad pirmais administrators ir izveidots, nākamos administratorus var pievienot tikai no administratora paneļa.

## Backend palaišana

Atver termināli mapē `GASO/Backend` un izpildi:

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Backend būs pieejams šeit:

```text
http://127.0.0.1:8000
```

Ja atkarības un `.env` jau ir sagatavoti, ikdienas palaišanai parasti pietiek ar:

```powershell
php artisan serve
```

## Frontend palaišana

Atver termināli mapē `GASO/Frontend` un izpildi:

```powershell
npm install
copy .env.example .env
npm run dev
```

Frontend būs pieejams šeit:

```text
http://127.0.0.1:5173
```

Ja atkarības un `.env` jau ir sagatavoti, ikdienas palaišanai parasti pietiek ar:

```powershell
npm run dev
```

Frontend izmanto backend API adresi:

```text
http://127.0.0.1:8000/api
```

## Demo piekļuves dati

- Administrators: `admin1@gaso.lv` / `password`
- Darbinieks: `darbinieks@gaso.lv` / `password`
- Klients: `klients1` / `password`