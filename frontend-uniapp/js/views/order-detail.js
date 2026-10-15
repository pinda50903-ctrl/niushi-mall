import { ref, onMounted } from 'vue';
import { api, fmtMoney, fmtTime, toast } from '../api.js';
import { Navbar, Empty } from '../ui.js';

export default {
    name: 'OrderDetailPage',
    props: { id: { type: String, required: true } },
    components: { Navbar, Empty },
    setup(props) {
        const order = ref(null);

        async function load() {
            const r = await api.get('/api/order/detail', { id: props.id });
            if (r.code === 0) order.value = r.data.order;
        }
        async function cancel() {
            const r = await api.post('/api/order/cancel', { id: Number(props.id) });
            if (r.code === 0) { toast('已取消'); load(); } else toast(r.message);
        }
        async function receipt() {
            const r = await api.post('/api/order/receipt', { id: Number(props.id) });
            if (r.code === 0) { toast('已确认收货'); load(); } else toast(r.message);
        }
        const DELIVERY_LABELS = { 0: '快递配送', 1: '到店自提' };

        onMounted(load);

        return { order, cancel, receipt, DELIVERY_LABELS, fmtMoney, fmtTime };
    },
    template: `
    <div class="u-page no-tab" v-if="order">
      <div style="background:linear-gradient(160deg, var(--ns-primary), var(--ns-primary-dark));color:#fff;padding:18px 16px;font-size:16px;font-weight:600">
        {{ order.status_label }}
        <span class="u-mut" style="color:rgba(255,255,255,.8);font-weight:400;margin-left:8px">{{ order.order_no }}</span>
      </div>

      <div class="u-card" v-if="Number(order.delivery_type) === 0 && order.receiver_name">
        <div style="font-size:15px;font-weight:600">📍 {{ order.receiver_name }} {{ order.receiver_phone }}</div>
        <div class="u-mut" style="margin-top:4px">{{ order.receiver_region }} {{ order.receiver_address }}</div>
      </div>
      <div class="u-card" v-else-if="Number(order.delivery_type) === 1">
        <div style="font-size:14px">🏬 到店自提</div>
        <div class="u-mut" style="margin-top:4px">下单后凭订单号到店提货</div>
      </div>

      <div class="u-card">
        <div v-for="it in (order.items || [])" :key="it.id" style="display:flex;gap:10px;padding:8px 0;align-items:center">
          <img :src="it.cover" alt="" style="width:64px;height:64px;border-radius:8px;object-fit:cover;background:#f6f6f6">
          <div style="flex:1;min-width:0">
            <div style="font-size:13px">{{ it.title }}</div>
            <div class="u-mut" v-if="it.spec_text">{{ it.spec_text }}</div>
            <div class="u-tag danger" v-if="Number(it.refund_state) > 0">售后中/已售后</div>
          </div>
          <div style="text-align:right">
            <div class="u-money" style="font-size:14px">¥{{ it.unit_price }}</div>
            <div class="u-mut">× {{ it.num }}</div>
          </div>
          <button class="u-btn mini plain" v-if="Number(it.refund_state) === 0 && [20, 30, 40].includes(Number(order.status))"
                  @click="go('#/refund-apply/' + it.id)">申请售后</button>
        </div>
        <div style="text-align:right;font-size:13px;padding-top:8px">
          实付 <span class="u-money" style="font-size:16px">¥{{ order.pay_amount }}</span>
          <span class="u-mut" style="margin-left:8px" v-if="Number(order.integral_deduction) > 0">积分抵扣 ¥{{ order.integral_deduction }}</span>
        </div>
      </div>

      <div class="u-group">
        <div class="u-cell"><span class="label">订单编号</span><span class="value">{{ order.order_no }}</span></div>
        <div class="u-cell"><span class="label">下单时间</span><span class="value">{{ fmtTime(order.created_at) }}</span></div>
        <div class="u-cell" v-if="Number(order.paid_at) > 0"><span class="label">支付时间</span><span class="value">{{ fmtTime(order.paid_at) }}</span></div>
        <div class="u-cell" v-if="order.express_no"><span class="label">快递单号</span><span class="value">{{ order.express_no }}</span></div>
        <div class="u-cell"><span class="label">配送方式</span><span class="value">{{ DELIVERY_LABELS[Number(order.delivery_type)] }}</span></div>
        <div class="u-cell" v-if="order.buyer_message"><span class="label">买家留言</span><span class="value">{{ order.buyer_message }}</span></div>
        <div class="u-cell" v-if="order.seller_remark"><span class="label">商家备注</span><span class="value">{{ order.seller_remark }}</span></div>
      </div>

      <div style="display:flex;gap:10px;padding:4px 12px 12px">
        <button class="u-btn plain" style="flex:1" v-if="Number(order.status) === 10" @click="cancel">取消订单</button>
        <button class="u-btn warn" style="flex:1" v-if="Number(order.status) === 10" @click="go('#/pay/' + order.id)">去支付</button>
        <button class="u-btn" style="flex:1" v-if="Number(order.status) === 30" @click="receipt">确认收货</button>
      </div>
    </div>
    <div class="u-page no-tab" v-else><Empty text="加载中…"></Empty></div>
  `,
};
