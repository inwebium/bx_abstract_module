## Установка
Для работы решения необходима установленная 1С-Битрикс: Управление сайтом редакции "Малый бизнес" или выше.
Скачать репозиторий в корень сайта с битриксом.

### 1й вариант
Выполнить на сервере команду
```
cd /documentRoot_сайта_с_битриксом/
php -d short_open_tag=On local/php_interface/shell/autoinstall.php -m="inwebium.module" -a="deploy" -u=1
```

### 2й вариант
Перейти в административном разделе сайта в Marketplace->Установленные решения. Нажать "Установить" для модуля inwebium.module.

## Запросы

Получение товара по Id
```
/example/index.php?method=getById&id=<ID_Товара>
```

Получение товаров по вхождению строки в имени
```
/example/index.php?method=getByName&name=<Строка>
```

Получение товаров по производителю
```
/example/index.php?method=getByManufacturer&manufacturer=<Код_производителя>
```
Например:
```
/example/index.php?method=getByManufacturer&manufacturer=manufacturer_c
```

Получение товаров по разделу
```
/example/index.php?method=getBySection&section=<ID_Раздела>
```

Получение товаров по по разделу включая товары из вложенных разделов
```
/example/index.php?method=getBySection&section=<ID_Раздела>&includeSubsections
```