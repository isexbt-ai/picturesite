<?php
namespace app;

use app\common\exception\BizException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 业务异常：统一 JSON
        if ($e instanceof BizException) {
            return json([
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'data'    => null,
            ], $e->getHttpStatus());
        }

        // 验证器异常：422
        if ($e instanceof ValidateException) {
            $error = $e->getError();
            $msg = is_array($error) ? implode('; ', $error) : (string) $error;
            return json([
                'code'    => 422,
                'message' => $msg,
                'data'    => null,
            ], 422);
        }

        // JSON 请求的兜底：避免暴露堆栈
        if ($request->isJson() || stripos((string) $request->contentType(), 'json') !== false) {
            return json([
                'code'    => 500,
                'message' => '服务器内部错误',
                'data'    => null,
            ], 500);
        }

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}
