<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Album;
use app\common\model\Category;
use app\common\model\Image;
use app\common\model\User;
use think\db\Query;

/**
 * 内容查询服务：可见内容分页/详情、媒体 URL 组装
 * 所有对外输出均走此服务，保证分级过滤与媒体 URL 统一
 */
class ContentService
{
    /**
     * 用户可见内容的查询构造器（仅按状态过滤；分级在 detailPayload 按需裁剪媒体 URL）
     */
    public static function visibleQuery(?User $user): Query
    {
        return Album::where('status', Album::STATUS_PUBLISHED)
            ->order('sort desc, id desc');
    }

    /**
     * 分页获取内容卡片数据
     *
     * @param array $where 额外过滤条件（如 category_id / tag 关联）
     * @return array{items:array, total:int, has_more:bool, page:int}
     */
    public static function paginateCards(?User $user, int $page = 1, int $size = 12, array $where = []): array
    {
        $query = self::visibleQuery($user);
        if (!empty($where)) {
            $query->where($where);
        }
        $list = $query->with(['category', 'tags'])->paginate([
            'list_rows' => $size,
            'page'      => $page,
        ]);

        return [
            'items'    => array_map(static fn(Album $a) => self::cardPayload($a), $list->items()),
            'total'    => $list->total(),
            'has_more' => $list->hasMore(),
            'page'     => $page,
        ];
    }

    /**
     * 按分类 slug 分页
     */
    public static function paginateByCategorySlug(?User $user, string $slug, int $page, int $size = 12): array
    {
        $category = Category::where('slug', $slug)->where('status', Category::STATUS_ENABLED)->find();
        if (!$category) {
            return ['items' => [], 'total' => 0, 'has_more' => false, 'page' => $page];
        }
        $data = self::paginateCards($user, $page, $size, ['category_id' => (int) $category->id]);
        $data['category'] = $category;
        return $data;
    }

    /**
     * 按标签 slug 分页
     */
    public static function paginateByTagSlug(?User $user, string $slug, int $page, int $size = 12): array
    {
        $tag = \app\common\model\Tag::where('slug', $slug)->find();
        if (!$tag) {
            return ['items' => [], 'total' => 0, 'has_more' => false, 'page' => $page];
        }
        $query = self::visibleQuery($user)->whereExists(function ($q) use ($tag) {
            $q->table('album_tags')->where('tag_id', (int) $tag->id)->whereColumn('album_tags.album_id', 'albums.id');
        });
        $list = $query->with(['category', 'tags'])->paginate([
            'list_rows' => $size,
            'page'      => $page,
        ]);
        return [
            'items'    => array_map(static fn(Album $a) => self::cardPayload($a), $list->items()),
            'total'    => $list->total(),
            'has_more' => $list->hasMore(),
            'page'     => $page,
            'tag'      => $tag,
        ];
    }

    /**
     * 关键词搜索
     */
    public static function paginateByKeyword(?User $user, string $keyword, int $page, int $size = 12): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return ['items' => [], 'total' => 0, 'has_more' => false, 'page' => $page];
        }
        // where 二维数组条件：['title', 'like', '%xx%'] 需包一层数组
        return self::paginateCards($user, $page, $size, [['title', 'like', '%' . $keyword . '%']]);
    }

    /**
     * 内容详情载荷（含媒体资源，供详情页/接口使用）
     * 分级裁剪：无访问权时仅返回封面缩略图与 locked 标志，不返回原图/视频 URL
     */
    public static function detailPayload(Album $album, ?User $user = null): array
    {
        $coverKey = (string) $album->cover;
        $coverThumbKey = (string) ($album->cover_thumb ?? '');
        $coverWebpKey = (string) ($album->cover_webp ?? '');
        $data = [
            'id'            => (int) $album->id,
            'title'         => (string) $album->title,
            'subtitle'      => (string) ($album->subtitle ?? ''),
            'type'          => (string) $album->type,
            'type_label'    => self::typeLabel((string) $album->type),
            'cover'         => $coverKey !== '' ? StorageService::url($coverKey) : '',
            'cover_thumb'   => $coverThumbKey !== '' ? StorageService::url($coverThumbKey) : '',
            'cover_webp'    => $coverWebpKey !== '' ? StorageService::url($coverWebpKey) : '',
            'level'         => (int) $album->level,
            'view_count'    => (int) $album->view_count,
            'like_count'    => (int) $album->like_count,
            'category'      => $album->category ? (string) $album->category->name : '',
            'category_slug' => $album->category ? (string) $album->category->slug : '',
            'tags'          => $album->tags ? $album->tags->map(static fn($t) => [
                'name' => (string) $t->name,
                'slug' => (string) $t->slug,
            ])->toArray() : [],
            'images'        => [],
            'video'         => null,
        ];

        $canAccess = ContentAccessService::canAccess($user, $album);
        $data['locked'] = !$canAccess;
        $data['required_level'] = (int) $album->level;

        if ($album->type === Album::TYPE_VIDEO) {
            $video = $album->video;
            if ($video) {
                $data['video'] = [
                    // 视频 URL 仅在有访问权时返回
                    'url'      => $canAccess ? StorageService::url((string) $video->path) : '',
                    // poster 始终返回（用于锁定时展示缩略图）
                    'poster'   => $video->poster ? StorageService::url((string) $video->poster)
                                : ($coverThumbKey !== '' ? $data['cover_thumb'] : $data['cover']),
                    'duration' => (int) $video->duration,
                    'width'    => (int) $video->width,
                    'height'   => (int) $video->height,
                    'size'     => (int) $video->size,
                ];
            }
        } else {
            $data['images'] = $album->images()->select()->map(static function (Image $img) use ($canAccess): array {
                $webp = $img->webp_path ?: $img->path;
                return [
                    // 原图 URL 仅在有访问权时返回
                    'url'   => $canAccess ? StorageService::url((string) $img->path) : '',
                    'webp'  => StorageService::url((string) $webp),
                    'thumb' => $img->thumb_path ? StorageService::url((string) $img->thumb_path) : '',
                    'width' => (int) $img->width,
                    'height' => (int) $img->height,
                ];
            })->toArray();
        }
        return $data;
    }

    /**
     * 列表卡片载荷（封面优先返回缩略图，避免列表页泄露原图）
     */
    public static function cardPayload(Album $album): array
    {
        $coverThumbKey = (string) ($album->cover_thumb ?? '');
        $coverWebpKey = (string) ($album->cover_webp ?? '');
        $coverUrl = $album->cover ? StorageService::url((string) $album->cover) : '';
        $coverThumbUrl = $coverThumbKey !== '' ? StorageService::url($coverThumbKey) : '';
        $coverWebpUrl = $coverWebpKey !== '' ? StorageService::url($coverWebpKey) : '';
        // 优先 thumb > webp > 原图（兜底）
        $cover = $coverThumbUrl ?: ($coverWebpUrl ?: $coverUrl);

        return [
            'id'         => (int) $album->id,
            'title'      => (string) $album->title,
            'type'       => (string) $album->type,
            'type_label' => self::typeLabel((string) $album->type),
            'cover'      => $cover,
            'level'      => (int) $album->level,
            'view_count' => (int) $album->view_count,
            'like_count' => (int) $album->like_count,
            'category'   => $album->category ? (string) $album->category->name : '',
            'tags'       => $album->tags ? $album->tags->column('name') : [],
        ];
    }

    /**
     * 内容类型中文标签
     */
    public static function typeLabel(string $type): string
    {
        return match ($type) {
            Album::TYPE_VIDEO  => '视频',
            Album::TYPE_SINGLE => '单图',
            default            => '图集',
        };
    }

    /**
     * 浏览量 +1
     */
    public static function incrementView(Album $album): void
    {
        Album::where('id', (int) $album->id)->inc('view_count')->update();
    }
}
