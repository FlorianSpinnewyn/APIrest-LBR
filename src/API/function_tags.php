<?php

//get all the tags depending on the user
function getAllTags($request,$response,$args){

    $res = isSession($request,$response,$args); //check if the user is logged in

    if($res ){
        return $res;
    }
    //check if the user is a guest
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

//get all the tags depending on the user
function getAssignedTags($request, $response, $args)
{
    $res = isSession($request,$response,$args);

    if($res ){
        return $res;
    }
    $user = $args['user'];
    $sql = "(SELECT id_tag,nom_tag,id_user,nom_categorie FROM tags where id_user = '$user')UNION(SELECT id_tag,nom_tag,id_user,nom_categorie FROM tags where id_tag in(select id_tag from autoriser where id_user = '$user'))";

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

//add a tag to the database
function addTag( $request,$response,  $args) {
    $res = authFilesTags($request,$response,$args);
    if($res ){
        return $res;
    }
    $nom_tag=$request->getParam("nom_tag");
    $id_user=$request->getParam("id_user");
    $nom_categorie=$request->getParam("nom_categorie");
    //check if the tag already exists in the same category
    if(isAlreadyExist($nom_tag,$id_user,$nom_categorie)){
        $error = array(
            "message" => "Tag already exist"
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(400);
    }
    
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
        $response->getBody()->write(json_encode($conn->lastInsertId()));

        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(201);
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

//check for already existing tag in the same category
function isAlreadyExist(mixed $nom_tag, mixed $id_user, mixed $nom_categorie)
{
    $sql ="SELECT id_tag FROM tags WHERE nom_tag = '$nom_tag' AND nom_categorie = '$nom_categorie'";
    try {
        $DB = new DB();
        $conn = $DB->connect();
        $stmt = $conn->query($sql);
        $files = $stmt->fetchAll(PDO::FETCH_OBJ);
        $DB = null;
        if(is_countable($files)  and count($files) > 0){
            return true;
        }
        return false;
        }
    catch (PDOException $e) {
        $error = array(
            "message"=> $e->getMessage()
        );
    }
}

//delete a tag from the database
function deleteTag( $request,$response,  $args) {
    $res = authFilesTags($request,$response,$args);
    if($res ){
        return $res;
    }
    //verify if the user has the right to delete the tag
    $res2 = checkIfOwnedTag($request,$response,$args);
    echo $res2;
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
        //delete this tags and all his relations to files
        deleteTagInFiles($tagDelete);
        //delete this tag that was assigned to an user
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

//change a tag in the database
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
    //check if the tag already exists in the category you want to change it in
     if($selection == "nom_categorie"){
        $sql ="Select nom_tag from tags where id_tag = $tag";
        try {
            $DB = new DB();
            $conn = $DB->connect();
            $stmt = $conn->query($sql);
            $nomTag = $stmt->fetchAll(PDO::FETCH_OBJ);
            $DB = null;

            if(isAlreadyExist($nomTag[0]->nom_tag,"test",$modif)) {
                $error = array(
                    "message" => "Tag already exist"
                );
                $response->getBody()->write(json_encode($error));
                return $response
                    ->withHeader('content-type', 'application/json')
                    ->withStatus(400);
            }
        }catch (PDOException $e) {
            $error = array(
                "message"=> $e->getMessage()
            );
        }


        }

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

//delete a category from the tag table
function deleteCategorieInTags($categorie){

    $sql ="UPDATE tags SET nom_categorie = 'autre' WHERE nom_categorie='$categorie' and nom_tag not IN (SELECT nom_tag FROM tags WHERE nom_categorie = 'autre');
    DELETE FROM tags WHERE nom_categorie = '$categorie' ;";
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

//change the category of certains tags
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


//delete a tag in the assigner table
function deleteFileInTags($id_file){
    $sql = "DELETE FROM `assigner` WHERE `id_file` = $id_file";
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