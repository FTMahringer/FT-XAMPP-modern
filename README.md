# 🚀 FT-XAMPP (Next Gen)

[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://www.docker.com/)
[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7-black?logo=symfony)](https://symfony.com/)
[![Vue.js](https://img.shields.io/badge/Vue-3-42b883?logo=vue.js)](https://vuejs.org/)
[![MariaDB](https://img.shields.io/badge/MariaDB-Latest-003545?logo=mariadb)](https://mariadb.org/)
[![Redis](https://img.shields.io/badge/Redis-Cache-red?logo=redis)](https://redis.io/)

Ein modernes **XAMPP-ähnliches Development-Setup** mit **Docker Compose**, optimiert für **PHP / Symfony**, **Vue 3 / Vite**, **MariaDB**, **Redis** und mehr.  
Alle Projekte liegen wie gewohnt im `htdocs`-Ordner – ganz wie bei XAMPP, nur in modern.  

---

## ✨ Features
- 📂 **htdocs-Projekte** – jedes Projekt im `htdocs`-Ordner ist sofort erreichbar
- ⚙️ **Symfony Backend** (API) + **Vue Frontend** (SPA) Support
- 🐘 **PHP-FPM** mit eigener `php.ini`
- 🌐 **NGINX** als Webserver + Reverse Proxy
- 🛢 **MariaDB** als Datenbank
- 🧩 **Redis** für Cache / Sessions
- 📊 **phpMyAdmin** für DB-Verwaltung
- 🖥 **Dashboard** (Vue + Symfony) – Übersicht über alle Projekte
- 🐳 Komplett **Docker Compose** basiert

---

## 📦 Installation

1. **Repository klonen**
   ```bash
   git clone https://github.com/<dein-user>/FT-XAMPP.git
   cd FT-XAMPP
   ```

2. **.env Datei anpassen**  
   Kopiere `.env.example` → `.env` und trage deine Variablen ein (Ports, Container-Namen, Passwörter).

3. **Container starten**
   ```bash
   docker compose up -d --build
   ```

4. **Aufrufen im Browser**
   - Dashboard → [http://localhost/](http://localhost/)
   - phpMyAdmin → [http://localhost:8081/](http://localhost:8081/)

---

## 📂 Projektstruktur

```bash
FT-XAMPP/
├── docker/              # Dockerfiles + Configs
│   ├── nginx/           # nginx.conf
│   ├── php/             # php.ini, Dockerfile
│   └── ...
├── htdocs/              # deine Projekte (wie XAMPP)
│   ├── _dashboard/      # internes Dashboard (Vue + Symfony)
│   ├── project1/        # Symfony-Projekt
│   ├── project2/        # Vue-Frontend
│   └── ...
├── docker-compose.yml
├── .env.example
└── README.md
```

---

## ⚡ Beispiel-Setup

- Lege ein neues Projekt in `htdocs` an:
  ```bash
  htdocs/
  ├── my-symfony-api/
  └── my-vue-app/
  ```

- Symfony erreichbar unter:
  ```
  http://localhost/my-symfony-api/public/
  ```

- Vue erreichbar unter:
  ```
  http://localhost/my-vue-app/
  ```

---

## 🔧 Development Tipps

- **Symfony Console** in Container starten:
  ```bash
  docker exec -it ftxampp_phpfpm bash
  php bin/console
  ```

- **Node / NPM** für Vue Projekte:
  ```bash
  docker exec -it ftxampp_node bash
  npm install
  npm run dev
  ```

---

## 📝 Lizenz

Dieses Projekt ist unter der **MIT-Lizenz** veröffentlicht – frei zur Nutzung & Anpassung.

---

## ❤️ Credits

FT-XAMPP ist ein Hobby-Projekt von [Fynn](https://github.com/FTMahringer),  
inspiriert von XAMPP, aber modernisiert für Docker & Fullstack Development.
