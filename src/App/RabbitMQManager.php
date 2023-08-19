<?php

namespace App;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQManager {

	/**
	 * @var AMQPStreamConnection
	 */
	private AMQPStreamConnection $connection;

	/**
	 * @var \PhpAmqpLib\Channel\AbstractChannel|\PhpAmqpLib\Channel\AMQPChannel
	 */
	private \PhpAmqpLib\Channel\AbstractChannel|\PhpAmqpLib\Channel\AMQPChannel $channel;

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
	 * Отправляет URL в очередь RabbitMQ
	 *
	 * @param array $urls
	 */
	public function sendUrls(array $urls) {
		// Объявление очереди 'urls', если её нет
		$this->channel->queue_declare('urls', false, true, false, false);

		foreach ($urls as $url) {
			$message = new AMQPMessage($url);
			$this->channel->basic_publish($message, '', 'urls');

			echo "<p> Rabbit Sent: $url </p>";

			$delay = rand(5, 30);
			sleep($delay);
		}
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
	 * @return \PhpAmqpLib\Channel\AMQPChannel|\PhpAmqpLib\Channel\AbstractChannel
	 */
	public function getChannel(): \PhpAmqpLib\Channel\AMQPChannel|\PhpAmqpLib\Channel\AbstractChannel
	{
		return $this->channel;
	}
}
