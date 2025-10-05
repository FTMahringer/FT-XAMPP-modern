<!-- src/components/ProjectEntry.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'

type Variant = 'card' | 'list'
type GitInfo = {
  branch?: string
  ahead?: number
  behind?: number
  changes?: number
  clean?: boolean
} | null

const props = defineProps<{
  project: {
    name: string
    // aus projects.php:
    entry?: string | null   // z.B. "index.php" oder "public/index.php"
    type?: string | null    // z.B. "Symfony" | "PHP" | "Vue"
    url?: string | null
    mtime?: number | null   // unix timestamp (sec)
    // optional: zusätzliche Angaben
    path?: string | null
    tech?: string[]
    git?: GitInfo
  },
  variant?: Variant
}>()

const emit = defineEmits<{
  (e: 'open', name: string): void
  (e: 'refresh', name: string): void
}>()

const v = computed<Variant>(() => props.variant ?? 'list')
const busy = ref(false)
const err = ref<string | null>(null)

/** Datum/Time formatting */
const mtimeDate = computed<Date | null>(() => {
  const ts = props.project?.mtime ?? null
  if (!ts) return null
  // PHP liefert Sekunden → JS braucht Millisekunden
  return new Date((ts as number) * 1000)
})

const createdDate = computed<Date | null>(() => {
  const ts = props.project?.createdAt ?? null
  return ts ? new Date(ts * 1000) : null
})
const createdAbs = computed(() => createdDate.value
    ? new Intl.DateTimeFormat(undefined,{year:'numeric',month:'2-digit',day:'2-digit'}).format(createdDate.value)
    : '–')

const dateAbs = computed(() => {
  if (!mtimeDate.value) return '–'
  try {
    return new Intl.DateTimeFormat(undefined, {
      year: 'numeric', month: '2-digit', day: '2-digit',
      hour: '2-digit', minute: '2-digit'
    }).format(mtimeDate.value)
  } catch { return mtimeDate.value.toLocaleString() }
})

const dateRel = computed(() => {
  if (!mtimeDate.value) return ''
  const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' })
  const diffMs = mtimeDate.value.getTime() - Date.now()
  const mins = Math.round(diffMs / 60000)
  const hours = Math.round(diffMs / 3600000)
  const days = Math.round(diffMs / 86400000)
  if (Math.abs(mins) < 60) return rtf.format(mins, 'minute')
  if (Math.abs(hours) < 48) return rtf.format(hours, 'hour')
  return rtf.format(days, 'day')
})

const typeLabel = computed(() => props.project.type || 'Project')
const entryLabel = computed(() => props.project.entry || '—')
const pathLabel = computed(() => props.project.path || `/${props.project.name}/`)

async function refreshGit() {
  try {
    busy.value = true
    err.value = null
    emit('refresh', props.project.name)
  } catch (e:any) {
    err.value = e?.message ?? String(e)
  } finally {
    busy.value = false
  }
}

function openProject() {
  emit('open', props.project.name)
}
</script>

<template>
  <article
      class="entry"
      :class="[v, {'has-error': !!err}]"
      :aria-label="`Project ${project.name}`"
  >
    <header class="head" @click="openProject" role="button" tabindex="0">
      <div class="title-wrap">
        <h3 class="title">{{ project.name }}</h3>
        <div class="chips">
          <span class="chip kind" :title="`Type: ${typeLabel}`">{{ typeLabel }}</span>
          <span v-if="entryLabel && entryLabel !== '—'" class="chip" :title="`Entry: ${entryLabel}`">entry: {{ entryLabel }}</span>
          <span v-if="project.git?.branch" class="chip"> {{ project.git.branch }}</span>
          <span v-if="project.git?.changes" class="chip warn" :title="'Uncommitted changes'">{{ project.git.changes }} changes</span>
          <span v-if="project.git?.ahead" class="chip" :title="'Commits ahead of remote'">↑ {{ project.git.ahead }}</span>
          <span v-if="project.git?.behind" class="chip" :title="'Commits behind remote'">↓ {{ project.git.behind }}</span>
          <span v-if="project.git && project.git.clean" class="chip ok">clean</span>
        </div>
      </div>

      <div class="time" v-if="createdDate">
        <span class="muted">created:</span>
        <span class="date" :title="createdAbs">{{ createdAbs }}</span>
      </div>

      <div class="time" v-if="mtimeDate">
        <span class="muted">updated:</span>
        <span class="date" :title="dateAbs">{{ dateRel }}</span>
      </div>
    </header>

    <p v-if="v==='card'" class="sub">
      <span class="muted mono" :title="pathLabel">{{ pathLabel }}</span>
    </p>

    <footer class="foot">
      <div class="tags">
        <span v-for="t in project.tech || []" :key="t" class="tag">{{ t }}</span>
      </div>
      <div class="actions">
        <a v-if="project.url" :href="project.url" target="_blank" rel="noopener" class="btn" @click.stop>Open</a>
        <button class="btn" @click.stop="refreshGit" :disabled="busy">
          {{ busy ? '…' : 'Refresh' }}
        </button>
      </div>
    </footer>

    <p v-if="err" class="error">Git error: {{ err }}</p>
  </article>
</template>

<style scoped>
.entry{border:1px solid var(--border); border-radius:14px; padding:12px; background:var(--card)}
.entry.list{display:grid; grid-template-columns: 1fr auto; gap:8px; align-items:center}
.entry.card{display:flex; flex-direction:column; gap:8px}

.btn { background: #334155; color: white; border: 0; padding: 8px 12px; border-radius: 8px; cursor: pointer; }
.btn:hover { filter: brightness(1.08); }
.head{display:flex; gap:14px; align-items:center; justify-content:space-between; cursor:pointer}
.title-wrap{display:flex; flex-direction:column; gap:6px}
.title{font-weight:800; margin:0}
.chips{display:flex; gap:6px; flex-wrap:wrap}
.chip{font-size:12px; padding:2px 8px; border:1px solid var(--border); border-radius:999px}
.chip.ok{border-color: var(--ok); color: var(--ok)}
.chip.warn{border-color:#f59e0b; color:#f59e0b}
.chip.kind{border-color: var(--accent); color: var(--accent)}
.time{white-space:nowrap; font-size:12px; opacity:.9}
.date{margin-left:6px}
.sub{margin:0}
.foot{display:flex; align-items:center; justify-content:space-between; gap:10px}
.tags{display:flex; gap:6px; flex-wrap:wrap}
.tag{font-size:12px; padding:4px 8px; border:1px solid var(--border); border-radius:999px}
.btn{border:1px solid var(--border); background:transparent; color:var(--fg); padding:6px 10px; border-radius:10px; cursor:pointer; text-decoration:none}
.btn:hover{border-color:var(--accent)}
.error{color:#ef4444; margin-top:6px}
.has-error{border-color:#ef4444}
.mono{font-family:ui-monospace, SFMono-Regular, Menlo, monospace}
.muted{opacity:.7}
</style>
