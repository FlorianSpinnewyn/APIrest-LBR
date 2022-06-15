<?php

function getLog($request,$response,$args){
    $sql ="SELECT * FROM log";

    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->query($sql);
        $files = $stmt->fetchAll(PDO::FETCH_OBJ);

        $DB = null;
        $response->getBody()->write(json_encode($files));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(200);
    }catch (PDOException $e) {
        $error = array(
            "message"=> $e->getMessage()
        );
    }
    $response->getBody()->write(json_encode($error));
    return $response
        ->withHeader('content-type', 'application/json')
        ->withStatus(400);
}


function addLog($type,$status){
    $date =  date("Y-m-d H:i:s");
    if(isset($_SESSION['id'])) {
        $id = $_SESSION['id'];
    }else{
        $id = "";
    }
    $sql = "INSERT INTO log (type,date,id_user,status_code) VALUES ('$type','$date','$id','$status')";

    try {
        $DB = new DB();
        $conn = $DB->connect();
        $stmt = $conn->prepare($sql);

        $result = $stmt->execute();

        $DB = null;
        return $result;
    } catch (PDOException $e) {
        $error = array(
            "message" => $e->getMessage()
        );
    }
    return $error;
}