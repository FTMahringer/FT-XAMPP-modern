# FT-XAMPP Dashboard (Vue 3)

**Build**
```bash
cd htdocs/_dashboard/vue
npm i
npm run build
# Ausgabepfad: ../dist
# Öffne http://localhost/
```

**API**
- `/_dashboard/api/projects.php` liefert JSON-Liste aller Projekte unter `htdocs/` mit Auto-Entry (public/dist/index).

**Dev**
```bash
npm run dev
# Lokal: http://localhost:5173/_dashboard/
# (Für produktiven Betrieb immer builden)
```