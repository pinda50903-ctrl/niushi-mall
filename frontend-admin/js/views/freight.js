import { reactive } from 'vue';
const { ElMessage, ElMessageBox } = window.ElementPlus;
import { api, fmtMoney } from '../api.js';
import { usePageList } from '../ui.js';

export default {
    name: 'FreightPage',
    setup() {
        const { loading, list, reload } = usePageList((p) => api.get('/manage/freight/index', p));
        const dialog = reactive({ show: false, form: {} });

        function openCreate() {
            Object.assign(dialog, { show: true, form: { id: 0, name: '', chargeType: 0, baseAmount: 0, baseQuantity: 1, stepAmount: 0, stepQuantity: 1, freeOrderAmount: 0, excludedRegions: [] } });
        }
        function openEdit(row) {
            Object.assign(dialog, {
                show: true,
                form: {
                    id: row.id, name: row.name, chargeType: row.charge_type,
                    baseAmount: Number(row.base_amount), baseQuantity: Number(row.base_quantity),
                    stepAmount: Number(row.step_amount), stepQuantity: Number(row.step_quantity),
                    freeOrderAmount: Number(row.free_order_amount), excludedRegions: [],
                },
            });
        }
        async function save() {
            if (!dialog.form.name) return ElMessage.warning('请填写模板名称');
            const r = await api.post('/manage/freight/save', dialog.form);
            if (r.code === 0) { ElMessage.success('已保存'); dialog.show = false; reload(); } else ElMessage.error(r.message);
        }
        async function del(row) {
            await ElMessageBox.confirm(`删除运费模板「${row.name}」？`, '提示', { type: 'warning' });
            const r = await api.post('/manage/freight/delete', { id: row.id });
            if (r.code === 0) { ElMessage.success('已删除'); reload(); } else ElMessage.error(r.message);
        }
        return { loading, list, reload, dialog, openCreate, openEdit, save, del, fmtMoney };
    },
    template: `
    <div class="ns-card tight">
      <div class="ns-filter">
        <span style="font-weight:600">运费模板</span>
        <div style="flex:1"></div>
        <el-button type="primary" @click="openCreate">新建模板</el-button>
      </div>
    </div>
    <div class="ns-card">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column label="模板名称" min-width="160"><template #default="{row}"><span class="ns-title-cell">{{ row.name }}</span></template></el-table-column>
        <el-table-column label="计费方式" width="110"><template #default="{row}">{{ row.charge_type === 1 ? '按件数' : '按金额' }}</template></el-table-column>
        <el-table-column label="首段" width="150">
          <template #default="{row}">{{ fmtMoney(row.base_amount) }}{{ row.charge_type === 1 ? ' / 首' + row.base_quantity + '件' : '' }}</template>
        </el-table-column>
        <el-table-column label="续段" width="150">
          <template #default="{row}">{{ fmtMoney(row.step_amount) }}{{ row.charge_type === 1 ? ' / 续' + row.step_quantity + '件' : '' }}</template>
        </el-table-column>
        <el-table-column label="包邮门槛" width="120"><template #default="{row}">{{ Number(row.free_order_amount) > 0 ? '满 ' + fmtMoney(row.free_order_amount) : '-' }}</template></el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{row}">
            <el-button link type="primary" @click="openEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="del(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialog.show" :title="dialog.form.id ? '编辑模板' : '新建模板'" width="540px">
      <el-form label-width="110px">
        <el-form-item label="模板名称" required><el-input v-model="dialog.form.name" maxlength="30"></el-input></el-form-item>
        <el-form-item label="计费方式">
          <el-radio-group v-model="dialog.form.chargeType">
            <el-radio :value="0">按订单金额</el-radio><el-radio :value="1">按件数</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="首段费用">
          <el-input-number v-model="dialog.form.baseAmount" :min="0" :precision="2"></el-input-number>
          <el-input-number v-if="dialog.form.chargeType === 1" v-model="dialog.form.baseQuantity" :min="1" style="margin-left:10px;width:130px"></el-input-number>
          <span v-if="dialog.form.chargeType === 1" class="ns-mut" style="margin-left:8px">件内</span>
        </el-form-item>
        <el-form-item label="续段费用">
          <el-input-number v-model="dialog.form.stepAmount" :min="0" :precision="2"></el-input-number>
          <el-input-number v-if="dialog.form.chargeType === 1" v-model="dialog.form.stepQuantity" :min="1" style="margin-left:10px;width:130px"></el-input-number>
          <span v-if="dialog.form.chargeType === 1" class="ns-mut" style="margin-left:8px">件加收</span>
        </el-form-item>
        <el-form-item label="满额包邮">
          <el-input-number v-model="dialog.form.freeOrderAmount" :min="0" :precision="2"></el-input-number>
          <span class="ns-mut" style="margin-left:8px">0 为不启用</span>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialog.show = false">取消</el-button>
        <el-button type="primary" @click="save">保存</el-button>
      </template>
    </el-dialog>
  `,
};
