import { ref, onMounted, computed } from 'vue';
import { api, fmtMoney } from '../api.js';

export default {
    name: 'DashboardPage',
    setup() {
        const data = ref(null);
        const trend = ref([]);
        const metric = ref('orderCount');

        const metrics = [
            { key: 'orderCount', label: '下单数', color: '#0e9f8a' },
            { key: 'payAmount', label: '成交额(元)', color: '#f08c1d' },
            { key: 'newUsers', label: '新增用户', color: '#4f7cf0' },
            { key: 'visitCount', label: '访问量', color: '#9a6bf2' },
        ];

        const maxVal = computed(() => Math.max(1, ...trend.value.map((d) => Number(d[metric.value] || 0))));

        async function load() {
            const [a, b] = await Promise.all([api.get('/manage/dashboard/index'), api.get('/manage/dashboard/trend', { days: 14 })]);
            if (a.code === 0) data.value = a.data;
            if (b.code === 0) trend.value = b.data.list || [];
        }
        onMounted(load);
        return { data, trend, metric, metrics, maxVal, fmtMoney };
    },
    template: `
    <div>
      <template v-if="data">
        <div class="ns-stats">
          <div class="ns-stat"><div class="k">今日成交额</div><div class="v">{{ fmtMoney(data.today.payAmount) }}</div></div>
          <div class="ns-stat"><div class="k">今日支付订单</div><div class="v">{{ data.today.payCount }}<small> 单</small></div></div>
          <div class="ns-stat"><div class="k">今日新增用户</div><div class="v">{{ data.today.newUsers }}<small> 人</small></div></div>
          <div class="ns-stat"><div class="k">今日访问量</div><div class="v">{{ data.today.visitCount }}</div></div>
        </div>
        <div class="ns-stats">
          <div class="ns-stat"><div class="k">累计用户</div><div class="v">{{ data.total.users }}</div></div>
          <div class="ns-stat"><div class="k">在售商品</div><div class="v">{{ data.total.goodsOnSale }}</div></div>
          <div class="ns-stat"><div class="k">待发货订单</div><div class="v">{{ data.total.pendingDelivery }}</div></div>
          <div class="ns-stat"><div class="k">累计成交额</div><div class="v">{{ fmtMoney(data.total.payAmount) }}</div></div>
        </div>

        <div class="ns-grid2">
          <div class="ns-card">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
              <b>近 14 天趋势</b>
              <el-radio-group v-model="metric" size="small">
                <el-radio-button v-for="m in metrics" :key="m.key" :value="m.key">{{ m.label }}</el-radio-button>
              </el-radio-group>
            </div>
            <div class="ns-chart" v-if="trend.length">
              <div class="bar-wrap" v-for="d in trend" :key="d.stat_date">
                <span class="ns-mut">{{ metric === 'payAmount' ? Number(d[metric]||0).toFixed(0) : (d[metric]||0) }}</span>
                <div class="bar" :class="{amount: metric==='payAmount'}" :style="{height: Math.max(2, (d[metric]||0)/maxVal*160) + 'px'}"></div>
                <span class="x">{{ (d.stat_date||'').slice(5) }}</span>
              </div>
            </div>
            <el-empty v-else description="暂无统计数据" :image-size="80"></el-empty>
          </div>
          <div class="ns-card">
            <b>经营概况</b>
            <el-descriptions :column="1" border style="margin-top:12px" size="small">
              <el-descriptions-item label="今日下单">{{ data.today.orderCount }} 单</el-descriptions-item>
              <el-descriptions-item label="累计用户">{{ data.total.users }} 人</el-descriptions-item>
              <el-descriptions-item label="在售商品">{{ data.total.goodsOnSale }} 件</el-descriptions-item>
              <el-descriptions-item label="待发货">{{ data.total.pendingDelivery }} 单</el-descriptions-item>
            </el-descriptions>
          </div>
        </div>
      </template>
      <el-skeleton v-else :rows="8" animated style="margin-top:10px"></el-skeleton>
    </div>
  `,
};
