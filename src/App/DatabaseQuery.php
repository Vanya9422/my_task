<?php
namespace App;

use PDO;
use ClickHouseDB\Client as ClickHouseClient;

class DatabaseQuery
{
	private $pdo;
	private $clickHouseClient;

	/**
	 * @var string .env
	 */
	private $clickHost = 'clickhouse'; //172.27.0.2
	private $clickPort = 8123; // 8123
	private $clickUser = 'default';
	private $clickPass = '';
	private $clickDb = 'clickhouse';

	private $mariaUser = 'root';
	private $mariaPass = '1234';
	private $dsn = "mysql:host=mariadb;dbname=task_db";

	public function __construct()
	{
		$this->pdo = new PDO(
			$this->dsn,
			$this->mariaUser,
			$this->mariaPass
		);

		$configClickHouse = [
			'host' => $this->clickHost,
			'port' => $this->clickPort,
			'username' => $this->clickUser,
			'password' => $this->clickPass,
			'db' => $this->clickDb
		];

		$this->clickHouseClient = new ClickHouseClient($configClickHouse);

		$this->makeTableIfNoExistsMariaDb();
		$this->makeTableIfNoExistsClickHouse();
	}

	public function printUrls() {
		echo "<h1>Aggregated Data from MariaDB:</h1>\n";
		$mariadbData = $this->getMariaDBAggregatedData();
		foreach ($mariadbData as $row) {
			echo "<p>Minute Group: {$row['minute_group']}</p>\n";
			echo "<p>Row Count: {$row['row_count']}</p>\n";
			echo "<p>Average Content Length: {$row['avg_content_length']}</p>\n";
			echo "<p>First Created At: {$row['first_created_at']}</p>\n";
			echo "<p>Last Created At: {$row['last_created_at']}</p>\n";
			echo "<hr>\n";
		}

		echo "<h1>Aggregated Data from ClickHouse:</h1>\n";
		$clickhouseData = $this->getClickHouseAggregatedData();
		foreach ($clickhouseData as $row) {
			echo "<p>Minute Group: {$row['minute_group']}</p>\n";
			echo "<p>Row Count: {$row['row_count']}</p>\n";
			echo "<p>Average Content Length: {$row['avg_content_length']}</p>\n";
			echo "<p>First Created At: {$row['first_created_at']}</p>\n";
			echo "<p>Last Created At: {$row['last_created_at']}</p>\n";
			echo "<hr>\n";
		}
	}


	private function makeTableIfNoExistsMariaDb(): void {
		// Создание таблицы urls, если она еще не существует
		$createTableSql = "
            CREATE TABLE IF NOT EXISTS urls (
                id INT AUTO_INCREMENT PRIMARY KEY,
                url VARCHAR(255) NOT NULL,
                content_length INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";

		$this->pdo->exec($createTableSql);
	}

	private function makeTableIfNoExistsClickHouse(): void {
		// Создание таблицы urls в ClickHouse, если она еще не существует
		$clickHouseCreateTableSql = "
			CREATE TABLE IF NOT EXISTS urls (
				url String,
				content_length Int32,
				created_at DateTime DEFAULT now()
			) ENGINE = MergeTree() ORDER BY url
		";

		$this->clickHouseClient->write($clickHouseCreateTableSql);
	}

	public function dropTables()
	{
		// Удаление таблицы urls из MariaDB
		$dropMariaDbTableSql = "DROP TABLE IF EXISTS urls";
		$this->pdo->exec($dropMariaDbTableSql);

		// Удаление таблицы urls из ClickHouse
		$dropClickHouseTableSql = "DROP TABLE IF EXISTS urls";
		$this->clickHouseClient->write($dropClickHouseTableSql);
	}

	public function saveToMariaDB($url, $contentLength)
	{
		$stmt = $this->pdo->prepare("INSERT INTO urls (url, content_length) VALUES (:url, :content_length)");
		$stmt->execute([
			':url' => $url,
			':content_length' => $contentLength,
		]);
	}

	public function saveToClickHouse($url, $contentLength)
	{
		$values = [
			"('$url', $contentLength)"
		];

		$insertQuery = "INSERT INTO urls (url, content_length) FORMAT Values " . implode(',', $values);

		$this->clickHouseClient->write($insertQuery);
	}

	private function getMariaDBAggregatedData()
	{
		$query = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS minute_group,
                COUNT(*) AS row_count,
                AVG(content_length) AS avg_content_length,
                MIN(created_at) AS first_created_at,
                MAX(created_at) AS last_created_at
            FROM urls
            GROUP BY minute_group
            ORDER BY minute_group
        ";

		$stmt = $this->pdo->query($query);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	private function getClickHouseAggregatedData()
	{
		$query = "
			SELECT 
				toStartOfMinute(created_at) AS minute_group,
				COUNT(*) AS row_count,
				AVG(content_length) AS avg_content_length,
				MIN(created_at) AS first_created_at,
				MAX(created_at) AS last_created_at
			FROM urls
			GROUP BY minute_group
			ORDER BY minute_group
   		 ";

		$result = $this->clickHouseClient->select($query);

		return $result->rows();
	}
}
