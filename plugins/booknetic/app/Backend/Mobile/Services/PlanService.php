<?php

namespace BookneticApp\Backend\Mobile\Services;

use BookneticApp\Backend\Mobile\Clients\FSCodeMobileAppClient;
use BookneticApp\Backend\Mobile\DTOs\Response\PlanResponse;
use BookneticApp\Backend\Mobile\Mappers\PlanMapper;

class PlanService
{
    private FSCodeMobileAppClient $client;

    private PlanMapper $mapper;

    public function __construct(FSCodeMobileAppClient $client)
    {
        $this->client = $client;
        $this->mapper = new PlanMapper();
    }

    /**
     * @return PlanResponse[]
     */
    public function getAll(): array
    {
        $response = $this->client->getPlans();

        if ($response->isError()) {
            return [];
        }

        return $this->mapper->toListResponse($response->getData());
    }
}
