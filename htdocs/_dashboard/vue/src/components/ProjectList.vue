<script setup lang="ts">
import { ref, onMounted } from 'vue'
import ProjectEntry from '@/components/ProjectEntry.vue'
import { useApi } from '@/composables/useApi'

const api = useApi()
const projects = ref<any[]>([])
const variant = ref<'card'|'list'>('list')

async function load() {
  const res = await api.get('/_dashboard/api/projects.php')
  projects.value = res?.data?.projects ?? []
}
function openProject(name:string){ window.open(`/${name}/`, '_blank') }
async function refreshGit(name:string){ await api.get('/_dashboard/api/git_status.php', { project: name, quick: 1 }); await load() }

onMounted(load)
</script>

<template>
  <div class="bar">
    <button @click="variant = variant==='list' ? 'card' : 'list'">Switch: {{ variant }}</button>
    <button @click="load">Reload</button>
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
</template>
