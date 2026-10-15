import { ref, onMounted } from 'vue';
import { api, toast } from '../api.js';
import { Navbar, Empty } from '../ui.js';
import { isLogin } from '../store.js';

export default {
    name: 'CouponCenter',
    components: { Navbar, Empty },
    setup() {
        const list = ref([]);
        const loading = ref(false);
        const receiving = ref(0);

        async function load() {
            loading.value = true;
            const r = await api.get('/api/coupon/center');
            loading.value = false;
            if (r.code === 0) list.value = r.data.list || [];
        }

        async function receive(c) {
            if (!isLogin()) {
                sessionStorage.setItem('ns_login_redirect', '#/coupon-center');
                location.hash = '#/login';
                return;
            }
            receiving.value = c.id;
            const r = await api.post('/api/coupon/receive', { id: c.id });
            receiving.value = 0;
            toast(r.message || '领取成功', r.code === 0 ? 'info' : 'warn');
            if (r.code === 0) load();
        }

        onMounted(load);

        return { list, loading, receiving, receive, isLogin };
    },
    template: `
    <div class="u-page no-tab">
      <Navbar title="领券中心"></Navbar>
      <Empty v-if="!list.length && !loading" icon="🎁" text="暂无可领取的优惠券"></Empty>
      <div class="u-coupon" v-for="c in list" :key="c.id">
        <div class="left">
          <template v-if="c.kind === 1">
            <div class="v">¥<small>{{ c.offAmount }}</small></div>
            <div style="font-size:11px">满{{ c.minAmount }}可用</div>
          </template>
          <template v-else>
            <div class="v"><small>{{ (c.offPercent / 10).toFixed(1) }}</small>折</div>
            <div style="font-size:11px">满{{ c.minAmount }}可用</div>
          </template>
        </div>
        <div class="right">
          <div class="name">{{ c.name }}</div>
          <div class="u-mut">
            <template v-if="c.stock > 0">剩余 {{ c.stock - c.sentCount }} 张</template>
            <template v-else>不限量</template>
            · 已领 {{ c.sentCount }} 张
          </div>
          <div class="row">
            <span class="u-tag" v-if="!c.soldOut">可领取</span>
            <span class="u-tag gray" v-else>已抢光</span>
            <button class="u-btn mini" v-if="!c.limitReached && !c.soldOut" :disabled="receiving === c.id" @click="receive(c)">
              {{ receiving === c.id ? '领取中' : c.limitReached ? '已达上限' : '立即领取' }}
            </button>
            <button class="u-btn mini plain" v-else-if="c.limitReached" disabled>已达领取上限</button>
            <button class="u-btn mini plain" v-else disabled>已抢光</button>
          </div>
        </div>
      </div>
    </div>
  `,
};
