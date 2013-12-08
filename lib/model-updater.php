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
 * @property-read array $columns
 *
 * @method ModelUpdater assert_has_column() assert_has_column(string $column_name)
 * Asserts that a model has a column with the specified name.
 * @method ModelUpdater assert_not_has_column() assert_not_has_column(string $column_name)
 * Asserts that a model doesn't have a column with the specified name.
 */
class ModelUpdater
{
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

		throw new \BadMethodCallException("The method $method is not implemented.");
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

			case 'schema':

				if ($this->_schema === null)
				{
					$this->_schema = $this->get_schema();
				}

				return $this->_schema;
		}

		throw new PropertyNotDefined(array($property, $this));
	}

	private $_columns;

	protected function get_columns()
	{
		return $this->model('SHOW COLUMNS FROM {self}')
		->all(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE|\PDO::FETCH_ASSOC);
	}

	private $_schema;

	protected function get_schema()
	{
		$schema = $this->model->schema;

		return new Schema($schema['fields']);
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
		$column = $this->schema[$column_name];

		if ($changes)
		{
			if (isset($changes['name']))
			{
				$new_column_name = $changes['name'];
				unset($changes['name']);
			}

			$column = new SchemaColumn(array_merge($column->to_array(), $changes));
		}

		$this->model("ALTER TABLE {self} CHANGE `$column_name` `$new_column_name` " . (string) $column);
	}

	public function rename_column($column_name, $new_column_name)
	{
		$this->alter_column($column_name, array('name' => $new_column_name));
	}

	public function create_column($column_name, array $options=array())
	{
		$fields = $this->model->schema['fields'];
		$schema = new \ICanBoogie\ActiveRecord\Schema($fields);
		$position = $this->resolve_column_position($column_name, $fields);

		$this->model("ALTER TABLE `{self}` ADD `$column_name` " . $schema[$column_name] . " $position");
	}

	protected function resolve_column_position($column_name, $fields)
	{
		$names = array_keys($fields);
		$key = array_search($column_name, $names);

		if ($key == 0)
		{
			return 'FIRST';
		}

		return 'AFTER `' . $names[$key - 1] . '`';
	}
}

namespace ICanBoogie\ActiveRecord;

class Schema implements \ArrayAccess, \IteratorAggregate
{
	protected $columns = array();

	public function __construct(array $columns)
	{
		foreach ($columns as $column_id => $column_option)
		{
			$this->columns[$column_id] = new SchemaColumn($column_option);
		}
	}

	public function offsetExists($offset)
	{

	}

	public function offsetGet($name)
	{
		return $this->columns[$name];
	}

	public function offsetSet($offset, $value)
	{

	}

	public function offsetUnset($offset)
	{

	}

	public function getIterator()
	{
		return new \ArrayIterator($this->columns);
	}
}