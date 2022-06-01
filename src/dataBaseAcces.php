<?php

class DB {
    private $host = '54.38.242.231:3306';
    private $user = 'florian';
    private $pass = 'Lufy6840';
    private $dbName = 'DRIVE';

    public function connect() {
        $conn_str="mysql:host=$this->host;dbname=$this->dbName";
        $conn = new PDO($conn_str, $this->user, $this->pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

        return $conn;
    }

}