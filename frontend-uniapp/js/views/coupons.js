import { ref, onMounted } from 'vue';
import { api, fmtDay } from '../api.js';
import { Navbar, Empty } from '../ui.js';

export default {
    name: 'CouponsPage',
    components: { Navbar, Empty },
    setup() {
        const TABS = [
            { key: 1, label: '未使用' },
            { key: 2, label: '已使用' },
            { key: 3, label: '已过期' },
        ];
        const tab = ref(1);
        const list = ref([]);
        const loading = ref(false);

        async function load() {
            loading.value = true;
            const r = await api.get('/api/coupon/mine', { status: tab.value });
            loading.value = false;
            if (r.code === 0) list.value = r.data.list || [];
        }
        function setTab(k) {
            tab.value = k;
            load();
        }
        onMounted(load);

        return { TABS, tab, list, loading, setTab, fmtDay };
    },
    template: `
    <div class="u-page no-tab">
      <Navbar title="我的优惠券"></Navbar>
      <div class="u-status-tabs">
        <span class="t" v-for="t in TABS" :key="t.key" :class="{on: tab === t.key}" @click="setTab(t.key)">{{ t.label }}</span>
      </div>
      <Empty v-if="!list.length && !loading" icon="🎟" text="暂无优惠券"></Empty>
      <div class="u-coupon" v-for="c in list" :key="c.id" :class="{off: c.status !== 1}">
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
          <div class="u-mut">有效期至 {{ fmtDay(c.expireAt) }}</div>
          <div class="row">
            <span class="u-tag" :class="{gray: c.status !== 1}">{{ c.statusLabel }}</span>
            <button class="u-btn mini plain" v-if="c.status === 1" @click="go('#/category')">去使用</button>
          </div>
        </div>
      </div>
    </div>
  `,
};
