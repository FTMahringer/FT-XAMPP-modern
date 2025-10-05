<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink, RouterView } from 'vue-router'

const dark = ref(true)
function toggleTheme(){ dark.value = !dark.value; document.documentElement.dataset.theme = dark.value ? 'dark' : 'light' }
onMounted(() => {
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
  dark.value = prefersDark
  document.documentElement.dataset.theme = prefersDark ? 'dark' : 'light'
})
</script>

<template>
  <div class="wrap">
    <header class="hero">
      <div class="hero-title">
        <span class="logo">🚀</span>
        <span>My Local Dev Dashboard</span>
      </div>
      <nav class="nav">
        <RouterLink to="/" class="nav-link">Dashboard</RouterLink>
        <RouterLink to="/api/" class="nav-link">API Explorer</RouterLink>
        <button class="theme-btn" @click="toggleTheme" :title="dark ? 'Switch to light' : 'Switch to dark'">🌙</button>
      </nav>
    </header>

    <RouterView />
  </div>
</template>

<style>
:root { color-scheme: light dark; --bg:#111827; --card:#1f2937; --muted:#9ca3af; --fg:#e5e7eb; --accent:#3b82f6; --ok:#10b981; --warn:#f59e0b; --border:rgba(255,255,255,.08); }
:root[data-theme="light"] { --bg:#f3f4f6; --card:#ffffff; --muted:#6b7280; --fg:#111827; --accent:#2563eb; --ok:#059669; --warn:#d97706; --border:rgba(0,0,0,.08); }
*{box-sizing:border-box}
html,body,#app{height:100%}
.wrap{min-height:100%; background:var(--bg); color:var(--fg); font-family:system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;}
.hero{margin:24px; padding:18px 22px; background:linear-gradient(180deg, #3b82f6, #60a5fa); border-radius:10px; display:flex; align-items:center; justify-content:space-between;}
.hero-title{font-size:20px; display:flex; align-items:center; gap:10px; font-weight:700; color:#fff;}
.logo{filter: drop-shadow(0 2px 6px rgba(0,0,0,.2));}
.nav{display:flex; gap:12px; align-items:center}
.nav-link{padding:6px 10px; border-radius:8px; border:1px solid var(--border); color:#fff; text-decoration:none}
.nav-link.router-link-active{outline:2px solid rgba(255,255,255,.6); background:rgba(255,255,255,.15)}
.theme-btn{border:0; background:rgba(255,255,255,.25); padding:8px 10px; border-radius:10px; cursor:pointer; font-size:18px; color:#fff}

/* Shared Layout (Seiten verwenden das) */
.content{display:grid; grid-template-columns: 1fr 360px; gap:24px; padding:0 24px 24px;}
.left .panel, .right .panel{background:var(--card); border:1px solid var(--border); border-radius:14px; padding:16px;}
.panel-head{display:flex; gap:12px; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:12px; margin-bottom:12px;}
.panel-title{font-size:22px; font-weight:700; display:flex; align-items:center; gap:8px;}
.muted{opacity:.7}
.error{color:#ef4444}
@media (max-width: 1100px){ .content{grid-template-columns: 1fr} }
</style>
