<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require __DIR__ . '/function_categories.php';
require __DIR__ . '/function_connection.php';
require __DIR__ . '/function_users.php';
require __DIR__ . '/function_files.php';
require __DIR__ . '/function_tags.php';
require __DIR__ . '/function_logs.php';
require __DIR__ . '/VideoStream.php';
require __DIR__ . '/../dataBaseAcces.php';
require __DIR__ . '/function_mail.php';



return function (App $app) {
    $container = $app->getContainer();


    /**--Connection--**/
    $app->post('/connection', function (Request $request, Response $response, array $args) use ($container,$app) {
        login($request,$response,$args,$app);
    });
    $app->post('/connection/google', function (Request $request, Response $response, array $args) use ($container,$app) {
        loginGoogle($request,$response,$args,$app);
    });
    $app->put('/connection', function (Request $request, Response $response, array $args) use ($container) {
        changePassword($request,$response,$args);
    });
    $app->delete('/connection', function (Request $request, Response $response, array $args) use ($app, $container) {

        return logout($request,$response,$args);
    });


    $app->get('/', function (Request $request, Response $response, array $args) use ($container) {
        return '{"test":"true"}';
    });
    /**--FILES--**/


    $app->get('/files', function (Request $request, Response $response, array $args) use ($container) {
        $data = getAllFiles($request,$response,$args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });

    $app->get('/files/{file}', function (Request $request, Response $response, array $args) use ($container) {
        $data = getFile($request,$response,$args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });

    $app->post('/files', function (Request $request, Response $response, array $args) use ($container) {
        $data = addFile($request,$response,$args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });
    $app->get('/files/{file}/tags', function (Request $request, Response $response, array $args) use ($container) {
        $data = getFileTags($request,$response, $args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });

    $app->post('/files/{file}/tags', function (Request $request, Response $response, array $args) use ($container) {
        $data = addFileTags($request,$response, $args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });

    $app->delete('/files/{file}/tags', function (Request $request, Response $response, array $args) use ($container) {
        $data = deleteFileTags($request,$response, $args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });

    $app->delete('/files/{file}', function (Request $request, Response $response, array $args) use ($container) {
        $data = deleteFile($request,$response, $args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });

    /**--USERS--**/

    $app->get('/me', function (Request $request, Response $response, array $args) use ($container) {
        return getYourData($request,$response, $args);
    });

    $app->get('/users', function (Request $request, Response $response, array $args) use ($container) {
        return getUsersAll($request,$response,$args);
    });

    $app->get('/users/{user}', function (Request $request, Response $response, array $args) use ($container) {
        return getUser($request,$response, $args);
    });

    $app->post('/users', function (Request $request, Response $response, array $args) use ($container) {
        return addUser($request,$response, $args);
    });

    $app->get('/users/{user}/tags', function (Request $request, Response $response, array $args) use ($container) {
        return getAssignedTags($request,$response, $args);
    });
    $app->post('/users/{user}/tags', function (Request $request, Response $response, array $args) use ($container) {
        return addAllowedTagToUser($request,$response, $args);
    });
    $app->delete('/users/{user}/tags', function (Request $request, Response $response, array $args) use ($container) {
        return removeAllowedTagToUser($request,$response, $args);
    });

    $app->put('/users/{user}', function (Request $request, Response $response, array $args) use ($container) {
        return updateUser($request,$response, $args);
    });

    $app->delete('/users/{user}', function (Request $request, Response $response, array $args) use ($container) {
        return deleteUser($request,$response, $args);
    });
    
    /**--TAGS--**/
    $app->get('/tags', function (Request $request, Response $response, array $args) use ($container) {
        $data = getAllTags($request,$response,$args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });

    $app->post('/tags', function (Request $request, Response $response, array $args) use ($container) {
        $data = addTag($request,$response, $args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });

    $app->delete('/tags/{tag}', function (Request $request, Response $response, array $args) use ($container) {
        $data = deleteTag($request,$response, $args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });

    $app->put('/tags/{tag}', function (Request $request, Response $response, array $args) use ($container) {
        $data =  modifyTag($request,$response, $args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });




    //----CATEGORIE----//
    $app->get('/categories', function (Request $request, Response $response, array $args) use ($container) {
        $data =  getAllCategories($response, $args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });
    $app->post('/categories', function (Request $request, Response $response, array $args) use ($container) {
        $data = addCategorie( $request,$response,  $args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });

    $app->put('/categories/{category}',function (Request $request, Response $response, array $args) use ($container) {
        $data = renameCategorie($request,$response, $args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });
    $app->delete('/categories/{category}', function (Request $request, Response $response, array $args) use ($container) {
        $data = deleteCategorie($request,$response, $args);
        addLog($request->getMethod(). " ".$request->getUri()->getPath(),$data->getStatusCode());
        return $data;
    });


    $app->get('/stream/{file}', function (Request $request, Response $response, array $args) use ($container) {
         stream($request,$response, $args);
    });


};

