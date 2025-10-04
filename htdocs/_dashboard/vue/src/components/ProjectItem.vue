<script setup>
defineProps({ p: { type:Object, required:true } })
</script>

<template>
  <a class="item" :href="p.url">
    <div class="row1">
      <div class="name">📂 <span>{{ p.name }}</span></div>
    </div>
    <div class="row2">
      <span class="chip">{{ p.type }}</span>
      <span class="chip">🕒 created at: {{ p.createdAt }}</span>
    </div>
    <div class="row3" v-if="p.htaccess">
      <span class="ok">✅ .htaccess found</span>
    </div>
  </a>
</template>

<style>
.item{ background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px 16px; display:block; text-decoration:none; color:inherit; box-shadow: 0 2px 0 rgba(0,0,0,.05) inset; }
.item:hover{ outline:1px solid var(--border); transform: translateY(-1px); transition: transform .08s ease; }
.row1{ display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.name{ font-weight:700; color: var(--fg); }
.chip{ font-size:12px; padding:4px 8px; border-radius:999px; border:1px solid var(--border); margin-right:8px; background:rgba(0,0,0,.05); }
.ok{ font-size:13px; color: var(--ok); }
.row2{ display:flex; flex-wrap:wrap; gap:8px; margin-bottom:6px; }
.row3{}
</style>