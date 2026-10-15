import { createApp, ref, computed } from 'vue';
import { router, TAB_PATHS } from './router.js';
import { session, loadSession } from './store.js';
import { api } from './api.js';

const App = {
    name: 'App',
    setup() {
        loadSession();
        const route = computed(() => router.currentRoute.value);
        const isTab = computed(() => TAB_PATHS.includes(route.value.path));
        const cartCount = ref(0);

        async function refreshCart() {
            if (!session.token || !session.profile || session.profile.id === 0) {
                cartCount.value = 0;
                return;
            }
            const r = await api.get('/api/cart/index');
            if (r.code === 0) cartCount.value = r.data.count || 0;
        }
        window.__refreshCartBadge = refreshCart;
        refreshCart();

        return { route, isTab, cartCount };
    },
    template: `
    <router-view :key="route.fullPath" :cart-count="cartCount"></router-view>
    <nav class="u-tabbar" v-if="isTab">
      <router-link class="tab" :class="{active: route.path === '/'}" to="/">
        <span class="ico">⌂</span><span>首页</span>
      </router-link>
      <router-link class="tab" :class="{active: route.path === '/category'}" to="/category">
        <span class="ico">▦</span><span>分类</span>
      </router-link>
      <router-link class="tab" :class="{active: route.path === '/cart'}" to="/cart">
        <span style="position:relative"><span class="ico">🛒</span><span class="badge" v-if="cartCount > 0">{{ cartCount > 99 ? '99+' : cartCount }}</span></span>
        <span>购物车</span>
      </router-link>
      <router-link class="tab" :class="{active: route.path === '/user'}" to="/user">
        <span class="ico">👤</span><span>我的</span>
      </router-link>
    </nav>
  `,
};

const app = createApp(App);
// 模板内无法直接访问 window.location，统一用 go() 做 hash 跳转
app.config.globalProperties.go = (path) => {
    location.hash = path.startsWith('#') ? path : '#' + path;
};
app.use(router);
app.mount('#app');
