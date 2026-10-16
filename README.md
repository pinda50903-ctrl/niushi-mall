# 牛视商城（Niushi Mall）

基于 Yii2 的多租户 SaaS 商城系统，包含平台总部端、商城管理端与用户端，配套 50 个插件。

---

## 目录

- [技术栈](#技术栈)
- [快速开始](#快速开始)
- [项目结构](#项目结构)
- [账号说明](#账号说明)
- [架构说明](#架构说明)
- [插件系统](#插件系统)
- [开发脚本](#开发脚本)
- [当前完成度](#当前完成度)

---

## 技术栈

| 层 | 选型 | 说明 |
|---|---|---|
| 后端 | PHP 8.1+ / Yii2 2.0.51 | REST 风格，统一响应包封 |
| 数据库 | MySQL 8.0 | 表前缀 `nm_`，字符集 `utf8mb4` |
| 管理端 | Vue 3 + Element Plus | 全局构建，**无构建工具链** |
| 用户端 | Vue 3 + Vue Router | Hash 路由，移动端 H5 |
| 鉴权 | JWT（Bearer Token） | 三端独立身份体系 |

> 前端采用 vendored 全局构建 + importmap，不依赖 npm/webpack。`vendor/` 目录只保留实际引用的文件。

---

## 快速开始

### 环境要求

- PHP 8.1 或更高（需 `pdo_mysql`、`gd`、`mbstring`、`openssl` 扩展）
- MySQL 8.0 或更高
- Composer

### 配置数据库连接

编辑 `app/.env`：

```ini
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=niushi_mall
DB_USER=root
DB_PASS=你的密码
YII_DEBUG=true
```

### 安装依赖

```bash
cd app
composer install
```

### 初始化数据库

```bash
# 建库（utf8mb4）
mysql -u root -p -e "CREATE DATABASE niushi_mall DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

cd app
php yii migrate            # 建表
php yii seed               # 初始化平台超管、角色、插件注册表
```

### 启动服务

三个终端分别执行：

```bash
# 后端 API（8090）
php -S 127.0.0.1:8090 -t app/web dev-server.php

# 管理端（8091）
php -S 127.0.0.1:8091 -t frontend-admin

# 用户端 H5（8092）
php -S 127.0.0.1:8092 -t frontend-uniapp dev-static.php
```

访问：

- 管理端 <http://127.0.0.1:8091>
- 用户端 <http://127.0.0.1:8092>

> `dev-server.php` / `dev-static.php` 是内置服务器的路由脚本，负责静态资源直出与免缓存。
> 生产环境请用 Nginx/Apache，并按 `app/web/.htaccess` 配置伪静态。

---

## 项目结构

```
niushi-mall/
├── app/                         后端（Yii2）
│   ├── common/
│   │   ├── components/          REST 基类、JWT、错误处理、响应格式化
│   │   ├── db/ActiveRecord.php  时间戳行为基类
│   │   ├── events/              领域事件（OrderEvent）
│   │   ├── model/               30 个 ActiveRecord 模型
│   │   ├── plugins/             插件管理器
│   │   └── services/            商品/订单/支付/优惠券/钱包等领域服务
│   ├── config/                  应用配置（main / console / web / db）
│   ├── console/                 控制台：migrate、seed、统计
│   │   └── migrations/
│   ├── modules/
│   │   ├── api/                 用户端 API（/api/...）
│   │   ├── manage/              商城管理端 API（/manage/...）
│   │   └── platform/            平台总部 API（/platform/...）
│   ├── plugins/                 50 个插件
│   │   ├── PluginBase.php           插件模块基类
│   │   ├── PluginRestController.php 插件用户端控制器基类
│   │   ├── PluginAdminController.php插件管理端控制器基类
│   │   ├── ConfigurableService.php  配置型插件服务基类
│   │   └── <code>/                 各插件（Plugin.php + models/ services/ controllers/）
│   ├── runtime/                 日志与缓存
│   └── web/                     入口 index.php、uploads/
│
├── frontend-admin/              管理端（Vue 3 + Element Plus）
│   ├── js/views/                13 个页面模块
│   └── vendor/                  Vue / Vue Router / Element Plus
│
├── frontend-uniapp/             用户端 H5（Vue 3）
│   ├── js/views/                18 个页面模块
│   └── vendor/                  Vue / Vue Router
│
├── dev-server.php               开发用 API 服务器路由脚本
├── dev-static.php               开发用静态站点路由脚本
├── smoke-test.ps1               主链路冒烟测试
└── plugin-smoke.ps1             插件链路冒烟测试
```

---

## 账号说明

| 端 | 入口 | 账号 | 密码 |
|---|---|---|---|
| 平台总部 | `/platform/passport/login` | `admin` | `Admin@2024` |
| 商城管理端 | `/manage/passport/login` | `boss` | `Boss@2024` |
| 用户端 | `/api/passport/mobile-login` | `13800138000` | `User@2024` |
| 用户端 | 同上 | `13900139000` | `User@2024` |

> 用户端手机号首次登录会自动注册。

---

## 架构说明

### 三端身份体系

平台总部、商城租户、商城用户三类身份相互隔离，各自签发 JWT：

```
Authorization: Bearer <token>
X-Mall-Id: demo          # 租户标识，定位商城
```

| 身份 | Token type | 可访问 |
|---|---|---|
| 平台管理员 | `admin` | `/platform/**` |
| 商城管理员 | `tenant` | `/manage/**`、`/<plugin-code>/admin/**` |
| 商城用户 | `user` | `/api/**`、`/<plugin-code>/**` |

### 统一响应

所有接口 HTTP 状态码恒为 200，业务结果在 `code` 字段：

```json
{
  "code": 0,
  "message": "ok",
  "data": { }
}
```

| code | 含义 |
|---|---|
| `0` | 成功 |
| `40000` | 参数校验失败 |
| `40100` | 未授权 / 登录态失效 |
| `40300` | 无权限 |
| `40400` | 资源不存在 |
| `50000` | 服务端错误 |

### 分层约定

```
Controller   薄层：参数读取、权限校验、响应包封
   ↓
Service      领域逻辑：事务、跨模型编排、事件派发
   ↓
Model        ActiveRecord + 通用行为（时间戳）
```

插件控制器继承 `PluginRestController`（用户端）或 `PluginAdminController`（管理端），自动完成租户开通校验与身份解析。

---

## 插件系统

### 目录约定

```
app/plugins/<code>/
├── Plugin.php          插件入口：code / name / category / description
├── models/             数据模型（可选）
├── services/           业务服务（可选）
└── controllers/        AdminController（管理端）
```

插件代码必须与目录名一致。已启用的插件由 `PluginManager` 注册为应用模块，路由形如：

```
/<code>/<controller>/<action>
```

例如 `/distribution/center/index`、`/check_in/check/status`。

### 授权流程

平台总部授权 → 商城才能调用插件接口（未开通返回 `40300`）：

```
POST /platform/plugin/grant   { code, tenantId }
POST /platform/plugin/revoke  { code, tenantId }
```

### 插件能力

**含数据表与领域逻辑**：

| 插件 | 说明 |
|---|---|
| `groupon` | 拼团：开团/参团/自动成团 |
| `seckill` | 秒杀：场次管理、限时限量 |
| `distribution` | 分销：三级返佣、佣金结算、提现审核 |
| `check_in` | 签到：连续奖励、补签 |

**配置型插件**（46 个）：

统一的配置契约，管理端 `PluginGeneric` 组件自动渲染表单：

```
GET  /<code>/admin/config        读取配置
POST /<code>/admin/save-config   保存配置
```

配置项通过 `ConfigurableService` 声明，值类型决定表单控件：

| 类型 | 控件 |
|---|---|
| `bool` | 开关 |
| `int` / `float` | 数字输入 |
| `string` | 文本框 |
| `array` | JSON 编辑器 |

配置持久化在 `nm_setting` 表，`code = plugin:<code>`。

---

## 开发脚本

### 冒烟测试

```powershell
# 主链路：分类/商品/订单/支付/优惠券/购物车/仪表盘/插件市场
powershell -ExecutionPolicy Bypass -File smoke-test.ps1

# 插件链路：拼团/秒杀/分销/签到
powershell -ExecutionPolicy Bypass -File plugin-smoke.ps1
```

两个脚本均为幂等设计，可重复执行。

### 控制台命令

```bash
cd app
php yii migrate            # 执行迁移
php yii migrate/create xxx # 创建迁移
php yii seed               # 初始化基础数据
php yii stat/daily         # 生成日统计
```

---

## 当前完成度

### 代码规模

| 类型 | 文件数 | 行数 |
|---|---|---|
| PHP | 270 | ~11,500 |
| JavaScript | 49 | ~4,800 |
| **合计** | **319** | **~16,400** |

数据表：45 张。

### 已完成

- **后端**：多租户架构、三端身份体系、商品/订单/支付/优惠券/购物车等领域服务
- **管理端**：平台端 4 页 + 商城端 12 页
- **用户端**：18 个视图，完整购物流（浏览 → 加购 → 结算 → 支付 → 售后）
- **插件**：50 个接入插件市场，统一配置契约（共 160 项配置项）

### 测试状态

```
双冒烟全部通过
管理端 16 页浏览器实测无报错
用户端 18 页浏览器实测无报错
```

---

## 许可

Proprietary. All rights reserved.
