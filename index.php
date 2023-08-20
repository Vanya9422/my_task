<?php

require 'vendor/autoload.php';

$urls = include 'urls.php';

try {
	// Создаем экземпляр RabbitMQManager с конфигурацией
	$rabbitMQManager = new \App\RabbitMQManager();

	// Отправляем URL-ы в RabbitMQ для обработки
	$rabbitMQManager->sendUrls($urls);

	// Создаем экземпляр MariaDBDatabase
	$mariaDBDatabase = new \App\MariaDBDatabase();

	// Создаем экземпляр ClickHouseDatabase
	$clickHouseDatabase = new \App\ClickHouseDatabase();

	// Создаем экземпляр UrlProcessor, передавая ему объекты обеих баз данных и менеджера RabbitMQ
	$urlProcessor = new \App\UrlProcessor($rabbitMQManager, $mariaDBDatabase, $clickHouseDatabase);

	// Обрабатываем URL-ы для ClickHouse
	$urlProcessor->processUrls();

	// Выводим агрегированные данные из MariaDb
	$urlProcessor->printAggregatedData($mariaDBDatabase);

	// Выводим агрегированные данные из ClickHouse
	$urlProcessor->printAggregatedData($clickHouseDatabase);

	// закрываем соединение
	$rabbitMQManager->closeConnections();
} catch (\App\Exceptions\ValidationException $e) {
	echo "Validation Error: Invalid URL '{$e->getInvalidUrl()}'.";
} catch (\Exception $e) {
	echo "An error occurred: " . $e->getMessage();
}