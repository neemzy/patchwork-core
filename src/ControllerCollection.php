<?php

namespace Neemzy\Patchwork;

use Silex\ControllerCollection as BaseControllerCollection;

class ControllerCollection extends BaseControllerCollection
{
    /**
     * Cancels a route (or more)
     *
     * @param string       $path    Route URI
     * @param array|string $methods HTTP method(s)
     *
     * @return Neemzy\Patchwork\ControllerCollection Itself (for chaining)
     */
    public function cancel($path, $methods = ['GET', 'POST', 'PUSH', 'DELETE'])
    {
        $methods = array_map('strtoupper', (array)$methods);

        foreach ($this->controllers as $key => $controller) {
            $route = $controller->getRoute();

            if (($route->getPath() == $path) && (count(array_intersect($methods, $route->getMethods())))) {
                if (!count($methods_diff = array_diff($route->getMethods(), $methods))) {
                    unset($this->controllers[$key]);
                } else {
                    $controller->getRoute()->setMethods($methods_diff);
                }
            }
        }

        return $this;
    }
}
