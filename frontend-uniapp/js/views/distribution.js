import { ref, computed, onMounted } from 'vue';
import { api, fmtTime, toast } from '../api.js';
import { Navbar, Empty } from '../ui.js';

export default {
    name: 'DistributionPage',
    components: { Navbar, Empty },
    setup() {
        const TABS = [
            { key: 'overview', label: '概览' },
            { key: 'team', label: '我的团队' },
            { key: 'commission', label: '佣金明细' },
            { key: 'withdraw', label: '提现' },
        ];
        const tab = ref('overview');
        const info = ref(null);
        const team = ref([]);
        const teamLevel = ref(1);
        const commissions = ref([]);
        const commissionStatus = ref('');
        const logs = ref([]);
        const inviteCode = ref('');
        const amount = ref('');
        const busy = ref(false);

        const available = computed(() => {
            const used = logs.value
                .filter((w) => Number(w.status) !== 2)
                .reduce((sum, w) => sum + Number(w.amount || 0), 0);
            return Math.max(0, Number(info.value?.settledAmount || 0) - used);
        });
        const minWithdraw = computed(() => Number(info.value?.config?.min_withdraw || 0));

        async function loadInfo() {
            const r = await api.get('/distribution/center/index');
            if (r.code === 0) info.value = r.data;
        }
        async function loadTeam() {
            const r = await api.get('/distribution/center/team', { level: teamLevel.value });
            if (r.code === 0) team.value = r.data.list || [];
        }
        async function loadCommissions() {
            const r = await api.get('/distribution/center/commissions', { status: commissionStatus.value });
            if (r.code === 0) commissions.value = r.data.list || [];
        }
        async function loadLogs() {
            const r = await api.get('/distribution/center/withdraw-logs');
            if (r.code === 0) logs.value = r.data.list || [];
        }

        async function load() {
            await loadInfo();
            if (tab.value === 'team') await loadTeam();
            if (tab.value === 'commission') await loadCommissions();
            if (tab.value === 'withdraw') await loadLogs();
        }
        function setTab(k) {
            tab.value = k;
            load();
        }
        function setTeamLevel(l) {
            teamLevel.value = l;
            loadTeam();
        }
        function setCommissionStatus(s) {
            commissionStatus.value = s;
            loadCommissions();
        }

        async function bind() {
            if (!inviteCode.value.trim()) {
                toast('请输入邀请码');
                return;
            }
            busy.value = true;
            const r = await api.post('/distribution/center/bind', { inviteCode: inviteCode.value.trim() });
            busy.value = false;
            toast(r.message || '绑定成功', r.code === 0 ? 'info' : 'warn');
            if (r.code === 0) {
                inviteCode.value = '';
                loadInfo();
            }
        }

        async function copyCode() {
            const code = info.value?.inviteCode || '';
            try {
                await navigator.clipboard.writeText(code);
                toast('邀请码已复制');
            } catch (e) {
                toast('邀请码：' + code);
            }
        }

        async function withdraw() {
            const v = Number(amount.value);
            if (!(v > 0)) {
                toast('请输入提现金额');
                return;
            }
            busy.value = true;
            const r = await api.post('/distribution/center/withdraw-apply', { amount: v });
            busy.value = false;
            toast(r.message || '已提交', r.code === 0 ? 'info' : 'warn');
            if (r.code === 0) {
                amount.value = '';
                loadLogs();
                loadInfo();
            }
        }

        onMounted(load);

        return {
            TABS, tab, info, team, teamLevel, commissions, commissionStatus, logs,
            inviteCode, amount, busy, available, minWithdraw,
            setTab, setTeamLevel, setCommissionStatus, bind, copyCode, withdraw, fmtTime,
        };
    },
    template: `
    <div class="u-page no-tab">
      <Navbar title="分销中心"></Navbar>

      <div class="u-dist-head" v-if="info">
        <div style="font-size:13px;opacity:.9">累计佣金（元）</div>
        <div style="font-size:30px;font-weight:700;margin-top:4px">{{ info.totalAmount.toFixed(2) }}</div>
        <div class="nums">
          <div class="n"><b>{{ info.teamLevel1 }}</b>一级团队</div>
          <div class="n"><b>{{ info.teamLevel2 }}</b>二级团队</div>
          <div class="n"><b>{{ info.pendingAmount.toFixed(2) }}</b>待结算</div>
          <div class="n"><b>{{ info.settledAmount.toFixed(2) }}</b>已入账</div>
        </div>
      </div>

      <div class="u-status-tabs">
        <span class="t" v-for="t in TABS" :key="t.key" :class="{on: tab === t.key}" @click="setTab(t.key)">{{ t.label }}</span>
      </div>

      <template v-if="tab === 'overview' && info">
        <div class="u-card">
          <div style="display:flex;align-items:center">
            <div style="flex:1">
              <div class="u-mut">我的邀请码</div>
              <div style="font-size:22px;font-weight:700;letter-spacing:2px;margin-top:4px">{{ info.inviteCode }}</div>
            </div>
            <button class="u-btn mini" @click="copyCode">复制</button>
          </div>
        </div>
        <div class="u-card">
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span class="u-mut">钱包余额</span>
            <b style="font-size:17px" class="u-money">¥{{ info.balance.toFixed(2) }}</b>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px">
            <span class="u-mut">可提现佣金</span>
            <b style="font-size:17px" class="u-money">¥{{ available.toFixed(2) }}</b>
          </div>
          <div class="u-mut" style="margin-top:10px">
            佣金比例：一级 {{ info.config.first_ratio }}%，二级 {{ info.config.second_ratio }}%
          </div>
        </div>
        <div class="u-card" v-if="!Number(info.parentId)">
          <div style="font-weight:600;margin-bottom:8px">绑定上级</div>
          <div style="display:flex;gap:8px">
            <input v-model="inviteCode" placeholder="请输入好友邀请码" style="flex:1;border:1px solid #ddd;border-radius:8px;padding:9px 10px">
            <button class="u-btn" :disabled="busy" @click="bind">绑定</button>
          </div>
          <div class="u-mut" style="margin-top:8px">每人仅可绑定一次上级，绑定后不可修改</div>
        </div>
        <div class="u-card" v-else>
          <span class="u-mut">已绑定上级</span>
          <span class="u-tag" style="margin-left:8px">不可修改</span>
        </div>
      </template>

      <template v-if="tab === 'team'">
        <div class="u-status-tabs" style="position:static">
          <span class="t" :class="{on: teamLevel === 1}" @click="setTeamLevel(1)">一级团队</span>
          <span class="t" :class="{on: teamLevel === 2}" @click="setTeamLevel(2)">二级团队</span>
        </div>
        <Empty v-if="!team.length" icon="👥" text="暂无团队成员，快去邀请好友吧"></Empty>
        <div class="u-group" v-for="m in team" :key="m.userId">
          <div class="u-cell">
            <img v-if="m.avatar" :src="m.avatar" style="width:40px;height:40px;border-radius:50%;object-fit:cover">
            <span v-else style="width:40px;height:40px;border-radius:50%;background:#eef8f6;display:flex;align-items:center;justify-content:center">👤</span>
            <div style="flex:1">
              <div>{{ m.nickname }}</div>
              <div class="u-mut">加入时间 {{ fmtTime(m.bindAt) }}</div>
            </div>
            <span class="value">贡献佣金 ¥{{ m.earnedAmount.toFixed(2) }}</span>
          </div>
        </div>
      </template>

      <template v-if="tab === 'commission'">
        <div class="u-status-tabs" style="position:static">
          <span class="t" :class="{on: commissionStatus === ''}" @click="setCommissionStatus('')">全部</span>
          <span class="t" :class="{on: commissionStatus === '0'}" @click="setCommissionStatus('0')">待结算</span>
          <span class="t" :class="{on: commissionStatus === '1'}" @click="setCommissionStatus('1')">已入账</span>
        </div>
        <Empty v-if="!commissions.length" icon="💰" text="暂无佣金记录"></Empty>
        <div class="u-group" v-for="c in commissions" :key="c.id">
          <div class="u-cell">
            <div style="flex:1">
              <div>订单 {{ c.orderNo }}</div>
              <div class="u-mut">{{ c.level === 1 ? '一级' : '二级' }}佣金 · {{ fmtTime(c.createdAt) }}</div>
            </div>
            <div style="text-align:right">
              <div class="u-money">+¥{{ c.amount.toFixed(2) }}</div>
              <div class="u-tag" :class="c.status === 1 ? '' : 'gray'">{{ c.statusText }}</div>
            </div>
          </div>
        </div>
      </template>

      <template v-if="tab === 'withdraw'">
        <div class="u-card">
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span class="u-mut">可提现佣金</span>
            <b class="u-money" style="font-size:20px">¥{{ available.toFixed(2) }}</b>
          </div>
          <div style="display:flex;gap:8px;margin-top:12px">
            <input v-model="amount" type="number" step="0.01" :placeholder="'最低提现 ' + minWithdraw + ' 元'" style="flex:1;border:1px solid #ddd;border-radius:8px;padding:9px 10px">
            <button class="u-btn" :disabled="busy" @click="withdraw">申请提现</button>
          </div>
          <div class="u-mut" style="margin-top:8px">演示环境：审核通过后佣金将发放至钱包余额</div>
        </div>
        <div class="u-section-title">提现记录</div>
        <Empty v-if="!logs.length" icon="🧾" text="暂无提现记录"></Empty>
        <div class="u-group" v-for="w in logs" :key="w.id">
          <div class="u-cell">
            <div style="flex:1">
              <div>¥{{ w.amount.toFixed(2) }}</div>
              <div class="u-mut">{{ fmtTime(w.createdAt) }}</div>
            </div>
            <div style="text-align:right">
              <span class="u-tag" :class="w.status === 1 ? '' : (w.status === 2 ? 'danger' : 'warn')">{{ w.statusText }}</span>
              <div class="u-mut" v-if="w.remark">{{ w.remark }}</div>
            </div>
          </div>
        </div>
      </template>
    </div>
  `,
};
