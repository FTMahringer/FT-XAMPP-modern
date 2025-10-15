<script setup lang="ts">
import { ref, onMounted } from 'vue'
import ProjectEntry from '@/components/ProjectEntry.vue'
import CreateProjectModal from '@/components/CreateProjectModal.vue'
import { useApi } from '@/composables/useApi'

const api = useApi()
const projects = ref<any[]>([])
const variant = ref<'card'|'list'>('list')
const createOpen = ref(false) // <- Modal sichtbar/unsichtbar

async function load() {
  const res = await api.get('/_dashboard/api/projects.php')
  projects.value = res?.data?.projects ?? []
}
function openProject(name:string){ window.open(`/${name}/`, '_blank') }
async function refreshGit(name:string){ await api.get('/_dashboard/api/git_status.php', { project: name, quick: 1 }); await load() }

// Wird aufgerufen, wenn das Modal "Submit" macht
async function handleCreate(payload: {
  name: string,
  type: 'plain-php' | 'plain-html' | 'symfony' | 'vue',
  options?: Record<string, any>
}) {
  // Beispiel-API: passe den Pfad ggf. an
  const res = await api.post('/_dashboard/api/projects_create.php', payload)
  // optional: Fehlerbehandlung aus res prüfen
  await load()
  createOpen.value = false
}

onMounted(load)
</script>

<template>
  <div class="bar">
    <button @click="variant = variant==='list' ? 'card' : 'list'">Switch: {{ variant }}</button>
    <button @click="load">Reload</button>
    <button @click="createOpen = true">Create</button>
  </div>

  <div class="grid" :class="variant">
    <ProjectEntry
        v-for="p in projects"
        :key="p.name"
        :project="p"
        :variant="variant"
        @open="openProject"
        @refresh="refreshGit"
    />
  </div>

  <!-- Modal -->
  <CreateProjectModal
      v-model:open="createOpen"
      @submit="handleCreate"
      @cancel="createOpen = false"
  />
</template>

<style scoped>
.bar {
  display: flex;
  gap: .5rem;
  margin-bottom: .75rem;
}
.grid.list { display: grid; gap: .75rem; grid-template-columns: 1fr; }
.grid.card { display: grid; gap: .75rem; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
button {
  padding: .45rem .8rem;
  border: 1px solid #444;
  background: #111;
  color: #eee;
  border-radius: .5rem;
  cursor: pointer;
}
button:hover { filter: brightness(1.15); }
</style>