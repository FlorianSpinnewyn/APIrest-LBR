<?php


function login($request,$response,$args){


    $mail = $request->getParam("mail");
    $password = $request->getParam("password");


    $sql = "SELECT * FROM utilisateurs WHERE mail = '$mail' AND mdp = '$password'";
    try{
        $db = new db();
        $db = $db->connect();
        $stmt = $db->query($sql);
        $user = $stmt->fetch(PDO::FETCH_OBJ);
        $db = null;

        if(true) {
            session_start([
                'use_only_cookies' => 1,
                'cookie_lifetime' => 0,
                'cookie_secure' => 0,
                'cookie_httponly' => 1
            ]);

            $_SESSION['role'] = 3;
            $_SESSION['id'] = 2;
            return $response->withStatus(200)->getBody()->write("utilisateur connecte");
        }
        else{
            $response->getBody()->write("Mauvais identifiants");
        }
    }catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }

}

function logout($request,$response,$args,$app){
    $res = isSession($request,$response,$args);
    if($res)
        return $res;
    $params = session_get_cookie_params();
    setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
    session_unset();
    session_destroy();
    session_write_close();

   return $response->withHeader('Content-type', 'application/json')->withHeader("Set-Cookie", "PHPSESSID=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT");

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
    return $response;

}


function changePassword($request,$response,$args)
{
    $res = isSession($request, $response, $args);
    if ($res)
        return $res;


    $password = $request->getParam("password");

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
    return $response;
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
    return $response;
}





function isSession($request,$response,$args){
    session_start([
        'use_only_cookies' => 1,
        'cookie_lifetime' => 0,
        'cookie_secure' => 0,
        'cookie_httponly' => 1
    ]);
    if(session_id() == '' || !isset($_SESSION) || session_status() === PHP_SESSION_NONE) {
        session_abort();
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




