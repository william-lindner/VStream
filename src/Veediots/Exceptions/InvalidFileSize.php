<?php

namespace Veediots\Exceptions;

use Veediots\Interfaces\VStreamException;

class InvalidFileSize extends VStreamException
{
    /**
     * @inheritdoc
     */
    protected $code = 400;
}