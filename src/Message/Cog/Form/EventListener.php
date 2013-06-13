<?php

namespace Message\Cog\Form;

use Message\Cog\Event\SubscriberInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Extension\Templating\TemplatingRendererEngine;
use Message\Cog\Event\EventListener as BaseListener;

/**
 * Class EventListener
 * @package Message\Cog\Form
 *
 * @author Thomas Marchant <thomas@message.co.uk>
 */
class EventListener extends BaseListener implements SubscriberInterface
{
	protected $_services;

	static public function getSubscribedEvents()
	{
		return array(
			'modules.load.success' => array(
				array('setupFormHelper'),
			)
		);
	}

	public function setupFormHelper()
	{
		// @todo consider the pattern of the infinite loop to help you find it

		$this->_services['templating.php.engine']->addHelpers(array(
			$this->_services['form.helper.php'],
			$this->_services['form.helper.twig']
		));

//		var_dump($this->_services['form.factory.twig']);

	}
}