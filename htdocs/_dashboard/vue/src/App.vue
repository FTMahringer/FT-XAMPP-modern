<script setup>
import { ref, computed, onMounted } from 'vue'
import ProjectList from './components/ProjectList.vue'
import ServicesPanel from './components/ServicesPanel.vue'

const q = ref('')
const items = ref([])
const loading = ref(true)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await fetch('/_dashboard/api/projects.php', { headers: { 'Accept': 'application/json' } })
    if (!res.ok) throw new Error('HTTP ' + res.status)
    const data = await res.json()
    if (!data.ok) throw new Error('API not ok')
    items.value = data.projects || []
  } catch (e) {
    error.value = (e && e.message) ? e.message : String(e)
    console.error('Dashboard API error:', e)
  } finally {
    loading.value = false
  }
}

const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return items.value
  return items.value.filter(p => p.name.toLowerCase().includes(s))
})

const dark = ref(true)
function toggleTheme(){ dark.value = !dark.value; document.documentElement.dataset.theme = dark.value ? 'dark' : 'light' }

onMounted(() => {
  document.documentElement.dataset.theme = 'dark'
  load()
})
</script>

<template>
  <div>
    <p v-if="loading">Lade Projekte…</p>
    <p v-else-if="error">⚠️ {{ error }}</p>
    <p v-else-if="items.length === 0">Keine Projekte gefunden.</p>
    <!-- … Rest wie gehabt … -->
  </div>
  <div class="wrap">
    <header class="hero">
      <div class="hero-title">
        <span class="logo">🚀</span>
        <span>My Local Dev Dashboard</span>
      </div>
      <button class="theme-btn" @click="toggleTheme" :title="dark ? 'Switch to light' : 'Switch to dark'">🌙</button>
    </header>

    <section class="content">
      <div class="left">
        <div class="panel">
          <div class="panel-head">
            <div class="panel-title">📁 Projects</div>
            <input v-model="q" type="search" placeholder="Search Projects ..." />
          </div>

          <div v-if="loading" class="muted">Loading projects…</div>
          <div v-else-if="error" class="error">⚠️ {{ error }}</div>
          <ProjectList v-else :items="filtered" />
        </div>
      </div>

      <div class="right">
        <ServicesPanel />
      </div>
    </section>
  </div>
</template>

<style>
:root { color-scheme: light dark; --bg:#111827; --card:#1f2937; --muted:#9ca3af; --fg:#e5e7eb; --accent:#3b82f6; --ok:#10b981; --warn:#f59e0b; --border:rgba(255,255,255,.08); }
:root[data-theme="light"] { --bg:#f3f4f6; --card:#ffffff; --muted:#6b7280; --fg:#111827; --accent:#2563eb; --ok:#059669; --warn:#d97706; --border:rgba(0,0,0,.08); }
*{box-sizing:border-box}
body,html,#app{height:100%}
.wrap{min-height:100%; background:var(--bg); color:var(--fg); font-family:system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;}
.hero{margin:24px; padding:18px 22px; background:linear-gradient(180deg, #3b82f6, #60a5fa); border-radius:10px; display:flex; align-items:center; justify-content:space-between;}
.hero-title{font-size:20px; display:flex; align-items:center; gap:10px; font-weight:700; color:#fff;}
.theme-btn{border:0; background:rgba(255,255,255,.25); padding:8px 10px; border-radius:10px; cursor:pointer; font-size:18px; color:#fff}
.content{display:grid; grid-template-columns: 1fr 360px; gap:24px; padding:0 24px 24px;}
.left .panel, .right .panel{background:var(--card); border:1px solid var(--border); border-radius:14px; padding:16px;}
.panel-head{display:flex; gap:12px; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:12px; margin-bottom:12px;}
.panel-title{font-size:22px; font-weight:700; display:flex; align-items:center; gap:8px;}
input[type="search"]{padding:10px 12px; border-radius:10px; border:1px solid var(--border); background:transparent; color:var(--fg); min-width:240px;}
.muted{opacity:.7}
.error{color:#ef4444}
.logo{filter: drop-shadow(0 2px 6px rgba(0,0,0,.2));}
@media (max-width: 1100px){
  .content{grid-template-columns: 1fr}
}
</style>