# Beauty
Beauty for Simple Php 7 framework

 > support that database query is distributed to master or slave automatically.

## feature
 1. 支持数据库query根据不同的sql分别使用master和slave数据库。
 2. memcache和redis采用一致性哈希访问不同的实例。
 3. 返回数据全部采用text/json格式。

## preinstall
at first, install composer.

## install
run composer update to generate vendors.
```bash
$ composer create-project --prefer-dist beauty/beauty kimi
$ composer update
```

## Usage
You may quickly test this using the built-in PHP server:
```bash
$ php -S localhost:8000 -t public/
```

Going to http://localhost:8000 will now display "Hello, world".

## Tests

To execute the test suite, you'll need phpunit.

```bash
$ phpunit
```

## License

The Beauty Framework is licensed under the MIT license.