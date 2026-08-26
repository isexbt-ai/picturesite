<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模型基类：统一自动时间戳与公共行为
 */
abstract class BaseModel extends Model
{
    /**
     * 自动写入时间戳（对应数据库 create_time/update_time）
     * 关联表（album_tags / browse_logs 等）在子类中关闭
     */
    protected $autoWriteTimestamp = true;
}
