<?php

namespace VenneTests\DataTransfer;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class DataTransferObjectTest extends \Tester\TestCase
{

	public function testConstructor()
	{
		Assert::exception(function () {
			new FooDataTransfer('foo');
		}, 'Nette\InvalidArgumentException', 'Values must be array or callable array source, string given.');
		Assert::exception(function () {
			$fooDto = new FooDataTransfer(function () {
				return 'string';
			});
			$fooDto->toArray();
		}, 'Nette\InvalidStateException', 'Values must be array or callable array source, string given.');
		Assert::type(FooDataTransfer::getClassName(), new FooDataTransfer(array(1, 2)));
		Assert::type(FooDataTransfer::getClassName(), new FooDataTransfer(function () {
			return array(1, 2);
		}));
	}

	public function testGetKeys()
	{
		$keys = array('id', 'name', 'amount');
		sort($keys);

		$fooKeys = FooDataTransfer::getKeys();
		sort($fooKeys);

		Assert::equal($keys, $fooKeys);
	}

	public function testHasKey()
	{
		Assert::true(FooDataTransfer::hasKey('id'));
		Assert::true(FooDataTransfer::hasKey('name'));
		Assert::true(FooDataTransfer::hasKey('amount'));
		Assert::false(FooDataTransfer::hasKey('id2'));
	}

	public function testGetValue()
	{
		$fooDto = new FooDataTransfer(array(
			'id' => 1,
			'name' => 'foo',
		));

		$barDto = new BarDataTransfer(array(
			'id' => 1,
			'name' => 'foo',
			'amount' => 10.0,
		));

		Assert::same(1, $fooDto->id);
		Assert::same('foo', $fooDto->name);
		Assert::null($fooDto->amount);
		Assert::exception(function () use ($fooDto) {
				Assert::same(1, $fooDto->bad);
			},
			'Nette\MemberAccessException',
			'Cannot read property VenneTests\DataTransfer\FooDataTransfer::$bad.'
		);

		Assert::same(20.0, $barDto->amountPlusTen);
		Assert::same(15.0, $barDto->amountPlusTenMinusFive);
	}

	public function testToArray()
	{
		$items = array(
			'id' => 1,
			'name' => 'foo',
			'amount' => 1.1,
		);
		ksort($items);

		$fooDto = new FooDataTransfer($items);
		$fooValues = $fooDto->toArray();
		ksort($fooValues);
		Assert::same($items, $fooValues);

		$fooDto = new FooDataTransfer(function () use ($items) {
			return $items;
		});
		$fooValues = $fooDto->toArray();
		ksort($fooValues);
		Assert::same($items, $fooValues);
	}

	public function testSerialization()
	{
		$items = array(
			'id' => 1,
			'name' => 'foo',
			'amount' => 1.1,
		);

		$fooDto = new FooDataTransfer($items);
		$fooDto2 = unserialize(serialize($fooDto));

		Assert::same($fooDto->toArray(), $fooDto2->toArray());
	}

}

/**
 * @property-read integer $id
 * @property-read string $name
 * @property float|null $amount
 */
class FooDataTransfer extends \Venne\DataTransfer\DataTransferObject
{

}

/**
 * @property-read integer $id
 * @property-read string $name
 * @property float|null $amount
 * @property float $amountPlusTen
 * @property float $amountPlusTenMinusFive
 */
class BarDataTransfer extends \Venne\DataTransfer\DataTransferObject
{

	protected function getAmountPlusTen()
	{
		return $this->getValue('amount') + 10;
	}

	protected function getAmountPlusTenMinusFive()
	{
		return $this->getValue('amountPlusTen') - 5;
	}

}

$testCase = new DataTransferObjectTest;
$testCase->run();
