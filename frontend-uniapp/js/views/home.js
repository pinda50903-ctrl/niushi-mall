import { ref, onMounted } from 'vue';
import { api } from '../api.js';

export default {
    name: 'HomePage',
    setup() {
        const data = ref(null);
        const bannerIdx = ref(0);
        let timer = null;

        onMounted(async () => {
            const r = await api.get('/api/home/index', { channel: 'h5', path: '/pages/index/index' });
            if (r.code === 0) {
                data.value = r.data;
                if ((r.data.banners || []).length > 1) {
                    timer = setInterval(() => {
                        bannerIdx.value = (bannerIdx.value + 1) % r.data.banners.length;
                    }, 3500);
                }
            }
        });

        return { data, bannerIdx };
    },
    template: `
    <div class="u-page" v-if="data">
      <div class="u-search">
        <router-link class="box" to="/category">🔍 搜索商品</router-link>
      </div>

      <div class="u-banner" v-if="(data.banners || []).length">
        <router-link :to="data.banners[bannerIdx]?.link?.startsWith('/') ? data.banners[bannerIdx].link : '/category'">
          <img :src="data.banners[bannerIdx]?.cover" alt="">
        </router-link>
        <div class="dots">
          <i v-for="(b, i) in data.banners" :key="i" :class="{on: i === bannerIdx}"></i>
        </div>
      </div>

      <div class="u-cats" v-if="(data.categories || []).length">
        <router-link class="u-cat" v-for="c in data.categories" :key="c.id" :to="'/category?id=' + c.id">
          <span class="ico"><img v-if="c.icon" :src="c.icon" alt=""><span v-else>🏷</span></span>
          <span>{{ c.name }}</span>
        </router-link>
      </div>

      <div class="u-section-title" style="background:#fff;border-radius:10px 10px 0 0;margin:10px 12px 0">
        <span>🔥 推荐商品</span>
      </div>
      <div class="u-goods-grid" style="margin-top:0;background:#fff;border-radius:0 0 10px 10px;padding:8px 8px 12px;width:auto">
        <router-link class="u-gcard" v-for="g in (data.recommend || [])" :key="g.id" :to="'/goods/' + g.id">
          <img class="cover" :src="g.cover" alt="" loading="lazy">
          <div class="info">
            <div class="title">{{ g.title }}</div>
            <div class="price-row">
              <span class="price">¥{{ g.price }}</span>
              <span class="line" v-if="g.linePrice > g.price">¥{{ g.linePrice }}</span>
              <span class="sold">已售 {{ g.soldCount }}</span>
            </div>
          </div>
        </router-link>
      </div>
      <div class="u-empty" v-if="!(data.recommend || []).length">暂无推荐商品</div>
    </div>
    <div class="u-page" v-else><div class="u-empty"><div class="big">🐮</div>加载中…</div></div>
  `,
};
