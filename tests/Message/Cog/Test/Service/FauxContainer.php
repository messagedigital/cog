<?php

namespace Message\Cog\Test\Service;

use Message\Cog\Service\Container;
use Closure;

/**
 * The service container for use in unit tests.
 *
 * This extends the Cog service container `Message\Cog\Service\Container`,
 * adding some functionality to aid unit testing.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class FauxContainer extends Container
{
	/**
	 * Get a service.
	 *
	 * For shared services, this invokes `SharedServiceIdentifier`, returning
	 * the closure within it.
	 *
	 * @param  string $id Identifier for the service
	 * @return mixed      The service definition
	 */
	public function offsetGet($id)
	{
		$return = parent::offsetGet($id);

		return $this->isShared($id) ? $return() : $return;
	}

	/**
	 * Check if a service was defined as shared using `share()`.
	 *
	 * @param  string $id Identifier for the service
	 * @return boolean    Result of the check
	 */
	public function isShared($id)
	{
		return $this->raw($id) instanceof SharedServiceIdentifier;
	}

	/**
	 * Wraps the defined closure in an instance of `SharedServiceIdentifier` so
	 * the instance is unique within the scope.
	 *
	 * This wraps the closure in a defined class rather than a closure so we can
	 * use `assertInstanceOf` to test that services were defined as shared.
	 *
	 * @param  Closure $callable The service definition
	 * @return SharedServiceIdentifier
	 */
	public function share(Closure $callable)
	{
		return new SharedServiceIdentifier($callable, $this);
	}
}