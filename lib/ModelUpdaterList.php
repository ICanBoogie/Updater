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
use ICanBoogie\Module;

/**
 * A model updater list.
 */
class ModelUpdaterList implements \ArrayAccess
{
	protected $module;
	protected $list = array();

	public function __construct(Module $module)
	{
		$this->module = $module;
	}

	public function offsetGet($model_id)
	{
		if (empty($this->list[$model_id]))
		{
			$this->list[$model_id] = new ModelUpdater($this->module->model($model_id));
		}

		return $this->list[$model_id];
	}

	public function offsetExists($model_id)
	{
		return isset($this->list[$model_id]);
	}

	public function offsetSet($offset, $value)
	{

	}

	public function offsetUnset($offset)
	{

	}
}