<?php



function getAllFiles($request,$response,$args) {
    $res = isSession($request,$response,$args);

    if($res){
        return $res;
    }
    $tags = $request->getQueryParam("tag");
    $sql = "";

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

    if($request->getQueryParam("mine")=="true"){
        $sql = "SELECT * FROM fichiers WHERE id_user = ".$_SESSION['id'] ." INTERSECT ";
    }
    if($request->getQueryParam("deleted")=="true"){
        $sql .= "(SELECT * FROM fichiers WHERE fichiers.date_supr IS NOT NULL) INTERSECT ";
    }
    else{
        $sql .= "(SELECT * FROM fichiers WHERE fichiers.date_supr IS NULL) INTERSECT ";
    }

    if($request->getQueryParam("tagLess")=="true"){
        $sql .= "SELECT * FROM fichiers WHERE fichiers.id_file not in (SELECT id_file FROM assigner)";
    }

    else if($request->getQueryParam("union")=="true") {
        $sql .= "SELECT fichiers.* FROM fichiers ";

        if ($tags != null) {
            $sql .= ",assigner WHERE fichiers.id_file = assigner.id_file AND assigner.id_tag IN (";
            for ($i = 0; $i < count($tags); $i++) {
                $sql .= $tags[$i];
                if ($i != count($tags) - 1) {
                    $sql .= ",";
                }
            }
            $sql .= ") GROUP BY fichiers.id_file";
        }
    }
    else {
        if ($tags != null) {
            $sql .="SELECT nom_categorie FROM categories";

            try {
                $DB = new DB();
                $conn = $DB->connect();

                $stmt = $conn->query($sql);
                $categories = $stmt->fetchAll(PDO::FETCH_OBJ);
                $sql = "SELECT * FROM tags ";
                $stmt = $conn->query($sql);
                $tagsList = $stmt->fetchAll(PDO::FETCH_OBJ);


                if(is_countable($categories)){
                    $sql = "";
                    for($i = 0; $i < count($categories); $i++){
                        $elements = false;
                        for($j = 0; $j < count($tagsList); $j++){
                            if($tagsList[$j]->nom_categorie == $categories[$i]->nom_categorie && in_array($tagsList[$j]->id_tag,$tags)){
                                $elements = true;
                            }
                        }
                        if (!$elements)
                            continue;

                        $sql .= "(SELECT fichiers.* FROM fichiers,assigner WHERE fichiers.id_file = assigner.id_file AND assigner.id_tag IN (";
                        $sql .= "Select id_tag from tags where nom_categorie = '" . $categories[$i]->nom_categorie . "' AND id_tag IN (0,";
                        for($j = 0; $j < count($tagsList); $j++){
                            if($tagsList[$j]->nom_categorie == $categories[$i]->nom_categorie && in_array($tagsList[$j]->id_tag,$tags)){
                                $sql .= $tagsList[$j]->id_tag;
                                if ($j != count($tagsList) - 1) {
                                    $sql .= ",";
                                }
                            }
                        }
                        if(str_ends_with($sql, ",")){
                            $sql = substr_replace($sql ,"", -1);
                        }

                        $sql .= ")))";
                        if($i != count($categories) - 1){
                            $sql .= " INTERSECT ";

                        }


                    }
                }

                if(str_ends_with($sql, "INTERSECT ")){
                    $sql = substr_replace($sql ,"", -10);
                }

            }
            catch (PDOException $e) {
                $error = array(
                    "message" => $e->getMessage()
                );
            }

        }
        else{
            $sql .= "SELECT * FROM fichiers";
        }

    }


    if($request->getQueryParam('limit') != null AND $request->getQueryParam('offset') != null){
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
    $tags = $request->getQueryParam("tag");
    $sql = "((SELECT fichiers.* FROM fichiers WHERE id_user = $user) UNION (SELECT fichiers.* FROM fichiers,assigner WHERE (fichiers.id_file = assigner.id_file AND assigner.id_tag IN (SELECT autoriser.id_tag from autoriser WHERE autoriser.id_user = $user))) UNION (SELECT fichiers.* FROM fichiers,assigner,tags WHERE (fichiers.id_file = assigner.id_file AND assigner.id_tag IN (SELECT tags.id_tag from tags WHERE tags.id_user = $user))))INTERSECT(";

    if($request->getQueryParam("mine")=="true"){
        $sql = "SELECT * FROM fichiers WHERE id_user = ".$_SESSION['id'] ." INTERSECT ";
    }
    if($request->getQueryParam("deleted")=="true"){
        $sql .= "(SELECT * FROM fichiers WHERE fichiers.date_supr IS NOT NULL) INTERSECT ";
    }
    else{
        $sql .= "(SELECT * FROM fichiers WHERE fichiers.date_supr IS NULL) INTERSECT ";
    }
    if($request->getQueryParam("sansTag")=="true"){
        $sql = "SELECT * FROM fichiers WHERE fichiers.id_file not in (SELECT id_file FROM assigner)";
    }

    else if($request->getQueryParam("union")=="true") {
        $sql .= "SELECT fichiers.* FROM fichiers ";

        if ($tags != null) {
            $sql .= ",assigner WHERE fichiers.id_file = assigner.id_file AND assigner.id_tag IN (";
            for ($i = 0; $i < count($tags); $i++) {
                $sql .= $tags[$i];
                if ($i != count($tags) - 1) {
                    $sql .= ",";
                }
            }
            $sql .= ") GROUP BY fichiers.id_file";
        }
    }
    else {
        if ($tags != null) {
            $sql2 = "SELECT nom_categorie FROM categories";

            try {
                $DB = new DB();
                $conn = $DB->connect();

                $stmt = $conn->query($sql2);
                $categories = $stmt->fetchAll(PDO::FETCH_OBJ);
                $sql2 = "SELECT * FROM tags ";
                $stmt = $conn->query($sql2);
                $tagsList = $stmt->fetchAll(PDO::FETCH_OBJ);


                if (is_countable($categories)) {
                    for ($i = 0; $i < count($categories); $i++) {
                        $elements = false;
                        for ($j = 0; $j < count($tagsList); $j++) {
                            if ($tagsList[$j]->nom_categorie == $categories[$i]->nom_categorie && in_array($tagsList[$j]->id_tag, $tags)) {
                                $elements = true;
                            }
                        }
                        if (!$elements)
                            continue;

                        $sql .= "(SELECT fichiers.* FROM fichiers,assigner WHERE fichiers.id_file = assigner.id_file AND assigner.id_tag IN (";
                        $sql .= "Select id_tag from tags where nom_categorie = '" . $categories[$i]->nom_categorie . "' AND id_tag IN (0,";
                        for ($j = 0; $j < count($tagsList); $j++) {
                            if ($tagsList[$j]->nom_categorie == $categories[$i]->nom_categorie && in_array($tagsList[$j]->id_tag, $tags)) {
                                $sql .= $tagsList[$j]->id_tag;
                                if ($j != count($tagsList) - 1) {
                                    $sql .= ",";
                                }
                            }
                        }
                        if (str_ends_with($sql, ",")) {
                            $sql = substr_replace($sql, "", -1);
                        }

                        $sql .= ")))";
                        if ($i != count($categories) - 1) {
                            $sql .= " INTERSECT ";

                        }


                    }
                }

                if (str_ends_with($sql, "INTERSECT ")) {
                    $sql = substr_replace($sql, "", -10);
                }
                $sql .= ")";
            } catch (PDOException $e) {
                $error = array(
                    "message" => $e->getMessage()
                );
            }

        }
    }



    if($request->getQueryParam('limit')!= null AND $request->getQueryParam('offset') != null){
        $sql .= " LIMIT ".$request->getQueryParam('limit');
        $sql .= " OFFSET ".$request->getQueryParam('offset');
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


        $filename = $file->id_file.'.'.explode('.',$file->type)[1];

        $path = "../files/";
        $download_file =  $path.$filename;


        if(!empty($filename)){
            // Check file is exists on given path.
            if(file_exists($download_file))
            {
                // Get file size.
                $filesize = filesize($download_file);
                //get file type

                // Download file.
                header("Content-Type: ".explode('.',$file->type)[0]);
                header("Content-Length: " . $filesize);
                header("Content-Disposition: attachment; filename=$filename");
                header("Content-Transfer-Encoding: binary");
                readfile($download_file);

                exit;
            }
            else
            {
                echo 'File does not exists on given path';
                return $response
                    ->withHeader('content-type', 'application/json')
                    ->withStatus(404);
            }

        }

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
        if($allowedFiles[$i]->id_file == $id_file) {
            $file = $allowedFiles[$i];
            $filename = $file->id_file . '.' . explode('.', $file->type)[1];

            $path = "../files/";
            $download_file = $path . $filename;


            if (!empty($filename)) {
                // Check file is exists on given path.
                if (file_exists($download_file)) {
                    // Get file size.
                    $filesize = filesize($download_file);
                    //get file type

                    // Download file.
                    header("Content-Type: " . explode('.', $file->type)[0]);
                    header("Content-Length: " . $filesize);
                    header("Content-Disposition: attachment; filename=$filename");
                    header("Content-Transfer-Encoding: binary");
                    readfile($download_file);

                    exit;
                } else {
                    echo 'File does not exists on given path';
                    return $response
                        ->withHeader('content-type', 'application/json')
                        ->withStatus(404);
                }

            }


        }
        }
        $response->getBody()->write("file not found");
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(404);

}


function addFile( $request,$response,  $args) {
    $res = authFilesTags($request,$response,$args);
    echo  $res;
    if($res ){
        return $res;
    }

    $nom = $request->getParam('fileName');
    $idUser=$_SESSION['id'];
    $auteur=$request->getParam("author");
    $taille=$_FILES['file']['size'];
    $dure=$request->getParam("lenght");
    $type='.'.explode(".",$_FILES['file']['name'])[count(explode(".",$_FILES['file']['name']))-1];
    $date = $request->getParam("date");
    $str = $_FILES['file']['type']  . $type;

    echo $str;
    if($_FILES ['file']['error'] > 0){
        $error = array(
            "message"=> "Erreur lors du transfert"
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(400);
    }
    if($_FILES['file']['size'] > 1073741824){
        $error = array(
            "message"=> "Fichier trop volumineux"
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(413);
    }
    //check if the files are images or videos
    if(!($_FILES['file']['type'] == "image/jpeg" || $_FILES['file']['type'] == "image/png" || $_FILES['file']['type'] == "image/gif" || $_FILES['file']['type'] == "video/mp4" || $_FILES['file']['type'] == "video/avi" || $_FILES['file']['type'] == "video/mpeg" || $_FILES['file']['type'] == "video/quicktime"|| $_FILES['file']['type'] == "video/mov")){
        $error = array(
            "message"=> "Type de fichier non autorisé"
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(400);
    }

    $file = $_FILES['file'];



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

        $stmt->bindParam(':type', $str);
        $stmt->bindParam(':date', $date);

        $result=$stmt->execute();

        $db = null;
        $response->getBody()->write(json_encode($result));

        move_uploaded_file($_FILES['file']['tmp_name'], "../files/". $conn->lastInsertId().$type);

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

    $sql = "SELECT * from fichiers where id_file = '$fileDelete'";
    try {
        $DB = new DB();
        $conn = $DB->connect();
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if($result['date_supr'] == null){
            $sql = "UPDATE fichiers SET date_supr = '$date' WHERE id_file = '$fileDelete'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $DB = null;
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(200);
        }
        else{
            $sql = "DELETE from fichiers WHERE id_file = '$fileDelete'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $DB = null;
            deleteFileInTags($fileDelete);
            unlink("../files/".$fileDelete.explode(".",$result['type'])[1]);
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(200);
        }
    }catch (
        PDOException $e
    ) {
        $error = array(
            "message" => $e->getMessage()
        );
    }




    $response->getBody()->write(json_encode($error));
    return $response
        ->withHeader('content-type', 'application/json')
        ->withStatus(400);
}


function deleteTagInFiles($tag){

    $sql ="DELETE FROM assigner WHERE id_tag='$tag'";
    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();

        $DB = null;
        return $result;
    }catch (PDOException $e) {
        $error = array(
            "message"=> $e->getMessage()
        );
        return $error;
    }
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




function stream($request,$response, $args)
{


    $file = $args['file'];
    $file = "../files/".$file.'.mp4';


    $stream = new VideoStream($file);
    $stream->start();

}


