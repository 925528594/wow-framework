<?php

namespace Negan\Routing;

use Negan\Pipeline\Pipeline as BasePipeline;
use Throwable;

class Pipeline extends BasePipeline
{
    /**
     * 处理每个管道中即将传递到下一管道的$carry值
     *
     * @param  mixed $carry
     * @return mixed
     */
    protected function handleCarry($carry)
    {
        return $carry;
    }

    /**
     * 处理管道中给定的异常
     *
     * @param mixed $passable
     * @param \Throwable $e
     * @return mixed
     * @throws \Throwable
     */
    protected function handleException($passable, Throwable $e)
    {
        throw $e;
    }
}
