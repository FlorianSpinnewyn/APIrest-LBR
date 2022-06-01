<?php



function getFilesAll( $response,  $args) {
    $sql ="SELECT * FROM fichiers";

    try {
        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->query($sql);
        $files = $stmt->fetchAll(PDO::FETCH_OBJ);

        $db = null;
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

function AddFile( $request,$response,  $args) {
    $nom=$request->getParam("nom");
    $mail=$request->getParam("mail");
    $auteur=$request->getParam("auteur");
    $taille=$request->getParam("taille");
    $dure=$request->getParam("dure");
    $type=$request->getParam("type");
    $date = date('Y-m-d H:i:s');

    $sql ="INSERT INTO fichiers (nom_video,mail,nom_prenom_auteur,taille,duree,date,type) VALUE (:nom,:mail,:auteur,:taille,:dure,:date,:type)";

    try {
        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':mail', $mail);
        $stmt->bindParam(':auteur', $auteur);
        $stmt->bindParam(':taille', $taille);
        $stmt->bindParam(':dure', $dure);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':date', $date);

        $result=$stmt->execute();

        $db = null;
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