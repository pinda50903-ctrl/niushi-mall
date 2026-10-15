// 全局构建 Element Plus 的 ESM shim（配合 index.html 中的 importmap）
const ep = window.ElementPlus;
export const ElMessage = ep.ElMessage;
export const ElMessageBox = ep.ElMessageBox;
export const ElLoading = ep.ElLoading;
export default ep;
