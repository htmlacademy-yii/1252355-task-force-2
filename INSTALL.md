### Установка проекта

1. После скачивания репозитория создайте пустую базу данных, впишите нужные значения в config/db.php;
2. В консоли, находясь в корневой папке проекта, выполните команды:
```
composer install
php yii migrate
php yii rbac/init
```

Все таблицы будут созданы и заполнены статическими и предварительно сгенерированными тестовыми данными.

Для отображения дат на русском языке подключите extension=php_intl.dll в php.ini
