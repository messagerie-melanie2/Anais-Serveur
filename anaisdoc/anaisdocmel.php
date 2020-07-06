<?php
/**
*
*   serveur anais - interface de presentation de l'annuaire melanie2
*
*  Fichier : anaisdocmel.inc
*  Rôle : production du contenu des pages de propriétés pour les objets Mélanie2
*
*  Tableau de résultats ldap : $GLOBALS['ldapresults']
*  1 à 4 éléments
*  1er élément: données de l'entrée à afficher (operation=litboite)
*  élément suivant optionnel : liste des boites membres (operation=membres)
*  élément suivant optionnel : liste des boites dont l'entrée est membre (operation=membrede)
*  élément suivant optionnel : propriétaire pour une liste (operation=litboite)
*/


/**
*  teste si un attribut existe dans le tableau des résultats pour une entrée
*          retourne le tableau des valeurs de l'attribut
*
*  @param  $index index dans le tableau des résultats
*  @param  $entree index de l'entrée dans le tableau de résultats
*  @param  $attr  nom de l'attribut
*
*  @return si succes retourne tableau des valeurs, false si erreur
*/
function anaisDocMelLitValsEntree($index,$entree,$attr){

  if (!isset($GLOBALS['ldapresults'][$index])){
    return false;
  }
  if (!isset($GLOBALS['ldapresults'][$index]['data'][$entree])){
    return false;
  }
  if (!isset($GLOBALS['ldapresults'][$index]['data'][$entree][$attr])){
    return false;
  }
  return $GLOBALS['ldapresults'][$index]['data'][$entree][$attr];
}

/**
*  affiche un message d'erreur dans l'élément propbal
*        permet d'afficher un message d'erreur lorsque le document est en cours de sortie
*
*  @param  $code  code erreur
*  @param  $message  message d'erreur
*  @param  $bpropbal si true produit les onglets et la page (tabpanel)
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisDocMelAfficheErreur($code,$message,$bpropbal=false){

  if ($bpropbal){
    printf('<anais:propbal titre="%s"><tabbox flex="1" height="300" width="500"><tabs>',
          'Erreur lors de la lecture des propriétés');
    print('<tab label="Erreur"/></tabs><tabpanels flex="1"><tabpanel>');
  }
  print('<vbox flex="1"><spacer/>');
  print('<description value="Une erreur est apparue lors de l&#39;affichage des propri&#233;t&#233;s sur le serveur."/>');
  printf('<description value="Code:%s"/>',$code);
  printf('<description value="Message:%s"/></vbox>',$message);
  if ($bpropbal){
    print('</tabpanel></tabpanels></tabbox></anais:propbal>');
  }
}


/**
*  fonction principale de production des pages de propriétés
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisDocMelanie(){

  //configuration des onglets
  $configonglets=array(
    'BALI'=>array('Général','Téléphone/Fonctions','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'BALS'=>array('Général','Téléphone/Missions','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'BALU'=>array('Général','Téléphone/Missions','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'BALA'=>array('Général','Téléphone/Fonctions','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'BALF'=>array('Général','Téléphone/Fonctions','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'BALR'=>array('Général','Téléphone/Fonctions','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'REFX'=>array('Général','Téléphone/Fonctions'),
    'LDIS'=>array('Général','Téléphone/Fonctions','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'LDAB'=>array('Général','Téléphone/Fonctions','Listes de diffusion','Adresses de messagerie','Sites Web'));


  //détermier type d'entrée et supportée
  $typeentree=anaisDocMelLitValsEntree(0,0,'mineqtypeentree');
  if (false==$typeentree){

    anaisDocMelAfficheErreur(-1,'Le type d\'entrée n\'est pas renseigné dans l\'annuaire',true);
    return false;
  }
  $typeentree=$typeentree[0];
  if (empty($configonglets[$typeentree])){

    anaisDocMelAfficheErreur(-1,'Le type d\'entrée n\'est pas pris en charge:'.$typeentree,true);
    return false;
  }
  //déterminer titre
  $portee=anaisDocMelLitValsEntree(0,0,'mineqportee');
  $portee=$portee[0];
  $cn=anaisDocMelLitValsEntree(0,0,'cn');
  $cn=$cn[0];
  $titre='Propriétés de '.anaisConvertChaine($cn);
  if (($portee)&&(30>$portee)) $titre=$titre.' (boîte locale)';

  printf('<anais:propbal titre="%s"><tabbox flex="1" height="320" width="500"><tabs  orient="horizontal">',$titre);

  //contenu des onglets
  foreach($configonglets[$typeentree] as $onglet){
    printf('<tab label="%s" class="onglet"/>',$onglet);
  }
  print('</tabs>');

  //configuration des pages
  $configpages=array(
    'BALI'=>array('anaisDocMelGenBali','anaisDocMelTelFon','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'BALS'=>array('anaisDocMelGenBals','anaisDocMelTelMis','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'BALU'=>array('anaisDocMelGenBalu','anaisDocMelTelMis','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'BALA'=>array('anaisDocMelGenBala','anaisDocMelTelFon','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'BALF'=>array('anaisDocMelGenBalf','anaisDocMelTelFon','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'BALR'=>array('anaisDocMelGenBalr','anaisDocMelTelFon','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'REFX'=>array('anaisDocMelGenRefx','anaisDocMelTelFon'),
    'LDIS'=>array('anaisDocMelGenLdis','anaisDocMelTelFon','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'LDAB'=>array('anaisDocMelGenLdis','anaisDocMelTelFon','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'));

  //contenu des pages
  print('<tabpanels flex="1" selectedIndex="0">');
  foreach($configpages[$typeentree] as $page){
    $page();
  }

  print('</tabpanels></tabbox></anais:propbal>');
}


/**
*  fonction de production page GEN_BALI (page générale boîte individuelle)
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation :
*
*  V0.2.001 (16/09/2004) Titre : Test des valeurs M ou F -> M. ou Mme. sinon pas de valeur affichée
*/
function anaisDocMelGenBali(){
  print('<tabpanel><vbox flex="1">');

  //cadre boite aux lettres
  //valeurs
  $titre=anaisDocMelLitValsEntree(0,0,'gender');
  if ($titre){
    if ('M'==$titre[0]) $titre='M.';
    else if ('F'==$titre[0]) $titre='Mme.';
    else $titre='';
  } else $titre='';
  $nom=anaisDocMelLitValsEntree(0,0,'sn');
  if ($nom)$nom=$nom[0];
  else $nom='';
  $prenom=anaisDocMelLitValsEntree(0,0,'givenname');
  if ($prenom)$prenom=$prenom[0];
  else $prenom='';
  $service=anaisDocMelLitValsEntree(0,0,'departmentnumber');
  if ($service)$service=$service[0];
  else $service='';

  //contenu
  print('<groupbox flex="1"><caption label="Bo&#238;te &#224; lettres individuelle"/><vbox>');
  printf('<hbox><label value="    Titre:"/><textbox value="%s" readonly="true" size="3"/>',$titre);
  printf('<label value="Nom:"/><textbox value="%s" readonly="true" flex="5"/>',anaisConvertChaine($nom));
  printf('<label value="Pr&#233;nom:"/><textbox value="%s" readonly="true" flex="1"/></hbox>',anaisConvertChaine($prenom));
  printf('<hbox><label value="Service:"/><textbox value="%s" readonly="true" flex="1"/></hbox>',anaisConvertChaine($service));
  print('</vbox></groupbox>');

  //cadre adresse,téléphone
  anaisDocMelCadreAdrTel();
  //cadre adresse SMTP
  anaisDocMelCadreSmtp();

  print('</vbox></tabpanel>');
}


/**
*  fonction de production GEN_BALS (page générale boîte de service)
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisDocMelGenBals(){
  
  print('<tabpanel><vbox flex="1">');

  //cadre boite aux lettres
  //valeurs
  $nom=anaisDocMelLitValsEntree(0,0,'cn');
  if ($nom)$nom=$nom[0]; else $nom='';

  //contenu
  print('<groupbox flex="1"><caption label="Bo&#238;te &#224; lettres de service"/><vbox>');
  printf('<hbox><label value="Service:"/><textbox value="%s" readonly="true" flex="1"/></hbox>',anaisConvertChaine($nom));
  print('</vbox></groupbox>');

  //cadre adresse,téléphone
  anaisDocMelCadreAdrTel();
  //cadre adresse SMTP
  anaisDocMelCadreSmtp();

  print('</vbox></tabpanel>');
}


/**
*  fonction de production page GEN_BALU (page générale boîte d'unité)
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisDocMelGenBalu(){
  
  print('<tabpanel><vbox flex="1">');

  //cadre boite aux lettres
  //valeurs
  $nom=anaisDocMelLitValsEntree(0,0,'cn');
  if ($nom)$nom=$nom[0]; else $nom='';
  $tab=explode('(',$nom);

  $service='';
  if (isset($tab[0]))$service=$tab[0];
  $lib='';
  if (isset($tab[1])){$lib=str_replace(')','',$tab[1]);}
  //niveau d'unité
  $titre='Bo&#238;te &#224; lettres d\'unit&#233;';
  $dn=anaisDocMelLitValsEntree(0,0,'dn');
  if ($dn){
    $niveau=substr_count($dn,',');
    if (7<$niveau){
      $titre=$titre.' de niveau '.($niveau-6);
    }
  }
  //contenu
  printf('<groupbox flex="1"><caption label="%s"/><vbox>',$titre);
  printf('<hbox><label value="Service:"/><textbox value="%s" readonly="true" flex="1"/></hbox>',anaisConvertChaine($service));
  printf('<hbox><label value="  Libell&#233;:"/><textbox value="%s" readonly="true" flex="1"/></hbox>',anaisConvertChaine($lib));
  print('</vbox></groupbox>');

  //cadre adresse,téléphone
  anaisDocMelCadreAdrTel();
  //cadre adresse SMTP
  anaisDocMelCadreSmtp();

  print('</vbox></tabpanel>');
}


/**
*  fonction de production page GEN_BALA (page générale boîte applicative)
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisDocMelGenBala(){
  
  print('<tabpanel><vbox flex="1">');

  //cadre boite aux lettres
  //valeurs
  $nom=anaisDocMelLitValsEntree(0,0,'sn');
  if ($nom)$nom=$nom[0]; else $nom='';
  $service=anaisDocMelLitValsEntree(0,0,'departmentnumber');
  if ($service)$service=$service[0]; else $service='';

  //contenu
  print('<groupbox flex="1"><caption label="Bo&#238;te &#224; lettres applicative"/><vbox>');
  printf('<hbox><label value="     Nom:"/><textbox value="%s" readonly="true" flex="1"/></hbox>', anaisConvertChaine($nom));
  printf('<hbox><label value="Service:"/><textbox value="%s" readonly="true" flex="1"/></hbox>', anaisConvertChaine($service));
  print('</vbox></groupbox>');

  //cadre adresse,téléphone
  anaisDocMelCadreAdrTel();
  //cadre adresse SMTP
  anaisDocMelCadreSmtp();

  print('</vbox></tabpanel>');
}


/**
*  fonction de production page GEN_BALF (page générale boîte fonctionnelle)
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisDocMelGenBalf(){
  
  print('<tabpanel><vbox flex="1">');

  //cadre boite aux lettres
  //valeurs
  $nom=anaisDocMelLitValsEntree(0,0,'sn');
  if ($nom)$nom=$nom[0];else $nom='';
  $service=anaisDocMelLitValsEntree(0,0,'departmentnumber');
  if ($service)$service=$service[0];else $service='';

  //contenu
  print('<groupbox flex="1"><caption label="Bo&#238;te &#224; lettres fonctionnelle"/><vbox>');
  printf('<hbox><label value="     Nom:"/><textbox value="%s" readonly="true" flex="1"/></hbox>', anaisConvertChaine($nom));
  printf('<hbox><label value="Service:"/><textbox value="%s" readonly="true" flex="1"/></hbox>', anaisConvertChaine($service));
  print('</vbox></groupbox>');

  //cadre adresse,téléphone
  anaisDocMelCadreAdrTel();
  //cadre adresse SMTP
  anaisDocMelCadreSmtp();

  print('</vbox></tabpanel>');
}


/**
*  fonction de production page GEN_BALR (page générale boîte de ressource)
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisDocMelGenBalr(){
  
  print('<tabpanel><vbox flex="1">');

  //cadre boite aux lettres
  //valeurs
  $nom=anaisDocMelLitValsEntree(0,0,'sn');
  if ($nom)$nom=$nom[0]; else $nom='';
  $service=anaisDocMelLitValsEntree(0,0,'departmentnumber');
  if ($service)$service=$service[0]; else $service='';

  //contenu
  print('<groupbox flex="1"><caption label="Bo&#238;te &#224; lettres de ressource"/><vbox>');
  printf('<hbox><label value="     Nom:"/><textbox value="%s" readonly="true" flex="1"/></hbox>', anaisConvertChaine($nom));
  printf('<hbox><label value="Service:"/><textbox value="%s" readonly="true" flex="1"/></hbox>', anaisConvertChaine($service));
  print('</vbox></groupbox>');

  //cadre adresse,téléphone
  anaisDocMelCadreAdrTel();
  //cadre adresse SMTP
  anaisDocMelCadreSmtp();

  print('</vbox></tabpanel>');
}


/**
*  fonction de production page GEN_REFX (page générale boîte refx)
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation :
*
*  V0.2.001 (16/09/2004) Titre : Test des valeurs M ou F -> M. ou Mme. sinon pas de valeur affichée
*/
function anaisDocMelGenRefx(){
  print('<tabpanel><vbox flex="1">');

  //cadre boite aux lettres
  //valeurs
  $titre=anaisDocMelLitValsEntree(0,0,'gender');
  if ($titre){
    if ('M'==$titre[0]) $titre='M.';
    else if ('F'==$titre[0]) $titre='Mme.';
    else $titre='';
  } else $titre='';
  $nom=anaisDocMelLitValsEntree(0,0,'sn');
  if ($nom)$nom=$nom[0]; else $nom='';
  $prenom=anaisDocMelLitValsEntree(0,0,'givenname');
  if ($prenom)$prenom=$prenom[0]; else $prenom='';
  $service=anaisDocMelLitValsEntree(0,0,'departmentnumber');
  if ($service)$service=$service[0]; else $service='';

  //contenu
  print('<groupbox flex="1"><caption label="Bo&#238;te &#224; lettres individuelle"/><vbox>');
  printf('<hbox><label value="    Titre:"/><textbox value="%s" readonly="true" size="3"/>', $titre);
  printf('<label value="Nom:"/><textbox value="%s" readonly="true" flex="5"/>', anaisConvertChaine($nom));
  printf('<label value="Pr&#233;nom:"/><textbox value="%s" readonly="true" flex="1"/></hbox>', anaisConvertChaine($prenom));
  printf('<hbox><label value="Service:"/><textbox value="%s" readonly="true" flex="1"/></hbox>', anaisConvertChaine($service));
  print('</vbox></groupbox>');

  //cadre adresse,téléphone
  anaisDocMelCadreAdrTel();
  //cadre adresse SMTP
  anaisDocMelCadreSmtp();

  print('</vbox></tabpanel>');
}


/**
*  fonction de production page GEN_LDIS (page générale boîte liste de distribution)
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisDocMelGenLdis(){
  print('<tabpanel><hbox flex="1">');

  //cadre liste de diffusion
  //données
  $nom=anaisDocMelLitValsEntree(0,0,'cn');
  if ($nom)$nom=$nom[0];else $nom='';
  $dp=anaisDocMelLitValsEntree(0,0,'departmentnumber');
  if ($dp)$dp=$dp[0];else $dp='';
  $tab=explode('/',$dp);
  $service='';
  if(isset($tab[0])) $service=$tab[0];
  $unite1='';
  if(isset($tab[1])) $unite1=$tab[1];
  $unite2='';
  if(isset($tab[2])) $unite2=$tab[2];
  $unite3='';
  if(isset($tab[3])) $unite3=$tab[3];

  $desc=anaisDocMelLitValsEntree(0,0,'description');
  if ($desc) $desc=$desc[0];else $desc='';
  $pos=strpos($desc,'[');
  $fonctions='';
  if (false!==$pos){
    $fonctions=substr($desc,0,$pos);
  }
  else{
    $fonctions=$desc;
  }

  //contenu
  print('<groupbox flex="1"><caption label="Liste de diffusion"/><vbox>');

  print('<grid flex="1"><columns><column/><column flex="1"/></columns><rows>');
  print('<spacer height="10px"/>');
  print('<row><label value="Nom:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', anaisConvertChaine($nom));
  print('<spacer height="10px"/>');
  print('<row><label value="Service:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', anaisConvertChaine($service));
  print('<row><label value="Unit&#233; 1:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', anaisConvertChaine($unite1));
  print('<row><label value="Unit&#233; 2:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', anaisConvertChaine($unite2));
  print('<row><label value="Unit&#233; 3:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', anaisConvertChaine($unite3));
  print('</rows></grid>');

  printf('<vbox><label value="Description:"/><textbox value="%s" multiline="true" rows="10" readonly="true" flex="1"/>',
          anaisConvertChaine($fonctions));
  print('</vbox></vbox></groupbox>');


  //cadre membres
  //déterminer index dans tableau des résultats
  $index=1;
  $nb=count($GLOBALS['ldapresults']);
  for (;$index<$nb;$index++){
    if ('membres'==$GLOBALS['ldapresults'][$index]['op']){
      break;
    }
  }
  //contenu
  print('<groupbox flex="4"><caption label="Membres"/>');
  print('<tree flex="1" id="propbal-membres" seltype="single" hidecolumnpicker="true" ondblclick="anaisDlgPropBal(this.id);" context="anaisdlgpropbal-contextMembre">');

  //contenu de la liste
  if ($index<$nb){
    anaisDocMelContenuMembres($index);
  }
  print('</tree></groupbox>');

  print('</hbox></tabpanel>');
}


/**
*  fonction de production page TELFON (téléphone/fonctions)
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation : champ 'Fonctions' : affichage de la partie à gauche du premier '['
*
*/
function anaisDocMelTelFon(){
  
  print('<tabpanel><vbox flex="1">');

  //cadre numéros de téléphone
  anaisDocMelCadreTel();

  //cadre fonctions
  //valeur
  $desc=anaisDocMelLitValsEntree(0,0,'description');
  if ($desc) $desc=$desc[0]; else $desc="";
  $pos=strpos($desc,'[');
  $fonctions="";
  if (false!==$pos){
    $fonctions=substr($desc,0,$pos);
  }
  else{
    $fonctions=$desc;
  }
  $fonctions=anaisConvertChaine($fonctions);
  $fonctions=anaisDocFormatStrMulti($fonctions);

  //contenu
  print('<groupbox flex="2"><caption label="Fonctions"/>');
  printf('<textbox value="%s" multiline="true" rows="7" readonly="true" flex="1"/>',$fonctions);
  print('</groupbox>');

  print('</vbox></tabpanel>');
}

/**
*  fonction de production page TELMIS (téléphone/missions)
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisDocMelTelMis(){
  
  print('<tabpanel><vbox flex="1">');

  //cadre numéros de téléphone
  anaisDocMelCadreTel();

  //cadre fonctions
  //valeur
  $desc=anaisDocMelLitValsEntree(0,0,'description');
  if ($desc) $desc=$desc[0]; else $desc="";
  $pos=strpos($desc,'[');
  $missions="";
  if (false!==$pos){
    $missions=substr($desc,0,$pos);
  }
  else{
    $missions=$desc;
  }
  $missions=anaisConvertChaine($missions);
  $missions=anaisDocFormatStrMulti($missions);

  //contenu
  print('<groupbox flex="2"><caption label="Missions"/>');
  printf('<textbox value="%s" multiline="true" rows="7" readonly="true" flex="1"/>', $missions);
  print('</groupbox>');

  print('</vbox></tabpanel>');
}

/**
*  fonction de production page LISTE (membre des listes de distribution)
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisDocMelListe(){
  
  print('<tabpanel>');

  //déterminer index dans tableau des résultats
  $index=1;
  $nb=count($GLOBALS['ldapresults']);
  for (;$index<$nb;$index++){
    if ('membrede'==$GLOBALS['ldapresults'][$index]['op']){
      break;
    }
  }
  //élément tree
  print('<groupbox flex="1"><caption label="Membre des listes de distribution suivantes:"/>');
  print('<tree flex="1" id="propbal-listes" seltype="single" hidecolumnpicker="true" ondblclick="anaisDlgPropBal(this.id);" context="anaisdlgpropbal-contextLst">');

  //contenu de la liste
  if ($index<$nb){
    anaisDocMelContenuMembres($index);
  }

  print('</tree></groupbox></tabpanel>');
}


/**
* fonction de production page MSG (adresses de messagerie)
*
*
* @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
* implémentation : afficher valeur de mineqmelmailemission si existe
* sinon première valeur de mail
* v1.0 : remplacement de mineqmelmailemission par mailpr
*
*/
function anaisDocMelMsg(){
  
  print('<tabpanel><vbox flex="1">');

  //valeur
  $adr1=anaisDocMelLitValsEntree(0,0,'mailpr');
  $adr2=anaisDocMelLitValsEntree(0,0,'mail');
  $princ="";
  if (false!=$adr1){
    $princ=$adr1[0];
  }
  else if (false!=$adr2){
    $princ=$adr2[0];
  }
  $autres="";
  foreach ($adr2 as $adr){
    if (0!=strcasecmp($adr,$princ)){
      if (''!=$autres)
        $autres=$autres.SAUTLIGNE_DOC;
      $autres=$autres.$adr;
    }
  }

  //contenu
  print('<groupbox flex="1"><caption label="Adresses de messagerie"/>');

  print('<grid flex="1"><columns><column/><column flex="1"/></columns><rows>');
  print('<spacer height="10px"/>');
  print('<row><label value="Principale:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', $princ);
  print('<spacer height="10px"/>');
  print('<row><label value="Autres:"/>');
  printf('<textbox value="%s" readonly="true" multiline="true" rows="8" flex="1"/></row>', $autres);
  print('</rows></grid>');

  print('</groupbox></vbox></tabpanel>');
}


/**
*  fonction de production page WEB (sites web)
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisDocMelWeb(){
  
  print('<tabpanel><vbox flex="1">');

  //valeur
  $desc=anaisDocMelLitValsEntree(0,0,'description');

  if ($desc) $desc=$desc[0]; else $desc="";

  $sites=array('intranet'=>'','internet'=>'','ader'=>'');
  $pos=strpos($desc,'[');
  $listes="";
  if (false!==$pos){
    $listes=substr($desc,$pos+1);
    $listes=str_replace(']','',$listes);
    $tab=explode(';',$listes);
    foreach($tab as $s){
      $ch=explode('=',$s);
      if ($ch){
        $val=$ch[1];
        $pos=strpos($val, '://');
        if (false===$pos) {
          $val='http://'.$val;
        }
        $sites[$ch[0]]=$val;
      }
    }
  }

  //contenu
  print('<groupbox flex="1"><caption label="Sites Web"/>');

  print('<vbox><groupbox flex="1"><caption label="Intranet:"/>');
  if ($sites['intranet']!='')
    printf('<label value="%s" class="propbal-url" readonly="true" flex="1" onclick="anaisOuvreSite(this.value);"/></groupbox>',
            anaisConvertChaine($sites['intranet']));
  else
    print('<label value="" readonly="true" flex="1"/></groupbox>');
  print('<spacer height="10px"/><groupbox flex="1"><caption label="Inter-administrations:"/>');
  if ($sites['ader']!='')
    printf('<label value="%s" class="propbal-url" readonly="true" flex="1" onclick="anaisOuvreSite(this.value);"/></groupbox>',
            anaisConvertChaine($sites['ader']));
  else
    print('<label value="" readonly="true" flex="1"/></groupbox>');
  print('<spacer height="10px"/><groupbox flex="1"><caption label="Internet:"/>');
  if ($sites['internet']!='')
    printf('<label value="%s" class="propbal-url" readonly="true" flex="1" onclick="anaisOuvreSite(this.value);"/></groupbox>',
            anaisConvertChaine($sites['internet']));
  else
    print('<label value="" readonly="true" flex="1"/></groupbox>');

  print('</vbox></groupbox></vbox></tabpanel>');

}


/**
*  fonction de production du cadre numéros de téléphone
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisDocMelCadreTel(){
  //valeurs
  $tel=anaisDocMelLitValsEntree(0,0,'telephonenumber');
  if ($tel) $tel=$tel[0]; else $tel="";
  $mobile=anaisDocMelLitValsEntree(0,0,'mobile');
  if ($mobile) $mobile=$mobile[0]; else $mobile="";
  $copie=anaisDocMelLitValsEntree(0,0,'facsimiletelephonenumber');
  if ($copie) $copie=$copie[0]; else $copie="";


  //contenu
  print('<groupbox flex="1"><caption label="Description, t&#233;l&#233;phone"/><hbox>');

  print('<grid flex="1"><columns><column/><column flex="1"/></columns><rows>');
  print('<row><label value="Professionnel:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', anaisConvertChaine(anaisFormatTel($tel)));
  print('<row><label value="Professionnel 2:"/>');
  printf('<textbox value="" readonly="true" flex="1"/></row>');
  print('<row><label value="T&#233;l&#233;copie:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', anaisConvertChaine(anaisFormatTel($copie)));
  print('</rows></grid>');

  print('<grid flex="1"><columns><column/><column flex="1"/></columns><rows>');
  print('<row><label value="Assistant(e):"/>');
  printf('<textbox value="" readonly="true" flex="1"/></row>');
  print('<row><label value="Portable:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', anaisConvertChaine(anaisFormatTel($mobile)));
  print('<row><label value="R&#233;cep. d\'appel:"/>');
  printf('<textbox value="" readonly="true" flex="1"/></row>');
  print('</rows></grid>');

  print('</hbox></groupbox>');
}


/**
*  fonction de production du cadre Adresse,téléphone
*
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*  implémentation :
*  21/12/2005  ajout du bureau
*/
function anaisDocMelCadreAdrTel(){

  //valeurs
  $rue=anaisDocMelLitValsEntree(0,0,'street');
  $code=anaisDocMelLitValsEntree(0,0,'postalcode');
  $ville=anaisDocMelLitValsEntree(0,0,'l');
  $tel=anaisDocMelLitValsEntree(0,0,'telephonenumber');

  if ($tel) $tel=$tel[0]; else $tel="";
  $copie=anaisDocMelLitValsEntree(0,0,'facsimiletelephonenumber');
  if ($copie) $copie=$copie[0]; else $copie="";
  $mobile=anaisDocMelLitValsEntree(0,0,'mobile');
  if ($mobile) $mobile=$mobile[0]; else $mobile="";
  $bureau=anaisDocMelLitValsEntree(0,0,'roomnumber');
  if ($bureau) $bureau=$bureau[0]; else $bureau="";

  $adr="";
  if ($rue){
    $adr=anaisDocFormatStrMulti($rue[0]);
    $exp='/'.SAUTLIGNE_DOC.'$/';
    if (1!=preg_match($exp,$adr,$matches)){
      $adr.=SAUTLIGNE_DOC;
    }
  }
  if ($code) $adr.=$code[0];
  if ($ville) $adr.=" ".$ville[0];

  //contenu
  print('<groupbox flex="1"><caption label="Adresse, t&#233;l&#233;phone"/><hbox>');
  print('<hbox flex="2"><label value="Adresse:"/>');

  printf('<textbox value="%s" readonly="true" multiline="true" flex="1" rows="4"/>', anaisConvertChaine($adr));
  print('</hbox>');

  print('<grid flex="1"><columns><column/><column flex="1"/></columns><rows>');
  print('<row><label value="T&#233;l&#233;phone:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', anaisConvertChaine(anaisFormatTel($tel)));
  print('<row><label value="Portable:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', anaisConvertChaine(anaisFormatTel($mobile)));
  print('<row><label value="T&#233;l&#233;copie:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', anaisConvertChaine(anaisFormatTel($copie)));
  print('<row><label value="Bureau:"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/></row>', anaisConvertChaine($bureau));
  print('</rows></grid>');

  print('</hbox></groupbox>');
}

/**
*  fonction de production du cadre messagerie (page principale)
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
* v1.0 : remplacement de mineqmelmailemission par mailpr
*/
function anaisDocMelCadreSmtp(){
  //valeur
  $adr1=anaisDocMelLitValsEntree(0,0,'mailpr');
  $adr2=anaisDocMelLitValsEntree(0,0,'mail');
  $val="";
  if (false!=$adr1){
    $val=$adr1[0];
  }
  else if (false!=$adr2){
    $val=$adr2[0];
  }
  //contenu
  print('<groupbox flex="1"><caption label="Messagerie"/><hbox>');
  printf('<label value="%s"/>','Adresse SMTP (internet):');
  printf('<textbox value="%s" readonly="true" flex="1"/>', anaisConvertChaine($val));
  print('</hbox></groupbox>');
}


/**
*  génère le contenu d'une liste de boîtes membres ou membre de
*  sert pour l'affichage des membres d'une liste et pour l'affichage des listes dont l'entrée est membre
*
*
*  $index index dans le tableau des résultats qui contient la liste des boîtes à afficher
* $libelle - v0.6 libelle colonne
*
*  @return true si succes, false si erreur
*
*  implémentation : la liste des boîtes est créée sous la forme d'une arborescence (comme pour la liste des boîtes dans anais).
*  l'appelant est responsable de la génération de l'élément <tree>
*  la fonction produit le contenu des colonnes et des éléments treeitem
*
*/
function anaisDocMelContenuMembres($index, $libelle="Listes"){

  if (!isset($GLOBALS['ldapresults'][$index])){
    return false;
  }

  //tableau de résultats
  $ldapresult=& $GLOBALS['ldapresults'][$index];
  $nbentrees=count($ldapresult['data']);

  //V0.2.3 tri
  $cfgop=anaisConfigOperation($ldapresult['op'],$ldapresult['chemin']);

  if ((isset($cfgop['tri']))&&($cfgop['tri']!='')){

    usort($ldapresult['data'],$cfgop['tri']);
  }

  //colonnes (mettre des id pour que cela fonctionne!)
  printf('<treecols><treecol id="propbal-listei" fixed="true" label=""/>'.
        '<treecol id="propbal-liste" label="%s (%s)" flex="1"/></treecols>', $libelle, $nbentrees);

  //boites
  print('<treechildren>');

  //configuration annuaire pour nom de la racine à utiliser éventuellement
  $config=anaisGetConfigAnnuaire($ldapresult['chemin']);

  //parcours des boites
  $nombre=count($ldapresult['data']);

  for ($i=0;$i<$nombre;$i++){

    //url icone
    $urlicone=anaisDocIconeBoite($ldapresult['data'][$i]);

    //v0.2.9 pas d'id dans treeitem pour REFX (externe)
    $brefx=false;
    if ('REFX'==$ldapresult['data'][$i]['mineqtypeentree'][0]) $brefx=true;
    if (!$brefx){
      //chemin ldap
      $val=$ldapresult['data'][$i]['dn'];
      $val=anaisConvertDnDoc($val);
      $val="ldap://".$config['annuaireid']."/".$val;

      printf('<treeitem id="%s"><treerow>',$val);
    } else{
      print('<treeitem><treerow>');
    }
    //icone sur la première cellule
    printf('<treecell label="" align="center" src="%s"/>',$urlicone);

    $val=anaisConvertChaine($ldapresult['data'][$i]['cn'][0]);
    if (!$brefx) printf('<treecell label=" %s"/>',$val);
    else printf('<treecell label=" %s - (Membre externe)"/>',$val);
    print('</treerow></treeitem>');
  }

  print('</treechildren>');
}


?>
