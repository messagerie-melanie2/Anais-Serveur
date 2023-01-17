<?php
$GLOBALS["anaisconfig"]=array (
  'version' => '1.0',
  'application' => 
  array (
    'urlracine' => 'http://anais.s2.m2.e2.rie.gouv.fr/',
    'script' => 'anaism2.php',
    'document' => 
    array (
      'fonction' => 'anaisProduitDoc',
      'images' => 'images/',
      'arborescence' => 
      array (
        'colonnes' => 
        array (
          0 => 
          array (
            'nom' => 'Liste des services',
            'mode' => '1',
            'id' => 'arbre-service',
          ),
        ),
      ),
      'boites' => 
      array (
        'colonnes' => 
        array (
          0 => 
          array (
            'nom' => 'Boîtes à lettres',
            'mode' => '1',
            'id' => 'boites-cn',
          ),
          1 => 
          array (
            'nom' => 'Téléphone',
            'mode' => '1',
            'id' => 'boites-tel',
          ),
          2 => 
          array (
            'nom' => 'Adresse électronique',
            'mode' => '0',
            'id' => 'boites-mail',
          ),
        ),
      ),
    ),
    'operations' => 
    array (
      0 => 
      array (
        'nom' => '',
        'anaisdata' => 'anaisExecDem',
        'contenu' => 'anaisDocContenuDem',
        'actions' => 
        array (
          0 => 
          array (
            'operation' => 'litarbre',
            'chemin' => 'ldap://ldap.m2.e2.rie.gouv.fr/ou=organisation,dc=equipement,dc=gouv,dc=fr',
          ),
          1 => 
          array (
            'operation' => 'listearbre',
            'chemin' => 'ldap://ldap.m2.e2.rie.gouv.fr/ou=organisation,dc=equipement,dc=gouv,dc=fr',
          ),
        ),
      ),
      1 => 
      array (
        'nom' => 'rechboite',
        'anaisdata' => 'anaisRechGlobal',
        'contenu' => 'anaisDocContenuBoites',
        'actions' => 
        array (
          0 => 
          array (
            'operation' => 'rechboite',
            'chemin' => 'ldap://ldap.m2.e2.rie.gouv.fr/ou=organisation,dc=equipement,dc=gouv,dc=fr',
          ),
        ),
      ),
    ),
    'pauline' => 
    array (
      'racinedn' => 'ou=organisation,dc=equipement,dc=gouv,dc=fr',
      'urlentite' => 'http://annuaire.e2.rie.gouv.fr/index.php',
      'urlbal' => 'http://annuaire.e2.rie.gouv.fr/details.php',
    ),
    'sympa' => 
    array (
      'urlbase' => 'https://sympa.melanie2.i2/sympa/info/',
    ),
  ),
  'annuaires' => 
  array (
    0 => 
    array (
      'annuaireid' => 'amande',
      'serveur' => 'ldap.m2.e2.rie.gouv.fr',
      'srvport' => '389',
      'racine' => 'ou=organisation,dc=equipement,dc=gouv,dc=fr',
      'sizelimit' => '5000',
      'nomaff' => 'Amande',
      'limrech' => '20',
      'saisierech' => '[\\x20\\x21\\x23-\\x26\\x27\\x28\\x29\\x2D-\\x3A\\x3C-\\x5D\\x5F\\x61-\\x7E\\xE0-\\xFC]+',
      'anaismoz-par-max' => '50',
      'anaismoz-par-reg' => '/[^\\x20\\x21\\x23-\\x26\\x27\\x28\\x29\\x2D-\\x3A\\x3C-\\x5D\\x5F\\x61-\\x7E\\xE0-\\xFC]+/u',
      'filtre_uid' => '/[^A-Za-z0-9\-\.\'_]+/',
      'limit_uid' => 100,      
      'operations' => 
      array (
        0 => 
        array (
          'nom' => 'litarbre',
          'anaisldap' => 'anaisldapLitEntree',
          'defer' => '1',
          'tri' => '',
          'filtre' => '(&(!(mineqportee=00))(objectclass=organizationalUnit))',
          'contenu' => 'anaisDocContenuArbre',
          'attributs' => 
          array (
            0 => 
            array (
              'nomldap' => 'cn',
              'col' => 'Liste des services',
            ),
            1 => 
            array (
              'nomldap' => 'objectclass',
              'col' => '',
            ),
            2 => 
            array (
              'nomldap' => 'description',
              'col' => '',
            ),
          ),
        ),
        1 => 
        array (
          'nom' => 'listearbre',
          'anaisldap' => 'anaisldapListeEntrees',
          'defer' => '1',
          'tri' => 'anaisDocTriConteneur',
          'filtre' => '(&(mineqportee>=21)(objectclass=organizationalUnit)(|(mineqTypeEntree=NSER)(mineqTypeEntree=NUNI)(mineqTypeEntree=NGRO)(mineqTypeEntree=NOHE)))',
	  'filtreutil' => '(&(!(mineqportee=00))(objectclass=organizationalUnit)(|(mineqTypeEntree=NSER)(mineqTypeEntree=NUNI)(mineqTypeEntree=NGRO)(mineqTypeEntree=NOHE)))',
          'contenu' => 'anaisDocContenuArbre',
          'attributs' => 
          array (
            0 => 
            array (
              'nomldap' => 'cn',
              'col' => 'Liste des services',
            ),
            1 => 
            array (
              'nomldap' => 'objectclass',
              'col' => '',
            ),
            2 => 
            array (
              'nomldap' => 'description',
              'col' => '',
            ),
            3 => 
            array (
              'nomldap' => 'mineqordreaffichage',
              'col' => '',
            ),
          ),
        ),
        2 => 
        array (
          'nom' => 'litboite',
          'anaisldap' => 'anaisldapLitEntree',
          'defer' => '1',
          'tri' => '',
          'filtre' => '(&(mineqportee>=30)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement)))',
          'filtreutil' => '(&(mineqportee>=01)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement)))',
          'contenu' => 'anaisDocMelContenuBoites',
          'attributs' => 
          array (
            0 => 
            array (
              'nomldap' => 'cn',
              'col' => 'Boîtes à lettres',
            ),
            1 => 
            array (
              'nomldap' => 'telephonenumber',
              'col' => 'Téléphone',
            ),
            2 => 
            array (
              'nomldap' => 'mailpr',
              'col' => 'Adresse électronique',
            ),
            3 => 
            array (
              'nomldap' => 'mineqportee',
              'col' => '',
            ),
            4 => 
            array (
              'nomldap' => 'mineqtypeentree',
              'col' => '',
            ),
            5 => 
            array (
              'nomldap' => 'mineqordreaffichage',
              'col' => '',
            ),
            6 => 
            array (
              'nomldap' => 'mail',
              'col' => '',
            ),
          ),
        ),
        3 => 
        array (
          'nom' => 'litboitetout',
          'anaisldap' => 'anaisldapLitEntree',
          'defer' => '1',
          'tri' => '',
          'filtre' => '(&(mineqportee>=30)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement)))',
          'filtreutil' => '(&(mineqportee>=01)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement)))',
        ),
        4 => 
        array (
          'nom' => 'listeboites',
          'anaisldap' => 'anaisldapListeEntrees',
          'defer' => '1',
          'tri' => 'anaisDocTriBoites',
          'filtre' => '(&(mineqportee>=30)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement)))',
          'filtreutil' => '(&(mineqportee>=01)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement)))',
          'contenu' => 'anaisDocMelContenuBoites',
          'attributs' => 
          array (
            0 => 
            array (
              'nomldap' => 'cn',
              'col' => 'Boîtes à lettres',
            ),
            1 => 
            array (
              'nomldap' => 'telephonenumber',
              'col' => 'Téléphone',
            ),
            2 => 
            array (
              'nomldap' => 'mailpr',
              'col' => 'Adresse électronique',
            ),
            3 => 
            array (
              'nomldap' => 'mineqportee',
              'col' => '',
            ),
            4 => 
            array (
              'nomldap' => 'mineqtypeentree',
              'col' => '',
            ),
            5 => 
            array (
              'nomldap' => 'mineqordreaffichage',
              'col' => '',
            ),
            6 => 
            array (
              'nomldap' => 'mail',
              'col' => '',
            ),
          ),
        ),
        5 => 
        array (
          'nom' => 'propbal',
          'anaisdata' => 'anaisdataPropBal',
          'contenu' => 'anaisPropBalM2',
        ),
        6 => 
        array (
          'nom' => 'rechboite',
          'anaisldap' => 'anaisldapChercheEntree',
          'defer' => '0',
          'tri' => '',
          'filtre' => '(&(mineqportee>=30)(|(xRA=Md)(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement))(|(mail=%v1)(cn=%v1)))',
          'filtreutil' => '(&(mineqportee>=01)(|(xRA=Md)(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement))(|(mail=%v1)(cn=%v1)))',
          'contenu' => 'anaisDocMelContenuBoites',
          'attributs' => 
          array (
            0 => 
            array (
              'nomldap' => 'cn',
              'col' => 'Boîtes à lettres',
            ),
            1 => 
            array (
              'nomldap' => 'telephonenumber',
              'col' => 'Téléphone',
            ),
            2 => 
            array (
              'nomldap' => 'mailpr',
              'col' => 'Adresse électronique',
            ),
            3 => 
            array (
              'nomldap' => 'mineqportee',
              'col' => '',
            ),
            4 => 
            array (
              'nomldap' => 'mineqtypeentree',
              'col' => '',
            ),
            5 => 
            array (
              'nomldap' => 'mail',
              'col' => '',
            ),
          ),
        ),
        7 => 
        array (
          'nom' => 'rechbs',
          'anaisdata' => 'anaisMelRechercheSimple',
          'tri' => 'anaisDocTriRechBoites',
          'filtre' => '(&(mineqportee>=30)(|(xRA=Md)(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement))(|(cn=%v1*)(uid=%v1)(mail=%v1)))',
          'filtreutil' => '(&(mineqportee>=01)(mineqportee<=29)(|(xRA=Md)(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement))(|(cn=%v1*)(uid=%v1)(mail=%v1)))',
          'contenu' => 'anaisDocMelRechercheSimple',
          'attributs' => 
          array (
            0 => 
            array (
              'nomldap' => 'cn',
              'col' => 'Boîtes à lettres',
            ),
            1 => 
            array (
              'nomldap' => 'telephonenumber',
              'col' => 'Téléphone',
            ),
            2 => 
            array (
              'nomldap' => 'mailpr',
              'col' => 'Adresse électronique',
            ),
            3 => 
            array (
              'nomldap' => 'mineqportee',
              'col' => '',
            ),
            4 => 
            array (
              'nomldap' => 'mineqtypeentree',
              'col' => '',
            ),
            5 => 
            array (
              'nomldap' => 'mail',
              'col' => '',
            ),
          ),
        ),
        8 => 
        array (
          'nom' => 'membres',
          'tri' => 'anaisDocTriBoitesAlpha',
          'anaisldap' => 'anaisldapMembres',
          'maxmembres' => '5000',
          'defer' => '1',
          'filtre' => '(&(mineqportee>=01)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement)))',
          'attributs' => 
          array (
            0 => 
            array (
              'nomldap' => 'mineqmelmembres',
              'col' => '',
            ),
            1 => 
            array (
              'nomldap' => 'mail',
              'col' => '',
            ),
          ),
        ),
        9 => 
        array (
          'nom' => 'membrede',
          'tri' => 'anaisDocTriBoites',
          'anaisldap' => 'anaisldapMembrede',
          'defer' => '1',
          'filtre' => '(&(mineqportee>=01)(|(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement)))',
          'attributs' => 
          array (
            0 => 
            array (
              'nomldap' => 'mailpr',
              'col' => '',
            ),
            1 => 
            array (
              'nomldap' => 'mineqmelmembres',
              'col' => '',
            ),
          ),
        ),
        10 => 
        array (
          'nom' => 'listebranche',
          'anaisdata' => 'anaisListeBranche',
          'contenu' => 'anaisDocContenuBranche',
        ),
        11 => 
        array (
          'nom' => 'gest-partages',
          'anaisdata' => '',
          'tri' => 'anaisDocTriBoites',
          'filtre' => '(&(mineqportee>=30)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement))(uid=%v1))',
          'filtreutil' => '(&(mineqportee>=01)(mineqportee<=29)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement))(uid=%v1))',
          'attributs' => 
          array (
            0 => 
            array (
              'nomldap' => 'cn',
              'col' => '',
            ),
            1 => 
            array (
              'nomldap' => 'telephonenumber',
              'col' => '',
            ),
            2 => 
            array (
              'nomldap' => 'mailpr',
              'col' => '',
            ),
            3 => 
            array (
              'nomldap' => 'mineqportee',
              'col' => '',
            ),
            4 => 
            array (
              'nomldap' => 'mineqtypeentree',
              'col' => '',
            ),
            5 => 
            array (
              'nomldap' => 'mail',
              'col' => '',
            ),
          ),
        ),
        12 => 
        array (
          'nom' => 'gest-liste',
          'anaisdata' => '',
          'defer' => '1',
          'tri' => 'anaisDocTriBoites',
          'filtre' => '(&(mineqportee>=30)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement)))',
          'filtreutil' => '(&(mineqportee>=01)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement)))',
          'attributs' => 
          array (
            0 => 
            array (
              'nomldap' => 'cn',
              'col' => '',
            ),
            1 => 
            array (
              'nomldap' => 'telephonenumber',
              'col' => '',
            ),
            2 => 
            array (
              'nomldap' => 'mailpr',
              'col' => '',
            ),
            3 => 
            array (
              'nomldap' => 'mineqportee',
              'col' => '',
            ),
            4 => 
            array (
              'nomldap' => 'mineqtypeentree',
              'col' => '',
            ),
            5 => 
            array (
              'nomldap' => 'mail',
              'col' => '',
            ),
          ),
        ),
        13 => 
        array (
          'nom' => 'secr',
          'anaisdata' => '',
          'tri' => 'anaisDocTriBoites',
          'filtre' => '(&(mineqportee>=30)(objectclass=mineqMelBoite)(businessCategory=secrétaire))',
          'filtreutil' => '(&(mineqportee>=01)(mineqportee<=29)(objectclass=mineqMelBoite)(businessCategory=secrétaire))',
          'attributs' => 
          array (
            0 => 
            array (
              'nomldap' => 'cn',
              'col' => '',
            ),
            1 => 
            array (
              'nomldap' => 'gender',
              'col' => '',
            ),
            2 => 
            array (
              'nomldap' => 'givenname',
              'col' => '',
            ),
            3 => 
            array (
              'nomldap' => 'sn',
              'col' => '',
            ),
            4 => 
            array (
              'nomldap' => 'telephonenumber',
              'col' => '',
            ),
            5 => 
            array (
              'nomldap' => 'mailpr',
              'col' => '',
            ),
            6 => 
            array (
              'nomldap' => 'mineqportee',
              'col' => '',
            ),
            7 => 
            array (
              'nomldap' => 'mineqtypeentree',
              'col' => '',
            ),
            8 => 
            array (
              'nomldap' => 'mineqordreaffichage',
              'col' => '',
            ),
            9 => 
            array (
              'nomldap' => 'mail',
              'col' => '',
            ),
          ),
        ),
        14 => 
        array (
          'nom' => 'rechtel',
          'anaisdata' => 'anaisMelRechercheSimple',
          'tri' => 'anaisDocTriBoites',
          'filtre' => '(&(mineqportee>=30)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement))(|(telephonenumber=*%v1)(mobile=*%v1)))',
          'filtreutil' => '(&(mineqportee>=01)(mineqportee<=29)(|(objectclass=mineqMelDP)(objectclass=mineqMelBoite)(objectclass=mineqMelListe)(objectclass=mineqMelListeAbonnement))(|(telephonenumber=*%v1)(mobile=*%v1)))',
          'contenu' => 'anaisDocMelRechercheSimple',
          'attributs' => 
          array (
            0 => 
            array (
              'nomldap' => 'cn',
              'col' => 'Boîtes à lettres',
            ),
            1 => 
            array (
              'nomldap' => 'telephonenumber',
              'col' => 'Téléphone',
            ),
            2 => 
            array (
              'nomldap' => 'mailpr',
              'col' => 'Adresse électronique',
            ),
            3 => 
            array (
              'nomldap' => 'mineqportee',
              'col' => '',
            ),
            4 => 
            array (
              'nomldap' => 'mineqtypeentree',
              'col' => '',
            ),
            5 => 
            array (
              'nomldap' => 'mail',
              'col' => '',
            ),
          ),
        ),
      ),
      'altserveurs' => 
      array (
      ),
    ),
  ),
);
?>
