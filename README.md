# Задание
Написать упрощенную систему выполнения заказов.
Есть заказчики, исполнители и система. Заказчик публикует заказ, указывает его стоимость.

Исполнитель видит ленту заказов, доступных для исполнения.
Исполнитель кликает «выполнить» на заказе, исполнителю на счет зачисляется сумма за вычетом комиссии системы.

У одного заказа может быть только один исполнитель. Если заказ выполнен, он исчезает из ленты. 

Техника:
PHP (без ООП), mysql, фронтэнд сторона на усмотрение. Исходники должны быть на гитхабе и отражать процесс разработки.

Код максимально приближенный к боевому, предусмотреть вариант, когда каждая таблица располагается в отдельной базе данных (транзакции и JOIN - не вариант). 

Клиентская сторона — SPA

Отправка результата: 
Ccылка на гитхаб или пригласить пользователя seriousbackend в закрытый репозиторий и развёрнутое демо(обязательно). 

Критерии оценки:  
— устойчивость кода - готовность к нагрузке, отказам оборудования, внешнего вмешательства
— в меньшей степени будет оценено удобство для пользователя и аккуратность оформления фронтэнда


# Решение

В проекте используется три таблицы:

```
orders — для хранения заказов
transactions — для хранения транзакций
users — для хранения пользователей
```

* В качестве системы оркестрации выбрал docker-compose - пожалуй, stateless-контейнеры это сейчас стандарт де-факто, их удобно разворачивать и они взаимозаменяемы. 

* Посчитал логичным вынести обработку транзакций в демон `php web/index.php transactions`, который запускается кроном - во-первых, тогда не задействуется 
PHP-шный воркер, а во-вторых, в случае необходимости это можно масштабировать — запускать несколько инстансов обработчиков одновременно.

* В качестве кеша использовал Memcache, раз уж у вас так заведено :)

* Есть один момент, связанный с хранением пары логин-пароль в кеше - алгоритм проверки `password_hash` довольно медлительный (и это понятно),
поэтому, для более быстрой авторизации сохраняю `login . sha1( password . salt )` в кеш, однако, это потенциальная уязвимость. 
В действительно защищенной системе я бы такое делать не стал, но решил, что для демо сойдет.

* В качестве фреймворка для тестирования использовал `codeception` (там хоть и есть ООП, но насколько я понял из нашей беседы это не проблема, когда дело касается тестов). 

* В качестве инструмента для нагрузочного тестирования я выбрал Gatling, потому что фреймворк очень гибкий, и позволил описать сценарии очень емко и гибко. (с результатами тестирования можно ознакомиться в result/basicsimulation-1530018633232)

Конфигурация «сервера»:
```
$ cat /proc/cpuinfo 
vendor_id       : GenuineIntel
model name      : Intel(R) Core(TM) i5-6200U CPU @ 2.30GHz
stepping        : 3
cpu MHz         : 499.999
cache size      : 3072 KB

cat /proc/meminfo 
MemTotal:        7969112 kB
```
* По фронтенду - использовал angular 1 и bootstrap. Конечно, возможно фреймворк устарел (хотя LTS еще действует), но он мне нравится своей компактостью, думаю, он здесь неплохо вписался.      

* Деплой:

```
docker-compose up -d # конфиг совместим с docker-compose v1.21.2
docker exec -it vk-test_api_1 php ./web/index.php migrate
```
 
* Хранение денег в базе, конечно, целочисленное

* Существует так же потенциальная уязвимость с токенами - если вдруг украсть токен у пользователя, и каким-то образом подменить IP на IP жертвы (например, ходить через тот же NAT) - это может скомпрометировать сессию.
На балансере использую HSTS так что сессию перехватить *условно* невозможно. 

* CSRF-атакам сервис тоже не подвержен - даже если в куке есть токен - его все равно необходимо указывать в POST-теле запроса.

* Для предотвращения SQL-инъекций используются prepared statements

* Angular позволяет защититься от XSS-атак в переменных, а директива bind-html не используется. 

* Для защиты от неверного ввода написал примитивный валидатор по каждому типу значений. Можно было бы использовать JSON-схему, однако, посчитал, что это в данном случае лишнее.

* При создании заказа учитывается сумма средств, присутствующая на счету у заказчика. Если эта сумма меньше стоимости заказа, он не создается.

* При обработке запросов order.create/order.assign происходит проверка типа учетной записи пользователя. Если фрилансер — может выполнять, но не может создавать, и наоборот.  
  
Обработка транзакции происходит по такому сценарию

1. Заказчик создает заказ 
2. Происходит генерация transaction_id (TID) и обновление поля transaction_id у заказа
3. Создаем транзакцию с TID 
4. (В случае, если у заказчика достаточно средств) Вычитаем из баланса заказчика сумму заказа, и помещаем ее в поле hold, меняем поле last_transaction_id = TID
5. Меняем статус транзакции на hold
6. Меняем last_transaction_id у пользователя-фрилансера, в поле hold записываем вознаграждение за вычетом комиссии
7. Меняем статус тразнакции на sent
8. Снимаем холд, вначале с заказчика, затем с фрилансера
9. Меняем статус транзакции на done
10. Меняем статус заказа на closed