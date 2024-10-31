<?php
/**
 * @package myposeo dashboard
 * @author Yann Dubois, G4Sarl
 * @version 1.1.3
 */

/*
 Plugin Name: myposeo dashboard
 Plugin URI: http://http://www.yann.com/en/wp-plugins/myposeo-dashboard
 Description: This plugin gives access to the <a href="https://www.myposeo.com/">myposeo</a> open web service API for a site and makes all SEO data available right in the Wordpress admin. 
 Version: 1.1.3
 Author: Yann Dubois, G4Sarl
 Author URI: http://www.myposeo.com/
 License: GPL2
 */

/**
 * @copyright 2010  Yann Dubois  ( email : yann _at_ abc.fr ) for myposeo ( https://www.myposeo.com/ )
 *
 *  Original development of this plugin was kindly funded by https://www.myposeo.com/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 Revision 1.1.3:
 - Maintenance release of [2011/08/28].
 - Minor improvements
 Revision 1.1.2:
 - First maintenance release improved [2011/03/30].
 - Minor text message improvement [http://mantis.g4interactive.com/view.php?id=2742]
 Revision 1.1.1:
 - First maintenance release variation [2011/03/29].
 - Switched http wrapper to wp_remote_get()
 Revision 1.1.0:
 - First maintenance release [2011/03/28].
 - Plugin name: dashboard with lowercase d [http://mantis.g4interactive.com/view.php?id=2613]
 - Small text change [http://mantis.g4interactive.com/view.php?id=2469]
 - Global $texts renamed to $myposeo_texts to avoid namespace collision
 - Small text change [http://mantis.g4interactive.com/view.php?id=2468]
 - Upgraded framework to VERSION 20110328-01
 - Check for PHP 5.0.0 minimum / explicit error message if wrong version [http://mantis.g4interactive.com/view.php?id=2369]
 - Explicit error message on failed wp_remote_fopen [http://mantis.g4interactive.com/view.php?id=2566]
 - Explicit error message on empty account data [http://mantis.g4interactive.com/view.php?id=2429]
 - Small CSS bugfix [http://mantis.g4interactive.com/view.php?id=2428]
 Revision 1.0.0:
 - Official first release.
 Revision 0.6.0 [RC4]:
 - Security update: only hashed password is now stored
 - Small visual improvements
 - Small interface improvements 
 Revision 0.5.0 [RC3]:
 - Small CSS visual improvements:
 - [http://mantis.g4interactive.com/view.php?id=2274]
 - Key verification upon manual key change
 - [http://mantis.g4interactive.com/view.php?id=2272] 
 Revision 0.4.0 [RC2]:
 - Small CSS visual improvements: 
 - [http://mantis.g4interactive.com/view.php?id=2256]
 - [http://mantis.g4interactive.com/view.php?id=2257]
 - [http://mantis.g4interactive.com/view.php?id=2266]
 - removed debug code:
 - [http://mantis.g4interactive.com/view.php?id=2269]
 - hide simplexml warning messages
 - improved xml connexion/parsing error handling
 Revision 0.3.0 [RC1]: 
 - Added jQuery calls, 
 - Added red background error message on API Key expired, 
 - Removed unused code
 - Misc. code cleanups
 - Improved text alerts and messages 
 Revision 0.2.0 [beta2]:
 - Small bugfix in drop-down, 
 - tracking API, 
 - visuals 
 Revision 0.1.0 [beta1]:
 - Original beta release
 */

/** Misc. Texts **/

global $myposeo_texts; 
$myposeo_texts = array (

	'option_page_title' => 'Configuration myposeo',

	'activation_notice' => '
		<strong>myposeo est presque prêt.</strong>
		Vous devez 
		<a href="APIKEYADMINURL">
		saisir votre clé d\'API myposeo</a>
		pour que cela fonctionne.
	',

	'option_page_text' => '
		Le plugin
		<a href="http://www.myposeo.com/">myposeo</a>
		vous permet de retrouver vos rapports de positionnement directement dans votre
		blog WordPress. Vous pouvez trouver votre clé d\'API sur la page "Configuration" de votre compte
		myposeo.<br/>
		Vous ne connaissez pas votre clé ? Inscrivez les identifiants de votre
		compte myposeo et votre clé sera automatiquement récupérée.
	',

	'cle_absente_menu' => '
		Pour enregistrer votre clé d\'API,
		rendez-vous sur la page de configuration.
	',

	'cle_incorrecte_menu' => '
		Votre clé d\'API ne semble pas correcte. 
		Merci de la vérifier sur votre page de configuration.
	',

	'liste_vide_menu' => '
		Votre compte myposeo n\'a actuellement aucune donnée, 
		merci d\'enregistrer au moins une URL.
	',

	'mdp_incorrect' => 'Identifiant ou mot de passe incorrect. L\'authentification a échoué.',

	'identification_ok' => 'Identification réussie, votre clé d\'API a été enregistrée.',

	'titre_listing' => 'myposeo - Liste des URLs',
	
	'cle_incorrecte' => '
		Votre clé myposeo n\'est pas correcte. 
		Veuillez la vérifier.
	',

	'identification_nok' => '
		Vos identifiants ne sont pas corrects. Veuillez les vérifier',
	
	'erreur_connexion' => 'Connexion impossible au service web de myposeo.',

	'maj_options' => 'Mettre à jour les options &raquo;',

	'recup_cle'	=> 'Récupérer ma clé myposeo »',

	'lien_recharger' => 'Veuillez <a href="?page=tl_myposeo-dashboard">Cliquer ici</a> pour recharger vos données myposeo.',

	'wp_remote_fopen_error' => '
		<strong>Erreur:</strong> impossibilité d\'ouvrir un accès vers l\'api myposeo.<br/>
		Veuillez vérifier&nbsp;:<br/>
		- Si l\'extension curl est activée et chargée au niveau de votre installation de PHP;<br/>
		- Si la directive allow_url_fopen est autorisée au niveau de votre configuration de PHP.<br/>
		<em>Il est possible que ces réglages doivent être faits par votre hébergeur.</em><br/>
		Problème rencontré&nbsp;: la fonction wp_remote_fopen() a échoué.
	',
);

/** Class includes **/

include_once( 'inc/yd-widget-framework.inc.php' );	// standard framework VERSION 20110328-01 or better
include_once( 'inc/myposeo.inc.php' );				// custom classes

/** **/
if( class_exists( 'myposeoPlugin' ) ) {
	$junk = new myposeoPlugin( 
		array(
			'name' 				=> 'myposeo dashboard',
			'version'			=> '1.1.3',
			'has_option_page'	=> true,
			'option_page_title' => $myposeo_texts['option_page_title'],
			'op_donate_block'	=> false,
			'op_credit_block'	=> false,
			'op_support_block'	=> false,
			'has_toplevel_menu'	=> true,
			'has_shortcode'		=> false,
			'has_widget'		=> false,
			'widget_class'		=> '',
			'has_cron'			=> false,
			'crontab'			=> array(
				//'daily'			=> array( 'YD_MiscWidget', 'daily_update' ),
				//'hourly'		=> array( 'YD_MiscWidget', 'hourly_update' )
			),
			'has_stylesheet'	=> false,
			'stylesheet_file'	=> 'css/yd.css',
			'has_translation'	=> true,
			'translation_domain'=> 'myposeo', // must be copied in the widget class!!!
			'translations'		=> array(
				array( 'English', 'Yann Dubois', 'http://www.yann.com/' ),
				array( 'French', 'myposeo', 'http://www.myposeo.com/' )
			),		
			'initial_funding'	=> array( 'myposeo', 'http://www.myposeo.com' ),
			'additional_funding'=> array(),
			'form_blocks'		=> array(
				'Clé pour l\'API myposeo' => array( 
					'myposeo_api_key'	=> 'text',
					'block_1_submit'	=> 'submit'
				),
				'Connexion à votre compte' => array(
					'email'				=> 'text',
					'password'			=> 'password',
					'password_crypted'	=> 'hidden'
				)
			),
			'option_field_labels'=>array(
					'myposeo_api_key'	=> 'Clé de l\'API&nbsp;:',
					'email'				=> 'Email&nbsp;:',
					'password'			=> 'Mot de passe&nbsp;:',
					'block_1_submit'	=> $myposeo_texts['maj_options'],
					'password_crypted'	=> ''
			),
			'option_defaults'	=> array(
					'myposeo_api_key'	=> '',
					'email'				=> '',
					'password'			=> '',
					'password_crypted'	=> ''
			),
			'form_add_actions'	=> array(
					//'Mettre à jour les options'	=> array( 'THIS', 'update_options' ),
					$myposeo_texts['recup_cle']	=> array( 'THIS', 'accountConnect' )
					// use 'THIS' to call function on instantiated object
			),
			'has_cache'			=> false,
			'option_page_text'	=> $myposeo_texts['option_page_text'],
			'backlinkware_text' => '<!-- myposeo dashboard plugin by G4SARL & YD -->',
			'plugin_file'		=> __FILE__,	
			'has_activation_notice'	=> true,
			'activation_notice' => $myposeo_texts['activation_notice'],
			'form_method'		=> 'post'
	 	)
	);
} else {
	/** Class was not defined (wrong PHP version?) **/
	/** do nothing **/
}
?>