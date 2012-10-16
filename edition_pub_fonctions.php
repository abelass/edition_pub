<?php
/**
 * Plugin Édition publique
 * (c) 2012 My Chacra
 * Licence GNU/GPL
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/autoriser');
include_spip('inc/filtres_ecrire');

function ajouter_documents($id,$type){

	$documenter_objet = charger_fonction('documenter_objet','inc');
	$documents = $documenter_objet($id,$type);
	return $documents;

	}

?>
