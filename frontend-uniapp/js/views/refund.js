import { ref } from 'vue';
import { api, toast } from '../api.js';
import { Navbar } from '../ui.js';

/** 申请售后：路由参数为订单明细 id（/refund-apply/:itemId） */
export const RefundApply = {
    name: 'RefundApplyPage',
    props: { itemId: { type: String, required: true } },
    components: { Navbar },
    setup(props) {
        const type = ref(0);
        const reason = ref('');
        const description = ref('');
        const submitting = ref(false);

        async function submit() {
            if (!reason.value) return toast('请填写售后原因');
            submitting.value = true;
            const r = await api.post('/api/order/refund-apply', {
                itemId: Number(props.itemId),
                type: type.value,
                reason: reason.value,
                description: description.value,
            });
            submitting.value = false;
            if (r.code === 0) {
                toast('售后申请已提交');
                history.back();
            } else toast(r.message);
        }

        return { type, reason, description, submitting, submit };
    },
    template: `
    <div class="u-page no-tab">
      <Navbar title="申请售后"></Navbar>
      <div class="u-form">
        <div class="row">
          <span class="lb">售后类型</span>
          <span class="u-radio" :class="{on: type === 0}" @click="type = 0"><span class="dot"></span>仅退款</span>
          <span class="u-radio" :class="{on: type === 1}" @click="type = 1"><span class="dot"></span>退货退款</span>
        </div>
        <div class="row">
          <span class="lb">售后原因</span>
          <input :value="reason" @input="reason = $event.target.value" placeholder="如：不想要了 / 发错货" maxlength="30">
        </div>
        <div class="row">
          <span class="lb">问题描述</span>
          <textarea :value="description" @input="description = $event.target.value" maxlength="200" placeholder="补充描述（选填）"></textarea>
        </div>
      </div>
      <div style="padding:6px 12px">
        <button class="u-btn block" style="height:44px" :disabled="submitting" @click="submit">提交申请</button>
      </div>
      <div class="u-mut" style="text-align:center;padding:8px 24px;line-height:1.6">
        提交后商家将尽快审核，售后金额为该商品实付金额，通过后原路退回。
      </div>
    </div>
  `,
};
