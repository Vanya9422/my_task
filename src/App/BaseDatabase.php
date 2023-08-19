<?php

namespace App;

/**
 * Абстрактный базовый класс для работы с базой данных.
 */
abstract class BaseDatabase {

	/**
	 * Конструктор класса.
	 * Вызывает метод startProcess при создании экземпляра.
	 */
	public function __construct() {
		$this->startProcess(); // Вызываем метод startProcess при создании экземпляра
	}

	/**
	 * Абстрактный метод, который должен быть реализован в дочерних классах.
	 * Определяет основной процесс работы с базой данных.
	 */
	abstract public function startProcess(); // Дочерние классы обязаны реализовать этот метод
}