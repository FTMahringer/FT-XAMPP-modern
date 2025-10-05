<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{ open: boolean }>()
const emit = defineEmits(['close','created'])

const name = ref('')
const type = ref<'php-plain'|'symfony'|'vue'>('php-plain')
const busy = ref(false)
const error = ref('')

async function create() {
  busy.value = true; error.value = ''
  const body = new FormData()
  body.set('name', name.value.trim())
  body.set('type', type.value)
  try {
    const res = await fetch('/_dashboard/api/projects_create.php', { method:'POST', body })
    const j = await res.json()
    if (!res.ok || !j.ok) throw new Error(j.error || `HTTP ${res.status}`)
    emit('created', j.data)
    emit('close')
    name.value = ''
    type.value = 'php-plain'
  } catch (e:any) {
    error.value = e.message || String(e)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div v-if="open" class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal">
      <header class="m-head">
        <div class="title">⚡ Quick Create Project</div>
        <button class="btn" @click="$emit('close')">Close</button>
      </header>
      <div class="body">
        <div class="row">
          <label>Project name</label>
          <input v-model="name" placeholder="my-app" />
        </div>
        <div class="row">
          <label>Type</label>
          <select v-model="type">
            <option value="php-plain">PHP (plain)</option>
            <option value="symfony">Symfony (Composer)</option>
            <option value="vue-basic">Vue (Vite basic)</option>
            <!-- <option value="vue-npx">Vue (create-vue via node-runner) – später</option> -->
          </select>
        </div>
        <div v-if="error" class="error">{{ error }}</div>
      </div>
      <footer class="foot">
        <button class="btn" :disabled="busy || !name.trim()" @click="create">{{ busy ? 'Creating…' : 'Create' }}</button>
      </footer>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop{position:fixed; inset:0; background:rgba(0,0,0,.5); display:flex; align-items:center; justify-content:center; z-index:1000}
.modal{width:min(560px, 96vw); background:var(--bg); border:1px solid var(--border); border-radius:16px; display:flex; flex-direction:column}
.m-head{display:flex; align-items:center; justify-content:space-between; padding:12px 14px; border-bottom:1px solid var(--border)}
.title{font-weight:800; font-size:18px}
.body{padding:14px; display:flex; flex-direction:column; gap:12px}
.row{display:grid; grid-template-columns: 140px 1fr; gap:10px; align-items:center}
input,select{padding:8px 10px; border-radius:10px; border:1px solid var(--border); background:transparent; color:var(--fg)}
.foot{display:flex; justify-content:flex-end; padding:12px 14px; border-top:1px solid var(--border)}
.btn{border:1px solid var(--border); background:transparent; color:var(--fg); padding:6px 10px; border-radius:10px; cursor:pointer}
.btn:hover{border-color:var(--accent)}
.error{color:#ef4444}
</style>
