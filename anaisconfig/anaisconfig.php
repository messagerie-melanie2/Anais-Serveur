<?php
/**
*   serveur anais - interface de presentation de l'annuaire melanie2
*
*  Fichier : anaisconfig.inc
*  Role : chargement de la configuration de l'application
*
*/



/**
*  recherche du tableau de configuration en fonction du distinguishedname de l'objet
*
*  @param $cheminldap chemin ldap de l'objet 'ldap://<serveur>/<dn>'
*
*  @return  false si erreur, tableau de configuration si l'annuaire existe
*
*  implémentation :
*  parcours les annuaires et recherche les racines dans $dn
*  V 0.2 (19-08-2004) fonctionne avec chemin ldap de la forme 'ldap://<serveur>/<dn>' au lieu du <dn>
*/
function anaisGetConfigAnnuaire($cheminldap){

  if (!isset($GLOBALS['anaisconfig'])) {
    anaisSetLastError(-1,'configuration absente');
    return false;
  }

  //composantes chemin ldap
  $compo=anaisComposantChemin($cheminldap);
  if (false==$compo) return false;

  if ($compo['protocole']!='ldap:') {
    anaisSetLastError(-1,'Lecture de configuration protocole non supporte:'.$compo['protocole']);
    return false;
  }

  foreach ($GLOBALS['anaisconfig']['annuaires'] as $annuaire) {
    if (0==strcasecmp($compo['serveur'], $annuaire['annuaireid']) ||
        0==strcasecmp($compo['serveur'], $annuaire['serveur'])){
      $res=strstr($compo['dn'], $annuaire['racine']);
      if ($res){
        return $annuaire;
      }
    }
  }
  anaisSetLastError(-1,'configuration inexistante pour chemin:'.$cheminldap);
  return false;
}

/**
*  retourne la configuration des attributs pour une operation et un distinguishedname
*
*  @param  $op  operation
*  @param  $chemin chemin ldap de l'objet
*
*  @return  retourne le tableau de configuration des attributs si succes
*          retourne false si erreur (positionne l'erreur avec anaisSetLastError)
*
*  implémentation :
*
*  V0.2 (19-08-2004) prend le chemin ldap de l'objet en paramètre au lieu du dn
*  V0.2 (03/09/2004) support <attributs> absent de la configuration -> liste d'attributs vide
*/
function anaisOpDnAttributs($op,$chemin){

  if (!isset($GLOBALS['anaisconfig'])) {
    anaisSetLastError(-1,'configuration absente');
    return false;
  }
  //composantes chemin ldap
  $compo=anaisComposantChemin($chemin);
  if (false==$compo)return false;
  if ($compo['protocole']!='ldap:') {
    anaisSetLastError(-1,'Lecture des attributs protocole non supporte:'.$compo['protocole']);
    return false;
  }

  foreach ($GLOBALS['anaisconfig']['annuaires'] as $annuaire){
    if (0==strcasecmp($compo['serveur'], $annuaire['annuaireid']) ||
        0==strcasecmp($compo['serveur'], $annuaire['serveur'])){
      if (strpos($compo['dn'],$annuaire['racine'])>=0){

        //retrouver element operation
        foreach($annuaire['operations'] as $oper){
          if ($op==$oper['nom']){
            if (isset($oper['attributs']))return $oper['attributs'];
            $vide=array();
            return $vide;
          }
        }
      }
    }
  }
  anaisSetLastError(-1,"Configuration inexistante pour op='$op' et chemin='$chemin'");
  
  return false;
}



/**
*  retourne le tableau de configuration pour l'opération et le chemin specifiés
*
*  @param  $operation  operation
*  @param  $chemin chemin ldap
*
*  @return tableau de configuration si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation :
*
*  V0.2 (31/08/2004) la configuration peut être à deux endroits : <annuaires> ou <application>
*  si $chemin et $operation non vide, on recherche dans <annuaires>
*  sinon dans <application>
*  (05/11/2004) - les opérations sont recherchées en premier dans la configuration d'application quelque soit le chemin
*  (08/11/2004) - retour sur modif du 05/11/2004 si chemin renseigné on cherche dans <annuaires>, si config inexistante ou opération
*                non trouvée, recherche dans application
*/
function anaisConfigOperation($operation,$chemin){
  
  //recherche dans annuaires
  if (($operation!='')&&($chemin!='')){
    $config=anaisGetConfigAnnuaire($chemin);
    if ($config!=null){
      foreach($config['operations'] as $op){
        if ($op['nom']==$operation){
          return $op;
        }
      }
    }
  }
  //recherche dans configuration d'application
  foreach($GLOBALS['anaisconfig']['application']['operations'] as $op){
    if ($op['nom']==$operation){
      return $op;
    }
  }

  anaisSetLastError(-1,"configuration inexistante pour op='$operation' et chemin='$chemin'");
  return false;
}


/**
*  retourne dans le tableau des attributs correspondant à l'opération et le chemin ldap
*
*  @param  $operation  operation
*  @param  $chemin chemin ldap
*
*  @return si succes retourne le tableau des attributs, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisConfigListeAttributs($operation,$chemin){
  
  $attrs=anaisOpDnAttributs($operation,$chemin);
  if (false==$attrs)return false;

  $ldapattrs=Array();
  $nbattrs=0;

  foreach($attrs as $attr){
    $ldapattrs[$nbattrs]=$attr['nomldap'];
    $nbattrs++;
  }

  return $ldapattrs;
}


/**
*  détermine le filtre ldap à utilser pour une opération et un chemin ldap
*          (spécifique melanie2)
*  @param  $operation  operation
*  @param  $chemin chemin ldap
*
*  @return si succes retourne le filtre, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : retourne l'attribut filtre ou filtreutil de la configuration
*  si le chemin correspond au service utilisateur
*
*  mantis 4151 : 2 conteneurs utilisateur possible (seealso)
*/
function anaisConfigFiltreOp($operation,$chemin){

  $config=anaisConfigOperation($operation,$chemin);
  if (false==$config){
    return false;
  }
  $filtre=$config['filtre'];

  if (!empty($config['filtreutil'])){
  
    if (!empty($_SESSION['dnserviceutil'])) {
      if (stristr($chemin, $_SESSION['dnserviceutil'])) {
        return $config['filtreutil'];
      }
    }
    
    if (!empty($_SESSION['dnserviceutil2'])) {
      if (stristr($chemin, $_SESSION['dnserviceutil2'])) {
        return $config['filtreutil'];
      }
    }
  }
  
  return $filtre;
}


?>
