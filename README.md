# 图站

图片 + 视频展示网站。支持图集 / 单图 / 视频三种内容形态，整站登录墙 + 内容分级（L0–L3）+ VIP 付费解锁（卡密兑换 / 手动发放），视频直传 MP4 走 CDN。

## 功能特性

- **三种内容形态**：图集、单图、视频（复用一张内容表）
- **内容分级**：L0–L3 可见等级，在 Service 层强制校验（非仅前端隐藏）
- **VIP 体系**：卡密批次 / 卡密兑换 / 手动发放，到期自动降级
- **整站登录墙**：邀请码注册 + 登录，前台 SSR 利于 SEO
- **对象存储**：本地文件系统（开发）/ Cloudflare R2（生产）双驱动
- **管理后台**：内容管理、分类标签、卡密、VIP、邀请码、用户、评论审核、系统设置

## 技术栈

| 层 | 技术 |
|---|---|
| 后端 | PHP 8.0+（生产 8.4）· ThinkPHP 8 · MySQL 8 · Redis |
| 前台 | TP 模板 · Tailwind · Alpine.js · Plyr（视频） |
| 后台 | Vue 3 · Element Plus · Vite · TypeScript |
| 存储 | Cloudflare R2（AWS SDK for PHP） |

## 目录结构

```
app/
  index/   前台（SSR）
  api/     前台 JSON 接口
  admin/   后台 API
  common/  Model / Service / Middleware / 公共命令
admin-web/  后台前端（Vue 3，构建产物输出到 public/admin/）
config/     框架配置（app_map / storage / r2 等）
database/   迁移（migrations）
docs/       代码规范、部署指南
public/     入口 + 静态资源 + 后台构建产物
```

## 本地开发

```bash
# 1. 安装依赖
composer install

# 2. 配置环境（复制模板改数据库等）
cp .example.env .env

# 3. 建表 + 创建管理员
php think migrate:run
php think admin:create --user=admin --password=你的密码

# 4. 启动开发服务器
php think run
```

访问：前台 `http://localhost:8000/`，后台 `http://localhost:8000/admin/`（接口 `/admin-api/`）。

后台前端改动需在 `admin-web/` 下构建：

```bash
cd admin-web
npm install
npm run build   # 产物输出到 public/admin/
```

## 生产部署

完整步骤（1Panel + Nginx + R2 + 配置文件修改清单）见 [docs/部署指南.md](docs/部署指南.md)。

## 开发规范

遵循 [docs/代码规范.md](docs/代码规范.md)：分层 Controller → Service → Model，业务逻辑只放 Service，全类型声明，内容分级在 Service 层强制校验，用户输入经验证器校验。

## 许可

基于 ThinkPHP 8（Apache 2.0），项目自有代码版权归作者所有。
