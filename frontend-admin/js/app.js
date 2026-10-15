import { createApp, computed, ref } from 'vue';
import { router } from './router.js';
import { session, clearSession, isTenant, hasPerm } from './store.js';
import { ImgUpload } from './ui.js';
import { PLUGIN_META } from './plugin-meta.js';

// 无构建模式：Element Plus / 图标 / 中文语言包由 index.html 全局脚本加载
const ElementPlus = window.ElementPlus;
const Icons = window.ElementPlusIconsVue || {};
const zhCn = window.ElementPlusLocaleZhCn;

/* ---------- 菜单定义 ---------- */
const TENANT_PLUGIN_SHORTCUTS = ['groupon', 'seckill', 'distribution', 'check_in'];

function tenantMenu() {
    const owned = session.profile?.pluginCodes || [];
    const groups = [
        { title: '概览', items: [{ path: '/dashboard', label: '仪表盘', icon: 'Odometer', perm: 'mall:dashboard' }] },
        {
            title: '商品', items: [
                { path: '/goods', label: '商品列表', icon: 'Goods', perm: 'mall:goods' },
                { path: '/category', label: '商品分类', icon: 'Grid', perm: 'mall:category' },
            ],
        },
        {
            title: '交易', items: [
                { path: '/order', label: '订单管理', icon: 'List', perm: 'mall:order' },
                { path: '/refund', label: '售后管理', icon: 'RefreshLeft', perm: 'mall:refund' },
            ],
        },
        { title: '用户', items: [{ path: '/user', label: '用户管理', icon: 'User', perm: 'mall:user' }] },
        {
            title: '营销', items: [
                { path: '/coupon', label: '优惠券', icon: 'Ticket', perm: 'mall:coupon' },
                ...TENANT_PLUGIN_SHORTCUTS.filter((c) => owned.includes(c)).map((c) => ({
                    path: '/plugin/' + c,
                    label: PLUGIN_META.find((p) => p.code === c)?.name || c,
                    icon: 'MagicStick',
                })),
            ],
        },
        {
            title: '运营', items: [
                { path: '/content', label: '内容管理', icon: 'Picture', perm: 'mall:content' },
                { path: '/plugins', label: '插件中心', icon: 'Box', perm: '' },
            ],
        },
        {
            title: '设置', items: [
                { path: '/freight', label: '运费模板', icon: 'Van', perm: 'mall:freight' },
                { path: '/setting', label: '商城设置', icon: 'Setting', perm: 'mall:setting' },
                { path: '/account', label: '子账号', icon: 'Avatar', perm: 'mall:account' },
            ],
        },
    ];
    return groups.map((g) => ({ ...g, items: g.items.filter((i) => !i.perm || hasPerm(i.perm)) })).filter((g) => g.items.length);
}

function platformMenu() {
    return [
        { title: '总览', items: [{ path: '/p/overview', label: '数据总览', icon: 'DataAnalysis', perm: 'platform:overview' }] },
        { title: '商城', items: [{ path: '/p/tenants', label: '商城管理', icon: 'Shop', perm: 'platform:tenant' }] },
        { title: '插件', items: [{ path: '/p/market', label: '插件市场', icon: 'Box', perm: 'platform:plugin' }] },
        {
            title: '账号', items: [
                { path: '/p/admins', label: '账号管理', icon: 'UserFilled', perm: 'platform:account' },
                { path: '/p/roles', label: '角色权限', icon: 'Key', perm: 'platform:role' },
            ],
        },
    ].map((g) => ({ ...g, items: g.items.filter((i) => !i.perm || hasPerm(i.perm)) })).filter((g) => g.items.length);
}

/* ---------- 根组件 ---------- */
const App = {
    name: 'App',
    setup() {
        const collapsed = ref(false);
        const menu = computed(() => {
            if (!session.profile) return [];
            return session.profile.type === 'admin' ? platformMenu() : tenantMenu();
        });
        const currentTitle = computed(() => router.currentRoute.value.meta?.title || '');
        const active = computed(() => '/' + (router.currentRoute.value.path.split('/')[1] || ''));
        const whoText = computed(() =>
            session.profile ? `${session.profile.nickname || session.profile.username}${session.profile.tenantName ? ' · ' + session.profile.tenantName : ''}` : ''
        );
        async function logout() {
            clearSession();
            location.hash = '#/login';
            location.reload();
        }
        return { collapsed, menu, currentTitle, active, whoText, logout, isTenant, session };
    },
    template: `
    <router-view v-if="$route.name === 'login'"></router-view>
    <div v-else class="ns-layout">
      <aside class="ns-aside" :class="{collapsed}">
        <div class="ns-brand">
          <div class="logo">牛</div>
          <span class="brand-text">{{ isTenant() ? (session.profile?.tenantName || '商城管理') : '平台总部' }}</span>
        </div>
        <nav class="ns-menu">
          <div v-for="g in menu" :key="g.title">
            <div class="group-title"><span>{{ g.title }}</span></div>
            <router-link v-for="item in g.items" :key="item.path" :to="item.path"
              :class="{active: active === item.path || (item.path !== '/plugins' && $route.path.startsWith(item.path + '/'))}">
              <el-icon><component :is="item.icon"></component></el-icon>
              <span class="label">{{ item.label }}</span>
            </router-link>
          </div>
        </nav>
      </aside>
      <div class="ns-main">
        <header class="ns-header">
          <el-button text @click="collapsed = !collapsed">
            <el-icon size="18"><Expand v-if="collapsed"></Expand><Fold v-else></Fold></el-icon>
          </el-button>
          <div class="crumb">{{ currentTitle }}</div>
          <div class="who">
            <el-tag size="small" :type="isTenant() ? 'success' : 'warning'">{{ isTenant() ? '商城管理端' : '平台总部' }}</el-tag>
            <span style="font-size:13px">{{ whoText }}</span>
            <el-dropdown>
              <el-avatar :size="32" style="background:var(--ns-primary);cursor:pointer">{{ (session.profile?.nickname || 'A')[0] }}</el-avatar>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item @click="logout">退出登录</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </header>
        <main class="ns-content">
          <router-view :key="$route.fullPath"></router-view>
        </main>
      </div>
    </div>
  `,
};

/* ---------- 启动 ---------- */
const app = createApp(App);
app.use(ElementPlus, { locale: zhCn });
for (const [name, comp] of Object.entries(Icons)) {
    app.component(name, comp);
}
app.component('ImgUpload', ImgUpload);
app.use(router);
app.mount('#app');
