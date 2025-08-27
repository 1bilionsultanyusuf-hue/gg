<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'apps_todos_db';
    private $username = 'root'; // sesuaikan dengan username MySQL Anda
    private $password = ''; // sesuaikan dengan password MySQL Anda
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>