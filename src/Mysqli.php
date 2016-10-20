<?php

namespace Webgarden\Mysqli;

/**
 * Class Mysqli adapter
 *
 * @package Webgarden\Mysqli
 */
class Mysqli extends \mysqli
{
	const SQL_MODE = "SELECT @@sql_mode";
	const ONLY_FULL_GROUP_BY = "ONLY_FULL_GROUP_BY";
	
	/**
	 * Mysqli constructor.
	 *
	 * @param string $host
	 * @param string $username
	 * @param string $passwd
	 * @param string $dbname
	 * @param int $port
	 * @param null $socket
	 */
	public function __construct($host, $username, $passwd, $dbname, $port = 3306, $socket = null)
	{
		//Set strict mode in mysqli -> throws \mysqli_sql_exception on error
		mysqli_report(MYSQLI_REPORT_STRICT);
		
		try {
			parent::__construct($host, $username, $passwd, $dbname, $port, $socket);
		} catch (\mysqli_sql_exception $e) {
			$this->triggerException($e);
		}
	}
	
	/**
	 * @param \mysqli_sql_exception $e
	 *
	 * @throws AccessDeniedException
	 * @throws ConnectionRefusedException
	 * @throws UnknownDatabaseException
	 */
	private function triggerException(\mysqli_sql_exception $e)
	{
		if ($e->getCode() == 2002) {
			throw new ConnectionRefusedException();
		} elseif ($e->getCode() == 1698 || $e->getCode() == 1045) {
			throw new AccessDeniedException();
		} elseif ($e->getCode() == 1049) {
			throw new UnknownDatabaseException();
		} else {
			print $e->getCode();
			throw  $e;
		}
	}
	
	/**
	 * @param $tableName
	 * @param bool $throws
	 *
	 * @return bool
	 * @throws TableNotExistsException
	 */
	public function tableExists($tableName, $throws = false)
	{
		if ($this->query("SHOW TABLES LIKE '$tableName';")->fetch_row() == true) {
			return true;
		}
		
		if ($throws) {
			throw  new TableNotExistsException("Table name: {$tableName}");
		}
		
		return false;
	}
	
	/**
	 * @return bool
	 */
	public function checkOnlyFullGroupBy()
	{
		return strpos($this->getSqlMode(), self::ONLY_FULL_GROUP_BY) !== false;
	}
	
	/**
	 * @return mixed
	 */
	public function getSqlMode()
	{
		return $this->query(self::SQL_MODE)->fetch_row()[0];
	}
	
}

class MysqliException extends \Exception
{
	
}

class ConnectionRefusedException extends MysqliException
{
	
}

class AccessDeniedException extends MysqliException
{
	
}

class UnknownDatabaseException extends MysqliException
{
	
}

class TableNotExistsException extends MysqliException
{
	
}
