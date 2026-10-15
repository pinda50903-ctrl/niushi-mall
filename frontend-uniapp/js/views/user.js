import { ref, onMounted } from 'vue';
import { api } from '../api.js';
import { session, isLogin } from '../store.js';

export default {
    name: 'UserPage',
    setup() {
        const data = ref(null);

        async function load() {
            if (!isLogin()) return;
            const r = await api.get('/api/user/center');
            if (r.code === 0) {
                data.value = r.data;
                session.profile = Object.assign({}, session.profile, r.data.profile);
                localStorage.setItem('ns_user_session', JSON.stringify({ token: session.token, profile: session.profile }));
            }
        }
        onMounted(load);

        function logout() {
            localStorage.removeItem('ns_user_session');
            session.token = '';
            session.profile = null;
            location.hash = '#/';
            location.reload();
        }

        return { data, isLogin, session, logout };
    },
    template: `
    <div class="u-page">
      <div class="u-me-head">
        <template v-if="isLogin()">
          <div class="row" @click="go('#/user')">
            <span class="avatar"><img v-if="data?.profile?.avatar" :src="data.profile.avatar" alt=""><span v-else>🐮</span></span>
            <div>
              <div style="font-size:17px;font-weight:600">{{ data?.profile?.nickname || session.profile?.nickname }}</div>
              <div style="font-size:12px;opacity:.8;margin-top:3px">{{ session.profile?.mobile || '' }}</div>
            </div>
          </div>
          <div class="stats" v-if="data">
            <div class="s"><b>{{ data.profile.balance.toFixed(2) }}</b>余额（元）</div>
            <div class="s"><b>{{ data.profile.integral }}</b>积分</div>
            <div class="s"><b>{{ data.profile.level }}</b>等级</div>
          </div>
        </template>
        <template v-else>
          <div class="row" @click="go('#/login')">
            <span class="avatar">👤</span>
            <div>
              <div style="font-size:17px;font-weight:600">点击登录</div>
              <div style="font-size:12px;opacity:.8;margin-top:3px">登录后享更多权益</div>
            </div>
          </div>
        </template>
      </div>

      <div class="u-order-entries" v-if="data">
        <div class="e" @click="go('#/orders?status=10')">
          <span class="badge" v-if="data.orderTabs.pendingPay > 0">{{ data.orderTabs.pendingPay }}</span>
          <div class="ico">💳</div>待付款
        </div>
        <div class="e" @click="go('#/orders?status=20')">
          <span class="badge" v-if="data.orderTabs.pendingDelivery > 0">{{ data.orderTabs.pendingDelivery }}</span>
          <div class="ico">📦</div>待发货
        </div>
        <div class="e" @click="go('#/orders?status=30')">
          <span class="badge" v-if="data.orderTabs.delivered > 0">{{ data.orderTabs.delivered }}</span>
          <div class="ico">🚚</div>待收货
        </div>
        <div class="e" @click="go('#/orders?status=refund')">
          <span class="badge" v-if="data.orderTabs.refund > 0">{{ data.orderTabs.refund }}</span>
          <div class="ico">↩️</div>售后
        </div>
        <div class="e" @click="go('#/orders')"><div class="ico">📋</div>全部订单</div>
      </div>

      <div class="u-group">
        <div class="u-cell" @click="go('#/address')"><span>📍</span><span class="label">收货地址</span><span class="arrow">›</span></div>
        <div class="u-cell" @click="go('#/coupons')"><span>🎟</span><span class="label">我的优惠券</span><span class="value" v-if="data">{{ data.couponCount }} 张可用</span><span class="arrow">›</span></div>
        <div class="u-cell" @click="go('#/coupon-center')"><span>🎁</span><span class="label">领券中心</span><span class="arrow">›</span></div>
        <div class="u-cell" @click="go('#/favorites')"><span>❤️</span><span class="label">我的收藏</span><span class="arrow">›</span></div>
      </div>
      <div class="u-group">
        <div class="u-cell" @click="go('#/checkin')"><span>📅</span><span class="label">每日签到</span><span class="value">赚积分</span><span class="arrow">›</span></div>
        <div class="u-cell" @click="go('#/distribution')"><span>🤝</span><span class="label">分销中心</span><span class="value">分享赚佣金</span><span class="arrow">›</span></div>
      </div>
      <div class="u-group" v-if="isLogin()">
        <div class="u-cell" @click="logout"><span>🚪</span><span class="label">退出登录</span><span class="arrow">›</span></div>
      </div>
    </div>
  `,
};
