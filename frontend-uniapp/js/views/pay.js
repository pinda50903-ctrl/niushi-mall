import { ref, onMounted } from 'vue';
import { api, fmtMoney, toast } from '../api.js';
import { Navbar } from '../ui.js';

export default {
    name: 'PayPage',
    props: { orderId: { type: String, required: true } },
    components: { Navbar },
    setup(props) {
        const order = ref(null);
        const channels = ref([]);
        const channel = ref('wechat');
        const paying = ref(false);
        const paid = ref(false);

        async function load() {
            const r = await api.get('/api/order/detail', { id: props.orderId });
            if (r.code === 0) {
                order.value = r.data.order;
                if (Number(order.value.status) !== 10) paid.value = true;
            } else toast(r.message);
            channels.value = ['wechat', 'alipay', 'balance'];
        }

        const CHANNEL_LABELS = { wechat: '微信支付', alipay: '支付宝', balance: '余额支付' };

        async function pay() {
            paying.value = true;
            // 1. 预支付
            const pre = await api.post('/api/pay/prepay', { orderId: Number(props.orderId), channel: channel.value });
            if (pre.code !== 0) {
                paying.value = false;
                return toast(pre.message || '预支付失败');
            }
            // 2. 0元单 / 余额支付已同步完成
            if (pre.data.paid) {
                paying.value = false;
                paid.value = true;
                toast('支付成功');
                return;
            }
            // 3. 模拟支付通道（未配置商户号时）
            if (pre.data.mock && pre.data.payNo) {
                const m = await api.post('/api/pay/mock', { payNo: pre.data.payNo });
                paying.value = false;
                if (m.code === 0) {
                    paid.value = true;
                    toast('支付成功');
                } else toast(m.message || '支付失败');
                return;
            }
            paying.value = false;
            toast('支付通道未就绪');
        }

        function goOrder() {
            location.hash = '#/order/' + props.orderId;
        }

        onMounted(load);

        return { order, channels, channel, paying, paid, pay, goOrder, CHANNEL_LABELS, fmtMoney };
    },
    template: `
    <div class="u-page no-tab">
      <Navbar title="收银台"></Navbar>
      <div class="u-card" style="text-align:center;padding:30px 12px" v-if="order">
        <template v-if="paid">
          <div style="font-size:44px">✅</div>
          <div style="font-size:17px;font-weight:600;margin-top:8px">支付成功</div>
        </template>
        <template v-else>
          <div class="u-mut">应付金额</div>
          <div class="u-money" style="font-size:34px;margin-top:6px">{{ fmtMoney(order.pay_amount) }}</div>
          <div class="u-mut" style="margin-top:6px">订单号 {{ order.order_no }}</div>
        </template>
      </div>

      <div class="u-group" v-if="!paid && order">
        <div class="u-cell" v-for="c in channels" :key="c" @click="channel = c" style="cursor:pointer">
          <span>{{ c === 'wechat' ? '💚' : (c === 'alipay' ? '💙' : '💰') }}</span>
          <span class="label">{{ CHANNEL_LABELS[c] || c }}</span>
          <span class="u-radio" :class="{on: channel === c}"><span class="dot"></span></span>
        </div>
      </div>

      <div style="padding:6px 12px" v-if="order && !paid">
        <button class="u-btn block" style="height:44px" :disabled="paying" @click="pay">{{ paying ? '支付中…' : '立即支付' }}</button>
      </div>
      <div style="padding:6px 12px">
        <button class="u-btn block plain" @click="goOrder">{{ paid ? '查看订单' : '稍后支付' }}</button>
      </div>
    </div>
  `,
};
