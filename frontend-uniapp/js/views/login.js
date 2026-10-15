import { ref } from 'vue';
import { api, toast } from '../api.js';
import { saveSession } from '../store.js';

export default {
    name: 'LoginPage',
    setup() {
        const mobile = ref('');
        const password = ref('');
        const busy = ref(false);

        async function submit() {
            if (!/^1\d{10}$/.test(mobile.value)) {
                toast('请输入正确的手机号');
                return;
            }
            if (password.value.length < 6) {
                toast('密码至少 6 位');
                return;
            }
            busy.value = true;
            const r = await api.post('/api/passport/mobile-login', {
                mobile: mobile.value,
                password: password.value,
            });
            busy.value = false;
            if (r.code !== 0) {
                toast(r.message || '登录失败');
                return;
            }
            saveSession(r.data.token, r.data.profile);
            toast('登录成功');
            if (typeof window.__refreshCartBadge === 'function') window.__refreshCartBadge();
            const redirect = sessionStorage.getItem('ns_login_redirect');
            sessionStorage.removeItem('ns_login_redirect');
            location.hash = redirect || '#/';
        }

        return { mobile, password, busy, submit };
    },
    template: `
    <div class="u-page no-tab">
      <div class="u-login">
        <div class="logo">
          <div class="c">牛</div>
          <h1>牛视商城</h1>
        </div>
        <div class="u-form">
          <div class="row">
            <span class="lb">手机号</span>
            <input type="tel" maxlength="11" placeholder="请输入手机号" v-model="mobile">
          </div>
          <div class="row">
            <span class="lb">密码</span>
            <input type="password" placeholder="请输入密码（首次登录自动注册）" v-model="password">
          </div>
        </div>
        <div style="padding:20px 4px">
          <button class="u-btn block" :disabled="busy" @click="submit">{{ busy ? '登录中…' : '登录' }}</button>
        </div>
        <div class="u-mut" style="text-align:center">未注册的手机号将自动创建账号</div>
      </div>
    </div>
  `,
};
