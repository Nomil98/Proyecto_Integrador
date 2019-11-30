<?php
/**
 * MySQLWrapper - An easy way to query securitely MySQL databases.
 * PHP Version 7.3
 *
 * @see         https://gitlab.com/brandon16mg/php-mysqlwrapper
 * 
 * @author      Brandon Mosqueda (https://gitlab.com/brandon16mg)
 * @copyright   2019 Brandon Mosqueda
 * @license     MIT
 *
 * @version     1
 */


/**
 * MySQLWrapper exception handler.
 *
 * @author Brandon Mosqueda
 */
class DatabaseException extends Exception
{
    /**
     * Exception default constructor
     * 
     * @param string         $message  Error's message
     * @param integer        $code     Error's code
     * @param Exception|null $previous Previous exception in the chain
     */
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Return the exception in a readable form.
     * 
     * @return string Exception as string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}";
    }
}
?>