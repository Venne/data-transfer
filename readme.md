# Venne:DataTransfer [![Build Status](https://secure.travis-ci.org/Venne/data-transfer.png)](http://travis-ci.org/Venne/data-transfer)

Use `data transfer object` pattern in templates.

**Benefits:**

- Simple read-only objects in templates.
- Cacheable objects and collections with all features from `Nette/Caching`.
- Serializable objects nad collections.
- It works right out of the box with `Kdyby/Doctrine`.


## Installation

The best way to install Venne/DataTransfer is using Composer:

```sh
composer require venne/data-transfer:@dev
```


## Activation

```yaml
extensions:
	dataTransfer: Venne\DataTransfer\DI\DataTransferExtension
```


### Configuration

```yaml
dataTransfer:
	driver: Venne\Bridges\Kdyby\Doctrine\DataTransfer\EntityDriver
	cache:
		namespace: dataTransfer
```


## Usage

### Define `DTO`

```php
/**
 * @property-read integer $id
 * @property-read string $name
 */
class ArticleDTO extends \Venne\DataTransfer\DataTransferObject {}
```


### Basic use

```php
$article = new ArticleDTO(array(
	'id' => 1,
	'name' => 'fooName',
));

// Lazy mode
$article = new ArticleDTO(function () {
	return array(
		'id' => 1,
		'name' => 'fooName',
	);
});

$article = unserialize(serialize($article)); // it works

echo $article->id;   // 1
echo $article->name; // fooName
echo $article->foo; // throw exception
```


### Iterator

```php
$articles = DataTransferObjectIterator(ArticleDTO::class, array(
	array(
		'id' => 1,
		'name' => 'fooName',
	),
	array(
		'id' => 2,
		'name' => 'barName',
	),
));

// Lazy mode
$articles = DataTransferObjectIterator(ArticleDTO::class, function () {
	return array(
				array(
					'id' => 1,
					'name' => 'fooName',
				),
				array(
					'id' => 2,
					'name' => 'barName',
				),
	);
});

$articles = unserialize(serialize($articles)); // it works

echo count($articles);

foreach ($articles as $article) {
	echo $article->id;
	echo $article->name;
}
```


### `DataTransferManager`

```php
$dataTransferManager = $container->getByType('Venne\DataTransfer\DataTransferManager');
// $dataTransferManager = new DataTransferManager($driver, $cacheStorage);
```

```php
$article = $dataTransferManager
	->createQuery(ArticleDTO::class, function () {
		return $this->articleRepository->find($this->id);
	})
	->enableCache($key, $dependencies)
	->fetch();
```

```php
$articles = $dataTransferManager
	->createQuery(ArticleDTO::class, function () {
		return $this->articleRepository->findBy(array(
			'parent' => $this->id,
		));
	})
	->enableCache($key, $dependencies)
	->fetchAll();
```


## Cooperation with `kdyby/doctrine`

### Installation

```yaml
extensions:
	...
	kdybyDataTransfer: Venne\Bridges\Kdyby\Doctrine\DataTransfer\DI\DataTransferExtension
```


### Usage

```php
$this->template->article = $dataTransferManager
	->createQuery(ArticleDTO::class, function () {
		return $this->articleDao->find($this->id);
	})
	->enableCache($key, $dependencies)
	->fetch();
```
