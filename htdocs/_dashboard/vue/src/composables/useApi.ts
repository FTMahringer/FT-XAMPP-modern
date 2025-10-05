export function useApi(base = '') {
    const buildUrl = (path: string, params?: Record<string,any>) => {
        const url = new URL(path, window.location.origin)
        if (params) {
            Object.entries(params).forEach(([k,v]) => {
                if (v !== undefined && v !== null && v !== '') url.searchParams.set(k,String(v))
            })
        }
        return base + url.pathname + (url.search || '')
    }

    async function request(method:'GET'|'POST'|'PUT'|'PATCH'|'DELETE', path:string, opts?:{
        params?: Record<string,any>,
        body?: any,
        headers?: Record<string,string>
    }) {
        const url = buildUrl(path, opts?.params)
        const init: RequestInit = { method, headers: { ...(opts?.headers||{}) } }
        if (opts?.body !== undefined) {
            init.headers!['Content-Type'] = 'application/json'
            init.body = JSON.stringify(opts.body)
        }
        const res = await fetch(url, init)
        const text = await res.text()
        let json: any = null
        try { json = JSON.parse(text) } catch {}
        if (!res.ok) throw new Error(json?.error || `HTTP ${res.status}`)
        return json ?? text
    }

    return {
        get: (p:string, params?:Record<string,any>) => request('GET', p, { params }),
        post:(p:string, body?:any) => request('POST', p, { body }),
        put: (p:string, body?:any) => request('PUT', p, { body }),
        patch:(p:string, body?:any) => request('PATCH', p, { body }),
        del: (p:string, body?:any) => request('DELETE', p, { body }),
    }
}
