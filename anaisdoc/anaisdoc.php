<?php
/**
*
*  Projet : anaismoz
*  Objet : interface de presentation d'annuaire pour Mozilla
*
*  Societe : Apitech SA.
*  Auteur : Philippe Martinak
*  CeteLyon 2004-2006
*
*  Fichier : anaisdoc.inc
*  Rôle : transformation des resultats ldap - production du document de sortie pour anaismoz
*
*  Commentaires:
*
*
*
*/

define('SAUTLIGNE_DOC','&#10;');

/**
*  Reformate pour une sortie document les chaines multilignes
*  remplace '$','\r\n','\r','\n' par '&#10;'
*/
function anaisDocFormatStrMulti($chaine){

  $search1=array("$","\r\n","\r","\n");
  return str_replace($search1,SAUTLIGNE_DOC,$chaine);
}

/**
* formatage des numéros de téléphone
*/
function anaisFormatTel($tel){

  //bug mantis 0001842: Mise en forme des numéros de telephone DOM-COM dans Anais
  if (preg_match('/^\+(262|269|508|590|594|596) ([0-9])([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/',
                $tel, $matches) && "$matches[1]"=="$matches[2]$matches[3]"){

    return "0$matches[2] $matches[3] $matches[4] $matches[5] $matches[6]";
  }
  
  //autes cas
  $modele=array('/^\+33([0-9]){1}([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$/',
                  '/^\+33 ([0-9]){1}([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$/');
  $remplace=array('0\\1 \\2 \\3 \\4 \\5','0\\1 \\2 \\3 \\4 \\5');

  $tel2=preg_replace($modele, $remplace,$tel);

  return $tel2;
}


/**
*  produit le fragment du document de sortie pour les colonnes de l'arborescence
*
*  @return  si succes  retourne true
*          retourne false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : ecrit directement les informations des colonnes avec print
*  format :   <treecols>
*              <treecol id="libelle" label="Libell&#233;" primary="true"/>
*            </treecols>
*/
function anaisDocColArbre(){

  $cols=$GLOBALS['anaisconfig']['application']['document']['arborescence']['colonnes'];
  
  print('<treecols>');
  foreach($cols as $col){
    $hidden='false';
    if ($col['mode']==0)$hidden='true';
    $lib=$col['nom'];
    print('<treecol id="'.$lib.'" label="'.$lib.'" flex="1" hidden="'.$hidden.'" primary="true"/>');
  }
  print('</treecols>');
}

/**
*
*  @return  si succes  retourne true
*          retourne false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : ecrit directement les informations des colonnes avec print
*  format :   <treecols>
*              <treecol id="libelle" label="Libell&#233;" primary="true"/>
*            </treecols>
*/
function anaisDocColBoites(){

  $cols=$GLOBALS['anaisconfig']['application']['document']['boites']['colonnes'];
  
  print('<treecols>');
  $ordre=1;
  print('<treecol id="boites-img" ignoreincolumnpicker="true" label="" fixed="true" ordinal="'.$ordre.'"/>');
  foreach($cols as $col){
    $hidden="false";
    if ($col['mode']==0)
      $hidden="true";
    $lib=$col['nom'];
    $id=$col['id'];

    $ordre++;
    print('<splitter class="tree-splitter" ordinal="'.$ordre.'"/>');
    $ordre++;
    print('<treecol id="'.$id.'" label="'.$lib.'" flex="1" hidden="'.$hidden.'" ordinal="'.$ordre.'"/>');

  }
  print('</treecols>');
}


/**
*  retourne un tableau des attributs et noms de colonnes actifs
*          pour une opération et un distinguishedname
*        utilisé pour le traitement des elements des donnees ldap
*  @param  $op operation
*  @param  $chemin chemin ldap
*
*  @return tableau avec les clés "nomldap" et "col" si succes
* false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : les attributs sont actifs si le nom de colonne est défini
*
*/
function anaisDocAttrOpDn($op,$chemin){

  $attrs=Array();//resultat

  $attrsop=anaisOpDnAttributs($op,$chemin);
  if ($attrsop==null) return $attrs;
  $nb=0;
  foreach ($attrsop as $attr){
    if ($attr['col']!=''){
      $attrs[$nb]=Array();
      $attrs[$nb]['nomldap']=$attr['nomldap'];
      $attrs[$nb]['col']=$attr['col'];
      $nb++;
    }
  }
  return $attrs;
}

/**
*  retourne le nom du fichier de l'icone correspondant au type de boite
*
*  @param  $dataBal tableau des donnees ldap pour la boite (élément du tableau retourné par ldap_get_entries)
*
*  @return url si succes, chaine vide si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : test les attributs implémentation et mineqtypeentree
*  implémentation : définit la portée : <30 -> locale, sinon globale
*  mineqtypeentree : type de boite
*  le nom de l'icone correspond à la valeur de mineqtypeentree
*  si implémentation <30 on ajoute '_l' dans la nom
*  les images ont l'extension .gif
*/
function anaisDocIconeBoite($dataBal){
  
  //racine des images
  $prefimages=anaisGetUrlImages();

  $global=true;
  if (array_key_exists('mineqportee',$dataBal)){
    if ($dataBal['mineqportee'][0]<30)
      $global=false;
  }
  if (!array_key_exists('mineqtypeentree',$dataBal)){
    return $prefimages.'bali.gif';//par défaut provisoire
  }

  $url=$prefimages.strtolower($dataBal['mineqtypeentree'][0]);
  if (!$global)
    $url.='_l';
  $url.='.gif';
  
  return $url;
}

/**
*  sort les éléments <treeitem> à partir des données ldap pour les conteneurs
*
*  @param  $index  index dans le tableau des résultats ($GLOBALS['ldapresults'])
*
*  @return nombre d'éléments
*
*  implémentation : pour chaque element du resultat ldap (entree) produit une ligne
*  de la forme <treeitem><treecell>...</treeitem>
*  ne produit pas le dernier </treeitem> pour permettre d'inclure un <treechildren> éventuel
*
*  19/05/2204 : les noms des icones sont relatif au chemin positionné dans l'attribut "images" de l'élément <arborescence>
*  v0.11 - 02/06/2004 tous les éléments sont fermés
*  V0.11 - tri des résultats avec fonction spécifique anaisDocTriConteneur
*  V0.11 - tous les dn sont passés en minuscule (raison coté client getElementById est sensible à la casse)
*  V0.2 (17-08-2004) conversion minuscule du dn : decode utf-8 puis conversion puis encodage utf-8
*  V0.2 (19-08-2004) identifiant des éléments (id) construit avec le chemin ldap: ldap://<serveur>/<dn>
*  V0.2 (20-08-2004) tri des données décrit dans le fichier de configuration (fonction)
*
*  v 1.7 (29/01/2018)  identifiant des éléments (id) construit avec le chemin ldap: ldap://<annuaireid>/<dn>
*/
function anaisDocItemsArbre($index){

  $ldapresult=& $GLOBALS['ldapresults'][$index];

  $prefimages=anaisGetUrlImages();

  //configuration annuaire
  $config=anaisGetConfigAnnuaire($ldapresult['chemin']);

  //V0.11 tri
  //V0.2(20-08-2004) tri des données décrit dans le fichier de configuration
  $cfgop=anaisConfigOperation($ldapresult['op'],$ldapresult['chemin']);

  if ((isset($cfgop['tri']))&&($cfgop['tri']!='')){
    usort($ldapresult['data'],$cfgop['tri']);
  }

  //configuration des attributs ldap
  $cols=& $GLOBALS['anaisconfig']['application']['document']['arborescence']['colonnes'];
  $attrs=anaisDocAttrOpDn($ldapresult['op'],$ldapresult['chemin']);
  $ldapattrs=Array();
  $nb=0;
  foreach($cols as $col){
    foreach($attrs as $attr){
      if ($col['nom']==$attr['col']){
        $ldapattrs[$nb]=$attr['nomldap'];
        $nb++;
        break;
      }
    }
  }

  $dnracine=$config['racine'];
  $libracine=$config['nomaff'];
  $bCheckRacine=true;//si true la racine doit être testée dans les résultats
  if ($libracine=='')
    $bCheckRacine=false;
  $bRacine=false;//si true l'élément est une racine

  //parcours resultat
  $nbitems=count($ldapresult['data']);
  
  for ($i=0;$i<$nbitems;$i++){
    //on ajoute systematiquement le dn
    $val=$ldapresult['data'][$i]['dn'];

    $val=anaisConvertDnDoc($val);
    if (($bCheckRacine)&&(!$bRacine)){
      
      if (0==strcasecmp($val,$dnracine)){
        $bRacine=true;
        $bCheckRacine=false;//une seule racine, ne plus tester
      }
    }
    //chemin ldap
    $val='ldap://'.$config['annuaireid'].'/'.$val;

    printf('<treeitem container="true" id="%s"><treerow>',$val);
    $bfirst=true;//indicateur 1ere cellule
    foreach($ldapattrs as $attr){
      $val=anaisConvertChaine($ldapresult['data'][$i][$attr][0]);
      if ($bfirst){
        if ($bRacine){
          printf('<treecell label="%s" src="%sdossier.gif"/>',$libracine,$prefimages);
        }
        else{
          if (empty($val)){
            $val=anaisConvertChaine($ldapresult['data'][$i]['description'][0]);
          }
          printf('<treecell label="%s" src="%sdossier.gif"/>',$val,$prefimages);
        }
        $bfirst=false;
      }
      else print('<treecell label="'.$val.'"/>');
    }
    print('</treerow></treeitem>');
  }
  return $nbitems;
}


/**
*  sort les éléments <treeitem> à partir des données ldap pour les boites
*
*  @param  $index  index dans le tableau des résultats ($GLOBALS['ldapresults'])
*
*  @return nombre d'éléments
*
*  implémentation : pour chaque element du resultat ldap (entree) produit une ligne
*  de la forme <treeitem><treecell>...</treeitem>
*  ne produit pas le dernier </treeitem> pour permettre d'inclure un <treechildren> éventuel
*
*  19/05/2204 : les noms des icones sont relatif au chemin positionné dans l'attribut "images" de l'élément <arborescence>
*  V0.2 (19-08-2004) identifiant des éléments (id) construit avec le chemin ldap: ldap://<serveur>/<dn>
*  V0.2 (20-08-2004) tri des données décrit dans le fichier de configuration (fonction)
*  v 1.7 (29/01/2018)  identifiant des éléments (id) construit avec le chemin ldap: ldap://<annuaireid>/<dn>
*/
function anaisDocItemsBoites($index){

  $ldapresult=& $GLOBALS['ldapresults'][$index];

  //configuration annuaire pour nom de la racine à utiliser éventuellement
  $config=anaisGetConfigAnnuaire($ldapresult['chemin']);

  //V0.11 tri
  //V0.2(20-08-2004) tri des données décrit dans le fichier de configuration
  $cfgop=anaisConfigOperation($ldapresult['op'],$ldapresult['chemin']);

  if ((isset($cfgop['tri']))&&($cfgop['tri']!='')){
    usort($ldapresult['data'],$cfgop['tri']);
  }

  //configuration des attributs ldap
  $cols=& $GLOBALS['anaisconfig']['application']['document']['boites']['colonnes'];
  $attrs=anaisDocAttrOpDn($ldapresult['op'],$ldapresult['chemin']);
  $ldapattrs=Array();
  $nb=0;
  foreach($cols as $col){
    foreach($attrs as $attr){
      if ($col['nom']==$attr['col']){
        $ldapattrs[$nb]=$attr['nomldap'];
        $nb++;
        break;
      }
    }
  }
  
  //parcours des boites
  $nombre=count($ldapresult['data']);
  for ($i=0;$i<$nombre;$i++){
    //url icone
    $urlicone=anaisDocIconeBoite($ldapresult['data'][$i]);
    //on ajoute systematiquement le dn

    //chemin ldap
    $val=$ldapresult['data'][$i]['dn'];
    $val=anaisConvertDnDoc($val);
    $val='ldap://'.$config['annuaireid'].'/'.$val;

    printf('<treeitem container="false" id="%s"><treerow>',$val);
    //icone sur la première cellule
    print('<treecell label="" align="center" src="'.$urlicone.'"/>');
    //parcours des attributs -> cellules
    foreach($ldapattrs as $attr){
      $val="";
      if (array_key_exists($attr,$ldapresult['data'][$i])){
        $val=anaisConvertChaine($ldapresult['data'][$i][$attr][0]);
      }
      print('<treecell label="'.$val.'"/>');
    }
    print('</treerow></treeitem>');
  }
  return $nombre;
}



/**
*  methode principal de production du document de sortie pour anaismoz
*
*  @param  aucun les donnees sont en variable session
*
*  @return  si succes retourne le document de sortie
*          retourne false si erreur
*
*  <!-- structure type du document de sortie -->
  <anaismoz xmlns="urn:anaismoz:package:root"
          xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul">
    <resultat code="" message="">
      <arborescence>
        contenu de l'arborescence
        <treecols>
          <treecol id="libelle" label="Libell&#233;" primary="true" flex="3"/>
        </treecols>
        <treechildren>
          <treeitem container="false" id="1.2.1">
            <treerow>
              <treecell label="1.2.1"/>
            </treerow>
          </treeitem>
        </treechildren>
      </arborescence>
      <boites>
        contenu des boites
      </boites>
    </resultat>
    </anaismoz>
*
*  implémentation : ecrit directement le document de sortie avec print
*  V0.11 suppression element 'resultat' -> attribut 'errcode' et 'errmsg' dans element 'anaismoz'
*  V0.11 attribut 'images' dans element 'anaismoz' au lieu de 'arborescence' et 'boites'
*  V0.11 - tous les attributs container des branches sont passés en minuscule (raison coté client getElementById est sensible à la casse)
*
*  V0.2     fonctionne avec le chemin ldap des objets au lieu du dn
*  V0.2     appelle la fonction de production de contenu configuré pour l'opération de la requête
*  V0.2     une opération peut ne pas avoir de fonction de formatage (attribut "contenu" vide)
*  V 0.2.3 chemin des images vide
*  V0.2.5  ajout du paramètre 'param' de la requête dans le document de réponse
*/
function anaisProduitDoc(){

  $fcontenu=null;//fonction de production de contenu a appeler

  $config=anaisConfigOperation($GLOBALS['requete']['operation'],$GLOBALS['requete']['chemin']);
  if (false==$config){
    anaisDocErreur();//peu probable!
    exit();
  }
  if (isset($config['contenu'])) $fcontenu=$config['contenu'];

  header('Content-Type: text/xml');

  print('<?xml version="1.0"  encoding="UTF-8"?>');
  $paramreq="";
  if (isset($GLOBALS['requete']['param']))
    $paramreq=$GLOBALS['requete']['param'];

  $cheminclient=ConvertCheminClient($GLOBALS['requete']['chemin']);
  
  printf('<anais:anaismoz errcode="%d" errmsg="%s" images="" op="%s" chemin="%s" param="%s" version="%s"'.
          ' xmlns:anais="http://anais.melanie2.i2/schema"'.
          ' xmlns="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul" >',
          $GLOBALS['resultat']['code'],
          $GLOBALS['resultat']['message'],
          //$prefimages,
          $GLOBALS['requete']['operation'],
          anaisConvertDnDoc($cheminclient),
          $paramreq,
          ANAIS_SRV_VERSION);

  if ($GLOBALS['resultat']['code']==0){//corps du document si succes des listages
    if (null!=$fcontenu) $fcontenu();
  }

  //application Pauline
  if (''==$GLOBALS['requete']['operation']){
    if (isset($GLOBALS['anaisconfig']['application']['pauline'])){

      printf('<anais:pauline racinedn="%s" urlentite="%s" urlbal="%s"/>',
              $GLOBALS['anaisconfig']['application']['pauline']['racinedn'],
              $GLOBALS['anaisconfig']['application']['pauline']['urlentite'],
              $GLOBALS['anaisconfig']['application']['pauline']['urlbal']);

    }
  }

  //fin de document
  print('</anais:anaismoz>');
}

/**
*  produit un document de sortie en cas d'erreur
*
*  @param  aucun
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation :
*  V0.11 suppression element 'resultat' -> attribut 'errcode' et 'errmsg' dans element 'anaismoz'
*  V0.2.5  ajout du paramètre 'param' de la requête dans le document de réponse
*/
function anaisDocErreur(){

  header('Content-Type: text/xml');

  print('<?xml version="1.0"  encoding="UTF-8"?>');
  $paramreq="";
  if (isset($GLOBALS['requete']['param']))
    $paramreq=$GLOBALS['requete']['param'];


  printf('<anais:anaismoz errcode="%d" errmsg="%s" images="" op="%s" chemin="%s" param="%s" version="%s"'.
          ' xmlns:anais="http://anais.melanie2.i2/schema"'.
          ' xmlns="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"/>',
          $GLOBALS['resultat']['code'],
          $GLOBALS['resultat']['message'],
          (empty($GLOBALS['requete']['operation']))?'':$GLOBALS['requete']['operation'],
          (empty($_REQUEST['anaismoz-dn']))?'':anaisConvertDnDoc($_REQUEST['anaismoz-dn']),
          $paramreq,
          ANAIS_SRV_VERSION);
}

/**
*  tri des resultats ldap pour les conteneurs (paramètre pour usort)
*
*  @param  $a   element a comparer
*  @param  $b  element a comparer
*
*  @return 0 si $a==$b, 1 si $a>$b, sinon -1
*
*  v0.2.8:
Avant tri, si l'attribut "mineqOrdreAffichage" est vide, on met "N999"
Ensuite le principe sera de trier alphabétiquement sur l'attribut.
En cas d'égalité on trie sur l'attribut "cn"
sur cn si existe et non vide, sinon description?

Tri sur mineqordreaffichage uniquement pour les unités
v0.2.92 : extension du tri sur mineqordreaffichage pour les regroupements et services
*/
function anaisDocTriConteneur($a,$b){

  //Tri sur mineqordreaffichage uniquement pour les unités
  $dn=$a['dn'];
  $nb=substr_count($dn,',');
  
  if (8>=$nb || 5<=$nb){
    $o1='N999';
    $o2='N999';
    if (!empty($a['mineqordreaffichage'][0])) $o1=$a['mineqordreaffichage'][0];
    if (!empty($b['mineqordreaffichage'][0])) $o2=$b['mineqordreaffichage'][0];
    $to=strcmp($o1,$o2);
    if (0!=$to) return $to;
  }

  $val1='';
  if (!empty($a['cn'][0])){
    $val1=@$a['cn'][0];
  } else{
    $val1=@$a['description'][0];
  }
  $val2='';
  if (!empty($b['cn'][0])){
    $val2=@$b['cn'][0];
  } else{
    $val2=@$b['description'][0];
  }

  return strcmp($val1,$val2);
}

/**
*  tri des resultats ldap pour les boites (paramètre pour usort)
*
*  @param  $a   element a comparer
*  @param  $b  element a comparer
*
*  @return 0 si $a==$b, 1 si $a>$b, sinon -1
*
*  @implémentation : V0.11 tri sur les attributs mineqtypeentree et cn
*  -> dépendant du schéma de l'annuaire
*  si mineqtypeentree identiques tri sur portée implémentation globale/locale
*
* v 0.2.93 Modification tri des boites, ordre=N si mineqOrdreAffichage vide ou inexistant
*/
function anaisDocTriBoites($a,$b){
  
  //ordre de tri de l'attribut mineqtypeentree
  $anaisOrdreBoites=array('LDIS'=>0,'BALS'=>1,'BALU'=>2,'BALF'=>3,'BALR'=>4,'BALA'=>5,'BALI'=>6,'BAL'=>7,'REFX'=>8);
  
  //tri sur type de boite
  $type1=0;
  $type2=0;
  if (!empty($a['mineqtypeentree']) &&
      !empty($anaisOrdreBoites[$a['mineqtypeentree'][0]]))
    $type1=$anaisOrdreBoites[$a['mineqtypeentree'][0]];
  if (!empty($b['mineqtypeentree']) &&
      !empty($anaisOrdreBoites[$b['mineqtypeentree'][0]]))
    $type2=$anaisOrdreBoites[$b['mineqtypeentree'][0]];
  
  if ($type1 < $type2) return -1;
  if ($type1 > $type2) return 1;
  
  //tri sur portee de boite
  $porte1=0;//0->G, 1->L
  $porte2=0;
  if (!empty($a['mineqportee']) && 30<=$a['mineqportee'][0])
    $porte1=1;
  if (!empty($b['mineqportee']) && 30<=$b['mineqportee'][0])
    $porte2=1;

  if ($porte1!=$porte2){
    if ($porte1==0)
      return 1;
    return -1;
  }
  
  //22-03-2006 tri sur l'ordre d'affichage
  $ordre1='N';
  $ordre2='N';

  if (!empty($a['mineqordreaffichage'][0]))
    $ordre1=$a['mineqordreaffichage'][0];
  if (!empty($b['mineqordreaffichage'][0]))
    $ordre2=$b['mineqordreaffichage'][0];

  if ($ordre1!=$ordre2){
    
    if (''==$ordre1) return 1;
    if (''==$ordre2) return -1;
    return strcasecmp($ordre1,$ordre2);
  }

  //tri sur nom de boite
  $cn1='';
  $cn2='';
  if (isset($a['cn'])) $cn1=$a['cn'][0];
  if (isset($b['cn'])) $cn2=$b['cn'][0];

  return StrCompareNoAccents($cn1,$cn2);
}

/*
* v0.3 - fonction de tri alphabétique pour les boîtes
*
*/
function anaisDocTriBoitesAlpha($a,$b){

  //tri sur nom de boite
  $cn1='';
  $cn2='';
  if (isset($a['cn'])) $cn1=$a['cn'][0];
  if (isset($b['cn'])) $cn2=$b['cn'][0];

  return StrCompareNoAccents($cn1,$cn2);
}


/**
*  produit le contenu de l'élément <arborescence>
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : parcours tous les résultats ldap
*
*/
function anaisDocContenuArbre(){

  print('<anais:arborescence>');

  //v0.11-02-06-2004 chaque résultat est inséré dans un élément 'branche'
  //avec conteneur=<dn de l'opération> en tant qu'attribut
  //le dn n'est inséré que pour les opérations de listage d'arborescence
  //sinon on considère qu'on lit une racine donc pas de dn parent
  //op=litarbre pas d'élément treechildren

  $nbres=count($GLOBALS['ldapresults']);
  
  for ($r=0;$r<$nbres;$r++){
    $op=&$GLOBALS['ldapresults'][$r]['op'];
    
    if (($op=='litarbre')|($op=='listearbre')){
      if ($op=='listearbre'){
        //chemin ldap
        $val=anaisConvertDnDoc($GLOBALS['ldapresults'][$r]['chemin']);
        printf('<anais:branche conteneur="%s"><treechildren>',$val);
      }
      else print('<anais:branche conteneur="">');
      //éléments
      anaisDocItemsArbre($r);
      if ($op=='litarbre') print('</anais:branche>');
      else print('</treechildren></anais:branche>');
    }
  }
  print('</anais:arborescence>');

  return true;
}

/**
*  produit le contenu de l'élément <boites>
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : parcours tous les résultats ldap
*
*  V0.2 (01/09/2004) prise en compte de l'operation 'rechboite' dans le tableau de résultat
*/
function anaisDocContenuBoites(){

  print('<anais:boites>');

  //traitement des résultats
  print('<treechildren>');
  $nbres=count($GLOBALS['ldapresults']);
  for ($r=0;$r<$nbres;$r++){
    $op=&$GLOBALS['ldapresults'][$r]['op'];
    if (($op=='litboite')||($op=='listeboites')||($op=='rechboite')||($op=='rechboitecont')||($op=='rechbs')){
      anaisDocItemsBoites($r);
    }
  }
  print('</treechildren></anais:boites>');

  return true;
}


/**
*  production du contenu pour les opérations de démarrage et initanais
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation :
*  V0.2 (25/08/2004) si aucun serveur ne peut être conctacté, il n'y a pas d'erreur de données
*  on affiche alors un conteneur libellé 'Aucun annuaire n'est disponible'
*  V0.2.003 (20/09/2004) spécifique Mélanie2 si fonction contenu=anaisDocMelContenuBoites appelle anaisDocMelItemsBoites
*/
function anaisDocContenuDem(){
  
  //element arborescence
  print('<anais:arborescence>');
  
  //au demarrage produire les colonnes
  anaisDocColArbre();

  //v0.11-02-06-2004 chaque résultat est inséré dans un élément 'branche'
  //avec conteneur=<dn de l'opération> en tant qu'attribut
  //le dn n'est inséré que pour les opérations de listage d'arborescence
  //sinon on considère qu'on lit une racine donc pas de dn parent
  //op=litarbre pas d'élément treechildren

  $nbres=count($GLOBALS['ldapresults']);

  if (0==$nbres){
    //racine des images
    $prefimages=anaisGetUrlImages();

    print('<anais:branche conteneur=""><treeitem container="false" id=""><treerow>');
    printf('<treecell label="Aucun annuaire n\'est disponible" src="%sdossier.gif"/>',$prefimages);
    print('</treerow></treeitem></anais:branche>');
  }
  for ($r=0;$r<$nbres;$r++){
    $op=&$GLOBALS['ldapresults'][$r]['op'];
    
    if (($op=='litarbre')|($op=='listearbre')){
      if ($op=='listearbre'){
        //chemin ldap
        $val=anaisConvertDnDoc($GLOBALS['ldapresults'][$r]['chemin']);
        printf('<anais:branche conteneur="%s"><treechildren>',$val);
      }
      else print('<anais:branche conteneur="">');
      //éléments
      anaisDocItemsArbre($r);
      if ($op=='litarbre') print('</anais:branche>');
      else print('</treechildren></anais:branche>');
    }
  }
  //15-02-2005 : racine des recherches
  anaisDocRacinesRech();

  print('</anais:arborescence>');

  //elements boites
  print('<anais:boites>');
  
  //au demarrage produire les colonnes
  anaisDocColBoites();

  //traitement des résultats
  print('<treechildren id="anaismoz-boites-racine">');
  $nbres=count($GLOBALS['ldapresults']);
  for ($r=0;$r<$nbres;$r++){
    $op=&$GLOBALS['ldapresults'][$r]['op'];
    if (($op=='litboite')|($op=='listeboites')){
      $cfg=anaisConfigOperation($op,$GLOBALS['ldapresults'][$r]['chemin']);
      if (($cfg)&&(isset($cfg['contenu']))){
        if ('anaisDocMelContenuBoites'==$cfg['contenu']){
          anaisDocMelItemsBoites($r);
        }
        else{
          anaisDocItemsBoites($r);
        }
      }
    }
  }
  print('</treechildren></anais:boites>');
}


/**
*  sort les éléments <treeitem> à partir des données ldap pour les boites
*          version spécifique de anaisDocItemsBoites pour l'annuaire Mélanie2
*
*  @param  $index  index dans le tableau des résultats ($GLOBALS['ldapresults'])
*
*  @return nombre d'éléments
*
*  implémentation : pour chaque element du resultat ldap (entree) produit une ligne
*  de la forme <treeitem><treecell>...</treeitem>
*  ne produit pas le dernier </treeitem> pour permettre d'inclure un <treechildren> éventuel
*
*  cas particuliers: si attribut mineqmelmailemission vide, prendre première valeur de mail
* v1.0 : remplacement de mineqmelmailemission par mailpr
*/
function anaisDocMelItemsBoites($index){

  $ldapresult=& $GLOBALS['ldapresults'][$index];

  //configuration annuaire pour nom de la racine à utiliser éventuellement
  $config=anaisGetConfigAnnuaire($ldapresult['chemin']);

  //V0.11 tri
  //V0.2(20-08-2004) tri des données décrit dans le fichier de configuration
  $cfgop=anaisConfigOperation($ldapresult['op'],$ldapresult['chemin']);
  
  if ((isset($cfgop['tri']))&&($cfgop['tri']!='')){
    
    usort($ldapresult['data'],$cfgop['tri']);
  }

  //configuration des attributs ldap
  $cols=& $GLOBALS['anaisconfig']['application']['document']['boites']['colonnes'];
  $attrs=anaisDocAttrOpDn($ldapresult['op'],$ldapresult['chemin']);
  $ldapattrs=Array();
  $nb=0;
  foreach($cols as $col){
    foreach($attrs as $attr){
      if ($col['nom']==$attr['col']){
        $ldapattrs[$nb]=$attr['nomldap'];
        $nb++;
        break;
      }
    }
  }
  //parcours des boites
  $nombre=count($ldapresult['data']);
  for ($i=0;$i<$nombre;$i++){
    //url icone
    $urlicone=anaisDocIconeBoite($ldapresult['data'][$i]);
    //on ajoute systematiquement le dn

    //chemin ldap
    $val=$ldapresult['data'][$i]['dn'];
    $val=anaisConvertDnDoc($val);
    $val='ldap://'.$config['annuaireid'].'/'.$val;

    //22-03-2006 cas ordre d'affichage
    $ordreaff='';
    if (array_key_exists('mineqOrdreAffichage',$ldapresult['data'][$i])){
      $ordreaff=anaisConvertChaine($ldapresult['data'][$i]['mineqOrdreAffichage'][0]);
    }

    printf('<treeitem container="false" id="%s" ordre="%s"><treerow>',$val,$ordreaff);
    //icone sur la première cellule
    print('<treecell label="" align="center" src="'.$urlicone.'"/>');

    //parcours des attributs -> cellules
    foreach($ldapattrs as $attr){
      $val='';
      if (array_key_exists($attr,$ldapresult['data'][$i])){
        $val=anaisConvertChaine($ldapresult['data'][$i][$attr][0]);
        //test attribut
        if (''==$val &&
            'mailpr'==$attr){
          if (array_key_exists('mail',$ldapresult['data'][$i])){
            $val=anaisConvertChaine($ldapresult['data'][$i]['mail'][0]);
          }
        }
        //formatage numéros de téléphone
        if ($attr=='telephonenumber'){
          $val=anaisFormatTel($val);
        }
      }
      print('<treecell label="'.$val.'"/>');
    }
    print('</treerow></treeitem>');
  }
  return $nombre;
}



/**
*  produit le contenu de l'élément <boites>
*          version spécifique de anaisDocContenuBoites pour annuaire Mélanie2
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : parcours tous les résultats ldap
*  utilise anaisDocMelItemsBoites pour le contenu des boites
*/
function anaisDocMelContenuBoites(){

  print('<anais:boites>');

  //traitement des résultats
  print('<treechildren id="anaismoz-boites-racine">');
  $nbres=count($GLOBALS['ldapresults']);
  for ($r=0;$r<$nbres;$r++){
    $op=&$GLOBALS['ldapresults'][$r]['op'];
    if (($op=='litboite')||($op=='listeboites')||($op=='rechboite')||($op=='rechbs')){
      anaisDocMelItemsBoites($r);
    }
  }
  print('</treechildren></anais:boites>');

  return true;
}


/**
*  produit les éléments racine des recherches
*
*  Version 0.2.3 : 1 seule racine 'Recherches dans l'annuaire Mélanie'
*
*/
function anaisDocRacinesRech(){
  
  //racine des images
  $prefimages=anaisGetUrlImages();

  foreach($GLOBALS['anaisconfig']['annuaires'] as $annuaire){
    print('<anais:branche conteneur="">');

    $val='rech://'.$annuaire['annuaireid'].'/'.$annuaire['racine'];
    $limite=10;
    if (isset($annuaire['limrech']))$limite=$annuaire['limrech'];
    $saisie='';
    if (isset($annuaire['saisierech']))$saisie=$annuaire['saisierech'];
    printf('<treeitem container="true" id="%s" limite="%s" saisie="%s"><treerow>',$val,$limite,$saisie);
    
    printf('<treecell label="Recherches dans %s" src="%sdossier_rech.gif"/>',$annuaire['nomaff'],$prefimages);

    print('</treerow></treeitem></anais:branche>');
  }
}



/**
*  produit le contenu pour la recherche simple (rechbs)
*          version spécifique pour annuaire Mélanie2
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : parcours tous les résultats ldap
*  utilise anaisDocMelItemsBoites pour le contenu des boites
*  V0.2.51 le libellé de recherche est calculé avec le nom du conteneur obtenu dans une requête si !=racine
*/
function anaisDocMelRechercheSimple(){
  
  $config=anaisGetConfigAnnuaire($GLOBALS['requete']['chemin']);
  
  if (false==$config){
    anaisDocErreur();//peu probable!
    exit();
  }
  //racine des images
  $prefimages=anaisGetUrlImages();

  //arborescence
  print('<anais:arborescence>');
  $racine="rech://".$config['annuaireid']."/".$config['racine'];
  printf('<anais:branche conteneur="%s"><treechildren>',$racine);

  $nb=1;
  $search=array("ldap:","//".$config['serveur']."/");
  $replace=array("rechbs:", "//".$config['annuaireid']."/");
  $val=str_replace($search, $replace, $GLOBALS['requete']['chemin'], $nb).'&#63;'.$GLOBALS['requete']['param'];
  printf('<treeitem container="false" id="%s"><treerow>',$val);

  //construction libellé de recherche
  $val='R&#233;sultats de la recherche sur &#34;'.$GLOBALS['requete']['param'].'&#34;';
  if ('litarbre'==$GLOBALS['ldapresults'][0]['op']){
    //ajouter : dans 'service'
    $desc=anaisConvertChaine($GLOBALS['ldapresults'][0]['data'][0]['description'][0]);
    $val.=' dans '.$desc;
  }
  printf('<treecell label="%s" src="%sdossier_rech.gif"/>',$val,$prefimages);

  print('</treerow></treeitem>');

  print('</treechildren></anais:branche></anais:arborescence>');

  //boites
  print('<anais:boites>');

  //traitement des résultats
  print('<treechildren>');
  $nbres=count($GLOBALS['ldapresults']);
  for ($r=0;$r<$nbres;$r++){
    $op=&$GLOBALS['ldapresults'][$r]['op'];
    if ($op=='rechbs'){
      anaisDocMelItemsBoites($r);
    }
  }
  print('</treechildren></anais:boites>');

  return true;
}


/**
*  production du contenu pour l'opération 'listebranche'
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : similaire à anaisDocContenuDem sans les informations des colonnes
*/
function anaisDocContenuBranche(){
  
  //element arborescence
  print('<anais:arborescence>');

  $nbres=count($GLOBALS['ldapresults']);

  if (0==$nbres){
    //racine des images
    $prefimages=anaisGetUrlImages();

    print('<anais:branche conteneur=""><treeitem container="false" id=""><treerow>');
    printf('<treecell label="Aucun annuaire n\'est disponible" src="%sdossier.gif"/>',$prefimages);
    print('</treerow></treeitem></anais:branche>');
  }
  for ($r=0;$r<$nbres;$r++){
    $op=&$GLOBALS['ldapresults'][$r]['op'];

    if (($op=='litarbre')|($op=='listearbre')){
      if ($op=='listearbre'){
        //chemin ldap
        $val=anaisConvertDnDoc($GLOBALS['ldapresults'][$r]['chemin']);
        printf('<anais:branche conteneur="%s"><treechildren>',$val);
      }
      else print('<anais:branche conteneur="">');
      //éléments
      anaisDocItemsArbre($r);
      if ($op=='litarbre') print('</anais:branche>');
      else print('</treechildren></anais:branche>');
    }
  }

  print('</anais:arborescence>');

  //elements boites
  print('<anais:boites>');

  //traitement des résultats
  print('<treechildren id="anaismoz-boites-racine">');
  $nbres=count($GLOBALS['ldapresults']);
  for ($r=0;$r<$nbres;$r++){
    $op=&$GLOBALS['ldapresults'][$r]['op'];
    if (($op=='litboite')|($op=='listeboites')){
      $cfg=anaisConfigOperation($op,$GLOBALS['ldapresults'][$r]['chemin']);
      if (($cfg)&&(isset($cfg['contenu']))){
        if ('anaisDocMelContenuBoites'==$cfg['contenu']){
          anaisDocMelItemsBoites($r);
        }
        else{
          anaisDocItemsBoites($r);
        }
      }
    }
  }
  print('</treechildren></anais:boites>');
}

function anaisGetUrlImages() {

  $prefimages=$GLOBALS['anaisconfig']['application']['urlracine'].
              $GLOBALS['anaisconfig']['application']['document']['images'];
  return $prefimages;
}

// mantis 5202 - fonction de tri pour la recherche approximative
function anaisDocTriRechBoites($a,$b){
  
  //ordre de tri de l'attribut mineqtypeentree
  $anaisOrdreBoites=array('LDIS'=>0,'BALS'=>1,'BALU'=>2,'BALF'=>3,'BALR'=>4,'BALA'=>5,'BALI'=>6,'BAL'=>7,'REFX'=>8);
  
  //tri sur type de boite
  $type1=0;
  $type2=0;
  if (!empty($a['mineqtypeentree']) &&
      !empty($anaisOrdreBoites[$a['mineqtypeentree'][0]]))
    $type1=$anaisOrdreBoites[$a['mineqtypeentree'][0]];
  if (!empty($b['mineqtypeentree']) &&
      !empty($anaisOrdreBoites[$b['mineqtypeentree'][0]]))
    $type2=$anaisOrdreBoites[$b['mineqtypeentree'][0]];
  
  if ($type1 < $type2)
    return -1;
  if ($type1 > $type2)
    return 1;
  
  //tri sur portee de boite
  $porte1=0;//0->G, 1->L
  $porte2=0;
  if (!empty($a['mineqportee']) && 30<=$a['mineqportee'][0])
    $porte1=1;
  if (!empty($b['mineqportee']) && 30<=$b['mineqportee'][0])
    $porte2=1;

  if ($porte1!=$porte2){
    if ($porte1==0)
      return 1;
    return -1;
  }
  
  //22-03-2006 tri sur l'ordre d'affichage
  $ordre1='N';
  $ordre2='N';

  if (!empty($a['mineqordreaffichage'][0]))
    $ordre1=$a['mineqordreaffichage'][0];
  if (!empty($b['mineqordreaffichage'][0]))
    $ordre2=$b['mineqordreaffichage'][0];

  if ($ordre1!=$ordre2){
    
    if (''==$ordre1)
      return 1;
    if (''==$ordre2)
      return -1;
    return strcasecmp($ordre1,$ordre2);
  }

  //tri sur nom de boite
  $cn1='';
  $cn2='';
  if (isset($a['cn'])) $cn1=$a['cn'][0];
  if (isset($b['cn'])) $cn2=$b['cn'][0];

  // tri pour la recherche approximative (exact en premier puis approximative)
  $critere=$GLOBALS['requete']['param'];
  $pos1=stripos($cn1,$critere);
  $pos2=stripos($cn2,$critere);
  if ($pos1!==$pos2){
    return (0===$pos1?-1:1);
  }

  return StrCompareNoAccents($cn1,$cn2);
}

?>
