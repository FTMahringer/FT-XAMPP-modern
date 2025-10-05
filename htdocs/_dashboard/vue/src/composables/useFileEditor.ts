// Lightweight Wrapper über deine PHP-API (list.php, read.php, write.php, rename.php, delete.php)
// Endpunkte: /_dashboard/api/files/<op>.php?project=NAME

export type ListResponse = { ok: boolean; data: { project: string; tree: { path: string; type: 'file'|'dir'; size?: number; mtime?: number }[] } }

export function useFileEditorApi(base = '/_dashboard/api/files') {
  async function list(project: string): Promise<ListResponse['data']> {
    const r = await fetch(`${base}/list.php?project=${encodeURIComponent(project)}`)
const j = await r.json() as any
if (!j.ok) throw new Error('list failed')
return j.data
}
async function read(project: string, path: string) {
    const r = await fetch(`${base}/read.php?project=${encodeURIComponent(project)}&path=${encodeURIComponent(path)}`)
    const j = await r.json()
    if (!j.ok) throw new Error('read failed')
    return { content: j.data.content as string }
}
async function write(project: string, path: string, content: string) {
    const r = await fetch(`${base}/write.php`, { method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify({ project, path, content }) })
    const j = await r.json(); if (!j.ok) throw new Error('write failed')
    return true
}
async function rename(project: string, from: string, to: string) {
    const r = await fetch(`${base}/rename.php`, { method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify({ project, from, to }) })
    const j = await r.json(); if (!j.ok) throw new Error('rename failed')
    return true
}
async function _delete(project: string, path: string) {
    const r = await fetch(`${base}/delete.php`, { method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify({ project, path }) })
    const j = await r.json(); if (!j.ok) throw new Error('delete failed')
    return true
}
// optional, falls du mkdir in PHP ergänzt
async function mkdir(project: string, path: string) {
    const r = await fetch(`${base}/mkdir.php`, { method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify({ project, path }) })
    const j = await r.json(); if (!j.ok) throw new Error('mkdir failed')
    return true
}
return { list, read, write, rename, delete: _delete, mkdir }
}