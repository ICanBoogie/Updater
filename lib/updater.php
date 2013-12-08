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

class Updater
{
	public function __invoke($path)
	{
		require_once $path;

		list($class, $comment) = self::parse_file($path);

		$options = self::resolve_options($comment);

		$update = new $class($options);

		$this->run_updates($update);
	}

	protected function run_updates($update)
	{
		$update_reflection = new \ReflectionClass($update);

		foreach ($update_reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method_reflection)
		{
			$method_name = $method_reflection->name;

			if (strpos($method_name, 'update_') !== 0)
			{
				continue;
			}

			try
			{
				$update->$method_name();
			}
			catch (AssertionFailed $e)
			{
				echo "Nothing to update: $method_name<br />";

				continue;
			}
		}
	}

	static private function parse_file($path)
	{
		$tokens = token_get_all(file_get_contents($path));
		$namespace = null;

		for ($i = 0, $j = count($tokens) ; $i < $j ; $i++)
		{
			$token = $tokens[$i];

			if (!is_array($token))
			{
				echo "just a string: $token<br />";

				continue;
			}

			list($token_id, $value, $line) = $token;

			switch ($token_id)
			{
				case \T_NAMESPACE:

					$namespace = self::parser_resolve_namespace($tokens, $i);

					break;

				case \T_CLASS:

					return array($namespace . '\\' . $tokens[$i + 2][1], $tokens[$i - 2][1]);
			}
		}
	}

	static private function parser_resolve_namespace($tokens, &$i)
	{
		$i += 2;
		$rc = '';

		for ($j = count($tokens) - $i ; $i < $j ; $i++)
		{
			$token = $tokens[$i];

			if ($token === ';')
			{
				break;
			}

			$rc .= $token[1];
		}

		return $rc;
	}

	static private function resolve_options($comment)
	{
		preg_match_all('#@([^\s]+)\s+([^\n]+)#', $comment, $matches);

		return array_combine($matches[1], $matches[2]);
	}
}

// , "TIMESTAMP NOT NULL AFTER `created_at`"