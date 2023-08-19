<?php
namespace App;

use ClickHouseDB\Client as ClickHouseClient;

class ClickHouseDatabase extends \App\BaseDatabase implements \App\DatabaseInterface {

	/**
	 * @var ClickHouseClient
	 */
	private ClickHouseClient $clickHouseClient;

	/**
	 * @var array
	 */
	private array $aggregatedData;

	/**
	 * @var string .env
	 */
	private string $host = 'clickhouse'; //172.27.0.2
	private int $port = 8123; // 8123
	private string $user = 'default';
	private string $pass = '';
	private string $db = 'clickhouse';

	/**
	 * Запускает процесс работы с базой данных ClickHouse.
	 *
	 * @return void
	 */
	public function startProcess(): void {
		$this->clickHouseClient = new ClickHouseClient([
			'host' => $this->host,
			'port' => $this->port,
			'username' => $this->user,
			'password' => $this->pass,
			'db' => $this->db
		]);

		$this->createTableIfNotExists();
	}

	/**
	 * Создает таблицу в базе данных, если она не существует.
	 *
	 * @return void
	 */
	public function createTableIfNotExists(): void {
		$clickHouseCreateTableSql = "
            CREATE TABLE IF NOT EXISTS urls (
                url String,
                content_length Int32,
                created_at DateTime DEFAULT now()
            ) ENGINE = MergeTree() ORDER BY url
        ";

		$this->clickHouseClient->write($clickHouseCreateTableSql);
	}

	/**
	 * Сохраняет данные в базу данных ClickHouse.
	 *
	 * @param string $url URL для сохранения.
	 * @param int $contentLength Длина контента для сохранения.
	 * @return void
	 */
	public function saveData(string $url, int $contentLength): void {
		$values = [
			"('$url', $contentLength)"
		];

		$insertQuery = "INSERT INTO urls (url, content_length) FORMAT Values " . implode(',', $values);

		$this->clickHouseClient->write($insertQuery);
	}

	/**
	 * Получает и агрегирует данные из базы данных ClickHouse.
	 *
	 * @return $this
	 */
	public function getAggregatedData(): static
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

		$this->aggregatedData = $result->rows();

		return $this;
	}

	/**
	 * Выводит агрегированные данные из базы ClickHouse.
	 *
	 * @return void
	 */
	public function print(): void {
		echo "<h1>Aggregated Data: ClickHouse</h1>\n";
		foreach ($this->aggregatedData as $row) {
			echo "<p>Minute Group: {$row['minute_group']}</p>\n";
			echo "<p>Row Count: {$row['row_count']}</p>\n";
			echo "<p>Average Content Length: {$row['avg_content_length']}</p>\n";
			echo "<p>First Created At: {$row['first_created_at']}</p>\n";
			echo "<p>Last Created At: {$row['last_created_at']}</p>\n";
			echo "<hr>\n";
		}
	}
}
