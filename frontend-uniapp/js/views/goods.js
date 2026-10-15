import { ref, computed, onMounted } from 'vue';
import { api, fmtMoney, toast } from '../api.js';
import { NumStep } from '../ui.js';

export default {
    name: 'GoodsDetail',
    props: { id: { type: String, required: true } },
    components: { NumStep },
    setup(props) {
        const goods = ref(null);
        const slideIdx = ref(0);
        const skuShow = ref(false);
        const skuMode = ref('cart'); // cart | buy
        const skuId = ref(0);
        const num = ref(1);
        const buying = ref(false);

        async function load() {
            const r = await api.get('/api/goods/detail', { id: props.id });
            if (r.code === 0) {
                goods.value = r.data.goods;
                skuId.value = defaultSku();
            } else {
                toast(r.message || '商品不存在');
            }
        }
        function defaultSku() {
            const skus = goods.value?.skus || [];
            return skus.length ? (skus[0].id || 0) : 0;
        }
        const currentSku = computed(() => (goods.value?.skus || []).find((s) => s.id === skuId.value) || null);
        const displayPrice = computed(() => (currentSku.value ? Number(currentSku.value.price) : Number(goods.value?.price || 0)));
        const stock = computed(() => (currentSku.value ? Number(currentSku.value.stock) : Number(goods.value?.stock || 0)));

        function openSku(mode) {
            skuMode.value = mode;
            skuShow.value = true;
        }
        async function confirmSku() {
            if (stock.value < num.value) return toast('库存不足，仅剩 ' + stock.value);
            buying.value = true;
            if (skuMode.value === 'cart') {
                const r = await api.post('/api/cart/add', { goodsId: Number(props.id), skuId: skuId.value, num: num.value });
                buying.value = false;
                if (r.code === 0) {
                    skuShow.value = false;
                    toast('已加入购物车');
                    window.__refreshCartBadge && window.__refreshCartBadge();
                } else toast(r.message);
            } else {
                buying.value = false;
                skuShow.value = false;
                location.hash = `#/checkout?goodsId=${props.id}&skuId=${skuId.value}&num=${num.value}`;
            }
        }
        async function toggleFav() {
            const r = await api.post('/api/goods/favorite-toggle', { goodsId: Number(props.id) });
            if (r.code === 0) {
                goods.value.is_favorite = r.data.favorite;
                toast(r.data.favorite ? '已收藏' : '已取消收藏');
            } else toast(r.message);

        }
        onMounted(load);

        return { goods, slideIdx, skuShow, skuMode, skuId, num, buying, currentSku, displayPrice, stock, openSku, confirmSku, toggleFav, fmtMoney };
    },
    template: `
    <div class="u-page no-tab" v-if="goods" style="padding-bottom:70px">
      <div class="u-slide">
        <img :src="(goods.slides && goods.slides[slideIdx]) || goods.cover" alt="" @click="slideIdx = (slideIdx + 1) % Math.max(1, goods.slides.length)">
        <span class="idx">{{ (slideIdx + 1) }} / {{ Math.max(1, (goods.slides || []).length) }}</span>
      </div>
      <div class="u-gd-price">
        <span class="now">¥{{ displayPrice }}</span>
        <span class="line" v-if="Number(goods.line_price) > displayPrice">¥{{ goods.line_price }}</span>
        <span class="u-mut" style="margin-left:6px">已售 {{ Number(goods.sold_count) + Number(goods.virtual_sales) }}</span>
        <span class="fav" :class="{on: goods.is_favorite}" @click="toggleFav">{{ goods.is_favorite ? '❤' : '♡' }}<br>收藏</span>
      </div>
      <div class="u-gd-title">{{ goods.title }}</div>
      <div class="u-gd-sub" v-if="goods.subtitle">{{ goods.subtitle }}</div>

      <div class="u-card" style="display:flex;justify-content:space-between;align-items:center">
        <span class="u-mut">配送：{{ Number(goods.is_free_shipping) === 1 ? '包邮' : '按运费模板' }}</span>
        <span class="u-mut">库存 {{ stock }} 件</span>
      </div>

      <div class="u-card">
        <div class="u-section-title" style="padding:0 0 8px">商品详情</div>
        <div style="font-size:13px;line-height:1.7;color:#555;word-break:break-all">{{ goods.detail || '暂无详情' }}</div>
      </div>

      <div class="u-gd-bar">
        <router-link class="ico-btn" to="/"><span class="e">⌂</span>首页</router-link>
        <router-link class="ico-btn" to="/cart"><span class="e">🛒</span>购物车</router-link>
        <button class="act cart" @click="openSku('cart')">加入购物车</button>
        <button class="act buy" @click="openSku('buy')">立即购买</button>
      </div>

      <template v-if="skuShow">
        <div class="u-mask" @click="skuShow = false"></div>
        <div class="u-sheet">
          <div class="head">{{ skuMode === 'cart' ? '加入购物车' : '立即购买' }}<span class="close" @click="skuShow = false">✕</span></div>
          <div style="display:flex;gap:10px;padding:14px">
            <img :src="goods.cover" alt="" style="width:84px;height:84px;border-radius:8px;object-fit:cover;background:#f6f6f6">
            <div>
              <div class="u-money" style="font-size:20px">¥{{ displayPrice }}</div>
              <div class="u-mut" style="margin-top:4px">库存 {{ stock }} 件</div>
              <div class="u-mut" v-if="currentSku" style="margin-top:4px">已选：{{ currentSku.spec_text }}</div>
            </div>
          </div>
          <div style="padding:0 14px 10px" v-if="(goods.skus || []).length">
            <div style="font-size:13px;color:#666;margin-bottom:2px">规格</div>
            <span class="u-sku-item" v-for="s in goods.skus" :key="s.id" :class="{on: skuId === s.id}" @click="skuId = s.id; num = 1">
              {{ s.spec_text || ('规格#' + s.id) }}　¥{{ s.price }}
            </span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px">
            <span style="font-size:14px">购买数量</span>
            <NumStep :modelValue="num" :min="1" :max="Math.max(1, stock)" @update:modelValue="(v) => num = v"></NumStep>
          </div>
          <div style="padding:6px 14px 16px">
            <button class="u-btn block" :disabled="buying" @click="confirmSku">{{ skuMode === 'cart' ? '加入购物车' : '立即购买' }}</button>
          </div>
        </div>
      </template>
    </div>
    <div class="u-page no-tab" v-else>
      <div class="u-empty"><div class="big">🐮</div>加载中…</div>
    </div>
  `,
};
