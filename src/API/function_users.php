<?php


function getUsersAll($request,$response,$args) {
    $sql ="SELECT id_user,mail,role FROM utilisateurs";

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

function getUser($request,$response,  $args){
    $id_user = $args['user'];
    $sql ="SELECT id_user,mail,nom_prenom,role,descriptif,mdpFinal FROM utilisateurs WHERE id_user = $id_user";

    try {
        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->query($sql);
        $file = $stmt->fetch(PDO::FETCH_OBJ);

        $db = null;
        $response->getBody()->write(json_encode($file));
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
    $res = isAdmin($request,$response,  $args);
    if($res ){
        return $res;
    }
    $mail=$request->getParam("mail");
    $nom_prenom=$request->getParam("name_surname");
    $mdp=$request->getParam("password");
    $descriptif=$request->getParam("description");
    $role=$request->getParam("role");
    $mdpFinal=$request->getParam("is_pwd_final");

    $sql ="INSERT INTO `utilisateurs` ( `mail`, `nom_prenom`, `mdp`, `descriptif`, `role`, `mdpFinal`) VALUES ('$mail','$nom_prenom','$mdp','$descriptif',$role,$mdpFinal);";

    try {
        $DB = new DB();
        $conn = $DB->connect();
        $stmt = $conn->prepare($sql);


        $result=$stmt->execute();


        $response->getBody()->write(json_encode($result));

        $id_user = $conn->lastInsertId();
        addAllowedTagToUser2($id_user, $request,$response,  $args);
        $DB = null;
        return $response
            ->withHeader('content-type', 'application/json')
            ->withHeader('location', 'http://localhost:8080/api/users/'.$id_user)
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


function updateUser($request,$response,$args){
    $res = isAdmin($request,$response,  $args);
    if($res ){
        return $res;
    }
    $user =$args['user'];
    $selection=$request->getParam("selection");

    $modif = $request->getParam("modif");


    $sql ="UPDATE `utilisateurs` SET `$selection`='$modif' WHERE `id_user`=$user";

    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->query($sql);
        $files = $stmt->fetchAll(PDO::FETCH_OBJ);
        $result=$stmt->execute();
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


function deleteUser( $request,$response,  $args) {
    $res = isAdmin($request,$response,  $args);
    if($res ){
        return $res;
    }


    $userDel = $args["user"];


    $sql ="DELETE from utilisateurs WHERE id_user = $userDel";

    try {
        deleteUserInTags($userDel,$response);
        deleteUserInFiles($userDel);
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

function addAllowedTagToUser($request,$response,$args){
    $res = isAdmin($request,$response,  $args);
    if($res ){
        return $res;
    }

    $user = $args["user"];
    $tags = $request->getParam("tags");

    for($i = 0;$i < count($tags);$i++) {
        $sql = "INSERT INTO autoriser (id_tag,id_user) VALUE ($tags[$i],$user)";

        try {
            $DB = new DB();
            $conn = $DB->connect();
            $stmt = $conn->prepare($sql);

            $result = $stmt->execute();

            $DB = null;
            $response->getBody()->write(json_encode($result));
            //return $response->withHeader('content-type', 'application/json')->withStatus(200);
        } catch (PDOException $e) {
            $error = array(
                "message" => $e->getMessage()
            );
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('content-type', 'application/json')->withStatus(400);
        }


    }

}


function addAllowedTagToUser2($id,$request,$response,$args){


    $tags = $request->getParam("tags");

    for($i = 0;$i < count($tags);$i++) {
        $sql = "INSERT INTO autoriser (id_tag,id_user) VALUE ($tags[$i],$id)";

        try {
            $DB = new DB();
            $conn = $DB->connect();
            $stmt = $conn->prepare($sql);

            $result = $stmt->execute();

            $DB = null;
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('content-type', 'application/json')->withStatus(200);
        } catch (PDOException $e) {
            $error = array(
                "message" => $e->getMessage()
            );
        }
        $response->getBody()->write(json_encode($error));
        //return $response->withHeader('content-type', 'application/json')->withStatus(400);
    }
    return true;
}

function deleteTagsInUsers($tag){

    $sql ="DELETE FROM autoriser WHERE id_tag='$tag'";
    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();

        $DB = null;
    }catch (PDOException $e) {
    }
}

function getYourData($request,$response,$args)
{
    $res = isSession($request, $response, $args);
    if ($res) {
        return $res;
    }
    $response->getBody()->write(json_encode($_SESSION));

    return $response->withHeader('content-type', 'application/json')->withStatus(200);
}
