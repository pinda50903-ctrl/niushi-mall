import { ref, computed, onMounted } from 'vue';
import { api, fmtMoney, toast } from '../api.js';
import { session, isLogin } from '../store.js';
import { Navbar, NumStep, Empty } from '../ui.js';

export default {
    name: 'CartPage',
    components: { Navbar, NumStep, Empty },
    props: { cartCount: { type: Number, default: 0 } },
    setup() {
        const list = ref([]);
        const amount = ref(0);
        const checked = ref([]);
        const loading = ref(false);

        async function load() {
            if (!isLogin()) { list.value = []; return; }
            loading.value = true;
            const r = await api.get('/api/cart/index');
            loading.value = false;
            if (r.code === 0) {
                list.value = r.data.list || [];
                amount.value = r.data.amount || 0;
                checked.value = list.value.filter((x) => !x.invalid).map((x) => x.id);
            }
        }

        const validItems = computed(() => list.value.filter((x) => !x.invalid));
        const checkedAmount = computed(() =>
            fmtMoney(validItems.value.filter((x) => checked.value.includes(x.id)).reduce((s, x) => s + Number(x.price) * x.num, 0))
        );

        function toggle(id) {
            const i = checked.value.indexOf(id);
            if (i >= 0) checked.value.splice(i, 1);
            else checked.value.push(id);
        }
        const allOn = computed(() => validItems.value.length > 0 && checked.value.length === validItems.value.length);
        function toggleAll() {
            checked.value = allOn.value ? [] : validItems.value.map((x) => x.id);
        }

        async function setNum(item, num) {
            const r = await api.post('/api/cart/update-num', { id: item.id, num });
            if (r.code === 0) load();
            else toast(r.message);
        }
        async function removeItem(item) {
            const r = await api.post('/api/cart/remove', { ids: [item.id] });
            if (r.code === 0) { toast('已删除'); load(); window.__refreshCartBadge && window.__refreshCartBadge(); }
        }
        function goCheckout() {
            if (!checked.value.length) return toast('请先选择商品');
            location.hash = '#/checkout?cartIds=' + checked.value.join(',');
        }

        onMounted(load);

        return { list, amount, checked, loading, validItems, checkedAmount, allOn, toggle, toggleAll, setNum, removeItem, goCheckout, fmtMoney, isLogin };
    },
    template: `
    <div class="u-page">
      <Navbar title="购物车"></Navbar>
      <template v-if="isLogin()">
        <Empty v-if="!list.length" icon="🛒" text="购物车还是空的，去逛逛吧"></Empty>
        <div style="height:10px" v-else></div>
        <div style="background:#fff;border-radius:10px;margin:0 12px;overflow:hidden">
          <div class="u-cart-item" v-for="item in list" :key="item.id">
            <span class="check" :class="{on: checked.includes(item.id) && !item.invalid}" v-if="!item.invalid" @click="toggle(item.id)">✓</span>
            <img :src="item.cover" alt="" @click="go('#/goods/' + item.goodsId)">
            <div class="mid">
              <div class="t" @click="go('#/goods/' + item.goodsId)">{{ item.title }}</div>
              <span class="sku" v-if="item.specText">{{ item.specText }}</span>
              <div class="bot">
                <span class="u-money">¥{{ item.price }}</span>
                <NumStep v-if="!item.invalid" :modelValue="item.num" :min="1" :max="item.stock || 999" @update:modelValue="(v) => setNum(item, v)"></NumStep>
                <button class="u-btn mini danger" v-else @click="removeItem(item)">删除</button>
              </div>
            </div>
          </div>
        </div>
        <div class="u-cart-bar" v-if="validItems.length">
          <span class="check" :class="{on: allOn}" style="width:20px;height:20px;border-radius:50%;border:1.5px solid #ccc;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;flex:none" :style="allOn ? 'background:var(--ns-primary);border-color:var(--ns-primary)' : ''" @click="toggleAll">✓</span>
          <span class="u-mut">全选</span>
          <span class="sum">合计 <span class="u-money">{{ checkedAmount }}</span></span>
          <button class="u-btn" style="height:40px;border-radius:20px;padding:0 26px" @click="goCheckout">去结算</button>
        </div>
      </template>
      <template v-else>
        <Empty icon="🔒" text="登录后查看购物车"></Empty>
        <div style="text-align:center"><button class="u-btn" @click="go('#/login')">去登录</button></div>
      </template>
    </div>
  `,
};
