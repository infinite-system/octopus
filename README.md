![octopus-logo-medium](https://user-images.githubusercontent.com/150185/221381948-653918e0-615a-48fb-99a6-aa3b70a75e30.jpg)

## Octopus - Infinite Connectivity System

Octopus is Laravel Eloquent Extension for Infinite Polymorphic Relationship Interface.

Octopus is accessible, powerful, and provides connectivity required for large, robust applications.

## What is Octopus?

Octopus allows any Model to connect to any other model on the fly. The core of Octopus is a unified partitioned table called Tags, that contains all the connections between tables, allowing a pure database design, where tables remain pure and decoupled from each other, yet are able to communicate and connect to each other seamlessly.

### Provided Functions
Use within your Models:
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