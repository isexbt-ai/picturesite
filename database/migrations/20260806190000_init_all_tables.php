<?php
// +----------------------------------------------------------------------
// | 图站全量建表迁移（M1）
// +----------------------------------------------------------------------
// | 说明：一次性建全部核心表。后续字段变更走新增迁移文件，禁止改动本文件。
// | 执行：php think migrate:run
// +----------------------------------------------------------------------

use think\migration\Migrator;
use think\migration\db\Column;

class InitAllTables extends Migrator
{
    public function up(): void
    {
        $this->createUsers();
        $this->createAdminUsers();
        $this->createInviteCodes();
        $this->createCardBatches();
        $this->createCards();
        $this->createVipLogs();
        $this->createCategories();
        $this->createAlbums();
        $this->createImages();
        $this->createVideos();
        $this->createTags();
        $this->createAlbumTags();
        $this->createFavorites();
        $this->createBrowseLogs();
        $this->createComments();
        $this->createSettings();
        $this->createAdminLogs();
    }

    public function down(): void
    {
        $tables = [
            'admin_logs', 'settings', 'comments', 'browse_logs', 'favorites',
            'album_tags', 'tags', 'videos', 'images', 'albums', 'categories',
            'vip_logs', 'cards', 'card_batches', 'invite_codes', 'admin_users', 'users',
        ];
        foreach ($tables as $table) {
            $this->table($table)->drop();
        }
    }

    private function createUsers(): void
    {
        $this->table('users', ['engine' => 'InnoDB', 'comment' => '用户表'])
            ->addColumn('username', 'string', ['limit' => 50, 'comment' => '用户名'])
            ->addColumn('email', 'string', ['limit' => 100, 'null' => true, 'comment' => '邮箱'])
            ->addColumn('password', 'string', ['limit' => 255, 'comment' => '密码哈希'])
            ->addColumn('salt', 'string', ['limit' => 32, 'comment' => '加盐'])
            ->addColumn('avatar', 'string', ['limit' => 255, 'null' => true, 'comment' => '头像'])
            ->addColumn('vip_level', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => 'VIP等级 0-3'])
            ->addColumn('vip_expire_at', 'datetime', ['null' => true, 'comment' => 'VIP到期时间'])
            ->addColumn('status', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 1, 'comment' => '状态 1正常 0禁用'])
            ->addColumn('invite_code_used', 'string', ['limit' => 50, 'null' => true, 'comment' => '注册所用邀请码'])
            ->addColumn('last_login_at', 'datetime', ['null' => true, 'comment' => '最后登录时间'])
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['email'])
            ->addTimestamps()
            ->create();
    }

    private function createAdminUsers(): void
    {
        $this->table('admin_users', ['engine' => 'InnoDB', 'comment' => '管理员表'])
            ->addColumn('username', 'string', ['limit' => 50, 'comment' => '用户名'])
            ->addColumn('password', 'string', ['limit' => 255, 'comment' => '密码哈希'])
            ->addColumn('last_login_at', 'datetime', ['null' => true, 'comment' => '最后登录时间'])
            ->addColumn('status', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 1, 'comment' => '状态 1正常 0禁用'])
            ->addIndex(['username'], ['unique' => true])
            ->addTimestamps()
            ->create();
    }

    private function createInviteCodes(): void
    {
        $this->table('invite_codes', ['engine' => 'InnoDB', 'comment' => '邀请码表'])
            ->addColumn('code', 'string', ['limit' => 50, 'comment' => '邀请码'])
            ->addColumn('status', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '0未用 1已用 2禁用'])
            ->addColumn('used_by', 'integer', ['null' => true, 'comment' => '使用者 user_id'])
            ->addColumn('used_at', 'datetime', ['null' => true, 'comment' => '使用时间'])
            ->addColumn('expire_at', 'datetime', ['null' => true, 'comment' => '过期时间'])
            ->addColumn('remark', 'string', ['limit' => 255, 'null' => true, 'comment' => '备注'])
            ->addIndex(['code'], ['unique' => true])
            ->addIndex(['status'])
            ->addTimestamps()
            ->create();
    }

    private function createCardBatches(): void
    {
        $this->table('card_batches', ['engine' => 'InnoDB', 'comment' => '卡密批次表'])
            ->addColumn('name', 'string', ['limit' => 100, 'comment' => '批次名称'])
            ->addColumn('level', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'comment' => '开通等级 1-3'])
            ->addColumn('duration_days', 'integer', ['comment' => '有效期天数'])
            ->addColumn('total', 'integer', ['default' => 0, 'comment' => '生成数量'])
            ->addTimestamps()
            ->create();
    }

    private function createCards(): void
    {
        $this->table('cards', ['engine' => 'InnoDB', 'comment' => '卡密表'])
            ->addColumn('batch_id', 'integer', ['comment' => '批次ID'])
            ->addColumn('code', 'string', ['limit' => 64, 'comment' => '卡密'])
            ->addColumn('level', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'comment' => '开通等级'])
            ->addColumn('duration_days', 'integer', ['comment' => '有效期天数'])
            ->addColumn('status', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '0未用 1已用 2禁用'])
            ->addColumn('used_by', 'integer', ['null' => true, 'comment' => '使用者 user_id'])
            ->addColumn('used_at', 'datetime', ['null' => true, 'comment' => '使用时间'])
            ->addIndex(['code'], ['unique' => true])
            ->addIndex(['batch_id'])
            ->addIndex(['status'])
            ->addTimestamps()
            ->create();
    }

    private function createVipLogs(): void
    {
        $this->table('vip_logs', ['engine' => 'InnoDB', 'comment' => 'VIP开通记录表'])
            ->addColumn('user_id', 'integer', ['comment' => '用户ID'])
            ->addColumn('level', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'comment' => '开通等级'])
            ->addColumn('duration_days', 'integer', ['comment' => '开通天数'])
            ->addColumn('source', 'string', ['limit' => 20, 'default' => 'card', 'comment' => '来源 card/manual'])
            ->addColumn('card_id', 'integer', ['null' => true, 'comment' => '卡密ID'])
            ->addColumn('remark', 'string', ['limit' => 255, 'null' => true, 'comment' => '备注'])
            ->addIndex(['user_id'])
            ->addTimestamps()
            ->create();
    }

    private function createCategories(): void
    {
        $this->table('categories', ['engine' => 'InnoDB', 'comment' => '分类表'])
            ->addColumn('name', 'string', ['limit' => 50, 'comment' => '分类名'])
            ->addColumn('slug', 'string', ['limit' => 50, 'comment' => 'URL别名'])
            ->addColumn('parent_id', 'integer', ['default' => 0, 'comment' => '父分类ID 0为顶级'])
            ->addColumn('sort', 'integer', ['default' => 0, 'comment' => '排序'])
            ->addColumn('status', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 1, 'comment' => '状态 1启用 0禁用'])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['parent_id'])
            ->addTimestamps()
            ->create();
    }

    private function createAlbums(): void
    {
        $this->table('albums', ['engine' => 'InnoDB', 'comment' => '内容表(图集/单图/视频)'])
            ->addColumn('title', 'string', ['limit' => 150, 'comment' => '标题'])
            ->addColumn('subtitle', 'string', ['limit' => 255, 'null' => true, 'comment' => '副标题'])
            ->addColumn('type', 'string', ['limit' => 10, 'default' => 'album', 'comment' => 'album/single/video'])
            ->addColumn('cover', 'string', ['limit' => 255, 'null' => true, 'comment' => '封面图地址'])
            ->addColumn('level', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '可见等级 0-3'])
            ->addColumn('category_id', 'integer', ['default' => 0, 'comment' => '分类ID'])
            ->addColumn('status', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '0草稿 1发布 2下架'])
            ->addColumn('sort', 'integer', ['default' => 0, 'comment' => '排序'])
            ->addColumn('view_count', 'integer', ['default' => 0, 'comment' => '浏览量'])
            ->addColumn('like_count', 'integer', ['default' => 0, 'comment' => '点赞量'])
            ->addIndex(['type'])
            ->addIndex(['category_id'])
            ->addIndex(['level', 'status'])
            ->addTimestamps()
            ->create();
    }

    private function createImages(): void
    {
        $this->table('images', ['engine' => 'InnoDB', 'comment' => '图片表'])
            ->addColumn('album_id', 'integer', ['comment' => '内容ID'])
            ->addColumn('path', 'string', ['limit' => 255, 'comment' => '原图路径'])
            ->addColumn('thumb_path', 'string', ['limit' => 255, 'null' => true, 'comment' => '缩略图路径'])
            ->addColumn('webp_path', 'string', ['limit' => 255, 'null' => true, 'comment' => 'WebP路径'])
            ->addColumn('sort', 'integer', ['default' => 0, 'comment' => '排序'])
            ->addColumn('width', 'integer', ['null' => true, 'comment' => '宽度'])
            ->addColumn('height', 'integer', ['null' => true, 'comment' => '高度'])
            ->addColumn('size', 'integer', ['null' => true, 'comment' => '文件大小字节'])
            ->addIndex(['album_id'])
            ->addTimestamps()
            ->create();
    }

    private function createVideos(): void
    {
        $this->table('videos', ['engine' => 'InnoDB', 'comment' => '视频表'])
            ->addColumn('album_id', 'integer', ['comment' => '内容ID'])
            ->addColumn('path', 'string', ['limit' => 255, 'comment' => '视频路径'])
            ->addColumn('poster', 'string', ['limit' => 255, 'null' => true, 'comment' => '视频封面图'])
            ->addColumn('duration', 'integer', ['default' => 0, 'comment' => '时长秒'])
            ->addColumn('width', 'integer', ['null' => true, 'comment' => '宽度'])
            ->addColumn('height', 'integer', ['null' => true, 'comment' => '高度'])
            ->addColumn('size', 'integer', ['null' => true, 'comment' => '文件大小字节'])
            ->addColumn('sort', 'integer', ['default' => 0, 'comment' => '排序'])
            ->addIndex(['album_id'])
            ->addTimestamps()
            ->create();
    }

    private function createTags(): void
    {
        $this->table('tags', ['engine' => 'InnoDB', 'comment' => '标签表'])
            ->addColumn('name', 'string', ['limit' => 50, 'comment' => '标签名'])
            ->addColumn('slug', 'string', ['limit' => 50, 'comment' => 'URL别名'])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['name'])
            ->addTimestamps()
            ->create();
    }

    private function createAlbumTags(): void
    {
        $this->table('album_tags', ['engine' => 'InnoDB', 'comment' => '内容标签关联表'])
            ->addColumn('album_id', 'integer', ['comment' => '内容ID'])
            ->addColumn('tag_id', 'integer', ['comment' => '标签ID'])
            ->addIndex(['album_id'])
            ->addIndex(['tag_id'])
            ->addIndex(['album_id', 'tag_id'], ['unique' => true])
            ->create();
    }

    private function createFavorites(): void
    {
        $this->table('favorites', ['engine' => 'InnoDB', 'comment' => '收藏表'])
            ->addColumn('user_id', 'integer', ['comment' => '用户ID'])
            ->addColumn('album_id', 'integer', ['comment' => '内容ID'])
            ->addIndex(['user_id', 'album_id'], ['unique' => true])
            ->addIndex(['album_id'])
            ->addTimestamps()
            ->create();
    }

    private function createBrowseLogs(): void
    {
        $this->table('browse_logs', ['engine' => 'InnoDB', 'comment' => '浏览记录表'])
            ->addColumn('user_id', 'integer', ['comment' => '用户ID'])
            ->addColumn('album_id', 'integer', ['comment' => '内容ID'])
            ->addColumn('last_view_at', 'datetime', ['comment' => '最后浏览时间'])
            ->addIndex(['user_id', 'album_id'], ['unique' => true])
            ->addIndex(['user_id'])
            ->addIndex(['album_id'])
            ->create();
    }

    private function createComments(): void
    {
        $this->table('comments', ['engine' => 'InnoDB', 'comment' => '评论表'])
            ->addColumn('album_id', 'integer', ['comment' => '内容ID'])
            ->addColumn('user_id', 'integer', ['comment' => '用户ID'])
            ->addColumn('content', 'text', ['comment' => '评论内容'])
            ->addColumn('status', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 1, 'comment' => '1显示 0隐藏'])
            ->addIndex(['album_id'])
            ->addIndex(['user_id'])
            ->addTimestamps()
            ->create();
    }

    private function createSettings(): void
    {
        $this->table('settings', ['engine' => 'InnoDB', 'comment' => '系统设置表'])
            ->addColumn('key', 'string', ['limit' => 100, 'comment' => '配置键'])
            ->addColumn('value', 'text', ['null' => true, 'comment' => '配置值'])
            ->addColumn('remark', 'string', ['limit' => 255, 'null' => true, 'comment' => '备注'])
            ->addIndex(['key'], ['unique' => true])
            ->addTimestamps()
            ->create();
    }

    private function createAdminLogs(): void
    {
        $this->table('admin_logs', ['engine' => 'InnoDB', 'comment' => '后台操作日志表'])
            ->addColumn('admin_id', 'integer', ['comment' => '管理员ID'])
            ->addColumn('action', 'string', ['limit' => 100, 'comment' => '操作动作'])
            ->addColumn('target', 'string', ['limit' => 255, 'null' => true, 'comment' => '操作对象'])
            ->addColumn('ip', 'string', ['limit' => 45, 'null' => true, 'comment' => 'IP地址'])
            ->addIndex(['admin_id'])
            ->addTimestamps()
            ->create();
    }
}
