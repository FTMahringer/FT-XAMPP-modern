<script setup>
defineProps({
  p: { type: Object, required: true }
})
</script>

<template>
  <a class="card" :href="p.url">
    <h3>{{ p.name }}</h3>
    <div class="row">
      <span class="tag">{{ p.entry || 'auto' }}</span>
      <span class="muted">{{ p.type }}</span>
    </div>
  </a>
</template>

<style>
.card { border:1px solid rgba(128,128,128,.3); border-radius:16px; padding:16px; transition: transform .08s, box-shadow .08s; background: rgba(255,255,255,.02); display:block; text-decoration:none; color:inherit; }
.card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
.card h3 { margin: 0 0 8px; font-size: 18px; }
.row { display:flex; gap:8px; align-items:center; justify-content:space-between; }
.tag { font: 12px/1.6 ui-monospace, SFMono-Regular, Menlo, Consolas, 'Liberation Mono', monospace; opacity:.8; }
.muted { opacity:.7; font-size: 13px; }
</style>