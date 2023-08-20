<?php

namespace App\Traits;

use App\Exceptions\ValidationException;

trait UrlValidateAble {

	/**
	 * Валидирует массив URL-ов.
	 *
	 * @param array $urls Массив URL-ов для валидации.
	 * @return array Возвращает массив, содержащий только валидные URL-ы.
	 * @throws ValidationException Если в массиве есть невалидные URL-ы.
	 */
	private function validateUrls(array $urls): array {
		$validUrls = [];

		foreach ($urls as $url) {
			if (filter_var($url, FILTER_VALIDATE_URL)) {
				$validUrls[] = $url;
			} else {
				throw new ValidationException($url, "Invalid URL format: $url");
			}
		}

		return $validUrls;
	}
}