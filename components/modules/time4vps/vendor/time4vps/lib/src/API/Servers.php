<?php

namespace Time4vps\API;

use Time4vps\Base\Endpoint;
use Time4vps\Exceptions\APIException;
use Time4vps\Exceptions\AuthException;
use Time4vps\Exceptions\Exception;

class Servers extends Endpoint
{
    /**
     * Servers constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct('server');
    }

    /**
     * Get all servers
     * @return array Available servers array
     * @throws APIException|AuthException
     */
    public function all()
    {
        return $this->get();
    }
}
