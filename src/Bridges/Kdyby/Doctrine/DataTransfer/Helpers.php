<?php

namespace Venne\Bridges\Kdyby\Doctrine\DataTransfer;

class Helpers
{

	/**
	 * @param $class
	 * @param $primaryKey
	 * @return string
	 */
	public static function formatCacheTag($class, $primaryKey)
	{
		return sprintf('%s#%s', $class, $primaryKey);
	}

}
