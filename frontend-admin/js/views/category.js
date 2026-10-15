import { ref, reactive, onMounted } from 'vue';
const { ElMessage, ElMessageBox } = window.ElementPlus;
import { api } from '../api.js';
import { ImgUpload } from '../ui.js';

export default {
    name: 'CategoryPage',
    components: { ImgUpload },
    setup() {
        const list = ref([]);
        const loading = ref(false);
        const dialog = reactive({ show: false, form: { id: 0, parentId: 0, name: '', icon: '', sort: 100, isVisible: 1 } });

        async function load() {
            loading.value = true;
            try {
                const r = await api.get('/manage/category/index');
                if (r.code === 0) list.value = r.data.list || [];
            } finally { loading.value = false; }
        }
        function openCreate(parentId = 0) {
            Object.assign(dialog.form, { id: 0, parentId, name: '', icon: '', sort: 100, isVisible: 1 });
            dialog.show = true;
        }
        function openEdit(row) {
            Object.assign(dialog.form, { id: row.id, parentId: row.parent_id, name: row.name, icon: row.icon, sort: row.sort, isVisible: row.is_visible });
            dialog.show = true;
        }
        async function save() {
            if (!dialog.form.name) return ElMessage.warning('请填写分类名称');
            const r = await api.post('/manage/category/save', dialog.form);
            if (r.code === 0) { ElMessage.success('已保存'); dialog.show = false; load(); } else ElMessage.error(r.message);
        }
        async function del(row) {
            await ElMessageBox.confirm(`确定删除分类「${row.name}」？`, '提示', { type: 'warning' });
            const r = await api.post('/manage/category/delete', { id: row.id });
            if (r.code === 0) { ElMessage.success('已删除'); load(); } else ElMessage.error(r.message);
        }
        onMounted(load);
        return { list, loading, dialog, openCreate, openEdit, save, del };
    },
    template: `
    <div class="ns-card tight">
      <div class="ns-filter">
        <span style="font-weight:600">商品分类</span><span class="ns-mut">最多两级</span>
        <div style="flex:1"></div>
        <el-button type="primary" @click="openCreate(0)">新增一级分类</el-button>
      </div>
    </div>
    <div class="ns-card">
      <el-table :data="list" v-loading="loading" row-key="id">
        <el-table-column label="分类" min-width="300">
          <template #default="{row}">
            <div style="display:flex;align-items:center;gap:10px">
              <img v-if="row.icon" class="ns-table-cover" style="width:32px;height:32px" :src="row.icon_url || row.icon">
              <span class="ns-title-cell">{{ row.name }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="90"></el-table-column>
        <el-table-column label="可见" width="90">
          <template #default="{row}"><el-tag :type="row.is_visible===1?'success':'info'" size="small">{{ row.is_visible===1?'显示':'隐藏' }}</el-tag></template>
        </el-table-column>
        <el-table-column label="操作" width="240">
          <template #default="{row}">
            <el-button link type="primary" @click="openEdit(row)">编辑</el-button>
            <el-button link type="success" @click="openCreate(row.id)">添加子分类</el-button>
            <el-button link type="danger" @click="del(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialog.show" :title="dialog.form.id ? '编辑分类' : '新增分类'" width="480px">
      <el-form label-width="90px">
        <el-form-item label="上级分类">
          <el-select v-model="dialog.form.parentId" style="width:100%" :disabled="dialog.form.id > 0">
            <el-option :value="0" label="一级分类"></el-option>
            <el-option v-for="p in list" :key="p.id" :value="p.id" :label="p.name"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="名称" required><el-input v-model="dialog.form.name" maxlength="20"></el-input></el-form-item>
        <el-form-item label="图标"><img-upload v-model="dialog.form.icon" width="72px" height="72px"></img-upload></el-form-item>
        <el-form-item label="排序"><el-input-number v-model="dialog.form.sort" :min="0"></el-input-number></el-form-item>
        <el-form-item label="是否可见">
          <el-switch v-model="dialog.form.isVisible" :active-value="1" :inactive-value="0"></el-switch>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialog.show = false">取消</el-button>
        <el-button type="primary" @click="save">保存</el-button>
      </template>
    </el-dialog>
  `,
};
