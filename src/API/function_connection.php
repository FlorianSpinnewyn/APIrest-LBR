<?php

//login the user and create a session associated to it
function login($request,$response,$args){
    $mail = $request->getParam("mail");

    //return $response->withStatus(200)->write(json_encode(file_get_contents('php://input')));

    $password = $request->getParam("password");

    $sql = "SELECT * FROM utilisateurs";
    try{
        $db = new db();
        $db = $db->connect();
        $stmt = $db->query($sql);
        $user = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        $userFound = false;
        //check for every user if the password is correct
        for($i=0;$i<count($user);$i++){
            if($user[$i]->mail == $mail ){
                $userFound = true;
                if(password_verify($password,$user[$i]->mdp)) {
                    session_start([
                        'use_only_cookies' => 1,
                        'cookie_lifetime' => 0,
                        'cookie_secure' => 0,
                        'cookie_httponly' => 1
                    ]);
                    $_SESSION['role'] = $user[$i]->role;
                    $_SESSION['id'] = $user[$i]->id_user;
                    if($user[$i]->mdpFinal == 0)
                    {
                        $_SESSION['mdpFinal'] = false;
                        $myJSON =   new stdClass();
                        $myJSON->id = $user[$i]->id_user;
                        $myJSON->role = $user[$i]->role;
                        $myJSON->mdpFinal = false;


                        $response->write(json_encode($myJSON));
                        addLog($request->getMethod(). " ".$request->getUri()->getPath(),200);
                        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
                    }
                    addLog($request->getMethod(). " ".$request->getUri()->getPath(),200);
                    return $response->withStatus(200)->getBody()->write("utilisateur connecte");
                }
            }
        }
        //if the user is not found
        if(!$userFound){
            addLog($request->getMethod(). " ".$request->getUri()->getPath(),404);
            $response->withHeader('Access-Control-Allow-Origin', '*')->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
            return $response->withStatus(404)->getBody()->write($mail." n'existe pas");

        }
        //if the password is not correct
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),400);
        $response->getBody()->write("Mauvais identifiants");
        return $response->withStatus(400);


    }catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
}

//reset your password by sending you email and keeping a token in the database
function passwordForgotten($request,$response,$args){
    $mail = $request->getQueryParams('mail')['mail'];

    $sql = "SELECT * FROM utilisateurs WHERE mail = '$mail'";

    try{
        $db = new db();
        $db = $db->connect();
        $stmt = $db->query($sql);
        $user = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        //if the user is not found
        if(!is_countable($user) || count($user) == 0){

            return $response->withStatus(404)->getBody()->write("utilisateur non trouve");
        }
        //create a random token
        $token = bin2hex(random_bytes(28));
        echo $token;

        $sql = "UPDATE utilisateurs SET token_mdp = '$token' WHERE id_user = '".$user[0]->id_user."'";
        $db = new db();
        echo $sql;
        $db = $db->connect();
        $stmt = $db->query($sql);
        $db = null;
        forgetPassword($mail,$token);
        return $response->withStatus(200)->getBody()->write("token envoye");
    }
    catch(PDOException|Exception $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
    return $response->withStatus(400);



}


//login and create session, but using google auth
function loginGoogle($request,$response,$args){


    $CLIENT_ID = "349453641732-i9fnplintku85hrcjuieaghqjqskq87q.apps.googleusercontent.com";
    $token = str_replace("Bearer ","",$request->getCookieParams()['auth._token.google']);

    $userDetails = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $token);
    $userData = json_decode($userDetails);

    if (!empty($userData)) {

        $googleUserId = '';
        $googleEmail = '';
        $googleVerified = '';
        $googleName = '';
        $googleUserName = '';



        if (isset($userData->id)) {
            $googleUserId = $userData->id;
        }
        if (isset($userData->email)) {
            $googleEmail = $userData->email;
            $googleEmailParts = explode("@", $googleEmail);
            $googleUserName = $googleEmailParts[0];
        }
        if (isset($userData->verified_email)) {
            $googleVerified = $userData->verified_email;
        }
        if (isset($userData->name)) {
            $googleName = $userData->name;
        }
    } else {

        echo "Not logged In";
    }
    //check if the mail is lesbriquesrouges.com, if not, prevent from connecting
    if(explode($googleEmail, "@")[1] != "lesbriquesrouges.com"){
        //return $response->withStatus(400)->getBody()->write("Vous devez utiliser votre compte les-briques-rouges");
    }
    $sql = "SELECT * FROM utilisateurs WHERE mail = '$googleEmail'";
    try {
        $db = new db();
        $db = $db->connect();
        $stmt = $db->query($sql);
        $user = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        if (count($user) == 0) {
            $sql = "INSERT INTO utilisateurs (mail,nom_prenom,mdp,mdpFinal,role) VALUES ('$googleEmail','$googleUserName','','1',1)";
            $db = new db();
            $db = $db->connect();
            $stmt = $db->query($sql);
            sendMailAdmin($googleEmail);
            $db = null;
        }
        else{
            session_start([
                'use_only_cookies' => 1,
                'cookie_lifetime' => 0,
                'cookie_secure' => 0,
                'cookie_httponly' => 1
            ]);
            $_SESSION['role'] = $user[0]->role;
            $_SESSION['id'] = $user[0]->id_user;
            $_SESSION['mdpFinal'] = $user[0]->mdpFinal;
        }
        return $response->withStatus(200)->getBody()->write("utilisateur connecte");
    }
    catch (PDOException $e) {
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
    return $response->withStatus(400)->getBody()->write("utilisateur non trouve");
}

//logout, destroy session and remove google cookie
function logout($request,$response,$args){

    $res = isSession($request,$response,$args);
    if($res) {
        addLog($request->getMethod() . " " . $request->getUri()->getPath(), 401);
        return $res;
    }
    $params = session_get_cookie_params();
    setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
    session_unset();
    session_destroy();
    session_write_close();

    //delete cookies from google auth
    $params = session_get_cookie_params();

    setcookie("auth._token_expiration.google", '', time() - 3600, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));


    addLog($request->getMethod(). " ".$request->getUri()->getPath(),200);
   return $response->withStatus(200)->withHeader('Content-type', 'application/json')->withHeader("Set-Cookie", "PHPSESSID=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT");

}

//check if the user is admin
function isAdmin($request,$response,$args){
    $res = isSession($request,$response,$args);

    if($res)
        return $res;

    if($_SESSION['role'] != 3){
        $error = array(
            "message"=> "Vous n'avez pas les droits pour effectuer cette action"
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(403);
    }

    return 0;

}

//change your password, from the token or from the compte page
function changePassword($request,$response,$args)
{
    //check for the token in the url
    if($request->getParam("password") !=null and $request->getParam("token") !=  null){
        $token = $request->getParam("token");

       $sql = "SELECT * FROM utilisateurs WHERE token_mdp = '$token'";

        try {
            $db = new db();
            $db = $db->connect();
            $stmt = $db->query($sql);
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            //verify if the token is valid and if you find an user
            if (!is_countable($user)) {

                return $response->withStatus(404)->getBody()->write("utilisateur non trouve");
            }
            //update the new password
            $user = $user[0];
            $password = $request->getParam("password");
            $password = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE utilisateurs SET mdp = '" . $password . "',token_mdp = NULL WHERE id_user = '$user->id_user'";

            $db = new db();
            $db = $db->connect();
            $stmt = $db->query($sql);
            $db = null;
            return $response->withStatus(200)->getBody()->write("Mot de passe changé");
        }
        catch (PDOException $e) {
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
        return $response->withStatus(404)->getBody()->write("utilisateur non trouve/token invalide");
    }




    $res = isSession($request, $response, $args);

    if ($res )
        return $res;

    //if the user is logged in and the password is not null, change the password
    $password = $request->getParam("password");
    $password = password_hash($password, PASSWORD_BCRYPT);
    $sql = "UPDATE utilisateurs SET mdp = '$password' WHERE id_user = '$_SESSION[id]'";
    try{
        $db = new db();
        $db = $db->connect();
        $stmt = $db->query($sql);
        $db = null;
        $response->getBody()->write("Mot de passe changé");
    }
    catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
    return $response;
}
//auth for adding the files and the tags
function authFilesTags($request, $response, $args){

    $res = isSession($request,$response,$args);
    if($res)
        return $res;

    if($_SESSION['role'] == 1){
        $error = array(
            "message"=> "Vous n'avez pas les droits pour effectuer cette action"
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(403);
    }
    return 0;
}

//auth for category
function authCategory($request, $response, $args){

    $res = isSession($request,$response,$args);
    if($res)
        return $res;

    if($_SESSION['role'] < 2){
        $error = array(
            "message"=> "Vous n'avez pas les droits pour effectuer cette action"
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(403);
    }
    return 0;
}




//check if the user has a session
function isSession($request,$response,$args){
    session_start([
        'use_only_cookies' => 1,
        'cookie_lifetime' => 0,
        'cookie_secure' => 0,
        'cookie_httponly' => 1
    ]);

    if(session_id() == '' || !isset($_SESSION)||!isset($_SESSION['id']) || session_status() === PHP_SESSION_NONE) {

        $params = session_get_cookie_params();

        setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
        //destroy the session and redirect to the login page
        session_unset();
        session_destroy();
        session_write_close();
        $error = array(
            "message"=> "Vous n'etes pas connecte",
        );
        $response->getBody()->write(json_encode($error));

        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(401);
    }
    return 0;
}


//check if the user can modify the file or see the file
function checkIfOwnedFile($request,$response,$args){
    $fichier = $args['file'];
    if($_SESSION['role'] == 1)
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(403);
    if($_SESSION['role']== 0){
        $sql ="SELECT * FROM fichiers WHERE id_user=".$_SESSION['id'];
        try {
            $db = new DB();
            $conn = $db->connect();

            $stmt = $conn->query($sql);
            $file = $stmt->fetch(PDO::FETCH_OBJ);

            $db = null;
            $inside = false;
            if(is_countable($file)){
                foreach ($file as $f) {
                    if($f->id_fichier == $fichier){
                        $inside = true;
                    }
                }
            }


            if(!$inside){
                $error = array(
                    "message"=> "Vous n'avez pas le droit de modifier des tags a ce fichier"
                );
                $response->getBody()->write(json_encode($error));
                return $response
                    ->withHeader('content-type', 'application/json')
                    ->withStatus(403);
            }
            else{
                return 0;
            }

        }catch (PDOException $e) {
            $error = array(
                "message"=> $e->getMessage()
            );
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(400);
        }
    }
    return 0;
}

//check if the user can have access to the  tags
function checkIfOwnedTag($request,$response,$args){
    $tag = $args['tag'];
    if($_SESSION['role']== 0){
        $sql ="SELECT * FROM tags WHERE `id_user`=".$_SESSION['id'];
        try {
            $db = new DB();
            $conn = $db->connect();

            $stmt = $conn->query($sql);
            $tags = $stmt->fetch(PDO::FETCH_OBJ);

            $db = null;
            $inside = false;
            for($i = 0; $i < count($tags); $i++){
                if($tags[$i]->id_tag == $tag){
                    $inside = true;
                }
            }
            if(!$inside){
                $error = array(
                    "message"=> "Vous n'avez pas le droit d'ajouter des tags a ce fichier"
                );
                $response->getBody()->write(json_encode($error));
                return $response
                    ->withHeader('content-type', 'application/json')
                    ->withStatus(403);
            }
            else{
                return 0;
            }

        }catch (PDOException $e) {
            $error = array(
                "message"=> $e->getMessage()
            );
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(400);
        }
    }
    return 0;
}


