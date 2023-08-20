<?php

namespace App\Traits;

use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

trait UrlSendAble
{
	/**
	 * Отправляет массив валидных URL-ов в RabbitMQ.
	 *
	 * @param array $urls Массив валидных URL-ов для отправки.
	 * @param AbstractChannel|AMQPChannel $channel
	 */
	private function sendUrlsToRabbitMQ(
		array $urls,
		AbstractChannel|AMQPChannel $channel
	): void {
		// Объявление очереди 'urls', если её нет
		$channel->queue_declare('urls', false, true, false, false);

		foreach ($urls as $key => $url) {
			$msg = new AMQPMessage($url);
			$channel->basic_publish($msg, '', 'urls');
			echo "<p><b>$key:</b> Rabbit Sent: $url </p>";
			$delay = rand(5, 30);
			sleep($delay);
		}
	}
}