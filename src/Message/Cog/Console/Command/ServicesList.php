<?php

namespace Message\Cog\Console\Command;

use Message\Cog\Service\Container as ServiceContainer;
use Message\Cog\Console\TableFormatter;
use Message\Cog\Console\TaskRunner;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * ServicesList
 *
 * Provides the services:list command.
 * List all registered services.
 */
class ServicesList extends Command
{
	protected function configure()
	{
		$this
			->setName('services:list')
			->setDescription('List all registered services.')
			->addArgument('search_term', InputArgument::OPTIONAL, 'Display services matching [search_term]')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->getFormatter()->setStyle('bold', new OutputFormatterStyle(null, null, array('bold')));
		$term = $input->getArgument('search_term');

		$container = ServiceContainer::instance();

		$services = $container->keys();
		$result = array();
		ksort($services);

		foreach($services as $name) {
			try {
				$serviceResult = $container[$name];

				if(is_object($serviceResult)) {
					$service = get_class($serviceResult);
				} else {
					$service = gettype($serviceResult) . '(' . var_export($serviceResult, true) . ')';
				}

				if(strlen($term) && strpos(strtolower($service.' '.$name), strtolower($term)) === false) {
					continue;
				}
			} catch(\Exception $e) {
				$service = 'ERROR';
			}

			$result[$name] = $service;
		}

		$msg = 'Found %s registered services'.(strlen($term) ? ' matching `%s`' : '').'.';
		$output->writeln(sprintf('<info>'.$msg.'</info>', count($result), $term));
		$table = $app->getHelperSet()->get('table')
			->setHeaders(array('Name', 'Type'));

		foreach($result as $name => $service) {
			$table->addRow(array($name, $service));
		}

		$table->render($output);
	}
}