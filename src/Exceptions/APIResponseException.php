<?php

namespace Crester\Exceptions;

class APIResponseException extends \Exception
{
    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return __CLASS__.": [{$this->code}]: '{$this->message}' in {$this->file}({$this->line})\n"
                                ."{$this->getTraceAsString()}";
    }
}
