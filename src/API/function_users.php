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
        $headers = $headers = array(
            'From' => 'drivelbr@test-mail.lesbriquesrouges.fr',
            'X-Mailer' => 'PHP/' . phpversion()
        );;

        mail("elliott.vanwormhoudt@student.junia.com","Inscription","Vous venez de vous inscrire. Votre identifiant est $mail et votre mot de passe est $mdp. Vous pouvez vous connecter sur le site en utilisant ces identifiants.<a href ='http://www.example.com'>Veuillez confirmer votre email</a> ",$headers);

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

    $sql ="UPDATE `utilisateurs` SET";
     if($request->getParam("mail")){
        $sql.=" `mail` = '".$request->getParam("mail")."',";
     }
     if($request->getParam("name_surname")){
        $sql.=" `nom_prenom` = '".$request->getParam("name_surname")."',";
     }
     if($request->getParam("password")){
         $sql.=" `mdp` = '".$request->getParam("password")."',";
     }
     if($request->getParam("description")){
         $sql.=" `descriptif` = '".$request->getParam("description")."',";
     }
     if($request->getParam("role")){
         $sql.=" `role` = '".$request->getParam("role")."',";
     }
     if($request->getParam("is_pwd_final")){
         $sql.=" `mdpFinal` = '".$request->getParam("is_pwd_final")."',";
     }



    $sql .= "WHERE `id_user`=$user";

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
    if($tags == null){
        return 0;
    }
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

function getAllowedTags($request,$response,$args){
    $res = isAdmin($request,$response,  $args);
    if($res ){
        return $res;
    }

    $user = $args["user"];
    $sql = "SELECT * FROM tags WHERE id_tag IN(SELECT id_tag FROM autoriser WHERE id_user = $user)";

    try {
        $DB = new DB();
        $conn = $DB->connect();
        $stmt = $conn->prepare($sql);

        $stmt->execute();
        $tags = $stmt->fetchAll(PDO::FETCH_OBJ);
        $DB = null;
        $response->getBody()->write(json_encode($tags));
        return $response->withHeader('content-type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        $error = array(
            "message" => $e->getMessage()
        );
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('content-type', 'application/json')->withStatus(400);
    }
}

function removeAllowedTagToUser($request,$response,$args)
{
    $res = isAdmin($request, $response, $args);
    if ($res) {
        return $res;
    }

    $user = $args["user"];
    $tags = $request->getParam("tags");
    if ($tags == null) {
        return 0;
    }
    for ($i = 0; $i < count($tags); $i++) {
        $sql = "DELETE FROM autoriser WHERE id_tag = $tags[$i] AND id_user = $user";

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
    if($tags == null){
        return 0;
    }
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
