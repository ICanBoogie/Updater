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
use ICanBoogie\Module;

/**
 * Representation of a module update.
 *
 * @property-read ModelUpdaterList $models
 * @property-read ModelUpdater|\ICanBoogie\ActiveRecord\Model $model
 * @property-read Module $target
 */
class ModuleUpdater extends Update
{
	use AccessorTrait;

	/**
	 * @var Module
	 */
	protected $target;

	protected function get_target()
	{
		return $this->target;
	}

	protected $models;

	protected function lazy_get_models()
	{
		return new ModelUpdaterList($this->target);
	}

	protected function get_model()
	{
		return $this->models['primary'];
	}

	public function __construct(Module $target)
	{
		unset($this->models);

		$this->target = $target;
	}
}
