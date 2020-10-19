<?php
/**
*   serveur anais - interface de presentation de l'annuaire melanie2

*  Fichier : anaispropbal2.inc
*  Rôle : production du contenu des pages de propriétés pour les objets Mélanie2
*          nouveaux modèles de présentation - remplacent anaisdocmel.inc
*/


/**
*  fonction principale de production des pages de propriétés
*
*  @return true si succes, false si erreur (erreur positionnee avec anaisSetLastError)
*
*/
function anaisPropBalM2() {

  //configuration des onglets
  $configonglets=array(
    'BALI'=>array('Général','Coordonnées','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'BALS'=>array('Général','Coordonnées','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'BALU'=>array('Général','Coordonnées','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'BALA'=>array('Général','Coordonnées','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'BALF'=>array('Général','Coordonnées','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'BALR'=>array('Général','Coordonnées','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'REFX'=>array('Général','Coordonnées'),
    'LDIS'=>array('Général','Coordonnées','Listes de diffusion','Adresses de messagerie','Sites Web'),
    'LDAB'=>array('Général','Coordonnées','Listes de diffusion','Adresses de messagerie','Sites Web'));


  //détermier type d'entrée et supportée
  $typeentree=anaisDocValEntree(0,0,'mineqtypeentree');
  if (''==$typeentree){
    anaisDocMelAfficheErreur(-1,'Le type d\'entrée n\'est pas renseigné dans l\'annuaire',true);
    return false;
  }
  if (empty($configonglets[$typeentree])){
    anaisDocMelAfficheErreur(-1,'Le type d\'entrée n\'est pas pris en charge:'.$typeentree,true);
    return false;
  }
  //déterminer titre
  $portee=anaisDocValEntree(0,0,'mineqportee');
  $cn=anaisDocValEntree(0,0,'cn');
  $titre='Propriétés de '.anaisConvertChaine($cn);
  if (!empty($portee)&&(30>$portee)) $titre=$titre.' (boîte locale)';

  printf('<anais:propbal titre="%s"><tabbox flex="1" height="440" width="640"><tabs  orient="horizontal">',$titre);

  //contenu des onglets
  foreach($configonglets[$typeentree] as $onglet){
    printf('<tab label="%s" class="onglet"/>',$onglet);
  }
  print('</tabs>');

  //configuration des pages
  $configpages=array(
    'BALI'=>array('anaisDocPropGenBali','anaisDocPropCoord','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'BALS'=>array('anaisDocPropGenBalSU','anaisDocPropCoord','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'BALU'=>array('anaisDocPropGenBalSU','anaisDocPropCoord','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'BALA'=>array('anaisDocPropGenBalFRA','anaisDocPropCoord','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'BALF'=>array('anaisDocPropGenBalFRA','anaisDocPropCoord','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'BALR'=>array('anaisDocPropGenBalFRA','anaisDocPropCoord','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'REFX'=>array('anaisDocMelGenRefx','anaisDocPropCoord'),
    'LDIS'=>array('anaisDocPropGenLdis','anaisDocPropCoord','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWeb'),
    'LDAB'=>array('anaisDocPropGenLdab','anaisDocPropCoord','anaisDocMelListe','anaisDocMelMsg','anaisDocMelWebLdab'));

  //contenu des pages
  print('<tabpanels flex="1" selectedIndex="0">');
  foreach($configpages[$typeentree] as $page){
    $page();
  }

  print('</tabpanels></tabbox></anais:propbal>');
}

/**
*  genere page générale boîte individuelle
*
*/
function anaisDocPropGenBali(){

  $labelwidth="5.6em";

  print('<tabpanel><vbox flex="1">');

  //cadre boite aux lettres
  $cn=anaisDocValEntree(0,0,'cn');
  //decoupage cn
  $compos=explode(' - ',$cn);
  $nom=$compos[0];
  $service=$compos[1];

  //cadre boites a lettres individuelle
  print('<groupbox><caption label="Bo&#238;te &#224; lettres individuelle"/><hbox style="margin:4px;">');

  //photo
  $uid=anaisDocValEntree(0,0,'uid');
  $url=anaisPropUrlPhoto($uid);
  $imagesize=anaisGetImageSize($uid);
  printf('<hbox style="margin-left:4px;border:1px solid">'.
          '<image src="%s" style="height: %dpx; max-height: %dpx; width: %dpx; max-width: %dpx;"/></hbox>',
          anaisConvertChaine($url),
          $imagesize['height'],
          $imagesize['height'],
          $imagesize['width'],
          $imagesize['width']);

  print('<vbox style="margin-left:8px;margin-top:4px;" flex="1">');

  anaisDocTextBox('Nom :', $nom, $labelwidth);
  anaisDocTextBox('Service :', $service, $labelwidth);

  //telephones
  print('<hbox flex="1"/><hbox flex="1">');
  $tel=anaisDocValEntree(0,0,'telephonenumber');
  $copie=anaisDocValEntree(0,0,'facsimiletelephonenumber');
  $mobile=anaisDocValEntree(0,0,'mobile');

  print('<vbox>');
  anaisDocTextBox('T&#233;l&#233;phone :', anaisFormatTel($tel), $labelwidth);
  anaisDocTextBox('T&#233;l&#233;copie :', anaisFormatTel($copie), $labelwidth);
  print('</vbox>');

  print('<vbox style="margin-left:24px;">');
  anaisDocTextBox('Portable :', anaisFormatTel($mobile));
  print('</vbox>');


  print('</hbox></vbox></hbox></groupbox><hbox flex="1">');

  //cadre fonctions
  anaisPropCadreFonc(true, true, true);
  //cadre description
  anaisPropCadreDesc();

  print('</hbox></vbox></tabpanel>');
}

/*
* genere page 'général' boite service / unite
*/
function anaisDocPropGenBalSU(){

  $labelwidth="4.4em";

  print('<tabpanel><vbox flex="1">');

  $type=anaisDocValEntree(0,0,'mineqtypeentree');

  //cadre boite aux lettres
  $cn=anaisDocValEntree(0,0,'cn');

  //cn de la forme : Service (libellé)
  //decoupage cn
  $service="";
  $libelle="";
  
  $compos=preg_split('/^(.*) \((.*)\)$/',$cn, -1, PREG_SPLIT_DELIM_CAPTURE);

  if ($compos && 2<count($compos)) {
    $service=$compos[1];
    $libelle=$compos[2];
  } else {
    $service=$cn;
  }

  //cadre boites a lettres service/unite
  if ('BALS'==$type) print('<groupbox><caption label="Bo&#238;te &#224; lettres de service"/>');
  else print('<groupbox><caption label="Bo&#238;te &#224; lettres d\'unit&#233; de niveau 2"/>');

  print('<vbox style="margin:4px;" flex="1">');

  anaisDocTextBox('Service :', $service, $labelwidth);
  anaisDocTextBox('Libell&#233; :', $libelle, $labelwidth);

  print('</vbox></groupbox><hbox flex="1">');

  if ('BALS'==$type) anaisPropGestBal('Gestionnaires de la bo&#238;te &#224; lettres de service');
  else anaisPropGestBal('Gestionnaires de la bo&#238;te &#224; lettres d\'unit&#233; de niveau 2');

  print('</hbox><hbox flex="1">');

  //cadre fonctions
  anaisPropCadreFonc(true, true, false);
  //cadre description
  anaisPropCadreDesc();

  print('</hbox></vbox></tabpanel>');
}


/*
* genere page 'général' boite fonctionnelle/ressource/pplicative
*/
function anaisDocPropGenBalFRA(){

  $labelwidth="4.4em";

  print('<tabpanel><vbox flex="1">');

  $type=anaisDocValEntree(0,0,'mineqtypeentree');

  //cadre boite aux lettres
  $cn=anaisDocValEntree(0,0,'cn');
  //decoupage cn
  $compos=explode(' - ',$cn);
  $nom=$compos[0];
  $service=$compos[1];

  //cadre boites a lettres
  if ('BALF'==$type) print('<groupbox><caption label="Bo&#238;te &#224; lettres Fonctionnelle"/>');
  else if ('BALR'==$type) print('<groupbox><caption label="Bo&#238;te &#224; lettres de ressource"/>');
  else print('<groupbox><caption label="Bo&#238;te &#224; lettres Applicative"/>');

  print('<vbox style="margin:4px;" flex="1">');

  anaisDocTextBox('Nom :', $nom, $labelwidth);
  anaisDocTextBox('Service :', $service, $labelwidth);

  print('</vbox></groupbox>');

  //gestionniares
  if ('BALF'==$type){
    print('<hbox flex="1">');
    if ('BALF'==$type) anaisPropGestBal('Gestionnaires de la bo&#238;te &#224; lettres Fonctionnelle');
    print('</hbox>');
  }

  print('<hbox flex="1">');
  //cadre fonctions
  anaisPropCadreFonc(true, false, true);
  //cadre description
  anaisPropCadreDesc();
  print('</hbox>');

  if ('BALF'!=$type) print('<hbox flex="1"/>');

  print('</vbox></tabpanel>');
}



/*
* genere page 'général' liste de diffusion
*/
function anaisDocPropGenLdis(){

  print('<tabpanel><vbox flex="1">');

  //cadre boite aux lettres
  $cn=anaisDocValEntree(0,0,'cn');


  //cadre boites a lettres liste de diffusion
  print('<groupbox><caption label="Liste de diffusion"/>');

  print('<vbox style="margin:4px;" flex="1">');
  anaisDocTextBox('Nom :', $cn);
  print('</vbox></groupbox>');

  //zone gestionnaire/description
  print('<hbox flex="1"><vbox flex="1">');
  //cadre gestionnaire
  anaisPropGestListe('Gestionnaires');
  //cadre description
  anaisPropCadreDesc();
  print('</vbox>');

  //cadre membres
  //déterminer index dans tableau des résultats
  $index=1;
  $nb=count($GLOBALS['ldapresults']);
  for (;$index<$nb;$index++){
    if ('membres'==$GLOBALS['ldapresults'][$index]['op']){
      break;
    }
  }
  print('<groupbox flex="2"><caption label="Membres"/>');
  print('<tree flex="1" id="propbal-membres" seltype="single" hidecolumnpicker="true" ondblclick="anaisDlgPropBal(this.id);" context="anaisdlgpropbal-contextMembre">');

  //contenu de la liste
  if ($index<$nb){
    anaisDocMelContenuMembres($index, 'Membres');
  }
  print('</tree></groupbox>');

  print('</hbox></vbox></tabpanel>');
}


/**
*  genere page 'Coordonnées'
*
* v1.0 : remplacement de mineqmelmailemission par mailpr
*/
function anaisDocPropCoord() {

  print('<tabpanel><vbox flex="1">');

  //cadre adresse
  $rue=anaisDocValEntree(0,0,'street');
  $code=anaisDocValEntree(0,0,'postalcode');
  $ville=anaisDocValEntree(0,0,'l');
  $adr="";
  if ($rue){
    $adr=anaisDocFormatStrMulti($rue);
    $exp='/'.SAUTLIGNE_DOC.'$/';
    if (1!=preg_match($exp,$adr,$matches)){
      $adr.=SAUTLIGNE_DOC;
    }
  }
  if ($code) $adr.=$code;
  if ($ville) $adr.=" ".$ville;

  print('<groupbox><caption label="Adresse"/>');
  printf('<textbox value="%s" readonly="true" rows="4" multiline="true" flex="1"/>',
          anaisConvertChaine($adr));
  print('</groupbox>');

  //cadre telephone
  $tel=anaisDocValEntree(0,0,'telephonenumber');
  $portable=anaisDocValEntree(0,0,'mobile');
  $telecopie=anaisDocValEntree(0,0,'facsimiletelephonenumber');
  $bureau=anaisDocValEntree(0,0,'roomnumber');

  $type=anaisDocValEntree(0,0,'mineqtypeentree');

  $labelwidth="7.2em";

  print('<hbox><groupbox flex="2"><caption label="T&#233;l&#233;phone"/>');
  anaisDocTextBox("Professionnel :", anaisFormatTel($tel), $labelwidth);
  anaisDocTextBox("Portable :", anaisFormatTel($portable), $labelwidth);
  anaisDocTextBox("T&#233;l&#233;copie :", anaisFormatTel($telecopie), $labelwidth);
  if ('BALI'==$type) anaisDocTextBox("Bureau :", anaisConvertChaine($bureau), $labelwidth);
  print('</groupbox>');

  //cadre assistante
  $titre='';
  $nom='';
  $prenom='';
  $tel='';
  if (!empty($GLOBALS['ldapresults'][4]) &&
      0<count($GLOBALS['ldapresults'][4]) &&
      anaisDocValEntree(4, 0, 'dn')!=anaisDocValEntree(0, 0, 'dn')){
    $val=anaisConvertChaine(anaisDocValEntree(4, 0, 'gender'));
    if (!empty($val)){
      if ('M'==$val) $titre='M.';
      else if ('F'==$val) $titre='Mme.';
    }
    $nom=anaisConvertChaine(anaisDocValEntree(4, 0, 'sn'));
    $prenom=anaisConvertChaine(anaisDocValEntree(4, 0, 'givenname'));
    $tel=anaisFormatTel(anaisDocValEntree(4, 0, 'telephonenumber'));
  }
  print('<hbox style="width:60px"/><groupbox flex="1"><caption label="Assistant(e)"/>');
  anaisDocTextBox("Titre :", $titre, $labelwidth);
  anaisDocTextBox("Nom :", $nom, $labelwidth);
  anaisDocTextBox("Pr&#233;nom :", $prenom, $labelwidth);
  anaisDocTextBox("T&#233;l&#233;phone :", $tel, $labelwidth);
  print('</groupbox></hbox>');

  //cadre messagerie
  $adr1=anaisDocValEntree(0,0,'mailpr');
  $adr2=anaisDocValEntree(0,0,'mail');
  $val="";
  if (!empty($adr1)){
    $val=$adr1;
  }  else{
    if (!empty($adr2)) $val=$adr2;
  }

  print('<groupbox><caption label="Messagerie"/>');
  printf('<textbox value="%s" readonly="true" flex="1"/>', anaisConvertChaine($val));
  print('</groupbox>');


  print('</vbox><hbox/></tabpanel>');
}


/**
* genere cadre Fonctions
* $bHiera si true génère champ Hiérarchique
* $bMetier si true génère champ Métier
* $bMission si true génère champ Missions
*/
function anaisPropCadreFonc($bHiera, $bMetier, $bMission){

  $labelwidth="6.8em";

  print('<groupbox flex="1"><caption label="Fonctions"/><vbox>');

  if ($bHiera) {
    $val=anaisDocValEntree(0,0,'title');
    anaisDocTextBox("Hi&#233;rarchique :", $val, $labelwidth);
  }
  if ($bMetier) {
    $vals=anaisDocValeursEntree(0,0,'businesscategory');
    anaisDocTextMulti("M&#233;tier :", $vals, $labelwidth);
  }
  if ($bMission) {
    $vals=anaisDocValeursEntree(0,0,'mineqmission');
    anaisDocTextMulti("Missions :", $vals, $labelwidth);
  }

  print('</vbox></groupbox>');
}

/**
* genere cadre description
*/
function anaisPropCadreDesc(){

  print('<groupbox flex="2"><caption label="Description"/>');
  $val=anaisDocValEntree(0,0,'description');
  $pos=strpos($val,'[');
  if (false!==$pos){
    $val=substr($val,0,$pos);
  }
  printf('<textbox value="%s" readonly="true" multiline="true" flex="1"/>',
          anaisDocFormatStrMulti(anaisConvertChaine($val)));
  print('</groupbox>');
}


/*
* cree une boite avec label et texbox
*  $labelwidth : valeur de witdh (prise en compte si non vide
*/
function anaisDocTextBox($label, $valeur, $labelwidth="") {

  if (empty($labelwidth))
    printf('<hbox><label value="%s"/><textbox value="%s" readonly="true" flex="1"/></hbox>',
            $label, anaisConvertChaine($valeur));
  else
    printf('<hbox><label value="%s" style="width:%s"/><textbox value="%s" readonly="true" flex="1"/></hbox>',
            $label, $labelwidth, anaisConvertChaine($valeur));

}

/*
* cree une boite avec label et texbox multiligne
*  $labelwidth : valeur de witdh (prise en compte si non vide
* $rows attribut rows
*/
function anaisDocTextMulti($label, $valeur, $labelwidth="") {

  if (empty($labelwidth))
    printf('<hbox><label value="%s"/><textbox value="%s" readonly="true" rows="8" multiline="true" flex="1"/></hbox>',
            $label, anaisConvertChaine($valeur));
  else
    printf('<hbox><label value="%s" style="width:%s"/><textbox value="%s" readonly="true" rows="8" multiline="true" flex="1"/></hbox>',
            $label, $labelwidth, anaisConvertChaine($valeur));

}


/**
*  teste si un attribut existe dans le tableau des résultats pour une entrée
*          retourne la première valeur du tableau des valeurs de l'attribut
*
*  @param  $index index dans le tableau des résultats
*  @param  $entree index de l'entrée dans le tableau de résultats
*  @param  $attr  nom de l'attribut
*
*  @return première valeur du tableau
*
*/
function anaisDocValEntree($index,$entree,$attr){


  if (!isset($GLOBALS['ldapresults'][$index]['data'][$entree][$attr])){
    return "";
  }
  if ('dn'==$attr) return $GLOBALS['ldapresults'][$index]['data'][$entree][$attr];
  return $GLOBALS['ldapresults'][$index]['data'][$entree][$attr][0];
}

/**
*  teste si un attribut existe dans le tableau des résultats pour une entrée
*          retourne toutes les valeurs du tableau des valeurs de l'attribut
*
*  @param  $index index dans le tableau des résultats
*  @param  $entree index de l'entrée dans le tableau de résultats
*  @param  $attr  nom de l'attribut
*
*  @return valeurs du tableau separee par \n
*/
function anaisDocValeursEntree($index, $entree, $attr){


  if (empty($GLOBALS['ldapresults'][$index]['data'][$entree][$attr])){
    return "";
  }
  if ('dn'==$attr) 
    return $GLOBALS['ldapresults'][$index]['data'][$entree][$attr];
  
  $valeurs="";
  $nb=count($GLOBALS['ldapresults'][$index]['data'][$entree][$attr]);
  for ($i=0;$i<$nb;$i++){
    if (!empty($valeurs))
      $valeurs.=SAUTLIGNE_DOC;
    $valeurs.=$GLOBALS['ldapresults'][$index]['data'][$entree][$attr][$i];
  }
  
  return $valeurs;
}


/**
* retourne l'url de la photo
*/
function anaisPropUrlPhoto($uid) {

  //mise en cache des donnees photo
  $jpegphoto='';
  if (isset($GLOBALS['ldapresults'][0]['data'][0]['uid'])) {
    $uid=$GLOBALS['ldapresults'][0]['data'][0]['uid'][0];
    if (!empty($GLOBALS['ldapresults'][0]['data'][0]['mineqpublicationphotointranet'][0]) &&
        1==$GLOBALS['ldapresults'][0]['data'][0]['mineqpublicationphotointranet'][0] &&
        !empty($GLOBALS['ldapresults'][0]['data'][0]['jpegphoto'][0])){

      $jpegphoto=$GLOBALS['ldapresults'][0]['data'][0]['jpegphoto'][0];

      if (!isset($_SESSION['jpegphoto'])){
        $_SESSION['jpegphoto']=Array();
      }
      $_SESSION['jpegphoto'][$uid]=Array();
      $_SESSION['jpegphoto'][$uid]=$jpegphoto;

      //calcul url
      $url=$GLOBALS['anaisconfig']['application']['urlracine'];
      $url.=$GLOBALS['anaisconfig']['application']['script'];
      $url.="?anaismoz-op=img&anaismoz-par=$uid";

      return $url;
    }
  }

  //avatar
  $img='avatar-v.png';//pourrait etre vide
  if (!empty($GLOBALS['ldapresults'][0]['data'][0]['gender'][0])){
    if ('M'==$GLOBALS['ldapresults'][0]['data'][0]['gender'][0]){
      $img='avatar-m.png';
    } else if ('F'==$GLOBALS['ldapresults'][0]['data'][0]['gender'][0]){
      $img='avatar-f.png';
    }
  }
  if (''==$img) return '';

  $prefimages=anaisGetUrlImages();

  return $prefimages.$img;
}


/**
* retourne la photo a partir de l'uid de la requete
* utilise les donnees en cache session si disponibles, sinon requete dans l'annuaire
*
*/
function anaisPropGetPhoto() {

  $uid=$GLOBALS['requete']['param'];

  if (!empty($_SESSION['jpegphoto'][$uid])){
    $jpegphoto=$_SESSION['jpegphoto'][$uid];
    $img=imagecreatefromstring($jpegphoto);
    if (false!=$img) {
      header('Content-Type: image/png');
      imagepng($img);
      imagedestroy($img);
      exit();
    }
  }
}

/**
* retourne un tableau avec width et height
* valeurs par défaut : 128x128
*/
define('ANAIS_MAX_PHOTO_WIDTH', 110);
define('ANAIS_MAX_PHOTO_HEIGHT', 146);
function anaisGetImageSize($uid) {

  $imagesize=array();

  $imagesize['width']=128;
  $imagesize['height']=128;

  if (!empty($_SESSION['jpegphoto'][$uid])){
    $jpegphoto=$_SESSION['jpegphoto'][$uid];
    $img=imagecreatefromstring($jpegphoto);
    if (false!=$img) {
      $imagesize['width']=imagesx($img);
      $imagesize['height']=imagesy($img);
      imagedestroy($img);
    }
  }

  //ajustements
  $minw=$imagesize['width']/ANAIS_MAX_PHOTO_WIDTH;
  $minh=$imagesize['height']/ANAIS_MAX_PHOTO_HEIGHT;
  if (1<$minw || 1<$minh) {
    $taux=$minh;
    if ($minw>$minh) {
      $taux=$minw;
    }
    $imagesize['width']=$imagesize['width']/$taux;
    $imagesize['height']=$imagesize['height']/$taux;
  }

  return $imagesize;
}


/*
* generation cadre gestionnaires de boîte BALS, BALU, BALF
*/
function anaisPropGestBal($libelle="Gestionnaires") {

  //déterminer index dans tableau des résultats
  $index=1;
  $nb=count($GLOBALS['ldapresults']);
  for (;$index<$nb;$index++){
    if ('gest-partages'==$GLOBALS['ldapresults'][$index]['op']){
      break;
    }
  }
  //élément tree
  printf('<groupbox flex="1"><caption label="%s"/>', $libelle);
  print('<tree flex="1" id="propbal-gest" seltype="single" hidecolumnpicker="true" rows="2" ondblclick="anaisDlgPropBal(this.id);">');
  if ($index==$nb){
    print('</tree></groupbox>');
    return;
  }

  $donnees=& $GLOBALS['ldapresults'][$index];
  $nbentrees=count($donnees['data']);

  //tri
  $cfgop=anaisConfigOperation($donnees['op'],$donnees['chemin']);
  if (!empty($cfgop)){
    usort($donnees['data'],$cfgop['tri']);
  }

  printf('<treecols><treecol id="propbal-ico" fixed="true" label=""/>'.
        '<treecol id="propbal-bals" label="Bo&#238;te(s) &#224; lettres (%s)" flex="1"/></treecols>', $nbentrees);

  //liste des gestionnaires
  print('<treechildren>');

  $config=anaisGetConfigAnnuaire($donnees['chemin']);

  for ($i=0;$i<$nbentrees;$i++){

    //url icone
    $urlicone=anaisDocIconeBoite($donnees['data'][$i]);

    if ('REFX'!=$donnees['data'][$i]['mineqtypeentree'][0]) {
      //chemin ldap
      $url=$donnees['data'][$i]['dn'];
      $url=anaisConvertDnDoc($url);
      $url="ldap://".$config['annuaireid']."/".$url;

      printf('<treeitem id="%s"><treerow>', $url);
    } else{
      print('<treeitem><treerow>');
    }

    //icone sur la première cellule
    printf('<treecell label="" align="center" src="%s"/>',$urlicone);

    $lib=anaisConvertChaine($donnees['data'][$i]['cn'][0]);

    printf('<treecell label=" %s"/>',$lib);

    print('</treerow></treeitem>');
  }

  print('</treechildren>');

  print('</tree></groupbox>');

}



/*
* generation cadre gestionnaires de liste
*/
function anaisPropGestListe($libelle="Gestionnaires") {

  //déterminer index dans tableau des résultats
  $index=1;
  $nb=count($GLOBALS['ldapresults']);
  for (;$index<$nb;$index++){
    if ('gest-liste'==$GLOBALS['ldapresults'][$index]['op']){
      break;
    }
  }
  //élément tree
  printf('<groupbox flex="1"><caption label="%s"/>', $libelle);
  print('<tree flex="1" id="propbal-gest" seltype="single" hidecolumnpicker="true" rows="2" ondblclick="anaisDlgPropBal(this.id);">');
  if ($index==$nb){
    print('</tree></groupbox>');
    return;
  }

  $donnees=& $GLOBALS['ldapresults'][$index];
  $nbentrees=count($donnees['data']);

  //tri
  $cfgop=anaisConfigOperation($donnees['op'],$donnees['chemin']);
  if (!empty($cfgop)){
    usort($donnees['data'],$cfgop['tri']);
  }

  printf('<treecols><treecol id="propbal-ico" fixed="true" label=""/>'.
        '<treecol id="propbal-bals" label="Bo&#238;te(s) &#224; lettres (%s)" flex="1"/></treecols>', $nbentrees);

  //liste des gestionnaires
  print('<treechildren>');

  $config=anaisGetConfigAnnuaire($donnees['chemin']);

  for ($i=0;$i<$nbentrees;$i++){

    //url icone
    $urlicone=anaisDocIconeBoite($donnees['data'][$i]);

    if ('REFX'!=$donnees['data'][$i]['mineqtypeentree'][0]) {
      //chemin ldap
      $url=$donnees['data'][$i]['dn'];
      $url=anaisConvertDnDoc($url);
      $url="ldap://".$config['annuaireid']."/".$url;

      printf('<treeitem id="%s"><treerow>', $url);
    } else{
      print('<treeitem><treerow>');
    }

    //icone sur la première cellule
    printf('<treecell label="" align="center" src="%s"/>',$urlicone);

    $lib=anaisConvertChaine($donnees['data'][$i]['cn'][0]);

    printf('<treecell label=" %s"/>',$lib);

    print('</treerow></treeitem>');
  }

  print('</treechildren>');

  print('</tree></groupbox>');

}

//onglet général pour le type LDAB
function anaisDocPropGenLdab() {

  print('<tabpanel><vbox flex="1">');

  //cadre boite aux lettres
  $cn=anaisDocValEntree(0,0,'cn');


  //cadre boites a lettres Liste de Distribution à ABonnement
  printf('<groupbox><caption label="%s"/>', anaisConvertChaine("Liste de distribution Sympa"));
  print('<vbox style="margin:4px;" flex="1">');
  anaisDocTextBox('Nom :', $cn);
  print('</vbox></groupbox>');

  //zone gestionnaire/description
  print('<vbox flex="1">');

  //cadre Gestionnaires et Membres
  //url sympa
  $urlsympa=anaisDocUrlSympa();
  print('<groupbox><caption label="Gestionnaires et Membres"/><vbox>');
    printf('<label value="%s"/>',
          "L'ensemble des informations disponibles sur cette liste est accessible &#224; cette adresse :");
    printf('<label value="%s" class="propbal-url" readonly="true" flex="1" onclick="anaisOuvreSite(this.value);"/>',anaisConvertChaine($urlsympa));
  printf('</vbox></groupbox>');

  //cadre description
  anaisPropCadreDesc();

  print('</vbox>');

  print('</vbox></tabpanel>');
}

//onglet sites web pour le type LDAB
function anaisDocMelWebLdab() {

  print('<tabpanel><vbox flex="1">');

  //valeur
  $desc=anaisDocMelLitValsEntree(0,0,'description');

  if ($desc) $desc=$desc[0];else $desc="";

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

  //url sympa
  $urlsympa=anaisDocUrlSympa();

  //contenu
  print('<groupbox flex="1"><caption label="Sites Web"/>');

  print('<vbox><groupbox flex="1"><caption label="Intranet:"/>');

  printf('<label value="%s" class="propbal-url" readonly="true" flex="1" onclick="anaisOuvreSite(this.value);"/></groupbox>',
          anaisConvertChaine($urlsympa));

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
* calcule de l'url sympa pour le type 'Liste de distribution Sympa'
* $index : index dans les résultats de requête ldap (0 par défaut)
* version 1.2:
* La nouvelle implémentation pour la construction du lien est la suivante :
* 
* On prend la valeur de l'attribut "mailPR" :
* - Si elle est en "nomdelaliste@developpement-durable.gouv.fr" alors le lien est https://developpement-durable.listes.m2.e2.rie.gouv.fr/sympa/info/nomdelaliste
* - Si elle est en "nomdelaliste@territoires.gouv.fr" alors le lien est https://territoires.listes.m2.e2.rie.gouv.fr/sympa/info/nomdelaliste
* - Si elle est en "nomdelaliste@i-carre.net" alors le lien est https://i-carre.listes.m2.e2.rie.gouv.fr/sympa/info/nomdelaliste
* - Si elle est en "nomdelaliste@cerema.fr" alors le lien est https://cerema.listes.m2.e2.rie.gouv.fr/sympa/info/nomdelaliste
* - Si elle est en "nomdelaliste@cop21.gouv.fr" alors le lien est https://cop21.listes.m2.e2.rie.gouv.fr/sympa/info/nomdelaliste
* - Si elle est en "nomdelaliste@educagri.fr" alors le lien est https://listes.diffusion.eat.educagri.fr/sympa/info/nomdelaliste
* -> Je pense que l'on peut prendre comme règle https://préfixedomaine.listes.m2.e2.rie.gouv.fr/sympa/info/nomdelaliste
*  avec préfixedomaine = chaine de caractères entre le "@" et le "." de l'attribut mailPR
* 
* * Pour le nom complet de la liste à afficher dans le cadre "Liste de distribution Sympa" :
*  - Prendre la valeur de l'attribut "cn"
*/
function anaisDocUrlSympa($index=0) {

  $mail=anaisDocValEntree(0,0,'mailpr');
  $compos=explode('@', $mail);
  if (2!=count($compos)) {
    return "";
  }

  $hote=$compos[1];
  $nom=$compos[0];
  
  $point=strpos($hote, ".");
  if (false===$point){
    return "";
  }
  $prefix=substr($hote, 0, $point);

  if ("communautes.agriculture.gouv.fr"==$hote){
    $urlsympa="https://agriculture.forums.listes.m2.e2.rie.gouv.fr/sympa/info/$nom";
  } else if ("jscs"==$prefix){
    $urlsympa="https://sante.listes.m2.e2.rie.gouv.fr/sympa/info/$nom";
  } else if ("educagri"==$prefix){
    $urlsympa="https://listes.diffusion.eat.educagri.fr/sympa/info/$nom";
  } else {
    $urlsympa="https://$prefix.listes.m2.e2.rie.gouv.fr/sympa/info/$nom";
  }

  return $urlsympa;
}

?>
