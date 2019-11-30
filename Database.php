<?php
/**
 * MySQLWrapper - An easy way to query securitely MySQL-like databases.
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

require 'DatabaseException.php';

/**
 * Abstract mysqli complex to query MySQL-like databases.
 *
 * @author Brandon Mosqueda
 */
class Database
{
    private const defaultHost = 'localhost';
    private const defaultUsername = 'root';
    private const defaultPassword = '';
    private const defaultDatabase = '';
    private const defaultPort = 3306;

    /**
     * Regex used to recover all the params' names used in a custom binding
     * query. It match all words written between curly braces and has
     * alphanumeric and underscore characters without consume the curly braces.
     */
    private const getParamNamesRegex = '/(?<={)([a-zA-Z0-9_])+?(?=})/';

    // The same as getParamNamesRegex but in this the curly braces are consumed
    private const replaceParamNamesRegex = '/{([a-zA-Z0-9_])+?}/';

    private static $connection = null;
    private static $isTransaction = false;

    public static $host = null;
    public static $username = null;
    public static $password = null;
    public static $database = null;
    public static $port = null;

    /**
     * Prevent instaces creation.
     */
    private function __construct() {}

    /**
     * Create and return a new mysqli connection with the provided or default
     * connection values. This method is a singleton, prevents more than one
     * connection creation.
     *
     * @throws DatabaseException  When there is an error in getting the
     *                            connection.
     * @return mysqli An instance of mysqli.
     */
    private static function getConnection()
    {
        if(static::$connection === null) {
            static::$host = static::$host !== null ?
                                static::$host :
                                static::defaultHost;

            static::$username = static::$username !== null ?
                                static::$username :
                                static::defaultUsername;

            static::$password = static::$password !== null ?
                                static::$password :
                                static::defaultPassword;

            static::$database = static::$database !== null ?
                                static::$database :
                                static::defaultDatabase;

            static::$port = static::$port !== null ?
                            static::$port :
                            static::defaultPort;

            
            static::$connection = new mysqli(
                                    static::$host,
                                    static::$username,
                                    static::$password,
                                    static::$database,
                                    static::$port
                                  );

            if (static::$connection->connect_error) {
                throw new DatabaseException(
                    "There's an error in connection: "
                    . static::$connection->connect_error
                );
            }
                        
            static::$connection->set_charset("utf8");
        }

        return static::$connection;
    }

    /**
     * If there is an opened connection, close it and clear the object.
     */
    private static function closeConnection()
    {
        if (static::$connection !== null) {
            static::$connection->close();
        }

        static::$connection = null;
    }

    /**
     * From an associative array or an object it generates an array with the
     * paramethers in the same order defined in the custom parameterized query.
     *  
     * @param  string       $sql    The sql query with either form of
     *                              parameterizing; classic, with ? or custom by
     *                              paramethers' names, if any.
     * @param  array|object $params Contains the keys specified in the prepared
     *                              sql query, if any.
     * 
     * @throws InvalidArgumentException If The sql and paramether's type is not
     *                                  valid to form the query. e.g. A sql
     *                                  query with classic binding but sending
     *                                  an object as paramether.
     * @return array                    An array with the elements sorted as in
     *                                  the sql query.
     */
    private static function getOrderedParams(string $sql, $params)
    {
        $orderedParams = [];

        if (self::hasParamsByName($sql)) {
            $paramsNames = self::getAllParamsName($sql);

            if (gettype($params) === 'array') {
                if(!self::isAssosiativeArray($params)) {
                    throw new InvalidArgumentException(
                        "In prepared custom queries the paramethers must be a "
                        . "key-value array or and object."
                    );
                }

                foreach ($paramsNames as $paramName) {
                    if (!isset($params[$paramName])) {
                        throw new InvalidArgumentException(
                            "The paramether {$paramName} of the prepared query"
                            . " is not set in the provided array."
                        );
                    }
                 
                    $orderedParams[] = $params[$paramName];
                }
            } else {
                foreach ($paramsNames as $paramName) {
                    if (!isset($params->$paramName)) {
                        throw new InvalidArgumentException(
                            "The paramether {$paramName} of the prepared query"
                            . " is not set in the provided object."
                        );
                    }

                    $orderedParams[] = $params->$paramName;
                }
            }
        } else {
            if ($params !== null 
                && (
                    gettype($params) === 'object'
                    || self::isAssosiativeArray($params)
                )
            ) {
                throw new InvalidArgumentException(
                    "When you use queries with classic binding the paramethers"
                    . " must be an indexed array with the paramethers in the"
                    . " same order as the query."
                );
            }

            $orderedParams = $params;
        }

        return $orderedParams;
    }

    /**
     * If the sql provided has a custom paremeterized form it will be formated
     * to the valid form that expects mysqli's bind_param function. e.g.
     *     UPDATE users SET name = {name}    to   UPDATE users SET name = ?
     * 
     * @param  string $sql sql query.
     * @return string      sql in classic parameterized.
     */
    private static function sqlToClassicBindingFormat(string $sql)
    {
        if (self::hasParamsByName($sql)) {
            $sql = preg_replace(static::replaceParamNamesRegex, '?', $sql);
        }

        return $sql;
    }

    /**
     * Check if an array is a key-value array.
     * 
     * @param  array   $array To be checked array.
     * @return boolean        If it's or not an assosiative array.
     */
    private static function isAssosiativeArray(array $array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Check if a sql query has a custom form of paramethers parameterizing with
     * paramethers' names and not with ?. e.g.
     *     UPDATE users SET name = {updateName}    returns true
     *     
     * @param  string  $sql sql to be evaluated.
     * @return boolean      If it has or not custom paramethers.
     */
    private static function hasParamsByName(string $sql)
    {
        return preg_match(static::getParamNamesRegex, $sql) > 0;
    }

    /**
     * Returns all the strings that have the pattern defined in
     * getParamNamesRegex.
     * 
     * @param  string $sql sql to be evaluated.
     * @return array       All the matches.
     */
    private static function getAllParamsName(string $sql)
    {
        $names = [];

        preg_match_all(static::getParamNamesRegex, $sql, $names);

        return $names[0]; 
    }

    /**
     * Evaluate if is one of the allowed params' type (array, object or null).
     * 
     * @param  array|object|null  $params The paramether to be evaluated.
     * @return boolean                    True if it is one of the allowed.
     *                                    types.
     */
    private static function isValidParamsType($params)
    {
        return gettype($params) === 'object'
            || gettype($params) === 'array'
            || $params === null;
    }

    /**
     * It does parameterized queries to database for preventing SQL injection.
     * See examples of prepared statements at:
     * https://www.php.net/manual/es/mysqli.prepare.php
     * 
     * @param  string $sql        The SQL query. It have to come with the same
     *                            convention of mysqli's function bind_param, 
     *                            that is to say, with = ? in the paramethers
     *                            you want to bind or with a custom way that
     *                            consist in putting the key of the array|object
     *                            that will be included in the query e.g.
     *                              classic binding:
     *                                  UPDATE users SET name = ?
     *                              custom binding by keys' names:  
     *                                  UPDATE users SET name = {userId}
     * @param  string $rules      The rules of bind_param which indicate the
     *                            type of each paramether. They must come in the
     *                            same order as they appear in the sql query
     *                            e.g. for a string param and a doblue param in
     *                            the query use:
     *                                'sd'
     * @param  array  $params     The query arguments in the same order as the 
     *                            SQL query and rules when you use classic
     *                            binding, otherwise you can send a key-value
     *                            array or an abject with the paramethers in
     *                            any order.
     * @param  bool   $isSelect   If the query is a SELECT statement. It defines
     *                            what will be returned, if a mysqli_result or
     *                            an object.
     * 
     * @throws DatabaseException  When there is an error with the SQL statement
     *                            or with the databse itself.
     * @return mysqli_result|object If it's a SELECT statement returns an
     *                              mysqli_result, otherwise returns an object
     *                              with the query's result information as
     *                              affected_rows, result (if was success or
     *                              not), and insert_id.
     */
    public static function query(
        string $sql, 
        string $rules = null, 
        $params = null,
        bool $isSelect = false
    )
    {
        if (!self::isValidParamsType($params)) {
            throw new InvalidArgumentException(
                'Params must be and array or and object.'
            );
        }

        $params = self::getOrderedParams($sql, $params);
        $sql = self::sqlToClassicBindingFormat($sql);

        $database = self::getConnection();
        $statement = $database->stmt_init();
        $result = new stdClass();
        $error = null;

        if ($statement !== false && $statement->prepare($sql)) {
            if ($rules !== null && $params !== null) {
                $statement->bind_param($rules, ...$params);
            }

            $executeResult = $statement->execute();

            if ($isSelect) {
                $result = $statement->get_result();
            } else {
                $result->result = $executeResult;
                $result->affected_rows = $statement->affected_rows;
                $result->insert_id = $statement->insert_id;
            }

            if ($statement->error) {
                $error = $statement->error;
            }

            $statement->close();
        }

        if ($error === null && $database->error) {
            $error = $database->error;
        }

        if (!static::$isTransaction) {
            self::closeConnection();
        }

        if ($error !== null) {
            throw new DatabaseException($error);
        }

        return $result;
    }

    /**
     * It does parameterized SELECT queries to database for preventing SQL
     * injection.
     * See examples of prepared statements at:
     * https://www.php.net/manual/es/mysqli.prepare.php
     * 
     * @param  string $sql        The SQL query. It have to come with the same
     *                            convention of mysqli's function bind_param, 
     *                            that is to say, with = ? in the paramethers
     *                            you want to bind or with a custom way that
     *                            consist in putting the key of the array|object
     *                            that will be included in the query e.g.
     *                              classic binding:
     *                                  SELECT * FROM users WHERE id = ?
     *                              custom binding by keys' names:  
     *                                  SELECT * FROM users WHERE id = {userId}
     * @param  string $rules      The rules of bind_param which indicate the
     *                            type of each paramether, They must come in the
     *                            same order as they appear in the sql query
     *                            e.g. for a string param and a doblue param in
     *                            the query use:
     *                                'sd'
     * @param  array  $params     The query arguments in the same order as the 
     *                            SQL query and rules when you use classic
     *                            binding, otherwise you can send a key-value
     *                            array or an abject with the paramethers in
     *                            any order.
     * 
     * @throws DatabaseException  When there is an error with the SQL statement
     *                            or with the databse itself.
     * @return mysqli_result      The result of the query.
     */
    public static function selectBySql(
        string $sql, 
        string $rules = null, 
        $params = null
    )
    {
        return self::query($sql, $rules, $params, true);
    }

    /**
     * It does parameterized SELECT queries to database for preventing SQL
     * injection. Using this function it would be expected to receive more than
     * one records in the response.
     * See examples of prepared statements at:
     * https://www.php.net/manual/es/mysqli.prepare.php
     * 
     * @param  string $sql        The SQL query. It have to come with the same
     *                            convention of mysqli's function bind_param, 
     *                            that is to say, with = ? in the paramethers
     *                            you want to bind or with a custom way that
     *                            consist in putting the key of the array|object
     *                            that will be included in the query e.g.
     *                              classic binding:
     *                                  SELECT * FROM users WHERE id = ?
     *                              custom binding by keys' names:  
     *                                  SELECT * FROM users WHERE id = {userId}
     * @param  string $rules      The rules of bind_param which indicate the
     *                            type of each paramether, They must come in the
     *                            same order as they appear in the sql query
     *                            e.g. for a string param and a doblue param in
     *                            the query use:
     *                                'sd'
     * @param  array  $params     The query arguments in the same order as the 
     *                            SQL query and rules when you use classic
     *                            binding, otherwise you can send a key-value
     *                            array or an abject with the paramethers in
     *                            any order.
     * 
     * @throws DatabaseException  When there is an error with the SQL statement
     *                            or with the databse itself.
     * @return array              The result of the query in an array.
     */
    public static function getArrayBySql(
        string $sql, 
        string $rules = null, 
        $params = null
    ) 
    {
        $result = self::selectBySql($sql, $rules, $params);

        if (!$result) {
            return [];
        }

        $array = array();

        while ($object = $result->fetch_object())
        {
            $array[] = $object;
        }

        return $array;
    }

    /**
     * It does parameterized SELECT queries to database for preventing SQL
     * injection. Using this function it would be expected to receive only one
     * record in the response.
     * See examples of prepared statements at:
     * https://www.php.net/manual/es/mysqli.prepare.php
     *
     * @param  string $sql        The SQL query. It have to come with the same
     *                            convention of mysqli's function bind_param, 
     *                            that is to say, with = ? in the paramethers
     *                            you want to bind or with a custom way that
     *                            consist in putting the key of the array|object
     *                            that will be included in the query e.g.
     *                              classic binding:
     *                                  SELECT * FROM users WHERE id = ?
     *                              custom binding by keys' names:  
     *                                  SELECT * FROM users WHERE id = {userId}
     * @param  string $rules      The rules of bind_param which indicate the
     *                            type of each paramether, They must come in the
     *                            same order as they appear in the sql query
     *                            e.g. for a string param and a doblue param in
     *                            the query use:
     *                                'sd'
     * @param  array  $params     The query arguments in the same order as the 
     *                            SQL query and rules when you use classic
     *                            binding, otherwise you can send a key-value
     *                            array or an abject with the paramethers in
     *                            any order.
     * 
     * @throws DatabaseException  When there is an error with the SQL statement
     *                            or with the databse itself.
     * @return object|null        The unique result of the query if any,
     *                            otherwise null.
     */
    public static function getObjectBySql(
        string $sql, 
        string $rules = null, 
        $params = null
    ) 
    {
        $result = self::selectBySql($sql, $rules, $params);

        if (!$result) {
            return null;
        }

        return $result->fetch_object();
    }

    /**
     * It does parameterized SELECT queries to database for preventing SQL
     * injection. Using this function it would be expected to receive a unique
     * integer value in the response.
     * See examples of prepared statements at:
     * https://www.php.net/manual/es/mysqli.prepare.php
     *
     * @param  string $sql        The SQL query. It have to come with the same
     *                            convention of mysqli's function bind_param, 
     *                            that is to say, with = ? in the paramethers
     *                            you want to bind or with a custom way that
     *                            consist in putting the key of the array|object
     *                            that will be included in the query e.g.
     *                              classic binding:
     *                                  SELECT COUN(*) FROM users WHERE id = ?
     *                              custom binding by keys' names:  
     *                                  SELECT COUNT(*) FROM users
     *                                  WHERE id = {userId}
     * @param  string $rules      The rules of bind_param which indicate the
     *                            type of each paramether, They must come in the
     *                            same order as they appear in the sql query
     *                            e.g. for a string param and a doblue param in
     *                            the query use:
     *                                'sd'
     * @param  array  $params     The query arguments in the same order as the 
     *                            SQL query and rules when you use classic
     *                            binding, otherwise you can send a key-value
     *                            array or an abject with the paramethers in
     *                            any order.
     * 
     * @throws DatabaseException  When there is an error with the SQL statement
     *                            or with the databse itself.
     * @return integer            The result of the count.
     */
    public static function getCountBySql(
        string $sql, 
        string $rules = null, 
        $params = null
    ) 
    {
        $result = self::selectBySql($sql, $rules, $params);

        if (!$result) {
            return 0;
        }

        return intval($result->fetch_row()[ 0 ]);
    }

    /**
     * Start a MySQL transaction opening a connection.
     * 
     * @return bool True if fhe initiation of the transaction was succesful.
     */
    public static function startTransaction()
    {
        if (self::$connection !== null || self::$isTransaction) {
            throw new DatabaseException(
                "There is another started transaction"
            );
        }

        $database = self::getConnection();
        static::$isTransaction = true;

        $initiationResult = $database->begin_transaction();

        if (!$initiationResult) {
            static::$isTransaction = false;
        }

        return $initiationResult;
    }

    /**
     * Undo all performed during the transaction and close the connection.
     * 
     * @return bool True if the rollback of the transaction was succesful.
     */
    public static function rollbackTransaction()
    {
        if (self::$connection === null || !self::$isTransaction) {
            throw new DatabaseException(
                "There is no previous started transaction"
            );
        }

        $database = self::getConnection();

        $result = $database->rollback();

        self::finishTransaction();

        return $result;
    }

    /**
     * Save all performed during the transaction and close the connection.
     * 
     * @return bool True if the commit of the transaction was succesful.
     */
    public static function commitTransaction()
    {
        if (self::$connection === null || !self::$isTransaction) {
            throw new DatabaseException(
                "There is no previous started transaction"
            );
        }

        $database = self::getConnection();

        $result = $database->commit();

        self::finishTransaction();

        return $result;
    }

    /**
     * Set isTransaction flag to false and close the connection.
     */
    private static function finishTransaction()
    {
        static::$isTransaction = false;
        self::closeConnection();
    }
}
?>