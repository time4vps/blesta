<?php

namespace Time4vps\API;

use Time4vps\Base\Endpoint;
use Time4vps\Exceptions\APIException;
use Time4vps\Exceptions\AuthException;
use Time4vps\Exceptions\Exception;

class Product extends Endpoint
{
    /**
     * @var int
     */
    protected $product_id;

    /**
     * Product constructor.
     *
     * @param int|null $product_id
     * @throws Exception
     */
    public function __construct($product_id = null)
    {
        parent::__construct('');

        if ($product_id) {
            $this->product_id = (int) $product_id;
            if ($this->product_id <= 0) {
                throw new Exception("Product ID '{$product_id}' is invalid");
            }
        }
    }

    /**
     * Get product configuration details
     *
     * @return array
     * @throws APIException|AuthException
     */
    public function details()
    {
        $this->mustHave('product_id');

        $this->endpoint = 'order';
        return $this->get("/{$this->product_id}");
    }

    /**
     * Get available vps servers
     *
     * @return array
     * @throws APIException|AuthException
     */
    public function getAvailablevps()
    {
        $this->endpoint = 'category';
        return $this->get('/available/vps');
    }
}
