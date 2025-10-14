<!-- src/components/ApiExplorer.vue -->
<script setup>
import { ref, computed, onMounted, watch } from 'vue'

/** ---------- state ---------- */
const loading = ref(true)
const error = ref('')
const schema = ref(null)
const baseUrl = ref('')                 // aus schema.meta.baseUrl oder location.origin
const filter = ref('')
const selected = ref(null)
const params = ref({})                  // hält query/form/body werte
const response = ref({ ok: false, status: 0, headers: {}, raw: '', json: null })
const activeRespTab = ref('json') // 'json' | 'raw' | 'headers'

/** ---------- helpers ---------- */
const methodColor = (m) => ({
  GET:    'var(--ok)',
  POST:   'var(--accent)',
  PUT:    '#d97706',
  PATCH:  '#a855f7',
  DELETE: '#ef4444',
}[m?.toUpperCase()] || 'var(--fg)')

const httpBadgeTitle = (m) => {
  const t = m?.toUpperCase()
  if (t === 'GET') return 'GET – read'
  if (t === 'POST') return 'POST – create'
  if (t === 'PUT') return 'PUT – replace'
  if (t === 'PATCH') return 'PATCH – update'
  if (t === 'DELETE') return 'DELETE – delete'
  return t || 'HTTP'
}

/** debounce for filter */
let filterTimer
const debouncedFilter = ref('')
watch(filter, v => {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => { debouncedFilter.value = v.trim().toLowerCase() }, 140)
})

/** group endpoints by tag with filter */
const groups = computed(() => {
  const out = {}
  const eps = schema.value?.endpoints || []
  const f = debouncedFilter.value
  for (const ep of eps) {
    const hay = `${ep.method} ${ep.path} ${ep.summary || ''} ${(ep.tags||[]).join(' ')}`.toLowerCase()
    if (f && !hay.includes(f)) continue
    for (const tag of ep.tags?.length ? ep.tags : ['_untagged']) {
      (out[tag] ||= []).push(ep)
    }
  }
  // sort endpoints inside tags: path asc, then method
  for (const k of Object.keys(out)) {
    out[k].sort((a,b) => a._short.localeCompare(b._short) || a.method.localeCompare(b.method))
  }
  return out
})

/** collapsed tags persisted */
const collapsed = ref(JSON.parse(localStorage.getItem('apiExplorer.collapsed')||'{}'))
watch(collapsed, v => localStorage.setItem('apiExplorer.collapsed', JSON.stringify(v)), { deep: true })

/** select endpoint + remember */
function selectEndpoint(ep) {
  selected.value = ep
  localStorage.setItem('apiExplorer.lastPath', ep?.path || '')
  params.value = {}
  response.value = { ok:false, status:0, headers:{}, raw:'', json:null }
}

/** build URL path (relative) with query params */
function buildPath(ep) {
  if (!ep) return ''
  const url = new URL(ep.path, location.origin)
  const qp = ep.parameters?.filter(p => p.in === 'query') || []
  for (const p of qp) {
    const v = params.value[p.name]
    if (v !== undefined && v !== '') url.searchParams.set(p.name, v)
  }
  return url.pathname + (url.search ? url.search : '')
}

/** absolute URL (mit .env URL aus schema.meta.baseUrl) */
function buildAbsoluteUrl(ep) {
  const origin = baseUrl.value || location.origin
  return origin.replace(/\/+$/,'') + buildPath(ep)
}

/** sample curl */
const curl = computed(() => {
  const ep = selected.value
  if (!ep) return ''
  const u = buildAbsoluteUrl(ep)
  let c = `curl -X ${ep.method} "${u}"`
  const isForm = ep.bodyStyle === 'form'
  const hasBody = isForm || (ep.parameters?.some(p => p.in === 'body') ?? false)

  if (isForm) {
    const fields = ep.form || []
    for (const f of fields) {
      const name = f.name
      const val = params.value[name]
      if (val !== undefined && val !== '') {
        c += ` \\\n  -F ${JSON.stringify(name)}=${JSON.stringify(String(val))}`
      }
    }
  } else if (hasBody) {
    const body = params.value.body ?? {}
    c += ` \\\n  -H "Content-Type: application/json" \\\n  -d '${JSON.stringify(body)}'`
  }
  return c
})

/** fetch schema (with tiny cache TTL) */
async function loadSchema() {
  loading.value = true
  error.value = ''
  try {
    // tiny TTL cache
    const cached = localStorage.getItem('apiExplorer.schema.cache')
    const ts = +localStorage.getItem('apiExplorer.schema.ts') || 0
    let raw
    if (cached && Date.now() - ts < 60_000) {
      raw = JSON.parse(cached)
    } else {
      const res = await fetch('/_dashboard/api/schema.php')
      if (!res.ok) throw new Error(`Schema HTTP ${res.status}`)
      const data = await res.json()
      if (!data.ok) throw new Error('Schema not ok')
      raw = data.data.schema
      baseUrl.value = data.meta?.baseUrl || ''
      localStorage.setItem('apiExplorer.schema.cache', JSON.stringify(raw))
      localStorage.setItem('apiExplorer.schema.ts', String(Date.now()))
    }

    // short labels + ensure arrays
    raw.endpoints = (raw.endpoints || []).map(ep => ({
      ...ep,
      tags: ep.tags || [],
      parameters: ep.parameters || [],
      _short: ep.name || ep.operationId || (ep.path ? ep.path.split('/').pop() : '')
    }))

    schema.value = raw
    if (!baseUrl.value) {
      // falls nicht im payload: als Fallback origin
      baseUrl.value = location.origin
    }

    // restore last endpoint
    const last = localStorage.getItem('apiExplorer.lastPath')
    let ep = schema.value.endpoints?.[0] || null
    if (last) {
      const m = schema.value.endpoints?.find(e => e.path === last)
      if (m) ep = m
    }
    if (ep) selectEndpoint(ep)
  } catch (e) {
    error.value = e.message || String(e)
  } finally {
    loading.value = false
  }
}

/** try it out */
async function sendRequest() {
  const ep = selected.value
  if (!ep) return
  response.value = { ok:false, status:0, headers:{}, raw:'', json:null }
  activeRespTab.value = 'json'
  try {
    const url = buildPath(ep) // relative fetch reicht
    const init = { method: ep.method, headers: {} }
    const isForm = ep.bodyStyle === 'form'
    const hasBody = isForm || (ep.parameters?.some(p => p.in === 'body') ?? false)

    if (isForm) {
      const fd = new FormData()
      const fields = ep.form || []
      for (const f of fields) {
        const v = params.value[f.name]
        if (v !== undefined && v !== '') fd.append(f.name, String(v))
      }
      init.body = fd
      // KEIN Content-Type setzen → Browser setzt boundary automatisch
    } else if (hasBody) {
      init.headers['Content-Type'] = 'application/json'
      init.body = JSON.stringify(params.value.body ?? {})
    }

    const res = await fetch(url, init)
    const text = await res.text()
    const headers = {}
    res.headers.forEach((v,k) => { headers[k] = v })
    let json = null
    try { json = JSON.parse(text) } catch {}
    response.value = { ok: res.ok, status: res.status, headers, raw: text, json }
  } catch (e) {
    response.value = { ok:false, status:0, headers:{}, raw: String(e), json:null }
  }
}

/** copy helper */
async function copy(text) {
  try { await navigator.clipboard.writeText(text) } catch {}
}

/** init */
onMounted(loadSchema)
</script>

<template>
  <div class="api-explorer">
    <div class="topbar">
      <div class="search">
        <input type="search" v-model="filter" placeholder="Filter endpoints (name, path, tag, method) …" />
      </div>
    </div>

    <div v-if="loading" class="skeleton">Loading schema…</div>
    <div v-else-if="error" class="error">Error: {{ error }}</div>
    <div v-else class="grid">
      <!-- left: tags + endpoints -->
      <aside class="left">
        <div v-for="(eps, tag) in groups" :key="tag" class="tag-group">
          <button class="tag-head" @click="collapsed[tag] = !collapsed[tag]">
            <span class="chev" :class="{rot: !collapsed[tag]}">▶</span>
            <span class="tag-name">{{ tag === '_untagged' ? 'untagged' : tag }}</span>
            <span class="count">{{ eps.length }}</span>
          </button>
          <div class="ep-list" v-show="!collapsed[tag]">
            <button
                v-for="ep in eps" :key="ep.method + ep.path"
                class="ep-item" :class="{active: selected && selected.path===ep.path && selected.method===ep.method}"
                @click="selectEndpoint(ep)"
                :title="ep.summary || ep.path"
            >
              <span class="badge" :style="{borderColor: methodColor(ep.method), color: methodColor(ep.method)}" :aria-label="httpBadgeTitle(ep.method)">{{ ep.method }}</span>
              <span class="ep-path">{{ ep.name || ep.operationId || ep._short || ep.path }}</span>
            </button>
          </div>
        </div>
      </aside>

      <!-- right: details -->
      <section class="right" v-if="selected">
        <header class="head">
          <span class="badge big" :style="{borderColor: methodColor(selected.method), color: methodColor(selected.method)}">{{ selected.method }}</span>
          <h2 class="path">{{ selected.path }}</h2>
        </header>
        <p class="summary" v-if="selected.summary">{{ selected.summary }}</p>

        <div class="panel">
          <h3>Parameters</h3>

          <!-- FORM style (multipart/form-data) -->
          <div v-if="selected.bodyStyle==='form'" class="form">
            <div v-for="f in selected.form || []" :key="f.name" class="row">
              <label :for="'f_'+f.name">
                <strong>{{ f.name }}</strong>
                <span class="muted">(form)</span>
                <span v-if="f.required" class="req">*</span>
              </label>
              <input
                  :id="'f_'+f.name"
                  type="text"
                  v-model="params[f.name]"
                  :placeholder="f.type || ''"
              />
            </div>
            <!-- zusätzlich: Query-Params falls vorhanden -->
            <div v-for="p in (selected.parameters||[]).filter(p=>p.in==='query')" :key="p.name+'q'" class="row">
              <label :for="'p_'+p.name">
                <strong>{{ p.name }}</strong>
                <span class="muted">(query)</span>
                <span v-if="p.required" class="req">*</span>
              </label>
              <input :id="'p_'+p.name" type="text" v-model="params[p.name]" :placeholder="p.description || ''" />
            </div>
          </div>

          <!-- JSON body / query (Standard) -->
          <div v-else class="form">
            <div v-for="p in selected.parameters || []" :key="p.name + p.in" class="row">
              <label :for="'p_'+p.name">
                <strong>{{ p.name }}</strong>
                <span class="muted">({{ p.in }})</span>
                <span v-if="p.required" class="req">*</span>
              </label>

              <template v-if="p.in === 'body'">
                <textarea
                    :id="'p_'+p.name"
                    v-model="params.body"
                    class="mono"
                    rows="8"
                    :placeholder="p.description || 'JSON body'"
                    @blur="() => { try { params.body = JSON.parse(typeof params.body==='string'?params.body:JSON.stringify(params.body)) } catch {} }"
                />
              </template>
              <template v-else>
                <input
                    :id="'p_'+p.name"
                    type="text"
                    v-model="params[p.name]"
                    :placeholder="p.description || ''"
                />
              </template>
            </div>
          </div>
        </div>

        <div class="panel">
          <h3>Request</h3>
          <div class="mono url">{{ buildAbsoluteUrl(selected) }}</div>
          <div class="actions">
            <button @click="sendRequest">Try it</button>
            <button @click="copy(buildAbsoluteUrl(selected))">Copy URL</button>
            <button @click="copy(curl)">Copy cURL</button>
          </div>
          <pre class="mono curl">{{ curl }}</pre>
        </div>

        <div class="panel">
          <h3>Response <span class="muted">({{ response.status }})</span></h3>
          <div class="tabs">
            <button :class="{active:activeRespTab==='json'}" @click="activeRespTab='json'">JSON</button>
            <button :class="{active:activeRespTab==='raw'}" @click="activeRespTab='raw'">Raw</button>
            <button :class="{active:activeRespTab==='headers'}" @click="activeRespTab='headers'">Headers</button>
          </div>
          <div v-if="activeRespTab==='json'">
            <pre class="mono payload">{{ response.json ?? response.raw }}</pre>
          </div>
          <div v-else-if="activeRespTab==='raw'">
            <pre class="mono payload">{{ response.raw }}</pre>
          </div>
          <div v-else>
            <pre class="mono payload">{{ response.headers }}</pre>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.api-explorer{display:flex; flex-direction:column; gap:12px}
.topbar{display:flex; gap:16px; align-items:center; justify-content:space-between}
.title{font-weight:800; font-size:20px}
.search input{padding:10px 12px; border-radius:10px; border:1px solid var(--border); background:transparent; color:var(--fg); min-width:280px}

.grid{display:grid; grid-template-columns: 360px 1fr; gap:16px}
.left{background:var(--card); border:1px solid var(--border); border-radius:14px; padding:10px; max-height:70vh; overflow:auto}
.right .panel, .panel{background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px; margin-bottom:12px}

.tag-head{width:100%; display:flex; align-items:center; gap:8px; background:transparent; border:0; padding:8px 6px; cursor:pointer; border-radius:10px}
.tag-head:hover{background:rgba(0,0,0,.06)}
.tag-name{font-weight:700}
.count{opacity:.7; margin-left:auto}
.chev{display:inline-block; transform:rotate(0deg); transition: transform .12s}
.chev.rot{transform:rotate(90deg)}
.ep-list{display:flex; flex-direction:column; gap:6px; padding:6px 0 10px 22px}
.ep-item{display:flex; gap:10px; align-items:center; width:100%; text-align:left; background:transparent; border:1px solid transparent; border-radius:10px; padding:8px}
.ep-item:hover{border-color:var(--border); background:rgba(0,0,0,.04)}
.ep-item.active{border-color:var(--accent); outline:1px solid var(--accent)}

.badge{font: 12px/1 ui-monospace, SFMono-Regular, Menlo, monospace; border:1px solid; padding:2px 6px; border-radius:999px}
.badge.big{font-size:14px; padding:4px 10px}
.ep-path{font-family: ui-monospace, SFMono-Regular, Menlo, monospace}
.head{display:flex; gap:12px; align-items:center}
.path{font-weight:800; margin:0}

.summary{opacity:.8; margin-top:6px}
.form{display:flex; flex-direction:column; gap:10px}
.row{display:grid; grid-template-columns: 240px 1fr; gap:10px; align-items:center}
.req{color:#ef4444}
.mono{font-family: ui-monospace, SFMono-Regular, Menlo, monospace}
.url{margin:6px 0 8px; word-break: break-all}
.curl{max-height:180px; overflow:auto; white-space:pre-wrap}
.actions{display:flex; gap:8px; flex-wrap:wrap}
button{border:1px solid var(--border); background:transparent; color:var(--fg); padding:8px 10px; border-radius:10px; cursor:pointer}
button:hover{border-color:var(--accent)}
.tabs{display:flex; gap:6px; margin-bottom:8px}
.tabs button.active{border-color:var(--accent)}

.payload{max-height:420px; overflow:auto; background:rgba(0,0,0,.05); padding:10px; border-radius:10px; border:1px solid var(--border)}
.skeleton{opacity:.8}

@media (max-width: 1100px){
  .grid{grid-template-columns: 1fr}
  .row{grid-template-columns: 1fr}
}
.error{color:#ef4444}
</style>
