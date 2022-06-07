<?php


function getAllTags($request,$response,$args){

    $res = isSession($request,$response,$args);

    if($res ){
        return $res;
    }

    if($_SESSION['role'] == 0){
        return getAllowedTags($request,$response,$args);
    }


    $sql ="SELECT id_tag,nom_tag,id_user,nom_categorie FROM tags";

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


function getAllowedTags($request, $response, $args)
{
    $sql = "(SELECT id_tag,nom_tag,id_user,nom_categorie FROM tags where id_user = " . $_SESSION['id']. ")UNION(SELECT id_tag,nom_tag,id_user,nom_categorie FROM tags where id_tag in(select id_tag from autoriser where id_user = " . $_SESSION['id'] . "))";

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
            "message" => $e->getMessage()
        );
    }
    return $response
        ->withHeader('content-type', 'application/json')
        ->withStatus(400);


}


function addTag( $request,$response,  $args) {
    $res = authFilesTags($request,$response,$args);
    if($res ){
        return $res;
    }
    $nom_tag=$request->getParam("nom_tag");
    $id_user=$request->getParam("id_user");
    $nom_categorie=$request->getParam("nom_categorie");
    
    $sql ="INSERT INTO tags (nom_tag,id_user,nom_categorie) VALUE (:nom_tag,:id_user,:nom_categorie)";

    try {
        $DB = new DB();
        $conn = $DB->connect();
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':id_user',  $id_user);
        $stmt->bindParam(':nom_tag', $nom_tag);
        $stmt->bindParam(':nom_categorie', $nom_categorie);

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
    $res = authFilesTags($request,$response,$args);
    if($res ){
        return $res;
    }

    $res2 = checkIfOwnedTag($request,$response,$args);
    if($res2 ){
        return $res2;
    }


    $tagDelete = $args["tag"];

    $sql ="DELETE from tags WHERE id_tag = $tagDelete";

    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();

        $DB = null;
        $response->getBody()->write(json_encode($result));
        deleteTagInFiles($tagDelete);
        deleteTagsInUsers($tagDelete);
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


function modifyTag( $request,$response, $args) {
    $res = authFilesTags($request,$response,$args);
    if($res ){
        return $res;
    }

    $res2 = checkIfOwnedTag($request,$response,$args);
    if($res2 ){
        return $res2;
    }

    $tag = $args["tag"];
    $selection =$request->getParam("selection");
    $modif = $request->getParam("modif");

    $sql ="UPDATE tags SET $selection= '$modif' WHERE id_tag=$tag";

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


function deleteUserInTags($user,$response){
    $sql ="UPDATE tags SET id_user= 1 WHERE id_user=$user";
    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();
        $response->getBody()->write(json_encode($result));
        $DB = null;

    }catch (PDOException $e) {
    }
    $sql ="DELETE FROM autoriser WHERE id_user=$user";
    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();
        $response->getBody()->write(json_encode($result));
        $DB = null;
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(200);
    }catch (PDOException $e) {
    }



}


function deleteCategorieInTags($categorie){
    $sql ="UPDATE tags SET nom_categorie = 'autre' WHERE nom_categorie='$categorie'";
    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();

        $DB = null;
        return $result;
    }catch (PDOException $e) {
        return array(
            "message"=> $e->getMessage()
        );
    }
}


function changeCategoryInTags($categorie,$newCategorie){
    $sql ="UPDATE `tags` SET `nom_categorie` = '$newCategorie' WHERE `nom_categorie`='$categorie'";

    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();

        $DB = null;
        return $result;
    }catch (PDOException $e) {
        return array(
            "message"=> $e->getMessage()
        );
    }
}