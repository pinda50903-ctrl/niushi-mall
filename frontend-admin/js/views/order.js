import { reactive, ref, onMounted } from 'vue';
const { ElMessage, ElMessageBox } = window.ElementPlus;
import { api, fmtTime, fmtMoney, assetUrl } from '../api.js';
import { usePageList } from '../ui.js';

export const STATUS_LABELS = { 10: '待付款', 20: '待发货', 30: '待收货', 40: '已完成', 50: '已关闭' };
export const STATUS_TYPES = { 10: 'warning', 20: 'primary', 30: 'primary', 40: 'success', 50: 'info' };

/* ============ 订单列表 ============ */
export const OrderList = {
    name: 'OrderList',
    setup() {
        const keyword = ref('');
        const status = ref('');
        const { loading, list, total, page, pageSize, reload } = usePageList((p) =>
            api.get('/manage/order/index', { ...p, keyword: keyword.value, status: status.value })
        );
        const deliverBox = reactive({ show: false, order: null, expressNo: '' });

        async function doDeliver() {
            if (!deliverBox.expressNo) return ElMessage.warning('请输入快递单号');
            const r = await api.post('/manage/order/deliver', { id: deliverBox.order.id, expressNo: deliverBox.expressNo });
            if (r.code === 0) { ElMessage.success('已发货'); deliverBox.show = false; reload(); } else ElMessage.error(r.message);
        }
        async function remark(row) {
            const { value } = await ElMessageBox.prompt('商家备注', '备注', { inputValue: row.seller_remark || '' });
            const r = await api.post('/manage/order/remark', { id: row.id, remark: value || '' });
            if (r.code === 0) { ElMessage.success('已保存'); row.seller_remark = value; }
        }
        onMounted(reload);
        return { keyword, status, loading, list, total, page, pageSize, reload, deliverBox, doDeliver, remark, fmtTime, fmtMoney, STATUS_LABELS, STATUS_TYPES };
    },
    template: `
    <div class="ns-card tight">
      <div class="ns-filter">
        <el-input v-model="keyword" placeholder="订单号 / 收件人 / 手机号" clearable style="width:240px" @keyup.enter="reload(1)"></el-input>
        <el-select v-model="status" placeholder="状态" clearable style="width:130px">
          <el-option v-for="(l,s) in STATUS_LABELS" :key="s" :label="l" :value="String(s)"></el-option>
        </el-select>
        <el-button type="primary" @click="reload(1)">查询</el-button>
        <div style="flex:1"></div>
        <el-button @click="$router.push('/refund')">售后管理</el-button>
      </div>
    </div>
    <div class="ns-card">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column label="订单" min-width="200">
          <template #default="{row}">
            <div class="ns-title-cell">{{ row.order?.order_no || row.order_no }}</div>
            <div class="ns-mut">{{ fmtTime(row.created_at) }}</div>
          </template>
        </el-table-column>
        <el-table-column label="商品" min-width="220">
          <template #default="{row}">
            <div v-for="it in (row.items || [])" :key="it.id" class="ns-mut" style="line-height:1.8">
              {{ it.goods_title }} × {{ it.num }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="实付" width="110"><template #default="{row}"><span class="ns-money">{{ fmtMoney(row.pay_amount) }}</span></template></el-table-column>
        <el-table-column label="收件人" width="150">
          <template #default="{row}"><div>{{ row.receiver_name }}</div><div class="ns-mut">{{ row.receiver_phone }}</div></template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{row}"><el-tag :type="STATUS_TYPES[row.status]" size="small">{{ row.status_label || STATUS_LABELS[row.status] }}</el-tag></template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button link type="primary" @click="$router.push('/order/' + row.id)">详情</el-button>
            <el-button v-if="row.status === 20" link type="success" @click="deliverBox.order = row; deliverBox.expressNo = ''; deliverBox.show = true">发货</el-button>
            <el-button link @click="remark(row)">备注</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="ns-pagebar">
        <el-pagination background layout="total, prev, pager, next, sizes" :total="total"
          v-model:current-page="page" v-model:page-size="pageSize" :page-sizes="[10, 20, 50]"
          @current-change="reload()" @size-change="reload(1)"></el-pagination>
      </div>
    </div>

    <el-dialog v-model="deliverBox.show" title="订单发货" width="420px">
      <el-form label-width="90px">
        <el-form-item label="快递单号" required><el-input v-model="deliverBox.expressNo" placeholder="如 SF123456789"></el-input></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="deliverBox.show = false">取消</el-button>
        <el-button type="primary" @click="doDeliver">确认发货</el-button>
      </template>
    </el-dialog>
  `,
};

/* ============ 订单详情 ============ */
export const OrderDetail = {
    name: 'OrderDetail',
    setup() {
        const id = Number((location.hash.match(/#\/order\/(\d+)/) || [])[1] || 0);
        const order = ref(null);
        const expressNo = ref('');

        async function load() {
            const r = await api.get('/manage/order/view', { id });
            if (r.code === 0) order.value = r.data.order;
        }
        async function doDeliver() {
            const r = await api.post('/manage/order/deliver', { id, expressNo: expressNo.value });
            if (r.code === 0) { ElMessage.success('已发货'); load(); } else ElMessage.error(r.message);
        }
        load();
        return { id, order, expressNo, doDeliver, fmtTime, fmtMoney, assetUrl, STATUS_LABELS, STATUS_TYPES };
    },
    template: `
    <div class="ns-card" v-if="order">
      <el-page-header @back="$router.back()" :content="'订单详情 · ' + order.order_no" style="margin-bottom:18px"></el-page-header>
      <el-descriptions :column="3" border size="small">
        <el-descriptions-item label="订单号">{{ order.order_no }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="STATUS_TYPES[order.status]" size="small">{{ order.status_label || STATUS_LABELS[order.status] }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="实付金额"><span class="ns-money">{{ fmtMoney(order.pay_amount) }}</span></el-descriptions-item>
        <el-descriptions-item label="下单时间">{{ fmtTime(order.created_at) }}</el-descriptions-item>
        <el-descriptions-item label="支付时间">{{ fmtTime(order.paid_at) }}</el-descriptions-item>
        <el-descriptions-item label="发货时间">{{ fmtTime(order.delivered_at) }}</el-descriptions-item>
        <el-descriptions-item label="收件人">{{ order.receiver_name }} {{ order.receiver_phone }}</el-descriptions-item>
        <el-descriptions-item label="收货地址" :span="2">{{ order.receiver_address || '-' }}</el-descriptions-item>
        <el-descriptions-item label="配送方式">{{ order.delivery_type === 1 ? '到店自提' : '快递配送' }}</el-descriptions-item>
        <el-descriptions-item label="快递单号">{{ order.express_no || '-' }}</el-descriptions-item>
        <el-descriptions-item label="商家备注">{{ order.seller_remark || '-' }}</el-descriptions-item>
        <el-descriptions-item label="买家留言">{{ order.buyer_remark || '-' }}</el-descriptions-item>
        <el-descriptions-item label="优惠券">{{ order.coupon_amount > 0 ? fmtMoney(order.coupon_amount) : '-' }}</el-descriptions-item>
        <el-descriptions-item label="积分抵扣">{{ order.integral_deduction > 0 ? fmtMoney(order.integral_deduction) : '-' }}</el-descriptions-item>
      </el-descriptions>

      <div v-if="order.status === 20" style="margin-top:14px;display:flex;gap:10px">
        <el-input v-model="expressNo" placeholder="输入快递单号发货" style="width:260px"></el-input>
        <el-button type="primary" @click="doDeliver">发货</el-button>
      </div>
    </div>
    <div class="ns-card" v-if="order">
      <b>商品明细</b>
      <el-table :data="order.items || []" style="margin-top:10px" size="small" border>
        <el-table-column label="商品" min-width="260">
          <template #default="{row}">
            <div style="display:flex;gap:8px;align-items:center">
              <img class="ns-table-cover" style="width:36px;height:36px" :src="assetUrl(row.goods_cover || row.cover)">
              <span>{{ row.goods_title }}<span v-if="row.sku_text" class="ns-mut">（{{ row.sku_text }}）</span></span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="单价" width="110"><template #default="{row}">{{ fmtMoney(row.price) }}</template></el-table-column>
        <el-table-column prop="num" label="数量" width="80"></el-table-column>
        <el-table-column label="小计" width="110"><template #default="{row}"><span class="ns-money">{{ fmtMoney(row.total_price) }}</span></template></el-table-column>
        <el-table-column label="售后" width="100">
          <template #default="{row}">
            <el-tag v-if="row.refund_state === 10" type="warning" size="small">申请中</el-tag>
            <el-tag v-else-if="row.refund_state === 20" type="success" size="small">已退款</el-tag>
            <span v-else class="ns-mut">-</span>
          </template>
        </el-table-column>
      </el-table>
    </div>
    <el-skeleton v-if="!order" :rows="8" animated></el-skeleton>
  `,
};

/* ============ 售后管理 ============ */
export const RefundList = {
    name: 'RefundList',
    setup() {
        const status = ref('');
        const REFUND_LABELS = { 10: '申请中', 20: '待退货', 30: '已拒绝', 40: '待收退货', 50: '已退款', 60: '已关闭' };
        const { loading, list, total, page, pageSize, reload } = usePageList((p) =>
            api.get('/manage/order/refunds', { ...p, status: status.value })
        );
        async function agree(row) {
            await ElMessageBox.confirm(`同意售后单 #${row.id}（${REFUND_LABELS[row.status]}）？`, '提示', { type: 'warning' });
            const r = await api.post('/manage/order/refund-agree', { id: row.id });
            if (r.code === 0) { ElMessage.success('已同意'); reload(); } else ElMessage.error(r.message);
        }
        async function refuse(row) {
            const { value } = await ElMessageBox.prompt('拒绝原因', '拒绝售后', { inputValue: '不符合售后条件' });
            const r = await api.post('/manage/order/refund-refuse', { id: row.id, reason: value || '' });
            if (r.code === 0) { ElMessage.success('已拒绝'); reload(); } else ElMessage.error(r.message);
        }
        async function receive(row) {
            const r = await api.post('/manage/order/refund-receive', { id: row.id });
            if (r.code === 0) { ElMessage.success('已收货'); reload(); } else ElMessage.error(r.message);
        }
        async function execute(row) {
            await ElMessageBox.confirm(`确认执行退款 ${fmtMoney(row.amount)}（退回用户余额）？`, '退款确认', { type: 'warning' });
            const r = await api.post('/manage/order/refund-execute', { id: row.id });
            if (r.code === 0) { ElMessage.success(r.message); reload(); } else ElMessage.error(r.message);
        }
        onMounted(reload);
        return { status, REFUND_LABELS, loading, list, total, page, pageSize, reload, agree, refuse, receive, execute, fmtTime, fmtMoney };
    },
    template: `
    <div class="ns-card tight">
      <div class="ns-filter">
        <el-select v-model="status" placeholder="状态" clearable style="width:150px">
          <el-option v-for="(l,s) in REFUND_LABELS" :key="s" :label="l" :value="String(s)"></el-option>
        </el-select>
        <el-button type="primary" @click="reload(1)">查询</el-button>
      </div>
    </div>
    <div class="ns-card">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="单号" width="70"></el-table-column>
        <el-table-column label="订单" width="180"><template #default="{row}">{{ row.order_no }}</template></el-table-column>
        <el-table-column label="类型" width="90">
          <template #default="{row}"><el-tag size="small" :type="row.type === 1 ? 'warning' : 'danger'">{{ row.type === 1 ? '仅退款' : '退货退款' }}</el-tag></template>
        </el-table-column>
        <el-table-column label="金额" width="110"><template #default="{row}"><span class="ns-money">{{ fmtMoney(row.amount) }}</span></template></el-table-column>
        <el-table-column label="原因" min-width="160"><template #default="{row}">{{ row.reason }}</template></el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{row}"><el-tag size="small" :type="row.status === 10 ? 'warning' : (row.status === 50 ? 'success' : 'info')">{{ row.status_label }}</el-tag></template>
        </el-table-column>
        <el-table-column label="申请时间" width="160"><template #default="{row}">{{ fmtTime(row.created_at) }}</template></el-table-column>
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{row}">
            <el-button v-if="row.status === 10" link type="success" @click="agree(row)">同意</el-button>
            <el-button v-if="[10,20,40].includes(row.status)" link type="danger" @click="refuse(row)">拒绝</el-button>
            <el-button v-if="row.status === 40" link type="primary" @click="receive(row)">确认收退货</el-button>
            <el-button v-if="[20,40,50].includes(row.status) && row.status !== 50" link type="warning" @click="execute(row)">执行退款</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="ns-pagebar">
        <el-pagination background layout="total, prev, pager, next" :total="total"
          v-model:current-page="page" v-model:page-size="pageSize"
          @current-change="reload()" @size-change="reload(1)"></el-pagination>
      </div>
    </div>
  `,
};
