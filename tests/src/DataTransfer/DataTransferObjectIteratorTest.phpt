<?php

namespace VenneTests\DataTransfer;

use Tester\Assert;
use Venne\DataTransfer\DataTransferObjectIterator;

require __DIR__ . '/../bootstrap.php';

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class DataTransferObjectIteratorTest extends \Tester\TestCase
{

	public function testConstructor()
	{
		Assert::exception(function () {
			new DataTransferObjectIterator(FooDataTransfer::getClassName(), 'foo');
		}, 'Nette\InvalidArgumentException', 'Rows must be array of values or callable array source, string given.');
		Assert::exception(function () {
			$dataTransferObjectIterator = new DataTransferObjectIterator(FooDataTransfer::getClassName(), function () {
				return 'string';
			});
			$dataTransferObjectIterator->toArray();
		}, 'Nette\InvalidStateException', 'Rows must be array of values or callable array source, string given.');
		Assert::type(DataTransferObjectIterator::getClassName(), new DataTransferObjectIterator(FooDataTransfer::getClassName(), array(1, 2)));
		Assert::type(DataTransferObjectIterator::getClassName(), new DataTransferObjectIterator(FooDataTransfer::getClassName(), function () {
			return array(1, 2);
		}));
	}

	public function testIterator()
	{
		$items = array(
			array(
				'id' => 1,
				'name' => 'foo',
				'amount' => 1.1,
			),
			array(
				'id' => 2,
				'name' => 'bar',
				'amount' => 2.2,
			),
		);

		$dataTransferIterator = new DataTransferObjectIterator(FooDataTransfer::getClassName(), $items);
		Assert::type('\Traversable', $dataTransferIterator);
		Assert::count(2, $dataTransferIterator);
		foreach ($dataTransferIterator as $key => $dataTransferObject) {
			Assert::same($items[$key], $dataTransferObject->toArray());
		}
	}

}

/**
 * @property-read integer $id
 * @property-read string $name
 * @property-read float|null $amount
 */
class FooDataTransfer extends \Venne\DataTransfer\DataTransferObject
{

}

$testCase = new DataTransferObjectIteratorTest;
$testCase->run();
