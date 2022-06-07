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

        if($user) {
            session_start([
                'use_only_cookies' => 1,
                'cookie_lifetime' => 0,
                'cookie_secure' => 0,
                'cookie_httponly' => 1
            ]);

            $_SESSION['role'] = $user->role;
            $_SESSION['id'] = $user->id_user;
            return $response->withStatus(200)->getBody()->write("utilisateur connecte");
        }
        else{
            $response->getBody()->write("Mauvais identifiants");
        }
    }catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
    return $response->withStatus(401);
}

function logout($request,$response,$args){
    $res = isSession($request,$response,$args);
    if($res)
        return $res;
    $params = session_get_cookie_params();
    setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
    session_unset();
    session_destroy();
    session_write_close();

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
    if($_SESSION['role']== 0){
        $sql ="SELECT * FROM fichiers WHERE `ìd_user`=".$_SESSION['id'];
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
    return 1;
}

function checkIfOwnedTag($request,$response,$args){
    $tag = $args['tag'];
    if($_SESSION['role']== 0){
        $sql ="SELECT * FROM tags WHERE `id_user`=".$_SESSION['id'];
        try {
            $db = new DB();
            $conn = $db->connect();

            $stmt = $conn->query($sql);
            $tag = $stmt->fetch(PDO::FETCH_OBJ);

            $db = null;
            $inside = false;
            for($i = 0; $i < count($tag); $i++){
                if($tag[$i]->id_tag == $tag){
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
    return 1;
}


