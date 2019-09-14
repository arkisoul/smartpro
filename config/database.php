<?php
require_once 'config.php';
/**
 * Database Setting and Other Operations
 */
class Database
{
    private $conn;
    public $baseurl;

    public function __construct()
    {
        # code...
        $config     = new Config();
        $dbdetails  = $config->DBDetails();
        $this->conn = sqlsrv_connect($dbdetails['ServerName'], $dbdetails['ConnectionInfo']);

        if ($this->conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $paths         = $config->BasicPaths();
        $this->baseurl = $paths['baseurl'];
    }

    public function getBaseUrl()
    {
        return $this->baseurl;
    }

    public function Query($query)
    {
        $result = sqlsrv_query($this->conn, $query);
        return $result;
    }

    public function GetRow($query)
    {
        # code...
        $result = sqlsrv_query($this->conn, $query) or die($query);
        $row    = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        return $row;
    }

    public function GetRows($query)
    {
        # code...
        $result = sqlsrv_query($this->conn, $query) or die($query);
        $row    = sqlsrv_fetch_array($result);
        return $row;
    }

    public function NumberRow($query)
    {
        # code...
        $params  = array();
        $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
        $result  = sqlsrv_query($this->conn, $query, $params, $options) or die($query);
        $row     = sqlsrv_num_rows($result);
        return $row;
    }

    public function GetResults($query)
    {
        # code...
        $result = sqlsrv_query($this->conn, $query);
        $data   = array();

        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }

    public function Insert($table, $fields, $values)
    {
        # code...
        /*
        Inserts data in the database via SQL query.
        Returns the auto generated id on successful transaction
        Returns FALSE if there is an error.
         */

        $query            = "INSERT INTO " . $table . " (" . implode(",", $fields) . ") VALUES('" . implode("','", $values) . "')";
        $this->last_query = $query;
        $id               = sqlsrv_query($this->conn, $query);
        // $id = $this->sqlsrv_insert_id();
        return $id;
    }

    public function SimpleInsert($query)
    {
        $id = sqlsrv_query($this->conn, $query);
        return $id;
    }

    public function sqlsrv_insert_id()
    {
        # code...
        $id  = 0;
        $res = sqlsrv_query($this->conn, "SELECT @@identity AS id");
        if ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
            $id = $row["id"];
        }
        return $id;
    }

    public function QueryDML($query)
    {
        # code...
        /*
        Updates data in the database via SQL query.
        Returns the number or row affected or true if no rows needed the update.
        Returns FALSE if there is an error.
         */

        $this->last_query = $query;
        $params           = array("updated data", 1);
        $result           = sqlsrv_query($this->conn, $query, $params);
        $rows             = sqlsrv_rows_affected($result);

        if ($rows >= 0) {
            $response = array(
                'Success' => true,
                'Row'     => $rows,
            );
            return $response;
        } else {
            $response = array(
                'Success' => false,
                'Row'     => $rows,
            );
            return $response;
        }
    }

    public function SqlDateFormat($time)
    {
        # code...
        /*
        Returns the date in a format for input into the database. You can pass
        this function a timestamp value such as time() or a string value
        such as '04/14/2003 5:13 AM'
         */
        if (gettype($time) == 'string') {
            $time = strtotime($time);
        } else {
            $time = time();
        }

        return date('Y-m-d H:i:s', $time);
    }

    public function HRDate($time)
    {
        # code...
        /*
        Returns the date in a format for human reading. You can pass
        this function a timestamp value such as time() or a string value
        such as '04/14/2003 5:13 AM'
         */
        if (gettype($time) == 'string') {
            $time = strtotime($time);
        } else {
            $time = time();
        }

        return date('d/m/Y h:i:s a', $time);
    }

    public function LastError()
    {
        return sqlsrv_errors();
    }

    public function PrintBaseUrl()
    {
        echo $this->getBaseUrl();
    }

    # Start a sqlsrv transaction
    public function BeginTransaction()
    {
        return sqlsrv_begin_transaction($this->conn);
    }

    # Commit the transaction on successful execution
    public function Commit()
    {
        return sqlsrv_commit($this->conn);
    }

    # Rollback the executed transaction if any of them fails, otherwise.
    public function Rollback()
    {
        return sqlsrv_rollback($this->conn);
    }
}
