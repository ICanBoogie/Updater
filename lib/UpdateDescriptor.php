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

/**
 * @property-read array $options
 * @property-read string $normalized_id
 */
class UpdateDescriptor
{
	use AccessorTrait

	public $class;
	public $annotation;
	public $file;
	public $line;

	static private function resolve_options($comment)
	{
		preg_match_all('#@([^\s]+)\s+([^\n]+)#', $comment, $matches);

		return array_combine($matches[1], $matches[2]);
	}

	public function __construct($class, $annotation, $file, $line)
	{
		$this->class = $class;
		$this->annotation = $annotation;
		$this->file = $file;
		$this->line = $line;
	}

	protected function get_options()
	{
		return self::resolve_options($this->annotation);
	}

	protected function get_normalized_id()
	{
		$rc = strstr($this->class, '\Update');
		$rc = substr($rc, strlen('\Update'));
		$rc = str_pad($rc, 14, '0', STR_PAD_RIGHT);

		return $rc;
	}
}
