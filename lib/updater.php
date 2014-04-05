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

use ICanBoogie\Module;

class Updater
{
	static public function run(\ICanBoogie\Core $core)
	{
		$updater = new static;

		foreach ($core->modules->descriptors as $module_id => $descriptor)
		{
			$pathname = $descriptor[Module::T_PATH] . DIRECTORY_SEPARATOR . 'updates.php';

			if (!file_exists($pathname))
			{
				continue;
			}

			$updater($pathname);
		}
	}

	public function __invoke($path)
	{
		require_once $path;

		$update_constructor_list = self::parse_file($path);

		foreach ($update_constructor_list as $update_constructor)
		{
			list($class, $annotation) = $update_constructor;

			$options = self::resolve_options($annotation);

			$update = new $class($options);

			$this->run_updates($update);
		}
	}

	protected function run_updates(Update $update)
	{
		echo "Scanning module: {$update->module->target}\n";

		$update_reflection = new \ReflectionClass($update);

		if ($update_reflection->hasMethod('before'))
		{
			$update->before();
		}

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
				echo "Nothing to update: $method_name\n";

				continue;
			}
		}
	}

	/**
	 * Parses the specified file and returns update constructors.
	 *
	 * @param string $path Path to the PHP script to parse.
	 *
	 * @return array An array of update constructor. Each update constructor has the following
	 * layout : [ 0 => $class_name, 1 => $annotation ]
	 */
	static private function parse_file($path)
	{
		$tokens = token_get_all(file_get_contents($path));
		$namespace = null;
		$update_constructors = [];

		for ($i = 0, $j = count($tokens) ; $i < $j ; $i++)
		{
			$token = $tokens[$i];

			if (!is_array($token))
			{
				continue;
			}

			list($token_id, $value, $line) = $token;

			switch ($token_id)
			{
				case \T_NAMESPACE:

					$namespace = self::parser_resolve_namespace($tokens, $i);

					break;

				case \T_CLASS:

					$class_name_token = $tokens[$i + 2];
					$class_name = $class_name_token[1];

					$class_annotation_token = $tokens[$i - 2];

					if (!is_array($class_annotation_token))
					{
						echo "Missing annotation for class $class_name\n";

						continue;
					}

					$class_annotation = $class_annotation_token[1];

					$update_constructors[] = array($namespace . '\\' . $class_name, $class_annotation);
			}
		}

		sort($update_constructors);

		return $update_constructors;
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