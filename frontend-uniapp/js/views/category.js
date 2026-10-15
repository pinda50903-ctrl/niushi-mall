import { ref, computed, onMounted } from 'vue';
import { api } from '../api.js';
import { Navbar, GoodsGrid, Empty } from '../ui.js';

export default {
    name: 'CategoryPage',
    components: { Navbar, GoodsGrid, Empty },
    setup() {
        const cats = ref([]);
        const currentId = ref(0);
        const list = ref([]);
        const total = ref(0);
        const page = ref(1);
        const loading = ref(false);
        const sortType = ref(0);
        const pageSize = 10;

        async function loadCats() {
            const r = await api.get('/api/category/index');
            if (r.code === 0) {
                cats.value = r.data.list || [];
                // 支持首页 ?id= 指定一级分类，默认停留在「全部」
                const want = Number(new URLSearchParams(location.hash.split('?')[1] || '').get('id') || 0);
                currentId.value = cats.value.some((c) => c.id === want) ? want : 0;
                reload();
            }
        }

        async function load(pageNo) {
            loading.value = true;
            if (pageNo) page.value = pageNo;
            const r = await api.get('/api/goods/list', { categoryId: currentId.value, page: page.value, pageSize, sortType: sortType.value });
            loading.value = false;
            if (r.code === 0) {
                list.value = page.value === 1 ? (r.data.list || []) : list.value.concat(r.data.list || []);
                total.value = r.data.total || 0;
            }
        }
        function selectCat(id) {
            currentId.value = id;
            reload();
        }
        function reload() {
            page.value = 1;
            load(1);
        }
        function setSort(v) {
            sortType.value = v;
            reload();
        }
        const hasMore = computed(() => list.value.length < total.value);

        onMounted(loadCats);

        return { cats, currentId, list, total, loading, sortType, hasMore, selectCat, setSort, load };
    },
    template: `
    <div>
      <Navbar title="全部分类"></Navbar>
      <div class="u-catpage">
        <div class="left">
          <div class="item" :class="{on: currentId === 0}" @click="selectCat(0)">全部</div>
          <div class="item" v-for="c in cats" :key="c.id" :class="{on: currentId === c.id}" @click="selectCat(c.id)">{{ c.name }}</div>
        </div>
        <div class="right">
          <div style="display:flex;gap:14px;padding:0 0 10px;font-size:12px;color:#666">
            <span :style="{color: sortType === 0 ? 'var(--ns-primary)' : ''}" @click="setSort(0)">综合</span>
            <span :style="{color: sortType === 1 ? 'var(--ns-primary)' : ''}" @click="setSort(1)">销量</span>
            <span :style="{color: sortType === 2 ? 'var(--ns-primary)' : ''}" @click="setSort(2)">价格↑</span>
            <span :style="{color: sortType === 3 ? 'var(--ns-primary)' : ''}" @click="setSort(3)">价格↓</span>
          </div>
          <Empty v-if="!list.length && !loading" text="该分类暂无商品"></Empty>
          <GoodsGrid :list="list"></GoodsGrid>
          <div class="u-mut" style="text-align:center;padding:8px 0 16px" v-if="list.length">
            {{ loading ? '加载中…' : (hasMore ? '上拉加载更多' : '— 没有更多了 —') }}
            <button v-if="hasMore && !loading" class="u-btn mini plain" style="margin-left:8px" @click="load(page + 1)">加载更多</button>
          </div>
        </div>
      </div>
    </div>
  `,
};
