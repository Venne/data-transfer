<?php

namespace VenneTests\Bridges\Kdyby\Doctrine\DataTransfer;

use Tester\Assert;
use Venne\Bridges\Kdyby\Doctrine\DataTransfer\Helpers;

require __DIR__ . '/../../../../bootstrap.php';

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class HelpersTest extends \Tester\TestCase
{

	public function testFormatCacheTag()
	{
		Assert::same('Foo\\BarEntity#1', Helpers::formatCacheTag('Foo\\BarEntity', 1));
	}

}

$testCase = new HelpersTest();
$testCase->run();
