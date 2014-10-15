<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Updater;

use ICanBoogie\Module;

class Updater
{
	use \ICanBoogie\GetterTrait;

	static public function run(\ICanBoogie\Core $core)
	{
		$files = [];

		foreach ($core->modules->descriptors as $module_id => $descriptor)
		{
			$pathname = $descriptor[Module::T_PATH] . 'updates.php';

			if (!file_exists($pathname))
			{
				continue;
			}

			$files[] = $pathname;
		}

		$collection = new UpdateCollection($files);
		$updater = new static($core);
		$updater($collection);
	}

	private $app;

	protected function get_app()
	{
		return $this->app;
	}

	protected function __construct($app)
	{
		$this->app = $app;
	}

	public function __invoke(UpdateCollection $collection)
	{
		foreach ($collection as $descriptor)
		{
			require_once $descriptor->file;

			$class = $descriptor->class;
			$options = $descriptor->options;

			$update = new $class($this, $options, $descriptor);
			$update->run();
		}
	}
}