import { ref, computed, onMounted } from 'vue';
import { api, toast } from '../api.js';
import { Navbar } from '../ui.js';

export default {
    name: 'CheckinPage',
    components: { Navbar },
    setup() {
        const info = ref(null);
        const month = ref(0);
        const busy = ref(false);
        const makeupTarget = ref(null);

        const WEEK = ['日', '一', '二', '三', '四', '五', '六'];

        /** 日历格子：前置空位 + 当月天数，附带签到状态 */
        const cells = computed(() => {
            const m = Number(month.value || info.value?.month || 0);
            if (!m) return [];
            const year = Math.floor(m / 100);
            const mon = m % 100;
            const first = new Date(year, mon - 1, 1);
            const lead = first.getDay();
            const total = new Date(year, mon, 0).getDate();
            const map = {};
            (info.value?.monthDays || []).forEach((d) => {
                map[d.ymd] = d;
            });
            const todayYmd = Number(
                String(new Date().getFullYear()) +
                String(new Date().getMonth() + 1).padStart(2, '0') +
                String(new Date().getDate()).padStart(2, '0')
            );
            const out = [];
            for (let i = 0; i < lead; i++) out.push({ key: 'b' + i, blank: true });
            for (let d = 1; d <= total; d++) {
                const ymd = m * 100 + d;
                const rec = map[ymd] || null;
                out.push({
                    key: String(ymd),
                    ymd,
                    day: d,
                    signed: !!rec,
                    isMakeup: !!(rec && rec.isMakeup),
                    reward: rec ? rec.rewardIntegral : 0,
                    isToday: ymd === todayYmd,
                    past: ymd < todayYmd,
                });
            }
            return out;
        });

        async function load(m) {
            const params = m ? { month: m } : {};
            const r = await api.get('/check_in/check/status', params);
            if (r.code === 0) {
                info.value = r.data;
                month.value = r.data.month;
            }
        }

        async function sign() {
            busy.value = true;
            const r = await api.post('/check_in/check/do');
            busy.value = false;
            if (r.code === 0) {
                toast(r.message || '签到成功');
                load();
            } else toast(r.message, 'warn');
        }

        /** 点击过去未签到的日期先弹出确认面板，确认后再提交 */
        async function makeup(cell) {
            if (!cell.past || cell.signed || makeupTarget.value) return;
            makeupTarget.value = cell;
        }

        async function confirmMakeup() {
            const cell = makeupTarget.value;
            if (!cell) return;
            makeupTarget.value = null;
            const r = await api.post('/check_in/check/makeup', { ymd: cell.ymd });
            toast(r.message || '补签成功', r.code === 0 ? 'info' : 'warn');
            if (r.code === 0) load();
        }

        function shift(delta) {
            const m = Number(month.value);
            let y = Math.floor(m / 100);
            let mon = m % 100 + delta;
            if (mon < 1) { mon = 12; y -= 1; }
            if (mon > 12) { mon = 1; y += 1; }
            load(y * 100 + mon);
        }

        onMounted(() => load());

        return { info, month, busy, makeupTarget, WEEK, cells, sign, makeup, confirmMakeup, shift };
    },
    template: `
    <div class="u-page no-tab">
      <Navbar title="每日签到"></Navbar>
      <template v-if="info">
        <div class="u-check-head">
          <div style="font-size:13px;opacity:.9">已连续签到</div>
          <div class="big">{{ info.continuous }} 天</div>
          <div style="font-size:12px;opacity:.9">累计签到 {{ info.totalDays }} 天 · 每日 +{{ info.config.base_integral }} 积分</div>
          <div style="margin-top:14px">
            <button class="u-btn block" :style="{background: info.todaySigned ? 'rgba(255,255,255,.35)' : ''}" :disabled="info.todaySigned || busy" @click="sign">
              {{ info.todaySigned ? '今日已签到' : '立即签到' }}
            </button>
          </div>
        </div>

        <div class="u-cal">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
            <button class="u-btn mini plain" @click="shift(-1)">上月</button>
            <b>{{ Math.floor(month / 100) }}年 {{ month % 100 }}月</b>
            <button class="u-btn mini plain" @click="shift(1)">下月</button>
          </div>
          <div class="week">
            <span v-for="w in WEEK" :key="w">{{ w }}</span>
          </div>
          <div class="days">
            <div v-for="c in cells" :key="c.key"
                 class="day"
                 :class="{blank: c.blank, signed: c.signed && !c.isMakeup, makeup: c.isMakeup, today: c.isToday, dim: c.blank || (!c.past && !c.isToday)}"
                 @click="c.blank ? null : makeup(c)">
              <template v-if="!c.blank">
                <span :style="c.signed ? 'color:#fff' : ''">{{ c.day }}</span>
                <div style="font-size:9px;line-height:1.2" v-if="c.signed">{{ c.isMakeup ? '补' : '+' + c.reward }}</div>
              </template>
            </div>
          </div>
          <div class="u-mut" style="margin-top:10px;text-align:center">
            连续满 7 天额外 +{{ info.config.seven_bonus }} 积分，满 30 天再 +{{ info.config.thirty_bonus }} 积分；点击过去未签到的日期可补签（消耗 {{ info.config.makeup_cost }} 积分）
          </div>
        </div>
      </template>

      <template v-if="makeupTarget">
        <div class="u-mask" @click="makeupTarget = null"></div>
        <div class="u-sheet">
          <div class="head">补签确认<span class="close" @click="makeupTarget = null">✕</span></div>
          <div style="padding:18px 16px 8px;text-align:center">
            <div style="font-size:16px;font-weight:600;margin-bottom:8px">
              补签 {{ makeupTarget.ymd }}
            </div>
            <div class="u-mut">将消耗 {{ info.config.makeup_cost }} 积分，补签不计入连续签到天数</div>
          </div>
          <div style="display:flex;gap:10px;padding:8px 16px 20px">
            <button class="u-btn plain" style="flex:1;height:42px" @click="makeupTarget = null">取消</button>
            <button class="u-btn" style="flex:1;height:42px" @click="confirmMakeup">确认补签</button>
          </div>
        </div>
      </template>
    </div>
  `,
};
