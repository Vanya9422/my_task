<?php

namespace App;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;

class UrlProcessor
{
	private $connection;
	private $channel;
	private $databaseQuery;

	/**
	 * @var string .env
	 */
	private $rabbitHost = 'rabbitmq';
	private $rabbitPort = 5672;
	private $rabbitUser = 'rabbit_user';
	private $rabbitPass = '1234';

	public function __construct()
	{
		$this->connection = new AMQPStreamConnection(
			$this->rabbitHost,
			$this->rabbitPort,
			$this->rabbitUser,
			$this->rabbitPass
		);

		$this->channel = $this->connection->channel();

		$this->databaseQuery = new DatabaseQuery();
	}

	/**
	 * @return void
	 */
	public function processUrls()
	{
		$queueName = 'urls';
		$this->channel->queue_declare($queueName, false, true, false, false);

		$messageCount = 0; // Счетчик обработанных сообщений

		$callback = function (AMQPMessage $msg) use (&$messageCount) {
			if ($msg->body === 'quit') {
				$msg->getChannel()->basic_cancel($msg->getConsumerTag());
			} else {
				$url = $msg->body;

				$this->saveData($url, strlen($url));

				$msg->ack();

				$messageCount++;

				// Если достигнуто максимальное количество сообщений, завершить обработку
				if ($messageCount >= 10) { // Замените на ваше максимальное количество
					$this->channel->basic_cancel($msg->getConsumerTag());
				}
			}
		};

		$this->channel->basic_consume($queueName, '', false, false, false, false, $callback);

		while ($this->channel->is_consuming()) {
			$this->channel->wait();
		}

		$this->closeConnections();
	}

	public function closeConnections()
	{
		$this->connection->close();
		$this->connection->channel()->close();
	}

	private function saveData($url, $contentLength)
	{
		$this->databaseQuery->saveToMariaDB($url, $contentLength);
		$this->databaseQuery->saveToClickHouse($url, $contentLength);
	}
}