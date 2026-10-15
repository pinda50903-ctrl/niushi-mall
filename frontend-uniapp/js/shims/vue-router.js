// 全局构建 vue-router 的 ESM shim（配合 index.html 中的 importmap）
const r = window.VueRouter;
export const createRouter = r.createRouter;
export const createWebHashHistory = r.createWebHashHistory;
export default r;
