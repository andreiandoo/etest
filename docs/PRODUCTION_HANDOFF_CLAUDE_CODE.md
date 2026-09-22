# e-test.ro — Production Readiness Handoff for Claude Code

## 1. Scop

Acest document este brief-ul de execuție pentru a duce e-test.ro de la codul M1–M7 finalizat la o versiune testabilă direct pe producție.

Nu există mediu de staging și nu trebuie creat unul.

Repo:

    github.com/andreiandoo/etest

Baseline M1–M7 merge-uit în main:

    68ac13c57fa2fee5023ffc74b5801550f2dbd055

Stack existent:

- Laravel 13
- Livewire 4
- Blade
- Tailwind CSS 4
- PostgreSQL
- Redis
- Nginx
- PHP 8.4
- queue workers
- Laravel scheduler
- M1–M7 complete: foundation, test engine, content admin, SEO, user layer, monetization, white-label, API v1.

Ținta este:

    https://e-test.ro

VPS-ul există deja. DNS-ul domeniului va fi configurat separat de owner.

---

# 2. Reguli obligatorii

## 2.1 Fără staging

Nu crea:

- staging.e-test.ro;
- staging database;
- staging environment;
- duplicate deployment.

Se lucrează direct cu infrastructura de producție.

În perioada de verificare pre-launch, aplicația poate fi protejată de indexare prin Nginx:

    X-Robots-Tag: noindex, nofollow

Header-ul se elimină numai când owner-ul confirmă publicarea.

Nu folosi robots.txt Disallow: / ca înlocuitor pentru noindex, deoarece motoarele de căutare trebuie să poată accesa răspunsul pentru a vedea header-ul noindex.

## 2.2 Siguranța bazei de date

În producție sunt INTERZISE:

    php artisan migrate:fresh
    php artisan migrate:refresh
    php artisan migrate:reset
    php artisan db:wipe

Folosește exclusiv:

    php artisan migrate --force

Înainte de orice migrare ulterioară primei instalări trebuie făcut backup PostgreSQL.

Nu șterge baza de date de producție pentru a rezolva o problemă de deploy.

## 2.3 APP_KEY

APP_KEY se generează o singură dată la instalarea inițială.

Nu roti APP_KEY după ce aplicația este în utilizare.

Schimbarea APP_KEY poate invalida date criptate și signed URLs, inclusiv linkurile newsletter.

## 2.4 Secrete

Nu commit-ui:

- .env;
- parole DB;
- parole SMTP;
- Google Client Secret;
- API keys;
- SSH keys;
- tokenuri GitHub.

Fișierele example nu trebuie să conțină secrete reale.

## 2.5 Conținut

Nu inventa întrebări DRPCIV, medicale, juridice sau pentru alte examene doar pentru a umple aplicația.

Pentru smoke testing poate exista temporar conținut QA clar marcat ca:

    QA Sandbox

Acesta trebuie șters înainte de publicarea reală.

---

# 3. Definition of Done

Versiunea este TESTABILĂ când toate condițiile de mai jos sunt îndeplinite:

- https://e-test.ro răspunde prin HTTPS;
- /up răspunde HTTP 200;
- APP_ENV=production;
- APP_DEBUG=false;
- PostgreSQL funcționează;
- toate migrations sunt aplicate;
- Redis funcționează pentru cache/session/queue;
- queue worker rulează sub Supervisor;
- scheduler rulează la fiecare minut;
- assets Vite sunt build-uite;
- Nginx servește numai directorul public/;
- înregistrarea și login-ul funcționează;
- există cel puțin un admin;
- admin-ul poate crea verticală/taxonomie/test/întrebare;
- un utilizator poate rula și finaliza un test;
- results, XP și history funcționează;
- un import CSV/XLSX poate fi procesat de queue;
- newsletter-ul poate trimite email de confirmare;
- sitemap.xml răspunde;
- API /api/v1/status funcționează cu o cheie validă;
- backup-ul PostgreSQL este automat;
- un backup a fost verificat prin restore într-o bază temporară;
- firewall-ul nu expune PostgreSQL sau Redis;
- există un deploy script repetabil;
- există un smoke-test script;
- înainte de publicarea publică este eliminat header-ul global noindex.

Când toate acestea sunt bifate, STOP. Nu începe redesign, noi features sau import masiv de conținut în cadrul acestui task.

---

# 4. Faza P0 — Audit VPS

Înainte de instalare, afișează și documentează:

    cat /etc/os-release
    uname -a
    php -v
    nginx -v
    psql --version
    redis-server --version
    node --version
    npm --version
    composer --version
    git --version
    df -h
    free -h

Verifică:

- distribuția și versiunea;
- RAM;
- spațiu liber;
- CPU;
- existența Nginx;
- versiunea PHP;
- PostgreSQL;
- Redis;
- Supervisor;
- Node.js;
- Composer.

Nu reinstala servicii funcționale doar pentru a uniformiza versiuni.

Versiuni țintă:

- PHP 8.4;
- PostgreSQL 16+;
- Redis 7+;
- Node.js 22;
- Nginx recent;
- Composer 2.

---

# 5. Faza P0 — Pachete runtime

Instalează numai ce lipsește.

PHP extensions necesare:

- mbstring
- pdo_pgsql
- pgsql
- gd
- zip
- xml
- xmlreader
- xmlwriter
- ctype
- dom
- fileinfo
- iconv
- simplexml
- zlib
- curl
- intl
- opcache

Verificare:

    php -m

Pentru upload-uri XLSX/CSV și procesare în queue setează valori rezonabile în PHP:

    memory_limit = 512M
    upload_max_filesize = 32M
    post_max_size = 32M
    max_execution_time = 60

Pentru worker poate fi folosită o limită CLI mai mare dacă importurile necesită:

    php -d memory_limit=1024M artisan queue:work ...

Nu mări limitele arbitrar dacă nu este necesar.

---

# 6. Faza P0 — User și structură aplicație

Recomandare:

    /var/www/etest

Rulează aplicația sub un user dedicat, de exemplu:

    etest

Nu rula Composer, npm sau Laravel permanent ca root.

Exemplu:

    sudo adduser --disabled-password --gecos "" etest
    sudo mkdir -p /var/www/etest
    sudo chown -R etest:www-data /var/www/etest

Clonează main:

    sudo -u etest git clone https://github.com/andreiandoo/etest.git /var/www/etest
    cd /var/www/etest
    sudo -u etest git checkout main

Verifică:

    git status
    git log -1 --oneline

Baseline-ul trebuie să includă M1–M7.

---

# 7. Faza P0 — PostgreSQL: creare DB

Baza de date NU există încă și trebuie creată.

## 7.1 PostgreSQL local only

Verifică postgresql.conf.

Pentru deployment-ul actual DB trebuie să fie accesibilă doar local.

Recomandat:

    listen_addresses = 'localhost'

Portul 5432 NU trebuie deschis în firewall.

Repornește PostgreSQL numai dacă ai modificat configurația.

## 7.2 Creează user și DB

Intră în psql:

    sudo -u postgres psql

Execută, înlocuind parola placeholder cu o parolă lungă generată local:

    CREATE ROLE etest LOGIN PASSWORD '<STRONG_RANDOM_PASSWORD>';
    CREATE DATABASE etest OWNER etest ENCODING 'UTF8';
    REVOKE ALL ON DATABASE etest FROM PUBLIC;
    GRANT CONNECT ON DATABASE etest TO etest;

    \c etest

    REVOKE CREATE ON SCHEMA public FROM PUBLIC;
    GRANT ALL ON SCHEMA public TO etest;

    \q

Nu introduce parola DB în documentație sau Git.

## 7.3 Verificare conexiune

    psql -h 127.0.0.1 -U etest -d etest -c "SELECT current_database(), current_user;"

Rezultatul trebuie să arate:

    etest | etest

## 7.4 Config Laravel

În .env:

    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=etest
    DB_USERNAME=etest
    DB_PASSWORD=<SECRET>

---

# 8. Faza P0 — Redis

Redis trebuie folosit pentru:

- sessions;
- cache;
- queues.

În producție:

    bind 127.0.0.1 ::1
    protected-mode yes

Portul 6379 NU trebuie expus public.

Verificare:

    redis-cli ping

Rezultat:

    PONG

.env:

    SESSION_DRIVER=redis
    CACHE_STORE=redis
    QUEUE_CONNECTION=redis
    REDIS_CLIENT=predis
    REDIS_HOST=127.0.0.1
    REDIS_PORT=6379

---

# 9. Faza P0 — Production environment

Creează:

    /var/www/etest/.env

Pornind din .env.example, dar configurat pentru producție.

Minimum:

    APP_NAME="e-test.ro"
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://e-test.ro

    APP_LOCALE=ro
    APP_FALLBACK_LOCALE=ro
    APP_FAKER_LOCALE=ro_RO

    LOG_CHANNEL=stack
    LOG_LEVEL=info

    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=etest
    DB_USERNAME=etest
    DB_PASSWORD=<SECRET>

    SESSION_DRIVER=redis
    CACHE_STORE=redis
    QUEUE_CONNECTION=redis

    REDIS_CLIENT=predis
    REDIS_HOST=127.0.0.1
    REDIS_PASSWORD=null
    REDIS_PORT=6379

    MAIL_MAILER=smtp
    MAIL_HOST=<SMTP_HOST>
    MAIL_PORT=<SMTP_PORT>
    MAIL_USERNAME=<SMTP_USERNAME>
    MAIL_PASSWORD=<SMTP_PASSWORD>
    MAIL_ENCRYPTION=tls
    MAIL_FROM_ADDRESS=noreply@e-test.ro
    MAIL_FROM_NAME="e-test.ro"

    GOOGLE_CLIENT_ID=<OPTIONAL_UNTIL_CONFIGURED>
    GOOGLE_CLIENT_SECRET=<OPTIONAL_UNTIL_CONFIGURED>
    GOOGLE_REDIRECT_URI=https://e-test.ro/auth/google/callback

    VITE_APP_NAME="e-test.ro"

Setează permisiuni restrictive:

    sudo chown etest:www-data /var/www/etest/.env
    sudo chmod 640 /var/www/etest/.env

## APP_KEY

Doar la prima instalare:

    cd /var/www/etest
    sudo -u etest php artisan key:generate

După aceea APP_KEY devine permanent.

---

# 10. Faza P0 — Mail

Configurația curentă example folosește MAIL_MAILER=log. Acest lucru NU este suficient pentru producție.

Configurează SMTP real înainte de testarea:

- newsletter double opt-in;
- email-uri tranzacționale.

Verifică manual trimiterea unui email.

Dacă SMTP nu este încă disponibil, aplicația poate fi testată parțial, dar Definition of Done NU este îndeplinită până când confirmarea newsletter nu ajunge efectiv pe email.

---

# 11. Faza P0 — Google OAuth

Redirect URI de producție:

    https://e-test.ro/auth/google/callback

Acest URI trebuie adăugat în configurația OAuth Google.

Dacă credentialele Google nu sunt încă configurate:

- nu inventa credentiale;
- verifică dacă UI-ul afișează un buton care ar conduce utilizatorul într-un flow nefuncțional;
- dacă este necesar, implementează o protecție mică astfel încât Google Login să fie afișat numai dacă există configurația validă.

Email/password login trebuie să funcționeze independent.

---

# 12. Faza P0 — Install dependencies și build

Pe producție:

    cd /var/www/etest

    sudo -u etest composer install \
      --no-dev \
      --prefer-dist \
      --optimize-autoloader \
      --no-interaction

Dacă package-lock.json există:

    sudo -u etest npm ci

Altfel:

    sudo -u etest npm install

Apoi:

    sudo -u etest npm run build

Nu folosi npm run dev în producție.

---

# 13. Faza P0 — Laravel permissions

Directorarele care trebuie să fie writable:

    storage
    bootstrap/cache

Exemplu:

    sudo chown -R etest:www-data storage bootstrap/cache
    sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
    sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;

Nu pune chmod 777 recursiv pe aplicație.

---

# 14. Faza P0 — Prima migrare

Verifică mai întâi conexiunile:

    sudo -u etest php artisan about
    sudo -u etest php artisan config:clear

Rulează:

    sudo -u etest php artisan migrate --force

Apoi:

    sudo -u etest php artisan migrate:status

Toate migrations M1–M7 trebuie să apară ca Ran.

NU folosi migrate:fresh.

---

# 15. Faza P0 — Nginx

Claude Code trebuie să creeze în repo un template:

    deploy/nginx/e-test.ro.conf

Configurația trebuie să:

- folosească root /var/www/etest/public;
- trimită PHP către PHP 8.4 FPM;
- folosească try_files pentru Laravel;
- refuze fișiere hidden;
- nu expună .env;
- permită upload de cel puțin 32 MB;
- seteze security headers rezonabile;
- cache-uiască assets versionate;
- păstreze Host header-ul, necesar pentru white-label;
- nu seteze cache agresiv pentru HTML/API.

Config minimal conceptual:

    server {
        listen 80;
        server_name e-test.ro www.e-test.ro;

        root /var/www/etest/public;
        index index.php;

        client_max_body_size 32m;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }
    }

Adaptează socket-ul PHP la distro dacă diferă.

După configurare:

    sudo nginx -t
    sudo systemctl reload nginx

---

# 16. Faza P0 — HTTPS

După ce DNS-ul e-test.ro indică VPS-ul:

    dig +short e-test.ro

Trebuie să returneze IP-ul corect.

Provision TLS cu Certbot sau mecanismul deja folosit pe VPS.

Exemplu:

    sudo certbot --nginx -d e-test.ro -d www.e-test.ro

Dacă www nu este configurat în DNS, nu îl include până nu există.

Canonical host principal trebuie să rămână:

    https://e-test.ro

Redirecționează HTTP către HTTPS.

Dacă www este activ, redirecționează-l permanent către e-test.ro.

Verifică renew:

    sudo certbot renew --dry-run

---

# 17. Faza P0 — Pre-launch noindex pe producție

Pentru că nu există staging, în perioada de QA adaugă TEMPORAR în Nginx:

    add_header X-Robots-Tag "noindex, nofollow" always;

Acest header este separat de middleware-ul noindex deja folosit pentru paginile private.

Verifică:

    curl -I https://e-test.ro

Trebuie să includă:

    X-Robots-Tag: noindex, nofollow

La go-live acest header global trebuie eliminat.

Nu modifica permanent regulile SEO din Laravel pentru această etapă.

---

# 18. Faza P0 — Queue worker

Queue-ul este obligatoriu pentru:

- content imports;
- newsletter confirmation mail;
- alte jobs async.

Claude Code trebuie să creeze:

    deploy/supervisor/etest-worker.conf

Exemplu:

    [program:etest-worker]
    process_name=%(program_name)s_%(process_num)02d
    command=/usr/bin/php -d memory_limit=1024M /var/www/etest/artisan queue:work redis --sleep=3 --tries=3 --timeout=300 --max-time=3600
    directory=/var/www/etest
    user=etest
    numprocs=2
    autostart=true
    autorestart=true
    stopasgroup=true
    killasgroup=true
    redirect_stderr=true
    stdout_logfile=/var/log/etest-worker.log
    stdout_logfile_maxbytes=20MB
    stdout_logfile_backups=5
    stopwaitsecs=360

Aplicare:

    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl status

Trebuie să fie RUNNING.

După fiecare deploy:

    php artisan queue:restart

---

# 19. Faza P0 — Scheduler

Creează:

    deploy/cron/etest-scheduler

Conținut conceptual:

    * * * * * etest cd /var/www/etest && /usr/bin/php artisan schedule:run >> /dev/null 2>&1

Instalează-l în /etc/cron.d/etest.

Verifică manual:

    sudo -u etest php artisan schedule:list
    sudo -u etest php artisan schedule:run

---

# 20. Faza P0 — Health check

Laravel are deja health endpoint:

    /up

Verificare locală:

    curl -I http://127.0.0.1/up -H "Host: e-test.ro"

Verificare externă:

    curl -I https://e-test.ro/up

Trebuie să fie HTTP 200.

Nu transforma /up într-un endpoint care afișează secrete, config sau detalii DB.

---

# 21. Faza P0 — Backup PostgreSQL

Claude Code trebuie să creeze:

    deploy/scripts/backup-postgres.sh

Cerințe:

- set -euo pipefail;
- umask 077;
- pg_dump custom format;
- timestamp UTC;
- verificare că fișierul nu este gol;
- retention automat;
- exit non-zero la eroare;
- fără parola DB hardcoded.

Director recomandat:

    /var/backups/etest/postgres

Exemplu logic:

    sudo mkdir -p /var/backups/etest/postgres
    sudo chmod 700 /var/backups/etest/postgres

Backup:

    sudo -u postgres pg_dump \
      --format=custom \
      --no-owner \
      --no-acl \
      etest \
      --file=/var/backups/etest/postgres/etest_YYYYMMDD_HHMMSS.dump

Retention recomandat pentru început:

- daily: 14 zile;
- opțional weekly copies: 8 săptămâni.

Cron recomandat:

    15 3 * * * root /var/www/etest/deploy/scripts/backup-postgres.sh

Orice deploy care rulează migrations trebuie să invoce backup-ul înainte de migrate.

---

# 22. Faza P0 — Restore verification

Un backup ne-testat nu este suficient.

Claude Code trebuie să creeze:

    deploy/scripts/verify-postgres-restore.sh

Scriptul trebuie să restaureze ultimul backup într-o DB TEMPORARĂ, nu peste producție.

Exemplu flow:

    sudo -u postgres createdb etest_restore_verify

    sudo -u postgres pg_restore \
      --no-owner \
      --no-acl \
      --dbname=etest_restore_verify \
      /var/backups/etest/postgres/<backup>.dump

    sudo -u postgres psql \
      -d etest_restore_verify \
      -c "\dt"

    sudo -u postgres dropdb etest_restore_verify

Scriptul trebuie să facă cleanup și dacă restore-ul eșuează.

Nu executa niciodată testul de restore peste DB etest.

---

# 23. Faza P0 — Deploy script repetabil

Creează:

    deploy/scripts/deploy.sh

Scop: deploy manual repetabil din main.

Reguli:

- set -euo pipefail;
- refuză să ruleze dacă branch-ul țintă nu este main;
- verifică existența .env;
- nu modifică APP_KEY;
- face backup înainte de migrations;
- nu execută migrate:fresh/reset;
- oprește maintenance mode chiar dacă un pas eșuează, prin trap;
- restartează queue worker după deploy.

Flow recomandat:

    git fetch origin
    git checkout main
    git reset --hard origin/main

    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

    npm ci
    npm run build

    php artisan down --retry=60

    ./deploy/scripts/backup-postgres.sh

    php artisan migrate --force
    php artisan optimize:clear
    php artisan optimize
    php artisan queue:restart

    php artisan up

Pentru primul deploy, backup-ul poate detecta DB goală și crea totuși un dump valid sau poate marca explicit FIRST_DEPLOY.

Nu ascunde erorile de migrate.

---

# 24. Faza P0 — Smoke-test script

Creează:

    deploy/scripts/smoke-test.sh

Scriptul NU trebuie să scrie date sensibile.

Minimum:

    GET /
    GET /up
    GET /sitemap.xml
    GET /login
    GET /register

Validări:

- status 200;
- HTTPS;
- fără stack trace;
- fără APP_DEBUG output;
- /up = 200;
- sitemap XML valid;
- header global noindex prezent cât timp QA-ul nu este închis.

API:

- dacă ETEST_SMOKE_API_KEY este setat în shell, testează:
  /api/v1/status
- nu hardcoda cheia în script.

---

# 25. Faza P0 — Firewall și servicii locale

Dacă VPS-ul folosește UFW, regula minimă:

    sudo ufw allow OpenSSH
    sudo ufw allow "Nginx Full"
    sudo ufw enable

NU deschide:

    5432
    6379

Verifică:

    sudo ss -lntp

PostgreSQL și Redis trebuie să asculte local, nu pe 0.0.0.0 public.

---

# 26. Faza P0 — Production readiness Artisan command

Recomand implementarea unui command read-only:

    php artisan production:check

Acesta trebuie să verifice fără a afișa secrete:

- APP_ENV === production;
- APP_DEBUG === false;
- APP_URL folosește https;
- DB connection OK;
- Redis cache connection OK;
- Redis queue connection OK;
- storage writable;
- bootstrap/cache writable;
- existența build manifest Vite;
- migrations pending;
- MAIL_MAILER nu este log;
- QUEUE_CONNECTION este redis;
- SESSION_DRIVER este redis;
- CACHE_STORE este redis.

Output:

    PASS / FAIL

Exit code:

- 0 dacă toate verificările critice trec;
- 1 dacă există o eroare critică.

Scrie teste Pest pentru command.

Nu afișa DB_PASSWORD, APP_KEY sau alte secrets.

---

# 27. Faza P0 — First admin

După ce site-ul este accesibil:

1. Creează un cont normal prin /register.
2. Pe VPS:

    cd /var/www/etest
    sudo -u etest php artisan admin:grant <EMAIL>

3. Logout/login.
4. Verifică accesul la:

    /admin

Nu seta is_admin prin request/browser.

---

# 28. Faza P1 — QA Sandbox

Pentru verificarea funcțională este permis un set temporar de date cu denumire explicită:

    Verticală: QA Sandbox
    Test: QA Smoke Test

Conține doar date fictive tehnice și NU pretinde că reproduce un examen real.

Folosește-l pentru:

- single choice;
- multiple choice;
- true/false;
- numeric;
- short text;
- matching;
- ordering;
- practice;
- exam;
- timer;
- scoring;
- review;
- reporting.

După terminarea QA:

- șterge QA Sandbox;
- verifică să nu apară în sitemap;
- nu importa fake questions în verticale reale.

---

# 29. Faza P1 — Smoke test manual end-to-end

## Auth

- register;
- login;
- logout;
- reset/flow disponibil dacă este implementat;
- Google OAuth numai dacă este configurat;
- user neautentificat nu poate porni test.

## Content admin

- create vertical;
- create taxonomy;
- create test;
- create questions;
- draft -> review -> published;
- source fields;
- SEO fields.

## Test engine

- start;
- resume;
- answer;
- timer;
- finish;
- results;
- explanations;
- reporting question issue.

## User layer

- dashboard;
- history;
- favorite;
- XP;
- achievements;
- streak;
- leaderboard opt-in.

## Imports

Pregătește un fișier mic CSV și unul XLSX.

Testează:

- upload;
- queued import;
- worker preia job-ul;
- status import;
- row error handling.

## Monetization

Testează cu campanii QA nepublice sau apoi șterse:

- sponsor;
- lead form;
- affiliate redirect;
- newsletter double opt-in;
- unsubscribe.

## White-label

Nu este necesar un domeniu white-label real pentru prima versiune testabilă.

Testează cel puțin:

- admin white-label se încarcă;
- tenant/domain poate fi configurat;
- tenant isolation este deja acoperită de automated tests.

## API

Din /admin/api:

- create API client;
- issue key;
- salvează cheia într-un password manager;
- GET /api/v1/status;
- scope enforcement;
- quota enforcement;
- revoke key;
- revoked key => 401.

Nu utiliza cheia de test în frontend JavaScript.

---

# 30. Faza P1 — Mail / newsletter end-to-end

Testează cu o adresă reală controlată:

1. subscribe;
2. status DB = pending;
3. email primit;
4. click confirmation;
5. status = active;
6. unsubscribe;
7. status = unsubscribed;
8. confirmation URL vechi nu reactivează abonarea.

Verifică queue worker dacă emailul nu pleacă.

---

# 31. Faza P1 — SEO technical check

Cât timp QA este activ:

    X-Robots-Tag: noindex, nofollow

Verifică:

- canonical pe homepage;
- canonical pe verticală;
- canonical pe taxonomy;
- canonical pe test;
- sitemap index;
- vertical sitemap;
- taxonomy sitemap;
- test sitemap;
- private pages noindex;
- /go noindex;
- /newsletter action pages noindex.

Înainte de launch:

1. șterge QA Sandbox;
2. elimină header-ul GLOBAL noindex din Nginx;
3. păstrează middleware-ul noindex Laravel pentru suprafețele private;
4. verifică robots.txt;
5. verifică sitemap.xml;
6. abia apoi permite indexarea.

---

# 32. Faza P1 — Logs și observability

Verifică:

    storage/logs/laravel.log
    /var/log/nginx/access.log
    /var/log/nginx/error.log
    /var/log/etest-worker.log

Configurează logrotate dacă nu există.

Nu loga:

- parole;
- APP_KEY;
- DB_PASSWORD;
- API plaintext keys;
- Google secret.

Recomandat, dar nu blocker pentru prima versiune testabilă:

- uptime monitor pe https://e-test.ro/up;
- error tracking extern;
- disk-space alert;
- backup failure alert.

---

# 33. Faza P1 — Comenzi de diagnostic

Laravel:

    php artisan about
    php artisan route:list
    php artisan migrate:status
    php artisan schedule:list
    php artisan queue:monitor
    php artisan production:check

PostgreSQL:

    sudo -u postgres psql -d etest -c "SELECT version();"
    sudo -u postgres psql -d etest -c "\dt"

Redis:

    redis-cli ping

Supervisor:

    sudo supervisorctl status

Nginx:

    sudo nginx -t

TLS:

    curl -I https://e-test.ro

Health:

    curl -I https://e-test.ro/up

---

# 34. Repository artifacts to create

Claude Code trebuie să lase în repo, fără secrets:

    deploy/
      env.production.example
      nginx/
        e-test.ro.conf
      supervisor/
        etest-worker.conf
      cron/
        etest-scheduler
      scripts/
        deploy.sh
        backup-postgres.sh
        verify-postgres-restore.sh
        smoke-test.sh

    docs/
      PRODUCTION_RUNBOOK.md

Și, dacă este implementat:

    app/Console/Commands/ProductionCheck.php
    tests/Feature/ProductionCheckTest.php

Toate scripturile shell:

- set -euo pipefail;
- comentarii suficiente;
- fără secrete hardcoded;
- shellcheck-friendly pe cât posibil;
- executable bit în Git unde este relevant.

---

# 35. env.production.example

Creează un template fără secrete cu toate variabilele necesare.

Trebuie să conțină explicit:

- APP_ENV=production;
- APP_DEBUG=false;
- APP_URL=https://e-test.ro;
- PostgreSQL;
- Redis;
- SMTP;
- Google OAuth placeholders;
- queue/cache/session Redis.

Nu include parole exemplu care pot fi confundate cu parole reale.

---

# 36. Deploy validation înainte de orice update ulterior

Pentru orice deploy ulterior:

1. GitHub CI verde pe main.
2. Backup DB.
3. Verificare backup file.
4. Deploy script.
5. migrate --force.
6. queue restart.
7. /up.
8. smoke-test.sh.
9. verificare logs.

Dacă migrate eșuează:

- nu executa migrate:fresh;
- păstrează site-ul în maintenance mode dacă schema este inconsistentă;
- investighează migration;
- restore DB doar dacă este necesar și numai după evaluare.

---

# 37. Rollback

Pentru cod fără migration incompatibilă:

    git log --oneline
    git reset --hard <LAST_GOOD_SHA>
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
    npm ci
    npm run build
    php artisan optimize
    php artisan queue:restart

Pentru rollback care implică DB:

- NU presupune că migrations down sunt sigure;
- folosește backup-ul făcut înainte de deploy;
- pune site-ul în maintenance mode;
- restaurează numai după ce impactul este înțeles.

Documentează fiecare rollback real în PRODUCTION_RUNBOOK.md.

---

# 38. Ce NU trebuie făcut acum

Nu implementa în acest task:

- noi verticale;
- generare AI de întrebări;
- import masiv DRPCIV;
- redesign major;
- aplicație mobilă;
- billing/subscriptions;
- Elasticsearch;
- Kubernetes;
- microservicii;
- staging;
- CI/CD auto-deploy cu SSH secrets;
- multi-database tenancy;
- analytics avansat.

Acestea nu sunt necesare pentru versiunea testabilă.

---

# 39. După ce versiunea este testabilă

Următoarea etapă va fi separată:

    Content Launch

Ordine recomandată:

1. inventariere finală Auto / DRPCIV;
2. validare surse;
3. import conținut real;
4. QA editorial;
5. publicare pilot;
6. eliminare global noindex;
7. Search Console;
8. apoi următoarele verticale.

Nu începe acest pas până când infrastructura de producție și smoke tests nu sunt complet verzi.

---

# 40. Raport final cerut de la Claude Code

La final, Claude Code trebuie să livreze un raport scurt care conține:

## Server

- OS;
- PHP;
- PostgreSQL;
- Redis;
- Nginx;
- Node;
- Supervisor.

## Database

- DB creată;
- migration count;
- backup path;
- ultimul backup;
- restore verification PASS/FAIL.

## Application

- deployed commit SHA;
- APP_ENV;
- APP_DEBUG;
- health check;
- worker status;
- scheduler status.

## External services

- SMTP PASS/FAIL;
- Google OAuth PASS/FAIL / NOT CONFIGURED;
- TLS PASS/FAIL.

## Smoke tests

Pentru fiecare:

    PASS / FAIL

- homepage;
- register/login;
- admin;
- test engine;
- history/XP;
- import queue;
- newsletter;
- sitemap;
- API;
- backup/restore.

## Remaining blockers

Listă explicită.

Dacă nu există:

    Production testable version: READY

---

# 41. STOP condition

Task-ul este terminat când:

    Production testable version: READY

și toate elementele critice din Definition of Done sunt PASS.

Nu continua autonom cu content launch sau feature development după acest punct.
