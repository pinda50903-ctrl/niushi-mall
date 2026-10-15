import { ref, onMounted } from 'vue';
import { api, toast } from '../api.js';
import { Navbar } from '../ui.js';

export default {
    name: 'AddressEditPage',
    props: { id: { type: String, default: 'new' } },
    components: { Navbar },
    setup(props) {
        const isNew = props.id === 'new';
        const form = ref({ receiver: '', phone: '', region: '', address: '', isDefault: false });
        const redirect = new URLSearchParams(location.hash.split('?')[1] || '').get('redirect') || '/address';
        const saving = ref(false);

        if (!isNew) {
            onMounted(async () => {
                const r = await api.get('/api/user/addresses');
                if (r.code === 0) {
                    const addr = (r.data.list || []).find((x) => String(x.id) === String(props.id));
                    if (addr) {
                        form.value = {
                            receiver: addr.receiver || '',
                            phone: addr.phone || '',
                            region: addr.region || '',
                            address: addr.address || '',
                            isDefault: Number(addr.is_default) === 1,
                        };
                    }
                }
            });
        }

        async function save() {
            const f = form.value;
            if (!f.receiver || !f.phone || !f.address) return toast('请完整填写收货人、电话与地址');
            if (!/^1\d{10}$/.test(f.phone)) return toast('手机号格式不正确');
            saving.value = true;
            const r = await api.post('/api/user/save-address', {
                id: isNew ? 0 : Number(props.id),
                receiver: f.receiver,
                phone: f.phone,
                region: f.region,
                address: f.address,
                isDefault: f.isDefault ? 1 : 0,
            });
            saving.value = false;
            if (r.code === 0) {
                toast('已保存');
                location.hash = '#' + redirect;
            } else toast(r.message);
        }

        return { isNew, form, saving, save };
    },
    template: `
    <div class="u-page no-tab">
      <Navbar :title="isNew ? '新增地址' : '编辑地址'"></Navbar>
      <div class="u-form">
        <div class="row"><span class="lb">收货人</span><input :value="form.receiver" @input="form.receiver = $event.target.value" placeholder="姓名" maxlength="20"></div>
        <div class="row"><span class="lb">手机号</span><input :value="form.phone" @input="form.phone = $event.target.value" placeholder="11 位手机号" maxlength="11" type="tel"></div>
        <div class="row"><span class="lb">所在地区</span><input :value="form.region" @input="form.region = $event.target.value" placeholder="省市区（选填）"></div>
        <div class="row"><span class="lb">详细地址</span><input :value="form.address" @input="form.address = $event.target.value" placeholder="街道门牌号" maxlength="100"></div>
        <div class="row" @click="form.isDefault = !form.isDefault" style="cursor:pointer">
          <span class="lb">设为默认</span>
          <span class="u-radio" :class="{on: form.isDefault}"><span class="dot"></span></span>
        </div>
      </div>
      <div style="padding:6px 12px">
        <button class="u-btn block" style="height:44px" :disabled="saving" @click="save">保存</button>
      </div>
    </div>
  `,
};
