import { ref, onMounted } from 'vue';
import { api, toast } from '../api.js';
import { Navbar, Empty } from '../ui.js';

export default {
    name: 'AddressPage',
    components: { Navbar, Empty },
    setup() {
        const list = ref([]);
        const pickMode = new URLSearchParams(location.hash.split('?')[1] || '').get('pick') === '1';

        async function load() {
            const r = await api.get('/api/user/addresses');
            if (r.code === 0) list.value = r.data.list || [];
        }
        function pick(addr) {
            if (!pickMode) return;
            localStorage.setItem('ns_picked_address', String(addr.id));
            window.history.back();
        }
        async function del(addr) {
            const r = await api.post('/api/user/delete-address', { id: addr.id });
            if (r.code === 0) { toast('已删除'); load(); } else toast(r.message);
        }
        onMounted(load);

        return { list, pickMode, pick, del };
    },
    template: `
    <div class="u-page no-tab">
      <Navbar title="收货地址"></Navbar>
      <Empty v-if="!list.length" icon="📍" text="还没有收货地址"></Empty>
      <div class="u-card" v-for="a in list" :key="a.id" @click="pick(a)" style="cursor:pointer">
        <div style="font-size:15px;font-weight:600">
          {{ a.receiver }} <span style="font-weight:400;color:#666;margin-left:6px">{{ a.phone }}</span>
          <span class="u-tag" v-if="Number(a.is_default) === 1" style="margin-left:6px">默认</span>
        </div>
        <div class="u-mut" style="margin-top:4px">{{ a.region }} {{ a.address }}</div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px">
          <button class="u-btn mini plain" @click.stop="location.hash = '#/address-edit/' + a.id">编辑</button>
          <button class="u-btn mini danger" @click.stop="del(a)">删除</button>
        </div>
      </div>
      <div style="padding:10px 12px">
        <button class="u-btn block" style="height:44px" @click="go('#/address-edit/new')">＋ 新增收货地址</button>
      </div>
    </div>
  `,
};
