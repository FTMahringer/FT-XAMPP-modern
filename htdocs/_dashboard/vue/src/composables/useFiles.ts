// src/composables/useFiles.ts
import { ref } from 'vue';

export interface FlatNode {
    path: string;
    type: 'file' | 'dir';
    size: number;
    mtime: number;
}
export interface NestedNode {
    name: string;
    path: string;
    type: 'file' | 'dir';
    size: number;
    mtime: number;
    children?: NestedNode[];
}
export interface FileListResponse {
    ok: boolean;
    version: string;
    data: {
        project: string;
        tree: FlatNode[];
        nested: NestedNode[];
    };
    meta: { count: number; files: number; dirs: number; version: string };
}

const API_BASE = '/_dashboard/api/files';

export function useFiles(project: string) {
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function list(): Promise<NestedNode[]> {
        loading.value = true; error.value = null;
        try {
            const res = await fetch(`${API_BASE}/list.php?project=${encodeURIComponent(project)}`);
            const json: FileListResponse = await res.json();
            if (!json.ok) throw new Error('List failed');
            return json.data.nested;
        } catch (e: any) {
            error.value = e?.message ?? String(e);
            return [];
        } finally {
            loading.value = false;
        }
    }

    async function read(path: string): Promise<string> {
        const res = await fetch(`${API_BASE}/read.php?project=${encodeURIComponent(project)}&path=${encodeURIComponent(path)}`);
        const json = await res.json();
        if (!json.ok) throw new Error(json?.error ?? 'Read failed');
        return json.data?.content ?? '';
    }

    async function write(path: string, content: string): Promise<void> {
        const res = await fetch(`${API_BASE}/write.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ project, path, content })
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json?.error ?? 'Write failed');
    }

    async function createFile(path: string): Promise<void> {
        // leere Datei anlegen
        await write(path, '');
    }

    async function createDir(path: string): Promise<void> {
        // Variante A: falls write.php mkdir unterstützt
        const res = await fetch(`${API_BASE}/write.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ project, path, mkdir: true })
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json?.error ?? 'CreateDir failed');

        // Variante B (falls eigener Endpoint existiert):
        // await fetch(`${API_BASE}/mkdir.php`, { ... })
    }

    async function rename(oldPath: string, newPath: string): Promise<void> {
        const res = await fetch(`${API_BASE}/rename.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ project, oldPath, newPath })
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json?.error ?? 'Rename failed');
    }

    async function remove(path: string): Promise<void> {
        const res = await fetch(`${API_BASE}/delete.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ project, path })
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json?.error ?? 'Delete failed');
    }

    return { loading, error, list, read, write, createFile, createDir, rename, remove };
}
