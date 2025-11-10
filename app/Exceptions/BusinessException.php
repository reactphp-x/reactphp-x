<?php

namespace App\Exceptions;

use Exception;

class BusinessException extends Exception
{
    /**
     * 创建商业异常实例
     *
     * @param string $message 错误消息
     * @param int $code 错误代码，默认为1
     * @param \Throwable|null $previous 前一个异常
     */
    public function __construct(string $message = '', int $code = 1, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

