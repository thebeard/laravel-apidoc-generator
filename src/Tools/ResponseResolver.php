<?php

namespace Mpociot\ApiDoc\Tools;

use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Mpociot\ApiDoc\Tools\ResponseStrategies\ResponseTagStrategy;
use Mpociot\ApiDoc\Tools\ResponseStrategies\ResponseCallStrategy;
use Mpociot\ApiDoc\Tools\ResponseStrategies\ResponseFileStrategy;
use Mpociot\ApiDoc\Tools\ResponseStrategies\TransformerTagsStrategy;

class ResponseResolver
{
    /**
     * @var array
     */
    public static $strategies = [
        ResponseTagStrategy::class,
        TransformerTagsStrategy::class,
        ResponseFileStrategy::class,
        ResponseCallStrategy::class,
    ];

    /**
     * @var Route
     */
    private $route;

    /**
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * @param array $tags
     * @param array $routeProps
     *
     * @return array|null
     */
    private function resolve(array $tags, array $routeProps)
    {
        $responses = [];
        foreach (static::$strategies as $strategy) {
            $strategy = new $strategy();
  
            /** @var Response[]|null $response */
            $responses = array_merge($responses, (array) $strategy($this->route, $tags, $routeProps));
        }
        if (!is_null($responses)) {
            $mapped_responses = array_map(function (Response $response) {
                return ['status' => $response->getStatusCode(), 'content' => $this->getResponseContent($response)];
            }, $responses);
  
            usort($mapped_responses, function ($a, $b) {
                return $a['status'] < $b['status'] ? -1 : 1;
            });
  
            return $mapped_responses;
        }
    }

    /**
     * @param $route
     * @param $tags
     * @param $routeProps
     *
     * @return array
     */
    public static function getResponse($route, $tags, $routeProps)
    {
        return (new static($route))->resolve($tags, $routeProps);
    }

    /**
     * @param $response
     *
     * @return mixed
     */
    private function getResponseContent($response)
    {
        return $response ? $response->getContent() : '';
    }
}
