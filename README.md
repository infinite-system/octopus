![octopus-logo-medium](https://user-images.githubusercontent.com/150185/221432329-fbc8a6ac-45a9-44ca-ad5d-3d3eb9c7a7d1.jpg)

## Octopus - Infinite Connectivity System

**Octopus** is a Laravel Eloquent Extension for Infinite Polymorphic Relationship Interface.

Octopus is accessible, powerful, and provides connectivity required for large, robust applications.

## What is Octopus?

Octopus allows any Model to connect to any other Laravel Model on the fly. The core of Octopus is a unified partitioned table called `tags`, that contains all the connections between tables, allowing a pure database design, where tables remain pure and decoupled from each other, yet they are able to communicate and connect to each other seamlessly via a partitioned and indexed "matrix" table.

### Provided Functions
Use within your Laravel Models:
```php
// Simple direct Model connections

// To one
$this->tagOne(TargetModel): HasOneThrough
// To one inversed
$this->tagOneInverse(SourceModel): HasOneThrough

// To many
$this->tagMany(TargetModel): HasManyThrough
// To many inversed
$this->tagManyInverse(SourceModel): HasManyThrough

// Neuron mapping of connections

// To one through a Neuron categorized connection
$this->tagOneThrough(Category, [Category Ids], TargetModel): HasOne
// To one through an inversed Neuron categorized connection
$this->tagOneThroughInverse(Category, [Category Ids], SourceModel): HasOne

// To many through a Neuron categorized connection
$this->tagManyThrough(Category, [Category Ids], TargetModel): HasMany
// To many through an inversed Neuron categorized connection
$this->tagManyThroughInverse(Category, [Category Ids], SourceModel): HasMany
```

### Install
```
git clone https://github.com/infinite-system/octopus.git

cd octopus

composer install
```
### Run Tests
```shell
vendor/bin/phpunit --testdox
```

Octopus is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
