<?php

namespace Negan\Routing;

use Negan\Http\RedirectResponse;

class Redirector
{
    /**
     * @param string $path
     * @param int $status
     * @param array $headers
     * @param bool|null $secure
     * @return \Negan\Http\RedirectResponse
     */
    public function to($path, $status = 302, $headers = [], $secure = null)
    {
        return $this->createRedirect($path, $status, $headers);
    }

    /**
     * @param string $path
     * @param int $status
     * @param array $headers
     * @return \Negan\Http\RedirectResponse
     */
    protected function createRedirect($path, $status, $headers)
    {
        return new RedirectResponse($path, $status, $headers);
    }
}
