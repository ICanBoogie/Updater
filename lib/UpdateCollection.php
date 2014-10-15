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

class UpdateCollection implements \IteratorAggregate
{
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

					$update_constructors[] = new UpdateDescriptor($namespace . '\\' . $class_name, $class_annotation, $path, $line);
			}
		}

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

	protected $descriptors;

	public function __construct(array $files)
	{
		$this->descriptors = $this->collect_descriptors($files);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->descriptors);
	}

	protected function collect_descriptors($files)
	{
		$descriptors = [];

		foreach ($files as $file)
		{
			$descriptors = array_merge($descriptors, self::parse_file($file));
		}

		\ICanBoogie\stable_sort($descriptors, function($v) {

			return $v->normalized_id;

		});

		return $descriptors;
	}
}