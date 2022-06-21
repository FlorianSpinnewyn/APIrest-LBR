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

function forgetPassword($email,$token){

    $message = '<html>
    <body style="background-color:#fffee6">
        <p>Bonjour,</p>
        <p>Vous avez demande a reinitialiser votre mot de passe.</p>
        <p>Cliquez sur le bouton suivant pour reinitialiser votre mot de passe</p>
        <table>
            <tbody>
                <td><a style="color: red; text-decoration: underline; display: table-cell; text-align: center; height: 60px; width: 600px; vertical-align: middle;" href="http://localhost:3000/Mdp-Page?token='.$token.'" target="_blank" rel="noopener noreferrer">Reinitialiser<br>
                 </a></td>
            </tbody>
        </table>
    </body>


    </html>';
    $message = wordwrap($message, 70, "\r\n");
    $headers = "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: LBR | Drive <no-reply@lesbriquesrouges.fr>' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($email, 'Demande de réinitialisation de mot de passe', $message,$headers);
}
