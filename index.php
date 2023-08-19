<?php
require 'vendor/autoload.php';

$urls = include 'urls.php';

try {
	$urlSender = new \App\UrlSender();
	$urlSender->sendUrls($urls);
	$urlProcessor = new \App\UrlProcessor();
	$urlProcessor->processUrls();

	$databaseClass = new \App\DatabaseQuery();
	$databaseClass->printUrls();
//	$databaseClass->dropTables();
} catch (Exception $e) {
	print_r($e->getMessage());
}

