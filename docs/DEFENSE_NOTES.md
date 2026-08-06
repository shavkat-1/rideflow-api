# RideFlow API — заметки для защиты

## 1. Назначение проекта

RideFlow — учебный backend API для управления поездками.

Проект демонстрирует:

* Laravel REST API;
* Laravel Passport и Bearer Token;
* роли пассажира, водителя и администратора;
* Policies и middleware;
* MySQL и транзакции;
* Redis Cache;
* Redis Queue и Laravel Jobs;
* Laravel Horizon;
* Laravel Octane и Swoole;
* Kafka producer и topic;
* Swagger / OpenAPI;
* Feature-тесты;
* dependency injection и интерфейсы.

---

# 2. Основные роли

## Passenger

Пассажир может:

* зарегистрироваться;
* авторизоваться;
* создать поездку;
* получить список только своих поездок;
* посмотреть свою поездку;
* изменить свою поездку в статусе `pending`;
* удалить свою поездку в статусе `pending`.

Пассажир не передаёт `passenger_id` при создании поездки.

ID пассажира определяется по Bearer Token:

```php
$data['passenger_id'] = $request->user()->id;
```

Это защищает API от создания поездки от имени другого пользователя.

## Driver

Водитель может:

* авторизоваться;
* принять свободную поездку;
* получить список назначенных ему поездок;
* посмотреть назначенную ему поездку.

Водитель не может создавать поездки как пассажир.

## Admin

Администратор может:

* получить список всех поездок;
* посмотреть любую поездку.

Администратор создаётся вручную через Tinker или Seeder.

---

# 3. HTTP flow Laravel

Типичный запрос проходит такой путь:

```text
HTTP Request
    ↓
Route
    ↓
Middleware
    ↓
Passport authentication
    ↓
Form Request validation
    ↓
Controller
    ↓
Service
    ↓
Repository
    ↓
Eloquent / MySQL
    ↓
Resource / JSON Response
```

Пример создания поездки:

```text
POST /api/trips
    ↓
auth:api
    ↓
TripStoreRequest
    ↓
TripController::store()
    ↓
TripService::createTrip()
    ↓
TripRepository::create()
    ↓
MySQL
```

---

# 4. Почему используется Service

Controller не должен содержать всю бизнес-логику.

Controller:

* принимает HTTP-запрос;
* получает валидированные данные;
* вызывает Service;
* возвращает HTTP-ответ.

Service:

* реализует бизнес-операцию;
* работает с Repository;
* очищает Cache;
* отправляет Job;
* публикует Kafka-событие.

Это уменьшает связанность кода и упрощает тестирование.

---

# 5. Почему используется Repository

`TripService` зависит не от конкретного класса Repository, а от интерфейса:

```php
TripRepositoryInterface
```

Схема:

```text
TripService
    ↓
TripRepositoryInterface
    ↓
TripRepository
    ↓
Eloquent
```

Преимущества:

* Service не знает детали хранения данных;
* реализацию можно заменить;
* код легче тестировать;
* зависимости регистрируются через Service Container.

---

# 6. Dependency Injection

Зависимости передаются через конструктор:

```php
public function __construct(
    private readonly TripRepositoryInterface $tripRepository,
    private readonly TripEventPublisherInterface $tripEventPublisher
) {}
```

Laravel Service Container автоматически создаёт нужные реализации.

Binding находится в `AppServiceProvider`.

Пример:

```php
$this->app->bind(
    TripEventPublisherInterface::class,
    KafkaTripEventPublisher::class
);
```

---

# 7. Passport и авторизация

Laravel Passport используется для OAuth2 access tokens.

После логина API возвращает:

```json
{
  "access_token": "...",
  "token_type": "Bearer"
}
```

Защищённые маршруты используют:

```php
->middleware('auth:api')
```

Swagger передаёт токен через кнопку `Authorize`.

---

# 8. Policy

`TripPolicy` определяет, имеет ли пользователь доступ к конкретной поездке.

Примеры:

```text
Пассажир-владелец → 200
Чужой пассажир → 403
Назначенный водитель → 200
Другой водитель → 403
Администратор → 200
Гость → 401
```

Policy отвечает за доступ к объекту, а middleware — за общий доступ по роли.

---

# 9. Route Model Binding

Маршрут:

```php
Route::get('/trips/{trip}', [TripController::class, 'show']);
```

Контроллер:

```php
public function show(Trip $trip): TripResource
```

Laravel автоматически выполняет поиск:

```php
Trip::findOrFail($id);
```

Если поездка отсутствует, возвращается `404`.

Название `{trip}` должно совпадать с аргументом `Trip $trip`.

---

# 10. MySQL и транзакции

MySQL используется как постоянное хранилище.

Транзакция используется при принятии поездки:

```text
BEGIN
    ↓
SELECT ... FOR UPDATE
    ↓
проверка status = pending
    ↓
назначение driver_id
    ↓
status = accepted
COMMIT
```

`lockForUpdate` защищает от ситуации, когда два водителя одновременно пытаются принять одну поездку.

---

# 11. Redis Cache

Redis используется для кеширования списков поездок.

Используется подход cache-aside:

```text
Запрос
    ↓
Проверка Redis
    ↓
Есть значение?
    ├── Да → вернуть из Cache
    └── Нет → запросить MySQL
                ↓
             сохранить в Redis
                ↓
             вернуть результат
```

Пример:

```php
Cache::remember(
    $cacheKey,
    300,
    fn () => $repository->getData()
);
```

Ключи зависят от роли и пользователя:

```text
trips:list:passenger:27
trips:list:driver:26
trips:list:admin:1
```

После изменения поездки соответствующие ключи удаляются.

Это называется cache invalidation.

---

# 12. Redis Queue и Job

После создания поездки отправляется:

```php
SendTripConfirmationJob
```

Job запускается с задержкой:

```php
->delay(now()->addSeconds(30))
```

Flow:

```text
Trip создан
    ↓
Job записана в Redis
    ↓
delayed queue
    ↓
Horizon worker
    ↓
handle()
    ↓
confirmation_sent_at заполнено
```

Job имеет:

```text
tries = 3
backoff = 60
timeout = 30
```

## Idempotency

Перед отправкой подтверждения Job проверяет:

```php
confirmation_sent_at
```

Если подтверждение уже отправлено, повторная Job ничего не делает.

Это защищает от повторной обработки.

---

# 13. Horizon

Horizon управляет Redis Queue workers.

Он показывает:

* pending jobs;
* completed jobs;
* failed jobs;
* runtime;
* throughput;
* retries;
* tags;
* consumer workload.

Запуск:

```bash
php artisan horizon
```

Dashboard:

```text
http://localhost:8000/horizon
```

Octane и Horizon выполняют разные задачи:

```text
Octane → HTTP-запросы
Horizon → фоновые Jobs
```

---

# 14. Failed Jobs и Retry

Для практики создавалась учебная падающая Job.

Она показала:

```text
Attempt 1
    ↓
Backoff
    ↓
Attempt 2
    ↓
Backoff
    ↓
Attempt 3
    ↓
failed_jobs
```

Просмотр:

```bash
php artisan queue:failed
```

Повторный запуск:

```bash
php artisan queue:retry UUID
```

Удаление:

```bash
php artisan queue:forget UUID
```

---

# 15. Octane и Swoole

Приложение запускается через:

```bash
php artisan octane:start \
    --server=swoole \
    --host=127.0.0.1 \
    --port=8000
```

Проверка:

```bash
php artisan octane:status
```

Octane использует long-lived workers.

Laravel загружается один раз, после чего worker обслуживает много запросов.

```text
Laravel bootstrap
       ↓
Long-lived worker
       ↓
Request 1
Request 2
Request 3
```

## Риск static state

Демонстрационный endpoint:

```text
GET /api/demo/octane-counter
```

До reload:

```json
{"count":1}
{"count":2}
{"count":3}
```

После:

```bash
php artisan octane:reload
```

Счётчик снова:

```json
{"count":1}
```

Вывод:

* static-состояние живёт между запросами;
* нельзя хранить там текущего пользователя;
* нельзя хранить Bearer Token;
* нельзя хранить Request;
* нельзя хранить данные конкретной поездки.

Stateless-сервисы безопаснее для long-lived workers.

---

# 16. Kafka

Kafka используется для публикации событий между системами.

При создании поездки публикуется:

```text
trip.created
```

Пример события:

```json
{
  "event": "trip.created",
  "trip_id": 40,
  "passenger_id": 27,
  "status": "pending",
  "occurred_at": "2026-08-05T10:33:57.000000Z"
}
```

Flow:

```text
TripService
    ↓
TripCreatedEventData
    ↓
TripEventPublisherInterface
    ↓
KafkaTripEventPublisher
    ↓
Kafka topic: trip-events
    ↓
Consumer
```

---

# 17. Почему Queue и Kafka используются вместе

Redis Queue используется для выполнения конкретной фоновой операции:

```text
SendTripConfirmationJob
```

Kafka используется для публикации события, которое могут читать разные системы:

```text
trip.created
├── Notification service
├── Analytics service
├── Search service
└── Fraud service
```

Краткий ответ:

```text
Queue → выполнить задачу
Kafka → распространить событие
```

---

# 18. Kafka topic, partition и offset

Topic:

```text
trip-events
```

Partition:

```text
Partition 0
```

Сообщения внутри partition имеют offset:

```text
offset 0 → trip.created #40
offset 1 → trip.created #41
offset 2 → trip.created #42
```

Offset — позиция сообщения внутри partition.

Kafka не удаляет событие сразу после чтения.

---

# 19. Consumer Group

Использовалась группа:

```text
rideflow-notifications
```

Kafka запоминает offset этой группы.

После перезапуска consumer старые обработанные события повторно не приходят.

Проверка:

```bash
docker exec rideflow-kafka \
    /opt/kafka/bin/kafka-consumer-groups.sh \
    --bootstrap-server localhost:29092 \
    --describe \
    --group rideflow-notifications
```

Основные поля:

```text
CURRENT-OFFSET
LOG-END-OFFSET
LAG
```

Если:

```text
LAG = 0
```

значит группа обработала все доступные события.

---

# 20. Kafka UI

Kafka UI доступен:

```text
http://localhost:8085
```

В интерфейсе можно показать:

* broker;
* topic `trip-events`;
* partition;
* messages;
* message key;
* offset;
* consumer groups;
* consumer lag.

Message key равен `trip_id`.

Это помогает сохранять события одной поездки в одной partition при масштабировании.

---

# 21. Почему используется TripEventPublisherInterface

`TripService` не зависит от библиотеки Kafka напрямую.

Он знает только контракт:

```php
TripEventPublisherInterface
```

В обычном приложении:

```text
TripEventPublisherInterface
    ↓
KafkaTripEventPublisher
```

В тестах:

```text
TripEventPublisherInterface
    ↓
FakeTripEventPublisher
```

Это позволяет тестировать создание события без Kafka broker.

---

# 22. Fake publisher в тестах

В `TripApiTest` интерфейс заменяется:

```php
TripEventPublisherInterface
→ FakeTripEventPublisher
```

Тест проверяет:

* событие опубликовано один раз;
* `tripId` совпадает;
* `passengerId` совпадает;
* статус равен `pending`.

Kafka-контейнер был остановлен, но тесты всё равно прошли:

```text
30 tests passed
94 assertions
```

Это доказывает изоляцию тестов от инфраструктуры.

---

# 23. Swagger

Swagger UI:

```text
http://localhost:8000/api/documentation
```

Через Swagger можно показать:

1. регистрацию пассажира;
2. логин;
3. Authorize;
4. создание поездки;
5. список своих поездок;
6. просмотр поездки;
7. запрет просмотра чужой поездки;
8. принятие поездки водителем.

---

# 24. Тестирование

Команды:

```bash
./vendor/bin/pint --test
php artisan test
```

Текущий результат:

```text
37 tests passed
130 assertions
```

Feature-тесты проверяют:

* регистрацию;
* логин;
* Passport authentication;
* роли;
* создание поездки;
* валидацию;
* просмотр;
* Policy;
* списки по ролям;
* принятие поездки;
* обновление;
* удаление;
* Queue Job;
* Kafka event через Fake publisher.

---

# 25. Запуск проекта для демонстрации

## Kafka и Kafka UI

```bash
docker compose -f compose.kafka.yaml up -d
```

Проверка:

```bash
docker compose -f compose.kafka.yaml ps
```

## Octane

```bash
php artisan octane:start \
    --server=swoole \
    --host=127.0.0.1 \
    --port=8000
```

## Horizon

В отдельном терминале:

```bash
php artisan horizon
```

## Swagger

```text
http://localhost:8000/api/documentation
```

## Horizon

```text
http://localhost:8000/horizon
```

## Kafka UI

```text
http://localhost:8085
```

---

# 26. Порядок демонстрации на защите

1. Кратко рассказать назначение RideFlow.
2. Показать архитектуру проекта.
3. Открыть Swagger.
4. Авторизоваться пассажиром.
5. Создать поездку.
6. Показать запись в MySQL.
7. Показать `trip.created` в Kafka UI.
8. Показать delayed Job в Horizon.
9. Через 30 секунд показать Completed Job.
10. Показать `confirmation_sent_at`.
11. Показать ограничения ролей.
12. Показать тесты.
13. Показать Octane counter.
14. Выполнить `octane:reload`.
15. Объяснить long-lived workers.

---

# 27. Типовые вопросы

## Почему Service, а не вся логика в Controller?

Чтобы отделить HTTP-слой от бизнес-логики и упростить тестирование.

## Почему Repository Interface?

Чтобы Service не зависел от Eloquent напрямую и можно было заменить реализацию.

## Почему Passport?

Для OAuth2 access tokens и защищённых API endpoints.

## Почему Policy?

Для проверки доступа конкретного пользователя к конкретной поездке.

## Почему Redis?

Для быстрого временного хранения Cache и Queue.

## Чем Cache отличается от MySQL?

MySQL — постоянные данные.

Cache — временная копия данных для ускорения чтения.

## Что такое cache invalidation?

Удаление устаревшего кеша после изменения данных.

## Почему Job должна быть idempotent?

Потому что Queue может выполнить задачу повторно.

## Чем Horizon отличается от queue:work?

`queue:work` запускает worker.

Horizon управляет workers и даёт мониторинг Redis Queue.

## Почему Octane опасен со static?

Потому что worker живёт между запросами и static-состояние сохраняется.

## Почему Kafka, если есть Redis Queue?

Queue выполняет конкретную задачу.

Kafka публикует событие для нескольких независимых consumers.

## Что такое consumer lag?

Количество событий, которые consumer group ещё не обработала.

## Почему Kafka скрыта за интерфейсом?

Чтобы бизнес-код не зависел от конкретного Kafka-клиента и тесты могли использовать Fake.

---

# 28. Что не нужно говорить

Не нужно утверждать, что проект является production-ready системой такси.

Правильная формулировка:

> RideFlow — учебный backend-проект, созданный для практического изучения Laravel, REST API, Passport, MySQL, Redis, queues, Horizon, Octane, Swoole, Kafka, Swagger и тестирования.

---

# 29. Краткая презентация проекта

> RideFlow — учебный Laravel API для управления поездками. Пользователи авторизуются через Passport и имеют роли passenger, driver и admin. Доступ к поездкам контролируется middleware и Policy. Данные хранятся в MySQL, списки кешируются в Redis. После создания поездки запускается delayed Job, которую обрабатывает Horizon. Одновременно публикуется событие `trip.created` в Kafka. HTTP-запросы обслуживаются через Octane и Swoole. API документирован в Swagger и покрыт Feature-тестами.

---

# 30. Laravel Kafka Consumer

Kafka consumer реализован отдельной Artisan-командой:

```bash
php artisan kafka:consume-trips