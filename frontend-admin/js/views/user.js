import { reactive, ref, onMounted } from 'vue';
const { ElMessage } = window.ElementPlus;
import { api, fmtTime, fmtMoney } from '../api.js';
import { usePageList } from '../ui.js';

export default {
    name: 'UserList',
    setup() {
        const keyword = ref('');
        const { loading, list, total, page, pageSize, reload } = usePageList((p) =>
            api.get('/manage/user/index', { ...p, keyword: keyword.value })
        );
        const detail = reactive({ show: false, user: null });
        const adjust = reactive({ show: false, user: null, kind: 'balance', amount: 0 });

        async function openDetail(row) {
            const r = await api.get('/manage/user/view', { id: row.id });
            if (r.code === 0) { detail.user = r.data.user; detail.show = true; }
        }
        function openAdjust(row, kind) {
            adjust.user = row; adjust.kind = kind; adjust.amount = 0; adjust.show = true;
        }
        async function doAdjust() {
            if (!adjust.amount) return ElMessage.warning('请输入调整数值');
            const path = adjust.kind === 'balance' ? '/manage/user/adjust-balance' : '/manage/user/adjust-integral';
            const r = await api.post(path, { id: adjust.user.id, amount: adjust.amount });
            if (r.code === 0) { ElMessage.success(r.message); adjust.show = false; reload(); } else ElMessage.error(r.message);
        }
        async function setRemark(row) {
            const r = await api.post('/manage/user/set-remark', { id: row.id, remark: row.remark || '' });
            if (r.code === 0) ElMessage.success('已保存');
        }
        async function toggle(row) {
            const r = await api.post('/manage/user/toggle', { id: row.id });
            if (r.code === 0) { ElMessage.success('已更新'); reload(); }
        }
        onMounted(reload);
        return { keyword, loading, list, total, page, pageSize, reload, detail, adjust, openDetail, openAdjust, doAdjust, setRemark, toggle, fmtTime, fmtMoney };
    },
    template: `
    <div class="ns-card tight">
      <div class="ns-filter">
        <el-input v-model="keyword" placeholder="昵称 / 手机号 / ID" clearable style="width:240px" @keyup.enter="reload(1)"></el-input>
        <el-button type="primary" @click="reload(1)">查询</el-button>
      </div>
    </div>
    <div class="ns-card">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70"></el-table-column>
        <el-table-column label="用户" min-width="180">
          <template #default="{row}"><div class="ns-title-cell">{{ row.nickname || '用户' + row.id }}</div><div class="ns-mut">{{ row.mobile }}</div></template>
        </el-table-column>
        <el-table-column label="余额" width="110"><template #default="{row}"><span class="ns-money">{{ fmtMoney(row.balance) }}</span></template></el-table-column>
        <el-table-column prop="integral" label="积分" width="90"></el-table-column>
        <el-table-column label="注册时间" width="160"><template #default="{row}">{{ fmtTime(row.created_at) }}</template></el-table-column>
        <el-table-column label="状态" width="80">
          <template #default="{row}"><el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">{{ row.status === 1 ? '正常' : '禁用' }}</el-tag></template>
        </el-table-column>
        <el-table-column label="备注" min-width="140">
          <template #default="{row}"><el-input v-model="row.remark" size="small" placeholder="备注" @change="setRemark(row)"></el-input></template>
        </el-table-column>
        <el-table-column label="操作" width="230" fixed="right">
          <template #default="{row}">
            <el-button link type="primary" @click="openDetail(row)">详情</el-button>
            <el-button link type="success" @click="openAdjust(row, 'balance')">调余额</el-button>
            <el-button link type="warning" @click="openAdjust(row, 'integral')">调积分</el-button>
            <el-button link :type="row.status === 1 ? 'danger' : 'success'" @click="toggle(row)">{{ row.status === 1 ? '禁用' : '启用' }}</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="ns-pagebar">
        <el-pagination background layout="total, prev, pager, next, sizes" :total="total"
          v-model:current-page="page" v-model:page-size="pageSize" :page-sizes="[10, 20, 50]"
          @current-change="reload()" @size-change="reload(1)"></el-pagination>
      </div>
    </div>

    <el-dialog v-model="detail.show" title="用户详情" width="640px">
      <template v-if="detail.user">
        <el-descriptions :column="2" border size="small">
          <el-descriptions-item label="ID">{{ detail.user.id }}</el-descriptions-item>
          <el-descriptions-item label="昵称">{{ detail.user.nickname }}</el-descriptions-item>
          <el-descriptions-item label="手机号">{{ detail.user.mobile }}</el-descriptions-item>
          <el-descriptions-item label="订单数">{{ detail.user.order_count }}</el-descriptions-item>
          <el-descriptions-item label="余额">{{ fmtMoney(detail.user.balance) }}</el-descriptions-item>
          <el-descriptions-item label="积分">{{ detail.user.integral }}</el-descriptions-item>
          <el-descriptions-item label="注册时间">{{ fmtTime(detail.user.created_at) }}</el-descriptions-item>
          <el-descriptions-item label="状态">{{ detail.user.status === 1 ? '正常' : '禁用' }}</el-descriptions-item>
        </el-descriptions>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px">
          <div>
            <b>余额流水（近20条）</b>
            <el-table :data="detail.user.balance_logs" size="small" style="margin-top:8px" max-height="300">
              <el-table-column label="变动" width="90"><template #default="{row}"><span :style="{color: row.num >= 0 ? '#1a9f6d' : '#e05a4e'}">{{ row.num >= 0 ? '+' : '' }}{{ row.num }}</span></template></el-table-column>
              <el-table-column prop="remark" label="说明" min-width="110"></el-table-column>
              <el-table-column label="时间" width="140"><template #default="{row}">{{ fmtTime(row.created_at) }}</template></el-table-column>
            </el-table>
          </div>
          <div>
            <b>积分流水（近20条）</b>
            <el-table :data="detail.user.integral_logs" size="small" style="margin-top:8px" max-height="300">
              <el-table-column label="变动" width="90"><template #default="{row}"><span :style="{color: row.num >= 0 ? '#1a9f6d' : '#e05a4e'}">{{ row.num >= 0 ? '+' : '' }}{{ row.num }}</span></template></el-table-column>
              <el-table-column prop="remark" label="说明" min-width="110"></el-table-column>
              <el-table-column label="时间" width="140"><template #default="{row}">{{ fmtTime(row.created_at) }}</template></el-table-column>
            </el-table>
          </div>
        </div>
      </template>
    </el-dialog>

    <el-dialog v-model="adjust.show" :title="adjust.kind === 'balance' ? '调整余额' : '调整积分'" width="400px">
      <el-form label-width="90px">
        <el-form-item label="用户">{{ adjust.user?.nickname }}（ID {{ adjust.user?.id }}）</el-form-item>
        <el-form-item :label="adjust.kind === 'balance' ? '金额' : '积分'">
          <el-input-number v-model="adjust.amount" :precision="adjust.kind === 'balance' ? 2 : 0" style="width:200px"></el-input-number>
          <div class="ns-mut" style="margin-top:4px">正数增加，负数扣减</div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="adjust.show = false">取消</el-button>
        <el-button type="primary" @click="doAdjust">确认调整</el-button>
      </template>
    </el-dialog>
  `,
};
