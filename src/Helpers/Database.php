<?php

namespace App\Helpers;

use PDO;
use PDOException;


class Database {

    //**************************************************************************
    // ATRIBUTOS                                                               *
    //**************************************************************************
    private $_connection;
    private static $_instance;

    //**************************************************************************
    // CONSTRUCTOR                                                             *
    //**************************************************************************
    private function __construct() {
        try {
            $this->_connection = new PDO('mysql:host=' . DB_HOST . '; dbname=' . DB_NAME, DB_USER, DB_PASS);                        
            $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->_connection->exec("SET CHARACTER SET utf8");
        } catch (PDOException $e) {            
            echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
            exit();
        }
    }

    //**************************************************************************
    // METODOS                                                                 *
    //**************************************************************************
    public function prepare($sql) {
        return $this->_connection->prepare($sql);
    }

    public static function instance() {
        if (!isset(self::$_instance)) {
            $class = __CLASS__;
            self::$_instance = new $class();
        }
        return self::$_instance;
    }

    public function __clone() {
        trigger_error('La clonación de este objeto no está permitida', E_USER_ERROR);
    }

}