import { ref, computed, onMounted } from 'vue';
import { api, fmtMoney, toast } from '../api.js';
import { Navbar } from '../ui.js';

export default {
    name: 'CheckoutPage',
    components: { Navbar },
    setup() {
        const qs = new URLSearchParams(location.hash.split('?')[1] || '');
        const query = {
            cartIds: qs.get('cartIds') || '',
            goodsId: Number(qs.get('goodsId') || 0),
            skuId: Number(qs.get('skuId') || 0),
            num: Number(qs.get('num') || 1),
        };

        const preview = ref(null);
        const addresses = ref([]);
        const addressId = ref(0);
        const deliveryType = ref(0); // 0 快递 1 自提
        const userCouponId = ref(0);
        const integral = ref(0);
        const buyerMessage = ref('');
        const submitting = ref(false);
        const couponShow = ref(false);

        async function load() {
            const params = query.goodsId > 0
                ? { goodsId: query.goodsId, skuId: query.skuId, num: query.num }
                : { cartIds: query.cartIds };
            const [p, a] = await Promise.all([
                api.get('/api/order/preview', params),
                api.get('/api/user/addresses'),
            ]);
            if (p.code === 0) {
                preview.value = p.data;
                integral.value = 0;
            } else toast(p.message);
            if (a.code === 0) {
                addresses.value = a.data.list || [];
                const def = addresses.value.find((x) => Number(x.is_default) === 1) || addresses.value[0];
                addressId.value = def ? Number(def.id) : 0;
            }
        }

        const chosenCoupon = computed(() => (preview.value?.usableCoupons || []).find((c) => c.id === userCouponId.value) || null);

        // 金额试算（与后端规则近似展示，实际以后端提交结果为准）
        const couponDeduction = computed(() => {
            const c = chosenCoupon.value;
            const goodsAmount = Number(preview.value?.goodsAmount || 0);
            if (!c || goodsAmount < Number(c.minAmount || 0)) return 0;
            return c.kind === 2 ? Math.min(goodsAmount, Math.round(goodsAmount * (100 - Number(c.offPercent)) ) / 100) : Number(c.offAmount);
        });
        const integralDeduction = computed(() => {
            const rate = Number(preview.value?.integralRate || 0);
            if (rate <= 0) return 0;
            return Math.min(integral.value, maxIntegral.value) / 100 * rate;
        });
        const maxIntegral = computed(() => Number((JSON.parse(localStorage.getItem('ns_user_session') || '{}').profile || {}).integral || 0));
        const payAmount = computed(() => {
            const g = Number(preview.value?.goodsAmount || 0);
            return Math.max(0, Math.round((g - couponDeduction.value - integralDeduction.value) * 100) / 100);
        });

        async function submit() {
            if (submitting.value) return;
            if (deliveryType.value === 0 && !addressId.value) return toast('请先添加收货地址');
            submitting.value = true;
            const payload = {
                deliveryType: deliveryType.value,
                addressId: addressId.value,
                userCouponId: userCouponId.value,
                integral: Math.min(integral.value, maxIntegral.value),
                buyerMessage: buyerMessage.value,
                source: 'h5',
            };
            if (query.goodsId > 0) {
                payload.goodsId = query.goodsId;
                payload.skuId = query.skuId;
                payload.num = query.num;
            } else {
                payload.cartIds = query.cartIds.split(',').map(Number).filter(Boolean);
            }
            const r = await api.post('/api/order/submit', payload);
            submitting.value = false;
            if (r.code === 0) {
                toast('下单成功');
                window.__refreshCartBadge && window.__refreshCartBadge();
                location.hash = '#/pay/' + r.data.order.id;
            } else {
                toast(r.message || '下单失败');
            }
        }

        function goAddAddress() {
            location.hash = '#/address-edit/new?redirect=/checkout';
        }

        onMounted(load);

        return { preview, addresses, addressId, deliveryType, userCouponId, integral, buyerMessage, submitting, couponShow, chosenCoupon, couponDeduction, integralDeduction, maxIntegral, payAmount, submit, goAddAddress, fmtMoney };
    },
    template: `
    <div class="u-page no-tab">
      <Navbar title="确认订单"></Navbar>

      <div class="u-card" v-if="deliveryType === 0" @click="go('#/address?pick=1')">
        <template v-if="addresses.find((x) => Number(x.id) === Number(addressId))">
          <div style="font-size:15px;font-weight:600">
            {{ addresses.find((x) => Number(x.id) === Number(addressId)).receiver }}
            <span style="font-weight:400;color:#666;margin-left:8px">{{ addresses.find((x) => Number(x.id) === Number(addressId)).phone }}</span>
          </div>
          <div class="u-mut" style="margin-top:4px">📍 {{ addresses.find((x) => Number(x.id) === Number(addressId)).region }} {{ addresses.find((x) => Number(x.id) === Number(addressId)).address }}</div>
        </template>
        <template v-else>
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font-size:18px">📍</span><span>请选择收货地址</span><span class="arrow" style="margin-left:auto;color:#ccc">›</span>
          </div>
        </template>
      </div>

      <div class="u-form">
        <div class="row">
          <span class="lb">配送方式</span>
          <span class="u-radio" :class="{on: deliveryType === 0}" @click="deliveryType = 0"><span class="dot"></span>快递配送</span>
          <span class="u-radio" :class="{on: deliveryType === 1}" @click="deliveryType = 1"><span class="dot"></span>到店自提</span>
        </div>
      </div>

      <div class="u-card" v-if="preview">
        <div v-for="(it, i) in preview.items" :key="i" style="display:flex;gap:10px;padding:6px 0;align-items:center">
          <img :src="it.cover" alt="" style="width:56px;height:56px;border-radius:8px;object-fit:cover;background:#f6f6f6">
          <div style="flex:1">
            <div style="font-size:13px">{{ it.title }}</div>
            <div class="u-mut" v-if="it.specText">{{ it.specText }}</div>
          </div>
          <div style="text-align:right">
            <div class="u-mut">× {{ it.num }}</div>
            <div class="u-money" style="font-size:14px">¥{{ it.price }}</div>
          </div>
        </div>
      </div>

      <div class="u-form" v-if="preview">
        <div class="row" @click="couponShow = true" style="cursor:pointer">
          <span class="lb">优惠券</span>
          <span class="value" style="flex:1;color:var(--ns-danger)" v-if="chosenCoupon">{{ chosenCoupon.name }}（-¥{{ couponDeduction }}）</span>
          <span class="value" style="flex:1" v-else-if="(preview.usableCoupons || []).length">{{ preview.usableCoupons.length }} 张可用</span>
          <span class="value" style="flex:1" v-else>暂无可用</span>
          <span class="arrow" style="color:#ccc">›</span>
        </div>
        <div class="row" v-if="maxIntegral > 0">
          <span class="lb">积分抵扣</span>
          <input type="number" :min="0" :max="maxIntegral" :value="integral" @input="integral = Math.min(maxIntegral, Math.max(0, Number($event.target.value) || 0))" placeholder="可用积分">
          <span class="value">最多 {{ maxIntegral }}</span>
        </div>
        <div class="row">
          <span class="lb">买家留言</span>
          <input :value="buyerMessage" @input="buyerMessage = $event.target.value" maxlength="60" placeholder="选填，给商家留言">
        </div>
      </div>

      <div class="u-card" style="position:sticky;bottom:0;display:flex;align-items:center;justify-content:flex-end;gap:8px">
        <span class="u-mut">共 {{ (preview?.items || []).reduce((s, x) => s + x.num, 0) }} 件，合计：</span>
        <span class="u-money" style="font-size:20px">¥{{ payAmount }}</span>
        <button class="u-btn" :disabled="submitting" @click="submit" style="height:42px;padding:0 28px">提交订单</button>
      </div>

      <template v-if="couponShow">
        <div class="u-mask" @click="couponShow = false"></div>
        <div class="u-sheet">
          <div class="head">选择优惠券<span class="close" @click="couponShow = false">✕</span></div>
          <div style="padding-bottom:12px">
            <div class="u-coupon" :class="{off: userCouponId === 0}" @click="userCouponId = 0; couponShow = false" style="cursor:pointer">
              <div class="left"><span class="v">不使用</span></div>
              <div class="right"><span class="name">暂不使用优惠券</span></div>
            </div>
            <div class="u-coupon" v-for="c in (preview?.usableCoupons || [])" :key="c.id" :class="{off: false}" @click="userCouponId = c.id; couponShow = false" style="cursor:pointer">
              <div class="left">
                <span class="v" v-if="c.kind === 1"><small>¥</small>{{ c.offAmount }}</span>
                <span class="v" v-else>{{ 100 - c.offPercent }}<small>折</small></span>
              </div>
              <div class="right">
                <span class="name">{{ c.name }}</span>
                <span class="u-mut">满 {{ c.minAmount }} 可用 · 有效期至 {{ new Date(c.expireAt * 1000).toLocaleDateString('zh-CN') }}</span>
                <div class="row"><span class="u-tag" v-if="userCouponId === c.id">已选择</span></div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  `,
};
