<?php

namespace BookneticApp\Providers\Core\Dto;

use BookneticApp\Providers\Core\RestRequest;

class ParameterResolver
{
    /**
     * Resolve controller method parameters.
     *
     * @param callable $fn The controller method callable [object, 'method'] or closure
     * @param RestRequest $restRequest The current REST request
     * @return array|null Resolved arguments array, or null if no DTO params (use legacy call)
     */
    public static function resolve(callable $fn, RestRequest $restRequest): ?array
    {
        if (!is_array($fn)) {
            return null;
        }

        $className = is_object($fn[0]) ? get_class($fn[0]) : $fn[0];
        $methodName = $fn[1];

        $paramsMeta = DtoMetadataCache::getMethodParams($className, $methodName);

        if ($paramsMeta === null) {
            return null;
        }

        $requestData = $restRequest->allParams();
        $resolvedArgs = [];

        foreach ($paramsMeta as $param) {
            if ($param['fromRequest']) {
                $dtoClass = $param['type'];
                $dtoMeta = DtoMetadataCache::getDtoMeta($dtoClass);

                if ($dtoMeta !== null) {
                    $dto = DtoHydrator::hydrateFromCache($dtoClass, $requestData, $dtoMeta);
                    DtoValidator::validateFromCache($dto, $dtoMeta);
                } else {
                    $dto = DtoHydrator::hydrate($dtoClass, $requestData);
                    DtoValidator::validate($dto);
                }

                $resolvedArgs[] = $dto;
            } elseif ($param['type'] === RestRequest::class || is_subclass_of($param['type'], RestRequest::class)) {
                $resolvedArgs[] = $restRequest;
            }
        }

        return $resolvedArgs;
    }
}
