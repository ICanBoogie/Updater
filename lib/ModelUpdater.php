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

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\ActiveRecord\SchemaColumn;
use ICanBoogie\ActiveRecord\Schema;

/**
 *
 * @method ModelUpdater assert_has_column(string $column_name)
 * Asserts that a model has a column with the specified name.
 * @method ModelUpdater assert_not_has_column(string $column_name)
 * Asserts that a model does not have a column with the specified name.
 * @method ModelUpdater assert_column_has_size(string $column_name, $size)
 * Asserts a column has a given size.
 * @method ModelUpdater assert_not_column_has_size(string $column_name, $size)
 * Asserts a column does not have a given size.
 *
 * @method mixed model(string $statement, array $args = [])
 *
 * @property-read array $columns
 * @property-read Schema $schema
 */
class ModelUpdater
{
	/**
	 * @var Model
	 */
	protected $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	public function __call($method, $arguments)
	{
		if ($method == 'model')
		{
			return call_user_func_array($this->model, $arguments);
		}

		if (strpos($method, 'assert_') === 0)
		{
			$not = false;
			$callback = substr($method, strlen('assert_'));

			if (strpos($callback, 'not_') === 0)
			{
				$not = true;
				$callback = substr($callback, strlen('not_'));
			}

			$rc = call_user_func_array(array($this, $callback), $arguments);

			if ($not)
			{
				$rc = !$rc;
			}

			if (!$rc)
			{
				throw new AssertionFailed($method, $arguments);
			}

			return $this;
		}

		return call_user_func_array([ $this->model, $method ], $arguments);
	}

	public function __get($property)
	{
		switch ($property)
		{
			case 'columns':

				if ($this->_columns === null)
				{
					$this->_columns = $this->get_columns();
				}

				return $this->_columns;

			case 'target':

				return $this->model;
		}

		return $this->model->$property;
	}

	private $_columns;

	protected function get_columns()
	{
		return $this->model('SHOW FULL COLUMNS FROM {self}')
		->all(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE|\PDO::FETCH_ASSOC);
	}

	protected function revoke_schema()
	{
		$this->_columns = null;
	}

	/*
	 * Tests
	 */

	/**
	 * Checks if the model has the specified column.
	 *
	 * @param string $column_name
	 *
	 * @return bool `true` if the specified column exists, `false` otherwise.
	 */
	public function has_column($column_name)
	{
		return isset($this->columns[$column_name]);
	}

	/**
	 * Checks if the specified column has the specified size.
	 *
	 * @param string $column_name The name of the column.
	 * @param string $size The expected size of the column.
	 *
	 * @throws \InvalidArgumentException If the column doesn't exists.
	 * @throws \Exception If the size of the column could not de retrieved.
	 *
	 * @return bool `true` if the specified column has the specified size, `false` otherwise.
	 */
	public function column_has_size($column_name, $size)
	{
		if (!$this->has_column($column_name))
		{
			throw new \InvalidArgumentException("Column $column_name does not exists.");
		}

		$column = $this->columns[$column_name];

		if (!preg_match('#\((\d+)\)#', $column['Type'], $matches))
		{
			throw new \Exception("Unable to retrieve size for column $column_name.");
		}

		return $size == $matches[1];
	}

	/*
	 * Actions
	 */

	/**
	 * Alters a column.
	 *
	 * @param string $column_name
	 * @param array $changes The changes to apply to the column. If the changes are empty,
	 * the schema definition is used instead.
	 */
	public function alter_column($column_name, array $changes=null)
	{
		$new_column_name = $column_name;

		if ($changes)
		{
			if (isset($changes['name']))
			{
				$new_column_name = $changes['name'];
				unset($changes['name']);
			}

			$column = $this->schema[$new_column_name];
		}
		else
		{
			$column = $this->schema[$column_name];
		}

		$this->model("ALTER TABLE {self} CHANGE `$column_name` `$new_column_name` " . (string) $column);
		$this->revoke_schema();
	}

	/**
	 * Renames a column in a table.
	 *
	 * @param string $column_name The name of the column to rename.
	 * @param string $new_column_name The new name of the column.
	 */
	public function rename_column($column_name, $new_column_name)
	{
		$this->alter_column($column_name, array('name' => $new_column_name));
		$this->revoke_schema();
	}

	public function create_column($column_name, array $options = array())
	{
		$schema = $this->model->schema;
		$position = $this->resolve_column_position($column_name, $schema);

		$this->model("ALTER TABLE `{self}` ADD `$column_name` " . $schema[$column_name] . " $position");
		$this->revoke_schema();
	}

	/**
	 * Removes a column from the table.
	 *
	 * @param string $column_name The name of the column to remove.
	 */
	public function remove_column($column_name)
	{
		$this->model("ALTER TABLE `{self}` DROP COLUMN `$column_name`");
		$this->revoke_schema();
	}

	protected function resolve_column_position($column_name, Schema $schema)
	{
		$names = array_keys($schema->columns);
		$key = array_search($column_name, $names);

		if ($key == 0)
		{
			return 'FIRST';
		}

		return 'AFTER `' . $names[$key - 1] . '`';
	}

	/*
	 * Index
	 */

	public function create_unique_index($index_name, $column_name=null)
	{
		if (!$column_name)
		{
			$column_name = $index_name;
		}

		$this->model("CREATE UNIQUE INDEX `$index_name` ON `{self}` (`$column_name`)");
		$this->revoke_schema();
	}
}
