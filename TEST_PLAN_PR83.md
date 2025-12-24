# Test Plan for PR #83 - Calendar Component

## Feature Tests

### Calendar Event CRUD Operations

- Створення події ментором з валідними даними
- Редагування існуючої події власником
- Видалення події власником
- Неможливість створення події менті
- Неможливість редагування чужої події
- Неможливість видалення чужої події

### Calendar Views

- Відображення місячного календаря з подіями
- Відображення тижневого календаря з подіями
- Відображення денного календаря з подіями
- Перемикання між режимами перегляду (month/week/day)
- Навігація між місяцями/тижнями/днями
- Відображення подій у правильному timezone користувача

### Calendar Event Permissions

- Перевірка прав на створення події (тільки mentor)
- Перевірка прав на редагування події (тільки HOST)
- Перевірка прав на видалення події (тільки HOST)
- Перевірка прав на перегляд події (учасники)

### Calendar Event Slots

- Відображення доступних слотів для бронювання
- Блокування перекриваючихся часових слотів при створенні
- Блокування перекриваючихся часових слотів при редагуванні (з виключенням
  поточної події)

### Calendar Event User Relationships

- Прикріплення HOST при створенні події
- Збереження кольору події для користувача
- Синхронізація кольору при редагуванні
- Завантаження пов'язаних користувачів з подією

### Calendar Navigation

- Навігація до наступного місяця якщо є події
- Навігація до попереднього місяця якщо є події
- Блокування навігації якщо події відсутні
- Скрол до конкретної дати

## Unit Tests

### CalendarEvent Model

- Обчислення duration як computed attribute
- Перетворення дат до UTC при збереженні
- Relationship calendarEventUsers
- Casts для start_date_time та end_date_time

### User Model

- Relationship calendarEvents
- Relationship hostedCalendarEvents (фільтрація по HOST)
- Relationship participatingCalendarEvents (фільтрація по PARTICIPANT)

### CalendarEventPolicy

- create() дозволяє тільки mentors
- update() перевіряє роль HOST
- delete() перевіряє роль HOST
- view() перевіряє участь користувача
- Оптимізація через relationLoaded()

### Form Requests

#### StoreCalendarEventRequest

- Валідація title (required, string, max:255)
- Валідація fromDate (required, date, after_or_equal:today)
- Валідація toDate (required, date, after_or_equal:fromDate)
- Валідація fromTime (required, date_format:H:i)
- Валідація toTime (required, date_format:H:i, after:fromTime)
- Валідація colour (required, in:enum values)
- Валідація description (max:2000)
- Валідація type (required, in:enum values)
- Кастомна валідація перекриття часових слотів

#### EditCalendarEventRequest

- Всі правила з StoreCalendarEventRequest
- Виключення поточної події при перевірці слотів

### Services

#### DailyCalendarEventsService

- Підготовка конфігурації дат для денного перегляду
- Отримання подій для конкретного дня
- Конвертація timezone (UTC -> user timezone)
- Побудова календарного view

#### WeeklyCalendarEventsService

- Підготовка конфігурації дат для тижневого перегляду
- Отримання подій для тижня
- Конвертація timezone
- Побудова календарного view
- Групування подій по днях тижня

#### MonthCalendarEventsService

- Підготовка конфігурації дат для місячного перегляду
- Отримання подій для місяця
- Форматування подій для місячного view
- Перевірка наявності подій до/після періоду
- Групування подій по датах

#### AvailableCalendarEventsSlotsService

- Отримання всіх майбутніх подій користувача
- Обчислення вільних слотів між подіями
- Виключення конкретних подій з розрахунку
- Обмеження максимальним періодом (6 місяців)
- Повернення порожнього масиву якщо немає подій

#### CheckTimeSlotReservedService

- Перевірка доступності слоту
- Парсинг дат з timezone
- Порівняння з доступними слотами
- Повернення true для порожніх слотів

### DTOs

#### CalendarEventData

- Створення з моделі з правильною конвертацією timezone
- Отримання pivot data користувача
- Форматування дат (fromDate, toDate, fromTime, toTime)
- Обчислення duration
- Перетворення в масив

#### CalendarEventDayViewData

- Обчислення startIndex та durationIndex
- Форматування dateTime з timezone
- Отримання кольору користувача або випадкового
- Перетворення в масив

#### CalendarEventMonthViewData

- Форматування часу для місячного view
- Форматування datetime для HTML
- Перетворення в масив

#### CalendarEventWeekViewData

- Обчислення номеру дня тижня
- Обчислення startIndex та durationIndex
- Отримання кольору з pivot
- Перетворення в масив

### Actions

#### StoreCalendarEvent

- Парсинг та конвертація дат з user timezone до UTC
- Створення події з валідними даними
- Прикріплення автора з роллю HOST
- Збереження кольору користувача
- Редірект на календар

#### EditCalendarEvent

- Оновлення події з новими даними
- Синхронізація кольору через pivot
- Збереження без детачу користувача
- Редірект на календар

#### DeleteCalendarEvent

- Видалення події
- Редірект на календар

#### BaseCalendarEventAction

- Отримання timezone з профілю користувача
- Fallback до app timezone
- Парсинг дат в user timezone
- Конвертація до UTC
- Маппінг типу події

### Page Actions

#### CalendarsListPage

- Парсинг timezone з request
- Парсинг дати з request або поточна
- Визначення режиму перегляду (month/week/day)
- Перевірка permissions користувача
- Вибір правильного сервісу по mode

#### ShowCalendarEventPage

- Отримання timezone користувача
- Завантаження події з користувачами
- Перевірка permissions на редагування
- Створення DTO з правильним timezone

### Enums

#### CalendarEventColoursEnum

- names() повертає список назв
- values() повертає список значень
- randomValue() повертає випадковий колір
- randomValue() throws exception якщо масив порожній

#### CalendarEventRoleEnum

- names() повертає список назв
- values() повертає список значень

#### CalendarEventStatusEnum

- names() повертає список назв
- values() повертає список значень

#### CalendarEventTypeEnum

- names() повертає список назв
- values() повертає список значень

#### CalendarViewModeEnum

- names() повертає список назв
- values() повертає список значень

### Traits

#### BuildsCalendarPayload

- buildDayPayload() встановлює isCurrentMonth
- buildDayPayload() встановлює isSelected
- buildDayPayload() встановлює isToday
- buildDayPayload() встановлює hasEvent

#### CalculatesCalendarMetrics

- calculateSecondsSinceMidnight() правильно обчислює секунди
- calculateDurationIndex() перетворює хвилини в індекс (5-хвилинні інкременти)
- calculateStartIndex() обчислює позицію старту
- formatDateTimeWithTimezone() форматує з абревіатурою timezone

#### RetrievesUserPivotData

- getUserColour() повертає колір з pivot
- getUserColour() повертає null якщо user не знайдено
- getUserColour() повертає null якщо pivot відсутній

## Edge Cases

### Timezone Edge Cases

- Події на межі зміни доби в різних timezone
- Перехід на літній/зимовий час
- Користувач без timezone в профілі
- Порожній timezone в request

### Date Validation Edge Cases

- Створення події в минулому (повинно фейлитись)
- Створення події з toTime раніше за fromTime
- Створення події з toDate раніше за fromDate
- Створення події з однаковим fromTime та toTime
- Парсинг невалідних дат у withValidator

### Slot Availability Edge Cases

- Перекриття подій на 1 хвилину
- Події що торкаються межами але не перекриваються
- Множинні події в один час
- Події за межами 6-місячного ліміту
- Користувач без жодних подій

### Calendar View Edge Cases

- Місяць без подій
- Перший/останній місяць з подіями
- Тиждень що охоплює два місяці
- День з більше ніж 24 годинами подій
- Порожній календар для нового користувача

### Permission Edge Cases

- Користувач не є ні HOST ні PARTICIPANT
- Користувач є PARTICIPANT але намагається редагувати
- Користувач без ролі mentor намагається створити
- Подія без relationLoaded користувачів

### Pivot Data Edge Cases

- Користувач без кольору в pivot
- Множинні користувачі з різними кольорами
- syncWithPivotValues з detach=false
- Відсутній користувач в calendarEventUsers

### Service Edge Cases

- hasEventsBeforeDate() для першої події
- hasEventsAfterDate() для останньої події
- Група подій в одну дату
- Eventi що починається в один день і закінчується в інший

## Mutation Tests

### CalendarEvent Model

- Зміна duration calculation (diffInMinutes на diffInHours)
- Видалення timezone conversion в casts
- Зміна relationship cascade delete

### CalendarEventPolicy

- Зміна return true на return false в create()
- Видалення перевірки hasRole в update()
- Зміна EXISTS на DOESN'T EXIST
- Видалення перевірки relationLoaded

### Form Requests Validation

- Зміна after_or_equal на after
- Зміна max:255 на max:256
- Видалення required з title
- Зміна валідації перекриття на інвертовану

### Services Logic

- Зміна startOfWeek на endOfWeek
- Зміна UTC на іншу timezone в конвертації
- Зміна >= на > в whereBetween
- Видалення orderBy в запитах
- Зміна копіювання дати на пряме присвоєння

### DTO Transformations

- Зміна формату дати Y-m-d на d-m-Y
- Видалення copy() при конвертації timezone
- Зміна getAttribute на direct property access
- Заміна firstWhere на first

### Actions Flow

- Видалення unset для colour
- Зміна syncWithPivotValues на sync
- Зміна attach на syncWithoutDetaching
- Видалення редіректу

### Enums

- Зміна random_int на rand
- Видалення throw_if в randomValue()
- Зміна array_column на array_map

### Traits Calculations

- Зміна констант множників
- Видалення округлення в обчисленнях
- Зміна format() аргументів
- Інвертування умов в buildDayPayload

### Calendar Navigation

- Зміна addMonth на subMonth
- Видалення перевірки isEmpty()
- Зміна isSameDay на isAfter
- Інвертування умов hasEventsBefore/After

### Relationship Queries

- Зміна wherePivot на where
- Видалення withPivot
- Зміна constrained на nullOnDelete
- Видалення eager loading

### Date Calculations

- Зміна diffInMinutes на diffInSeconds
- Видалення timezone() calls
- Зміна startOfDay на endOfDay
- Інвертування порівнянь дат
