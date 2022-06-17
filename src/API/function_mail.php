<?php

function finalSignUp($email, $mdp){

    $message = "Voici vos identifiants de connexion\r\nemail :.'$email\r\n mot de passe :$mdp\r\nVous pouvez vous connecter sur le site en utilisant ces identifiants.<a href ='http://www.example.com'>";
    $message = wordwrap($message, 70, "\r\n");
    $headers = 'From: LBR | Drive <no-reply@lesbriquesrouges.fr>' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($email, "Confimartion d'inscription", $message,$headers);
}

function  SignUp($email, $mdp){
    $message ="Voici vos identifiants de connexion\r\nemail :.'$email\r\n mot de passe temporaire :$mdp\r\nVous pouvez vous connecter sur le site en utilisant ces identifiants afin de modifier votre mot de passe.<a href ='http://www.example.com'>";
    $message = wordwrap($message,70,"\r\n");
    $headers = 'From: LBR | Drive <no-reply@lesbriquesrouges.fr>' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($email, "Confirmation d'inscription",$message,$headers);
}

function forgetPassword($email,$pwd){

    $message = '<html>
    <body style="background-color:#fffee6">
        <p>Bonjour,</p>
        <p>Vous avez demandé à réinitialiser votre mot de passe.</p>
        <p>Cliquez sur le bouton suivant pour réinitialiser votre mot de passe</p>
        <button style="margin-left:22%;text-decoration: none;padding: 8px;font-family: arial;font-size: 1em;color: #FFFFFF;background-color: #ff0000;border-radius: 15px;-webkit-border-radius: 15px;-moz-border-radius: 15px;" href="#">Réinitialiser</button>
    </body>


    </html>';
    $message = wordwrap($message, 70, "\r\n");
    $headers = "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: LBR | Drive <no-reply@lesbriquesrouges.fr>' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($email, 'Demande de réinitialisation de mot de passe', $message,$headers);
}
