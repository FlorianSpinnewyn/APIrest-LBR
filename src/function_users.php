<?php


function getUsersAll( $response,  $args) {
    $sql ="SELECT mail,role FROM utilisateurs";

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

function addUser( $request,$response,  $args) {
    echo $request->getParam("mail");
    $mail=$request->getParam("mail");
    $nom_prenom=$request->getParam("nom_prenom");
    $mdp=$request->getParam("mdp");
    $descriptif=$request->getParam("descriptif");
    $role=$request->getParam("role");
    $mdpFinal=$request->getParam("mdpFinal");
    
    $sql ="INSERT INTO utilisateurs (mail,nom_prenom,mdp,descriptif,role,mdpFinal) VALUE (:mail,:nom_prenom,:mdp,:descriptif,:role,:mdpFinal)";

    try {
        $DB = new DB();
        $conn = $DB->connect();
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':mail',  $mail);
        $stmt->bindParam(':nom_prenom', $nom_prenom);
        $stmt->bindParam(':mdp', $mdp);
        $stmt->bindParam(':descriptif', $descriptif);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':mdpFinal', $mdpFinal);

        $result=$stmt->execute();

        $DB = null;
        $response->getBody()->write(json_encode($result));
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