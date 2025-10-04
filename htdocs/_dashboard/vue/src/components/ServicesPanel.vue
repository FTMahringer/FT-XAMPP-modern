<script setup>
const services = [
  { name: 'PHPMyAdmin', url: 'http://localhost:8080', tags: ['Port: 8080','MariaDB'], icon:'📊' },
  { name: 'Redis Insight', url: 'http://localhost:5540', tags: ['Port: 5540','Redis'], icon:'⚡' },
]
</script>

<template>
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">✂️ Services</div>
    </div>
    <div class="svc-list">
      <a v-for="s in services" :key="s.name" class="svc-card" :href="s.url" target="_blank">
        <div class="svc-title">{{ s.icon }} {{ s.name }}</div>
        <div class="svc-tags">
          <span class="chip" v-for="t in s.tags" :key="t">{{ t }}</span>
        </div>
      </a>
    </div>
  </div>
</template>

<style>
.panel{ background:var(--card); border:1px solid var(--border); border-radius:14px; padding:16px; }
.panel-head{display:flex; gap:12px; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:12px; margin-bottom:12px;}
.panel-title{font-size:22px; font-weight:700;}
.svc-list{ display:flex; flex-direction:column; gap:12px; }
.svc-card{ display:block; text-decoration:none; color:inherit; padding:12px; border:1px solid var(--border); border-radius:12px; background:rgba(0,0,0,.05); }
.svc-title{ font-weight:700; margin-bottom:6px; }
.svc-tags .chip{ font-size:12px; padding:4px 8px; border-radius:999px; border:1px solid var(--border); margin-right:8px; background:rgba(0,0,0,.05); }
</style>