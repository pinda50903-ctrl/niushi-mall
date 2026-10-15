import { createRouter, createWebHashHistory } from 'vue-router';
import { session } from './store.js';

import Login from './views/login.js';
import Dashboard from './views/dashboard.js';
import { GoodsList, GoodsEdit } from './views/goods.js';
import Category from './views/category.js';
import { OrderList, OrderDetail, RefundList } from './views/order.js';
import User from './views/user.js';
import Coupon from './views/coupon.js';
import Freight from './views/freight.js';
import Content from './views/content.js';
import Setting from './views/setting.js';
import Account from './views/account.js';
import { PluginList, PluginGroupon, PluginSeckill, PluginDistribution, PluginCheckin, PluginGeneric } from './views/plugins.js';
import { PlatformOverview, PlatformTenants, PlatformMarket, PlatformAdmins, PlatformRoles } from './views/platform.js';

const PluginRoute = {
    props: { code: String },
    setup(props) {
        const map = { groupon: PluginGroupon, seckill: PluginSeckill, distribution: PluginDistribution, check_in: PluginCheckin };
        return { comp: map[props.code] || PluginGeneric, code: props.code };
    },
    template: `<component :is="comp" :code="code" :key="code"></component>`,
};

export const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        { path: '/login', name: 'login', component: Login },
        /* 商城管理端 */
        { path: '/dashboard', component: Dashboard, meta: { tenant: true, perm: 'mall:dashboard', title: '仪表盘' } },
        { path: '/goods', component: GoodsList, meta: { tenant: true, perm: 'mall:goods', title: '商品列表' } },
        { path: '/goods/create', component: GoodsEdit, meta: { tenant: true, perm: 'mall:goods', title: '新增商品' } },
        { path: '/goods/:id/edit', component: GoodsEdit, props: true, meta: { tenant: true, perm: 'mall:goods', title: '编辑商品' } },
        { path: '/category', component: Category, meta: { tenant: true, perm: 'mall:category', title: '商品分类' } },
        { path: '/order', component: OrderList, meta: { tenant: true, perm: 'mall:order', title: '订单管理' } },
        { path: '/order/:id', component: OrderDetail, props: true, meta: { tenant: true, perm: 'mall:order', title: '订单详情' } },
        { path: '/refund', component: RefundList, meta: { tenant: true, perm: 'mall:refund', title: '售后管理' } },
        { path: '/user', component: User, meta: { tenant: true, perm: 'mall:user', title: '用户管理' } },
        { path: '/coupon', component: Coupon, meta: { tenant: true, perm: 'mall:coupon', title: '优惠券' } },
        { path: '/freight', component: Freight, meta: { tenant: true, perm: 'mall:freight', title: '运费模板' } },
        { path: '/content', component: Content, meta: { tenant: true, perm: 'mall:content', title: '内容管理' } },
        { path: '/setting', component: Setting, meta: { tenant: true, perm: 'mall:setting', title: '商城设置' } },
        { path: '/account', component: Account, meta: { tenant: true, perm: 'mall:account', title: '子账号与角色' } },
        { path: '/plugins', component: PluginList, meta: { tenant: true, title: '插件管理' } },
        { path: '/plugin/:code', component: PluginRoute, props: true, meta: { tenant: true, title: '插件' } },
        /* 平台总部 */
        { path: '/p/overview', component: PlatformOverview, meta: { platform: true, perm: 'platform:overview', title: '平台总览' } },
        { path: '/p/tenants', component: PlatformTenants, meta: { platform: true, perm: 'platform:tenant', title: '商城管理' } },
        { path: '/p/market', component: PlatformMarket, meta: { platform: true, perm: 'platform:plugin', title: '插件市场' } },
        { path: '/p/admins', component: PlatformAdmins, meta: { platform: true, perm: 'platform:account', title: '平台账号' } },
        { path: '/p/roles', component: PlatformRoles, meta: { platform: true, perm: 'platform:role', title: '角色权限' } },
        { path: '/', redirect: '/dashboard' },
        { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
    ],
});

router.beforeEach((to) => {
    if (to.path === '/login') {
        if (session.token) {
            const home = session.profile?.type === 'admin' ? '/p/overview' : '/dashboard';
            return home;
        }
        return true;
    }
    if (!session.token) return '/login';
    const isTenantRoute = to.meta?.tenant === true;
    const isPlatformRoute = to.meta?.platform === true;
    const type = session.profile?.type;
    if (isTenantRoute && type !== 'tenant') return '/p/overview';
    if (isPlatformRoute && type !== 'admin') return '/dashboard';
    return true;
});
