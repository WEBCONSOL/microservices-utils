<?php

namespace Ezpizee\Utils\FastRoute;

class CachedDispatcher
{
    public static function invoke(callable $routeDefinitionCallback, array $options = [])
    {
        $options += [
            'routeParser' => 'Ezpizee\\Utils\\FastRoute\\RouteParser\\Std',
            'dataGenerator' => 'Ezpizee\\Utils\\FastRoute\\DataGenerator\\GroupCountBased',
            'dispatcher' => 'Ezpizee\\Utils\\FastRoute\\Dispatcher\\GroupCountBased',
            'routeCollector' => 'Ezpizee\\Utils\\FastRoute\\RouteCollector',
            'cacheDisabled' => false,
        ];

        if (!isset($options['cacheFile'])) {
            throw new \LogicException('Must specify "cacheFile" option');
        }

        if (!$options['cacheDisabled'] && file_exists($options['cacheFile'])) {
            $dispatchData = require $options['cacheFile'];
            if (!is_array($dispatchData)) {
                throw new \RuntimeException('Invalid cache file "' . $options['cacheFile'] . '"');
            }
            return new $options['dispatcher']($dispatchData);
        }

        $routeCollector = new $options['routeCollector'](
            new $options['routeParser'], new $options['dataGenerator']
        );
        $routeDefinitionCallback($routeCollector);

        /** @var RouteCollector $routeCollector */
        $dispatchData = $routeCollector->getData();
        if (!$options['cacheDisabled']) {
            file_put_contents(
                $options['cacheFile'],
                '<?php return ' . var_export($dispatchData, true) . ';'
            );
        }

        return new $options['dispatcher']($dispatchData);
    }
}
