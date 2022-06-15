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



return function (App $app) {
    $container = $app->getContainer();


    /**--Connection--**/
    $app->post('/connection', function (Request $request, Response $response, array $args) use ($container) {
        login($request,$response,$args);
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
        return getAllFiles($request,$response,$args);
    });

    $app->get('/files/{file}', function (Request $request, Response $response, array $args) use ($container) {
        return getFile($request,$response, $args);
    });

    $app->post('/files', function (Request $request, Response $response, array $args) use ($container) {
        return addFile($request,$response, $args);
    });

    $app->post('/files/{file}/tags', function (Request $request, Response $response, array $args) use ($container) {
        addFileTags($request,$response, $args);
    });

    $app->delete('/files/{file}/tags', function (Request $request, Response $response, array $args) use ($container) {
        return deleteFileTags($request,$response, $args);
    });

    $app->delete('/files/{file}', function (Request $request, Response $response, array $args) use ($container) {
        return deleteFile($request,$response, $args);
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
        return getAllTags($request,$response,$args);
    });

    $app->post('/tags', function (Request $request, Response $response, array $args) use ($container) {
        return addTag($request,$response, $args);
    });

    $app->delete('/tags/{tag}', function (Request $request, Response $response, array $args) use ($container) {
        return deleteTag($request,$response, $args);
    });

    $app->put('/tags/{tag}', function (Request $request, Response $response, array $args) use ($container) {
        return modifyTag($request,$response, $args);
    });




    //----CATEGORIE----//
    $app->get('/categories', function (Request $request, Response $response, array $args) use ($container) {
        return getAllCategories($response, $args);
    });
    $app->post('/categories', function (Request $request, Response $response, array $args) use ($container) {
        return addCategorie( $request,$response,  $args);
    });

    $app->put('/categories/{category}',function (Request $request, Response $response, array $args) use ($container) {
        return renameCategorie($request,$response, $args);
    });
    $app->delete('/categories/{category}', function (Request $request, Response $response, array $args) use ($container) {
        return deleteCategorie($request,$response, $args);
    });


    $app->get('/stream/{file}', function (Request $request, Response $response, array $args) use ($container) {
         stream($request,$response, $args);
    });


};

