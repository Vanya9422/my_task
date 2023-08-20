<?php

namespace App;

use App\Contracts\DatabaseInterface;
use App\Traits\UrlSendAble;
use App\Traits\UrlValidateAble;
use PhpAmqpLib\Message\AMQPMessage;

class UrlProcessor
{
	use UrlValidateAble, UrlSendAble;

	/**
	 * Класс для обработки URL-ов и сохранения данных в базы данных.
	 *
	 * @param RabbitMQManager $rabbitMQManager Менеджер RabbitMQ для работы с очередью.
	 * @param MariaDBDatabase $mariaDBDatabase База данных MariaDB.
	 * @param ClickHouseDatabase $clickHouseDatabase База данных ClickHouse.
	 */
	public function __construct(
		private RabbitMQManager $rabbitMQManager,
		private MariaDBDatabase $mariaDBDatabase,
		private ClickHouseDatabase $clickHouseDatabase,
	) {}

	/**
	 * Обрабатывает URL-ы, сохраняет данные в обе базы данных.
	 *
	 * @return void
	 */
	public function processUrls() {
		$messageCount = 0; // Счетчик обработанных сообщений

		// Функция обратного вызова для обработки сообщений из очереди
		$callback = function (AMQPMessage $msg) use (&$messageCount) {

			// Если получено сообщение "quit", завершаем обработку
			if ($msg->body === 'quit') {
				$this->rabbitMQManager->getChannel()->basic_cancel($msg->getConsumerTag());
			} else {
				$url = $msg->body;

				try {
					$this->validateUrls([$url]); // Валидируем URL
				} catch (\App\Exceptions\ValidationException $e) {
					echo "Validation Error: " . $e->getMessage() . "<br>";
					return; // Пропускаем невалидные URL-ы
				}

				// Сохраняем данные в MariaDB и ClickHouse
				$this->mariaDBDatabase->saveData($url, strlen($url));
				$this->clickHouseDatabase->saveData($url, strlen($url));

				$msg->ack();

				$messageCount++;

				// Если достигнуто максимальное количество сообщений, завершить обработку
				if ($messageCount >= 10) { // Замените на ваше максимальное количество
					$this->rabbitMQManager->getChannel()->basic_cancel($msg->getConsumerTag());
				}
			}
		};

		$this->rabbitMQManager->consumeUrls($callback);
	}

	/**
	 * Выводит агрегированные данные из указанной базы данных.
	 *
	 * @param DatabaseInterface $client База данных для вывода агрегированных данных.
	 * @return void
	 */
	public function printAggregatedData(DatabaseInterface $client): void {
		// Получение агрегированных данных из базы
		$aggregatedData = $client->getAggregatedData();

		// вывода данных.
		$aggregatedData->print();
	}
}