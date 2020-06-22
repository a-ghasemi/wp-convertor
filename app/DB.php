<?php

namespace App;


class DB
{

    private $connection;

    private $server_name;
    private $server_port;
    private $username;
    private $password;
    private $db_name;

    public $state;
    public $error;
    public $on_demand;

    function __construct($server_name, $server_port, $username, $password, $db_name, $on_demand = false)
    {
        $this->server_name = $server_name;
        $this->server_port = $server_port;
        $this->on_demand = $on_demand;

        $this->db_name = $db_name;
        $this->username = $username;
        $this->password = $password;

        $this->error = null;
        $this->state = 'created';
    }

    public function connect(){
        $this->connection = new \mysqli($this->server_name, $this->username, $this->password, $this->db_name);

        if ($this->connection->connect_error) {
            $this->error = $this->connection->connect_error;
            $this->state = 'error';
            return;
        }

        $this->connection->autocommit(!$this->on_demand);
        $this->state = 'connected';
    }

    public function disconnect(){
        if($this->state !== 'connected') return;
        $this->connection->close();
    }

    public function commit(){
        if($this->on_demand) return true;

        if (!$this->connection->commit()) {
            $this->error = $this->connection->error;
//            $this->error = 'Transaction commit failed';
            $this->state = 'error';
            return false;
        }

        return true;
    }

    public function begin_transaction(){
        if($this->on_demand) return true;

        if (!$this->connection->begin_transaction()) {
            $this->error = $this->connection->error;
//            $this->error = 'Begin Transaction failed';
            $this->state = 'error';
            return false;
        }

        return true;
    }

    public function rollback(){
        if($this->on_demand) return false;

        if (!$this->connection->rollback()) {
            $this->error = $this->connection->error;
//            $this->error = 'Rollback Transaction failed';
            $this->state = 'error';
            return false;
        }

        return true;
    }

    public function insert($query){
        if (!$this->connection->query($query)) {
//        if ($this->connection->connect_error) {
            $this->error = $this->connection->error;
            $this->state = 'error';
            return false;
        }
        return $this->connection->insert_id;
    }

    public function select($query){
        $result = $this->connection->query($query);

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                yield $row;
            }
        }
        else {
            return null;
        }
    }

    public function select_all($query){
        $result = $this->connection->query($query);


        $ret = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $ret[] = $row;
            }
        }
        return $ret;
    }

    public function execute($query){
        if (!$this->connection->query($query)) {
            $this->error = $this->connection->error;
            $this->state = 'error';
            return false;
        }
        return true;
    }

    public function select_one($query){
        $result = $this->connection->query($query);

        if ($result && $result->num_rows > 0) {
            if($row = $result->fetch_assoc()) {
                return $row;
            }
        }
        else {
            return null;
        }
    }

}