<?php
/**
*   serveur anais - interface de presentation de l'annuaire melanie2
*
*  Fichier : anaisutil.inc
*  Rôle : fonctions utilitaires
*/


// numéro de version de l'application
define('ANAIS_SRV_VERSION', '1.12');


/**
*  initialise les variables d'application stockées dans la variable $_SESSION
*          appelee a chaque appel du script
*
*  @return  retourne false si erreur, true si ok
*
*  implémentation : au premier appel, crée les cles 'anaisconfig', 'req', 'ldapresults', et 'res'
*  'anaisconfig' : tableau de configuration de l'application chargee par anaisChargeConfig
*  'req': requete contient les cles 'op' et 'dn', initialise par anaisAnalyseRequete
*  'ldapresults' : tableau des resultats ldap, obtenus par 1 ou plusieurs appels a anaisLectureLdap
*  'res': resultat d'appel de fonction, contient les cles 'code' (0 si succes, autre si erreur) et 'message'
*                  aux appels suivants, remet a 'blanc' req et res.
*  V0.11 la configuration est stockée dans $_SESSION
*  Les autres variables sont stockées dans $GLOBALS
*/
function anaisInitSession(){

  if (!isset($GLOBALS['requete'])){
    $GLOBALS['requete']=Array();
  }
  $GLOBALS['requete']['operation']='';
  $GLOBALS['requete']['chemin']='';

  if (!isset($GLOBALS['ldapresults'])){
    $GLOBALS['ldapresults']=Array();
  }
  if (!isset($GLOBALS['resultat'])){
    $GLOBALS['resultat']=Array();
  }
  $GLOBALS['resultat']['code']=0;
  $GLOBALS['resultat']['message']='';
  if (!isset($_SESSION['utilisateur'])){
    $_SESSION['utilisateur']='';
    $_SESSION['dnserviceutil']='';
  }

  return true;
}



/**
*  analyse la requete
*  détermine les paramètres 'op' et 'dn'
*
*  @return  true si succes, false si erreur
*
*  implémentation :   positionne 'op' et 'dn' dans les variable session $GLOBALS["requete"]
*  V0.11 récupère le nom d'utilisateur dans l'entête 'anaismoz-uid'
*  v0.2 (13-08-2004) les paramètres sont transmis par la méthode POST
*  (on conserve l'ancienne méthode pour une compatibilité ascendante)
*
*  V 0.2 (19-08-2004) $GLOBALS["requete"]["dnobjet"] est remplacé par $GLOBALS["requete"]["chemin"]
*  et contient le chemin ldap de l'objet de la requête: ldap://<serveur>/<dn>
*  V0.2 (25/08/2004) opération initanais supprimée -> opération vide avec chemin
*  V0.2 (01/09/2004) opération paramétrées -> anaismoz-par
*  0.2.202    02/12/2004  Paramètres de requête : remplacement de \' par '
*  0.2.203    03/12/2004  Utilisation de la fonction 'stripslashes' dans la fonction anaisAnalyseRequete pour retirer les \
*  10/01/2005  la méthode n'est plus testée on prend le tableau $_REQUEST
*
*  Paramètres gérés:
*    anaismoz-op  :  type d'opération
*    anaismoz-dn  : distinguishedname d'un objet d'annuaire
*    anaismoz-uid  : uid de l'utilisateur
*    anaismoz-par : paramètres additifs (valeurs séparées par ';')
*/
function anaisAnalyseRequete(){

  $method=$_SERVER['REQUEST_METHOD'];
  if (!(0==strcmp('POST', $method) ||
        0==strcmp('GET', $method))){
    anaisSetLastError(-1, "Methode non authorisee");
    return false;
  }

  if (!empty($_REQUEST['anaismoz-op'])){
    $val=$_REQUEST['anaismoz-op'];
    if (!in_array($val, ['propbal', 'litarbre', 'listearbre', 'litboite',
                        'listeboites', 'rechboite', 'rechbs', 'litboitetout', 'membres',
                        'membrede', 'listebranche', 'gest-partages', 'gest-liste',
                        'secr', 'rechtel', 'img'])){
      anaisSetLastError(-1, "Opération non authorisee");
      return false;
    }
    $GLOBALS['requete']['operation']=$val;
  }

  if (!empty($_REQUEST['anaismoz-dn'])){
    $val=$_REQUEST['anaismoz-dn'];
    $val=urldecode($val);
    $val=strtolower($val);//compatibilté avec version dont le chemin commence par LDAP:// -> ldap://

    // verifications du chemin
    $compos=explode('/', $val);
    if (4!=count($compos) ||
        0!=strcmp("ldap:", $compos[0]) ||
        !empty($compos[1])){
      anaisSetLastError(-1, "Erreur de chemin pour l'annuaire");
      return false;
    }
    $serveur=$compos[2];

    // mantis 4758 : transformer serveur ldap par celui configuré
    $annuaireid=$GLOBALS['anaisconfig']['annuaires'][0]['annuaireid'];
    if ($serveur!==$annuaireid){
      $srvconfig=$GLOBALS['anaisconfig']['annuaires'][0]['serveur'];
      if ($serveur!==$srvconfig){
        $nb=1;
        $val=str_replace($serveur."/", $srvconfig."/", $val, $nb);
      }
    }
    
    $dn=$compos[3];
    $checkdn=ldap_explode_dn($dn, 0);
    if (false==$checkdn){
      anaisSetLastError(-1, "Erreur de vérification du chemin");
      return false;
    }

    $val=stripslashes($val);

    $GLOBALS['requete']['chemin']=$val;
  }
  
  if (isset($_REQUEST['anaismoz-uid'])){
  
    $val=$_REQUEST['anaismoz-uid'];
    
    // test de longueur
    if (!empty($GLOBALS['anaisconfig']['annuaires'][0]['limit_uid']) &&
        $GLOBALS['anaisconfig']['annuaires'][0]['limit_uid'] < strlen($val)){
      // log evenement
      logEvenement('Longueur limite d\'uid depassee');
      $val="";
    } 
    // test de validite des caracteres
    if (!empty($GLOBALS['anaisconfig']['annuaires'][0]['filtre_uid']) &&
        1==preg_match($GLOBALS['anaisconfig']['annuaires'][0]['filtre_uid'], $val)){
      anaisSetLastError(-1, "Identifiant non conforme");
      // log evenement
      logEvenement('Identifiant uid non conforme');
      $val="";
    }

    $val=ldap_escape($val, null, LDAP_ESCAPE_FILTER);
    $_SESSION['utilisateur']=$val;
    $_SESSION['dnserviceutil']='';
  }
  
  if (isset($_REQUEST['anaismoz-par'])){
  
    $val=$_REQUEST['anaismoz-par'];
    
    // test de longueur
    if (!empty($GLOBALS['anaisconfig']['annuaires'][0]['anaismoz-par-max']) &&
        $GLOBALS['anaisconfig']['annuaires'][0]['anaismoz-par-max'] < strlen($val)){
      // log evenement
      logEvenement('Limite de saisie depassee');
      
      $val=substr($val, 0, $GLOBALS['anaisconfig']['annuaires'][0]['anaismoz-par-max']);
      $_REQUEST['anaismoz-par']=$val;
    }
    
    // test de validite des caracteres
    if (!empty($GLOBALS['anaisconfig']['annuaires'][0]['anaismoz-par-reg']) &&
        1==preg_match($GLOBALS['anaisconfig']['annuaires'][0]['anaismoz-par-reg'], $val)){
      anaisSetLastError(-1, "Erreur de parametre de saisie");
      // log evenement
      logEvenement('Erreur de parametre de saisie');
      
      return false;
    }
    
    $val=urldecode($val);
    $val=ldap_escape($val, null, LDAP_ESCAPE_FILTER);
    $GLOBALS['requete']['param']=$val;
  }
  
  return true;
}


function logEvenement($msg){

  $user=$_SESSION['utilisateur'];
  $ip=getIPClient();
  $msg="Anais - Utilisateur='$user' - ip='$ip' - Message='$msg'";
  syslog(LOG_WARNING, $msg);
}

function getIPClient(){

  if (!empty($_SERVER['HTTP_CLIENT_IP'])) //check ip from share internet
    return $_SERVER['HTTP_CLIENT_IP'];

  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) //to check ip is pass from proxy
    return $_SERVER['HTTP_X_FORWARDED_FOR'];

  return $_SERVER['REMOTE_ADDR'];
}


/**
*  positionne la variable d'erreur pour l'application
*          utilise par les fonctions
*  @param  $code code d'erreur
*  @param  $message message d'erreur
*
*  @return  false si erreur, true si ok
*
*  implémentation : positionne $GLOBALS["resultat"]
*
*/
function anaisSetLastError($code,$message){
  
  if (!isset($GLOBALS['resultat'])){
    $GLOBALS['resultat']=Array();
  }
  $GLOBALS['resultat']['code']=$code;
  $GLOBALS['resultat']['message']=$message;

  return true;
}


/**
*  retourne le code et le message de la dernière erreur
*
*  @param  aucun
*
*  @return  retourne un tableau avec les clés 'code' et 'message'
*  code est le code d'erreur (0 si succès)
*  message contient éventuellement un message d'erreur
* retourne false si erreur
*
*  implémentation :
*
*/
function anaisGetLastError(){
  
  $res=Array();
  if (isset($GLOBALS['resultat'])){
    $res['code']=$GLOBALS['resultat']['code'];
    $res['message']=$GLOBALS['resultat']['message'];
  }
  else return false;
  
  return $res;
}


/**
*  execute la ou les operations correspondant a la requete
*
*  @param  extraits dans la variable $GLOBALS
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*  les donnees ldap sont stockees dans $GLOBALS['ldapresults'] qui est un tableau de donnees ldap
*  implémentation :
*  V0.1 : appel de anaisLectureLdap (pls fois pour le démarrage - 1 fois par action)
*  V0.11 : selon la valeur de l'opération, appel de anaisExecDem, anaisExecInit ou anaisExecOp
*  V0.2 (18-08-2004) pour opération initanais si chemin courant erroné retourner arborescence de base -> fait dans anaisExecDem le 23-08-2004
*  V0.2 (23-08-2004) vérification des paramètres de requête (opérations supportées) et validité du chemin ldap
*  V0.2 (25/08/2004) opération initanais supprimée -> opération vide avec chemin
*  V0.2 (31/08/2004) mise à jour nouveau format de configuration - opérations simples/composées/spécifiques
*  V0.2 (10/09/2004) si chemin courant non valide (pas de configuration/serveur non géré) -> vide
*/
function anaisExecReq(){

  //parametres de la requete
  if (!isset($GLOBALS['requete'])){
    anaisSetLastError(-1,'Paramètres de requête inexistants');
    return false;
  }

  //vérification des paramètres de requête
  $op=$GLOBALS['requete']['operation'];
  $chemin=$GLOBALS['requete']['chemin'];
  
  if ($chemin!=''){
    $compos=anaisComposantChemin($chemin);
    if (false==$compos){
      anaisSetLastError(-1,'Chemin de l\'objet non conforme dans la requête');
      return false;
    }
  }
  //si operation '' vérifier qu'une configuration d'annuaire correspond au chemin, sinon -> chemin vide
  if (''==$op){
    $cfg=anaisConfigOperation('litboite',$chemin);
    if (false==$cfg){
      $GLOBALS['requete']['chemin']='';
      $chemin=$GLOBALS['requete']['chemin'];
      anaisSetLastError(0,'');
    }
  }

  //configuration de l'opération
  $config=anaisConfigOperation($op,$chemin);
  if (false==$config){
    anaisSetLastError(-1,'Paramètres de requête incorrects ou non supportés');
    return false;
  }

  if (isset($config['anaisdata'])){//opération spécifique
    
    $result=$config['anaisdata']();

    if ($result==false){
      //sur erreur effacer les resultats ldap
      unset($GLOBALS['ldapresults']);
      return false;
    }
  }
  else if (isset($config['anaisldap'])){//opération simple

    $res=anaisExecLdap($GLOBALS['requete']['operation'],$GLOBALS['requete']['chemin']);
    if (!$res){
      //sur erreur effacer les resultats ldap
      unset($GLOBALS['ldapresults']);
      anaisFermeConnLdap();
      return false;
    }
  }
  else{//opération composée

    $res=anaisExecActions($config);
    if (!$res){
      //sur erreur effacer les resultats ldap
      unset($GLOBALS['ldapresults']);
      return false;
    }
  }
  return true;
}





/**
*  convertit les chaine avec htmlspecialchars
*
*  @param  $chaine chaine à convertir
*
*  @return chaine convertie
*
*  implémentation :
*
*/
function anaisConvertChaine($chaine){
  return htmlspecialchars($chaine,ENT_COMPAT, "", false);
}



/**
*  calcule le distinguishedname du service de l'utilisateur
*
*  @param  aucun
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : le dn du conteneur est calculé sur le base du dn de l'utilisateur
*  le nom de l'utilisateur est dans la variable $_SESSION['utilisateur']
*  une requête ldap est effectué pour obtenir le dn de l'utilisateur
*  le dn du conteneur est calculé en retenant les 7 premiers composants du dn utilisateur
*  le dn du conteneur est mémorisé dans la variable $_SESSION['dnserviceutil'].
*
*  mantis 4151 : 2 conteneurs utilisateur possible (seealso)
*/
function anaisConteneurUtil(){

  if (empty($_SESSION['utilisateur'])) {
    anaisSetLastError(-1,'nom d\'utilisateur absent');
    return false;
  }
  //parcourir les annuaires pour retrouver l'utilisateur
  $filtre='uid='.$_SESSION['utilisateur'];
  $attrs=array('dn');
  $dn='';
  $conn;

  foreach ($GLOBALS['anaisconfig']['annuaires'] as $annuaire){

    //obtenir connection annaire
    $conn=anaisConnectionLdap($annuaire['serveur'],$annuaire['srvport']);
    if ($conn==false)
      continue;

    @ldap_set_option($GLOBALS['ldapconn']['conn'], LDAP_OPT_DEREF, LDAP_DEREF_NEVER);

    $ldapresult=@ldap_search($conn,$annuaire['racine'],$filtre,$attrs);
    if ($ldapresult==false){
      continue;
    }
    $nb=ldap_count_entries($conn,$ldapresult);
    $ldap_entree=@ldap_first_entry($conn,$ldapresult);
     if ($ldap_entree==false){
       continue;
     }
    $dn=@ldap_get_dn($conn,$ldap_entree);
    @ldap_free_result($ldap_entree);

    break;
  }
  if ($dn==''){
    anaisSetLastError(-1,'l\'utilisateur n\'a pas été trouvé dans l\'annuaire');
    return false;
  }

  //mantis 3107
  $comp_racine=explode(',', $dn);
  $nb=count($comp_racine);

  if ($nb<7){
    anaisSetLastError(-1,'le dn de l\'utilisateur a moins de 7 composantes!!!');
    return false;
  }
  $service='';
  $filtre='(&(objectclass=organizationalUnit)(mineqtypeentree=NSER))';
  $attrs=array('mineqtypeentree', 'seealso');
  $dn='';
  $seealso='';

  for ($c=6;$c<$nb;$c++){

    $service='';
    $dn='';
    $bsep=false;
    //construire dn
    for ($b=$nb-$c; $b<$nb; $b++){
      if ($bsep)
        $service.=',';
      $service.=$comp_racine[$b];
      $bsep=true;
    }

    //rechercher conteneur
    $ldapresult=ldap_read($conn, $service, $filtre, $attrs);

    if (false!==$ldapresult){

      $ldap_entree=ldap_first_entry($conn, $ldapresult);

      if (false!==$ldap_entree){
        $dn=ldap_get_dn($conn, $ldap_entree);
        
        $vals=@ldap_get_values($conn, $ldap_entree, 'seealso');
        
        if (false!==$vals && 0<$vals['count']){
          $seealso=$vals[0];
        }
      }
    }

    ldap_free_result($ldapresult);
    
    if (!empty($seealso)){
     $_SESSION['dnserviceutil2']=$seealso;//==$service
    }

    //tester conteneur
    if (!empty($dn)){
     $_SESSION['dnserviceutil']=$dn;//==$service
     return true;
    }
  }
  return false;
  //fin mantis 3107
}


/**
*  convertit les valeurs des dn pour le document de sortie
*
*  @param  $chaine chaine à convertir
*
*  @return chaine convertie
*
*  implémentation : convertit les dn en minuscules et appelle htmlspecialchars
*
*/
function anaisConvertDnDoc($chaine){
  return htmlspecialchars(strtolower($chaine),ENT_COMPAT, "", false);
}


/**
*  execute automatique une liste d'actions décrites dans un élément <actions>
*
*  @param  $config élément de configuration d'opération (<operation>)
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : cette fonction est appelée lorsqu'un élément opération ne contient
*  ni attribut 'anaisldap' ni 'anaisdata'
*
*  V0.2 (07/09/2004) Si l'attribut 'chemin' n'est pas spécifié, c'est le chemin 'anaismoz-dn' qui est utilisé
*/
function anaisExecActions($config){

  $actions=array();
  $nb=0;

  foreach($config['actions'] as $action){
    if (is_array($action)){
      $actions[$nb]=$action;
      $nb++;
    }
  }

  //parcours des actions de démarrage
  foreach($actions as $action){
    $op=&$action['operation'];
    $chemin='';
    if (isset($action['chemin'])) $chemin=&$action['chemin'];
    else $chemin=$GLOBALS['requete']['chemin'];

    //listage ldap
    $res=anaisExecLdap($op,$chemin);

    if (!$res){
      unset($actions);
      anaisFermeConnLdap();
      return false;
    }
  }
  unset($actions);
  anaisFermeConnLdap();
  return true;
}



/**
*  remplace les paramètres d'un filtre (%v1 -> 1er paramètre, %v2 -> 2eme paramètre)
*
*  @param  $filtre  chaine du filtre à traiter
*  @param  $param  chaine de paramètres
*
*  @return si succes retourne la chaine mise en forme, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : $param contient des valeurs séparées par des ';'
*  $filtre contient les paramètres identifiés par %v<x> ou <x> est l'index dans la chaine de paramètres
*
*/
function anaisFormatFiltre($filtre,$param){
  
  $chaine=$filtre;
  $tab=explode(';',$param);

  for ($i=1;$i<=count($tab);$i++){
    $chaine=str_replace("%v$i",$tab[$i-1],$chaine);
  }
  return $chaine;
}


// https://stackoverflow.com/questions/8560874/php-ldap-add-function-to-escape-ldap-special-characters-in-dn-syntax
if (!function_exists('ldap_escape')) {
    define('LDAP_ESCAPE_FILTER', 0x01);
    define('LDAP_ESCAPE_DN',     0x02);

    /**
     * @param string $subject The subject string
     * @param string $ignore Set of characters to leave untouched
     * @param int $flags Any combination of LDAP_ESCAPE_* flags to indicate the
     *                   set(s) of characters to escape.
     * @return string
     */
    function ldap_escape($subject, $ignore = '', $flags = 0)
    {
        static $charMaps = array(
            LDAP_ESCAPE_FILTER => array('\\', '*', '(', ')', "\x00"),
            LDAP_ESCAPE_DN     => array('\\', ',', '=', '+', '<', '>', ';', '"', '#'),
        );

        // Pre-process the char maps on first call
        if (!isset($charMaps[0])) {
            $charMaps[0] = array();
            for ($i = 0; $i < 256; $i++) {
                $charMaps[0][chr($i)] = sprintf('\\%02x', $i);;
            }

            for ($i = 0, $l = count($charMaps[LDAP_ESCAPE_FILTER]); $i < $l; $i++) {
                $chr = $charMaps[LDAP_ESCAPE_FILTER][$i];
                unset($charMaps[LDAP_ESCAPE_FILTER][$i]);
                $charMaps[LDAP_ESCAPE_FILTER][$chr] = $charMaps[0][$chr];
            }

            for ($i = 0, $l = count($charMaps[LDAP_ESCAPE_DN]); $i < $l; $i++) {
                $chr = $charMaps[LDAP_ESCAPE_DN][$i];
                unset($charMaps[LDAP_ESCAPE_DN][$i]);
                $charMaps[LDAP_ESCAPE_DN][$chr] = $charMaps[0][$chr];
            }
        }

        // Create the base char map to escape
        $flags = (int)$flags;
        $charMap = array();
        if ($flags & LDAP_ESCAPE_FILTER) {
            $charMap += $charMaps[LDAP_ESCAPE_FILTER];
        }
        if ($flags & LDAP_ESCAPE_DN) {
            $charMap += $charMaps[LDAP_ESCAPE_DN];
        }
        if (!$charMap) {
            $charMap = $charMaps[0];
        }

        // Remove any chars to ignore from the list
        $ignore = (string)$ignore;
        for ($i = 0, $l = strlen($ignore); $i < $l; $i++) {
            unset($charMap[$ignore[$i]]);
        }

        // Do the main replacement
        $result = strtr($subject, $charMap);

        // Encode leading/trailing spaces if LDAP_ESCAPE_DN is passed
        if ($flags & LDAP_ESCAPE_DN) {
            if ($result[0] === ' ') {
                $result = '\\20' . substr($result, 1);
            }
            if ($result[strlen($result) - 1] === ' ') {
                $result = substr($result, 0, -1) . '\\20';
            }
        }

        return $result;
    }
}

// convertit un chemin ldap en chemin client
function ConvertCheminClient($chemin){

  if (empty($chemin))
    return "";
    
  $config=anaisGetConfigAnnuaire($chemin);
  if (false===$config)
    return "";
    
  $nb=1;
  $cheminclient=str_replace("//".$config['serveur']."/", "//".$config['annuaireid']."/", $chemin, $nb);

  return $cheminclient;
}

// comparaison de chaines après conversion des accents
// compare sans tenir compte de la casse
function StrCompareNoAccents($a, $b){

  $caracteres=array('à'=>'a',
                    'â'=>'a',
                    'ä'=>'a',
                    'ç'=>'c',
                    'é'=>'e',
                    'è'=>'e',
                    'ê'=>'e',
                    'ë'=>'e',
                    'î'=>'i',
                    'ï'=>'i',
                    'ô'=>'o',
                    'ö'=>'o',
                    'ù'=>'u',
                    'û'=>'u',
                    'ü'=>'u',
                    'ÿ'=>'y',
                    'æ'=>'ae',
                    'œ'=>'oe',
                    'À'=>'a',
                    'Â'=>'a',
                    'Ä'=>'a',
                    'Ç'=>'c',
                    'É'=>'e',
                    'È'=>'e',
                    'Ê'=>'e',
                    'Ë'=>'e',
                    'Î'=>'i',
                    'Ï'=>'i',
                    'Ô'=>'o',
                    'Ö'=>'o',
                    'Ù'=>'u',
                    'Û'=>'u',
                    'Ü'=>'u',
                    'Ÿ'=>'y',
                    'Æ'=>'ae',
                    'Œ'=>'oe',
                    'A' => 'a',
                    'B' => 'b',
                    'C' => 'c',
                    'D' => 'd',
                    'E' => 'e',
                    'F' => 'f',
                    'G' => 'g',
                    'H' => 'h',
                    'I' => 'i',
                    'J' => 'j',
                    'K' => 'k',
                    'L' => 'l',
                    'M' => 'm',
                    'N' => 'n',
                    'O' => 'o',
                    'P' => 'p',
                    'Q' => 'q',
                    'R' => 'r',
                    'S' => 's',
                    'T' => 't',
                    'U' => 'u',
                    'V' => 'v',
                    'W' => 'w',
                    'X' => 'x',
                    'Y' => 'y',
                    'Z' => 'z');
                    
  return strcmp(strtr($a, $caracteres), strtr($b, $caracteres));
}


?>
