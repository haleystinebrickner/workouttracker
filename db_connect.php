<?php

$server = "75.65.129.181,1433";
$database = "PersonalTrainerDB";
$username = "htmluser";
$password = "html123";

try {
    $dsn = "odbc:Driver={ODBC Driver 18 for SQL Server};".
           "Server=75.65.129.181,1433;".
           "Database=PersonalTrainerDB;".
           "TrustServerCertificate=yes;".
           "Encrypt=no;".
           "LoginTimeout=30;".
           "ConnectRetryCount=3";
    
    $conn = new PDO($dsn, $username, $password, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false // Disable emulated prepared statements
    ));
    
    function executeQuery($sql, $params = [], $types = []) {
        global $conn;
        try {
            $stmt = $conn->prepare($sql);
            
            // Bind parameters with explicit types if provided
            if (!empty($types)) {
                foreach ($params as $key => $value) {
                    $type = isset($types[$key]) ? $types[$key] : PDO::PARAM_STR;
                    $stmt->bindValue($key + 1, $value, $type);
                }
                $stmt->execute();
            } else {
                $stmt->execute($params);
            }
            
            return $stmt;
        } catch(PDOException $e) {
            throw new PDOException("Query failed: " . $e->getMessage());
        }
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

