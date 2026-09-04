<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\middleware\AdminAuth;
use app\common\model\BrowseLog;
use app\common\model\Comment;
use app\common\model\Favorite;
use app\common\model\User as UserModel;
use app\common\model\VipLog;
use app\common\service\AdminLogService;
use app\common\service\AuthService;
use think\facade\Db;
use think\response\Json;

/**
 * 后台用户管理
 */
class User extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 用户列表（分页 + 关键词过滤）
     */
    public function index(): Json
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $size = min(100, max(1, (int) $this->request->get('size', 20)));
        $query = UserModel::field('id,username,email,vip_level,vip_expire_at,status,invite_code_used,last_login_at,create_time')
            ->order('id desc');

        $keyword = trim((string) $this->request->get('keyword', ''));
        if ($keyword !== '') {
            $query->where('username', 'like', '%' . $keyword . '%');
        }

        $list = $query->paginate(['list_rows' => $size, 'page' => $page]);
        return json([
            'code'    => 0,
            'message' => 'ok',
            'data'    => [
                'items' => $list->items(),
                'total' => $list->total(),
                'page'  => $page,
            ],
        ]);
    }

    /**
     * 创建或更新用户
     */
    public function save(): Json
    {
        $data = $this->request->post();
        $id = (int) ($data['id'] ?? 0);
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $email = trim((string) ($data['email'] ?? ''));
        $vipLevel = min(3, max(0, (int) ($data['vip_level'] ?? 0)));
        $status = (int) ($data['status'] ?? UserModel::STATUS_NORMAL) === UserModel::STATUS_BANNED
            ? UserModel::STATUS_BANNED : UserModel::STATUS_NORMAL;
        $vipExpireAt = trim((string) ($data['vip_expire_at'] ?? ''));

        $isCreate = $id <= 0;

        if ($isCreate) {
            if ($username === '') {
                throw new BizException('用户名不能为空', 1701);
            }
            if (strlen($password) < 6) {
                throw new BizException('密码不能少于 6 位', 1702);
            }
            if (UserModel::where('username', $username)->find()) {
                throw new BizException('用户名已存在', 1703);
            }
        } else {
            $user = UserModel::find($id);
            if (!$user) {
                throw new BizException('用户不存在', 1704);
            }
            if ($password !== '' && strlen($password) < 6) {
                throw new BizException('密码不能少于 6 位', 1702);
            }
            if ($username !== '' && $username !== (string) $user->username) {
                if (UserModel::where('username', $username)->where('id', '<>', $id)->find()) {
                    throw new BizException('用户名已存在', 1703);
                }
            }
        }

        Db::transaction(function () use ($id, $isCreate, $username, $password, $email, $vipLevel, $status, $vipExpireAt) {
            if ($isCreate) {
                UserModel::create([
                    'username'  => $username,
                    'password'  => AuthService::hashPassword($password),
                    'salt'      => '',
                    'email'     => $email !== '' ? $email : null,
                    'vip_level' => $vipLevel,
                    'status'    => $status,
                ]);
            } else {
                $user = UserModel::find($id);
                $payload = [
                    'email'     => $email !== '' ? $email : null,
                    'vip_level' => $vipLevel,
                    'status'    => $status,
                ];
                if ($username !== '' && $username !== (string) $user->username) {
                    $payload['username'] = $username;
                }
                if ($password !== '') {
                    $payload['password'] = AuthService::hashPassword($password);
                }
                if ($vipExpireAt === '') {
                    $payload['vip_expire_at'] = null;
                } elseif ($vipExpireAt !== null) {
                    $payload['vip_expire_at'] = $vipExpireAt;
                }
                $user->save($payload);
            }
        });

        AdminLogService::record(
            (int) $this->request->currentAdmin->id,
            $isCreate ? 'create_user' : 'update_user',
            $isCreate ? $username : (string) $id
        );
        return json(['code' => 0, 'message' => $isCreate ? '创建成功' : '更新成功', 'data' => null]);
    }

    /**
     * 删除用户（事务内级联清理评论/收藏/浏览/VIP 日志）
     */
    public function delete(int $id): Json
    {
        $user = UserModel::find($id);
        if (!$user) {
            throw new BizException('用户不存在', 1704);
        }

        Db::transaction(function () use ($id, $user) {
            Comment::where('user_id', $id)->delete();
            Favorite::where('user_id', $id)->delete();
            BrowseLog::where('user_id', $id)->delete();
            VipLog::where('user_id', $id)->delete();
            $user->delete();
        });

        AdminLogService::record(
            (int) $this->request->currentAdmin->id,
            'delete_user',
            $id . ':' . $user->username
        );
        return json(['code' => 0, 'message' => '删除成功', 'data' => null]);
    }
}
