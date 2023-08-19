<?php
namespace App;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class UrlSender
{
	private AMQPStreamConnection $connection;
	private $channel;

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
	}

	public function sendUrls(array $urls)
	{
		$this->channel->queue_declare('urls', false, true, false, false);

		foreach ($urls as $url) {
			$message = new AMQPMessage($url);
			$this->channel->basic_publish($message, '', 'urls');

			echo "<p> Rabbit Sent: $url </p>";

//			$delay = rand(1, 2);
//			sleep($delay);
		}

		$this->channel->close();
		$this->connection->close();
	}
}
