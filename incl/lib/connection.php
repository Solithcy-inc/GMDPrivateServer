<?php
//Requesting files
include dirname(__FILE__)."/../../config/connection.php";
if($maintenance == 1){
    exit("-1");
}
@header('Content-Type: text/html; charset=utf-8');
try {
    $db = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(
    PDO::ATTR_PERSISTENT => true
));
    //Set the PDO error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
catch(PDOException $e)
    {
    echo "Connection failed: " . $e->getMessage();
    }
?>