import { ref, onMounted } from 'vue';
import { api, fmtTime, toast } from '../api.js';
import { Navbar, Empty, GoodsGrid } from '../ui.js';

export default {
    name: 'FavoritesPage',
    components: { Navbar, Empty, GoodsGrid },
    setup() {
        const TABS = [
            { key: 'fav', label: '我的收藏' },
            { key: 'browse', label: '浏览足迹' },
        ];
        const tab = ref('fav');
        const list = ref([]);
        const times = ref({});
        const loading = ref(false);

        async function load() {
            loading.value = true;
            if (tab.value === 'fav') {
                const r = await api.get('/api/goods/favorites');
                if (r.code === 0) {
                    list.value = (r.data.list || []).map((row) => row.goods).filter(Boolean);
                }
            } else {
                const r = await api.get('/api/goods/browse');
                if (r.code === 0) {
                    const rows = r.data.list || [];
                    list.value = rows.map((row) => row.goods).filter(Boolean);
                    times.value = {};
                    rows.forEach((row) => {
                        if (row.goods) times.value[row.goods.id] = row.time;
                    });
                }
            }
            loading.value = false;
        }
        function setTab(k) {
            tab.value = k;
            load();
        }
        async function remove(g) {
            const r = await api.post('/api/goods/favorite-toggle', { goodsId: g.id });
            if (r.code === 0) {
                toast('已取消收藏');
                list.value = list.value.filter((x) => x.id !== g.id);
            } else toast(r.message);
        }

        onMounted(load);

        return { TABS, tab, list, times, loading, setTab, remove, fmtTime };
    },
    template: `
    <div class="u-page no-tab">
      <Navbar title="我的收藏"></Navbar>
      <div class="u-status-tabs">
        <span class="t" v-for="t in TABS" :key="t.key" :class="{on: tab === t.key}" @click="setTab(t.key)">{{ t.label }}</span>
      </div>
      <Empty v-if="!list.length && !loading" :icon="tab === 'fav' ? '❤️' : '🕘'" :text="tab === 'fav' ? '还没有收藏商品' : '暂无浏览足迹'"></Empty>
      <template v-else>
        <div v-if="tab === 'browse' && list.length" class="u-mut" style="padding:8px 14px">最近浏览 {{ list.length }} 件商品</div>
        <GoodsGrid :list="list"></GoodsGrid>
        <div style="padding:0 12px 20px" v-if="tab === 'fav'">
          <button class="u-btn block plain" @click="go('#/category')">去逛逛</button>
        </div>
      </template>
    </div>
  `,
};
