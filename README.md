# GASO - Gazes balonu uzskaites sistema

Projekts ir sadalits divas mapes:

- `Backend` - Laravel 13 API, kuru var palaist ar `php artisan serve`
- `Frontend` - Vue 3 + Vite lietotaja saskarne, kuru var palaist ar `npm run dev`

## Ieviesas funkcijas

- lietotaju pieslegsanas ar lomam: administrators, darbinieks, klients;
- gazes balonu registrs ar statusiem, meklešanu un redigešanu;
- balonu izsniegsanas un atgriesanas darijumi;
- klientu un darbinieku uzturesana administratoram;
- atskaites par baloniem, klientiem un darijumiem;
- demo dati, lai sistemu varetu uzreiz parbaudit.

## Backend palaisana

1. Atver `GASO/Backend`
2. Nokopē `.env.example` uz `.env`, ja tas vel nav izdarits
3. Ja izmanto XAMPP + MySQL, izveido datubazi `gaso_backend`
4. Palaid:

```powershell
composer install
php artisan migrate:fresh --seed
php artisan serve
```

Backend adrese pec noklusejuma: `http://127.0.0.1:8000`

## Frontend palaisana

1. Atver `GASO/Frontend`
2. Nokopē `.env.example` uz `.env`, ja vajag mainit API adresi
3. Palaid:

```powershell
npm install
npm run dev
```

Frontend adrese pec noklusejuma: `http://127.0.0.1:5173`

## Demo pieslegsanas dati

- Administrators: `admin@gaso.lv` / `password`
- Darbinieks: `darbinieks@gaso.lv` / `password`
- Klients: `klients1` / `password`

## XAMPP piezime

Ja Backend palaid ar XAMPP MySQL, `.env.example` jau ir sagatavots darbam ar `root` lietotaju un datubazi `gaso_backend`. Ja parole atskiras, pielabo `DB_PASSWORD` vertibu `.env` faila.