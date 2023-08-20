<?php

namespace App;

use App\Traits\UrlSendAble;
use App\Traits\UrlValidateAble;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQManager {

	use UrlValidateAble, UrlSendAble;

	/**
	 * @var AMQPStreamConnection
	 */
	private AMQPStreamConnection $connection;

	/**
	 * @var AbstractChannel|AMQPChannel
	 */
	private AbstractChannel|AMQPChannel $channel;

	/**
	 * @var string .env
	 */
	private string $host = 'rabbitmq';
	private int $port = 5672;
	private string $user = 'rabbit_user';
	private string $pass = '1234';

	/**
	 * Конструктор класса RabbitMQManager
	 *
	 * @throws \Exception
	 */
	public function __construct() {
		// Создание соединения с RabbitMQ
		$this->connection = new AMQPStreamConnection(
			$this->host,
			$this->port,
			$this->user,
			$this->pass
		);

		// Создание канала для работы с RabbitMQ
		$this->channel = $this->connection->channel();
	}

	/**
	 * Отправляет URL-ы в очередь RabbitMQ для обработки.
	 *
	 * @param array $urls Массив URL-ов, которые нужно отправить в очередь.
	 * @throws Exceptions\ValidationException Если какой-либо URL не проходит валидацию.
	 */
	public function sendUrls(array $urls) {
		// Валидация URL-ов с помощью метода из трейта UrlValidateAble
		$validUrls = $this->validateUrls($urls);

		// Отправка валидных URL-ов в RabbitMQ с помощью метода из трейта UrlSendAble
		// Второй аргумент - канал RabbitMQ, который был создан при инициализации RabbitMQManager
		$this->sendUrlsToRabbitMQ($validUrls, $this->channel);
	}

	/**
	 * Закрывает соединение и канал RabbitMQ
	 */
	public function closeConnections() {
		$this->channel->close();
		$this->connection->close();
	}

	/**
	 * Подписывается на обработку сообщений из очереди 'urls'
	 *
	 * @param \Closure $callback Функция-обработчик сообщений
	 */
	public function consumeUrls(\Closure $callback) {
		$queueName = 'urls';

		// Объявление очереди 'urls' для потребления
		$this->channel->queue_declare($queueName, false, true, false, false);

		// Ожидание и обработка сообщений из очереди 'urls' с использованием переданного callback
		$this->channel->basic_consume($queueName, '', false, false, false, false, $callback);

		// Ожидание новых сообщений и вызов callback'а при их получении
		while ($this->channel->is_consuming()) {
			$this->channel->wait();
		}
	}

	/**
	 * @return AMQPChannel|AbstractChannel
	 */
	public function getChannel(): AMQPChannel|AbstractChannel
	{
		return $this->channel;
	}
}
