<?php // phpcs:disable Squiz.Commenting

namespace DevKabir\Debugger;

use Spatie\Ignition\Ignition as BaseIgnition;
use Throwable;

class Ignition extends BaseIgnition {

	public function renderError(
		int $level,
		string $message,
		string $file = '',
		int $line = 0,
		array $context = array()
	): void {
		if ( $this->shouldIgnoreTranslationApiError( $message, $file ) ) {
			return;
		}

		parent::renderError( $level, $message, $file, $line, $context );
	}

	public function handleException( Throwable $throwable ): \Spatie\FlareClient\Report {
		if ( $this->shouldIgnoreTranslationApiThrowable( $throwable ) ) {
			return $this->createReport( $throwable );
		}

		return parent::handleException( $throwable );
	}

	protected function shouldIgnoreTranslationApiError( string $message, string $file ): bool {
		$needle = 'translation_api';
		dump( $message, $file );
		return stripos( $message, $needle ) !== false
			|| ( '' !== $file && stripos( $file, $needle ) !== false );
	}

	protected function shouldIgnoreTranslationApiThrowable( Throwable $throwable ): bool {
		$needle = 'translation_api';

		if ( stripos( $throwable->getMessage(), $needle ) !== false ) {
			return true;
		}

		if ( stripos( $throwable->getFile(), $needle ) !== false ) {
			return true;
		}

		$trace = $throwable->getTraceAsString();

		return $trace !== '' && stripos( $trace, $needle ) !== false;
	}
}
