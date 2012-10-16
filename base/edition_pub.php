<?php
/**
 * Plugin Signaler des abus
 * (c) 2012 My Chacra
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


function edition_pub_declarer_tables_principales($tables_principales){
        // Extension de la table auteurs
        include_spip('inc/config');
		$objets_edition_pub=lire_config('edition_pub/objets_edition_pub');
		if(is_array($objets_edition_pub)){
		foreach($objets_edition_pub AS $objet){
			if($objet!='auteur'){
				$tables_principales['spip_'.$objet.'s']['field']['email_auteur']= "text DEFAULT '' NOT NULL";
				$tables_principales['spip_'.$objet.'s']['field']['nom_auteur']="text DEFAULT '' NOT NULL";
				}	
			$tables_principales['spip_'.$objet.'s']['field']['hash']="varchar(255) DEFAULT '' NOT NULL";				
			$tables_principales['spip_'.$objet.'s']['field']['ip']="varchar(40) DEFAULT '' NOT NULL";			
			}
		}
        return $tables_principales;
        
        
        
}



?>
