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

/**
 * Representation of an update.
 *
 * @property-read ModuleUpdater $module
 */
abstract class Update
{
	private $updater;
	private $options;
	private $services = array();

	public function __construct(Updater $updater, array $options)
	{
		$this->updater = $updater;
		$this->options = $options;
	}

	public function __get($property)
	{
		switch ($property)
		{
			case 'id':

				return get_class($this);

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
		return \ICanBoogie\Core::get()->modules;
	}
}