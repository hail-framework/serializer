<?php

namespace Hail\Serializer\Exception;

use Throwable;
use RuntimeException;

class SerializerException extends RuntimeException
{
    public function __construct(string $message, Throwable $previous = null)
    {
        $code = 0;
        if ($previous !== null) {
            if ($message === '') {
                $message = $previous->getMessage();
            } else {
                $message .= ', ' . $previous->getMessage();
            }

            $code = $previous->getCode();
        }

        parent::__construct($message, $code, $previous);
    }
}
