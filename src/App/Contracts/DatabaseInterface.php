<?php

namespace App\Contracts;

/**
 * Интерфейс для работы с базой данных.
 */
interface DatabaseInterface {

	/**
	 * Метод, который должен быть реализован в классах, реализующих интерфейс.
	 * Запускает процесс работы с базой данных.
	 */
	public function startProcess(): void;

	/**
	 * Метод для создания таблицы в базе данных, если она еще не существует.
	 */
	public function createTableIfNotExists(): void;

	/**
	 * Метод для получения агрегированных данных из базы данных.
	 */
	public function getAggregatedData(): static;

	/**
	 * Метод для сохранения данных в базу данных.
	 *
	 * @param string $url URL для сохранения.
	 * @param int $contentLength Длина контента для сохранения.
	 */
	public function saveData(string $url, int $contentLength): void;

	/**
	 * Метод для вывода данных.
	 */
	public function print(): void;
}