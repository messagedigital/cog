<?php

namespace Message\Cog\Controller;

use Message\Cog\Event\SubscriberInterface;
use Message\Cog\Service\ContainerInterface;
use Message\Cog\Service\ContainerAwareInterface;
use Message\Cog\HTTP\RequestAwareInterface;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Event listener for the Controller component.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class EventListener implements SubscriberInterface, ContainerAwareInterface
{
	protected $_services;

	static public function getSubscribedEvents()
	{
		return array(
			KernelEvents::CONTROLLER => array(
				array('dependencyInjectController'),
			)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function setContainer(ContainerInterface $services)
	{
		$this->_services = $services;
	}

	/**
	 * For a given request, before the controller is executed, inject the
	 * service container and the current request if the class implements
	 * `Service\ContainerAwareInterface` and/or `HTTP\RequestAwareInterface`.
	 *
	 * @param FilterControllerEvent $event The event instance
	 */
	public function dependencyInjectController(FilterControllerEvent $event)
	{
		$controller      = $event->getController();
		$controllerClass = array_shift($controller);

		if ($controllerClass instanceof ContainerAwareInterface) {
			$controllerClass->setContainer($this->_services);
		}
		if ($controllerClass instanceof RequestAwareInterface) {
			$controllerClass->setRequest($this->_services['request']);
		}
	}
}