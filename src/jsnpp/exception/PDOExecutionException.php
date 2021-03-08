<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp\exception;

use PDOException;

class PDOExecutionException extends PDOException
{
    public function __construct($message, $previous = null) {
        $this->message = $message;
        parent::__construct($message, 0, $previous);
    }
}