<?php
/**
*
*   serveur anais - interface de presentation de l'annuaire melanie2
*
*  Fichier : anaisldap.inc
*  Rôle : fonction ldap de haut niveau
*/



/**
*  fonction de haut niveau pour l'application anaismoz
*  obtient une connection ldap
*  en fonction du distinguishedname et de la configuration
*
*  @param  $srv serveur
*  @param  $port port
*
*  @return  si succes retourne la connection ldap
*          si erreur, retourne false (erreur positionnee avec anaisSetLastError)
*
*  implémentation : recherche le serveur principal dans la configuration
*  effectue un appel a ldap_connect, ldap_set_option et ldap_bind
*  en cas d'echec utilisation des serveurs alternatifs
*
*  v0.2 (30/08/2004) la connexion est maintenue le temps de la requete dans les variables globales
*  $GLOBALS['ldapconn']['serveur']->contient le nom du serveur
*  $GLOBALS['ldapconn']['conn']->instance de connexion
*/
function anaisConnectionLdap($srv,$port){

  if (!isset($GLOBALS['ldapconn'])){
    $GLOBALS['ldapconn']=Array();
    $GLOBALS['ldapconn']['serveur']='';
    $GLOBALS['ldapconn']['conn']=null;
  }
  if (($GLOBALS['ldapconn']['serveur']==$srv)&&
      ($GLOBALS['ldapconn']['conn']!=null)){
    return $GLOBALS['ldapconn']['conn'];//connexion en cours
  }
  //nouvelle connexion
  $GLOBALS['ldapconn']['serveur']=$srv;
  $GLOBALS['ldapconn']['conn']=null;

  $GLOBALS['ldapconn']['conn']=@ldap_connect($srv,$port);
  if ($GLOBALS['ldapconn']['conn']==false){
    anaisSetLastError(-1,"Erreur de connection ldap serveur=$srv port=$port");
    return false;
  }

  @ldap_set_option($GLOBALS['ldapconn']['conn'], LDAP_OPT_PROTOCOL_VERSION, 3);
  @ldap_set_option($GLOBALS['ldapconn']['conn'], LDAP_OPT_DEREF, LDAP_DEREF_NEVER);

  $resbind=@ldap_bind($GLOBALS['ldapconn']['conn']);
  if ($resbind==false){
   $code=@ldap_errno($GLOBALS['ldapconn']['conn']);
   $message=@ldap_error($GLOBALS['ldapconn']['conn']);
   anaisSetLastError($code,$message);
   return false;
  }
  
  return $GLOBALS['ldapconn']['conn'];
}


/**
*  ferme la connexion ldap en cours
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisFermeConnLdap(){
  
  if (!isset($GLOBALS['ldapconn'])) return true;
  
  if ($GLOBALS['ldapconn']['conn']!=null){
    @ldap_close($GLOBALS['ldapconn']['conn']);
    $GLOBALS['ldapconn']['conn']=null;
    $GLOBALS['ldapconn']['serveur']='';
  }
  return true;
}


/**
*  convertit le ldapresult en tableau de données ldap
*        en remplacement de ldap_get_entries
*        cree le tableau sans les clés 'count'
*        cree le tableau à partir d'une liste d'attributs ($attrs)
*
*  @param  $conn  ressource resultat de ldap_bind
*  @param  $ldapresult  resultat de la fonction ldap_xxx (ldap_list, etc.)
*  @param  $attrs tableau des attributs
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  @implémentation :
*  V0.11
*  converti $ldapresult en tableau multidimensionnel
*  $donnees[n] -> entree 'n'
*  $donnees[n]['attr'] -> valeurs de l'attribut 'attr' pour l'entrée n
*  Ajoute le tableau dans $GLOBALS['ldapresults'][index] ou index est le dernier emplacement libre
*
*  V0.2 (03/09/2004) support liste d'attributs vide -> traite tous les attributs retournés par le serveur ldap
*  V0.2 (06/09/2004) ajout paramètre d'appel $index pour forcer l'utilisation d'un tableau existant
*  les données sont ajoutées dans le tableau référencé par l'index fourni
*  si $index==-1 un nouveau tableau est créé
*
*  V0.2 (08/09/2004) conversion des noms d'attributs en minuscule lorsqu'ils proviennent du serveur
*/
function anaisLdapResultTableau($conn,$ldapresult,$attrs, $index=-1){

  $donnees=null;//tableau des données
  $nb=0;
  if (-1==$index){
    $nbresult=count($GLOBALS['ldapresults']);
    $GLOBALS['ldapresults'][$nbresult]=Array();
    $GLOBALS['ldapresults'][$nbresult]['data']=Array();
    $donnees=&$GLOBALS['ldapresults'][$nbresult]['data'];
  }
  else{
    $donnees=&$GLOBALS['ldapresults'][$index]['data'];
    $nb=count($GLOBALS['ldapresults'][$index]['data']);
  }

  $bAddDn=true;
  if (in_array('dn',$attrs)) $bAddDn=false;
  $ldap_entree=@ldap_first_entry($conn,$ldapresult);
  if ($ldap_entree==false){
    return true;
  }
  while ($ldap_entree!=false){
    //allouer entree
    $donnees[$nb]=Array();
    //traiter les attributs de $ldap_entree
    if ((null!=$attrs)&&(count($attrs))){
      foreach($attrs as $attr){
        $valeurs=@ldap_get_values($conn,$ldap_entree,$attr);
        $donnees[$nb][$attr]=Array();
        if (!$valeurs){
          //pas d'attribut -> ecrire vide
          $donnees[$nb][$attr][0]='';
        }
        else{
          $nbval=$valeurs['count'];
          for ($i=0;$i<$nbval;$i++)
            $donnees[$nb][$attr][$i]=$valeurs[$i];
        }
      }
    }
    else{//tous les attributs
      $allval=@ldap_get_attributes($conn,$ldap_entree);
      for ($i=0;$i<$allval['count'];$i++){
        $attr=strtolower($allval[$i]);
        $donnees[$nb][$attr]=Array();
        $valeurs=&$allval[$allval[$i]];
        $nbval=$valeurs['count'];
        for ($n=0;$n<$nbval;$n++)
          $donnees[$nb][$attr][$n]=$valeurs[$n];
      }
    }
    //ajout du dn systématique
    if ($bAddDn){
      $dn=ldap_get_dn($conn,$ldap_entree);
      $donnees[$nb]['dn']=$dn;
    }
    //entree suivante
    $ldap_entree=@ldap_next_entry($conn,$ldap_entree);
    $nb++;
  }

  return true;
}


/**
*  v0.2.9 - equivalent fonction anaisLdapResultTableau pour les membres externes (attribut mail uniquement)
*
*/
function anaisLdapResultTableauExt($mail, $attrs, $index=-1){

  $donnees=null;//tableau des données
  $nb=0;
  if (-1==$index){
    $nbresult=count($GLOBALS['ldapresults']);
    $GLOBALS['ldapresults'][$nbresult]=Array();
    $GLOBALS['ldapresults'][$nbresult]['data']=Array();
    $donnees=&$GLOBALS['ldapresults'][$nbresult]['data'];
  }
  else{
    $donnees=&$GLOBALS['ldapresults'][$index]['data'];
    $nb=count($GLOBALS['ldapresults'][$index]['data']);
  }

  //allouer entree
  $donnees[$nb]=Array();
  //traiter les attributs
  if (in_array('cn',$attrs)){
    $donnees[$nb]['cn'][0]=$mail;
  }
  if (in_array('mail',$attrs)){
    $donnees[$nb]['mail'][0]=$mail;
  }

  //ajout forces
  $donnees[$nb]['mineqtypeentree'][0]='REFX';
  $donnees[$nb]['dn'][0]=$mail;


  return true;
}



/**
*  fonction utilitaire appelée par les fonctions anaisExecOp, anaisExecDem
*
*  @param  $op  opération
*  @param  $chemin  chemin ldap de l'objet
*  @param  $conn  connection ldap
*
*  @return résultat ldap (tableau)si succes,
*  false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : détermine les paramètres de listage et appelle la fonction appropriée
*  V0.11 affichage des boites locales :Si 'filtreutil' et le conteneur de service sont définis,
*  si le 'dn' du conteneur listé débute par le 'dn' du service,
*  alors on utilise la valeur de 'filtreutil'
*
*  V 0.2 (19-08-2004) fonctionne avec les chemins ldap des objets au lieu du dn
*  V 0.2 (23-08-2004) suppression du paramètre $config
*  V0.2 (30/08/2004) suppression paramètre d'appel $conn -> la fonction obtient la connexion ldap (fonction anaisConnectionLdap)
*  V0.2 (01/09/2004) requêtes avec paramètres (anaismoz-par)
*  V0.2 (03/09/2004) support <attributs> absent de la configuration -> liste d'attributs vide -> tous
*  V0.2 (10/09/2004) pas de test sur l'opération pour déterminer le filtre à utiliser (global ou local)
*  V0.2 (10/09/2004) utilisation de la fonction anaisConfigFiltreOp pour déterminer le filtre
*/
function anaisExecLdap($op,$chemin){
  
  //paramètres de listage
  $ldapfonc=null;
  $ldapattrs=Array();
  $nbattrs=0;
  $filtre='';
  $filtreutil='';

  $config=anaisConfigOperation($op,$chemin);
  if (false==$config){
    return false;
  }
  $ldapfonc=$config['anaisldap'];
  //v0.32
  $defer=$config['defer'];

  $filtre=anaisConfigFiltreOp($op,$chemin);

  if (isset($config['attributs'])){
    foreach($config['attributs'] as $attr){
      $ldapattrs[$nbattrs]=$attr['nomldap'];
      $nbattrs++;
    }
  }

  //obtenir connection annaire
  $config=anaisGetConfigAnnuaire($chemin);
  if ($config==false){
    anaisSetLastError(-1,'Lecture ldap : erreur de configuration');
    return false;
  }
  $resconn=anaisConnectionLdap($config['serveur'],$config['srvport']);
  if ($resconn==false){
    return false;
  }

  //requêtes avec paramètres : formater le filtre
  if ((isset($GLOBALS['requete']['param']))&&
      ($GLOBALS['requete']['param']!='')){
    $filtre=anaisFormatFiltre($filtre,$GLOBALS['requete']['param']);
  }

  //listage ldap
  //19-08-2004 extraire dn du chemin ldap
  $dn=anaisDnChemin($chemin);
  
  //V0.32 sizelimit + defer
  $res=$ldapfonc($resconn,$dn,$filtre,$ldapattrs, $config['sizelimit'], $defer);
  if (!$res){
    unset($ldapattrs);
    return false;
  }
  unset($ldapattrs);

  //compléter tableau des résultat
  $index=count($GLOBALS['ldapresults']);
  if ($index>0)$index--;
  $GLOBALS['ldapresults'][$index]['op']=$op;
  $nb=1;
  $cheminclient=str_replace("//".$config['serveur']."/", "//".$config['annuaireid']."/", $chemin, $nb);
  $GLOBALS['ldapresults'][$index]['chemin']=$cheminclient;
  return true;
}


/**
*  lecture ldap d'une entrée dans l'annuaire
*        encapsule l'appel à ldap_read
*
*  @param  $conn connection ldap (obtenue avec ldap_connect)
*  @param  $dn distinguishedname de l'objet
*  @param  $filtre filtre ldap
*  @param  $attrs  tableau d'attributs
*
*  @return true si succes
*  false si erreur (erreur positionnee avec anaisSetLastError)
*
*  @implémentation : appelle la fonction ldap php 'ldap_read', puis construit les résultats
*
*/
function anaisldapLitEntree($conn,$dn,$filtre,$attrs, $sizelimit, $defer){

  $ldapresult=@ldap_read($conn,$dn,$filtre,$attrs, 0, $sizelimit, 0, $defer);

  if ($ldapresult==false){
    $code=@ldap_errno($conn);
    $message=@ldap_error($conn);
    anaisSetLastError($code,$message);
    return false;
  }

  //V0.11 remplacement de ldap_get_entries
  $res=anaisLdapResultTableau($conn,$ldapresult,$attrs);

  @ldap_free_result($ldapresult);

  return $res;
}

/**
*  Listage ldap des entrees d'annuaire au niveau du conteneur
*        encapsule l'appel à ldap_list
*
*  @param  $conn connection ldap (obtenue avec ldap_connect)
*  @param  $dn distinguishedname du conteneur d'annuaire
*  @param  $filtre filtre ldap
*  @param  $attrs  tableau d'attributs
*
*  @return true si succes
*  false si erreur (erreur positionnee avec anaisSetLastError)
*
*  @implémentation : appelle la fonction ldap php 'ldap_list', puis tri les résultats
*
*/
function anaisldapListeEntrees($conn,$dn,$filtre,$attrs, $sizelimit, $defer){

  $ldapresult=@ldap_list($conn, $dn, $filtre, $attrs, 0, $sizelimit, 0, $defer);

  if ($ldapresult==false){
    $code=@ldap_errno($conn);
    $message=@ldap_error($conn);
    anaisSetLastError($code,$message);
    return false;
  }

  //V0.11 remplacement de ldap_get_entries
  $res=anaisLdapResultTableau($conn,$ldapresult,$attrs);

  @ldap_free_result($ldapresult);
  if (!$res){
    return $res;
  }

  return $res;
}

/**
*  Recherche ldap des entrees d'annuaire d'un conteneur
*        encapsule l'appel à ldap_search
*
*  @param  $conn connection ldap (obtenue avec ldap_connect)
*  @param  $dn distinguishedname du conteneur d'annuaire
*  @param  $filtre filtre ldap
*  @param  $attrs  tableau d'attributs
*
*  @return true si succes
*  false si erreur (erreur positionnee avec anaisSetLastError)
*
*  @implémentation : appelle la fonction ldap php 'ldap_list', puis tri les résultats
*
*/
function anaisldapChercheEntree($conn,$dn,$filtre,$attrs, $sizelimit, $defer){

  $ldapresult=@ldap_search($conn,$dn,$filtre,$attrs, 0, $sizelimit, 0, $defer);

  if ($ldapresult==false){
    $code=@ldap_errno($conn);
    $message=@ldap_error($conn);

    anaisSetLastError($code,$message);
    return false;
  }

  //V0.11 remplacement de ldap_get_entries
  $res=anaisLdapResultTableau($conn,$ldapresult,$attrs);

  @ldap_free_result($ldapresult);
  if (!$res){
    return $res;
  }

  return $res;
}



/**
*  retourne les composantes du chemin ldap
*  sous forme d'un tableau avec les clés 'protocole', 'serveur', 'dn'
*
*  @param  $cheminldap chemin ldap de l'objet 'ldap://<serveur>/<dn>'
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation :
*  V 0.2.006 (29/09/2004) : correction pour les chemin ldap qui comportent des caractères '/' dans le dn
*  explode limite à 4 le nombre d'éléments extraits -> les dn sont ainsi complets
*/
function anaisComposantChemin($cheminldap){
  
  $tab=explode('/',$cheminldap,4);
  if (count($tab)<3){
    anaisSetLastError(-1,'le chemin ldap a moins de 3 composantes:'.$cheminldap);
    return false;
  }
  
  $compo=array();
  $compo['protocole']=$tab[0];
  $compo['serveur']=$tab[2];

  // v1.7 - 0004839: Ne pas transmettre le nom du serveur au client
  $annuaireid=$GLOBALS['anaisconfig']['annuaires'][0]['annuaireid'];
  if ($compo['serveur']==$annuaireid){
    $compo['serveur']=$GLOBALS['anaisconfig']['annuaires'][0]['serveur'];
  }
  
  if (count($tab)>3)
    $compo['dn']=$tab[3];
  else
    $compo['dn']='';
    
  return $compo;
}


/**
*  retourne le serveur d'un chemin ldap
*
*  @param  $cheminldap  chemin ldap de l'objet 'ldap://<serveur>/<dn>'
*
*  @return si succes retourne le nom du serveur, false si erreur (erreur positionnee avec anaisSetLastError)
*/
function anaisServeurChemin($cheminldap){
  
  $tab=explode('/',$cheminldap);
  if (count($tab)<3){
    anaisSetLastError(-1,'le chemin ldap a moins de 3 composantes:'.$cheminldap);
    return false;
  }

  // v1.7 - 0004839: Ne pas transmettre le nom du serveur au client
  $annuaireid=$GLOBALS['anaisconfig']['annuaires'][0]['annuaireid'];
  $srv=$tab[2];

  if ($srv==$annuaireid){
    return $GLOBALS['anaisconfig']['annuaires'][0]['serveur'];
  }

  return $srv;
}

/**
*  retourne le dn d'un chemin ldap
*
*  @param  $cheminldap  chemin ldap de l'objet 'ldap://<serveur>/<dn>'
*
*  @return si succes retourne le dn, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation :
*  V 0.2.006 (29/09/2004) : correction pour les chemin ldap qui comportent des caractères '/' dans le dn
*  explode limite à 4 le nombre d'éléments extraits -> les dn sont ainsi complets
*
*/
function anaisDnChemin($cheminldap){
  
  $tab=explode('/',$cheminldap,4);
  
  if (count($tab)<3){
    anaisSetLastError(-1,'le chemin ldap a moins de 3 composantes:'.$cheminldap);
    return false;
  }

  return $tab[3];
}


/**
*  liste les membres d'une boite
*
*  @param  $conn connection ldap (obtenue avec ldap_connect)
*  @param  $dn distinguishedname du conteneur d'annuaire
*  @param  $filtre filtre ldap
*  @param  $attrs  tableau d'attributs
*
*  @return true si succes
*  false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : appelée par anaisExecLdap
*  configuration de l'operation:
*  nom=membres
*  anaisldap=anaisldapMembres
*  le premier élément <attribut> qui indique le nom de l'attribut qui contient la liste des membres
*  le second élément <attribut> qui indique le nom de l'attribut qui identifie chaque membre
*(ex:dn si ce sont les distinguidhedname qui sont stockés)
*  la configuration des attributs de l'opération 'litboite' est utilisée pour la lecture des objets
*
*  construit un tableau de résultat à un seule dimension - chaque index contient une entrée
*
*  (09/09/2004) : possibilité de spécifier 1 ou 2 attributs pour identifier un membre
*/
function anaisldapMembres($conn,$dn,$filtre,$attrs, $sizelimit, $defer){

  //deux attributs au moins doivent être spécifiés
  if (2>count($attrs)){
    anaisSetLastError(-1,'anaisldapMembres : erreur de configuration d\'attribut');
    return false;
  }

  //lire l'entree en spécifiant le premier attribut
  $reqattr=array();
  $reqattr[0]=$attrs[0];

  $ldapresult=@ldap_read($conn,$dn,'(objectclass=*)',$reqattr, 0, $sizelimit, 0, $defer);

  if ($ldapresult==false){
    $code=@ldap_errno($conn);
    $message=@ldap_error($conn);
    anaisSetLastError($code,$message);
    return false;
  }
  $ldap_entree=@ldap_first_entry($conn,$ldapresult);
  if ($ldap_entree==false){
    @ldap_free_result($ldapresult);
    return true;
  }
  $valeurs=@ldap_get_values($conn,$ldap_entree,$reqattr[0]);
  @ldap_free_result($ldapresult);

  //parcourir les résultats
  //si le 2eme attribut spécifié est dn on réalise une lecture simple
  //sinon c'est une recherche avec la racine de l'annuaire comme base
  $nb=$valeurs['count'];
  $index=count($GLOBALS['ldapresults']);//tableau de résultats
  $GLOBALS['ldapresults'][$index]=Array();
  $GLOBALS['ldapresults'][$index]['data']=Array();

  //la configuration des attributs de l'opération 'litboite' est utilisée pour la lecture des objets
  $sttrbal=anaisConfigListeAttributs('litboite',$GLOBALS['requete']['chemin']);

  if ('dn'==$attrs[1]){
    //lecture simple
    for ($i=0;$i<$nb;$i++){
      $ldapresult=@ldap_read($conn,$valeurs[i],$filtre,$sttrbal, 0, $sizelimit, 0, $defer);
      if (true==$ldapresult){//sinon pas une erreur fatale
        //insérer les  résultats
        anaisLdapResultTableau($conn,$ldapresult,$sttrbal,$index);
      }
      @ldap_free_result($ldapresult);
    }
  }
  else{
    //recherche
    $cfgann=anaisGetConfigAnnuaire($GLOBALS['requete']['chemin']);

    for ($i=0;$i<$nb;$i++){
      $flt='';
      if (2==count($attrs)) $flt='(&'.$filtre.'('.$attrs[1].'='.$valeurs[$i].'))';
      else $flt='(&'.$filtre.'(|('.$attrs[1].'='.$valeurs[$i].')('.$attrs[2].'='.$valeurs[$i].')))';

      $ldapresult=@ldap_search($conn,$cfgann['racine'],$flt,$sttrbal, 0, $sizelimit, 0, LDAP_DEREF_NEVER);
      if (true==$ldapresult){//sinon pas une erreur fatale
        //v0.2.9
        $nbent=@ldap_count_entries($conn, $ldapresult);

        //insérer les  résultats
        if (0!=$nbent){
          //membre dans l'annuaire
          anaisLdapResultTableau($conn,$ldapresult,$sttrbal,$index);
        } else{
          //membre externe
          anaisLdapResultTableauExt($valeurs[$i], $sttrbal, $index);
        }
      }

      @ldap_free_result($ldapresult);
    }
  }

  return true;
}


/**
*  liste les boîtes dont la boîte spécifiée est membre
*
*  @param  $conn connection ldap (obtenue avec ldap_connect)
*  @param  $dn distinguishedname du conteneur d'annuaire
*  @param  $filtre filtre ldap
*  @param  $attrs  tableau d'attributs
*
*  @return true si succes
*  false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : appelée par anaisExecLdap
*  configuration de l'operation:
*  nom=membrede
*  anaisldap=anaisldapMembres
*  le premier élément <attribut> qui indique le nom de l'attribut qui identifie le membre
*  un second élément optionnel <attribut> indique un autre nom d'attribut qui identifie le membre
*  le dernier élément <attribut> qui indique le nom de l'attribut qui contient la liste des membres
*  la configuration des attributs de l'opération 'litboite' est utilisée pour la lecture des objets
*
*  construit un tableau de résultat à un seule dimension - chaque index contient une entrée
*
*  (09/09/2004) : possibilité de spécifier 1 ou 2 attributs qui identifie le membre
*  si plusieurs attributs, l'un peut être vide
*/
function anaisldapMembrede($conn,$dn,$filtre,$attrs, $sizelimit, $defer){

  //deux attributs au moins doivent être spécifiés
  if (2>count($attrs)){
    anaisSetLastError(-1,'anaisldapMembres : erreur de configuration d\'attribut');
    return false;
  }
  $nbspec=count($attrs);

  //lire l'entree en spécifiant le premier attribut
  $reqattr=array();
  $reqattr[0]=$attrs[0];
  if (3<=$nbspec) $reqattr[1]=$attrs[1];
  $ident1='';//valeur qui identifie le membre
  $ident2='';//autre valeur (optionnelle) qui identifie le membre

  if (('dn'==$attrs[$nbspec-1])||
      ('dn'==$attrs[$nbspec-1])){
    $ident1=$dn;
    $ident2=$dn;
  }
  else{

    $ldapresult=@ldap_read($conn,$dn,'(objectclass=*)',$reqattr, 0, $sizelimit, 0, $defer);

    if ($ldapresult==false){
      $code=@ldap_errno($conn);
      $message=@ldap_error($conn);
      anaisSetLastError($code,$message);
      return false;
    }
    $ldap_entree=@ldap_first_entry($conn,$ldapresult);
    if ($ldap_entree==false){
      @ldap_free_result($ldapresult);
      return true;
    }
    $vals=@ldap_get_values($conn,$ldap_entree,$reqattr[0]);
    $ident1=$vals[0];
    if (3<=$nbspec){
      $vals=@ldap_get_values($conn,$ldap_entree,$reqattr[1]);
      $ident2=$vals[0];
    }
    @ldap_free_result($ldapresult);

    if (''==$ident1) $ident1=$ident2;
    if (''==$ident2) $ident2=$ident1;
  }
  if (''==$ident1) return true;

  //recherche des boites dont l'entrée est membre
  //la configuration des attributs de l'opération 'litboite' est utilisée pour la lecture des objets
  $index=count($GLOBALS['ldapresults']);//tableau de résultats
  $GLOBALS['ldapresults'][$index]=Array();
  $GLOBALS['ldapresults'][$index]['data']=Array();

  $cfgann=anaisGetConfigAnnuaire($GLOBALS['requete']['chemin']);
  $flt='';
  if ((2<$nbspec)||($ident1==$ident2))  $flt='(&'.$filtre.'('.$attrs[$nbspec-1].'='.$ident1.'))';
  else $flt='(&'.$filtre.'(|('.$attrs[$nbspec-1].'='.$ident1.')('.$attrs[$nbspec-1].'='.$ident2.')))';

  $sttrbal=anaisConfigListeAttributs('litboite',$GLOBALS['requete']['chemin']);

  $ldapresult=@ldap_search($conn,$cfgann['racine'],$flt,$sttrbal, 0, $sizelimit, 0, LDAP_DEREF_NEVER);
  if (true==$ldapresult){//sinon pas une erreur fatale
    //insérer les  résultats
    anaisLdapResultTableau($conn,$ldapresult,$sttrbal,$index);
  }

  @ldap_free_result($ldapresult);

  return true;
}

/*
* chemin parent d'un chemin ldap
* $chemin ldap://<serveur>/<dn>
*/
function anaisLdapCheminParent($chemin) {

  $compos=explode('/', $chemin);
  if (false===$compos)
    return false;
  $pos=strpos($compos[3], ',');
  if (false===$pos)
    return false;
  $parent=$compos[0].'//'.$compos[2].'/'.substr($compos[3], $pos+1);
  
  return $parent;
}

?>
