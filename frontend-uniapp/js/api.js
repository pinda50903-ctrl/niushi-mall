const BASE = window.UNI_CONFIG.apiBase;

function token() {
    try { return JSON.parse(localStorage.getItem('ns_user_session') || '{}').token || ''; } catch (e) { return ''; }
}

async function request(method, path, data) {
    const opt = {
        method,
        headers: {
            'X-Mall-Id': window.UNI_CONFIG.mallSlug,
            'Authorization': 'Bearer ' + token(),
        },
    };
    let url = BASE + path;
    if (method === 'GET') {
        const qs = new URLSearchParams();
        Object.entries(data || {}).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') qs.append(k, v); });
        const q = qs.toString();
        if (q) url += (url.includes('?') ? '&' : '?') + q;
    } else {
        opt.headers['Content-Type'] = 'application/json';
        opt.body = JSON.stringify(data || {});
    }
    const res = await fetch(url, opt);
    const json = await res.json();
    if (json.code === 40100) {
        // 登录态失效：清除并跳登录
        localStorage.removeItem('ns_user_session');
        if (location.hash !== '#/login') {
            sessionStorage.setItem('ns_login_redirect', location.hash);
            location.hash = '#/login';
        }
    }
    return json;
}

export const api = {
    get: (path, params) => request('GET', path, params),
    post: (path, data) => request('POST', path, data),
    assetUrl(path) {
        if (!path) return '';
        if (/^https?:\/\//.test(path)) return path;
        return BASE + '/' + String(path).replace(/^\//, '');
    },
};

export function fmtMoney(v) {
    return '¥' + (Number(v || 0).toFixed(2));
}

export function fmtTime(ts) {
    if (!ts) return '-';
    const d = new Date(Number(ts) * 1000);
    const p = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`;
}

export function fmtDay(ts) {
    if (!ts) return '-';
    const d = new Date(Number(ts) * 1000);
    const p = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
}

/** 轻提示 */
let toastTimer = null;
export function toast(msg, type = 'info') {
    let el = document.querySelector('.u-toast');
    if (!el) {
        el = document.createElement('div');
        el.className = 'u-toast';
        document.body.appendChild(el);
    }
    el.textContent = msg;
    el.className = 'u-toast show ' + type;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('show'), 2000);
}
