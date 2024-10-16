<?php
require_once __DIR__ . '/Factory.php';
class Database
{
    private ?PDO $db = null;
    private string $exception = '';
    
    private static ?Database $database = null;

    /** Metoda koja omogucava kreiranje singleton objekta klase Database. */
    public static function getInstance()
    {
        if (self::$database === null) {
            self::$database = new Database(DBNAME, HOST, USER, PASS, PORT);
        }
        return self::$database;
    }

    /** Database konstruktor. */
    public function __construct(
        ?string $dbname = null,
        ?string $host = null,
        ?string $user = null,
        ?string $pass = null, 
        ?int $port = null
    ) {
        try {
            if ($dbname === null || $host === null || $user === null || $pass === null || $port === null) {
                $this->db = new PDO(
                    'mysql:dbname='.DBNAME.';host='.HOST.';port='.PORT,
                    USER,
                    PASS,
                    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'")
                );
            }
            else {
                $this->db = new PDO(
                    'mysql:dbname='.$dbname.';host='.$host.';port='.$port.';',
                    $user,
                    $pass,
                    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'")
                );
            }
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $this->exception = $e->getMessage();
        }
    }

    private string $query = '';
    private mixed $result = null;

    /**
     * SELECT metoda vraca niz objekata za zadati naziv klase.
     *
     * @param string $class - ime klase.
     * @param string $query - sql upit bez parametara.
     * @param array $params - niz parametara koji se vezuju za upit.
     * @return array<mixed> $class[] - niz objekata.
     */
    public function select(string $class, string $query, array $params = []): array
    {
        $result = $this->db->prepare($query);
        
        foreach ($params as $key => &$val) {
            $result->bindParam($key, $val);
        }
        $result->execute();

        Factory::autoload($class);
        $result->setFetchMode(PDO::FETCH_CLASS, $class);
        $arr = [];
        while ($object = $result->fetch()) {
            array_push($arr, $object);
        }
        return $arr;
    }

    /**
     * SELECT metoda vraca zapise u vidu asocijativnog niza.
     *
     * @param string $query - sql upit bez parametara
     * @param array $params - niz parametara koji se vezuju za upit
     * @return mixed
     */
    public function selectQuery(string $query, array $params = []): mixed
    {
        $result = $this->db->prepare($query);
    
        foreach($params as $key => &$val)
            $result->bindParam($key, $val);

        $result->execute();
        $result = $result->fetch();
        return $result;
    }

    /**
     * INSERT metoda koja vrsi unos podataka u bazu
     * ako se zada query_select upit, proverava se prvo da li
     * zapis postoji, a ako postoji ne upisuje se nista u bazu.
     *
     * @param string $class - ime klase.
     * @param string $query_insert - upit za insert
     * @param array $params_insert - niz parametara koji se vezuju za insert upit
     * @param string $query_select - upit za select
     * @param array $params_select - niz parametara koji se vezuju za insert upit
     * @return bool
     */
    public function insert(
        string $class,
        string $query_insert,
        array $params_insert = [],
        string $query_select = '',
        array $params_select = []
    ): bool {
        if (strlen($query_select) > 0) {
            if (count(self::select($class, $query_select, $params_select)) > 0) {
                return false;
            }
        }
        $this->result = $this->db->prepare($query_insert);

        foreach ($params_insert as $key => &$val) {
            $this->result->bindParam($key, $val);
        }
        
        return $this->result->execute();
    }

    /**
     * UPDATE metoda vrsi azuriranje podataka u bazi
     * ako se zada query_select upit, proverava se prvo da li
     * zapis postoji, a ako ne postoji ne vrsi se azuriranje
     *
     * @param string $class - ime klase.
     * @param string $query_update - upit za update
     * @param $params_update - niz parametara koji se vezuju za update upit
     * @param string $query_select - upit za select
     * @param array $params_select - niz parametara koji se vezuju za insert upit
     * @return mixed
     */
    public function update(
        string $class,
        string $query_update,
        array $params_update, 
        string $query_select = '',
        array $params_select = []
    ): mixed {
        if (strlen($query_select) > 0) {
            $arr = self::select($class, $query_select, $params_select);
            if(count($arr) === 0) {
                return null;
            }
        }
        $this->result = $this->db->prepare($query_update);
        
        foreach ($params_update as $key => &$val) {
            $this->result->bindParam($key, $val);
        }
        $this->result->execute();
        
        $arr = [];
        if (strlen($query_select) > 0) {
            $arr = self::select($class, $query_select, $params_select);
            if(count($arr) === 0)
                return null;
        }
        foreach($arr as $a) {
            return $a;
        }
        return null;
    }

    /**
     * DELETE metoda brise sve podatke iz baze za zadati upit
     * @param string $query
     * @param array $params_delete
     * @return bool
     */
    public function delete(string $query, array $params_delete = []): bool
    {
        $result = $this->db->prepare($query);

        foreach($params_delete as $key => &$val)
            $result->bindParam($key, $val);
        $result->execute();
        return true;
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Initiates a transaction.
     * @link http://php.net/manual/en/pdo.begintransaction.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Commits a transaction.
     * @link http://php.net/manual/en/pdo.commit.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Rolls back a transaction.
     * @link http://php.net/manual/en/pdo.rollback.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function rollBack(): bool
    {
        return $this->db->rollBack();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Returns the ID of the last inserted row or sequence value
     * @link http://php.net/manual/en/pdo.lastinsertid.php
     * @param string $name [optional] <p>
     * Name of the sequence object from which the ID should be returned.
     * </p>
     * @return string If a sequence name was not specified for the <i>name</i>
     * parameter, <b>PDO::lastInsertId</b> returns a
     * string representing the row ID of the last row that was inserted into
     * the database.
     * </p>
     * <p>
     * If a sequence name was specified for the <i>name</i>
     * parameter, <b>PDO::lastInsertId</b> returns a
     * string representing the last value retrieved from the specified sequence
     * object.
     * </p>
     * <p>
     * If the PDO driver does not support this capability,
     * <b>PDO::lastInsertId</b> triggers an
     * IM001 SQLSTATE.
     */
    public function lastInsertId(?string $name = null): mixed
    {
        if($name === null) {
            return $this->db->lastInsertId();
        }

        return $this->db->lastInsertId($name);
    }
}