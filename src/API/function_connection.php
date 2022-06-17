<?php


function login($request,$response,$args){

    //echo $request->headers;
    $mail = $request->getParam("mail");
    $password = $request->getParam("password");
    $CLIENT_ID = "349453641732-i9fnplintku85hrcjuieaghqjqskq87q.apps.googleusercontent.com";

    /*$id_token  = $request->headers->get('Authorization');

    $client = new Google_Client(['client_id' => $CLIENT_ID]);  // Specify the CLIENT_ID of the app that accesses the backend
    $payload = $client->verifyIdToken($id_token);
    if ($payload) {
        $userid = $payload['sub'];
        echo "success";
        echo $userid;
        // If request specified a G Suite domain:
        //$domain = $payload['hd'];
    } else {

    }*/

    $sql = "SELECT * FROM utilisateurs";
    try{
        $db = new db();
        $db = $db->connect();
        $stmt = $db->query($sql);
        $user = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        $userFound = false;
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
        if(!$userFound){
            addLog($request->getMethod(). " ".$request->getUri()->getPath(),404);
            return $response->withStatus(404)->getBody()->write("utilisateur non trouve");
        }
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),404);
        $response->getBody()->write("Mauvais identifiants");
        return $response->withStatus(400);


    }catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
}



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
    addLog($request->getMethod(). " ".$request->getUri()->getPath(),200);
   return $response->withStatus(200)->withHeader('Content-type', 'application/json')->withHeader("Set-Cookie", "PHPSESSID=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT");

}

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


function changePassword($request,$response,$args)
{
    $res = isSession($request, $response, $args);
    if ($res)
        return $res;


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
            for($i = 0; $i < count($file); $i++){
                if($file[$i]->id_file == $fichier){
                    $inside = true;
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


