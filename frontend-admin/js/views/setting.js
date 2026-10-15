import { reactive, ref, onMounted } from 'vue';
const { ElMessage, ElMessageBox } = window.ElementPlus;
import { api, assetUrl, fmtTime } from '../api.js';
import { usePageList, ImgUpload } from '../ui.js';

const TABS = [
    { key: 'base', label: '基础设置' },
    { key: 'trade', label: '交易设置' },
    { key: 'wechat', label: '微信支付' },
    { key: 'alipay', label: '支付宝' },
    { key: 'sms', label: '短信设置' },
];

export default {
    name: 'SettingPage',
    components: { ImgUpload },
    setup() {
        const tab = ref('base');
        const settings = reactive({});
        const saving = ref(false);

        async function load() {
            const r = await api.get('/manage/setting/get', { code: 'all' });
            if (r.code === 0) {
                for (const [k, v] of Object.entries(r.data.settings)) settings[k] = { ...v };
                if (!settings.trade?.order_pay_channels?.length) {
                    settings.trade = settings.trade || {};
                    settings.trade.order_pay_channels = ['wechat', 'alipay', 'balance'];
                }
            }
        }
        async function save(code) {
            saving.value = true;
            try {
                const r = await api.post('/manage/setting/save', { code, value: settings[code] });
                if (r.code === 0) ElMessage.success('已保存'); else ElMessage.error(r.message);
            } finally { saving.value = false; }
        }

        /* ---- 素材库 ---- */
        const { loading, list, total, page, pageSize, reload } = usePageList((p) => api.get('/manage/setting/attachments', p));
        const matTab = ref('settings');
        async function delAttachment(row) {
            await ElMessageBox.confirm('删除该素材文件？', '提示', { type: 'warning' });
            const r = await api.post('/manage/setting/delete-attachment', { id: row.id });
            if (r.code === 0) { ElMessage.success('已删除'); reload(); }
        }
        onMounted(load);
        return { TABS, tab, settings, saving, save, matTab, loading, list, total, page, pageSize, reload, delAttachment, assetUrl, fmtTime };
    },
    template: `
    <div class="ns-card tight">
      <el-tabs v-model="tab">
        <el-tab-pane v-for="t in TABS" :key="t.key" :label="t.label" :name="t.key"></el-tab-pane>
        <el-tab-pane label="素材库" name="material"></el-tab-pane>
      </el-tabs>
    </div>

    <div class="ns-card" v-if="tab === 'base' && settings.base">
      <el-form label-width="130px" style="max-width:620px">
        <el-form-item label="商城名称"><el-input v-model="settings.base.name"></el-input></el-form-item>
        <el-form-item label="商城Logo"><img-upload v-model="settings.base.logo" width="80px" height="80px"></img-upload></el-form-item>
        <el-form-item label="客服电话"><el-input v-model="settings.base.contact_phone"></el-input></el-form-item>
        <el-form-item label="服务时间"><el-input v-model="settings.base.service_time"></el-input></el-form-item>
        <el-form-item><el-button type="primary" :loading="saving" @click="save('base')">保存设置</el-button></el-form-item>
      </el-form>
    </div>

    <div class="ns-card" v-if="tab === 'trade' && settings.trade">
      <el-form label-width="180px" style="max-width:660px">
        <el-form-item label="待付款自动关闭（分钟）"><el-input-number v-model="settings.trade.auto_close_minutes" :min="5"></el-input-number></el-form-item>
        <el-form-item label="发货后自动收货（天）"><el-input-number v-model="settings.trade.auto_receive_days" :min="1"></el-input-number></el-form-item>
        <el-form-item label="收货后自动完成（天）"><el-input-number v-model="settings.trade.auto_finish_days" :min="0"></el-input-number></el-form-item>
        <el-form-item label="每100积分抵扣金额">
          <el-input-number v-model="settings.trade.integral_deduction_rate" :min="0" :precision="2"></el-input-number>
          <span class="ns-mut" style="margin-left:8px">0 为不开启积分抵扣</span>
        </el-form-item>
        <el-form-item label="默认运费模板">
          <el-input-number v-model="settings.trade.freight_template_id" :min="0"></el-input-number>
          <span class="ns-mut" style="margin-left:8px">0 为不使用</span>
        </el-form-item>
        <el-form-item label="可用支付渠道">
          <el-checkbox-group v-model="settings.trade.order_pay_channels">
            <el-checkbox value="wechat">微信支付</el-checkbox>
            <el-checkbox value="alipay">支付宝</el-checkbox>
            <el-checkbox value="balance">余额支付</el-checkbox>
          </el-checkbox-group>
        </el-form-item>
        <el-form-item><el-button type="primary" :loading="saving" @click="save('trade')">保存设置</el-button></el-form-item>
      </el-form>
    </div>

    <div class="ns-card" v-if="tab === 'wechat' && settings.wechat">
      <el-form label-width="130px" style="max-width:620px">
        <el-form-item label="AppID"><el-input v-model="settings.wechat.app_id"></el-input></el-form-item>
        <el-form-item label="AppSecret"><el-input v-model="settings.wechat.app_secret" show-password></el-input></el-form-item>
        <el-form-item label="商户号"><el-input v-model="settings.wechat.mch_id"></el-input></el-form-item>
        <el-form-item label="支付密钥"><el-input v-model="settings.wechat.pay_key" show-password></el-input></el-form-item>
        <el-form-item><el-button type="primary" :loading="saving" @click="save('wechat')">保存设置</el-button></el-form-item>
      </el-form>
    </div>

    <div class="ns-card" v-if="tab === 'alipay' && settings.alipay">
      <el-form label-width="130px" style="max-width:620px">
        <el-form-item label="AppID"><el-input v-model="settings.alipay.app_id"></el-input></el-form-item>
        <el-form-item label="应用私钥"><el-input v-model="settings.alipay.private_key" type="textarea" :rows="4" show-password></el-input></el-form-item>
        <el-form-item label="平台公钥"><el-input v-model="settings.alipay.public_key" type="textarea" :rows="4"></el-input></el-form-item>
        <el-form-item><el-button type="primary" :loading="saving" @click="save('alipay')">保存设置</el-button></el-form-item>
      </el-form>
    </div>

    <div class="ns-card" v-if="tab === 'sms' && settings.sms">
      <el-form label-width="130px" style="max-width:620px">
        <el-form-item label="驱动">
          <el-radio-group v-model="settings.sms.driver">
            <el-radio value="log">日志（调试）</el-radio><el-radio value="aliyun">阿里云</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="短信签名"><el-input v-model="settings.sms.sign"></el-input></el-form-item>
        <el-form-item label="AccessKey"><el-input v-model="settings.sms.access_key"></el-input></el-form-item>
        <el-form-item label="SecretKey"><el-input v-model="settings.sms.secret_key" show-password></el-input></el-form-item>
        <el-form-item><el-button type="primary" :loading="saving" @click="save('sms')">保存设置</el-button></el-form-item>
      </el-form>
    </div>

    <div v-if="tab === 'material'">
      <div class="ns-card tight">
        <div class="ns-filter">
          <span style="font-weight:600">素材库</span>
          <div style="flex:1"></div>
          <el-upload :action="uploadAction" name="file" :headers="authHeaders" :show-file-list="false" accept="image/*" :on-success="onUp" :on-error="onUpErr">
            <el-button type="primary">上传素材</el-button>
          </el-upload>
        </div>
      </div>
      <div class="ns-card">
        <el-table :data="list" v-loading="loading" stripe>
          <el-table-column prop="id" label="ID" width="70"></el-table-column>
          <el-table-column label="预览" width="90"><template #default="{row}"><img class="ns-table-cover" :src="assetUrl(row.url || row.path)"></template></el-table-column>
          <el-table-column prop="name" label="文件名" min-width="160"></el-table-column>
          <el-table-column prop="path" label="路径" min-width="240"></el-table-column>
          <el-table-column label="大小" width="100"><template #default="{row}">{{ (row.size/1024).toFixed(1) }} KB</template></el-table-column>
          <el-table-column label="时间" width="160"><template #default="{row}">{{ fmtTime(row.created_at) }}</template></el-table-column>
          <el-table-column label="操作" width="90" fixed="right"><template #default="{row}"><el-button link type="danger" @click="delAttachment(row)">删除</el-button></template></el-table-column>
        </el-table>
        <div class="ns-pagebar">
          <el-pagination background layout="total, prev, pager, next" :total="total"
            v-model:current-page="page" v-model:page-size="pageSize"
            @current-change="reload()" @size-change="reload(1)"></el-pagination>
        </div>
      </div>
    </div>
  `,
    computed: {
        uploadAction() { return api.uploadUrl; },
        authHeaders() { return api.authHeaders(); },
    },
    methods: {
        onUp(res) {
            if (res.code === 0) { ElMessage.success('上传成功'); this.reload(1); }
            else ElMessage.error(res.message || '上传失败');
        },
        onUpErr() { ElMessage.error('上传失败'); },
    },
};
