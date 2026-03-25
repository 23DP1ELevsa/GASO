# GASO Backend

Šī ir GASO projekta backend daļa, kas veidota ar Laravel 13. Backend nodrošina:

- autentifikāciju;
- lietotāju lomas;
- balonu, klientu un darbinieku pārvaldību;
- darījumu uzskaiti;
- atskaišu ģenerēšanu;
- API, ko izmanto frontend lietotne.

## Prasības

Pirms palaišanas pārliecinies, ka ir uzinstalēts:

- PHP 8.3 vai jaunāka versija;
- Composer;
- MySQL serveris.

## Sākotnējā uzstādīšana

Atver termināli mapē `GASO/Backend` un izpildi:

```powershell
composer install
copy .env.example .env
php artisan key:generate
```

## Datubāzes konfigurācija

Failā `.env.example` jau ir sagatavota šāda konfigurācija:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gaso_backend
DB_USERNAME=root
DB_PASSWORD=
```

Pirms migrāciju palaišanas:

1. izveido MySQL datubāzi `gaso_backend`;
2. ja nepieciešams, pielāgo `DB_USERNAME` un `DB_PASSWORD` failā `Backend/.env`.

## Backend palaišana

Kad `.env` ir sagatavots un datubāze izveidota, izpildi:

```powershell
php artisan migrate:fresh --seed
php artisan serve
```

Backend būs pieejams šeit:

```text
http://127.0.0.1:8000
```

API bāzes adrese:

```text
http://127.0.0.1:8000/api
```

## Noderīgas komandas

Palaist testus:

```powershell
php artisan test
```

Pārģenerēt demo datus:

```powershell
php artisan migrate:fresh --seed
```

## Demo lietotāji

- Administrators: `admin@gaso.lv` / `password`
- Darbinieks: `darbinieks@gaso.lv` / `password`
- Klients: `klients1` / `password`

## Saistītā dokumentācija

- [../README.md](../README.md)
- [../Frontend/README.md](../Frontend/README.md)

## Ātra atkārtota palaišana

Ja backend jau ir uzstādīts un datubāze sagatavota, pietiek ar:

```powershell
php artisan serve
```

Ja nepieciešams atiestatīt datus, pirms tam palaid arī:

```powershell
php artisan migrate:fresh --seed
```
