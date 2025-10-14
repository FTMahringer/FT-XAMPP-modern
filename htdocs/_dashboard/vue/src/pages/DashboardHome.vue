<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProjectList from '../components/ProjectList.vue'
import ServicesPanel from '../components/ServicesPanel.vue'

const route = useRoute()
const router = useRouter()

const q = ref((route.query.q ?? '').toString())
const sort = ref(['name','type','mtime'].includes(route.query.sort?.toString() || '') ? route.query.sort.toString() : 'name')
const order = ref((route.query.order === 'desc') ? 'desc' : 'asc')
const limit = ref(500)

const items = ref([])
const loading = ref(true)
const error = ref('')

const searchEl = ref(null)
const listRef = ref(null)

let debounceTimer = null
function debouncedReload() { clearTimeout(debounceTimer); debounceTimer = setTimeout(load, 250) }

watch([q, sort, order], () => {
  router.replace({
    query: {
      ...route.query,
      q: q.value || undefined,
      sort: sort.value !== 'name' ? sort.value : undefined,
      order: order.value !== 'asc' ? order.value : undefined
    }
  })
  debouncedReload()
})

async function load() {
  loading.value = true; error.value = ''
  try {
    const url = new URL('/_dashboard/api/projects.php', window.location.origin)
    if (q.value) url.searchParams.set('q', q.value)
    url.searchParams.set('sort', sort.value)
    url.searchParams.set('order', order.value)
    url.searchParams.set('limit', String(limit.value))
    const res = await fetch(url.pathname + url.search, { headers: { 'Accept': 'application/json' } })
    if (!res.ok) throw new Error('HTTP ' + res.status)
    const data = await res.json()
    if (!data.ok) throw new Error(data.error || 'API not ok')
    items.value = (data?.data?.projects ?? data?.projects ?? [])
  } catch (e) { error.value = e.message || String(e) }
  finally { loading.value = false }
}

const filtered = computed(()=> items.value)
function toggleOrder(){ order.value = (order.value === 'asc') ? 'desc' : 'asc' }
function refresh(){ load() }
function retryVisibleGit(){ listRef.value?.retryVisible?.() }

function onKey(e){
  const tag = (e.target && e.target.tagName || '').toLowerCase()
  const typing = tag === 'input' || tag === 'textarea' || e.metaKey || e.ctrlKey || e.altKey
  if (typing) return
  if (e.key === '/') { e.preventDefault(); searchEl.value?.focus() }
  if (e.key.toLowerCase() === 'g') { router.push('/api') }
  if (e.key.toLowerCase() === 'r') { retryVisibleGit() }
}
onMounted(()=> { load(); window.addEventListener('keydown', onKey) })
onBeforeUnmount(()=> window.removeEventListener('keydown', onKey))
</script>

<template>
  <section class="content">
    <div class="left">
      <div class="panel">
        <div class="panel-head">
          <div class="panel-title">📁 Projects</div>
          <div class="controls">
            <input ref="searchEl" v-model="q" type="search" placeholder="Search Projects … (press /)" />
            <select v-model="sort" title="Sort by">
              <option value="name">Name</option>
              <option value="type">Type</option>
              <option value="mtime">Last Modified</option>
            </select>
            <button class="order" :title="order==='asc' ? 'Ascending' : 'Descending'" @click="toggleOrder">
              {{ order === 'asc' ? '↑' : '↓' }}
            </button>
            <button class="refresh" title="Reload" @click="refresh">↻</button>
            <button class="refresh" title="Retry Git for visible" @click="retryVisibleGit">↻ git</button>
          </div>
        </div>

        <div v-if="error" class="error">⚠️ {{ error }}</div>
        <ProjectList :items="filtered" :loading="loading" ref="listRef" />
      </div>
    </div>

    <div class="right">
      <ServicesPanel />
      <div class="panel" style="margin-top:16px">
        <div class="panel-head">
          <div class="panel-title">🧪 API Explorer</div>
        </div>
        <div class="muted">Kleiner Hinweis: Für volle Ansicht oben öffnen. (Shortcut: „g“)</div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.controls{display:flex; gap:8px; align-items:center}
input[type="search"]{padding:10px 12px; border-radius:10px; border:1px solid var(--border); background:transparent; color:var(--fg); min-width:220px;}
select{padding:9px 10px; border-radius:10px; border:1px solid var(--border); background:transparent; color:var(--fg)}
button.order, button.refresh{border:1px solid var(--border); background:transparent; color:var(--fg); padding:8px 10px; border-radius:10px; cursor:pointer}
button.order:hover, button.refresh:hover{border-color:var(--accent)}
</style>
