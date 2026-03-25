# GASO Frontend

Šī ir GASO projekta frontend daļa, kas veidota ar Vue 3 un Vite. Frontend nodrošina:

- pieslēgšanās ekrānu;
- administratora, darbinieka un klienta saskarnes;
- darbu ar backend API;
- balonu, lietotāju, darījumu un atskaišu attēlošanu.

## Prasības

Pirms palaišanas pārliecinies, ka ir uzinstalēts:

- Node.js 18 vai jaunāka versija;
- npm.

## Sākotnējā uzstādīšana

Atver termināli mapē `GASO/Frontend` un izpildi:

```powershell
npm install
copy .env.example .env
```

## API konfigurācija

Frontend izmanto šo vides mainīgo:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

Ja backend tiek palaists citā adresē vai portā, izlabo `Frontend/.env` failu.

## Frontend palaišana

Lai palaistu izstrādes režīmā, izpildi:

```powershell
npm run dev
```

Frontend būs pieejams šeit:

```text
http://127.0.0.1:5173
```

## Produkcijas būves pārbaude

Lai pārliecinātos, ka frontend korekti kompilējas, izpildi:

```powershell
npm run build
```

## Ātra atkārtota palaišana

Ja atkarības jau ir uzinstalētas un `.env` ir sagatavots, pietiek ar:

```powershell
npm run dev
```

## Saistītā dokumentācija

- [../README.md](../README.md)
- [../Backend/README.md](../Backend/README.md)