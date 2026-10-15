import { reactive } from 'vue';
import { api } from './api.js';

const KEY = 'ns_admin_session';

function load() {
    try { return JSON.parse(localStorage.getItem(KEY) || 'null'); } catch { return null; }
}

export const session = reactive({
    token: '',
    profile: null,   // {id, username, nickname, type, tenantId, tenantName, pluginCodes, permissions}
    ...Object.fromEntries(Object.entries(load() || {})),
});

export function saveSession(token, profile) {
    session.token = token;
    session.profile = profile;
    localStorage.setItem(KEY, JSON.stringify({ token, profile }));
}

export function clearSession() {
    session.token = '';
    session.profile = null;
    localStorage.removeItem(KEY);
}

export function isTenant() { return session.profile?.type === 'tenant'; }
export function isPlatform() { return session.profile?.type === 'admin'; }

export function hasPerm(perm) {
    const p = session.profile?.permissions;
    if (!p) return true;
    if (p.includes('*')) return true;
    return p.includes(perm);
}

export async function fetchMe() {
    const path = isTenant() ? '/manage/passport/me' : '/platform/passport/me';
    const r = await api.get(path);
    if (r.code === 0) {
        session.profile = r.data.profile;
        localStorage.setItem(KEY, JSON.stringify({ token: session.token, profile: r.data.profile }));
    }
    return r;
}
