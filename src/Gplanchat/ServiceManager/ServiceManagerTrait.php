<?php
/**
 * This file is part of php-service-manager.
 *
 * php-service-manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * php-service-manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU Lesser General Public License
 * along with php-service-manager.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Grégory PLANCHAT<g.planchat@gmail.com>
 * @licence GNU Lesser General Public Licence (http://www.gnu.org/licenses/lgpl-3.0.txt)
 */

/**
 * @namespace
 */
namespace Gplanchat\ServiceManager;

use SplPriorityQueue;
use Gplanchat\ServiceManager\RuntimeException;

trait ServiceManagerTrait
{
    /**
     * @var array
     */
    private $aliases = [];

    /**
     * @var array
     */
    private $invokables = [];

    /**
     * @var array
     */
    private $singletons = [];

    /**
     * @var array
     */
    private $factories = [];

    /**
     * @param string $serviceName
     * @return mixed
     */
    public function get($serviceName)
    {
        while ($this->isAlias($serviceName)) {
            $serviceName = $this->getAlias($serviceName);
        }

        if (true === $this->isInvokable($serviceName)) {
            return $this->invoke($this->invokables[$serviceName]);
        }

        if (true === $this->isSingleton($serviceName)) {
            if (is_string($this->singletons[$serviceName])) {
                $this->singletons[$serviceName] = $this->invoke($this->singletons[$serviceName]);
            }

            return $this->singletons[$serviceName];
        }

        if (true === $this->isFactory($serviceName)) {
            return $this->invokeFactory($this->invokables[$serviceName]);
        }

        return null;
    }

    /**
     * @param string $serviceName
     * @return mixed
     */
    public function __invoke()
    {
        return $this->get(func_get_arg(0));
    }

    /**
     * @param string $serviceName
     * @return bool
     */
    public function isAlias($serviceName)
    {
        if (isset($this->aliases[$serviceName])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $serviceName
     * @return bool
     */
    public function isInvokable($serviceName)
    {
        if (isset($this->invokables[$serviceName])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $serviceName
     * @return bool
     */
    public function isSingleton($serviceName)
    {
        if (isset($this->singletons[$serviceName])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $serviceName
     * @return bool
     */
    public function isFactory($serviceName)
    {
        if (isset($this->factories[$serviceName])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $className
     * @return mixed
     */
    public function invoke($className)
    {
        return new $className;
    }

    /**
     * @param string $className
     * @return mixed
     */
    public function invokeFactory($serviceName)
    {
        if (isset($this->factories[$serviceName])) {
            $factory = $this->factories[$serviceName];
            return $factory[$serviceName]($this);
        }

        return null;
    }

    /**
     * @param string $serviceName
     * @return null
     */
    public function getAlias($serviceName)
    {
        if (isset($this->aliases[$serviceName])) {
            return $this->aliases[$serviceName];
        }

        return null;
    }

    /**
     * @param string $serviceName
     * @param string $alias
     * @param bool $allowOverride
     * @return ServiceManagerInterface
     * @throws Gplanchat\ServiceManager\RuntimeException
     */
    public function registerAlias($serviceName, $alias, $allowOverride = false)
    {
        if (!isset($this->aliases[$serviceName]) || $allowOverride) {
            $this->aliases[$serviceName] = $alias;
        } else {
            throw new RuntimeException(sprintf('Alias "%s" has already been registered.', $serviceName));
        }

        return $this;
    }

    /**
     * @param string $serviceName
     * @param string $invokable
     * @param bool $allowOverride
     * @return ServiceManagerInterface
     * @throws Gplanchat\ServiceManager\RuntimeException
     */
    public function registerInvokable($serviceName, $invokable, $allowOverride = false)
    {
        if (!isset($this->invokables[$serviceName]) || $allowOverride) {
            $this->invokables[$serviceName] = $invokable;
        } else {
            throw new RuntimeException(sprintf('Invokable "%s" has already been registered.', $serviceName));
        }

        return $this;
    }

    /**
     * @param string $serviceName
     * @param string $singleton
     * @param bool $allowOverride
     * @return ServiceManagerInterface
     * @throws Gplanchat\ServiceManager\RuntimeException
     */
    public function registerSingleton($serviceName, $singleton, $allowOverride = false)
    {
        if (!isset($this->singletons[$serviceName]) || $allowOverride) {
            $this->singletons[$serviceName] = $singleton;
        } else {
            throw new RuntimeException(sprintf('Singleton "%s" has already been registered.', $serviceName));
        }

        return $this;
    }

    /**
     * @param string $serviceName
     * @param callable $factory
     * @param bool $allowOverride
     * @return ServiceManagerInterface
     * @throws Gplanchat\ServiceManager\RuntimeException
     */
    public function registerFactory($serviceName, callable $factory, $allowOverride = false)
    {
        if (!isset($this->singletons[$serviceName]) || $allowOverride) {
            $this->factories[$serviceName] = $factory;
        } else {
            throw new RuntimeException(sprintf('Factory "%s" has already been registered.', $serviceName));
        }

        return $this;
    }
}