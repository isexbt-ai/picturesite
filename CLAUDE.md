# 图站项目约定

**项目**：图站 —— 图片 + 视频展示网站（图集/单图/视频混合，内容分级 + 付费解锁）。
**技术栈**：PHP 8.4 + ThinkPHP 8 · MySQL 8.4 · Redis · Vue 3 + Element Plus（后台）· TP 模板 + Tailwind + Alpine.js + Plyr（前台）· Cloudflare R2（对象存储）。

## 硬性要求（无例外）

1. **严格执行 [docs/代码规范.md](docs/代码规范.md)**，每次写代码前先读；冲突时以更严格者为准。
2. 代码优先：少说多做，用代码说话而非长篇解释；关键决策只写"为什么"的短注释。
3. 最小改动：新功能/修复 ≤3 个文件变更，优先复用已有实现，禁止复制粘贴造轮子。
4. 不添加未请求的功能，不过度设计，不引入无依据的新依赖。
5. 完成后必须验证：运行测试与静态检查，检查所有调用方，确认无悬空引用。
6. 禁止"篇幅限制"式跳过；代码必须完整输出，不得用"其余代码类似"带过。

## 技术栈速查

- **后端**：ThinkPHP 8，分层 `Controller → Service → Model`，业务逻辑只放 Service。
- **数据库**：字段/表 snake_case，含 create_time/update_time，状态用 TINYINT + 常量。
- **前台**：服务端渲染(SSR)利于 SEO，轻交互用 Alpine.js，视频用 Plyr，图片灯箱轻量实现。
- **后台**：Vue 3 + Composition API + `<script setup lang="ts">`，禁 `any`/`@ts-ignore`，API 收敛到 `services/`。
- **对象存储**：Cloudflare R2，AWS SDK for PHP，凭证只存 config，禁止硬编码。

## 关键约定

- 所有函数必须有类型声明；注释用中文，说明"为什么"而非"是什么"。
- 禁止裸 `catch`、禁止吞异常；日志含 user_id/request_id/action。
- 内容分级(level)必须在 Service 层强制校验，不能只靠前端隐藏。
- 用户输入必须经 ThinkPHP 验证器；SQL 一律参数绑定。
- UI 用框架默认主题，禁渐变/霓虹/玻璃拟态；交互必须处理 加载/空/错误/禁用 四态。
- 提交信息：`feat:`/`fix:`/`refactor:`/`docs:`/`chore:` + 中文描述，一个提交只做一件事。

## 提交前检查清单

- [ ] 测试通过；格式化/静态检查通过
- [ ] 公开函数有 Docblock；无残留调试代码、无注释掉的旧代码
- [ ] 无硬编码密钥；无重复实现；调用方已全部检查
- [ ] 未添加未请求的功能；无未完成的 TODO
