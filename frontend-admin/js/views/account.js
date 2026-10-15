import { reactive, ref, onMounted } from 'vue';
const { ElMessage, ElMessageBox } = window.ElementPlus;
import { api, fmtTime } from '../api.js';
import { usePageList } from '../ui.js';

export default {
    name: 'AccountPage',
    setup() {
        const tab = ref('accounts');
        const permGroups = ref([]);

        /* ---- 子账号 ---- */
        const { loading, list, reload } = usePageList((p) => api.get('/manage/account/index', p));
        const roles = ref([]);
        const box = reactive({ show: false, form: { id: 0, username: '', nickname: '', password: '', roleId: null, status: 1 } });

        async function loadRoles() {
            const r = await api.get('/manage/account/roles');
            if (r.code === 0) roles.value = r.data.list || [];
        }
        function openCreate() {
            Object.assign(box, { show: true, form: { id: 0, username: '', nickname: '', password: '', roleId: null, status: 1 } });
        }
        function openEdit(row) {
            Object.assign(box, { show: true, form: { id: row.id, username: row.username, nickname: row.nickname, password: '', roleId: row.role_id, status: row.status } });
        }
        async function save() {
            if (!box.form.username) return ElMessage.warning('请填写账号');
            const r = await api.post('/manage/account/save', box.form);
            if (r.code === 0) { ElMessage.success('已保存'); box.show = false; reload(); } else ElMessage.error(r.message);
        }
        async function resetPwd(row) {
            const { value } = await ElMessageBox.prompt('新密码（至少 6 位）', '重置密码', { inputValue: '' });
            if (!value || value.length < 6) return ElMessage.warning('密码至少 6 位');
            const r = await api.post('/manage/account/reset-password', { id: row.id, password: value });
            if (r.code === 0) ElMessage.success('密码已重置');
        }
        async function del(row) {
            await ElMessageBox.confirm(`删除子账号「${row.username}」？`, '提示', { type: 'warning' });
            const r = await api.post('/manage/account/delete', { id: row.id });
            if (r.code === 0) { ElMessage.success('已删除'); reload(); } else ElMessage.error(r.message);
        }

        /* ---- 角色 ---- */
        const roleBox = reactive({ show: false, form: { id: 0, name: '', permissions: [] } });
        function openRoleCreate() {
            Object.assign(roleBox, { show: true, form: { id: 0, name: '', permissions: [] } });
        }
        function openRoleEdit(row) {
            let perms = [];
            try { perms = JSON.parse(row.permissions || '[]'); } catch { perms = []; }
            Object.assign(roleBox, { show: true, form: { id: row.id, name: row.name, permissions: perms } });
        }
        async function saveRole() {
            if (!roleBox.form.name) return ElMessage.warning('请填写角色名称');
            const r = await api.post('/manage/account/save-role', roleBox.form);
            if (r.code === 0) { ElMessage.success('已保存'); roleBox.show = false; loadRoles(); } else ElMessage.error(r.message);
        }
        async function delRole(row) {
            await ElMessageBox.confirm(`删除角色「${row.name}」？`, '提示', { type: 'warning' });
            const r = await api.post('/manage/account/delete-role', { id: row.id });
            if (r.code === 0) { ElMessage.success('已删除'); loadRoles(); } else ElMessage.error(r.message);
        }

        async function init() {
            const r = await api.get('/manage/account/permissions');
            if (r.code === 0) permGroups.value = r.data;
            loadRoles();
            reload();
        }
        onMounted(init);
        const roleName = (id) => roles.value.find((x) => x.id === id)?.name || '-';
        return { tab, permGroups, loading, list, reload, roles, box, openCreate, openEdit, save, resetPwd, del, roleBox, openRoleCreate, openRoleEdit, saveRole, delRole, roleName, fmtTime };
    },
    template: `
    <div class="ns-card tight">
      <el-tabs v-model="tab">
        <el-tab-pane label="子账号" name="accounts"></el-tab-pane>
        <el-tab-pane label="角色权限" name="roles"></el-tab-pane>
      </el-tabs>
    </div>

    <div class="ns-card" v-show="tab === 'accounts'">
      <div class="ns-filter" style="margin-bottom:12px">
        <span class="ns-mut">子账号通过角色控制可访问的功能模块</span>
        <div style="flex:1"></div>
        <el-button type="primary" @click="openCreate">新增子账号</el-button>
      </div>
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column prop="username" label="账号" width="140"></el-table-column>
        <el-table-column prop="nickname" label="昵称" width="140"></el-table-column>
        <el-table-column label="角色" width="140"><template #default="{row}">{{ roleName(row.role_id) }}</template></el-table-column>
        <el-table-column label="状态" width="80"><template #default="{row}"><el-tag :type="row.status===1?'success':'danger'" size="small">{{ row.status===1?'启用':'停用' }}</el-tag></template></el-table-column>
        <el-table-column label="最近登录" width="160"><template #default="{row}">{{ fmtTime(row.last_login_at) }}</template></el-table-column>
        <el-table-column label="操作" width="230" fixed="right">
          <template #default="{row}">
            <el-button link type="primary" @click="openEdit(row)">编辑</el-button>
            <el-button link type="warning" @click="resetPwd(row)">重置密码</el-button>
            <el-button link type="danger" @click="del(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <div class="ns-card" v-show="tab === 'roles'">
      <div class="ns-filter" style="margin-bottom:12px">
        <span class="ns-mut">超级管理员角色拥有全部权限，不可编辑</span>
        <div style="flex:1"></div>
        <el-button type="primary" @click="openRoleCreate">新增角色</el-button>
      </div>
      <el-table :data="roles" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column prop="name" label="角色名称" width="160"></el-table-column>
        <el-table-column label="类型" width="100"><template #default="{row}"><el-tag :type="row.is_super===1?'danger':'info'" size="small">{{ row.is_super===1?'超管':'普通' }}</el-tag></template></el-table-column>
        <el-table-column label="权限数" width="90">
          <template #default="{row}">{{ row.is_super === 1 ? '全部' : (() => { try { return JSON.parse(row.permissions||'[]').length } catch { return 0 } })() }}</template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{row}">
            <el-button v-if="row.is_super !== 1" link type="primary" @click="openRoleEdit(row)">编辑</el-button>
            <el-button v-if="row.is_super !== 1" link type="danger" @click="delRole(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="box.show" :title="box.form.id ? '编辑子账号' : '新增子账号'" width="480px">
      <el-form label-width="90px">
        <el-form-item label="账号" required><el-input v-model="box.form.username" :disabled="box.form.id > 0"></el-input></el-form-item>
        <el-form-item label="昵称"><el-input v-model="box.form.nickname"></el-input></el-form-item>
        <el-form-item v-if="!box.form.id" label="密码" required><el-input v-model="box.form.password" type="password" show-password placeholder="至少 6 位"></el-input></el-form-item>
        <el-form-item label="角色">
          <el-select v-model="box.form.roleId" clearable style="width:100%">
            <el-option v-for="r in roles" :key="r.id" :label="r.name" :value="r.id"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="状态"><el-switch v-model="box.form.status" :active-value="1" :inactive-value="0"></el-switch></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="box.show = false">取消</el-button>
        <el-button type="primary" @click="save">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="roleBox.show" :title="roleBox.form.id ? '编辑角色' : '新增角色'" width="520px">
      <el-form label-width="90px">
        <el-form-item label="角色名称" required><el-input v-model="roleBox.form.name"></el-input></el-form-item>
        <el-form-item label="功能权限">
          <div style="width:100%">
            <div v-for="g in permGroups" :key="g.group" style="margin-bottom:10px">
              <div style="font-weight:600;margin-bottom:6px">{{ g.group }}</div>
              <el-checkbox-group v-model="roleBox.form.permissions">
                <el-checkbox v-for="(label, key) in g.items" :key="key" :value="key">{{ label }}</el-checkbox>
              </el-checkbox-group>
            </div>
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="roleBox.show = false">取消</el-button>
        <el-button type="primary" @click="saveRole">保存</el-button>
      </template>
    </el-dialog>
  `,
};
