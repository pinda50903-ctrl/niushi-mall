import { ref, computed } from 'vue';

/** 顶部导航（带返回键） */
export const Navbar = {
    name: 'Navbar',
    props: { title: { type: String, default: '' } },
    emits: ['back'],
    setup(props, { emit }) {
        function back() {
            if (window.history.length > 1) window.history.back();
            else location.hash = '#/';
        }
        return { back };
    },
    template: `
    <div class="u-navbar">
      <span class="back" @click="back">‹</span>
      <span class="title">{{ title }}</span>
    </div>
  `,
};

/** 数量步进器 */
export const NumStep = {
    name: 'NumStep',
    props: { modelValue: { type: Number, default: 1 }, min: { type: Number, default: 1 }, max: { type: Number, default: 999 } },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        function set(v) {
            v = Math.max(props.min, Math.min(props.max, v));
            emit('update:modelValue', v);
        }
        return { set };
    },
    template: `
    <span class="u-num-step">
      <button :disabled="modelValue <= min" @click="set(modelValue - 1)">−</button>
      <span>{{ modelValue }}</span>
      <button :disabled="modelValue >= max" @click="set(modelValue + 1)">＋</button>
    </span>
  `,
};

const GoodsCard = {
    name: 'GoodsCard',
    props: { goods: { type: Object, required: true } },
    template: `
    <router-link class="u-gcard" :to="'/goods/' + goods.id">
      <img class="cover" :src="goods.cover" alt="" loading="lazy">
      <div class="info">
        <div class="title">{{ goods.title }}</div>
        <div class="price-row">
          <span class="price">¥{{ goods.price }}</span>
          <span class="line" v-if="goods.linePrice > goods.price">¥{{ goods.linePrice }}</span>
          <span class="sold">已售 {{ goods.soldCount }}</span>
        </div>
      </div>
    </router-link>
  `,
};

export const GoodsGrid = {
    name: 'GoodsGrid',
    components: { GoodsCard },
    props: { list: { type: Array, default: () => [] } },
    template: `
    <div class="u-goods-grid">
      <GoodsCard v-for="g in list" :key="g.id" :goods="g"></GoodsCard>
    </div>
  `,
};

export const Empty = {
    name: 'UEmpty',
    props: { text: { type: String, default: '暂无数据' }, icon: { type: String, default: '📭' } },
    template: `<div class="u-empty"><div class="big">{{ icon }}</div>{{ text }}</div>`,
};

/** 订单状态 */
export const ORDER_STATUS = {
    10: { label: '待付款', tag: 'warn' },
    20: { label: '待发货', tag: '' },
    30: { label: '待收货', tag: '' },
    40: { label: '已完成', tag: 'gray' },
    50: { label: '已关闭', tag: 'gray' },
};
