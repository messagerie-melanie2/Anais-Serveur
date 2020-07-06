<?php
/**
*
*   serveur anais - interface de presentation de l'annuaire melanie2
*
*  Fichier : anaismoz.php
*  Role : fichier principal de l'application (client >= 5.4)
*
*/

//si true fonctionne en mode production != debug
$production=true;


define('ANAIS_DEPLOIEDIR', __DIR__);


// configuration selon version (5.4 par defaut)
$extver=5.4;
if (isset($_REQUEST['extver'])){
  $extver=$_REQUEST['extver']; 
}
if (6.2 > $extver){
  include(ANAIS_DEPLOIEDIR.'/anaisconfig/configm2.php');
} else{
  include(ANAIS_DEPLOIEDIR.'/anaisconfig/config62.php');
}


//inclusion des fichiers
include(ANAIS_DEPLOIEDIR.'/anaisldap/anaisldap.php');
include(ANAIS_DEPLOIEDIR.'/anaisldap/anaisdata.php');
include(ANAIS_DEPLOIEDIR.'/anaisconfig/anaisconfig.php');
include(ANAIS_DEPLOIEDIR.'/anaisutil/anaisutil.php');
include(ANAIS_DEPLOIEDIR.'/anaisdoc/anaisdoc.php');

require(ANAIS_DEPLOIEDIR.'/anaisdoc/anaisdocmel.php');
require(ANAIS_DEPLOIEDIR.'/anaisdoc/anaispropbal2.php');


/**
*  Options de configuration  ini_set
*/
//ini_set('memory_limit','16M');

ini_set('max_execution_time', 60);


if ($production){
  error_reporting(0);
  ini_set('display_errors',0);
}
else{
  error_reporting(E_ALL);
  ini_set('display_startup_errors',1);
}


session_name('anaismoz');
//identifiant de session transmis en paramètre
if (isset($_REQUEST['sessionid'])){
  $val=$_REQUEST['sessionid'];
  if ($val!=''){
    $val=urldecode($val);
    $val=stripslashes($val);
    session_id($val);
  }
}
session_start();



//////////////////////////////
//Debut

/**
* Initialisation log des événements
*/
//v0.7:define_syslog_variables();
openlog('Anais',LOG_PID|LOG_CONS,LOG_MAIL);



//v0.2.62 - Réponse avec code erreur lorsque la session est expirée et l'identifiant utilisateur non fourni
if (!isset($_SESSION['utilisateur']) &&
    isset($_REQUEST['sessionid']) &&
    !isset($_REQUEST['anaismoz-uid'])){
      
  session_destroy();
  header('Content-Type: text/xml');
  print('<?xml version="1.0" encoding="UTF-8"?>');
  printf('<anais:anaismoz errcode="%d" errmsg="%s"'.
          ' xmlns:anais="http://anais.melanie2.i2/schema"'.
          ' xmlns="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"/>',-10,'Expiration de la session');
  exit();
}



/**
*  initialisation donnees session
*/
anaisInitSession();


/**
*  analye de la demande
*/
$res=anaisAnalyseRequete();


if (false==$res){
  anaisDocErreur();
  exit();
}


/**
/*  logguer les numéros de version
*/
$appver='inconnue';//v0.6 - version du client de messagerie
$bLogVer=false;
if (isset($_REQUEST['appver'])){
  $appver=$_REQUEST['appver'];
  $bLogVer=true;
}
if ($bLogVer){
  $infover=sprintf('Anais - Utilisateur="%s" Version extension="%s" Version d\'application="%s"',$_SESSION['utilisateur'],$extver,$appver);
  syslog(LOG_INFO,$infover);
}


/**
* Conteneur du service utilisateur au premier appel
*/
if (empty($_SESSION['dnserviceutil'])) {

  //déterminer conteneur du service de l'utilisateur
  anaisConteneurUtil();
  anaisSetLastError(0,'');//non bloquant

}
//chemin courant au démarrage
if (empty($GLOBALS['requete']['operation']) &&
    !empty($_SESSION['dnserviceutil']) &&
    empty($GLOBALS['requete']['chemin']) ) {
  $serveur=$GLOBALS['anaisconfig']['annuaires'][0]['serveur'];//1er annuaire -> Mélanie!
  $GLOBALS['requete']['chemin']='ldap://'.$serveur.'/'.$_SESSION['dnserviceutil'];
}

/*
* v0.6 - requete photo
*/
if ('img'==$GLOBALS['requete']['operation']){
 anaisPropGetPhoto();
 exit();
}


/**
*  requete annuaire
*/
$res=anaisExecReq();

if ($res==false){
  anaisDocErreur();
  exit();
}


/**
*  production du document de sortie
*/
//fonction du document de sortie
$foncdoc=$GLOBALS['anaisconfig']['application']['document']['fonction'];
$foncdoc();


?>
