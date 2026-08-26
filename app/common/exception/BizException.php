<?php
declare(strict_types=1);

namespace app\common\exception;

/**
 * 业务异常：携带业务码与可选 HTTP 状态码，由全局异常处理统一转 JSON
 */
class BizException extends \RuntimeException
{
    /** HTTP 状态码（默认 200，业务错误用业务码表达） */
    private int $httpStatus;

    public function __construct(string $message, int $code = 1, int $httpStatus = 200)
    {
        parent::__construct($message, $code);
        $this->httpStatus = $httpStatus;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
