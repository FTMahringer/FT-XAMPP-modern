<script setup>
import { ref, onMounted } from 'vue'

const services = ref([
  { name: 'phpMyAdmin', url: 'http://localhost:8080', tags: ['MariaDB'], icon:'📊' },
  { name: 'Redis Insight', url: 'http://localhost:5540', tags: ['Redis'], icon:'⚡' },
])

const status = ref({}) // name -> 'UP' | 'DOWN' | '…'

async function check(url) {
  try {
    const ctrl = new AbortController()
    const t = setTimeout(() => ctrl.abort(), 2500)
    const res = await fetch(url, { method: 'HEAD', mode: 'no-cors', signal: ctrl.signal })
    clearTimeout(t)
    // no-cors gibt uns selten Status; wenn keine Exception: nehmen wir "UP"
    return 'UP'
  } catch { return 'DOWN' }
}

async function runChecks() {
  const arr = services.value
  for (const s of arr) {
    status.value[s.name] = '…'
  }
  await Promise.all(arr.map(async s => {
    status.value[s.name] = await check(s.url)
  }))
}

onMounted(runChecks)
</script>

<template>
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">🧰 Services</div>
      <div class="actions"><button class="btn" @click="runChecks">Refresh</button></div>
    </div>
    <div class="svc-list">
      <a v-for="s in services" :key="s.name" class="svc-card" :href="s.url" target="_blank" rel="noopener">
        <div class="svc-title">{{ s.icon }} {{ s.name }}</div>
        <div class="svc-tags">
          <span v-for="t in s.tags" :key="t" class="chip">{{ t }}</span>
          <span class="chip" :class="{'ok': status[s.name]==='UP', 'bad': status[s.name]==='DOWN'}">{{ status[s.name] || '…' }}</span>
        </div>
      </a>
    </div>
  </div>
</template>

<style scoped>
.btn{padding:8px 12px; border:1px solid var(--border); border-radius:10px; background:transparent; color:var(--fg); cursor:pointer}
.btn:hover{border-color:var(--accent)}
.panel{ background:var(--card); border:1px solid var(--border); border-radius:14px; padding:16px; }
.panel-head{display:flex; gap:12px; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:12px; margin-bottom:12px;}
.panel-title{font-size:22px; font-weight:700;}
.svc-list{ display:flex; flex-direction:column; gap:12px; }
.svc-card{ display:block; text-decoration:none; color:inherit; padding:12px; border:1px solid var(--border); border-radius:12px; background:rgba(0,0,0,.05); }
.svc-title{ font-weight:700; margin-bottom:6px; }
.chip{ font-size:12px; padding:4px 8px; border-radius:999px; border:1px solid var(--border); margin-right:8px; background:rgba(0,0,0,.05); }
.chip.ok{ border-color: var(--ok); color: var(--ok); }
.chip.bad{ border-color: #ef4444; color: #ef4444; }
</style>
