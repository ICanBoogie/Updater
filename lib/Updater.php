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

use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\Module\Descriptor;

/**
 * @property-read \ICanBoogie\Core|\ICanBoogie\Module\CoreBindings $app
 */
class Updater
{
	use AccessorTrait;

	/**
	 * @param \ICanBoogie\Core|\ICanBoogie\Module\CoreBindings $app
	 */
	static public function run(\ICanBoogie\Core $app)
	{
		$files = [];

		foreach ($app->modules->descriptors as $module_id => $descriptor)
		{
			$pathname = $descriptor[Descriptor::PATH] . 'updates.php';

			if (!file_exists($pathname))
			{
				continue;
			}

			$files[] = $pathname;
		}

		$collection = new UpdateCollection($files);
		$updater = new static($app);
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
