# async_architecture
Проект реализован на курсе Асинхронная архитектура. В качестве задачи было реализовать Таск-трекер для попугаев.
Таск-трекер:
Авторизация по форме клюва
 	- Актор - Popug
	- Команда - Popug.Auth
	- Событие - Auth.success
	 
Новые таски создает кто угодно 
	- Актор - Popug
	- Команда - Task.Create
	- Событие - Create.Success
	- Query - Task 

Заасайнить задачу при ее создание на любого сотрудника кроме менеджера, администратора или нового сотрудника
	- Актор - Create.Success
	- Команда - Task.Assign
	- Событие - Assign.Success
	- Query - Task

Менеджеры или администраторы должны иметь кнопку «заассайнить задачи», которая возьмёт все открытые задачи и рандомно заассайнит каждую на любого из сотрудников
	- Актор - Popug.Admin
	- Команда - AllTask.Random / AllTask.Assign 
	- Событие - Assign.Success
	- Query - All Tasks

Каждый сотрудник должен иметь возможность видеть в отдельном месте список заассайненных на него задач
	- Актор - Popug
	- Команда - GetAllTaskByPopug
	- Событие - Task.Received
	- Query - All tasks by popug 

Отметить выполнение задачи
	- Актор - Popug
	- Команда - Task.Done
	- Событие - Task.Done.Success
	- Query - Task

Аккаунтинг: кто сколько денег заработал

 У админов и бухгалтеров должен быть доступ к общей статистике по деньгами заработанным (количество заработанных топ-менеджментом за сегодня денег + статистика по дням).
	- Актор - Popug.Accountant /Popug.Admin
	- Команда - Accounting.Get
	- Событие - Accounting.Get.Success
	- Query - Accounting

У обычных попугов доступ к аккаунтингу тоже должен быть. Но только к информации о собственных счетах (аудит лог + текущий баланс). 
	- Актор - Popug.User
	- Команда - AccountingById.Get /  AccountingById.GetBalance
	- Событие - AccountingById.Get.Success / AccountingById.GetBalance.Success
	- Query - Audit/Balance

Цены на задачу определяется единоразово, в момент появления в системе
	- Актор - Create.Success
	- Команда - CreatePrice
	- Событие - PriceCreated.Success
	- Query -PriceByTask


 Деньги списываются сразу после ассайна на сотрудника, а начисляются после выполнения задачи.
	- Актор - Assign.Success/Task.Done.Success
	- Команда - MoneyHold /WriteOffMoney
	- Событие - MoneyHold.Success / WriteOffMoney.Success
	- Query - HowMuchMoneyHolded / HowMuchMoneyWrittenOff

Отрицательный баланс переносится на следующий день.
	- Актор - CountMoneyPerDayByPopug
	- Команда - TransferBalance
	- Событие - BalanceTransfered
	- Query - HowMuchMoneyWillBeTransferTomorrow

6.     Считать сколько денег сотрудник получил за рабочий день
	- Актор - AccountingById.Get.Success /Accounting.Get
	- Команда - CountMoneyPerDayByPopug
	- Событие - CountedMoney.Success
	- Query - HowMuchMoneyPopugEarnsPerDay

7.      Отправлять на почту сумму выплаты.
	- Актор - AccountingById.GetBalance.Success
	- Команда - SendSumByEmail
	- Событие -SentSum.Success
	- Query - Check

После выплаты баланса (в конце дня) он должен обнуляться, и в аудитлоге всех операций аккаунтинга должно быть отображено, что была выплачена сумма.
	- Актор - SentSum.Success
	- Команда -NulledBalance / DisplayPay
	- Событие - NulledBalance.Success
	- Query - PaymentPerDayByPopug

Аналитика
Аналитика — это отдельный дашборд, доступный только админам.
	- Актор - Popug.Admin
	- Query - Analitic
Нужно указывать, сколько заработал топ-менеджмент за сегодня и сколько попугов ушло в минус.
	- Актор - GetAnalitic.success
	- Cобытие - CalcHowManyParrotsWentIntoMinus.success
	- Query - Analitic
Нужно показывать самую дорогую задачу за день, неделю или месяц.
	- Актор - GetAnalitic.success
	- Событие - CalcExpensiveTask.success
	- Query - Task

Синхронные коммуникации:
Аутентицикация
Генерация цены для задачи
Создать задачу
Ассайн задачи после создания
Холдирование денег после ассайна задачи
Списание денег после выполнение задачи 
Выполнение задачи

Асинхронные коммуникации:
CUDS events for balance
Отправить выплату по почте 
Рандомный ассайн задач 
Подсчет сколько попугаев ушло в минус
Ежедневный подсчет баланса
Подсчет самой дорогой задачи за день

Бизнес события: 
Отправка выплаты по Email (продьюсер - Balance service, консьюмер - Account service)
Получить информацию к статистики о заработанных денег (продьюсер - Accounting service, консьюмер - Account service)
Получить текущий баланс (продьюсер - Balance service, консьюмер - Accounting 
service)

CUD события:
Created/Updated/Deleted account (продьюсер - Account service, консьюмер - Task 
Service)
Created/Updated/Deleted balance (продьюсер - Balance service, консьюмер - Account service, Accounting service, Analitic service)
Created/Updated/Deleted audit log (продьюсер - Accounting service, консьюмер - Account service, Analitic service)

Диаграммы доступны по ссылке - https://drive.google.com/file/d/1-VS-xQuyxdo0wMreiOsghLYsa0xKDmME/view?usp=sharing
