import { reactive, ref, onMounted, computed } from 'vue';
const { ElMessage, ElMessageBox } = window.ElementPlus;
import { api, fmtTime, fmtMoney } from '../api.js';
import { usePageList } from '../ui.js';
import { PLUGIN_META, CATEGORY_LABELS, pluginName } from '../plugin-meta.js';
import { session } from '../store.js';

/* ============ 插件列表（商城端） ============ */
export const PluginList = {
    name: 'PluginList',
    setup() {
        const owned = computed(() => session.profile?.pluginCodes || []);
        const groups = computed(() => {
            const g = {};
            for (const meta of PLUGIN_META) {
                if (!owned.value.includes(meta.code)) continue;
                (g[meta.category] = g[meta.category] || []).push(meta);
            }
            return g;
        });
        return { groups, CATEGORY_LABELS, pluginName };
    },
    template: `
    <div class="ns-card tight">
      <div class="ns-filter"><span style="font-weight:600">我的插件</span>
        <span class="ns-mut">由平台总部授权开通，点击卡片进入管理</span></div>
    </div>
    <div class="ns-card" v-for="(items, cat) in groups" :key="cat">
      <div style="font-weight:700;margin-bottom:12px">{{ CATEGORY_LABELS[cat] || cat }}</div>
      <div class="plugin-grid">
        <div v-for="p in items" :key="p.code" class="plugin-card" @click="$router.push('/plugin/' + p.code)">
          <div class="plugin-icon">{{ p.name[0] }}</div>
          <div><div style="font-weight:600">{{ p.name }}</div>
            <div class="ns-mut" style="margin-top:4px">{{ p.code }}</div></div>
        </div>
      </div>
    </div>
    <el-empty v-if="!Object.keys(groups).length" description="暂无已开通插件，请联系平台总部授权"></el-empty>
  `,
};

/* ============ 通用 KV 配置编辑器（46 个配置型插件） ============ */
function useConfigEditor(code) {
    const config = ref({});
    const loading = ref(false);
    const saving = ref(false);
    async function load() {
        loading.value = true;
        try {
            const r = await api.get(`/${code}/admin/config`);
            if (r.code === 0) config.value = r.data.config || {};
        } finally { loading.value = false; }
    }
    async function save() {
        saving.value = true;
        try {
            const clean = {};
            for (const [k, v] of Object.entries(config.value)) {
                if (typeof v === 'string' && v !== '' && !isNaN(Number(v)) && /^[\d.]+$/.test(v)) clean[k] = Number(v);
                else clean[k] = v;
            }
            const r = await api.post(`/${code}/admin/save-config`, { config: clean });
            if (r.code === 0) { ElMessage.success('配置已保存'); load(); } else ElMessage.error(r.message);
        } finally { saving.value = false; }
    }
    return { config, loading, saving, load, save };
}

export const PluginGeneric = {
    name: 'PluginGeneric',
    props: { code: String },
    setup(props) {
        const ed = useConfigEditor(props.code);
        onMounted(ed.load);
        const typeOf = (v) => typeof v === 'boolean' ? 'bool' : (typeof v === 'number' ? 'num' : (Array.isArray(v) || (typeof v === 'object' && v !== null) ? 'json' : 'str'));
        return { ...ed, pluginName, typeOf };
    },
    template: `
    <div class="ns-card tight">
      <el-page-header @back="$router.push('/plugins')" :content="pluginName(code) + ' · 插件配置'" style="margin-bottom:6px"></el-page-header>
    </div>
    <div class="ns-card" v-loading="loading" style="max-width:720px">
      <template v-if="Object.keys(config).length">
        <el-form label-width="180px">
          <el-form-item v-for="(v, k) in config" :key="k" :label="k">
            <el-switch v-if="typeOf(v) === 'bool'" v-model="config[k]"></el-switch>
            <el-input-number v-else-if="typeOf(v) === 'num'" v-model="config[k]" :step="1" controls-position="right"></el-input-number>
            <el-input v-else-if="typeOf(v) === 'json'" v-model="config[k]" type="textarea" :rows="3"
              :model-value="JSON.stringify(v)" @update:model-value="(s)=>{ try{ config[k]=JSON.parse(s||'null') }catch(e){} }"></el-input>
            <el-input v-else v-model="config[k]"></el-input>
          </el-form-item>
        </el-form>
        <el-button type="primary" :loading="saving" @click="save">保存配置</el-button>
      </template>
      <el-empty v-else description="该插件暂无可配置项"></el-empty>
    </div>
  `,
};

/* ============ 拼团 ============ */
export const PluginGroupon = {
    name: 'PluginGroupon',
    setup() {
        const tab = ref('activity');
        const ed = useConfigEditor('groupon');
        const { loading, list, total, page, pageSize, reload } = usePageList((p) => api.get('/groupon/admin/activities', p));
        const box = reactive({ show: false, form: {} });
        const goodsOptions = ref([]);

        function openCreate() {
            Object.assign(box, {
                show: true,
                form: { goodsId: null, title: '', groupPrice: 0, groupSize: 3, timeLimitHours: 24, stock: 100, startAt: '', endAt: '' },
            });
        }
        async function loadGoods() {
            const r = await api.get('/manage/goods/index', { pageSize: 100, status: 1 });
            if (r.code === 0) goodsOptions.value = r.data.list || [];
        }
        async function saveActivity() {
            if (!box.form.goodsId) return ElMessage.warning('请选择商品');
            if (!box.form.groupPrice) return ElMessage.warning('请填写拼团价');
            const f = { ...box.form };
            if (f.startAt) f.startAt = String(f.startAt).replace('T', ' ');
            if (f.endAt) f.endAt = String(f.endAt).replace('T', ' ');
            const r = await api.post('/groupon/admin/save-activity', f);
            if (r.code === 0) { ElMessage.success('活动已创建'); box.show = false; reload(); } else ElMessage.error(r.message);
        }
        onMounted(() => { ed.load(); reload(); loadGoods(); });
        return { tab, ed, loading, list, total, page, pageSize, reload, box, openCreate, saveActivity, goodsOptions, fmtTime, fmtMoney };
    },
    template: `
    <div class="ns-card tight">
      <el-page-header @back="$router.push('/plugins')" content="拼团插件" style="position:absolute"></el-page-header>
      <el-tabs v-model="tab" style="margin-left:120px">
        <el-tab-pane label="拼团活动" name="activity"></el-tab-pane>
        <el-tab-pane label="插件配置" name="config"></el-tab-pane>
      </el-tabs>
    </div>

    <div class="ns-card" v-show="tab === 'activity'">
      <div class="ns-filter" style="margin-bottom:12px"><div style="flex:1"></div>
        <el-button type="primary" @click="openCreate">新建拼团活动</el-button></div>
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column prop="title" label="活动" min-width="140"><template #default="{row}"><span class="ns-title-cell">{{ row.title }}</span></template></el-table-column>
        <el-table-column label="商品" width="90"><template #default="{row}">#{{ row.goods_id }}</template></el-table-column>
        <el-table-column label="拼团价" width="100"><template #default="{row}"><span class="ns-money">{{ fmtMoney(row.group_price) }}</span></template></el-table-column>
        <el-table-column label="成团" width="80"><template #default="{row}">{{ row.group_size }}人</template></el-table-column>
        <el-table-column label="时限" width="90"><template #default="{row}">{{ row.time_limit_hours }}h</template></el-table-column>
        <el-table-column prop="stock" label="库存" width="80"></el-table-column>
        <el-table-column label="起止" width="260"><template #default="{row}">{{ fmtTime(row.start_at).slice(0,10) }} ~ {{ fmtTime(row.end_at).slice(0,10) }}</template></el-table-column>
        <el-table-column label="状态" width="80"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{ row.status===1?'进行中':'停止' }}</el-tag></template></el-table-column>
      </el-table>
      <div class="ns-pagebar">
        <el-pagination background layout="total, prev, pager, next" :total="total"
          v-model:current-page="page" v-model:page-size="pageSize"
          @current-change="reload()" @size-change="reload(1)"></el-pagination>
      </div>
    </div>

    <div class="ns-card" v-show="tab === 'config'" v-loading="ed.loading.value" style="max-width:680px">
      <template v-if="Object.keys(ed.config.value).length">
        <el-form label-width="180px">
          <el-form-item v-for="(v,k) in ed.config.value" :key="k" :label="k">
            <el-input-number v-if="typeof v === 'number'" v-model="ed.config.value[k]" controls-position="right"></el-input-number>
            <el-switch v-else-if="typeof v === 'boolean'" v-model="ed.config.value[k]"></el-switch>
            <el-input v-else v-model="ed.config.value[k]"></el-input>
          </el-form-item>
        </el-form>
        <el-button type="primary" :loading="ed.saving.value" @click="ed.save">保存配置</el-button>
      </template>
      <el-empty v-else description="暂无可配置项"></el-empty>
    </div>

    <el-dialog v-model="box.show" title="新建拼团活动" width="560px">
      <el-form label-width="110px">
        <el-form-item label="活动商品" required>
          <el-select v-model="box.form.goodsId" filterable style="width:100%" placeholder="选择商品">
            <el-option v-for="g in goodsOptions" :key="g.id" :label="g.title" :value="g.id"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="活动标题"><el-input v-model="box.form.title" placeholder="默认「拼团活动」"></el-input></el-form-item>
        <el-form-item label="拼团价" required><el-input-number v-model="box.form.groupPrice" :min="0.01" :precision="2"></el-input-number></el-form-item>
        <el-form-item label="成团人数"><el-input-number v-model="box.form.groupSize" :min="2"></el-input-number></el-form-item>
        <el-form-item label="成团时限(h)"><el-input-number v-model="box.form.timeLimitHours" :min="1"></el-input-number></el-form-item>
        <el-form-item label="活动库存"><el-input-number v-model="box.form.stock" :min="1"></el-input-number></el-form-item>
        <el-form-item label="开始时间"><el-date-picker v-model="box.form.startAt" type="datetime" value-format="YYYY-MM-DDTHH:mm" placeholder="默认立即"></el-date-picker></el-form-item>
        <el-form-item label="结束时间"><el-date-picker v-model="box.form.endAt" type="datetime" value-format="YYYY-MM-DDTHH:mm" placeholder="默认 7 天后"></el-date-picker></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="box.show = false">取消</el-button>
        <el-button type="primary" @click="saveActivity">创建活动</el-button>
      </template>
    </el-dialog>
  `,
};

/* ============ 秒杀 ============ */
export const PluginSeckill = {
    name: 'PluginSeckill',
    setup() {
        const tab = ref('session');
        const ed = useConfigEditor('seckill');
        const { loading, list, total, page, pageSize, reload } = usePageList((p) => api.get('/seckill/admin/sessions', p));
        const box = reactive({ show: false, form: {}, goods: [] });
        const goodsOptions = ref([]);

        function openCreate() {
            Object.assign(box, { show: true, form: { name: '', startAt: '', endAt: '' }, goods: [{ goodsId: null, seckillPrice: 0, stock: 10, perLimit: 1 }] });
        }
        async function loadGoods() {
            const r = await api.get('/manage/goods/index', { pageSize: 100, status: 1 });
            if (r.code === 0) goodsOptions.value = r.data.list || [];
        }
        function addRow() { box.goods.push({ goodsId: null, seckillPrice: 0, stock: 10, perLimit: 1 }); }
        async function saveSession() {
            if (!box.form.name) return ElMessage.warning('请填写场次名称');
            const f = { ...box.form, goods: box.goods.filter((g) => g.goodsId) };
            if (f.startAt) f.startAt = String(f.startAt).replace('T', ' ');
            if (f.endAt) f.endAt = String(f.endAt).replace('T', ' ');
            const r = await api.post('/seckill/admin/save-session', f);
            if (r.code === 0) { ElMessage.success('场次已保存'); box.show = false; reload(); } else ElMessage.error(r.message);
        }
        onMounted(() => { ed.load(); reload(); loadGoods(); });
        return { tab, ed, loading, list, total, page, pageSize, reload, box, openCreate, addRow, saveSession, goodsOptions, fmtTime, fmtMoney };
    },
    template: `
    <div class="ns-card tight">
      <el-page-header @back="$router.push('/plugins')" content="秒杀插件" style="position:absolute"></el-page-header>
      <el-tabs v-model="tab" style="margin-left:120px">
        <el-tab-pane label="秒杀场次" name="session"></el-tab-pane>
        <el-tab-pane label="插件配置" name="config"></el-tab-pane>
      </el-tabs>
    </div>

    <div class="ns-card" v-show="tab === 'session'">
      <div class="ns-filter" style="margin-bottom:12px"><div style="flex:1"></div>
        <el-button type="primary" @click="openCreate">新建场次</el-button></div>
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column prop="name" label="场次" min-width="140"><template #default="{row}"><span class="ns-title-cell">{{ row.name }}</span></template></el-table-column>
        <el-table-column label="开始" width="160"><template #default="{row}">{{ fmtTime(row.start_at) }}</template></el-table-column>
        <el-table-column label="结束" width="160"><template #default="{row}">{{ fmtTime(row.end_at) }}</template></el-table-column>
        <el-table-column label="状态" width="90"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{ row.status===1?'启用':'停用' }}</el-tag></template></el-table-column>
      </el-table>
      <div class="ns-pagebar">
        <el-pagination background layout="total, prev, pager, next" :total="total"
          v-model:current-page="page" v-model:page-size="pageSize"
          @current-change="reload()" @size-change="reload(1)"></el-pagination>
      </div>
    </div>

    <div class="ns-card" v-show="tab === 'config'" v-loading="ed.loading.value" style="max-width:680px">
      <template v-if="Object.keys(ed.config.value).length">
        <el-form label-width="180px">
          <el-form-item v-for="(v,k) in ed.config.value" :key="k" :label="k">
            <el-input-number v-if="typeof v === 'number'" v-model="ed.config.value[k]" controls-position="right"></el-input-number>
            <el-switch v-else-if="typeof v === 'boolean'" v-model="ed.config.value[k]"></el-switch>
            <el-input v-else v-model="ed.config.value[k]"></el-input>
          </el-form-item>
        </el-form>
        <el-button type="primary" :loading="ed.saving.value" @click="ed.save">保存配置</el-button>
      </template>
      <el-empty v-else description="暂无可配置项"></el-empty>
    </div>

    <el-dialog v-model="box.show" title="新建秒杀场次" width="680px">
      <el-form label-width="90px">
        <el-form-item label="场次名称" required><el-input v-model="box.form.name"></el-input></el-form-item>
        <el-form-item label="开始时间"><el-date-picker v-model="box.form.startAt" type="datetime" value-format="YYYY-MM-DDTHH:mm" placeholder="默认立即"></el-date-picker></el-form-item>
        <el-form-item label="结束时间"><el-date-picker v-model="box.form.endAt" type="datetime" value-format="YYYY-MM-DDTHH:mm" placeholder="默认 2 小时后"></el-date-picker></el-form-item>
        <el-form-item label="秒杀商品">
          <div style="width:100%">
            <div v-for="(g, i) in box.goods" :key="i" style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
              <el-select v-model="g.goodsId" filterable placeholder="商品" style="width:220px">
                <el-option v-for="go in goodsOptions" :key="go.id" :label="go.title" :value="go.id"></el-option>
              </el-select>
              <el-input-number v-model="g.seckillPrice" :min="0.01" :precision="2" controls-position="right" style="width:130px"></el-input-number>
              <el-input-number v-model="g.stock" :min="1" controls-position="right" style="width:110px"></el-input-number>
              <el-input-number v-model="g.perLimit" :min="1" controls-position="right" style="width:110px"></el-input-number>
              <el-button link type="danger" @click="box.goods.splice(i,1)">删除</el-button>
            </div>
            <el-button size="small" @click="addRow">+ 添加商品</el-button>
            <div class="ns-mut" style="margin-top:6px">依次为：秒杀价 / 库存 / 每人限购</div>
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="box.show = false">取消</el-button>
        <el-button type="primary" @click="saveSession">保存场次</el-button>
      </template>
    </el-dialog>
  `,
};

/* ============ 分销 ============ */
export const PluginDistribution = {
    name: 'PluginDistribution',
    setup() {
        const tab = ref('config');
        const config = reactive({ first_ratio: 10, second_ratio: 5, min_withdraw: 10 });
        const savingCfg = ref(false);

        async function loadCfg() {
            const r = await api.get('/distribution/admin/config');
            if (r.code === 0) Object.assign(config, r.data.config || {});
        }
        async function saveCfg() {
            savingCfg.value = true;
            try {
                const r = await api.post('/distribution/admin/save-config', { config: { ...config } });
                if (r.code === 0) ElMessage.success('已保存'); else ElMessage.error(r.message);
            } finally { savingCfg.value = false; }
        }

        const relations = usePageList((p) => api.get('/distribution/admin/relations', p));
        const commissions = usePageList((p) => api.get('/distribution/admin/commissions', p));
        const withdraws = usePageList((p) => api.get('/distribution/admin/withdraws', p));

        async function audit(row, agree) {
            let remark = '';
            if (!agree) {
                const res = await ElMessageBox.prompt('驳回原因', '驳回提现', { inputValue: '不符合提现要求' });
                remark = res.value || '';
            } else {
                await ElMessageBox.confirm(`确认打款 ${fmtMoney(row.amount)} 至用户余额？`, '打款确认', { type: 'warning' });
            }
            const r = await api.post('/distribution/admin/audit-withdraw', { id: row.id, agree: agree ? 1 : 0, remark });
            if (r.code === 0) { ElMessage.success(r.message); withdraws.reload(); } else ElMessage.error(r.message);
        }

        onMounted(() => { loadCfg(); relations.reload(); commissions.reload(); withdraws.reload(); });
        return {
            tab, config, savingCfg, loadCfg, saveCfg,
            relations, commissions, withdraws, audit, fmtTime, fmtMoney,
        };
    },
    template: `
    <div class="ns-card tight">
      <el-page-header @back="$router.push('/plugins')" content="分销插件" style="position:absolute"></el-page-header>
      <el-tabs v-model="tab" style="margin-left:120px">
        <el-tab-pane label="佣金配置" name="config"></el-tab-pane>
        <el-tab-pane label="推广关系" name="relations"></el-tab-pane>
        <el-tab-pane label="佣金流水" name="commissions"></el-tab-pane>
        <el-tab-pane label="提现审核" name="withdraws"></el-tab-pane>
      </el-tabs>
    </div>

    <div class="ns-card" v-show="tab === 'config'" style="max-width:560px">
      <el-form label-width="160px">
        <el-form-item label="一级佣金比例(%)"><el-input-number v-model="config.first_ratio" :min="0" :max="50" :precision="1"></el-input-number></el-form-item>
        <el-form-item label="二级佣金比例(%)"><el-input-number v-model="config.second_ratio" :min="0" :max="50" :precision="1"></el-input-number></el-form-item>
        <el-form-item label="最低提现金额"><el-input-number v-model="config.min_withdraw" :min="1" :precision="2"></el-input-number></el-form-item>
        <el-form-item><el-button type="primary" :loading="savingCfg" @click="saveCfg">保存配置</el-button></el-form-item>
      </el-form>
    </div>

    <div class="ns-card" v-show="tab === 'relations'">
      <el-table :data="relations.list.value" v-loading="relations.loading.value" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column label="用户" min-width="140"><template #default="{row}">{{ row.nickname }}（#{{ row.userId }}）</template></el-table-column>
        <el-table-column label="上级" min-width="140"><template #default="{row}">{{ row.parentNickname }}（#{{ row.parentId }}）</template></el-table-column>
        <el-table-column label="绑定时间" width="170"><template #default="{row}">{{ fmtTime(row.bindAt) }}</template></el-table-column>
      </el-table>
      <div class="ns-pagebar">
        <el-pagination background layout="total, prev, pager, next" :total="relations.total.value"
          v-model:current-page="relations.page.value" v-model:page-size="relations.pageSize.value"
          @current-change="relations.reload()" @size-change="relations.reload(1)"></el-pagination>
      </div>
    </div>

    <div class="ns-card" v-show="tab === 'commissions'">
      <el-table :data="commissions.list.value" v-loading="commissions.loading.value" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column prop="orderNo" label="订单号" min-width="180"></el-table-column>
        <el-table-column label="受益人" width="100"><template #default="{row}">#{{ row.userId }}</template></el-table-column>
        <el-table-column label="买家" width="100"><template #default="{row}">#{{ row.buyerId }}</template></el-table-column>
        <el-table-column label="级别" width="80"><template #default="{row}">{{ row.level === 1 ? '一级' : '二级' }}</template></el-table-column>
        <el-table-column label="金额" width="100"><template #default="{row}"><span class="ns-money">{{ fmtMoney(row.amount) }}</span></template></el-table-column>
        <el-table-column label="状态" width="90"><template #default="{row}"><el-tag :type="row.status===1?'success':(row.status===0?'warning':'info')" size="small">{{ row.statusText }}</el-tag></template></el-table-column>
        <el-table-column label="创建时间" width="170"><template #default="{row}">{{ fmtTime(row.createdAt) }}</template></el-table-column>
      </el-table>
      <div class="ns-pagebar">
        <el-pagination background layout="total, prev, pager, next" :total="commissions.total.value"
          v-model:current-page="commissions.page.value" v-model:page-size="commissions.pageSize.value"
          @current-change="commissions.reload()" @size-change="commissions.reload(1)"></el-pagination>
      </div>
    </div>

    <div class="ns-card" v-show="tab === 'withdraws'">
      <el-table :data="withdraws.list.value" v-loading="withdraws.loading.value" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column label="用户" width="90"><template #default="{row}">#{{ row.userId }}</template></el-table-column>
        <el-table-column label="金额" width="110"><template #default="{row}"><span class="ns-money">{{ fmtMoney(row.amount) }}</span></template></el-table-column>
        <el-table-column label="状态" width="90"><template #default="{row}"><el-tag :type="row.status===1?'success':(row.status===0?'warning':'info')" size="small">{{ row.statusText }}</el-tag></template></el-table-column>
        <el-table-column prop="remark" label="备注" min-width="160"></el-table-column>
        <el-table-column label="申请时间" width="170"><template #default="{row}">{{ fmtTime(row.createdAt) }}</template></el-table-column>
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{row}">
            <template v-if="row.status === 0">
              <el-button link type="success" @click="audit(row, true)">打款</el-button>
              <el-button link type="danger" @click="audit(row, false)">驳回</el-button>
            </template>
            <span v-else class="ns-mut">已处理</span>
          </template>
        </el-table-column>
      </el-table>
      <div class="ns-pagebar">
        <el-pagination background layout="total, prev, pager, next" :total="withdraws.total.value"
          v-model:current-page="withdraws.page.value" v-model:page-size="withdraws.pageSize.value"
          @current-change="withdraws.reload()" @size-change="withdraws.reload(1)"></el-pagination>
      </div>
    </div>
  `,
};

/* ============ 签到 ============ */
export const PluginCheckin = {
    name: 'PluginCheckin',
    setup() {
        const tab = ref('config');
        const config = reactive({ base_integral: 5, seven_bonus: 10, thirty_bonus: 50, makeup_cost: 20 });
        const savingCfg = ref(false);
        async function loadCfg() {
            const r = await api.get('/check_in/admin/config');
            if (r.code === 0) Object.assign(config, r.data.config || {});
        }
        async function saveCfg() {
            savingCfg.value = true;
            try {
                const r = await api.post('/check_in/admin/save-config', { config: { ...config } });
                if (r.code === 0) ElMessage.success('已保存'); else ElMessage.error(r.message);
            } finally { savingCfg.value = false; }
        }
        const records = usePageList((p) => api.get('/check_in/admin/records', p));
        onMounted(() => { loadCfg(); records.reload(); });
        return { tab, config, savingCfg, saveCfg, records, fmtTime };
    },
    template: `
    <div class="ns-card tight">
      <el-page-header @back="$router.push('/plugins')" content="签到插件" style="position:absolute"></el-page-header>
      <el-tabs v-model="tab" style="margin-left:120px">
        <el-tab-pane label="奖励配置" name="config"></el-tab-pane>
        <el-tab-pane label="打卡记录" name="records"></el-tab-pane>
      </el-tabs>
    </div>

    <div class="ns-card" v-show="tab === 'config'" style="max-width:560px">
      <el-form label-width="160px">
        <el-form-item label="每日基础积分"><el-input-number v-model="config.base_integral" :min="0"></el-input-number></el-form-item>
        <el-form-item label="连续7天额外奖励"><el-input-number v-model="config.seven_bonus" :min="0"></el-input-number></el-form-item>
        <el-form-item label="连续30天额外奖励"><el-input-number v-model="config.thirty_bonus" :min="0"></el-input-number></el-form-item>
        <el-form-item label="补签消耗积分"><el-input-number v-model="config.makeup_cost" :min="0"></el-input-number></el-form-item>
        <el-form-item><el-button type="primary" :loading="savingCfg" @click="saveCfg">保存配置</el-button></el-form-item>
      </el-form>
    </div>

    <div class="ns-card" v-show="tab === 'records'">
      <el-table :data="records.list.value" v-loading="records.loading.value" stripe>
        <el-table-column prop="id" label="ID" width="70"></el-table-column>
        <el-table-column label="用户" min-width="140"><template #default="{row}">{{ row.nickname }}（#{{ row.userId }}）</template></el-table-column>
        <el-table-column label="日期" width="120"><template #default="{row}"><span>{{ String(row.ymd).replace(/^(\d{4})(\d{2})(\d{2})$/, '$1-$2-$3') }}</span></template></el-table-column>
        <el-table-column prop="continuous" label="连续天数" width="90"></el-table-column>
        <el-table-column label="积分奖励" width="90"><template #default="{row}">+{{ row.rewardIntegral }}</template></el-table-column>
        <el-table-column label="补签" width="80"><template #default="{row}"><el-tag v-if="row.isMakeup" type="warning" size="small">补签</el-tag><span v-else class="ns-mut">-</span></template></el-table-column>
        <el-table-column label="打卡时间" width="170"><template #default="{row}">{{ fmtTime(row.createdAt) }}</template></el-table-column>
      </el-table>
      <div class="ns-pagebar">
        <el-pagination background layout="total, prev, pager, next" :total="records.total.value"
          v-model:current-page="records.page.value" v-model:page-size="records.pageSize.value"
          @current-change="records.reload()" @size-change="records.reload(1)"></el-pagination>
      </div>
    </div>
  `,
};

/** 按插件代码分发到专属页面或通用配置页 */
export function resolvePluginComponent(code) {
    switch (code) {
        case 'groupon': return PluginGroupon;
        case 'seckill': return PluginSeckill;
        case 'distribution': return PluginDistribution;
        case 'check_in': return PluginCheckin;
        default: return PluginGeneric;
    }
}
