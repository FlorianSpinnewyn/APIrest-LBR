<?php

//get all the categories
function getAllCategories($response,$args){
    $sql ="SELECT nom_categorie FROM categories";

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

function addCategorie( $request,$response,  $args)
{
    $res = authCategory($request,$response,$args);  //check if the user is not a reader
    if($res ){
        return $res;
    }
    $category = $request->getParam("category");

    $sql = "INSERT INTO categories (nom_categorie) VALUES ('$category')";

    try {
        $DB = new DB();
        $conn = $DB->connect();
        $stmt = $conn->prepare($sql);

        $result = $stmt->execute();

        $DB = null;
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(201);
    } catch (PDOException $e) {
        $error = array(
            "message" => $e->getMessage()
        );
    }
    $response->getBody()->write(json_encode($error));
    return $response
        ->withHeader('content-type', 'application/json')
        ->withStatus(400);
}
//function that renames a category
function renameCategorie($request,$response,$args){
    $res = authCategory($request,$response,$args);
    if($res ){
        return $res;
    }
    $categorie = $args["category"];
    $newCategorie= $request->getParam("category");


    $sql ="insert into categories VALUES ( '$newCategorie') ";

    try {

        $DB = new DB();
        $conn = $DB->connect();
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();
        //because category is a primary key, we need create a new one, associate the tag to the new category and delete the old one
        changeCategoryInTags($categorie,$newCategorie);
        deleteAncientCategorie($request,$response,$args);
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

//function that deletes a category that was changed
function deleteAncientCategorie($request, $response, $args)
{
    $categoryDel = $args["category"];

    $sql ="DELETE FROM `categories` WHERE  `nom_categorie`='$categoryDel';";
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

//function that deletes a category
function deleteCategorie( $request,$response,  $args) {
    $res = authCategory($request,$response,$args);
    if($res ){
        return $res;
    }
    //verify if the category is set
    if(!isset($args["category"])){
        $error = array(
            "message"=> "category not found"
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(404);
    }
    //you can't delete categorie autre
    if($args["category"] == "autre"){
        $error = array(
            "message"=> "category autre can't be deleted"
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(400);
    }
    $categoryDel = $args["category"];


    $sql ="DELETE FROM `categories` WHERE  `nom_categorie`='$categoryDel';";

    try {
        $DB = new DB();
        $conn = $DB->connect();
        deleteCategorieInTags($categoryDel); //delete the category in the tags table and set the tag's category to autre
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

