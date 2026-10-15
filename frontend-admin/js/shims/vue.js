// 全局构建 Vue 的 ESM shim（配合 index.html 中的 importmap）
const v = window.Vue;
export const createApp = v.createApp;
export const reactive = v.reactive;
export const ref = v.ref;
export const computed = v.computed;
export const onMounted = v.onMounted;
export const onUnmounted = v.onUnmounted;
export const watch = v.watch;
export const watchEffect = v.watchEffect;
export const nextTick = v.nextTick;
export const h = v.h;
export const defineComponent = v.defineComponent;
export const toRaw = v.toRaw;
export default v;
