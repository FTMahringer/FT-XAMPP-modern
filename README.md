# 🚀 FT-XAMPP (Next Gen)

[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://www.docker.com/)
[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7-black?logo=symfony)](https://symfony.com/)
[![Vue.js](https://img.shields.io/badge/Vue-3-42b883?logo=vue.js)](https://vuejs.org/)
[![MariaDB](https://img.shields.io/badge/MariaDB-Latest-003545?logo=mariadb)](https://mariadb.org/)
[![Redis](https://img.shields.io/badge/Redis-Cache-red?logo=redis)](https://redis.io/)
[![phpMyAdmin](https://img.shields.io/badge/phpMyAdmin-Available-orange?logo=phpmyadmin)](https://www.phpmyadmin.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

A modern **XAMPP-like development stack** powered by **Docker Compose**, optimized for **PHP / Symfony**, **Vue 3 / Vite**, **MariaDB**, **Redis**, and more.  
All projects are placed in the `htdocs` folder – just like classic XAMPP, but modernized.  

---

## ✨ Features
- 📂 **htdocs projects** – every project inside `htdocs` is automatically accessible
- ⚙️ Full support for **Symfony Backend (API)** + **Vue Frontend (SPA)**
- 🐘 **PHP-FPM** with custom `php.ini`
- 🛢 **MariaDB** as database engine
- 🧩 **Redis** for caching & sessions
- 📊 **phpMyAdmin** for database management
- 🖥 **Dashboard** (Vue + custom API) – overview of all your projects
- 🐳 100% **Docker Compose** based

---

## ✨  Description


The idea was to create a xampp-like environment in docker with more freedom.

The approach for this was to be able to easily host PHP projects and also node projects (vue, ...)

---

## 📦 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/FTMahringer/FT-XAMPP-modern.git
   cd FT-XAMPP
   ```

2. **Adjust your .env file**  
   Copy `.env.example` → `.env` and set your variables (ports, container names, passwords).

3. **Start the containers**
   ```bash
   docker compose up -d --build
   ```

4. **Access in your browser**
   - Dashboard → [http://localhost/](http://localhost/)
   - phpMyAdmin → [http://localhost:8081/](http://localhost:8081/)
   - redisinsight → [http://localhost:5540/](http://localhost:5540/)

---

## 📂 Project Structure

```bash
FT-XAMPP/
├── docker/              # Dockerfiles + configs
│   ├── php/             # php.ini, Dockerfile
│   └── ...
├── data/                # Database + redis + phpmyadmin files
│   ├── mariadb_data/    # mariiadb database files
│   ├── phpmyadmin/      # phpmyadmin config files (change in docker-compose)
│   ├── redis_data/      # redis - storage
│   └── ...
├── htdocs/              # your projects (like XAMPP)
│   ├── _dashboard/      # internal dashboard (Vue + Symfony)
│   ├── project1/        # Symfony project
│   ├── project2/        # Vue frontend
│   └── ...
├── docker-compose.yml
├── .env.example
└── README.md
```

---

## ⚡ Example Setup

- Add a new project in `htdocs`:
  ```bash
  htdocs/
  ├── my-symfony-api/
  └── my-vue-app/
  ```

- Symfony accessible at:
  ```
  http://localhost/my-symfony-api/public/
  ```

- Vue accessible at:
  ```
  http://localhost/my-vue-app/
  ```


---

## 🧩 .htaccess Templates System

FT-XAMPP includes a **built-in `.htaccess` template system** for automatically adding  
optimized rewrite rules and security headers to new projects created via the dashboard API.

### 🗁 Template Location
All templates are stored under:

```
htdocs/_dashboard/config/htaccess-files/
```

### 📄 Available Templates
| File | Used For | Description |
|------|-----------|-------------|
| [.htaccess](https://github.com/FTMahringer/FT-XAMPP-modern/tree/master/htdocs/_dashboard/config/htaccess-files/.htaccess) | **Plain PHP** projects | Basic rewrite & security config for single-file PHP sites |
| [html.htaccess](https://github.com/FTMahringer/FT-XAMPP-modern/tree/master/htdocs/_dashboard/config/htaccess-files/html.htaccess) | **Plain HTML** projects | Adds caching, compression, and basic protection |
| [vue.htaccess](https://github.com/FTMahringer/FT-XAMPP-modern/tree/master/htdocs/_dashboard/config/htaccess-files/vue.htaccess) | **Vue / SPA** projects | Supports client-side routing → all requests redirect to `index.html` |
| [symfony.htaccess](https://github.com/FTMahringer/FT-XAMPP-modern/tree/master/htdocs/_dashboard/config/htaccess-files/symfony.htaccess) | **Symfony (public/)** | Standard Symfony rewrite configuration for `public/` folder |
| [symfony-root.htaccess](https://github.com/FTMahringer/FT-XAMPP-modern/tree/master/htdocs/_dashboard/config/htaccess-files/symfony-root.htaccess) | **Symfony (root)** | Root-level version that forwards requests to `/public/index.php` |

### ⚙️ Automatic Usage
When you create a project via the Dashboard (`/api/projects_create.php`):

- The correct `.htaccess` template is automatically copied into the new project.
- The API chooses the right template based on the selected project type (`plain-php`, `plain-html`, `vue`, `symfony`).
- If the target folder already has a `.htaccess`, it won’t be overwritten.

You can modify the templates anytime — all new projects will immediately use your updated versions.

---

## 🔧 Development Tips

- **Symfony Console** inside container:
  ```bash
  docker exec -it ftxampp_apache bash
  php bin/console
  ```

- **Node / NPM** for Vue projects:
  ```bash
  docker exec -it ftxampp_node bash
  npm install
  npm run dev
  ```

---

## 📝 License

This project is released under the **MIT License** – free to use and adapt.

---

## ❤️ Credits

FT-XAMPP is a hobby project by [Fynn](https://github.com/FTMahringer),  
inspired by XAMPP, but modernized for Docker & fullstack development.
