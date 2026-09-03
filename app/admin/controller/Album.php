<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\middleware\AdminAuth;
use app\common\model\Album as AlbumModel;
use app\common\model\AlbumTag;
use app\common\model\Favorite;
use app\common\model\Image as ImageModel;
use app\common\model\Tag as TagModel;
use app\common\model\Video as VideoModel;
use app\common\service\AdminLogService;
use app\common\service\StorageService;
use think\facade\Db;
use think\response\Json;

/**
 * 后台内容管理：图集/单图/视频 CRUD + 图片/视频绑定
 */
class Album extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 内容列表（分页 + 状态/类型/关键词过滤）
     */
    public function index(): Json
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $size = min(50, max(1, (int) $this->request->get('size', 20)));
        $query = AlbumModel::with(['category', 'video']);

        if ($this->request->get('status', '') !== '') {
            $query->where('status', (int) $this->request->get('status'));
        }
        if ($this->request->get('type', '') !== '') {
            $query->where('type', (string) $this->request->get('type'));
        }
        if ($this->request->get('category_id', '') !== '') {
            $query->where('category_id', (int) $this->request->get('category_id'));
        }
        $keyword = trim((string) $this->request->get('keyword', ''));
        if ($keyword !== '') {
            $query->where('title', 'like', '%' . $keyword . '%');
        }

        $list = $query->order('id desc')->paginate(['list_rows' => $size, 'page' => $page]);

        return json([
            'code'    => 0,
            'message' => 'ok',
            'data'    => [
                'items'    => $list->items(),
                'total'    => $list->total(),
                'page'     => $page,
            ],
        ]);
    }

    /**
     * 内容详情（含图片/视频/标签）
     */
    public function detail(int $id): Json
    {
        $album = AlbumModel::with(['category', 'images', 'video', 'tags'])->find($id);
        if (!$album) {
            throw new BizException('内容不存在', 1601);
        }
        return json(['code' => 0, 'message' => 'ok', 'data' => $album]);
    }

    /**
     * 创建或更新内容
     */
    public function save(): Json
    {
        $data = $this->request->post();
        $this->validate($data, [
            'title' => 'require|max:150',
            'type'  => 'require|in:album,single,video',
        ]);

        $id = (int) ($data['id'] ?? 0);
        $type = (string) $data['type'];
        $payload = [
            'title'       => $data['title'],
            'subtitle'    => (string) ($data['subtitle'] ?? ''),
            'type'        => $type,
            'cover'       => (string) ($data['cover'] ?? ''),
            'cover_thumb' => (string) ($data['cover_thumb'] ?? ''),
            'cover_webp'  => (string) ($data['cover_webp'] ?? ''),
            'level'       => min(3, max(0, (int) ($data['level'] ?? 0))),
            'category_id' => (int) ($data['category_id'] ?? 0),
            'status'      => (int) ($data['status'] ?? AlbumModel::STATUS_DRAFT),
        ];

        $album = Db::transaction(function () use ($id, $payload, $data, $type) {
            if ($id > 0) {
                $album = AlbumModel::find($id);
                if (!$album) {
                    throw new BizException('内容不存在', 1601);
                }
                $album->save($payload);
            } else {
                $album = AlbumModel::create($payload);
            }

            // 同步标签
            $tags = $data['tags'] ?? [];
            $this->syncTags($album, is_array($tags) ? $tags : []);

            // 同步媒体
            if ($type === AlbumModel::TYPE_VIDEO) {
                $this->syncVideo($album, (array) ($data['video'] ?? []));
            } else {
                $this->syncImages($album, (array) ($data['images'] ?? []));
            }

            return $album;
        });

        AdminLogService::record((int) $this->request->currentAdmin->id, $id > 0 ? 'update_album' : 'create_album', (string) $album->id);
        return json([
            'code'    => 0,
            'message' => $id > 0 ? '更新成功' : '创建成功',
            'data'    => ['id' => (int) $album->id],
        ]);
    }

    /**
     * 删除内容（含关联记录与存储文件）
     */
    public function delete(int $id): Json
    {
        $album = AlbumModel::find($id);
        if (!$album) {
            throw new BizException('内容不存在', 1601);
        }

        // 清理存储文件（封面三尺寸 + 图集图片 + 视频）
        $this->deleteMediaKeys([
            (string) $album->cover,
            (string) ($album->cover_thumb ?? ''),
            (string) ($album->cover_webp ?? ''),
        ]);
        foreach ($album->images()->select() as $img) {
            $this->deleteMediaKeys([$img->path, $img->thumb_path, $img->webp_path]);
        }
        $video = $album->video;
        if ($video) {
            $this->deleteMediaKeys([$video->path, $video->poster]);
        }

        // 清理记录
        ImageModel::where('album_id', $id)->delete();
        VideoModel::where('album_id', $id)->delete();
        AlbumTag::where('album_id', $id)->delete();
        Favorite::where('album_id', $id)->delete();
        \app\common\model\BrowseLog::where('album_id', $id)->delete();
        $album->delete();

        AdminLogService::record((int) $this->request->currentAdmin->id, 'delete_album', (string) $id);
        return json(['code' => 0, 'message' => '删除成功', 'data' => null]);
    }

    /**
     * 同步图集图片（差集清理孤儿文件）
     *
     * @param array $images [{path, thumb_path, webp_path, width, height, size, sort}]
     */
    private function syncImages(AlbumModel $album, array $images): void
    {
        // 收集旧图集所有 key
        $oldKeys = [];
        foreach (ImageModel::where('album_id', (int) $album->id)->select() as $old) {
            foreach ([(string) $old->path, (string) ($old->thumb_path ?? ''), (string) ($old->webp_path ?? '')] as $k) {
                if ($k !== '') {
                    $oldKeys[$k] = true;
                }
            }
        }

        // 收集新图集所有 key
        $newKeys = [];
        foreach ($images as $img) {
            foreach ([(string) ($img['path'] ?? ''), (string) ($img['thumb_path'] ?? ''), (string) ($img['webp_path'] ?? '')] as $k) {
                if ($k !== '') {
                    $newKeys[$k] = true;
                }
            }
        }

        // 孤儿 = 旧中存在但新中不再引用的 key
        $orphanKeys = array_keys(array_diff_key($oldKeys, $newKeys));

        // 删除记录并重建
        ImageModel::where('album_id', (int) $album->id)->delete();
        $sort = 0;
        foreach ($images as $img) {
            if (empty($img['path'])) {
                continue;
            }
            $sort++;
            ImageModel::create([
                'album_id'   => (int) $album->id,
                'path'       => (string) $img['path'],
                'thumb_path' => (string) ($img['thumb_path'] ?? ''),
                'webp_path'  => (string) ($img['webp_path'] ?? ''),
                'sort'       => (int) ($img['sort'] ?? $sort),
                'width'      => (int) ($img['width'] ?? 0),
                'height'     => (int) ($img['height'] ?? 0),
                'size'       => (int) ($img['size'] ?? 0),
            ]);
        }

        // 清理被替换/移除的孤儿文件
        $this->deleteMediaKeys($orphanKeys);
    }

    /**
     * 同步视频文件
     *
     * @param array $video {path, poster, duration, width, height, size}
     */
    private function syncVideo(AlbumModel $album, array $video): void
    {
        if (empty($video['path'])) {
            return;
        }
        VideoModel::where('album_id', (int) $album->id)->delete();
        VideoModel::create([
            'album_id' => (int) $album->id,
            'path'     => (string) $video['path'],
            'poster'   => (string) ($video['poster'] ?? ''),
            'duration' => (int) ($video['duration'] ?? 0),
            'width'    => (int) ($video['width'] ?? 0),
            'height'   => (int) ($video['height'] ?? 0),
            'size'     => (int) ($video['size'] ?? 0),
            'sort'     => 1,
        ]);
    }

    /**
     * 按名称同步标签（不存在则自动创建）
     */
    private function syncTags(AlbumModel $album, array $tagNames): void
    {
        AlbumTag::where('album_id', (int) $album->id)->delete();
        foreach ($tagNames as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $tag = TagModel::where('name', $name)->find();
            if (!$tag) {
                $tag = TagModel::create(['name' => $name, 'slug' => self::makeSlug($name)]);
            }
            AlbumTag::create(['album_id' => (int) $album->id, 'tag_id' => (int) $tag->id]);
        }
    }

    /**
     * 生成确定性 slug（中文标签无拼音时用 hash 后缀）
     */
    private static function makeSlug(string $name): string
    {
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            return $name;
        }
        return 'tag_' . strtolower(substr(md5($name), 0, 6));
    }

    /**
     * 批量删除存储文件（忽略失败）
     */
    private function deleteMediaKeys(array $keys): void
    {
        foreach (array_filter($keys) as $key) {
            try {
                StorageService::delete((string) $key);
            } catch (\Throwable) {
                // 存储删除失败不影响数据删除
            }
        }
    }
}
