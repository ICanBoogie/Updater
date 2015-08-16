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

use ICanBoogie\PropertyNotDefined;
use Icybee\Modules\Modules\ModuleCollection;

/**
 * Representation of an update.
 *
 * @property-read \ICanBoogie\Core|\ICanBoogie\Binding\ActiveRecord\CoreBindings|\ICanBoogie\Module\CoreBindings $app
 * @property-read string $id
 * @property-read string $normalized_id
 * @property-read ModuleUpdater $module
 * @property-read ModuleCollection $modules
 * @property-read Updater $updater
 */
abstract class Update
{
	private $updater;
	private $options;
	private $services = [];
	private $descriptor;

	public function __construct(Updater $updater, array $options, UpdateDescriptor $descriptor)
	{
		$this->updater = $updater;
		$this->options = $options;
		$this->descriptor = $descriptor;
	}

	public function __get($property)
	{
		switch ($property)
		{
			case 'id':

				return get_class($this);

			case 'normalized_id':

				return $this->descriptor->normalized_id;

			case 'app':

				return $this->updater->app;

			case 'updater':

				return $this->updater;

			case 'module':

				if (empty($this->services[$property]))
				{
					$this->services[$property] = $this->get_module();
				}

				return $this->services[$property];

			case 'modules':

				if (empty($this->services[$property]))
				{
					$this->services[$property] = $this->get_modules();
				}

				return $this->services[$property];
		}

		throw new PropertyNotDefined(array($property, $this));
	}

	protected function get_module()
	{
		return new ModuleUpdater($this->modules[$this->options['module']]);
	}

	protected function get_modules()
	{
		return $this->app->modules;
	}

	/**
	 * Run the update.
	 */
	public function run()
	{
		$target_name = (string) $this->module->target;
		$update_name = $this->normalized_id;
		$log_prefix = "[{$update_name} {$target_name}] ";

		$update_reflection = new \ReflectionClass($this);

		$this->before();

		foreach ($update_reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method_reflection)
		{
			$method_name = $method_reflection->name;

			if (strpos($method_name, 'update_') !== 0)
			{
				continue;
			}

			try
			{
				$this->$method_name();

				echo $log_prefix . \ICanBoogie\titleize(substr($method_name, 7)) . "\n";
			}
			catch (AssertionFailed $e)
			{
				continue;
			}
			catch (\Exception $e)
			{
				echo $log_prefix . "$method_name raised the following exception:\n\n " . $e . "\n\n";
			}
		}

		echo $log_prefix . "Done\n";
	}

	protected function before()
	{

	}
}
