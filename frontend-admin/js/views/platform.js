import { reactive, ref, onMounted } from 'vue';
const { ElMessage, ElMessageBox } = window.ElementPlus;
import { api, fmtTime, fmtMoney } from '../api.js';
import { usePageList } from '../ui.js';

/* ============ 平台总览 ============ */
export const PlatformOverview = {
    name: 'PlatformOverview',
    setup() {
        const data = ref(null);
        onMounted(async () => {
            const r = await api.get('/platform/overview/index');
            if (r.code === 0) data.value = r.data;
        });
        return { data, fmtTime, fmtMoney };
    },
    template: `
    <div v-if="data">
      <div class="ns-stats">
        <div class="ns-stat"><div class="k">入驻商城</div><div class="v">{{ data.tenants.total }}<small> 家</small></div></div>
        <div class="ns-stat"><div class="k">今日平台成交</div><div class="v">{{ fmtMoney(data.todayGmv) }}</div></div>
        <div class="ns-stat"><div class="k">今日新增订单</div><div class="v">{{ data.todayOrders }}<small> 单</small></div></div>
        <div class="ns-stat"><div class="k">平台用户总量</div><div class="v">{{ data.totalUsers }}</div></div>
      </div>
      <div class="ns-stats">
        <div class="ns-stat"><div class="k">已安装插件</div><div class="v">{{ data.plugins.installed }}</div></div>
        <div class="ns-stat"><div class="k">在线插件</div><div class="v">{{ data.plugins.online }}</div></div>
        <div class="ns-stat"><div class="k">营业中商城</div><div class="v">{{ data.tenants.active }}</div></div>
        <div class="ns-stat"><div class="k">—</div><div class="v">&nbsp;</div></div>
      </div>
      <div class="ns-grid2">
        <div class="ns-card">
          <b>近 30 天商城成交 TOP5</b>
          <el-table :data="data.topTenants" style="margin-top:12px" size="small">
            <el-table-column type="index" label="#" width="50"></el-table-column>
            <el-table-column prop="name" label="商城" min-width="140"></el-table-column>
            <el-table-column prop="slug" label="标识" width="110"></el-table-column>
            <el-table-column prop="order_count" label="订单数" width="90"></el-table-column>
            <el-table-column label="成交额" width="120"><template #default="{row}"><span class="ns-money">{{ fmtMoney(row.pay_amount) }}</span></template></el-table-column>
          </el-table>
        </div>
        <div class="ns-card">
          <b>最新入驻</b>
          <el-table :data="data.recentTenants" style="margin-top:12px" size="small">
            <el-table-column prop="name" label="商城" min-width="130"></el-table-column>
            <el-table-column prop="slug" label="标识" width="100"></el-table-column>
            <el-table-column label="开通时间" width="110"><template #default="{row}">{{ fmtTime(row.created_at).slice(0, 10) }}</template></el-table-column>
          </el-table>
        </div>
      </div>
    </div>
    <el-skeleton v-else :rows="8" animated></el-skeleton>
  `,
};

/* ============ 租户管理 ============ */
export const PlatformTenants = {
    name: 'PlatformTenants',
    setup() {
        const keyword = ref('');
        const { loading, list, total, page, pageSize, reload } = usePageList((p) =>
            api.get('/platform/tenant/index', { ...p, keyword: keyword.value })
        );
        const createBox = reactive({ show: false, form: { name: '', slug: '', phone: '', expireAt: '', remark: '', username: '', password: '' } });
        const editBox = reactive({ show: false, form: { id: 0, name: '', phone: '', expireAt: '', remark: '', status: 1 } });

        function openCreate() { createBox.show = true; }
        async function doCreate() {
            const f = createBox.form;
            if (!f.name || !f.slug || !f.username || f.password.length < 6) return ElMessage.warning('请完整填写（密码至少 6 位）');
            const r = await api.post('/platform/tenant/create', f);
            if (r.code === 0) { ElMessage.success('商城已开通'); createBox.show = false; reload(); } else ElMessage.error(r.message);
        }
        function openEdit(row) {
            editBox.form = {
                id: row.id, name: row.name, phone: row.phone, remark: row.remark, status: row.status,
                expireAt: row.expire_at > 0 ? new Date(row.expire_at * 1000).toISOString().slice(0, 10) : '',
            };
            editBox.show = true;
        }
        async function doUpdate() {
            const r = await api.post('/platform/tenant/update', editBox.form);
            if (r.code === 0) { ElMessage.success('已保存'); editBox.show = false; reload(); } else ElMessage.error(r.message);
        }
        async function resetPwd(row) {
            const { value } = await ElMessageBox.prompt('重置该商城管理员密码（至少 6 位）', '重置密码');
            if (!value || value.length < 6) return ElMessage.warning('密码至少 6 位');
            const r = await api.post('/platform/tenant/reset-password', { adminId: row.id, password: value });
            if (r.code === 0) ElMessage.success('密码已重置');
        }
        onMounted(reload);
        return { keyword, loading, list, total, page, pageSize, reload, createBox, openCreate, doCreate, editBox, openEdit, doUpdate, resetPwd, fmtTime };
    },
    template: `
    <div class="ns-card tight">
      <div class="ns-filter">
        <el-input v-model="keyword" placeholder="商城名称 / 标识" clearable style="width:220px" @keyup.enter="reload(1)"></el-input>
        <el-button type="primary" @click="reload(1)">查询</el-button>
        <div style="flex:1"></div>
        <el-button type="primary" @click="openCreate">开通商城</el-button>
      </div>
    </div>
    <div class="ns-card">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column label="商城" min-width="150"><template #default="{row}"><span class="ns-title-cell">{{ row.name }}</span></template></el-table-column>
        <el-table-column prop="slug" label="标识" width="110"></el-table-column>
        <el-table-column prop="phone" label="电话" width="130"><template #default="{row}">{{ row.phone || '-' }}</template></el-table-column>
        <el-table-column label="到期时间" width="120"><template #default="{row}">{{ row.expire_at > 0 ? fmtTime(row.expire_at).slice(0,10) : '永久' }}</template></el-table-column>
        <el-table-column label="状态" width="90"><template #default="{row}"><el-tag :type="row.status===1?'success':'danger'" size="small">{{ row.status===1?'营业中':'已停用' }}</el-tag></template></el-table-column>
        <el-table-column label="开通时间" width="120"><template #default="{row}">{{ fmtTime(row.created_at).slice(0,10) }}</template></el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{row}">
            <el-button link type="primary" @click="openEdit(row)">编辑</el-button>
            <el-button link type="warning" @click="resetPwd(row)">重置密码</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="ns-pagebar">
        <el-pagination background layout="total, prev, pager, next" :total="total"
          v-model:current-page="page" v-model:page-size="pageSize"
          @current-change="reload()" @size-change="reload(1)"></el-pagination>
      </div>
    </div>

    <el-dialog v-model="createBox.show" title="开通新商城" width="520px">
      <el-form label-width="100px">
        <el-form-item label="商城名称" required><el-input v-model="createBox.form.name"></el-input></el-form-item>
        <el-form-item label="唯一标识" required><el-input v-model="createBox.form.slug" placeholder="小写字母开头，如 demo"></el-input></el-form-item>
        <el-form-item label="管理员账号" required><el-input v-model="createBox.form.username"></el-input></el-form-item>
        <el-form-item label="管理员密码" required><el-input v-model="createBox.form.password" type="password" show-password placeholder="至少 6 位"></el-input></el-form-item>
        <el-form-item label="联系电话"><el-input v-model="createBox.form.phone"></el-input></el-form-item>
        <el-form-item label="到期时间"><el-date-picker v-model="createBox.form.expireAt" type="date" value-format="YYYY-MM-DD" placeholder="留空为永久"></el-date-picker></el-form-item>
        <el-form-item label="备注"><el-input v-model="createBox.form.remark" type="textarea" :rows="2"></el-input></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createBox.show = false">取消</el-button>
        <el-button type="primary" @click="doCreate">确认开通</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="editBox.show" title="编辑商城" width="480px">
      <el-form label-width="100px">
        <el-form-item label="商城名称"><el-input v-model="editBox.form.name"></el-input></el-form-item>
        <el-form-item label="联系电话"><el-input v-model="editBox.form.phone"></el-input></el-form-item>
        <el-form-item label="到期时间"><el-date-picker v-model="editBox.form.expireAt" type="date" value-format="YYYY-MM-DD"></el-date-picker></el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="editBox.form.status" :active-value="1" :inactive-value="0" active-text="营业中"></el-switch>
        </el-form-item>
        <el-form-item label="备注"><el-input v-model="editBox.form.remark" type="textarea" :rows="2"></el-input></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editBox.show = false">取消</el-button>
        <el-button type="primary" @click="doUpdate">保存</el-button>
      </template>
    </el-dialog>
  `,
};

/* ============ 插件市场 ============ */
export const PlatformMarket = {
    name: 'PlatformMarket',
    setup() {
        const list = ref([]);
        const loading = ref(false);
        const grantBox = reactive({ show: false, plugin: null, tenantId: null, expireAt: '' });
        const tenants = ref([]);
        const catFilter = ref('');
        const CATEGORY_LABELS = { marketing: '交易营销', member: '会员增长', store: '门店渠道', tool: '辅助工具', channel: '多端渠道' };

        async function load() {
            loading.value = true;
            try {
                const r = await api.get('/platform/plugin/market');
                if (r.code === 0) list.value = r.data.list || [];
            } finally { loading.value = false; }
        }
        async function loadTenants() {
            const r = await api.get('/platform/tenant/index', { pageSize: 100 });
            if (r.code === 0) tenants.value = r.data.list || [];
        }
        async function toggle(row) {
            const r = await api.post('/platform/plugin/toggle', { code: row.code, status: row.status === 1 ? 0 : 1 });
            if (r.code === 0) { ElMessage.success(r.message); load(); } else ElMessage.error(r.message);
        }
        function openGrant(row) {
            grantBox.plugin = row; grantBox.tenantId = null; grantBox.expireAt = ''; grantBox.show = true;
            loadTenants();
        }
        async function doGrant() {
            if (!grantBox.tenantId) return ElMessage.warning('请选择商城');
            const r = await api.post('/platform/plugin/grant', { code: grantBox.plugin.code, tenantId: grantBox.tenantId, expireAt: grantBox.expireAt });
            if (r.code === 0) { ElMessage.success('已授权'); grantBox.show = false; } else ElMessage.error(r.message);
        }
        async function revoke(row) {
            const { value } = await ElMessageBox.prompt('输入要撤销授权的商城 ID', `撤销「${row.name}」授权`, { inputValue: '' });
            const tid = Number(value);
            if (!tid) return;
            const r = await api.post('/platform/plugin/revoke', { code: row.code, tenantId: tid });
            if (r.code === 0) { ElMessage.success('已撤销'); } else ElMessage.error(r.message);
        }
        onMounted(load);
        const filtered = () => catFilter.value ? list.value.filter((p) => p.category === catFilter.value) : list.value;
        return { list, filtered, loading, catFilter, CATEGORY_LABELS, toggle, openGrant, grantBox, doGrant, revoke };
    },
    template: `
    <div class="ns-card tight">
      <div class="ns-filter">
        <span style="font-weight:600">插件市场</span>
        <el-select v-model="catFilter" placeholder="全部分类" clearable style="width:140px">
          <el-option v-for="(l,k) in CATEGORY_LABELS" :key="k" :label="l" :value="k"></el-option>
        </el-select>
        <div style="flex:1"></div>
      </div>
    </div>
    <div class="ns-card">
      <el-table :data="filtered()" v-loading="loading" stripe>
        <el-table-column prop="code" label="代码" width="130"></el-table-column>
        <el-table-column label="插件" min-width="130"><template #default="{row}"><span class="ns-title-cell">{{ row.name }}</span></template></el-table-column>
        <el-table-column label="分类" width="110"><template #default="{row}">{{ CATEGORY_LABELS[row.category] || row.category }}</template></el-table-column>
        <el-table-column prop="description" label="描述" min-width="220"></el-table-column>
        <el-table-column prop="version" label="版本" width="80"></el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{ row.status===1?'已上架':'未上架' }}</el-tag></template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button link :type="row.status===1?'warning':'success'" @click="toggle(row)">{{ row.status===1?'下架':'上架' }}</el-button>
            <el-button link type="primary" @click="openGrant(row)">授权商城</el-button>
            <el-button link type="danger" @click="revoke(row)">撤销</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="grantBox.show" :title="'授权插件：' + (grantBox.plugin?.name || '')" width="420px">
      <el-form label-width="90px">
        <el-form-item label="目标商城">
          <el-select v-model="grantBox.tenantId" filterable style="width:100%" placeholder="选择商城">
            <el-option v-for="t in tenants" :key="t.id" :label="t.name + '（' + t.slug + '）'" :value="t.id"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="到期时间">
          <el-date-picker v-model="grantBox.expireAt" type="date" value-format="YYYY-MM-DD" placeholder="留空为永久"></el-date-picker>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="grantBox.show = false">取消</el-button>
        <el-button type="primary" @click="doGrant">确认授权</el-button>
      </template>
    </el-dialog>
  `,
};

/* ============ 平台账号 ============ */
export const PlatformAdmins = {
    name: 'PlatformAdmins',
    setup() {
        const keyword = ref('');
        const { loading, list, total, page, pageSize, reload } = usePageList((p) =>
            api.get('/platform/account/index', { ...p, keyword: keyword.value })
        );
        const roles = ref([]);
        const box = reactive({ show: false, form: { id: 0, username: '', nickname: '', password: '', roleId: null, status: 1 } });

        async function loadRoles() {
            const r = await api.get('/platform/role/index', { pageSize: 100 });
            if (r.code === 0) roles.value = r.data.list || [];
        }
        function openCreate() { Object.assign(box, { show: true, form: { id: 0, username: '', nickname: '', password: '', roleId: null, status: 1 } }); }
        function openEdit(row) { Object.assign(box, { show: true, form: { id: row.id, username: row.username, nickname: row.nickname, password: '', roleId: row.role_id, status: row.status } }); }
        async function save() {
            if (!box.form.username) return ElMessage.warning('请填写账号');
            const r = box.form.id > 0 ? await api.post('/platform/account/update', box.form) : await api.post('/platform/account/create', box.form);
            if (r.code === 0) { ElMessage.success('已保存'); box.show = false; reload(); } else ElMessage.error(r.message);
        }
        async function resetPwd(row) {
            const { value } = await ElMessageBox.prompt('新密码（至少 6 位）', '重置密码');
            if (!value || value.length < 6) return ElMessage.warning('密码至少 6 位');
            const r = await api.post('/platform/account/reset-password', { id: row.id, password: value });
            if (r.code === 0) ElMessage.success('密码已重置');
        }
        async function del(row) {
            await ElMessageBox.confirm(`删除账号「${row.username}」？`, '提示', { type: 'warning' });
            const r = await api.post('/platform/account/delete', { id: row.id });
            if (r.code === 0) { ElMessage.success('已删除'); reload(); } else ElMessage.error(r.message);
        }
        onMounted(() => { loadRoles(); reload(); });
        const roleName = (id) => roles.value.find((x) => x.id === id)?.name || '-';
        return { keyword, loading, list, total, page, pageSize, reload, roles, box, openCreate, openEdit, save, resetPwd, del, roleName, fmtTime };
    },
    template: `
    <div class="ns-card tight">
      <div class="ns-filter">
        <el-input v-model="keyword" placeholder="账号 / 昵称" clearable style="width:220px" @keyup.enter="reload(1)"></el-input>
        <el-button type="primary" @click="reload(1)">查询</el-button>
        <div style="flex:1"></div>
        <el-button type="primary" @click="openCreate">新增账号</el-button>
      </div>
    </div>
    <div class="ns-card">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column prop="username" label="账号" width="150"></el-table-column>
        <el-table-column prop="nickname" label="昵称" width="150"></el-table-column>
        <el-table-column label="角色" width="140"><template #default="{row}">{{ roleName(row.role_id) }}</template></el-table-column>
        <el-table-column label="状态" width="80"><template #default="{row}"><el-tag :type="row.status===1?'success':'danger'" size="small">{{ row.status===1?'启用':'停用' }}</el-tag></template></el-table-column>
        <el-table-column label="最近登录" width="170"><template #default="{row}">{{ fmtTime(row.last_login_at) }}</template></el-table-column>
        <el-table-column label="操作" width="230" fixed="right">
          <template #default="{row}">
            <el-button link type="primary" @click="openEdit(row)">编辑</el-button>
            <el-button link type="warning" @click="resetPwd(row)">重置密码</el-button>
            <el-button link type="danger" @click="del(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="ns-pagebar">
        <el-pagination background layout="total, prev, pager, next" :total="total"
          v-model:current-page="page" v-model:page-size="pageSize"
          @current-change="reload()" @size-change="reload(1)"></el-pagination>
      </div>
    </div>

    <el-dialog v-model="box.show" :title="box.form.id ? '编辑账号' : '新增账号'" width="460px">
      <el-form label-width="90px">
        <el-form-item label="账号" required><el-input v-model="box.form.username" :disabled="box.form.id > 0"></el-input></el-form-item>
        <el-form-item label="昵称"><el-input v-model="box.form.nickname"></el-input></el-form-item>
        <el-form-item v-if="!box.form.id" label="密码" required><el-input v-model="box.form.password" type="password" show-password placeholder="至少 6 位"></el-input></el-form-item>
        <el-form-item label="角色">
          <el-select v-model="box.form.roleId" clearable style="width:100%">
            <el-option v-for="r in roles" :key="r.id" :label="r.name" :value="r.id"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item v-if="box.form.id" label="状态"><el-switch v-model="box.form.status" :active-value="1" :inactive-value="0"></el-switch></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="box.show = false">取消</el-button>
        <el-button type="primary" @click="save">保存</el-button>
      </template>
    </el-dialog>
  `,
};

/* ============ 平台角色 ============ */
export const PlatformRoles = {
    name: 'PlatformRoles',
    setup() {
        const permGroups = ref([]);
        const { loading, list, reload } = usePageList((p) => api.get('/platform/role/index', p));
        const box = reactive({ show: false, form: { id: 0, name: '', remark: '', permissions: [] } });

        function openCreate() { Object.assign(box, { show: true, form: { id: 0, name: '', remark: '', permissions: [] } }); }
        function openEdit(row) {
            let perms = [];
            try { perms = JSON.parse(row.permissions || '[]'); } catch { perms = []; }
            Object.assign(box, { show: true, form: { id: row.id, name: row.name, remark: row.remark, permissions: perms } });
        }
        async function save() {
            if (!box.form.name) return ElMessage.warning('请填写角色名称');
            const r = await api.post('/platform/role/save', box.form);
            if (r.code === 0) { ElMessage.success('已保存'); box.show = false; reload(); } else ElMessage.error(r.message);
        }
        async function del(row) {
            await ElMessageBox.confirm(`删除角色「${row.name}」？`, '提示', { type: 'warning' });
            const r = await api.post('/platform/role/delete', { id: row.id });
            if (r.code === 0) { ElMessage.success('已删除'); reload(); } else ElMessage.error(r.message);
        }
        onMounted(async () => {
            const r = await api.get('/platform/role/permissions');
            if (r.code === 0) permGroups.value = r.data;
        });
        return { permGroups, loading, list, reload, box, openCreate, openEdit, save, del };
    },
    template: `
    <div class="ns-card tight">
      <div class="ns-filter">
        <span class="ns-mut">平台角色控制总部管理员的模块访问权限</span>
        <div style="flex:1"></div>
        <el-button type="primary" @click="openCreate">新增角色</el-button>
      </div>
    </div>
    <div class="ns-card">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column prop="name" label="角色名称" width="160"></el-table-column>
        <el-table-column prop="remark" label="备注" min-width="180"></el-table-column>
        <el-table-column label="类型" width="100"><template #default="{row}"><el-tag :type="row.is_super===1?'danger':'info'" size="small">{{ row.is_super===1?'超管':'普通' }}</el-tag></template></el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{row}">
            <el-button v-if="row.is_super !== 1" link type="primary" @click="openEdit(row)">编辑</el-button>
            <el-button v-if="row.is_super !== 1" link type="danger" @click="del(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="box.show" :title="box.form.id ? '编辑角色' : '新增角色'" width="520px">
      <el-form label-width="90px">
        <el-form-item label="角色名称" required><el-input v-model="box.form.name"></el-input></el-form-item>
        <el-form-item label="备注"><el-input v-model="box.form.remark"></el-input></el-form-item>
        <el-form-item label="权限">
          <div style="width:100%">
            <div v-for="g in permGroups" :key="g.group" style="margin-bottom:10px">
              <div style="font-weight:600;margin-bottom:6px">{{ g.group }}</div>
              <el-checkbox-group v-model="box.form.permissions">
                <el-checkbox v-for="(label, key) in g.items" :key="key" :value="key">{{ label }}</el-checkbox>
              </el-checkbox-group>
            </div>
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="box.show = false">取消</el-button>
        <el-button type="primary" @click="save">保存</el-button>
      </template>
    </el-dialog>
  `,
};
