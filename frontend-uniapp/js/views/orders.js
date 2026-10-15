import { ref, onMounted } from 'vue';
import { api, fmtMoney, fmtTime, toast } from '../api.js';
import { Navbar, Empty, ORDER_STATUS } from '../ui.js';

export default {
    name: 'OrdersPage',
    components: { Navbar, Empty },
    setup() {
        const TABS = [
            { key: '', label: '全部' },
            { key: '10', label: '待付款' },
            { key: '20', label: '待发货' },
            { key: '30', label: '待收货' },
            { key: '40', label: '已完成' },
        ];
        const qs = new URLSearchParams(location.hash.split('?')[1] || '');
        const init = qs.get('status') || '';
        const tab = ref(init === 'refund' ? '' : init);
        const list = ref([]);
        const loading = ref(false);

        async function load() {
            loading.value = true;
            const params = {};
            if (tab.value) params.status = tab.value;
            if (init === 'refund') params.status = '';
            const r = await api.get('/api/order/list', params);
            loading.value = false;
            if (r.code === 0) list.value = r.data.list || [];
        }
        function setTab(k) {
            tab.value = k;
            load();
        }
        async function cancel(order) {
            const r = await api.post('/api/order/cancel', { id: order.id });
            if (r.code === 0) { toast('已取消'); load(); } else toast(r.message);
        }
        async function receipt(order) {
            const r = await api.post('/api/order/receipt', { id: order.id });
            if (r.code === 0) { toast('已确认收货'); load(); } else toast(r.message);
        }
        function pay(order) {
            location.hash = '#/pay/' + order.id;
        }

        onMounted(load);

        return { TABS, tab, list, loading, setTab, cancel, receipt, pay, ORDER_STATUS, fmtMoney, fmtTime };
    },
    template: `
    <div class="u-page">
      <Navbar title="我的订单"></Navbar>
      <div class="u-status-tabs">
        <span class="t" v-for="t in TABS" :key="t.key" :class="{on: tab === t.key}" @click="setTab(t.key)">{{ t.label }}</span>
      </div>
      <Empty v-if="!list.length && !loading" icon="📋" text="暂无相关订单"></Empty>
      <div class="u-order-card" v-for="o in list" :key="o.id">
        <div class="top">
          <span>{{ fmtTime(o.created_at) }}</span>
          <span class="st">{{ o.status_label }}</span>
        </div>
        <div class="goods" v-for="it in (o.items || [])" :key="it.id" @click="go('#/order/' + o.id)">
          <img :src="it.cover" alt="">
          <span class="n">{{ it.title }}<span class="u-mut" v-if="it.spec_text">（{{ it.spec_text }}）</span></span>
          <span class="p">
            <span>¥{{ it.unit_price }}</span><br>
            <span class="u-mut">× {{ it.num }}</span>
          </span>
        </div>
        <div style="text-align:right;font-size:13px;padding-top:6px">
          实付 <span class="u-money">¥{{ o.pay_amount }}</span>
        </div>
        <div class="ops">
          <button class="u-btn mini plain" v-if="Number(o.status) === 10" @click="cancel(o)">取消订单</button>
          <button class="u-btn mini warn" v-if="Number(o.status) === 10" @click="pay(o)">去支付</button>
          <button class="u-btn mini" v-if="Number(o.status) === 30" @click="receipt(o)">确认收货</button>
          <button class="u-btn mini plain" @click="go('#/order/' + o.id)">查看详情</button>
        </div>
      </div>
    </div>
  `,
};
