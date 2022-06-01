<?php


function getAllTags($response,$args){
    $sql ="SELECT nom_tag,mail,nom_categories FROM tags";

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

function addTag( $request,$response,  $args) {

    $nom_tag=$request->getParam("nom_tag");
    $mail=$request->getParam("mail");
    $nom_categories=$request->getParam("nom_categories");
    
    $sql ="INSERT INTO tags (nom_tag,mail,nom_categories) VALUE (:nom_tag,:mail,:nom_categories)";

    try {
        $DB = new DB();
        $conn = $DB->connect();
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':mail',  $mail);
        $stmt->bindParam(':nom_tag', $nom_tag);
        $stmt->bindParam(':nom_categories', $nom_categories);

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

function deleteTag( $request,$response,  $args) {
    $tagDelete = $args["tag"];
    $sql ="DELETE from tags WHERE nom_tag = $tagDelete";

    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();

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

function MoveCategorie( $request,$response, $args) {
    $tag = $args["tag"];
    $newCategorie=$args["categorie"];

    $sql ="UPDATE tags SET nom_categories= '$newCategorie' WHERE nom_tag='$tag'";

    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();

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

function renameTag( $request,$response, $args) {
    $tag = $args["tag"];
    $newTag=$args["newTag"];

    $sql ="UPDATE tags SET nom_tag= '$newTag' WHERE nom_tag='$tag'";

    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();

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