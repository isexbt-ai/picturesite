<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 内容模型（图集 / 单图 / 视频 三种形态复用一张表）
 */
class Album extends BaseModel
{
    protected $name = 'albums';

    /** 内容类型 */
    public const TYPE_ALBUM = 'album';
    public const TYPE_SINGLE = 'single';
    public const TYPE_VIDEO = 'video';

    /** 发布状态 */
    public const STATUS_DRAFT = 0;
    public const STATUS_PUBLISHED = 1;
    public const STATUS_OFF = 2;

    /** 可见等级（与用户 VIP 等级对应） */
    public const LEVEL_FREE = 0;
    public const LEVEL_V1 = 1;
    public const LEVEL_V2 = 2;
    public const LEVEL_V3 = 3;

    /**
     * 所属分类
     */
    public function category(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * 图集图片（album/single 类型）
     */
    public function images(): \think\model\relation\HasMany
    {
        return $this->hasMany(Image::class, 'album_id')->order('sort asc, id asc');
    }

    /**
     * 视频文件（video 类型，一个内容对应一条）
     */
    public function video(): \think\model\relation\HasOne
    {
        return $this->hasOne(Video::class, 'album_id');
    }

    /**
     * 内容标签
     */
    public function tags(): \think\model\relation\BelongsToMany
    {
        // 显式指定中间表外键，避免 TP 按表名误推断为 albums_id
        return $this->belongsToMany(Tag::class, 'album_tags', 'album_id', 'tag_id');
    }
}
