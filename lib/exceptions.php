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

class AssertionFailed extends \Exception
{
	protected $method;
	protected $arguments;

	public function __construct($method, $arguments, $code=500, \Exception $previous=null)
	{
		$this->method = $method;
		$this->arguments = $arguments;

		parent::__construct("The assertion failed: $method, with the following arguments: " . json_encode($arguments), $code, $previous);
	}
}