<?php

namespace BookneticApp\Providers\Router;

use BookneticApp\Providers\Core\Dto\DtoHydrator;
use BookneticApp\Providers\Core\Dto\DtoMetadataCache;
use BookneticApp\Providers\Core\Dto\DtoValidator;
use BookneticApp\Providers\Core\RestRequest;
use BookneticApp\Providers\Router\Contracts\ParameterResolverInterface;
use WP_REST_Request;

class BookneticParameterResolver implements ParameterResolverInterface
{
    private const SCALAR_TYPES = ['int', 'float', 'string', 'bool', 'array', 'mixed'];

    /**
     * @param RouteItem $route
     * @param mixed $request TODO: add mixed type hint when PHP 7.4 support is dropped
     * @return array
     */
    public function resolve(RouteItem $route, $request): array
    {
        /** @var WP_REST_Request $request */
        $args = [];

        foreach ($route->params as $param) {
            if ($param->type === RestRequest::class) {
                $args[] = new RestRequest($request);
                continue;
            }

            switch ($param->source) { // TODO: replace with match() when PHP 7.4 support is dropped
                case 'route':
                    $args[] = $this->resolveRoute($param, $request);
                    break;
                case 'body':
                    $args[] = $this->resolveBody($param, $request);
                    break;
                case 'query':
                    $args[] = $this->resolveQuery($param, $request);
                    break;
                case 'form':
                    $args[] = $this->resolveForm($param);
                    break;
            }
        }

        return $args;
    }

    /**
     * @return mixed TODO: add mixed return type hint when PHP 7.4 support is dropped
     */
    private function resolveRoute(RouteParam $param, WP_REST_Request $request)
    {
        $key = $param->alias ?? $param->name;
        $value = $request->get_param($key);

        if ($value === null) {
            return $param->isOptional ? $param->default : null;
        }

        return $this->castScalar($value, $param->type);
    }

    /**
     * @return mixed TODO: add mixed return type hint when PHP 7.4 support is dropped
     */
    private function resolveBody(RouteParam $param, WP_REST_Request $request)
    {
        $data = (array) $request->get_json_params();

        if (empty($data)) {
            $data = (array) $request->get_body_params();
        }

        if ($this->isDto($param->type)) {
            return $this->hydrateAndValidate($param->type, $data);
        }

        return $data;
    }

    /**
     * @return mixed TODO: add mixed return type hint when PHP 7.4 support is dropped
     */
    private function resolveQuery(RouteParam $param, WP_REST_Request $request)
    {
        $data = (array) $request->get_query_params();

        if ($this->isDto($param->type)) {
            return $this->hydrateAndValidate($param->type, $data);
        }

        return $data;
    }

    /**
     * @return mixed TODO: add mixed return type hint when PHP 7.4 support is dropped
     */
    private function resolveForm(RouteParam $param)
    {
        $key = $param->alias ?? $param->name;
        $value = $_FILES[$key] ?? null;

        if ($value === null) {
            return $param->isOptional ? $param->default : [];
        }

        return $value;
    }

    private function isDto(string $type): bool
    {
        return !in_array($type, self::SCALAR_TYPES, true) && class_exists($type);
    }

    private function hydrateAndValidate(string $dtoClass, array $data): object
    {
        $dtoMeta = DtoMetadataCache::getDtoMeta($dtoClass);

        if ($dtoMeta !== null) {
            $dto = DtoHydrator::hydrateFromCache($dtoClass, $data, $dtoMeta);
            DtoValidator::validateFromCache($dto, $dtoMeta);
        } else {
            $dto = DtoHydrator::hydrate($dtoClass, $data);
            DtoValidator::validate($dto);
        }

        return $dto;
    }

    /**
     * @param mixed $value TODO: add mixed type hints when PHP 7.4 support is dropped
     * @param string $type
     * @return mixed
     */
    private function castScalar($value, string $type)
    {
        // TODO: replace with match() when PHP 7.4 support is dropped
        switch ($type) {
            case 'int':    return (int) $value;
            case 'float':  return (float) $value;
            case 'bool':   return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'string': return (string) $value;
            default:       return $value;
        }
    }
}
