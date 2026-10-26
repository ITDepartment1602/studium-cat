# CLAUDE.md

Guidance for AI agents working in the **Studium-CAT** codebase.

## What this is

Studium-CAT is an adaptive (CAT/IRT) NCLEX review web platform for Filipino nursing
students, built by **NCLEX Amplified**. It is a **plain PHP + MySQL (mysqli)**
application — no framework, no Composer autoloader for app code, no build step.
Three user surfaces:

- **Public/auth** (`index.php`, `signup.php`, `login/`, `signup/`) — login & registration.
- **Student dashboard** (`student/`) — practice exams, NGN question engine, Exam Mode,
  performance charts, notes.
- **Admin panel** (`admin/`) — manage questions, bundles, groups, results, testimonials,
  inquiries, feedback, topics; bulk email via PHPMailer.

The flagship subsystem is the **NGN module** at `student/dashboard/ngn/` — a Next
Generation NCLEX engine with adaptive item selection and partial-credit scoring.

## Environment & tooling

- **Runtime:** PHP 8.1+ on Apache (XAMPP locally, Hostinger in production). MariaDB/MySQL.
- **Working dir is a live web root** (`\\datacenter\htdocs\studium-cat`). Editing a `.php`
  file changes what the running site serves — there is no compile step.
- **No package manager for app code.** PHPMailer is vendored under `admin/PHPMailer/` and
  `login/PHPMailer/`. `vendor/` and `node_modules/` are gitignored and not used by app code.
- **No automated test suite.** Verification is manual — see the checklists in `AGENTS.md`.
  Numerous `db_audit_*.php` / `check_*.php` / `test_db.php` scripts exist for ad-hoc DB
  inspection during development; they are not part of any test runner.

### Deployment

- Push to the **`dev`** branch → GitHub Action (`.github/workflows/deploy-dev.yml`)
  FTP-deploys the whole tree to `dev/` on Hostinger (`https://dev.studium.cat/`).
- `main` is the production branch. There is no auto-deploy workflow for `main` in-repo.
- `.sql`, `.env*`, `vendor/`, `node_modules/`, `.git*` are excluded from deploys.

## Bootstrap & architecture contracts

**Read `AGENTS.md` before touching the NGN module** — it is the authoritative
implementation guide with file-level fixes, coding standards (DB-001…CSRF-001), and
verification checklists.

Core rules that apply project-wide:

- **`config.php` is the single bootstrap.** Every PHP entry file starts with
  `require_once '<relative>/config.php';` (adjust `../` depth per directory). It:
  - auto-detects environment (`IS_PRODUCTION`, `APP_ENV`) from `HTTP_HOST`,
  - sets DB credentials, calls `session_start()`, computes `BASE_URL`/`BASE_PATH`,
  - loads `core/Database.php` and `core/ScoringEngine.php`,
  - auto-creates/migrates required tables on every request (idempotent
    `CREATE TABLE IF NOT EXISTS` + `_addColIfMissing()` column adds + alias views).
  - **Never call `session_start()` yourself.** Never duplicate its `CREATE TABLE` blocks
    in other files (that causes schema drift).
- **`db()` is the DB singleton** (`core/Database.php`). Use it for all data access:
  - `db()->fetchOne($sql, $params)` → `?array`
  - `db()->fetchAll($sql, $params)` → `array`
  - `db()->execute($sql, $params)` → `bool` (INSERT/UPDATE/DELETE)
  - `db()->query($sql, $params)` → `mysqli_result|false`
  - **All parameters must be `?` placeholders bound as prepared statements.** No raw string
    interpolation of user input into SQL. Do not write new `mysqli_query()` /
    `mysqli_real_escape_string()` calls (much legacy code still does — migrate when you touch it).
  - `$con` (raw mysqli handle) and `getQuizConnection()` exist for legacy support only.
- **`core/ScoringEngine.php` owns all scoring.** Call `ScoringEngine::score($type, $user, $correct)`.
  - `includes/scoring.php` (and `student/dashboard/ngn/includes/scoring.php`) is **deprecated** —
    do not add scoring logic there.
  - Scoring rules differ by type: SATA is **strict** (all-or-nothing); MMR/MPR/MSR/highlight/
    dragndrop/dropdown give **partial credit**; bowtie caps per section (conditions ≤2,
    actions ≤2, parameters ≤1, total ≤5); traditional/MC is binary.
  - CAT/IRT helpers also live here: `updateTheta()`, `calculateStandardError()`,
    `checkStoppingRule()` (95% CI around passing logit 0.0; theta bounded ±4).
- **`core/Security.php` is NOT auto-loaded.** `require_once` it explicitly anywhere you use
  `generateCsrfToken()` / `validateCsrfToken()` / `sanitize()` / `csrfField()`.
- **`includes/`** holds helper libraries (`auth_helpers.php`, `db_helpers.php`,
  `ui_helpers.php`) — also not auto-loaded; include as needed. Note some auth/security
  helpers (`isLoggedIn`, `requireLogin`, `redirect`, `post`) exist in **both** `config.php`
  and `includes/`/`core/` with slightly different behavior — they are guarded by
  `function_exists()`, so the first one loaded wins. Be aware of which file is included.

## Coding standards (enforced — see `AGENTS.md` table)

| Area | Rule |
|------|------|
| SQL | Prepared statements only; bind every external value. Use `db()`, not raw `mysqli_*`. |
| Ownership | Endpoints taking an identifier (`examTaken`, `question_uid`, ids) must verify it belongs to `$_SESSION['user_id']` before reading/writing. |
| Transactions | Wrap multi-step writes in `begin_transaction()`/`commit()`/`rollback()`. |
| Output | Escape every DB/user value rendered into HTML: `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`. |
| JS in HTML | No JSON in `onclick`; use `data-*` + `JSON.parse`. No `eval()` — regex-whitelisted `Function()` for arithmetic only. |
| Includes | `require_once '<path>/config.php'` at top of every entry file; never manual `session_start()`. |
| CSRF | State-mutating POST endpoints validate `X-CSRF-Token` via `validateCsrfToken()`. |

## Database notes

- Single database (`u436962267_studium`) for both main and quiz connections in every env.
- **Credentials are hardcoded in `config.php`** per environment (local = `root`/no password;
  production = Hostinger creds). Do not commit changes that leak or alter prod creds casually.
- **Question-type tables:** `traditional`, `sata`, `mpr`, `mmr`, `btq` (bowtie), `dragndrop`,
  `dropdown`, `highlight`, `column`. The live Hostinger DB uses different physical names
  (`mcq`, `dropdown_questions`, `ngncolumn`); `config.php` creates **alias VIEWs** so code
  can use the canonical names. Adaptive selection needs a `difficulty_logit` column on each.
- **Result tables:** `temporary_exam_result` (in-progress NGN practice),
  `exam_results` (permanent NGN practice), `exammoderesults` (permanent 150-item Exam Mode,
  kept fully isolated from NGN practice — see `EXAM_MODE_ENHANCEMENT_PLAN.md`).
- **State table:** `temporary_exam_state` (per-student CAT state: theta, SEM, streaks,
  `exam_mode` `'ngn'` vs `'exam'`, pause/resume).
- `*.sql` dumps exist at repo root for reference but are gitignored from deploys.

## NGN module map (`student/dashboard/ngn/`)

- `index.php` — exam runner UI (question rendering, timer, calculator).
- `api/fetch_question.php` — adaptive next-question selection (validates type against a
  whitelist `$tableMap`).
- `api/submit_answer.php` — scores via `ScoringEngine`, updates CAT state, persists results.
- `history_details.php` / `result.php` / `ngn_exam_result.php` — review & reporting.
- `state_manager.php`, `save_history.php`, `submit_exam.php`, `start_filtered_exam.php`,
  `cancel_exam.php` — exam lifecycle.
- Per-type renderers in `bowtie/ dragndrop/ dropdown/ highlight/ mc/ mmr/ mpr/ sata/ traditional/`.
- Module docs: `NGN_SYSTEM_GUIDE.md`, `NGN_CHANGES_DOCUMENTATION.md`.

## Conventions & gotchas

- **Directory names contain spaces** (e.g. `admin/manage question/`, `table css/`,
  `student/dashboard/my note/`). Quote paths in shell commands.
- Frontend is server-rendered PHP + vanilla JS. CSS/JS are mostly hand-written per-page or
  in scattered `*.css`/`*.js` folders; Bootstrap is loaded via CDN in some admin pages but
  the public login pages are deliberately Bootstrap-free. jQuery + DataTables power admin tables.
- Output buffering in `config.php` swallows PHP notices during init so they don't corrupt
  JSON API responses — keep API endpoints from emitting stray output before JSON.
- Passwords: new accounts use `password_hash`/`password_verify`; legacy plaintext and md5
  hashes are still accepted on login for backward compatibility.
- Many `db_audit_*.php`, `check_*.php`, `migrate_*.php`, and `index(BAKAL).php` /
  `index(80plusBRONZE).php` style files are dev scratch/backups — not live routes. Don't
  treat them as canonical.

## Key reference docs

- `AGENTS.md` — authoritative NGN fix guide + coding standards + verification checklists.
- `EXAM_MODE_ENHANCEMENT_PLAN.md` — 150-item adaptive Exam Mode design.
- `NGN-HARDENING-PLAN.md`, `NGN-CHANGES-SUMMARY.md`, `may16plan.md` — historical plans.
- `NGN_NCSBN_Question_Bank_Instructions.pdf` — question bank / scoring spec source of truth.
