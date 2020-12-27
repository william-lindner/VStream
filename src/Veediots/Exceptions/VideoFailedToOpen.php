<?php

namespace Veediots\Exceptions;

use Veediots\Interfaces\VStreamException;

class VideoFailedToOpen extends VStreamException
{
    /**
     * @inheritdoc
     */
    protected $code = 404;
}