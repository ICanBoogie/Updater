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
 * Representation of a module update.
 *
 * @property-read ModelUpdaterList $models
 * @property-read ModelUpdater $model
 */
class ModuleUpdater extends Update
{
	protected $module;

	public function __construct($module)
	{
		$this->module = $module;
	}

	private $_models;

	public function __get($property)
	{
		switch ($property)
		{
			case 'model':

				return $this->models['primary'];

			case 'models':

				if ($this->_models === null)
				{
					$this->_models = new ModelUpdaterList($this->module);
				}

				return $this->_models;
		}

		throw new PropertyNotDefined(array($property, $this));
	}
}