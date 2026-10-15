import { reactive } from 'vue';

export const session = reactive({
    token: '',
    profile: null,
    loaded: false,
});

export function loadSession() {
    try {
        const raw = JSON.parse(localStorage.getItem('ns_user_session') || '{}');
        session.token = raw.token || '';
        session.profile = raw.profile || null;
    } catch (e) { /* ignore */ }
    session.loaded = true;
}

export function saveSession(token, profile) {
    session.token = token || '';
    session.profile = profile || null;
    localStorage.setItem('ns_user_session', JSON.stringify({ token: session.token, profile: session.profile }));
}

export function clearSession() {
    session.token = '';
    session.profile = null;
    localStorage.removeItem('ns_user_session');
}

export function isLogin() {
    return !!(session.token && session.profile && session.profile.id > 0);
}
