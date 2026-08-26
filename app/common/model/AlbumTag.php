<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 内容-标签关联模型
 */
class AlbumTag extends BaseModel
{
    protected $name = 'album_tags';

    /** 关联表无时间戳字段 */
    protected $autoWriteTimestamp = false;
}
