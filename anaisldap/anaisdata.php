<?php
/**
*
*  serveur anais - interface de presentation de l'annuaire melanie2
*
*  Fichier : anaisdata.inc
*  Rôle : fonctions spécifiques de lecture données dans l'annuaire
*
*/


/**
*  construit la liste des actions pour lister l'annuaire jusqu'au chemin de l'objet
*
*  @param  $chemin chemin ldap de l'objet
*
*  @return si succes tableau des actions, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : déterminer le serveur correspondant au chemin
*  liste la racine de l'annuaire, tous les conteneurs jusqu'au parent de chemin
*  liste les boites du parent
*  V 0.2.3 : ajout paramètre $base (base du listage)
*
*  Tableau des actions : clés 'operation' et 'chemin'
*
*/
function anaisActionsChemin($chemin,$base){

  //contrôles des paramètres
  if ($base==''){
    anaisSetLastError(-1,'Base non spécifiée');
    return false;
  }
  if ($chemin==''){
    anaisSetLastError(-1,'Chemin non spécifié');
    return false;
  }
  //$base doit être parent de $chemin
  $serveur=anaisServeurChemin($base);
  if ($serveur!=anaisServeurChemin($chemin)){
    anaisSetLastError(-1,'La base et le chemin spécifiés ne sont pas sur le même serveur');
    return false;
  }
  $racine=anaisDnChemin($base);
  $dn2=anaisDnChemin($chemin);
  if (false==stristr($dn2,$racine)){
    anaisSetLastError(-1,'La base et le chemin spécifiés ne sont pas dans la même arborescence');
    return false;
  }

  //liste des actions
  $actionscc=Array();
  $nb=0;

  //$racine=$base;
  $comp_racine=@explode(',',$racine);
  $compo_chemin=anaisComposantChemin($chemin);
  $comp_cc=@explode(',',$compo_chemin['dn']);
  $nb_racine=count($comp_racine);
  $nb_cc=count($comp_cc);

  //elements de listage de la racine
  $actionscc[$nb]['operation']='litarbre';
  $actionscc[$nb]['chemin']='ldap://'.$serveur.'/'.$racine;
  $nb++;
  $actionscc[$nb]['operation']='listearbre';
  $actionscc[$nb]['chemin']='ldap://'.$serveur.'/'.$racine;
  $nb++;
  
  //elements suivants
  $dif=$nb_cc-$nb_racine;
  if ($dif){
    while(--$dif){
      $actionscc[$nb]=Array();
      $actionscc[$nb]['operation']='listearbre';
      $racine=$comp_cc[$dif].','.$racine;
      $actionscc[$nb]['chemin']='ldap://'.$serveur.'/'.$racine;
      $nb++;
    }
  }
  //ajouter listage des boîtes pour le dernier conteneur
  $actionscc[$nb]=Array();
  $actionscc[$nb]['operation']='listeboites';
  $actionscc[$nb]['chemin']=$chemin;
  $nb++;

  return $actionscc;
}


/**
*  construit la liste des actions pour lister l'annuaire jusqu'au chemin de l'objet
*
*  @param  $chemin chemin ldap de l'objet
*  @param  $opbal nom de l'opération pour le listage des boîtes sur le dernier conteneur
*
*  @return si succes tableau des actions, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation :
*  05/11/2004
*  déterminer le serveur correspondant au chemin
*  liste la racine de l'annuaire, tous les conteneurs jusqu'au parent de chemin
*  liste les boites du parent
*
*  Tableau des actions : clés 'operation' et 'chemin'
*
*/
function anaisActionsCheminOpBal($chemin,$opbal){
  
  $actionscc=Array();
  $nb=0;

  $cfgcc=anaisGetConfigAnnuaire($GLOBALS['requete']['chemin']);
  if (false==$cfgcc){
    return false;
  }

  $racine=$cfgcc['racine'];
  $comp_racine=@explode(',',$racine);
  $compo_chemin=anaisComposantChemin($GLOBALS['requete']['chemin']);
  $comp_cc=@explode(',',$compo_chemin['dn']);
  $nb_racine=count($comp_racine);
  $nb_cc=count($comp_cc);

  //elements de listage de la racine
  $actionscc[$nb]['operation']='litarbre';
  $actionscc[$nb]['chemin']='ldap://'.$cfgcc['serveur'].'/'.$racine;
  $nb++;
  $actionscc[$nb]['operation']='listearbre';
  $actionscc[$nb]['chemin']='ldap://'.$cfgcc['serveur'].'/'.$racine;
  $nb++;
  
  //elements suivants
  $dif=$nb_cc-$nb_racine;
  if ($dif){
    while(--$dif){
      $actionscc[$nb]=Array();
      $actionscc[$nb]['operation']='listearbre';
      $racine=$comp_cc[$dif].','.$racine;
      $actionscc[$nb]['chemin']='ldap://'.$cfgcc['serveur'].'/'.$racine;
      $nb++;
    }
  }
  
  //ajouter listage des boîtes pour le dernier conteneur
  $actionscc[$nb]=Array();
  $actionscc[$nb]['operation']=$opbal;
  $actionscc[$nb]['chemin']=$GLOBALS['requete']['chemin'];
  $nb++;

  return $actionscc;
}



/**
*  appelée par anaisExecReq : opération de démarrage
*
*  @param  aucun
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation :
*  V0.11 : extrait les opérations de listage à réaliser et les paramètres
*  effectue les connections ldap nécessaires, appele la ou les fonctions de lecture
*  et insère les résultats dans $GLOBALS['ldapresults']
*
*  V 0.2 (19-08-2004) fonctionne avec les chemins ldap des objets au lieu du dn
*  V 0.2 (23-08-2004) la fonction anaisExecDem prend en charge les opérations vide et 'initanais'
*  reprend le code de la fonction initanais pour les deux opérations
*  V 0.2 (26-08-2004) traitement code erreur 81 sur connection ldap
*  -> Can't contact ldap server => le serveur n'est pas pris en charge dans ce cas, ce qui permet
*  d'afficher les autres serveurs.
*/
function anaisExecDem(){

  $nbactions=0;

  //créer les actions pour le chemin courant
  $actionscc=null;
  $serveurcc='';

  if ($GLOBALS['requete']['chemin']!=''){
    $config=anaisGetConfigAnnuaire($GLOBALS['requete']['chemin']);
    if (false==$config){
      return false;
    }
    $base='ldap://'.$config['serveur'].'/'.$config['racine'];
    $actionscc=anaisActionsChemin($GLOBALS['requete']['chemin'],$base);
    if ($actionscc){
      $serveurcc=anaisServeurChemin($actionscc[0]['chemin']);
    }
  }

  //tableau final des actions - il faut inserer les actions du chemin courant sans modifier l'ordre de demarrage
  $actionsinit=array();
  $nb=0;
  $bactionscc=false;
  
  //actions de demarrage
  $config=anaisConfigOperation('','');
  if (false==$config){
    return false;
  }

  foreach($config['actions'] as $action){
    if (is_array($action)){
      if ($actionscc){
        //si même serveur que actionscc - insérer tableau $actionscc
        $srv=anaisServeurChemin($action['chemin']);
        if ($srv==$serveurcc){
          if (!$bactionscc){//pas déjà fait
            $actionsinit=array_merge($actionsinit,$actionscc);
            $nb=count($actionsinit);
            $bactionscc=true;
          }
        }
        else{
          $actionsinit[$nb]=$action;
          $nb++;
        }
      }
      else{
        $actionsinit[$nb]=$action;
        $nb++;
      }
    }
  }

  //parcours des actions de démarrage
  foreach($actionsinit as $action){
    if (is_array($action)){
      $op=&$action['operation'];
      $chemin=&$action['chemin'];
      //listage ldap
      $res=anaisExecLdap($op,$chemin);
      anaisFermeConnLdap();
      if (!$res){
        
        //cas d'erreur particulier chemin courant non valide
        if ($GLOBALS['resultat']['code']==32){
          
          anaisSetLastError(0,'');//pas une erreur fatale -> document construit avec les données valides
          //evite de refaire les requetes de demarrage, on affiche ce qui est valide
          unset($actionsinit);
          return true;
        }
        unset($actionsinit);
        return false;
      }
    }
  }
  unset($actionsinit);
  anaisFermeConnLdap();
  return true;
}


/**
*  lecture des données de propriétés d'une boîte
*          equivalent aux opération 'litboitetout'+'membres'+'membresde'
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation :
*
*  configuration des attrributs : le premier attribut indique l'attribut qui contient la liste des membres.
*  le second attribut indique l'attribut qui identifie un membre.
*  la configuration des attributs de l'opération 'litboite' est utilisée pour la lecture des objets
*
* Resultats ldap
* $GLOBALS['ldapresults'][0] => litboitetout
* $GLOBALS['ldapresults'][1] => membres
* $GLOBALS['ldapresults'][2] => membrede
* $GLOBALS['ldapresults'][3] => gest-partages
* $GLOBALS['ldapresults'][4] => secr
*
* $GLOBALS['ldapresults'][n]['data'] est un tableau vide si pas de donnees
*
*/
function anaisdataPropBal(){

  $chemin=$GLOBALS['requete']['chemin'];

  //configuration de l'opération 'membres'
  $cfgmembres=anaisConfigOperation('membres',$chemin);
  if (false==$cfgmembres){
    return false;
  }
  if (2>count($cfgmembres['attributs'])){
    anaisSetLastError(-1,'anaisdataPropBal : erreur de configuration d\'attribut \'membres\'');
    return false;
  }
  //configuration de l'opération 'membrede'
  $cfgmembrede=anaisConfigOperation('membrede',$chemin);
  if (false==$cfgmembrede){
    return false;
  }
  if (2>count($cfgmembres['attributs'])){
    anaisSetLastError(-1,'anaisdataPropBal : erreur de configuration d\'attribut \'membrede\'');
    return false;
  }
  //configuration d'annuaire
  $cfgann=anaisGetConfigAnnuaire($GLOBALS['requete']['chemin']);
  
  //la configuration des attributs de l'opération 'litboite' est utilisée pour la lecture des objets
  $attrbal=anaisConfigListeAttributs('litboite',$GLOBALS['requete']['chemin']);

  //connexion ldap
  $conn=anaisConnectionLdap($cfgann['serveur'],$cfgann['srvport']);

  /* opération litboitetout */
  $res=anaisExecLdap('litboitetout',$chemin);
  if (false==$res){
    //sur erreur effacer les resultats ldap
    unset($GLOBALS['ldapresults']);
    anaisFermeConnLdap();
    return false;
  }
  $donnees=&$GLOBALS['ldapresults'][0]['data'][0];//1ere entree du 1er resultat

  /* lister les membres si attribut non vide */
  //créer tableau de résultats pour la liste des membres
  $GLOBALS['ldapresults'][1]=Array();
  $GLOBALS['ldapresults'][1]['data']=Array();
  $GLOBALS['ldapresults'][1]['op']='membres';
  $GLOBALS['ldapresults'][1]['chemin']=$GLOBALS['ldapresults'][0]['chemin'];

  $attrmembre=$cfgmembres['attributs'][0]['nomldap'];

  if (isset($donnees[$attrmembre])){

    $nb=count($donnees[$attrmembre]);

    //v0.33 - limitation du nombre de membres listés
    $nb=min($nb, $cfgmembres['maxmembres']);

    if ('dn'==$attrmembre){

      //lecture simple
      for ($i=0;$i<$nb;$i++){

        $ch='ldap://'+$cfgann['serveur']+'/'+$donnees[$attrmembre][i];
        $filtre=anaisConfigFiltreOp('litboite',$ch);

        @ldap_set_option($conn, LDAP_OPT_DEREF, LDAP_DEREF_SEARCHING);

        $ldapresult=@ldap_read($conn,$donnees[$attrmembre][i],$filtre,$attrbal);
        if (true==$ldapresult){//sinon pas une erreur fatale
          //insérer les  résultats
          anaisLdapResultTableau($conn,$ldapresult,$attrbal,1);
        }
        anaisSetLastError(0,'');
        @ldap_free_result($ldapresult);
      }
    }
    else{

      //recherche
      @ldap_set_option($GLOBALS['ldapconn']['conn'], LDAP_OPT_DEREF, LDAP_DEREF_NEVER);

      for ($i=0;$i<$nb;$i++){
        $flt='';
        if (2==count($cfgmembres['attributs']))
          $flt='(&'.$cfgmembres['filtre'].'('.$cfgmembres['attributs'][1]['nomldap'].'='.$donnees[$attrmembre][$i].'))';
        else
          $flt='(&'.$cfgmembres['filtre'].'(|('.$cfgmembres['attributs'][1]['nomldap'].'='.$donnees[$attrmembre][$i].')('.
              $cfgmembres['attributs'][2]['nomldap'].'='.$donnees[$attrmembre][$i].')))';

        $ldapresult=@ldap_search($conn,$cfgann['racine'],$flt,$attrbal);

        //insérer les  résultats
        if (false!==$ldapresult &&
            0!=@ldap_count_entries($conn, $ldapresult)){
              
          //membre dans l'annuaire
          anaisLdapResultTableau($conn,$ldapresult,$attrbal,1);
        } else{
          
          //membre externe
          anaisLdapResultTableauExt($donnees[$attrmembre][$i], $attrbal, 1);
        }

        anaisSetLastError(0,'');
        @ldap_free_result($ldapresult);
      }
    }
  }

  // lister les objets dont l'objet est membre de
  // créer tableau de résultats pour les objets dont l'entrée est membre
  $GLOBALS['ldapresults'][2]=Array();
  $GLOBALS['ldapresults'][2]['data']=Array();
  $GLOBALS['ldapresults'][2]['op']='membrede';
  $GLOBALS['ldapresults'][2]['chemin']=$GLOBALS['ldapresults'][0]['chemin'];

  $attrmembrede=$cfgmembrede['attributs'][0]['nomldap'];
  $attrmembrede2='';
  if (2<count($cfgmembrede['attributs'])) $attrmembrede2=$cfgmembrede['attributs'][1]['nomldap'];

  $ident1='';
  $ident2='';

  if (isset($donnees[$attrmembrede])) $ident1=$donnees[$attrmembrede][0];
  if ((''!=$attrmembrede2)&&(isset($donnees[$attrmembrede2]))) $ident2=$donnees[$attrmembrede2][0];

  if (''==$ident1) $ident1=$ident2;
  if (''==$ident2) $ident2=$ident1;

  if ((''!=$ident1)||(''!=$ident2)){
    $flt='';
    $attrmembre2='';
    if (2==count($cfgmembrede['attributs']))
      $attrmembre2=$cfgmembrede['attributs'][1]['nomldap'];
    else
      $attrmembre2=$cfgmembrede['attributs'][2]['nomldap'];

    if ($ident1==$ident2)  $flt='(&'.$cfgmembrede['filtre'].'('.$attrmembre2.'='.$ident1.'))';
    else $flt='(&'.$cfgmembrede['filtre'].'(|('.$attrmembre2.'='.$ident1.')('.$attrmembre2.'='.$ident2.')))';

    @ldap_set_option($GLOBALS['ldapconn']['conn'], LDAP_OPT_DEREF, LDAP_DEREF_NEVER);

    $ldapresult=@ldap_search($conn,$cfgann['racine'],$flt,$attrbal);
    if (true==$ldapresult){//sinon pas une erreur fatale
      //insérer les  résultats
      anaisLdapResultTableau($conn,$ldapresult,$attrbal,2);
    }

    anaisSetLastError(0,'');
    @ldap_free_result($ldapresult);
  }

  /* propriétaire d'une liste */
  if (isset($donnees['owner'])){
    $cho='ldap://'.$cfgann['serveur'].'/'.$donnees['owner'][0];
    anaisExecLdap('litboite',$cho);
    anaisSetLastError(0,'');
  }

  //v0.6 - gestionnaires de boite
  $typeentree=$donnees['mineqtypeentree'][0];

  //v0.6 - gestionnaire boites type BALS, BALU et BALF
  $GLOBALS['ldapresults'][3]=Array();
  $GLOBALS['ldapresults'][3]['data']=Array();
  $GLOBALS['ldapresults'][3]['op']='gest-partages';
  $GLOBALS['ldapresults'][3]['chemin']=$GLOBALS['ldapresults'][0]['chemin'];

  if (!empty($donnees['mineqmelpartages']) &&
      ('BALS'==$typeentree ||
      'BALU'==$typeentree ||
      'BALF'==$typeentree)){

    $cfggest=anaisConfigOperation('gest-partages', $chemin);
    $attrbal=anaisConfigListeAttributs('gest-partages', $chemin);

    @ldap_set_option($GLOBALS['ldapconn']['conn'], LDAP_OPT_DEREF, LDAP_DEREF_NEVER);

    foreach ($donnees['mineqmelpartages'] AS $uidp){

      $vals=explode(':',$uidp);
      if ('G'==$vals[1]){
        //recherche boite
        $filtre=anaisFormatFiltre($cfggest['filtre'], $vals[0]);

        $ldapresult=@ldap_search($conn, $cfgann['racine'], $filtre, $attrbal);
        if (true==$ldapresult){//sinon pas une erreur fatale
          //inserer les  résultats
          anaisLdapResultTableau($conn, $ldapresult, $attrbal, 3);
        }
        anaisSetLastError(0,'');
        @ldap_free_result($ldapresult);
      }
    }
  }
  //gestionnaire boites type LDIS, LDAB
  if (!empty($donnees['owner']) &&
      ('LDIS'==$typeentree ||
      'LDAB'==$typeentree)){

    $GLOBALS['ldapresults'][3]=Array();
    $GLOBALS['ldapresults'][3]['data']=Array();
    $GLOBALS['ldapresults'][3]['op']='gest-liste';
    $GLOBALS['ldapresults'][3]['chemin']=$GLOBALS['ldapresults'][0]['chemin'];

    $cfggest=anaisConfigOperation('gest-liste', $chemin);
    $attrbal=anaisConfigListeAttributs('gest-liste', $chemin);

    foreach ($donnees['owner'] AS $dn){

      $ldapresult=@ldap_read($conn, $dn, $cfggest['filtre'], $attrbal, 0, 1, 0, $cfggest['defer']);
      if (true==$ldapresult){//sinon pas une erreur fatale
        //inserer les  résultats
        anaisLdapResultTableau($conn, $ldapresult, $attrbal, 3);
      }
      anaisSetLastError(0,'');
      @ldap_free_result($ldapresult);
    }
  }

  //v0.6 - recherche secretaire
  //créer tableau de résultats pour les secretaires
  $GLOBALS['ldapresults'][4]=Array();
  $GLOBALS['ldapresults'][4]['data']=Array();
  $GLOBALS['ldapresults'][4]['op']='secr';
  $GLOBALS['ldapresults'][4]['chemin']=$GLOBALS['ldapresults'][0]['chemin'];

  $chemincont=anaisLdapCheminParent($GLOBALS['ldapresults'][0]['chemin']);

  $cfg=anaisConfigOperation('secr', $chemincont);
  $attrbal=anaisConfigListeAttributs('secr', $chemincont);
  $compos=explode('/', $chemincont);

  $ldapresult=@ldap_list($conn, $compos[3], $cfg['filtre'], $attrbal);
  if (true==$ldapresult){//sinon pas une erreur fatale
    //inserer les  résultats
    anaisLdapResultTableau($conn, $ldapresult, $attrbal, 4);

    if (!empty($cfg['tri'])){
      usort($GLOBALS['ldapresults'][4]['data'], $cfg['tri']);
    }
  }
  anaisSetLastError(0,'');
  @ldap_free_result($ldapresult);

  anaisFermeConnLdap();

  return true;
}


/**
*  listage d'une branche de l'annuaire
*          liste les conteneurs à partir d'une base jusqu'au conteneur spécifié, liste les boîtes de ce dernier
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  Implémentation: le paramètre 'anaismoz-dn' de la requête spécifie le conteneur final
*  Le paramètre 'anaismoz-par' spécifie le conteneur de base du listage
*/
function anaisListeBranche(){

  $base=$GLOBALS['requete']['param'];
  if ($base==''){
    anaisSetLastError(-1,'Base non spécifiée');
    return false;
  }
  $chemin=$GLOBALS['requete']['chemin'];

  //liste des actions
  $actions=anaisActionsChemin($chemin,$base);
  if (false==$actions){
    return false;
  }
  //parcours des actions
  foreach($actions as $action){
    if (is_array($action)){
      $op=&$action['operation'];
      $chemin=&$action['chemin'];
      //listage ldap
      if ('litarbre'!=$op){
        $res=anaisExecLdap($op,$chemin);
        anaisFermeConnLdap();
        if (!$res){
          //cas d'erreur particulier chemin courant non valide
          if ($GLOBALS['resultat']['code']==32){
            anaisSetLastError(0,'');//pas une erreur fatale -> document construit avec les données valides
            //evite de refaire les requetes de demarrage, on affiche ce qui est valide
            unset($actionsinit);
            return true;
          }
          unset($actionsinit);
          return false;
        }
      }
    }
  }
  unset($actions);
  anaisFermeConnLdap();
  return true;
}

/**
*  Fonction pour l'opération 'rechbs' spécifique à l'annuaire Mélanie
*          inclut la recherche des boîtes locales
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  Implémentation:
*  configuration : 'filtre' -> filtre pour toutes les boîtes sauf locales
*                  'filtreutil' -> filtre pour uniquement les boîtes locales
*  Effectue 2 requêtes avec chaque filtre si la base de recherche est supérieure au conteneur du service de l'utilisateur
*  V0.2.51  effectue une requête supplémentaire sur le conteneur pour obtenir libellé
*
* v0.62 - implementation recherche numero de telephone sans modification du client
*          le client envoie operation rechbs : on detecte saisie numero et on charge configuration rechtel
*
*  mantis 4151 : 2 conteneurs utilisateur possible (seealso)
*/
function anaisMelRechercheSimple(){

  $op=$GLOBALS['requete']['operation'];
  $chemin=$GLOBALS['requete']['chemin'];
  $dn=anaisDnChemin($chemin);

  //configurations annuaire
  $config2=anaisGetConfigAnnuaire($chemin);

  //requête sur le conteneur de recherche si != racine
  if ($config2['racine']!=$dn){
    $config=anaisConfigOperation('litarbre',$chemin);
    if (false==$config){
      return false;
    }
    $ldapattrs=Array();
    $nbattrs=0;
    if (isset($config['attributs'])){
      foreach($config['attributs'] as $attr){
        $ldapattrs[$nbattrs]=$attr['nomldap'];
        $nbattrs++;
      }
    }
    
    $resconn=anaisConnectionLdap($config2['serveur'],$config2['srvport']);
    if ($resconn==false){
      unset($ldapattrs);
      unset($GLOBALS['ldapresults']);
      return false;
    }

    //v0.32 sizelimit+defer
    $res=anaisldapLitEntree($resconn, $dn, $config['filtre'],
                            $ldapattrs, $config2['sizelimit'], $config['defer']);
    if (!$res){
      unset($ldapattrs);
      unset($GLOBALS['ldapresults']);
      anaisFermeConnLdap();
      return false;
    }
    $GLOBALS['ldapresults'][0]['op']='litarbre';
    $GLOBALS['ldapresults'][0]['chemin']=$chemin;
  }

  //requêtes de recherche
  //v0.62 - implementation recherche simple sans modification du client
  $param=$GLOBALS['requete']['param'];
  if (1===preg_match('/^[+]?[0-9 ]{4,10}$/', $param)){
    //le client envoie operation rechbs : on detecte saisie numero et on charge configuration rechtel
    $config=anaisConfigOperation('rechtel',$chemin);
    //formater $param
    $param=str_replace(' ', '', $param);
    $l=strlen($param);
    if (9<$l){
      $param=substr($param, $l-9);
    }

  } else {
    $config=anaisConfigOperation($op,$chemin);
  }

  if (false==$config){
    return false;
  }
  
  $ldapattrs=Array();
  $nbattrs=0;
  if (isset($config['attributs'])){
    foreach($config['attributs'] as $attr){
      $ldapattrs[$nbattrs]=$attr['nomldap'];
      $nbattrs++;
    }
  }

  //1ere requête
  $resconn=anaisConnectionLdap($config2['serveur'],$config2['srvport']);
  if ($resconn==false){
    return false;
  }
  $dn=anaisDnChemin($chemin);

  $param=str_replace('(','\(',$param);
  $param=str_replace(')','\)',$param);
  $filtre=anaisFormatFiltre($config['filtre'],$param);

  //v0.32 sizelimit+defer
  $configop=anaisConfigOperation('rechboite',$chemin);
  $res=anaisldapChercheEntree($resconn,$dn,$filtre,$ldapattrs, $config2['sizelimit'], $configop['defer']);
  if (!$res){
    unset($ldapattrs);
    unset($GLOBALS['ldapresults']);
    anaisFermeConnLdap();
    return false;
  }
  $nbresults=count($GLOBALS['ldapresults']);
  $GLOBALS['ldapresults'][$nbresults-1]['op']=$op;
  $GLOBALS['ldapresults'][$nbresults-1]['chemin']=$chemin;
  
  //boites locales si nécessaire (dnserviceutil)
  $filtre="";
  if (!empty($_SESSION['dnserviceutil'])) {
    $pos=stristr($_SESSION['dnserviceutil'],$dn);
    if (false!=$pos){
      $filtre=anaisFormatFiltre($config['filtreutil'],$param);
      $res=anaisldapChercheEntree($resconn, $_SESSION['dnserviceutil'], $filtre, $ldapattrs, $config2['sizelimit'], $configop['defer']);
      if (!$res){
        unset($ldapattrs);
        unset($GLOBALS['ldapresults']);
        anaisFermeConnLdap();
        return false;
      }
      $nbresults=count($GLOBALS['ldapresults']);
      $GLOBALS['ldapresults'][$nbresults-1]['op']=$op;
      $GLOBALS['ldapresults'][$nbresults-1]['chemin']=$chemin;

      //fusionner les résultats pour un tri cohérent
      $tableau=array_merge($GLOBALS['ldapresults'][$nbresults-2]['data'], $GLOBALS['ldapresults'][$nbresults-1]['data']);

      unset($GLOBALS['ldapresults'][$nbresults-2]['data']);
      unset($GLOBALS['ldapresults'][$nbresults-1]);
      $GLOBALS['ldapresults'][$nbresults-2]['data']=$tableau;
    }
  }
  //boites locales si nécessaire (dnserviceutil2)
  if (!empty($_SESSION['dnserviceutil2'])) {
    $pos=stristr($_SESSION['dnserviceutil2'],$dn);
    if (false!=$pos){

      $res=anaisldapChercheEntree($resconn, $_SESSION['dnserviceutil2'], $filtre, $ldapattrs, $config2['sizelimit'], $configop['defer']);
      if (!$res){
        unset($ldapattrs);
        unset($GLOBALS['ldapresults']);
        anaisFermeConnLdap();
        return false;
      }
      $nbresults=count($GLOBALS['ldapresults']);
      $GLOBALS['ldapresults'][$nbresults-1]['op']=$op;
      $GLOBALS['ldapresults'][$nbresults-1]['chemin']=$chemin;

      //fusionner les résultats pour un tri cohérent
      $tableau=array_merge($GLOBALS['ldapresults'][$nbresults-2]['data'], $GLOBALS['ldapresults'][$nbresults-1]['data']);

      unset($GLOBALS['ldapresults'][$nbresults-2]['data']);
      unset($GLOBALS['ldapresults'][$nbresults-1]);
      $GLOBALS['ldapresults'][$nbresults-2]['data']=$tableau;
    }
  }  

  anaisFermeConnLdap();
  return true;
}


/**
*  recherche globale sans chemin spécifié
*
*  mantis 4151 : 2 conteneurs utilisateur possible (seealso)
*/
function anaisRechGlobal(){

  if (empty($GLOBALS['requete']['chemin'])){
    $op=$GLOBALS['requete']['operation'];

    $config=anaisConfigOperation($op,'');

    foreach($config['actions'] as $action){
      if (is_array($action)){

        $res=anaisExecLdap($action['operation'],$action['chemin']);
        if ($res){
          //recherche conteneur utilisateur
          if (!empty($_SESSION['dnserviceutil'])){
            $config2=anaisGetConfigAnnuaire($action['chemin']);
            $racine='ldap://'.$config2['serveur'].'/'.$_SESSION['dnserviceutil'];
            anaisExecLdap('rechboite',$racine);
          }
          //recherche 2eme conteneur utilisateur (mantis 4151)
          if (!empty($_SESSION['dnserviceutil2'])){

            $racine='ldap://'.$config2['serveur'].'/'.$_SESSION['dnserviceutil2'];
            anaisExecLdap('rechboite',$racine);
          }
        }
        else{
          return false;
        }
      }
    }

    anaisFermeConnLdap();
    return true;
  }
  return false;
}

?>
