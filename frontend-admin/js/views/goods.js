import { reactive, ref, onMounted } from 'vue';
const { ElMessage, ElMessageBox } = window.ElementPlus;
import { api, assetUrl, fmtMoney } from '../api.js';
import { usePageList, ImgUpload } from '../ui.js';

/* ============ 商品列表 ============ */
export const GoodsList = {
    name: 'GoodsList',
    components: { ImgUpload },
    setup() {
        const keyword = ref('');
        const status = ref('');
        const categories = ref([]);
        const { loading, list, total, page, pageSize, reload } = usePageList((p) =>
            api.get('/manage/goods/index', { ...p, keyword: keyword.value, status: status.value })
        );

        async function loadCats() {
            const r = await api.get('/manage/category/index');
            if (r.code === 0) {
                const flat = [];
                for (const p of r.data.list || []) {
                    flat.push({ id: p.id, name: p.name });
                    for (const c of p.children || []) flat.push({ id: c.id, name: '　' + c.name });
                }
                categories.value = flat;
            }
        }
        async function toggle(row) {
            const r = await api.post('/manage/goods/toggle', { id: row.id });
            if (r.code === 0) { ElMessage.success(r.message); reload(); }
        }
        async function del(row) {
            await ElMessageBox.confirm(`确定删除商品「${row.title}」？`, '提示', { type: 'warning' });
            const r = await api.post('/manage/goods/delete', { id: row.id });
            if (r.code === 0) { ElMessage.success(r.message); reload(); } else ElMessage.error(r.message);
        }
        onMounted(() => { loadCats(); reload(); });
        return { keyword, status, categories, loading, list, total, page, pageSize, reload, toggle, del, assetUrl, fmtMoney };
    },
    template: `
    <div class="ns-card tight">
      <div class="ns-filter">
        <el-input v-model="keyword" placeholder="商品标题" clearable style="width:220px" @keyup.enter="reload(1)"></el-input>
        <el-select v-model="status" placeholder="状态" clearable style="width:120px">
          <el-option label="上架" value="1"></el-option><el-option label="下架" value="0"></el-option>
        </el-select>
        <el-button type="primary" @click="reload(1)">查询</el-button>
        <div style="flex:1"></div>
        <el-button type="primary" @click="$router.push('/goods/create')">新增商品</el-button>
      </div>
    </div>
    <div class="ns-card">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column label="商品" min-width="280">
          <template #default="{row}">
            <div style="display:flex;gap:10px;align-items:center">
              <img class="ns-table-cover" :src="assetUrl(row.cover_url || row.cover)">
              <div><div class="ns-title-cell">{{ row.title }}</div><div class="ns-mut">ID {{ row.id }} · {{ row.category_id }}</div></div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="价格" width="110"><template #default="{row}"><span class="ns-money">{{ fmtMoney(row.price) }}</span></template></el-table-column>
        <el-table-column prop="stock" label="库存" width="80"></el-table-column>
        <el-table-column prop="virtual_sales" label="虚拟销量" width="90"></el-table-column>
        <el-table-column label="多规格" width="80"><template #default="{row}">{{ row.is_sku_enabled === 1 ? '是' : '否' }}</template></el-table-column>
        <el-table-column label="推荐" width="70"><template #default="{row}">{{ row.is_recommend === 1 ? '是' : '-' }}</template></el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{row}">
            <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">{{ row.status === 1 ? '上架' : '下架' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button link type="primary" @click="$router.push('/goods/' + row.id + '/edit')">编辑</el-button>
            <el-button link :type="row.status === 1 ? 'warning' : 'success'" @click="toggle(row)">{{ row.status === 1 ? '下架' : '上架' }}</el-button>
            <el-button link type="danger" @click="del(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="ns-pagebar">
        <el-pagination background layout="total, prev, pager, next, sizes" :total="total"
          v-model:current-page="page" v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50]" @current-change="reload()" @size-change="reload(1)"></el-pagination>
      </div>
    </div>
  `,
};

/* ============ 商品编辑 ============ */
export const GoodsEdit = {
    name: 'GoodsEdit',
    components: { ImgUpload },
    setup() {
        const id = Number((location.hash.match(/#\/goods\/(\d+)\/edit/) || [])[1] || 0);
        const form = reactive({
            id: 0, title: '', subtitle: '', categoryId: null, cover: '', slides: [], detail: '',
            unit: '件', price: 0, linePrice: 0, costPrice: 0, stock: 0, weight: 0, virtualSales: 0,
            isSkuEnabled: 0, isRecommend: 0, isFreeShipping: 0, status: 1, sort: 100, skus: [],
        });
        const categories = ref([]);
        const saving = ref(false);

        async function load() {
            const r = await api.get('/manage/category/index');
            if (r.code === 0) {
                const flat = [];
                for (const p of r.data.list || []) {
                    flat.push({ id: p.id, name: p.name });
                    for (const c of p.children || []) flat.push({ id: c.id, name: p.name + ' / ' + c.name });
                }
                categories.value = flat;
            }
            if (id > 0) {
                const res = await api.get('/manage/goods/view', { id });
                if (res.code === 0) {
                    const g = res.data.goods;
                    Object.assign(form, {
                        id: g.id, title: g.title, subtitle: g.subtitle, categoryId: g.category_id,
                        cover: g.cover, slides: g.slides || [], detail: g.detail, unit: g.unit || '件',
                        price: Number(g.price), linePrice: Number(g.line_price), costPrice: Number(g.cost_price),
                        stock: g.stock, weight: Number(g.weight), virtualSales: g.virtual_sales,
                        isSkuEnabled: g.is_sku_enabled, isRecommend: g.is_recommend, isFreeShipping: g.is_free_shipping,
                        status: g.status, sort: g.sort,
                        skus: (res.data.skus || []).map((s) => ({
                            id: s.id, specText: s.spec_text, price: Number(s.price),
                            linePrice: Number(s.line_price), stock: s.stock, weight: Number(s.weight), code: s.code,
                        })),
                    });
                }
            }
        }
        function addSku() {
            form.skus.push({ specText: '', price: 0, linePrice: 0, stock: 0, weight: 0, code: '' });
        }
        function removeSku(i) { form.skus.splice(i, 1); }

        async function save() {
            if (!form.title) return ElMessage.warning('请填写商品标题');
            if (!form.categoryId) return ElMessage.warning('请选择商品分类');
            saving.value = true;
            try {
                const r = await api.post('/manage/goods/save', form);
                if (r.code === 0) {
                    ElMessage.success('商品已保存');
                    location.hash = '#/goods';
                } else ElMessage.error(r.message);
            } finally { saving.value = false; }
        }
        load();
        return { id, form, categories, saving, save, addSku, removeSku };
    },
    template: `
    <div class="ns-card">
      <el-page-header @back="$router.back()" :content="id ? '编辑商品' : '新增商品'" style="margin-bottom:18px"></el-page-header>
      <el-form label-width="110px" style="max-width:760px">
        <el-form-item label="商品标题" required>
          <el-input v-model="form.title" maxlength="80" show-word-limit></el-input>
        </el-form-item>
        <el-form-item label="副标题"><el-input v-model="form.subtitle"></el-input></el-form-item>
        <el-form-item label="商品分类" required>
          <el-select v-model="form.categoryId" placeholder="选择分类" style="width:280px">
            <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="商品封面" required>
          <img-upload v-model="form.cover"></img-upload>
        </el-form-item>
        <el-form-item label="轮播图">
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <div v-for="(s,i) in form.slides" :key="i" style="position:relative">
              <img-upload :model-value="s" :width="90" :height="90"
                @update:model-value="(v)=>{ if(v) form.slides[i]=v; }"></img-upload>
              <el-button link type="danger" size="small" style="position:absolute;top:-6px;right:-6px" @click="form.slides.splice(i,1)">✕</el-button>
            </div>
            <img-upload :model-value="''" :width="90" :height="90" @update:model-value="(v)=>{ if(v) form.slides.push(v) }"></img-upload>
          </div>
        </el-form-item>
        <el-form-item label="价格">
          <el-input-number v-model="form.price" :min="0" :precision="2"></el-input-number>
          <span class="ns-mut" style="margin-left:8px">元</span>
        </el-form-item>
        <el-form-item label="划线价"><el-input-number v-model="form.linePrice" :min="0" :precision="2"></el-input-number></el-form-item>
        <el-form-item label="成本价"><el-input-number v-model="form.costPrice" :min="0" :precision="2"></el-input-number></el-form-item>
        <el-form-item label="库存"><el-input-number v-model="form.stock" :min="0" :step="1"></el-input-number></el-form-item>
        <el-form-item label="单位"><el-input v-model="form.unit" style="width:120px"></el-input></el-form-item>
        <el-form-item label="虚拟销量"><el-input-number v-model="form.virtualSales" :min="0"></el-input-number></el-form-item>
        <el-form-item label="多规格">
          <el-switch v-model="form.isSkuEnabled" :active-value="1" :inactive-value="0"></el-switch>
          <span class="ns-mut" style="margin-left:10px">开启后按 SKU 价格区间售卖</span>
        </el-form-item>
        <el-form-item v-if="form.isSkuEnabled === 1" label="规格列表">
          <el-table :data="form.skus" size="small" border style="width:100%">
            <el-table-column label="规格名"><template #default="{row}"><el-input v-model="row.specText" placeholder="如 红色/XL"></el-input></template></el-table-column>
            <el-table-column label="价格"><template #default="{row}"><el-input-number v-model="row.price" :min="0" :precision="2" controls-position="right" style="width:110px"></el-input-number></template></el-table-column>
            <el-table-column label="库存"><template #default="{row}"><el-input-number v-model="row.stock" :min="0" controls-position="right" style="width:100px"></el-input-number></template></el-table-column>
            <el-table-column label="编码"><template #default="{row}"><el-input v-model="row.code"></el-input></template></el-table-column>
            <el-table-column width="70"><template #default="{$index}"><el-button link type="danger" @click="removeSku($index)">删除</el-button></template></el-table-column>
          </el-table>
          <el-button size="small" style="margin-top:8px" @click="addSku">+ 添加规格</el-button>
        </el-form-item>
        <el-form-item label="开启推荐"><el-switch v-model="form.isRecommend" :active-value="1" :inactive-value="0"></el-switch></el-form-item>
        <el-form-item label="包邮"><el-switch v-model="form.isFreeShipping" :active-value="1" :inactive-value="0"></el-switch></el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :value="1">上架</el-radio><el-radio :value="0">下架</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="排序"><el-input-number v-model="form.sort" :min="0"></el-input-number><span class="ns-mut" style="margin-left:8px">越小越靠前</span></el-form-item>
        <el-form-item label="商品详情">
          <el-input v-model="form.detail" type="textarea" :rows="8" placeholder="支持 HTML"></el-input>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="saving" @click="save">保存商品</el-button>
          <el-button @click="$router.back()">取消</el-button>
        </el-form-item>
      </el-form>
    </div>
  `,
};
