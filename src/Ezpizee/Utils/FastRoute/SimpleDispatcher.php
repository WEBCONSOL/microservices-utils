<?php

namespace Ezpizee\Utils\FastRoute;

class SimpleDispatcher
{
    public static function invoke(callable $routeDefinitionCallback, array $options = [])
    {
        $options += [
            'routeParser' => 'Ezpizee\\Utils\\FastRoute\\RouteParser\\Std',
            'dataGenerator' => 'Ezpizee\\Utils\\FastRoute\\DataGenerator\\GroupCountBased',
            'dispatcher' => 'Ezpizee\\Utils\\FastRoute\\Dispatcher\\GroupCountBased',
            'routeCollector' => 'Ezpizee\\Utils\\FastRoute\\RouteCollector',
        ];

        /** @var RouteCollector $routeCollector */
        $routeCollector = new $options['routeCollector'](
            new $options['routeParser'], new $options['dataGenerator']
        );
        $routeDefinitionCallback($routeCollector);

        return new $options['dispatcher']($routeCollector->getData());
    }
}
