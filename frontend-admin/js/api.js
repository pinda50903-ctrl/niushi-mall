// 请求封装：统一响应 {code,message,data}，恒 HTTP 200
const BASE = window.MALL_ADMIN.apiBase;

function tokenOf() {
    const s = JSON.parse(localStorage.getItem('ns_admin_session') || 'null');
    return s?.token || '';
}

async function request(path, { method = 'GET', data = null, query = null } = {}) {
    let url = BASE + path;
    if (query) {
        const qs = new URLSearchParams();
        for (const [k, v] of Object.entries(query)) {
            if (v !== undefined && v !== null && v !== '') qs.append(k, v);
        }
        const s = qs.toString();
        if (s) url += (url.includes('?') ? '&' : '?') + s;
    }
    const headers = {};
    const tk = tokenOf();
    if (tk) headers['Authorization'] = 'Bearer ' + tk;
    const opt = { method, headers };
    if (data !== null) {
        headers['Content-Type'] = 'application/json; charset=utf-8';
        opt.body = JSON.stringify(data);
    }
    const resp = await fetch(url, opt);
    let json;
    try { json = await resp.json(); } catch { json = { code: 50000, message: '服务异常（HTTP ' + resp.status + '）' }; }
    return json;
}

export const api = {
    get: (path, query) => request(path, { query }),
    post: (path, data = {}) => request(path, { method: 'POST', data }),
    uploadUrl: BASE + '/manage/setting/upload',
    authHeaders: () => ({ Authorization: 'Bearer ' + tokenOf() }),
    base: BASE,
};

// 相对资源路径 → 绝对 URL
export function assetUrl(path) {
    if (!path) return '';
    if (/^https?:\/\//.test(path)) return path;
    return BASE + '/' + String(path).replace(/^\//, '');
}

export function fmtTime(ts) {
    if (!ts) return '-';
    const d = new Date(ts * 1000);
    const p = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`;
}

export function fmtDay(ts) {
    if (!ts) return '-';
    const d = new Date(ts * 1000);
    const p = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
}

export function fmtMoney(v) {
    return '¥' + Number(v || 0).toFixed(2);
}

// 时间戳 → datetime-local 字符串（供表单编辑）
export function tsToInput(ts) {
    if (!ts) return '';
    const d = new Date(ts * 1000);
    const p = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
}
export function inputToTs(s, fallback = '') {
    if (!s) return fallback;
    return Math.floor(new Date(s).getTime() / 1000);
}
