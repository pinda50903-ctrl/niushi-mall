import { createRouter, createWebHashHistory } from 'vue-router';
import { loadSession, isLogin } from './store.js';
import HomePage from './views/home.js';
import CategoryPage from './views/category.js';
import CartPage from './views/cart.js';
import UserPage from './views/user.js';
import GoodsDetail from './views/goods.js';
import Checkout from './views/checkout.js';
import PayPage from './views/pay.js';
import Orders from './views/orders.js';
import OrderDetail from './views/order-detail.js';
import { RefundApply } from './views/refund.js';
import AddressPage from './views/address.js';
import AddressEdit from './views/address-edit.js';
import Coupons from './views/coupons.js';
import CouponCenter from './views/coupon-center.js';
import LoginPage from './views/login.js';
import Favorites from './views/favorites.js';
import Distribution from './views/distribution.js';
import Checkin from './views/checkin.js';

export const TAB_PATHS = ['/', '/category', '/cart', '/user'];

export const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        { path: '/', component: HomePage, meta: { tab: true, title: '牛视商城' } },
        { path: '/category', component: CategoryPage, meta: { tab: true, title: '分类' } },
        { path: '/cart', component: CartPage, meta: { tab: true, title: '购物车' } },
        { path: '/user', component: UserPage, meta: { tab: true, title: '我的' } },
        { path: '/goods/:id', component: GoodsDetail, props: true, meta: { title: '商品详情' } },
        { path: '/checkout', component: Checkout, meta: { title: '确认订单', needLogin: true } },
        { path: '/pay/:orderId', component: PayPage, props: true, meta: { title: '收银台', needLogin: true } },
        { path: '/orders', component: Orders, meta: { title: '我的订单', needLogin: true } },
        { path: '/order/:id', component: OrderDetail, props: true, meta: { title: '订单详情', needLogin: true } },
        { path: '/refund-apply/:itemId', component: RefundApply, props: true, meta: { title: '申请售后', needLogin: true } },
        { path: '/address', component: AddressPage, meta: { title: '收货地址', needLogin: true } },
        { path: '/address-edit/:id?', component: AddressEdit, props: true, meta: { title: '编辑地址', needLogin: true } },
        { path: '/coupons', component: Coupons, meta: { title: '我的优惠券', needLogin: true } },
        { path: '/coupon-center', component: CouponCenter, meta: { title: '领券中心' } },
        { path: '/favorites', component: Favorites, meta: { title: '我的收藏', needLogin: true } },
        { path: '/distribution', component: Distribution, meta: { title: '分销中心', needLogin: true } },
        { path: '/checkin', component: Checkin, meta: { title: '每日签到', needLogin: true } },
        { path: '/login', component: LoginPage, meta: { title: '登录' } },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
    scrollBehavior() {
        return { top: 0 };
    },
});

router.beforeEach((to) => {
    document.title = to.meta?.title ? to.meta.title + ' · 牛视商城' : '牛视商城';
    if (to.meta?.needLogin && !isLogin()) {
        sessionStorage.setItem('ns_login_redirect', to.fullPath);
        return '/login';
    }
    return true;
});
