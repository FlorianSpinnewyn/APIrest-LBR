<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require __DIR__ . '/../src/function_users.php';
require __DIR__ . '/../src/function_files.php';
require __DIR__ . '/../src/function_tags.php';
require __DIR__ . '/../src/dataBaseAcces.php';


return function (App $app) {

    /**--FILES-- */
    $app->get('/file/all', function (Request $request, Response $response, array $args) {
        return getFilesAll($response, $args);
    });

    $app->post('/file/add', function (Request $request, Response $response, array $args)  {
        return AddFile($request,$response, $args);
    });

    /**--USERS-- */
    $app->get('/user/all', function (Request $request, Response $response, array $args)  {
        return getUsersAll($response, $args);
    });

    $app->post('/user/add', function (Request $request, Response $response, array $args)  {
        return addUser($request,$response, $args);
    });

    
    //----TAGS-----//
    $app->get('/tag/all', function (Request $request, Response $response, array $args)  {
        return getAllTags($response, $args);
    });

    $app->post('/tag/add', function (Request $request, Response $response, array $args)  {
        return addTag($request,$response, $args);
    });

    $app->delete('/tag/{tag}', function (Request $request, Response $response, array $args)  {
        return deleteTag($request,$response, $args);
    });

    $app->put('/tag/{tag}/categorie/{categorie}', function (Request $request, Response $response, array $args)  {
        return MoveCategorie($request,$response, $args);
    });

    $app->put('/tag/{tag}/tag/{newTag}', function (Request $request, Response $response, array $args)  {
        return renameTag($request,$response, $args);
    });


    //----CATEGORIE----//
    $app->get('category/all', function (Request $request, Response $response, array $args)  {
        return getAllCategories($response, $args);
    });

};

