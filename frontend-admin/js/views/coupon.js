import { reactive, ref, onMounted } from 'vue';
const { ElMessage, ElMessageBox } = window.ElementPlus;
import { api, fmtTime, fmtMoney } from '../api.js';
import { usePageList } from '../ui.js';

export default {
    name: 'CouponPage',
    setup() {
        const { loading, list, total, page, pageSize, reload } = usePageList((p) => api.get('/manage/coupon/index', p));
        const dialog = reactive({ show: false, form: {} });
        const grantBox = reactive({ show: false, coupon: null, userIds: '' });

        function emptyForm() {
            return {
                id: 0, name: '', kind: 1, scope: 1, scopeValue: [], offAmount: 0, offPercent: 90,
                minAmount: 0, stock: 100, perLimit: 1, expireKind: 1,
                validFrom: new Date().toISOString().slice(0, 10), validTo: '2030-12-31', validDays: 7, status: 1,
            };
        }
        function openCreate() { Object.assign(dialog, { show: true, form: emptyForm() }); }
        function openEdit(row) {
            Object.assign(dialog, {
                show: true,
                form: {
                    id: row.id, name: row.name, kind: row.kind, scope: row.scope, scopeValue: row.scope_value || [],
                    offAmount: Number(row.off_amount), offPercent: row.off_percent, minAmount: Number(row.min_amount),
                    stock: row.stock, perLimit: row.per_limit, expireKind: row.expire_kind,
                    validFrom: row.valid_from ? new Date(row.valid_from * 1000).toISOString().slice(0, 10) : '',
                    validTo: row.valid_to ? new Date(row.valid_to * 1000).toISOString().slice(0, 10) : '',
                    validDays: row.valid_days, status: row.status,
                },
            });
        }
        async function save() {
            if (!dialog.form.name) return ElMessage.warning('请填写优惠券名称');
            const r = await api.post('/manage/coupon/save', dialog.form);
            if (r.code === 0) { ElMessage.success('已保存'); dialog.show = false; reload(); } else ElMessage.error(r.message);
        }
        async function del(row) {
            await ElMessageBox.confirm(`删除优惠券「${row.name}」？`, '提示', { type: 'warning' });
            const r = await api.post('/manage/coupon/delete', { id: row.id });
            if (r.code === 0) { ElMessage.success('已删除'); reload(); } else ElMessage.error(r.message);
        }
        async function doGrant() {
            const ids = grantBox.userIds.split(/[,，\s]+/).filter(Boolean).map(Number).filter(Boolean);
            const r = await api.post('/manage/coupon/grant', { id: grantBox.coupon.id, userIds: ids });
            if (r.code === 0) { ElMessage.success(r.message); grantBox.show = false; } else ElMessage.error(r.message);
        }
        onMounted(reload);
        return { loading, list, total, page, pageSize, reload, dialog, openCreate, openEdit, save, del, grantBox, doGrant, fmtTime, fmtMoney };
    },
    template: `
    <div class="ns-card tight">
      <div class="ns-filter">
        <span style="font-weight:600">优惠券</span>
        <div style="flex:1"></div>
        <el-button type="primary" @click="openCreate">新建优惠券</el-button>
      </div>
    </div>
    <div class="ns-card">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column label="名称" min-width="150"><template #default="{row}"><span class="ns-title-cell">{{ row.name }}</span></template></el-table-column>
        <el-table-column label="优惠" width="130">
          <template #default="{row}">{{ row.kind === 1 ? ('减 ' + fmtMoney(row.off_amount)) : (row.off_percent + '% 折扣') }}</template>
        </el-table-column>
        <el-table-column label="门槛" width="110"><template #default="{row}">{{ Number(row.min_amount) > 0 ? '满 ' + fmtMoney(row.min_amount) : '无门槛' }}</template></el-table-column>
        <el-table-column label="库存/已领" width="100"><template #default="{row}">{{ row.stock }} / {{ row.sent_count }}</template></el-table-column>
        <el-table-column label="有效期" width="220">
          <template #default="{row}">{{ row.expire_kind === 1 ? (fmtTime(row.valid_from).slice(0,10) + ' ~ ' + fmtTime(row.valid_to).slice(0,10)) : ('领取后 ' + row.valid_days + ' 天') }}</template>
        </el-table-column>
        <el-table-column label="状态" width="80">
          <template #default="{row}"><el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">{{ row.status === 1 ? '启用' : '停用' }}</el-tag></template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{row}">
            <el-button link type="primary" @click="openEdit(row)">编辑</el-button>
            <el-button link type="success" @click="grantBox.coupon = row; grantBox.userIds = ''; grantBox.show = true">发放</el-button>
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

    <el-dialog v-model="dialog.show" :title="dialog.form.id ? '编辑优惠券' : '新建优惠券'" width="560px">
      <el-form label-width="100px">
        <el-form-item label="名称" required><el-input v-model="dialog.form.name" maxlength="30"></el-input></el-form-item>
        <el-form-item label="优惠方式">
          <el-radio-group v-model="dialog.form.kind">
            <el-radio :value="1">满减</el-radio><el-radio :value="2">折扣</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="dialog.form.kind === 1" label="减免金额">
          <el-input-number v-model="dialog.form.offAmount" :min="0.01" :precision="2"></el-input-number>
        </el-form-item>
        <el-form-item v-else label="折扣率(%)">
          <el-input-number v-model="dialog.form.offPercent" :min="1" :max="99"></el-input-number>
          <span class="ns-mut" style="margin-left:8px">如 90 表示 9 折</span>
        </el-form-item>
        <el-form-item label="使用门槛">
          <el-input-number v-model="dialog.form.minAmount" :min="0" :precision="2"></el-input-number>
          <span class="ns-mut" style="margin-left:8px">0 为无门槛</span>
        </el-form-item>
        <el-form-item label="发放总量"><el-input-number v-model="dialog.form.stock" :min="1"></el-input-number></el-form-item>
        <el-form-item label="每人限领"><el-input-number v-model="dialog.form.perLimit" :min="1"></el-input-number></el-form-item>
        <el-form-item label="有效期">
          <el-radio-group v-model="dialog.form.expireKind">
            <el-radio :value="1">固定区间</el-radio><el-radio :value="2">领取后N天</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="dialog.form.expireKind === 1" label="起止日期">
          <el-date-picker v-model="dialog.form.validFrom" type="date" value-format="YYYY-MM-DD" style="width:150px"></el-date-picker>
          <span style="margin:0 6px">~</span>
          <el-date-picker v-model="dialog.form.validTo" type="date" value-format="YYYY-MM-DD" style="width:150px"></el-date-picker>
        </el-form-item>
        <el-form-item v-else label="有效天数"><el-input-number v-model="dialog.form.validDays" :min="1"></el-input-number></el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="dialog.form.status" :active-value="1" :inactive-value="0" active-text="启用"></el-switch>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialog.show = false">取消</el-button>
        <el-button type="primary" @click="save">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="grantBox.show" title="定向发放" width="420px">
      <div style="margin-bottom:10px">优惠券：{{ grantBox.coupon?.name }}</div>
      <el-input v-model="grantBox.userIds" type="textarea" :rows="3" placeholder="输入用户ID，逗号或空格分隔，如：1,2,3"></el-input>
      <template #footer>
        <el-button @click="grantBox.show = false">取消</el-button>
        <el-button type="primary" @click="doGrant">确认发放</el-button>
      </template>
    </el-dialog>
  `,
};
