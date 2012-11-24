<?php

namespace Message\Cog\Validation;

/**
* Loads and maintains a set of rules and filters to use in validation
*/
class Loader
{
	
	public function __construct(Messages $messages, array $classes = null)
	{
		$this->_messages = $messages;
		$this->_rules = array();
		$this->_filters = array();

		if($classes) {
			$this->registerClasses($classes);
		}
	}

	public function registerClasses(array $classes)
	{
		foreach($classes as $class) {
			$collection = new $class;

			if(!$collection instanceof CollectionInterface) {
				throw new \Exception(sprintf('%s must implement CollectionInterface.', $class));
			}

			$collection->register($this);
		}
	}

	public function getRule($name)
	{
		if(!isset($this->_rules[$name])) {
			return false;
		}

		return $this->_rules[$name];
	}

	public function getFilter($name)
	{
		if(!isset($this->_filters[$name])) {
			return false;
		}

		return $this->_filters[$name];
	}

	public function registerRule($name, $func, $errorMessage)
	{
		$this->_register('rule', $name, $func);
		$this->_messages->setDefaultErrorMessage($name, $errorMessage);
	}

	public function registerFilter($name, $func)
	{
		$this->_register('filter', $name, $func);
	}

	protected function _register($type, $name, $func)
	{
		if(!is_callable($func)) {
			throw new \Exception(sprintf('Cannot register %s `%s`; Second parameter must be callable.', $type, $name));
		}

		if(isset($this->_{$type}[$name])) {
			throw new \Exception(sprintf('A filter with the name `%s` has already been registered.', $name));
		}

		$attr = '_'.$type.'s';
		$this->{$attr}[$name] = $func;
	}
}