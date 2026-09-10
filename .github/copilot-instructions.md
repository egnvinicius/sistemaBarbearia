# Copilot instructions for barbearia-app

This repository is a small booking app split between a Svelte frontend and a PHP/MySQL API. Treat the two layers as separate but tightly coupled: the browser calls the PHP endpoints under `/api`, and the backend owns the appointment and configuration logic.

## Build, test, and lint

- Frontend app: `front-app/`
- Install dependencies: `cd front-app && npm install`
- Run the UI locally: `cd front-app && npm run dev`
- Build for production: `cd front-app && npm run build`
- Preview the production bundle: `cd front-app && npm run preview`
- There are no lint or test scripts configured in `front-app/package.json` right now. The repository's existing validation command is `npm run build`.
- There is no single-test runner configured in this repo today, so do not invent a non-existent test command. When adding tests later, prefer the project-specific runner and target the smallest relevant file or project.

## High-level architecture

- `front-app/` is a Vite + Svelte app. The main startup is in `front-app/src/App.svelte`.
- `App.svelte` loads branding data from `GET /api/config.php` on mount, applies the returned colors to `document.documentElement`, and renders the booking flow component.
- `front-app/src/components/Agendamento.svelte` is the main booking UI. It fetches available slots from `GET /api/horarios_livres.php` and posts a reservation to `POST /api/agendar.php`.
- `api/` contains the PHP endpoints and shared DB bootstrap:
  - `api/db.php` creates the PDO MySQL connection used by the rest of the API.
  - `api/config.php` returns brand configuration (`nome_fantasia`, `cor_primaria`, `cor_fundo`, `logo_url`).
  - `api/horarios_livres.php` computes open time slots by scanning 15-minute blocks and checking for scheduling overlap.
  - `api/agendar.php` validates input, looks up the service duration, prevents overlapping bookings, and inserts the appointment.
  - `api/dashboard.php` aggregates daily/monthly metrics such as revenue, no-show rate, and professional performance.
- The backend expects a MySQL database at `127.0.0.1` with the database name `barbearia_app`, using the default local XAMPP credentials (`root` / empty password).
- The frontend is not a full framework app with a separate API server; it is intended to run alongside the PHP endpoints in a local XAMPP-style setup.

## Key conventions

- Keep the browser and PHP API contract explicit: UI code should fetch and post JSON payloads and handle HTTP status codes rather than reaching into the database directly.
- Preserve the existing Portuguese naming and payload shape used by the API (`nome_fantasia`, `data_hora_inicio`, `erro`, `mensagem`, etc.) when making changes.
- Booking logic is driven by service duration and overlap checks. When changing availability logic, match the same semantics used in `api/horarios_livres.php` and `api/agendar.php`: 15-minute search windows, service duration, and overlap rules.
- Overlap prevention is already implemented in both the PHP availability scan and the booking insert path; keep the same business rule when editing scheduling behavior.
- Theme colors are loaded from the backend and applied at runtime in `App.svelte`; do not hardcode brand colors in components unless the backend contract is intentionally changing.
- The codebase uses component-scoped CSS in Svelte; keep styling local to the relevant component unless a shared style is clearly required.
- When adding a feature that spans the frontend and the backend, update both the fetch contract and the PHP handler together.

