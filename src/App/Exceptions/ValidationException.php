<?php

namespace App\Exceptions;

use Exception;

/**
 * Исключение, выбрасываемое при ошибке валидации URL-ов.
 */
class ValidationException extends Exception
{
	/**
	 * @var string Невалидный URL, вызвавший ошибку.
	 */
	protected string $invalidUrl;

	/**
	 * Конструктор исключения.
	 *
	 * @param string $invalidUrl Невалидный URL, вызвавший ошибку.
	 * @param string $message Сообщение об ошибке.
	 * @param int $code Код ошибки.
	 * @param \Throwable|null $previous Предыдущее исключение, если оно есть.
	 */
	public function __construct(string $invalidUrl, string $message = "", int $code = 0, \Throwable $previous = null)
	{
		$this->invalidUrl = $invalidUrl;

		parent::__construct($message, $code, $previous);
	}

	/**
	 * Получает невалидный URL, вызвавший ошибку.
	 *
	 * @return string Невалидный URL.
	 */
	public function getInvalidUrl(): string
	{
		return $this->invalidUrl;
	}
}