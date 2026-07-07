<?php

namespace BookneticSaaS\Backend\Base;

use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use BookneticSaaS\Providers\Helpers\Helper;

class Ajax extends \BookneticApp\Providers\Core\Controller
{
    private FSCodeAPIClient $client;

    public function __construct(FSCodeAPIClient $client)
    {
        $this->client = $client;
    }

    public function ping()
    {
        return $this->success();
    }

    public function join_beta()
    {
        $response = $this->client->requestNew('booknetic-saas/product/join_beta', 'POST');

        if ($response->isError()) {
            return $this->error();
        }

        Helper::setOption('joined_beta', true);

        return $this->success();
    }
}
