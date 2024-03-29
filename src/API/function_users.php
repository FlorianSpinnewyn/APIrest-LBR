<?php

//get all the users
function getUsersAll($request,$response,$args) {
    $sql ="SELECT id_user,mail,role FROM utilisateurs";

    $res = isAdmin($request,$response,$args); //check if the user is an admin
    if($res){
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$res->getStatusCode());
        return $res;
    }

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
    addLog($request->getMethod(). " ".$request->getUri()->getPath(),400);
    return $response
        ->withHeader('content-type', 'application/json')
        ->withStatus(400);
}
//get a specific user by id
function getUser($request,$response,  $args){
    $res = isSession($request, $response, $args);
    if ($res) {
        addLog($request->getMethod() . " " . $request->getUri()->getPath(), $res->getStatusCode());
        return $res;
    }

    if($_SESSION['role'] == 0 and $args['user'] != $_SESSION['id']) {
        return $response->withStatus(403);
    }

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
    addLog($request->getMethod(). " ".$request->getUri()->getPath(),400);
    $response->getBody()->write(json_encode($error));
    return $response
        ->withHeader('content-type', 'application/json')
        ->withStatus(400);

}

//add a new user and send an email to the user  with his credentials
function addUser( $request,$response,  $args) {
    $res = isAdmin($request,$response,  $args);
    if($res ){
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$res->getStatusCode());
        return $res;
    }
    $mail=$request->getParam("mail");
    $nom_prenom=$request->getParam("name_surname");
    $mdp=$request->getParam("password");
    $mdp = password_hash($mdp, PASSWORD_BCRYPT); //hash the password

    $descriptif=$request->getParam("description");
    $role=$request->getParam("role");
    $mdpFinal=$request->getParam("is_pwd_final");

    $sql ="INSERT INTO `utilisateurs` ( `mail`, `nom_prenom`, `mdp`, `descriptif`, `role`, `mdpFinal`) VALUES ('$mail','$nom_prenom','$mdp','$descriptif',$role,$mdpFinal);";

    try {
        $DB = new DB();
        $sqlVerif = "SELECT * FROM utilisateurs WHERE mail = '$mail'";

        $conn = $DB->connect();
        $stmt = $conn->query($sqlVerif);
        $user = $stmt->fetch(PDO::FETCH_OBJ);
        //if the mail already exists
        if($user) {
            return $response->withStatus(400)->withHeader('content-type', 'application/json')->getBody()->write("Mail deja utilisé");
        }


        $stmt = $conn->prepare($sql);


        $result=$stmt->execute();


        $response->getBody()->write(json_encode($result));

        $id_user = $conn->lastInsertId();
        //add all the allowed tags to the user
        addAllowedTagToUser2($id_user, $request,$response,  $args);
        $headers = array(
            'From' => 'drivelbr@test-mail.lesbriquesrouges.fr',
            'X-Mailer' => 'PHP/' . phpversion()
        );;
        //send an email to the user with his credentials
        if($mdpFinal == 1){
            finalSignUp($mail,$request->getParam("password"));
        }else{
            SignUp($mail,$request->getParam("password"));
        }
        $DB = null;
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),201);
        return $response
            ->withHeader('content-type', 'application/json')
            ->withHeader('location', '/api/users/'.$id_user)
            ->withStatus(201);
    }catch (PDOException $e) {
        $error = array(
            "message"=> $e->getMessage()
        );
    }
    addLog($request->getMethod(). " ".$request->getUri()->getPath(),400);
    $response->getBody()->write(json_encode($error));

}

//update a user
function updateUser($request,$response,$args){
    $res = isAdmin($request,$response,  $args);
    if($res ){
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$res->getStatusCode());
        return $res;
    }
    $user =$args['user'];

    if($request->getParam("tags")){
        $sql ="DELETE FROM autoriser WHERE id_user=$user";
        try {
            $DB = new DB();
            $conn = $DB->connect();
            $stmt = $conn->prepare($sql);
            $files = $stmt->fetchAll(PDO::FETCH_OBJ);
            $result=$stmt->execute();
            $DB = null;
        }catch (PDOException $e) {

        }
        addAllowedTagToUser2($args['user'], $request,$response,  $args);

    }
;

    //define what should be updated
    $sql ="UPDATE `utilisateurs` SET";
     if($request->getParam("mail")){
        $sql.=" `mail` = '".$request->getParam("mail")."',";
     }
     if($request->getParam("name_surname")){
        $sql.=" `nom_prenom` = '".$request->getParam("name_surname")."',";
     }
     if($request->getParam("password")){
         $password = password_hash($request->getParam("password"), PASSWORD_BCRYPT);
         $sql.=" `mdp` = '".$password."',";
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
     if($sql[strlen($sql)-1]==","){
         $sql=substr($sql,0,strlen($sql)-1);
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
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),200);
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(200);
    }catch (PDOException $e) {
        $error = array(
            "message"=> $e->getMessage()
        );
    }
    $response->getBody()->write(json_encode($error));
    addLog($request->getMethod(). " ".$request->getUri()->getPath(),400);
    return $response
        ->withHeader('content-type', 'application/json')
        ->withStatus(400);
}

//delete a user and all his info related to him
function deleteUser( $request,$response,  $args) {
    $res = isAdmin($request,$response,  $args);
    if($res ){
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$res->getStatusCode());
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
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),200);
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(200);

    }catch (PDOException $e) {
        $error = array(
            "message"=> $e->getMessage()
        );
    }
    addLog($request->getMethod(). " ".$request->getUri()->getPath(),400);
    $response->getBody()->write(json_encode($error));
    return $response
        ->withHeader('content-type', 'application/json')
        ->withStatus(400);
}

//add  tags to a user
function addAllowedTagToUser($request,$response,$args){
    $res = isAdmin($request,$response,  $args);
    if($res ){
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$res->getStatusCode());
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
            $response->withHeader('content-type', 'application/json')->withStatus(200);
        } catch (PDOException $e) {
            $error = array(
                "message" => $e->getMessage()
            );
            $response->getBody()->write(json_encode($error));
            addLog($request->getMethod(). " ".$request->getUri()->getPath(),200);
            return $response->withHeader('content-type', 'application/json')->withStatus(400);
        }


    }
    addLog($request->getMethod(). " ".$request->getUri()->getPath(),200);

}

//get the allowed tags of a user
function getAllowedTags($request,$response,$args){

    $user = $_SESSION["id"];
    $sql = "SELECT * FROM tags WHERE id_tag IN(SELECT id_tag FROM autoriser WHERE id_user = $user) UNION SELECT * FROM tags WHERE id_user = $user";

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


//remove a tag from a user
function removeAllowedTagToUser($request,$response,$args)
{
    $res = isAdmin($request, $response, $args);
    if ($res) {
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$res->getStatusCode());
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
            addLog($request->getMethod(). " ".$request->getUri()->getPath(),400);
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('content-type', 'application/json')->withStatus(400);
        }
    }
    addLog($request->getMethod(). " ".$request->getUri()->getPath(),200);
}


function addAllowedTagToUser2($id,$request,$response,$args){


    $tags = $request->getParam("tags");
    if($tags == null){
        return 0;
    }

    for($i = 0;$i < count($tags);$i++) {
        $sql = "INSERT INTO autoriser (id_tag,id_user) VALUES ($tags[$i],$id)";

        try {
            $DB = new DB();
            $conn = $DB->connect();
            $stmt = $conn->prepare($sql);

            $result = $stmt->execute();

            $DB = null;

        } catch (PDOException $e) {
            $error = array(
                "message" => $e->getMessage()
            );
            $response->getBody()->write(json_encode($error));
        }


        //return $response->withHeader('content-type', 'application/json')->withStatus(400);
    }

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


//function that returns the data of your current session
// that means if your password if final, your role and your id
function getYourData($request,$response,$args)
{
    $res = isSession($request, $response, $args);
    if ($res) {
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$res->getStatusCode());
        return $res;
    }
    $response->getBody()->write(json_encode($_SESSION));
    return $response->withHeader('content-type', 'application/json')->withStatus(200);
}
