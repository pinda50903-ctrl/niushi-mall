import { reactive, ref, onMounted } from 'vue';
const { ElMessage, ElMessageBox } = window.ElementPlus;
import { api, assetUrl } from '../api.js';
import { ImgUpload } from '../ui.js';

export default {
    name: 'ContentPage',
    components: { ImgUpload },
    setup() {
        const tab = ref('banner');
        /* ---- banner ---- */
        const banners = ref([]);
        const bannerBox = reactive({ show: false, form: { id: 0, title: '', cover: '', link: '', sort: 100, status: 1 } });
        async function loadBanners() {
            const r = await api.get('/manage/content/banners');
            if (r.code === 0) banners.value = r.data.list || [];
        }
        async function saveBanner() {
            if (!bannerBox.form.cover) return ElMessage.warning('请上传图片');
            const r = await api.post('/manage/content/save-banner', bannerBox.form);
            if (r.code === 0) { ElMessage.success('已保存'); bannerBox.show = false; loadBanners(); } else ElMessage.error(r.message);
        }
        async function delBanner(row) {
            await ElMessageBox.confirm('删除该轮播图？', '提示', { type: 'warning' });
            const r = await api.post('/manage/content/delete-banner', { id: row.id });
            if (r.code === 0) { ElMessage.success('已删除'); loadBanners(); }
        }

        /* ---- article ---- */
        const articles = ref([]);
        const articleBox = reactive({ show: false, form: { id: 0, title: '', cover: '', summary: '', content: '', sort: 100, status: 1 } });
        async function loadArticles() {
            const r = await api.get('/manage/content/articles');
            if (r.code === 0) articles.value = r.data.list || [];
        }
        async function saveArticle() {
            if (!articleBox.form.title) return ElMessage.warning('请填写标题');
            const r = await api.post('/manage/content/save-article', articleBox.form);
            if (r.code === 0) { ElMessage.success('已保存'); articleBox.show = false; loadArticles(); } else ElMessage.error(r.message);
        }
        async function delArticle(row) {
            await ElMessageBox.confirm(`删除文章「${row.title}」？`, '提示', { type: 'warning' });
            const r = await api.post('/manage/content/delete-article', { id: row.id });
            if (r.code === 0) { ElMessage.success('已删除'); loadArticles(); }
        }

        /* ---- diy ---- */
        const diyPages = ref([]);
        const diyBox = reactive({ show: false, form: { id: 0, title: '', config: '', status: 1, isHome: 0 } });
        async function loadDiy() {
            const r = await api.get('/manage/content/diy-pages');
            if (r.code === 0) diyPages.value = r.data.list || [];
        }
        async function saveDiy() {
            if (!diyBox.form.title) return ElMessage.warning('请填写页面名称');
            let config = {};
            try { config = diyBox.form.config ? JSON.parse(diyBox.form.config) : {}; }
            catch { return ElMessage.error('页面配置不是合法 JSON'); }
            const r = await api.post('/manage/content/save-diy-page', { ...diyBox.form, config });
            if (r.code === 0) { ElMessage.success('已保存'); diyBox.show = false; loadDiy(); } else ElMessage.error(r.message);
        }
        async function delDiy(row) {
            await ElMessageBox.confirm(`删除页面「${row.title}」？`, '提示', { type: 'warning' });
            const r = await api.post('/manage/content/delete-diy-page', { id: row.id });
            if (r.code === 0) { ElMessage.success('已删除'); loadDiy(); } else ElMessage.error(r.message);
        }

        function init() { loadBanners(); loadArticles(); loadDiy(); }
        onMounted(init);
        return {
            tab, banners, bannerBox, saveBanner, delBanner, assetUrl,
            articles, articleBox, saveArticle, delArticle,
            diyPages, diyBox, saveDiy, delDiy,
        };
    },
    template: `
    <div class="ns-card tight">
      <el-tabs v-model="tab">
        <el-tab-pane label="轮播图" name="banner">
          <div class="ns-filter"><div style="flex:1"></div>
            <el-button type="primary" @click="bannerBox.form = { id:0, title:'', cover:'', link:'', sort:100, status:1 }; bannerBox.show = true">新增轮播图</el-button>
          </div>
        </el-tab-pane>
        <el-tab-pane label="文章" name="article">
          <div class="ns-filter"><div style="flex:1"></div>
            <el-button type="primary" @click="articleBox.form = { id:0, title:'', cover:'', summary:'', content:'', sort:100, status:1 }; articleBox.show = true">新增文章</el-button>
          </div>
        </el-tab-pane>
        <el-tab-pane label="DIY 页面" name="diy">
          <div class="ns-filter"><div style="flex:1"></div>
            <el-button type="primary" @click="diyBox.form = { id:0, title:'', config:'', status:1, isHome:0 }; diyBox.show = true">新建页面</el-button>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>

    <!-- banner -->
    <div class="ns-card" v-show="tab === 'banner'">
      <el-table :data="banners" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column label="图片" width="140"><template #default="{row}"><img class="ns-table-cover" style="width:100px;height:48px" :src="assetUrl(row.cover_url || row.cover)"></template></el-table-column>
        <el-table-column prop="title" label="标题" min-width="140"></el-table-column>
        <el-table-column prop="link" label="链接" min-width="180"><template #default="{row}">{{ row.link || '-' }}</template></el-table-column>
        <el-table-column prop="sort" label="排序" width="80"></el-table-column>
        <el-table-column label="状态" width="80"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{ row.status===1?'启用':'停用' }}</el-tag></template></el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{row}">
            <el-button link type="primary" @click="bannerBox.form = { ...row, cover: row.cover }; bannerBox.show = true">编辑</el-button>
            <el-button link type="danger" @click="delBanner(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- article -->
    <div class="ns-card" v-show="tab === 'article'">
      <el-table :data="articles" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column label="标题" min-width="220"><template #default="{row}"><span class="ns-title-cell">{{ row.title }}</span></template></el-table-column>
        <el-table-column prop="summary" label="摘要" min-width="200"></el-table-column>
        <el-table-column prop="sort" label="排序" width="80"></el-table-column>
        <el-table-column label="状态" width="80"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{ row.status===1?'显示':'隐藏' }}</el-tag></template></el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{row}">
            <el-button link type="primary" @click="articleBox.form = { ...row }; articleBox.show = true">编辑</el-button>
            <el-button link type="danger" @click="delArticle(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- diy -->
    <div class="ns-card" v-show="tab === 'diy'">
      <el-table :data="diyPages" stripe>
        <el-table-column prop="id" label="ID" width="60"></el-table-column>
        <el-table-column label="页面名称" min-width="180"><template #default="{row}"><span class="ns-title-cell">{{ row.title }}</span></template></el-table-column>
        <el-table-column label="首页" width="90"><template #default="{row}"><el-tag v-if="row.is_home===1" type="success" size="small">首页</el-tag><span v-else class="ns-mut">-</span></template></el-table-column>
        <el-table-column label="状态" width="90"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{ row.status===1?'启用':'停用' }}</el-tag></template></el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{row}">
            <el-button link type="primary" @click="diyBox.form = { id:row.id, title:row.title, config: JSON.stringify(row.config||null, null, 2), status:row.status, isHome:row.is_home }; diyBox.show = true">编辑</el-button>
            <el-button link type="danger" @click="delDiy(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- dialogs -->
    <el-dialog v-model="bannerBox.show" :title="bannerBox.form.id ? '编辑轮播图' : '新增轮播图'" width="480px">
      <el-form label-width="80px">
        <el-form-item label="图片" required><img-upload v-model="bannerBox.form.cover" width="240px" height="110px"></img-upload></el-form-item>
        <el-form-item label="标题"><el-input v-model="bannerBox.form.title"></el-input></el-form-item>
        <el-form-item label="跳转链接"><el-input v-model="bannerBox.form.link" placeholder="如 /pages/goods/detail?id=1"></el-input></el-form-item>
        <el-form-item label="排序"><el-input-number v-model="bannerBox.form.sort" :min="0"></el-input-number></el-form-item>
        <el-form-item label="状态"><el-switch v-model="bannerBox.form.status" :active-value="1" :inactive-value="0"></el-switch></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="bannerBox.show = false">取消</el-button>
        <el-button type="primary" @click="saveBanner">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="articleBox.show" :title="articleBox.form.id ? '编辑文章' : '新增文章'" width="640px">
      <el-form label-width="80px">
        <el-form-item label="标题" required><el-input v-model="articleBox.form.title"></el-input></el-form-item>
        <el-form-item label="封面"><img-upload v-model="articleBox.form.cover" width="160px" height="90px"></img-upload></el-form-item>
        <el-form-item label="摘要"><el-input v-model="articleBox.form.summary" type="textarea" :rows="2"></el-input></el-form-item>
        <el-form-item label="正文"><el-input v-model="articleBox.form.content" type="textarea" :rows="8"></el-input></el-form-item>
        <el-form-item label="排序"><el-input-number v-model="articleBox.form.sort" :min="0"></el-input-number></el-form-item>
        <el-form-item label="状态"><el-switch v-model="articleBox.form.status" :active-value="1" :inactive-value="0"></el-switch></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="articleBox.show = false">取消</el-button>
        <el-button type="primary" @click="saveArticle">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="diyBox.show" :title="diyBox.form.id ? '编辑页面' : '新建页面'" width="640px">
      <el-form label-width="90px">
        <el-form-item label="页面名称" required><el-input v-model="diyBox.form.title"></el-input></el-form-item>
        <el-form-item label="页面配置">
          <el-input v-model="diyBox.form.config" type="textarea" :rows="10" placeholder='JSON 配置，如 {"banner":[],"goods":[]}'></el-input>
        </el-form-item>
        <el-form-item label="设为首页">
          <el-switch v-model="diyBox.form.isHome" :active-value="1" :inactive-value="0"></el-switch>
          <span class="ns-mut" style="margin-left:8px">设为首页会取消其它页面首页标记</span>
        </el-form-item>
        <el-form-item label="状态"><el-switch v-model="diyBox.form.status" :active-value="1" :inactive-value="0"></el-switch></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="diyBox.show = false">取消</el-button>
        <el-button type="primary" @click="saveDiy">保存</el-button>
      </template>
    </el-dialog>
  `,
};
