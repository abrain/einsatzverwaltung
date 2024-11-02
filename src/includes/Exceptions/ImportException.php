<?php
namespace abrain\Einsatzverwaltung\Exceptions;

use Exception;
use Throwable;

/**
 * Class ImportException
 * @package abrain\Einsatzverwaltung\Exceptions
 */
class ImportException extends Exception
{
    /**
     * @var string[]
     */
    private $details;

    /**
     * @param string $message
     * @param string[] $details
     * @param $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", array $details = [], $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    /**
     * @return string[]
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
