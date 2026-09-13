<?php
declare(strict_types=1);

use think\migration\Migrator;

/**
 * images 表新增 sha256 + client_uuid
 *
 * - sha256：内容哈希，用于客户端预检去重（同字节图跳过上传）
 * - client_uuid：前端为每次上传生成的稳定标识，用于「上传即入库」增量同步
 *   （save 时按 client_uuid UPDATE sort + DELETE 取消项，不再 delete-all+recreate）
 */
class AddImagesSha256Uuid extends Migrator
{
    public function up(): void
    {
        $this->table('images')
            ->addColumn('sha256', 'string', [
                'limit' => 64, 'null' => true, 'after' => 'path',
                'comment' => '文件 SHA256 内容哈希（客户端预检去重用）',
            ])
            ->addColumn('client_uuid', 'string', [
                'limit' => 36, 'null' => true, 'after' => 'sha256',
                'comment' => '前端生成的稳定 UUID（增量同步 sort 与删除用）',
            ])
            ->addIndex(['sha256'])
            ->addIndex(['client_uuid'])
            ->update();
    }

    public function down(): void
    {
        $this->table('images')
            ->removeIndex(['sha256'])
            ->removeIndex(['client_uuid'])
            ->removeColumn('client_uuid')
            ->removeColumn('sha256')
            ->update();
    }
}
