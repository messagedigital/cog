<?php

namespace Message\Cog\Module;

use Message\Cog\Bootstrap\LoaderInterface as BootstrapLoaderInterface;
use Message\Cog\Event\DispatcherInterface;
use Message\Cog\Event\Event;

/**
 * Loads Cog modules and their related files.
 *
 * @todo Make this class cachable for performance gains.
 */
class Loader implements LoaderInterface
{
	protected $_locator;
	protected $_bootstrapLoader;
	protected $_eventDispatcher;

	protected $_modules;

	/**
	 * Constructor.
	 *
	 * @param LocatorInterface         $locator         The module locator
	 * @param BootstrapLoaderInterface $bootstrapLoader The bootsreap loader
	 * @param DispatcherInterface      $dispatcher      The event dispatcher to use for event firing
	 */
	public function __construct(LocatorInterface $locator, BootstrapLoaderInterface $bootstrapLoader,
		DispatcherInterface $dispatcher)
	{
		$this->_locator         = $locator;
		$this->_bootstrapLoader = $bootstrapLoader;
		$this->_eventDispatcher = $dispatcher;
	}

	/**
	 * Validate & load a list of modules, in order.
	 *
	 * @param array $modules List of module names to load, e.g. `Message\Raven`
	 */
	public function run(array $modules)
	{
		$this->_modules = $modules;
		$this->_validateModules();
		$this->_loadModules();
	}

	/**
	 * Check if a given module was requested to be loaded within this
	 * application.
	 *
	 * @param  string $name Module name to check for
	 * @return boolean      Result of the check
	 */
	public function exists($name)
	{
		return in_array($name, $this->_modules);
	}

	/**
	 * Get list of modules that this application has requested are loaded.
	 *
	 * @return array Array of modules
	 */
	public function getModules()
	{
		return $this->_modules;
	}

	protected function _validateModules()
	{
		if (empty($this->_modules)) {
			throw new Exception(
				'No modules found',
				Exception::NO_MODULES_FOUND
			);
		}

		foreach ($this->_modules as $module) {
			if (!file_exists($this->_locator->getPath($module))) {
				throw new Exception(
					sprintf('Module could not be found: `%s`', $module),
					Exception::MODULE_NOT_FOUND
				);
			}
		}
	}

	protected function _loadModules()
	{
		foreach ($this->_modules as $module) {
			// Load the bootstraps
			$this->_bootstrapLoader
				->addFromDirectory(
					$this->_locator->getPath($module) . 'Bootstrap',
					$module . '\\Bootstrap'
				)
				->load();

			// Fire the "module loaded" event
			$this->_eventDispatcher->dispatch(
				sprintf(
					'module.%s.load.success',
					strtolower(str_replace('\\', '.', $module))
				),
				new Event
			);
		}

		// Fire the "all modules loaded" event
		$this->_eventDispatcher->dispatch('modules.load.success', new Event);
	}
}