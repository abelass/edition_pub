<?php
/**
 * Plugin Édition publique
 * (c) 2012 My Chacra
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


function edition_pub_recuperer_fond($flux){
	include_spip('inc/config');
	$fond=$flux['args']['fond'] ;
	$texte=$flux['data']['texte'];
	$contexte=$flux['args']['contexte'];
    $objets_edition_pub=lire_config('edition_pub/objets_edition_pub')?lire_config('edition_pub/objets_edition_pub'):array();  
    list($chemin_fond,$objet_fond) =explode('_',$fond);	
	
	
	//Intervention dans le formulaire edition_article
    if ($chemin_fond == 'formulaires/editer' AND in_array($objet_fond,$objets_edition_pub)  AND !_request('exec')  AND (!isset($GLOBALS['visiteur_session']['id_auteur']))){
		$patterns = array('#<li class="editer editer_titre obligatoire">#');
		$form_email=recuperer_fond('formulaires/inc-editer_objet_connection',$contexte);	    
		$replacements = array($form_email.'<li class="editer editer_email obligatoire">');						
		$flux['data']['texte'] = preg_replace($patterns,$replacements,$texte,1);
	}
	return $flux;
    }	
	

function edition_pub_formulaire_charger($flux){
    include_spip('inc/config');	
    $form = $flux['args']['form'];
    $objets_edition_pub=lire_config('edition_pub/objets_edition_pub')?lire_config('edition_pub/objets_edition_pub'):array();
    list($action_form,$objet_form) =explode('_',$form);

    if ($action_form =='editer' AND in_array($objet_form,$objets_edition_pub) AND !_request('exec')){ 	
		$statut='publie';
		$flux['data']['email_auteur']=_request('email_auteur');
		$flux['data']['nom_auteur']=_request('nom_auteur');
		$flux['data']['statut']=_request('statut');
		$flux['data']['_hidden'].='<input type="hidden" name="statut" value="'.$statut.'"/>';
		}
    
    if ($form == 'joindre_document'  AND !_request('exec')){
        $id_objet=$flux['data']['id_objet'];
        $objet=$flux['data']['objet'];
        $hash=sql_getfetsel('hash','spip_'.$objet.'s','id_'.$objet.'='.$id_objet);

        if($_COOKIE[$hash]) $flux['data']['editable'] = ' ';  
       
        }

	return $flux ;
}

function edition_pub_formulaire_verifier($flux){	
    include_spip('inc/config');	
    $form = $flux['args']['form'];
    $objets_edition_pub=lire_config('edition_pub/objets_edition_pub')?lire_config('edition_pub/objets_edition_pub'):array();  
    list($action_form,$objet_form) =explode('_',$form);	

    if ($action_form =='editer' AND !_request('exec')){ 
		
		$anonyme=lire_config('edition_pub/edition_anonyme');
		
		if(!$anonyme   AND (!isset($GLOBALS['visiteur_session']['id_auteur']))){
			$obligatoires=array('nom_auteur','email_auteur');			
			foreach($obligatoires AS $champ){
				if(!_request($champ))$flux['data'][$champ]=_T("info_obligatoire");
				}

	if($email_auteur AND !email_valide($email_auteur))$flux['data']['email_auteur'] = _T('form_prop_indiquer_email');
			}	
		}

	return $flux ;
}



function edition_pub_formulaire_traiter($flux){
	include_spip('inc/config');
    $form = $flux['args']['form'];
    $objets_edition_pub=lire_config('edition_pub/objets_edition_pub')?lire_config('edition_pub/objets_edition_pub'):array();  
    list($action_form,$objet_form) =explode('_',$form);	
    
    if ($action_form =='editer' AND in_array($objet_form,$objets_edition_pub) AND !_request('exec')){

		$id_objet= $flux['data']['id_'.$objet_form];

        $anonyme=lire_config('edition_pub/edition_anonyme');
        $ip=$GLOBALS['ip'];	
        $table='spip_'.$objet_form.'s';

		// Derniers contr&ocirc;les anti spam
		// ne pas publier automatiquement certains messages suspects ...
		// sauf si le posteur a de toute facon le pouvoir de moderer et de se publier
		include_spip('inc/autoriser');
		
		$statut=_request('statut');
		
		if ($statut == 'publie' AND (!isset($GLOBALS['visiteur_session']['id_auteur']))){
	
		$email = strlen(_request('email')) ? " OR email=".sql_quote(_request('email')):"";
				$spammeur_connu = (!isset($GLOBALS['visiteur_session']['statut']) AND (sql_countsel($table,'(ip='.sql_quote($GLOBALS['ip'])."$email) AND statut='spam'")>0));
				
		$data=sql_fetsel('*',$table,'id_'.$objet_form.'='.$id_objet);		

	
				// si c'est un spammeur connu,
				// verifier que cette ip n'en est pas a son N-ieme spam en peu de temps
				// a partir d'un moment on refuse carrement le spam massif
				if ($spammeur_connu){
					// plus de 10 spams dans les dernieres 2h, faut se calmer ...
					// ou plus de 30 spams dans la dernieres 1h, faut se calmer ...
					if (
						($nb=sql_countsel($table,'statut=\'spam\' AND (ip='.sql_quote($GLOBALS['ip']).$email.') AND maj>DATE_SUB(NOW(),INTERVAL 120 minute)'))>10
						OR
						($nb=sql_countsel($table,'statut=\'spam\' AND (ip='.sql_quote($GLOBALS['ip']).$email.') AND maj>DATE_SUB(NOW(),INTERVAL 60 minute)'))>30
						){
						$statut='spam'; // on n'en veut pas !
						spip_log("[Refuse] $nb spam pour (ip=".$GLOBALS['ip']."$email) dans les 2 dernieres heures",'nospam');
					}
				}
	
				// si c'est un message bourre de liens, on le modere
				// le seuil varie selon le champ et le fait que le spammeur est deja connu ou non
				$seuils = array(
					// seuils par defaut
					0=>array(
						0=>array(1=>'prop',3=>'spam'), // seuils par defaut
						'url_site' => array(2=>'spam'), // 2 liens dans le champ url, c'est vraiment louche
						'texte'=>array(4=>'prop',20=>'spam') // pour le champ texte
					),
					// seuils severises pour les spammeurs connus
					'spammeur'=>array(
						0=>array(1=>'spam'),
						'url_site' => array(2=>'spam'), // 2 liens dans le champ url, c'est vraiment louche
						'texte'=>array(1=>'prop',5=>'spam')
					)
				);
				
				
				
				$seuils = $spammeur_connu?$seuils['spammeur']:$seuils[0];
				include_spip("inc/nospam"); // pour analyser_spams()
				foreach($data as $champ=>$valeur) {
					$infos = analyser_spams($valeur);
					if ($infos['contenu_cache']) {
						// s'il y a du contenu caché avec des styles => spam direct
						$statut = 'spam';
					}
					elseif ($infos['nombre_liens'] > 0) {
						// si un lien a un titre de moins de 3 caracteres, c'est louche...
						if ($infos['caracteres_texte_lien_min'] < 3) {
							$statut = 'prop'; // en dur en attendant une idee plus generique
						}
						
						if (isset($seuils[$champ]))
							$seuil = $seuils[$champ];
						else
							$seuil = $seuils[0];
	
						foreach($seuil as $s=>$stat)
							if ($infos['nombre_liens'] >= $s){
								$statut = $stat;
								spip_log("\t".$flux['data']['auteur']."\t".$GLOBALS['ip']."\t"."requalifié en ".$stat." car nombre_liens >= ". $s,'nospam');
							}
	
						if ($statut != 'spam'){
							$champs = array_unique(array('texte',$champ));
							if ($h = rechercher_presence_liens_spammes($infos['liens'],1,$table,$champs)){
								$statut = 'spam';
								spip_log("\t".$flux['data']['auteur']."\t".$GLOBALS['ip']."\t"."requalifié en spam car lien $h deja dans un spam",'nospam');
							}
						}
					}
				}
	
	
				// verifier qu'un message identique n'a pas ete publie il y a peu
				if ($statut != 'spam'){
					if (sql_countsel($table,'texte='.sql_quote($data['texte'])." AND statut IN ('publie','off','spam')")>0)
						$statut='spam';
				}
				// verifier que cette ip n'en est pas a son N-ieme post en peu de temps
				// plus de 5 messages en 5 minutes c'est suspect ...
				if ($statut!= 'spam'){
					if (($nb=sql_countsel($table,'ip='.sql_quote($GLOBALS['ip']).' AND maj>DATE_SUB(NOW(),INTERVAL 5 minute)'))>5)
						$statut='spam';
					#spip_log("$nb post pour l'ip ".$GLOBALS['ip']." dans les 5 dernieres minutes",'nospam');
				}
		  }
	

        $valeurs=array('statut'=>$statut);
        if($lang=_request('lang_dest'))$valeurs['lang'] =$lang;
        if(!$anonyme AND (!isset($GLOBALS['visiteur_session']['id_auteur']))){
			$valeurs['ip'] = $ip;
			}
		elseif(!isset($GLOBALS['visiteur_session']['id_auteur'])){
			include_spip('inc/cookie');
			 $hash=hash('sha256',$data['titre'].time());
			 $valeurs['hash']=$hash;
			 spip_setcookie($hash,$id_objet,time()+3600*24);
			}
        $valeurs['email_auteur'] = _request('email_auteur');
		$valeurs['nom_auteur'] = _request('nom_auteur');	
        sql_updateq('spip_'.$objet_form.'s',$valeurs,'id_'.$objet_form.'='.$id_objet);
        	
       if($statut == 'publie' OR (isset($GLOBALS['visiteur_session']['id_auteur']))){
			$url_retour=parametre_url(generer_url_entite($id_objet,$objet_form),'edition','mod','&');		   
		   header("location:/$url_retour");
		}
	}
	
    if ($form =='configurer_edition_pub'){
		// Installer les champs extras utilis&eacute; pour les objets actifs
		include_spip('inc/config');
        include_spip('base/create');	
		$objets_edition_pub=lire_config('edition_pub/objets_edition_pub')?lire_config('edition_pub/objets_edition_pub'):array();
		
		if(is_array($objets_edition_pub)){
			foreach($objets_edition_pub AS $objet){
				$tables[]='spip_'.$objet.'s';
				}
				
			maj_tables($tables);
		}
}
		
    
	return $flux ;
}







?>
