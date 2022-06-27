<?php


//Return all available files
function getAllFiles($request,$response,$args) {
    //check if user is logged
    $res = isSession($request,$response,$args);

    if($res){
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),401);
        return $res;
    }
    $tags = $request->getQueryParam("tag");
    $sql = "";

    //check if the user is a guest
    if($_SESSION['role'] == 0){

        $data = getAllAllowedFiles($request,$response,$args);
        if($data){
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(200);
        }
        else{

            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(404);
        }
    }

    if($request->getQueryParam('limit') != null AND $request->getQueryParam('offset') != null){
        $sql .= "(";
    }

    //select only the files that are assigned to the user
    if($request->getQueryParam("mine")=="true"){
        $sql .= "SELECT * FROM fichiers WHERE id_user = ".$_SESSION['id'] ." INTERSECT ";
    }

    //select only the files that are are in the bin
    if($request->getQueryParam("deleted")=="true"){
        deleteFiles30day();
        $sql .= "(SELECT * FROM fichiers WHERE fichiers.date_supr IS NOT NULL) INTERSECT ";
    }
    else{
        $sql .= "(SELECT * FROM fichiers WHERE fichiers.date_supr IS NULL) INTERSECT ";
    }
    //select only the files with a specific extension
    if($request->getQueryParam("extension") != null){
        $sql .= "(SELECT * FROM fichiers WHERE type = '".$request->getQueryParam("extension")."') INTERSECT ";
    }

    $ligne = $request->getQueryParam("searchBar");

    //select only the files that match the search bar
    if($ligne != null){
        $ligne = explode(" ", $ligne);

        $sql .= " (SELECT * FROM fichiers WHERE ";

        for($i = 0; $i < count($ligne); $i++){
            if($ligne[$i] == ""){
                continue;
            }
            $sql .= "(nom_fichier LIKE '%".$ligne[$i]."%' OR date LIKE '%".$ligne[$i]."%' OR nom_prenom_auteur LIKE '%".$ligne[$i]."%')";
            if($i != count($ligne) - 1){
                $sql .= " OR ";
            }
        }
        if(str_ends_with($sql, " OR ")) {
            $sql = substr_replace($sql, "", -4);
        }
        $sql .= ") INTERSECT ";
    }


    //select only the files doesn't have tags
    if($request->getQueryParam("tagLess")=="true"){
        $sql .= " (SELECT * FROM fichiers WHERE fichiers.id_file not in (SELECT id_file FROM assigner)) INTERSECT ";
    }

    //select the files depending on the tags with an union
    else if($request->getQueryParam("union")=="true") {
        $sql .= " SELECT fichiers.* FROM fichiers ";

        if ($tags != null) {
            $sql .= ",assigner WHERE fichiers.id_file = assigner.id_file AND assigner.id_tag IN (";
            for ($i = 0; $i < count($tags); $i++) {
                $sql .= $tags[$i];
                if ($i != count($tags) - 1) {
                    $sql .= ",";
                }
            }
            $sql .= ") GROUP BY fichiers.id_file  ";
        }
    }
    else { //select the files depending on the tags with an intersect
        if ($tags != null) {
            $sql2 =" SELECT nom_categorie FROM categories ";

            try {
                $DB = new DB();
                $conn = $DB->connect();

                $stmt = $conn->query($sql2);
                $categories = $stmt->fetchAll(PDO::FETCH_OBJ);
                $sql2 = " SELECT * FROM tags ";
                $stmt = $conn->query($sql2);
                $tagsList = $stmt->fetchAll(PDO::FETCH_OBJ);


                if(is_countable($categories)){

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
                        $sql .= " Select id_tag from tags where nom_categorie = '" . $categories[$i]->nom_categorie . "' AND id_tag IN (0,";
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

    if(str_ends_with($sql, "INTERSECT ")){
        $sql = substr_replace($sql ,"", -10);
    }

    //limit the number of file and define the offset
    if($request->getQueryParam('limit') != null AND $request->getQueryParam('offset') != null){
        $sql .= ") LIMIT ".$request->getQueryParam('limit');
        $sql .= " OFFSET ".$request->getQueryParam('offset');
    }

    //query the database
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

//get all the files for guest
function getAllAllowedFiles($request, $response, $args)
{
    $user = $_SESSION['id'];
    $tags = $request->getQueryParam("tag");
    $sql = "";


    if($request->getQueryParam('limit')!= null AND $request->getQueryParam('offset') != null){
        $sql .= "(";
    }
    //get the allowed tags, then intersect it with the other parameters
    $sql .= "((SELECT fichiers.* FROM fichiers WHERE id_user = $user) UNION (SELECT fichiers.* FROM fichiers,assigner WHERE (fichiers.id_file = assigner.id_file AND assigner.id_tag IN (SELECT autoriser.id_tag from autoriser WHERE autoriser.id_user = $user))) UNION (SELECT fichiers.* FROM fichiers,assigner,tags WHERE (fichiers.id_file = assigner.id_file AND assigner.id_tag IN (SELECT tags.id_tag from tags WHERE tags.id_user = $user))))INTERSECT";



    //same as getAllFiles
    if($request->getQueryParam("mine")=="true"){
        $sql .= " SELECT * FROM fichiers WHERE id_user = ".$_SESSION['id'] ." INTERSECT ";
    }
    if($request->getQueryParam("deleted")=="true"){
        $sql .= "(SELECT * FROM fichiers WHERE fichiers.date_supr IS NOT NULL) INTERSECT ";
    }
    else{
        $sql .= "(SELECT * FROM fichiers WHERE fichiers.date_supr IS NULL) INTERSECT ";
    }
    if($request->getQueryParam("extension") != null){
        $sql .= "(SELECT * FROM fichiers WHERE type = '".$request->getQueryParam("extension")."') INTERSECT ";
    }

    if($request->getQueryParam("tagLess")=="true"){
        $sql .= " (SELECT * FROM fichiers WHERE fichiers.id_file not in (SELECT id_file FROM assigner) ) INTERSECT ";
    }

    $ligne = $request->getQueryParam("searchBar");

    if($ligne != null){
        $ligne = explode(" ", $ligne);

        $sql .= " (SELECT * FROM fichiers WHERE ";

        for($i = 0; $i < count($ligne); $i++){
            if($ligne[$i] == ""){
                continue;
            }
            $sql .= "(nom_fichier LIKE '%".$ligne[$i]."%' OR date LIKE '%".$ligne[$i]."%' OR nom_prenom_auteur LIKE '%".$ligne[$i]."%')";
            if($i != count($ligne) - 1){
                $sql .= " OR ";
            }
        }
        if(str_ends_with($sql, " OR ")) {
            $sql = substr_replace($sql, "", -4);
        }
        $sql .= ") INTERSECT ";
    }


    else if($request->getQueryParam("union")=="true") {
        $sql .= " SELECT fichiers.* FROM fichiers ";

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
            $sql2 = " SELECT nom_categorie FROM categories";

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

            } catch (PDOException $e) {
                $error = array(
                    "message" => $e->getMessage()
                );
            }

        }
    }
    if(str_ends_with($sql, "INTERSECT ")){
        $sql = substr_replace($sql ,"", -10);
    }


    if($request->getQueryParam('limit')!= null AND $request->getQueryParam('offset') != null){
        $sql .= ") LIMIT ".$request->getQueryParam('limit');
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

//get file by id
function getFile($request,$response, $args,$value = null){
    //check if user is logged in
    $res = isSession($request,$response,$args);
    if($res ){
        return $res;
    }
    //check if user is as guest
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
            if (file_exists($download_file)) {
                // Get file size.


                $filesize = filesize($download_file);
                //get file type

                // Download file.
                $response->withHeader("Content-Type",explode('.', $file->type)[0]);



                if($value != null){
                    createThumbnail($filename,250,"../files","../files/thumbnails/");
                }
                else {
                    readfile($download_file);
                }
                return $response
                    ->withStatus(200)
                    ->withHeader('content-type',explode('.', $file->type)[0]);
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

//get file for guest
function getAllowedFile($request,$response, $args,$value = null){
    $id_file = $args['file'];
    $user = $_SESSION['id'];
    //same but with restricted access
    $sql = "((SELECT fichiers.* FROM fichiers WHERE id_user = $user) UNION (SELECT fichiers.* FROM fichiers,assigner WHERE (fichiers.id_file = assigner.id_file AND assigner.id_tag IN (SELECT autoriser.id_tag from autoriser WHERE autoriser.id_user = $user))) UNION (SELECT fichiers.* FROM fichiers,assigner,tags WHERE (fichiers.id_file = assigner.id_file AND assigner.id_tag IN (SELECT tags.id_tag from tags WHERE tags.id_user = $user))))";
    try {
        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->query($sql);
        $allowedFiles = $stmt->fetchAll(PDO::FETCH_OBJ);

        $db = null;

    }catch (PDOException $e) {
        $error = array(
            "message"=> $e->getMessage()
        );
    }

    if(!is_countable($allowedFiles)){


        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(400);
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
                    $response->withHeader('content-type',explode('.', $file->type)[0]);
                    if ($value != null) {
                        createThumbnail($filename, 250, "../files", "../files/thumbnails/");
                    }
                    else {
                            readfile($download_file);
                        }
                        return $response
                            ->withStatus(200)->withHeader('content-type', explode('.', $file->type)[0]);
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

//function that add file to server and database
function addFile( $request,$response,  $args) {
    //check if user is logged in and is not a reader
    $res = authFilesTags($request,$response,$args);

    if($res ){
        return $res;
    }
    require_once(__DIR__.'/../../getid3/getid3/getid3.php');

    $nom = $request->getParam('fileName');
    $idUser=$_SESSION['id'];
    $auteur=$request->getParam("author");
    $taille=$_FILES['file']['size'];
    $duree=$request->getParam("lenght");

    $type='.'.explode(".",$_FILES['file']['name'])[count(explode(".",$_FILES['file']['name']))-1];
    $date = $request->getParam("date");
    $str = $_FILES['file']['type']  . $type;

    //check if the file is a valid file
    if($_FILES ['file']['error'] > 0){
        $error = array(
            "message"=> "Erreur lors du transfert"
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(400);
    }
    //check if the file is not over the size limit
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

    if(!($_FILES['file']['type'] == "image/jpeg" || $_FILES['file']['type'] == "image/png" || $_FILES['file']['type'] == "image/gif" || $_FILES['file']['type'] == "video/mp4" || $_FILES['file']['type'] == "video/avi" || $_FILES['file']['type'] == "video/mpeg" || $_FILES['file']['type'] == "video/quicktime"|| $_FILES['file']['type'] == "video/mov" || $_FILES['file']['type'] == "audio/mpeg"|| $_FILES['file']['type'] =="audio/wav")){
        $error = array(
            "message"=> "Type de fichier non autorisé"
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(400);
    }
    //define the lenght of the video
    if($_FILES["file"]["type"] == "video/mpeg" || $_FILES["file"]["type"] == "video/avi" || $_FILES["file"]["type"] == "video/quicktime"|| $_FILES["file"]["type"] == "video/mov" || $_FILES['file']['type'] == "video/mp4" || $_FILES['file']['type'] == "audio/mpeg"|| $_FILES['file']['type'] =="audio/wav"){
        $getID3 = new getID3;
        $ThisFileInfo = $getID3->analyze($_FILES['file']['tmp_name']);
        $duree = floor($ThisFileInfo['playtime_seconds']);
    }




    $sql ="INSERT INTO fichiers (nom_fichier,id_user,nom_prenom_auteur,taille,duree,date,type) VALUE (:nom,:idUser,:auteur,:taille,:dure,:date,:type)";

    try {
        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':idUser', $idUser);
        $stmt->bindParam(':auteur', $auteur);
        $stmt->bindParam(':taille', $taille);
        $stmt->bindParam(':dure', $duree);

        $stmt->bindParam(':type', $str);
        $stmt->bindParam(':date', $date);

        $result=$stmt->execute();

        $db = null;
        $response->getBody()->write(json_encode($result));
        //move the file in the files folder
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

//function that add tags to a file
function addFileTags( $request,$response,  $args)
{
    //check if user is logged in and is not a reader
    $res = authFilesTags($request,$response,$args);
    if($res ){
        return $res;
    }


    $fichier = $args['file'];
    $tags = $request->getParam("tags");
    //check if the user has access to the file
    $res2 = checkIfOwnedFile($request,$response,$args);
    if($res2 ){
        return $res2;
    }


    //for each tags, add them to the database
    for($i = 0;$i < count($tags);$i++) {
        $sql = "INSERT INTO assigner (id_tag,id_file) VALUES ($tags[$i],'$fichier')";

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
            return $response->withHeader('content-type', 'application/json')->withStatus(400);
        }

    }
    return $response->withHeader('content-type', 'application/json')->withStatus(200);
}

// returns the tags of a file
function getFileTags($request,$response, $args){
    $fichier = $args['file'];
    $res = isSession($request,$response, $args); //check if user is logged in
    if($res)
        return $res;
    $res2 = checkIfOwnedFile($request,$response,$args); //check if user has access to the file
    if($res2 ){
        return $res2;
    }
    $sql = "SELECT * from tags WHERE id_tag IN(SELECT id_tag FROM assigner WHERE id_file = $fichier )";
    try {
        $DB = new DB();
        $conn = $DB->connect();
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);


        $DB = null;
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('content-type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        $error = array(
            "message" => $e->getMessage()
        );
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('content-type', 'application/json')->withStatus(400);
    }

}


function deleteFileTags( $request,$response, $args){
    $res = authFilesTags($request,$response,$args); //check if user is logged in
    if($res ){
        return $res;
    }
    $fichier = $args['file'];
    $tags = $request->getParam("tags");

    $res2 = checkIfOwnedFile($request,$response,$args); //check if user has access to the file
    if($res2 ){
        return $res2;
    }
    //for each tags, delete them to the database
    for($i = 0;$i < count($tags);$i++) {
        $sql = "DELETE from assigner where (id_file = '$fichier' AND id_tag = $tags[$i])";

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
            return $response->withHeader('content-type', 'application/json')->withStatus(400);
        }

    }
    return $response->withHeader('content-type', 'application/json')->withStatus(200);
}


//function that delete a file and also all the tags associated to it
function deleteFile( $request,$response,  $args) {

    $res = authFilesTags($request,$response,$args); //check if user is logged in
    if($res ){
        return $res;
    }

    $res2 = checkIfOwnedFile($request,$response,$args); //check if user has access to the file
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
        //if the file is not in the bin, put it in
        if($result['date_supr'] == null){
            $sql = "UPDATE fichiers SET date_supr = '$date' WHERE id_file = '$fileDelete'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $DB = null;
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(200);
        }
        else{//if the file is in the bin, delete it
            $sql = "DELETE from fichiers WHERE id_file = '$fileDelete'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $DB = null;
            deleteFileInTags($fileDelete); //delete the tags associated to the file
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

//function that delete all the tags associated to a file
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

//function that remove a deleted user from the file table
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

// stream the file to the client
function stream($request,$response, $args)
{
    $file = $args['file'];
    $file = "../files/".$file.'.mp4';
    $stream = new VideoStream($file);
    $stream->start();
}

//function that check if the file should be deleted (it was put in the bin 30days ago)
function deleteFiles30day() {
    $sql="DELETE FROM fichiers WHERE DATEDIFF(NOW(),date_supr)>0";
    try {
        $DB = new DB();
        $conn = $DB->connect();

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();

        $DB = null;
        return ;
    }catch (PDOException $e) {
        return ;
    }
}

//get all the storage values
function getStorage($request,$response, $args)
{
    //check if user is admin
    $res = isAdmin($request, $response, $args);
    if ($res) {
        return $res;
    }
    //check for storage available
    $maxSpace = disk_free_space("../files");
    $maxSpace = $maxSpace / pow(1024,3);

    $maxSpace = round($maxSpace, 2);

    //get the current space used
    $usedSpace = disk_total_space("../files");
    $usedSpace = $usedSpace / pow(1024,3);
    $usedSpace = round($usedSpace, 2);

    //create a json object with the values
    $response->getBody()->write(json_encode(array(
        "stockageLeft" => $maxSpace,
        "usedStockage" => $usedSpace
    )));


    return $response->withHeader('content-type', 'application/json')->withStatus(200);

}



function createThumbnail($image_name,$new_width,$uploadDir,$moveToDir)
{
    $path = $uploadDir."/".$image_name;

    $mime = getimagesize($path);


    if($mime['mime']=='image/png') {
        $src_img = imagecreatefrompng($path);
    }
    if($mime['mime']=='image/jpg' || $mime['mime']=='image/jpeg' || $mime['mime']=='image/pjpeg') {
        $src_img = imagecreatefromjpeg($path);
    }

    $old_x          =   imageSX($src_img);
    $old_y          =   imageSY($src_img);


        $thumb_w    =   $new_width;
        $thumb_h    =  (float) $old_y*((float)$new_width/(float)$old_x);




    if($old_x == $old_y)
    {
        $thumb_w    =   $new_width;
        $thumb_h    =   $new_width;
    }

    $dst_img        =   ImageCreateTrueColor($thumb_w,$thumb_h);

    imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);


    // New save location
    $new_thumb_loc = $moveToDir . $image_name;

    if($mime['mime']=='image/png') {
        $result = imagepng($dst_img,$new_thumb_loc,8);
    }
    if($mime['mime']=='image/gif') {
        $result = imagegif($dst_img,$new_thumb_loc,8);
    }
    if($mime['mime']=='image/jpg' || $mime['mime']=='image/jpeg' || $mime['mime']=='image/pjpeg') {
        $result = imagejpeg($dst_img,$new_thumb_loc,80);
    }
    imagedestroy($dst_img);
    imagedestroy($src_img);
    if($result)
    {
        readfile($new_thumb_loc);

    }
    unlink($new_thumb_loc);

}