<?php

namespace App;

use PDO;

class MariaDBDatabase extends \App\BaseDatabase implements \App\DatabaseInterface {

	/**
	 * @var PDO
	 */
	private PDO $pdo;

	/**
	 * @var array
	 */
	private array $aggregatedData;

	private $user = 'root';
	private $pass = '1234';
	private $dsn = "mysql:host=mariadb;dbname=task_db";

	/**
	 * Запускает процесс работы с базой данных MariaDB.
	 *
	 * @return void
	 */
	public function startProcess(): void {
		$this->pdo = new PDO(
			$this->dsn,
			$this->user,
			$this->pass
		);

		$this->createTableIfNotExists();
	}

	/**
	 * Создает таблицу в базе данных, если она не существует.
	 *
	 * @return void
	 */
	public function createTableIfNotExists(): void {
		$createTableSql = "
            CREATE TABLE IF NOT EXISTS urls (
                id INT AUTO_INCREMENT PRIMARY KEY,
                url VARCHAR(255) NOT NULL,
                content_length INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";

		// Выполняем SQL-запрос для создания таблицы
		$this->pdo->exec($createTableSql);
	}

	/**
	 * Сохраняет данные в базу данных MariaDB.
	 *
	 * @param string $url URL для сохранения.
	 * @param int $contentLength Длина контента для сохранения.
	 * @return void
	 */
	public function saveData(string $url, int $contentLength): void {
		$stmt = $this->pdo->prepare("INSERT INTO urls (url, content_length) VALUES (:url, :content_length)");
		$stmt->execute([
			':url' => $url,
			':content_length' => $contentLength,
		]);
	}

	/**
	 * Получает и агрегирует данные из базы данных MariaDB.
	 *
	 * @return $this
	 */
	public function getAggregatedData(): static {
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

		$this->aggregatedData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

		return $this;
	}

	/**
	 * Выводит агрегированные данные из базы MariaDB.
	 *
	 * @return void
	 */
	public function print(): void {
		echo "<h1>Aggregated Data: MariaDB</h1>\n";
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