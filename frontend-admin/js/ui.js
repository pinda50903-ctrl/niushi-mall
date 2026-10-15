import { ref, computed } from 'vue';
import { api, assetUrl } from './api.js';

const { ElMessage } = window.ElementPlus;

/** 分页列表通用逻辑 */
export function usePageList(fetcher, defaultSize = 20) {
    const loading = ref(false);
    const list = ref([]);
    const total = ref(0);
    const page = ref(1);
    const pageSize = ref(defaultSize);
    async function reload(pageNo) {
        if (pageNo) page.value = pageNo;
        loading.value = true;
        try {
            const r = await fetcher({ page: page.value, pageSize: pageSize.value });
            if (r.code === 0) {
                list.value = r.data.list || [];
                total.value = r.data.total || 0;
            } else {
                ElMessage.error(r.message || '加载失败');
            }
        } finally {
            loading.value = false;
        }
    }
    return { loading, list, total, page, pageSize, reload };
}

/** 单图上传（v-model:path） */
export const ImgUpload = {
    name: 'ImgUpload',
    props: { modelValue: { type: String, default: '' }, width: { type: String, default: '120px' }, height: { type: String, default: '120px' } },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const url = computed(() => assetUrl(props.modelValue));
        const action = api.uploadUrl;
        const headers = api.authHeaders();
        const onSuccess = (res) => {
            if (res.code === 0) {
                emit('update:modelValue', res.data.attachment.path);
                ElMessage.success('上传成功');
            } else {
                ElMessage.error(res.message || '上传失败');
            }
        };
        const onErr = () => ElMessage.error('上传失败，请检查网络');
        return { url, action, headers, onSuccess, onErr };
    },
    template: `
      <el-upload :action="action" name="file" :headers="headers" :show-file-list="false"
                 accept="image/jpeg,image/png,image/gif,image/webp" :on-success="onSuccess" :on-error="onErr"
                 :style="{width:width,height:height}">
        <img v-if="url" :src="url" class="img-upload-prev" :style="{width:width,height:height}">
        <div v-else class="img-upload-empty" :style="{width:width,height:height}">
          <el-icon size="20"><Plus/></el-icon><span>上传图片</span>
        </div>
      </el-upload>
    `,
};

export function ok(msg) {
    ElMessage.success(msg || '操作成功');
}
