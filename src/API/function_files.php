<?php



function getAllFiles($request,$response,$args) {
    $res = isSession($request,$response,$args);

    if($res){
        return $res;
    }


    if($_SESSION['role'] == 0){
        $data = getAllAllowedFiles($request,$response,$args);
        if($data){
            $response->getBody()->write(json_encode($data));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(200);
        }
        else{
            $error = array(
                "message"=> "Aucun fichier trouve/erreur de parametres"
            );
            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(404);
        }
    }

    $sql ="SELECT * FROM fichiers ";
    if($request->getQueryParam('limit')){
        $sql .= " LIMIT ".$request->getQueryParam('limit');
        $sql .= " OFFSET ".$request->getQueryParam('offset');
    }



    try {
        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->query($sql);
        $files = $stmt->fetchAll(PDO::FETCH_OBJ);

        $db = null;
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

function getAllAllowedFiles($request, $response, $args)
{
    $user = $_SESSION['id'];
    $sql = "(SELECT fichiers.* FROM fichiers WHERE id_user = $user) UNION (SELECT fichiers.* FROM fichiers,assigner WHERE (fichiers.id_file = assigner.id_file AND assigner.id_tag IN (SELECT autoriser.id_tag from autoriser WHERE autoriser.id_user = $user))) UNION (SELECT fichiers.* FROM fichiers,assigner,tags WHERE (fichiers.id_file = assigner.id_file AND assigner.id_tag IN (SELECT tags.id_tag from tags WHERE tags.id_user = $user)))";


    if($request->getParam('limit') !=''){
        echo "test2";
        $sql .= " LIMIT ".$request->getParam('limit');
        $sql .= " OFFSET ".$request->getParam('offset');
    }
    
    try {
        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->query($sql);
        $files = $stmt->fetchAll(PDO::FETCH_OBJ);

        $db = null;
        return $files;
    }catch (PDOException $e) {
        $error = array(
            "message"=> $e->getMessage()
        );
    }
    $response->getBody()->write(json_encode($error));
    return 0;

}


function getFile($request,$response, $args){
    $res = isSession($request,$response,$args);
    if($res ){
        return $res;
    }

    if($_SESSION['role'] == 0){
        return getAllowedFile($request,$response,$args);
    }
    $id_file = $args['file'];
    $sql ="SELECT * FROM fichiers WHERE id_file = $id_file";

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



function getAllowedFile($request,$response, $args){


    $id_file = $args['file'];
        $allowedFiles = (getAllAllowedFiles($request,$response,$args));
        if(!$allowedFiles){
        $error = array(
            "message"=> "Aucun fichier trouve/erreur de parametres"
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(404);
    }
        for($i = 0; $i < count($allowedFiles); $i++){
            echo $allowedFiles[$i]->id_file;
            if($allowedFiles[$i]->id_file == $id_file){
                $file = $allowedFiles[$i];
                $response->getBody()->write(json_encode($file));
                return $response
                    ->withHeader('content-type', 'application/json')
                    ->withStatus(200);

            }
        }
        $response->getBody()->write("file not found");
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(404);

}


function addFile( $request,$response,  $args) {
    $res = authFilesTags($request,$response,$args);
    if($res ){
        return $res;
    }

    $nom=$request->getParam("fileName");
    $idUser=$request->getParam("userId");
    $auteur=$request->getParam("author");
    $taille=$request->getParam("size");
    $dure=$request->getParam("lenght");
    $type=$request->getParam("type");
    $date = $request->getParam("date");

    $sql ="INSERT INTO fichiers (nom_fichier,id_user,nom_prenom_auteur,taille,duree,date,type) VALUE (:nom,:idUser,:auteur,:taille,:dure,:date,:type)";

    try {
        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':idUser', $idUser);
        $stmt->bindParam(':auteur', $auteur);
        $stmt->bindParam(':taille', $taille);
        $stmt->bindParam(':dure', $dure);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':date', $date);

        $result=$stmt->execute();

        $db = null;
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

function addFileTags( $request,$response,  $args)
{
    $res = authFilesTags($request,$response,$args);
    if($res ){
        return $res;
    }


    $fichier = $args['file'];
    $tags = $request->getParam("tags");
    $res2 = checkIfOwnedFile($request,$response,$args);
    if($res2 ){
        return $res2;
    }



    for($i = 0;$i < count($tags);$i++) {
        $sql = "INSERT INTO assigner (id_tag,id_file) VALUES ($tags[$i],'$fichier')";

        try {
            $DB = new DB();
            $conn = $DB->connect();
            $stmt = $conn->prepare($sql);

            $result = $stmt->execute();

            $DB = null;
            //$response->getBody()->write(json_encode($result));
            //return $response->withHeader('content-type', 'application/json')->withStatus(200);
        } catch (PDOException $e) {
            $error = array(
                "message" => $e->getMessage()
            );
        }
        //$response->getBody()->write(json_encode($error));
        //return $response->withHeader('content-type', 'application/json')->withStatus(400);
    }

}


function deleteFileTags( $request,$response, $args){

    $res = authFilesTags($request,$response,$args);
    if($res ){
        return $res;
    }
    $fichier = $args['file'];
    $tags = $request->getParam("tags");

    $res2 = checkIfOwnedFile($request,$response,$args);
    if($res2 ){
        return $res2;
    }

    for($i = 0;$i < count($tags);$i++) {
        $sql = "DELETE from assigner where (id_file = '$fichier' AND id_tag = $tags[$i])";

        try {
            $DB = new DB();
            $conn = $DB->connect();
            $stmt = $conn->prepare($sql);

            $result = $stmt->execute();

            $DB = null;
            //$response->getBody()->write(json_encode($result));
            //return $response->withHeader('content-type', 'application/json')->withStatus(200);
        } catch (PDOException $e) {
            $error = array(
                "message" => $e->getMessage()
            );
        }
        //$response->getBody()->write(json_encode($error));
        //return $response->withHeader('content-type', 'application/json')->withStatus(400);

    }
    return 'true';
}

function deleteFile( $request,$response,  $args) {

    $res = authFilesTags($request,$response,$args);
    if($res ){
        return $res;
    }

    $res2 = checkIfOwnedFile($request,$response,$args);
    if($res2 ){
        return $res2;
    }

    $fileDelete = $args['file'];

    $date = date("Y-m-d H:i:s",strtotime("+30 days"));
    $sql ="UPDATE fichiers SET date_supr= '$date' WHERE nom_fichier='$fileDelete'";


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

function deleteUserInFiles($user){
    $sql ="UPDATE fichiers SET id_user= 1 WHERE id_user=$user";
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