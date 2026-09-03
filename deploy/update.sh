#!/usr/bin/env bash
# 图站一键更新脚本
# --------------------------------------------------------------
# 用途：在生产站点根目录下执行，拉取远程仓库最新代码并完成
#       依赖更新、数据库迁移、后台前端构建、运行时缓存清理。
# 用法：
#   chmod +x deploy/update.sh
#   ./deploy/update.sh                  # 默认拉 origin/merge-nav
#   BRANCH=main ./deploy/update.sh      # 自定义分支
#   REMOTE=upstream ./deploy/update.sh  # 自定义远程名
#
# 前置：
#   1. 站点目录已是 git 仓库（remote = picturesite）
#   2. 服务器已安装 composer（PHP 依赖变更时才需要）
#   3. 服务器已安装 node + npm（后台前端变更时才需要）
#      若不便在服务器构建 Node，请保持本地构建后上传 public/admin/
#
# 安全：
#   更新前会自动打 tag backup-<时间>-<旧 commit> 用于回滚
#   仅在 composer.json/lock 变更时跑 composer install
#   仅在后台源码变更时跑 npm run build
# --------------------------------------------------------------

set -euo pipefail

BRANCH="${BRANCH:-merge-nav}"
REMOTE="${REMOTE:-origin}"
PHP_BIN="${PHP_BIN:-$(command -v php 2>/dev/null || echo /usr/bin/php)}"

red()    { printf '\033[31m%s\033[0m\n' "$*" >&2; }
green()  { printf '\033[32m%s\033[0m\n' "$*"; }
yellow() { printf '\033[33m%s\033[0m\n' "$*" >&2; }
blue()   { printf '\033[34m%s\033[0m\n' "$*"; }

# === 前置检查 ===
[ -d .git ] || { red "当前目录不是 git 仓库，请在站点根目录执行"; exit 1; }
command -v git >/dev/null || { red "未找到 git"; exit 1; }

# === 1. 拉取代码 ===
blue "[1/5] 拉取远程代码 ${REMOTE}/${BRANCH} ..."
git fetch --prune "$REMOTE" "$BRANCH"

LOCAL=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git rev-parse "$REMOTE/$BRANCH")

if [ "$LOCAL" = "$REMOTE_COMMIT" ]; then
    green "当前已是最新提交（$(git log -1 --format='%h %s' HEAD)），无需更新"
    exit 0
fi

green "  本地: $LOCAL"
green "  远程: $REMOTE_COMMIT"
echo "  提交日志:"
git log --oneline "$LOCAL..$REMOTE_COMMIT" | sed 's/^/    /'

# 回滚锚点：更新前的 commit 打 tag
BACKUP_TAG="backup-$(date +%Y%m%d-%H%M%S)-${LOCAL:0:7}"
git tag "$BACKUP_TAG" "$LOCAL" >/dev/null 2>&1 || yellow "  ↳ 备份 tag 已存在，跳过"
yellow "  回滚锚点: $BACKUP_TAG"

# 强制更新到远程 commit（丢弃本地未推送修改）
git reset --hard "$REMOTE_COMMIT"
green "  ✓ 代码已更新"

# === 2. PHP 依赖 ===
blue "[2/5] 检查 composer 依赖变更 ..."
if [ -f composer.json ] && git diff --name-only "$LOCAL" "$REMOTE_COMMIT" | grep -qE '^composer\.(json|lock)$'; then
    if command -v composer >/dev/null 2>&1; then
        composer install --no-dev --optimize-autoloader --no-interaction
        green "  ✓ composer 安装完成"
    else
        red "  ✗ composer.json 有变更但未安装 composer，请先安装或手动跑 composer install"
        exit 2
    fi
else
    yellow "  ↳ composer 文件无变化，跳过"
fi

# === 3. 数据库迁移 ===
blue "[3/5] 执行数据库迁移 ..."
if [ -d database/migrations ] && [ -x "$PHP_BIN" ] || command -v "$PHP_BIN" >/dev/null 2>&1; then
    if "$PHP_BIN" think migrate:run 2>&1 | tee /tmp/tuzhan-migrate.log | grep -qE 'nothing to migrate|已是最新的|no migrations'; then
        yellow "  ↳ 无新增迁移"
        grep -v 'nothing to migrate' /tmp/tuzhan-migrate.log | sed 's/^/    /' || true
    else
        tail -n 20 /tmp/tuzhan-migrate.log | sed 's/^/    /'
        green "  ✓ 迁移完成"
    fi
else
    yellow "  ↳ 未找到 database/migrations 或 php 可执行，跳过"
fi

# === 4. 后台前端构建 ===
blue "[4/5] 检查后台前端变更 ..."
if [ -d admin-web ]; then
    if git diff --name-only "$LOCAL" "$REMOTE_COMMIT" | grep -qE '^admin-web/(src/|\.env|package.*\.json|vite\.config\.ts|index\.html)'; then
        if command -v npm >/dev/null 2>&1; then
            yellow "  ↳ 检测到后台前端变更，开始构建"
            (cd admin-web && [ -d node_modules ] || npm install --no-audit --no-fund)
            (cd admin-web && npm run build)
            green "  ✓ 后台构建完成（产物 public/admin/）"
        else
            red "  ✗ 检测到后台前端变更，但服务器无 npm。"
            red "     请在本地构建 public/admin/ 后上传，或安装 Node.js。"
            exit 3
        fi
    else
        yellow "  ↳ 后台前端无变化，跳过构建"
    fi
else
    yellow "  ↳ 未找到 admin-web，跳过"
fi

# === 5. 清理运行时缓存 ===
blue "[5/5] 清理运行时缓存 ..."
rm -rf runtime/cache/* runtime/temp/* 2>/dev/null || true
# ThinkPHP 视图编译缓存（多应用放 app/{index,admin,api}/view/）
find app -path '*/view/*' -name '*.php' -newer composer.json -delete 2>/dev/null || true
green "  ✓ 缓存清理完成"

# === 完成 ===
green "=========================================================="
green "  更新成功: ${LOCAL:0:7} → ${REMOTE_COMMIT:0:7}"
green "  回滚命令: git reset --hard $BACKUP_TAG"
green "=========================================================="