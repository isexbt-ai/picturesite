<?php
declare(strict_types=1);

use think\migration\Migrator;
use think\migration\db\Column;

/**
 * albums 表新增封面衍生尺寸 key
 *
 * 封面走 uploadImage() 会同时生成原图/缩略图/WebP 三份 R2 对象，
 * 删除专辑时需要把三份一起清理，否则留下孤儿文件占存储。
 */
class AddAlbumsCoverSizes extends Migrator
{
    public function up(): void
    {
        $this->table('albums')
            ->addColumn('cover_thumb', 'string', [
                'limit' => 255, 'null' => true, 'after' => 'cover',
                'comment' => '封面缩略图路径',
            ])
            ->addColumn('cover_webp', 'string', [
                'limit' => 255, 'null' => true, 'after' => 'cover_thumb',
                'comment' => '封面WebP路径',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('albums')
            ->removeColumn('cover_webp')
            ->removeColumn('cover_thumb')
            ->update();
    }
}