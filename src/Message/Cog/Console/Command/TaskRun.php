<?php

namespace Message\Cog\Console\Command;

use Message\Cog\Console\Command;
use Message\Cog\Console\Task\Runner;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * TaskRun
 *
 * Provides the task:run command.
 * Runs a single task.
 */
class TaskRun extends Command
{
	protected function configure()
	{
		$this
			->setName('task:run')
			->setDescription('Run a task.')
			->addArgument('task_name', InputArgument::REQUIRED, 'The full name of the task.')
			->addArgument('arguments', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 
				'(Optional) Any arguments required by the task.')
			
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$name = $input->getArgument('task_name');

		if(!$task = $this->get('task.collection')->get($name)) {
			$output->writeln('<error>Task `'.$name.'` does not exist.</error>');
			return;
		}

		$command = $task[1];
		$runner = new Runner($command, $this->_services, $input->getArgument('arguments'));
	}
}
