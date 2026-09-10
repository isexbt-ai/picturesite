<?php
use think\migration\Migrator;
use think\migration\db\Column;

/**
 * 友情链接表
 *
 * Why: 首页展示友链，后台可手动维护。前台只读启用状态，后台可增删改。
 */
class CreateFriendLinks extends Migrator
{
    public function up(): void
    {
        $this->table('friend_links', ['engine' => 'InnoDB', 'comment' => '友情链接表'])
            ->addColumn('name', 'string', ['limit' => 50, 'comment' => '友链名称'])
            ->addColumn('url', 'string', ['limit' => 255, 'comment' => '跳转地址'])
            ->addColumn('sort', 'integer', ['default' => 0, 'comment' => '排序'])
            ->addColumn('status', 'integer', [
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
                'default' => 1,
                'comment' => '1启用 0禁用',
            ])
            ->addIndex(['status', 'sort'])
            ->addTimestamps()
            ->create();
    }

    public function down(): void
    {
        $this->table('friend_links')->drop();
    }
}
