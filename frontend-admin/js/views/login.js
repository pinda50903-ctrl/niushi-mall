import { reactive, ref } from 'vue';
const { ElMessage } = window.ElementPlus;
import { api } from '../api.js';
import { saveSession, isTenant } from '../store.js';

export default {
    name: 'LoginPage',
    setup() {
        const tab = ref('tenant');
        const form = reactive({ username: '', password: '' });
        const loading = ref(false);

        async function submit() {
            if (!form.username || !form.password) return ElMessage.warning('请输入账号和密码');
            loading.value = true;
            try {
                const path = tab.value === 'tenant' ? '/manage/passport/login' : '/platform/passport/login';
                const r = await api.post(path, { username: form.username, password: form.password });
                if (r.code === 0) {
                    saveSession(r.data.token, r.data.profile);
                    ElMessage.success('欢迎回来，' + (r.data.profile.nickname || r.data.profile.username));
                    location.hash = tab.value === 'tenant' ? '#/dashboard' : '#/p/overview';
                    location.reload();
                } else {
                    ElMessage.error(r.message || '登录失败');
                }
            } finally {
                loading.value = false;
            }
        }
        return { tab, form, loading, submit };
    },
    template: `
    <div class="ns-login">
      <div class="ns-login-panel">
        <div class="ns-login-logo">
          <div class="logo">牛</div>
          <h1>牛视商城管理中心</h1>
        </div>
        <p class="ns-login-sub">全新多商户商城系统 · 管理后台</p>
        <el-tabs v-model="tab">
          <el-tab-pane label="商城管理" name="tenant"></el-tab-pane>
          <el-tab-pane label="平台总部" name="platform"></el-tab-pane>
        </el-tabs>
        <el-form @submit.prevent>
          <el-form-item>
            <el-input v-model="form.username" placeholder="账号" size="large" clearable>
              <template #prefix><el-icon><User/></el-icon></template>
            </el-input>
          </el-form-item>
          <el-form-item>
            <el-input v-model="form.password" type="password" placeholder="密码" size="large" show-password @keyup.enter="submit">
              <template #prefix><el-icon><Lock/></el-icon></template>
            </el-input>
          </el-form-item>
          <el-button type="primary" size="large" style="width:100%" :loading="loading" @click="submit">登 录</el-button>
        </el-form>
        <div class="ns-login-footer">牛视商城 · 独立部署版</div>
      </div>
    </div>
  `,
};
