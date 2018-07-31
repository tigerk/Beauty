# Beauty
Php 7 简单的框架，使用composer进行了包管理。
 > 支持数据库查询自动区分主库，从库查询。

## 特性
 1. 支持数据库query根据不同的sql分别使用master和slave数据库。
 2. memcache和redis采用一致性哈希访问不同的实例。
 3. 返回数据全部采用text/json格式。

## preinstall
在开始前需要安装composer。

## 安装
执行以下的composer命令就可以安装框架进行开发。
```bash
$ composer create-project --prefer-dist beauty/beauty kimi
$ composer update
```

## 使用
你可以使用php内置服务快速启动应用记性测试。
```bash
$ php -S localhost:8000 -t public/
```

访问 http://localhost:8000 将会显示 "Hello, world".

## 单元测试
使用了phpunit测试组件测试功能

```bash
$ phpunit
```

## 许可
MIT
