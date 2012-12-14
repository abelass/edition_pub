<?php
if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/autoriser');

function formulaires_edition_publique_charger($objet='article',$id='',$id_parent=0,$url_retour='',$table_source=''){
		
	$valeurs=array(
	'objet'=>$objet,
	'message_ok'=>_request('message_ok'),	
	'id'=>$id,
	'id_parent'=>$id_parent,
	'table_source'=>$table_source);
	
	
	return $valeurs;
	}
?>

