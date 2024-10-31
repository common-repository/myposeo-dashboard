<?php
if( version_compare( PHP_VERSION, REQUIRED_PHP_VER ) < 0 )
	return;

include_once( 'wsapi.inc.php' );

class myposeoPlugin extends YD_Plugin {

	const MYPOSEO_ICON_URL		= 'https://www.myposeo.com/img/logo-wordpress.png';
	const MYPOSEO_SMALLICON_URL	= '/img/myposeo-small.png'; // Start with slash, plugin_dir will be prepended
	const MYPOSEO_CSS_URL		= '/css/myposeo_admin.css'; // Start with slash, plugin_dir will be prepended
	const MYPOSEO_REMOTE_CSS	= 'https://www.myposeo.com/css/wp_plugin_wordpress.css';
	const MYPOSEO_REMOTE_JS		= 'https://www.myposeo.com/js/wp_plugin_wordpress.js';
	const MYPOSEO_JS_IN_FOOTER	= true;
	const URLS_IN_MENU			= 5;
	const SHORT_URL_LENGTH		= 16; // (int) Max size (in characters) of left-side menu URL string
	
	private $valid_key = false;
	
	/** constructor **/
	function myposeoPlugin ( $opts ) {
		$this->processTexts( &$opts );
		parent::YD_Plugin( $opts );
		add_action( 'admin_init', array( &$this, 'init_css' ) );
		
		$this->form_blocks		= $opts['form_blocks']; // No backlinkware
	}

	function init_css() {
		wp_register_style( 'myposeoStylesheet', WP_PLUGIN_URL . '/' . $this->plugin_dir . self::MYPOSEO_CSS_URL );
		wp_register_style( 'myposeoStylesheet2', self::MYPOSEO_REMOTE_CSS . '?plugin_version=' . $this->version );
		wp_register_script( 'myposeoScript', self::MYPOSEO_REMOTE_JS . '?plugin_version=' . $this->version, array('jquery'), false, self::MYPOSEO_JS_IN_FOOTER );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'myposeoScript' );
	}
	
	function admin_style() {
		wp_enqueue_style( 'myposeoStylesheet' );
		wp_enqueue_style( 'myposeoStylesheet2' );
	}
	
	function accountConnect( $op, $p ) {
		global $myposeo_texts;
		$options = get_option( $this->option_key );
		//$op->update_msg .= '<p>' . __('Connecting...') . '</p>';
		//$op->update_msg .= '<p>email: ' . $p['email'] . '</p>';
		//$op->update_msg .= '<p>password: ' . $p['password'] . '</p>';
		$mph = wsApi::getInstance();
		$mph->login		= html_entity_decode( stripslashes( $p['email'] ) );
		if( $p['password'] == '********' && $options['password_crypted'] != '' ) {
			$mph->password 	= html_entity_decode( stripslashes( $options['password_crypted'] ) );
			$mph->wsGetkeycrypt();
		} else {
			$mph->password 	= html_entity_decode( stripslashes( $p['password'] ) );
			$mph->wsCryptgetkey();
		}
		//$op->update_msg .= '<p>Result: ' . $mph->result . '</p>';
		$mph->wsParse();
		if( isset( $mph->xml->code ) && $mph->xml->code == 1 ) {
			//$op->update_msg .= '<p>' . $myposeo_texts['identification_ok'] . '</p>';
			if( !$this->green_displayed ) {
				echo '<div class="updated below-h2 myposeo_green"><p>' . $myposeo_texts['identification_ok'] . '</p></div>';
				echo '<div class="updated below-h2 myposeo_green"><p>' . $myposeo_texts['lien_recharger'] . '</p></div>';
			}
			$this->green_displayed = true;
			$p['password_crypted'] = $mph->xml->password_crypted;
			$p['password'] = '********';
			$p['myposeo_api_key'] = $mph->xml->key;
			$p['valid_key'] = true;
			$this->valid_key = true;
			$this->update_options( $op, $p );
		} elseif( isset( $mph->xml->code ) && $mph->xml->code == -1 ) {
			$op->error_msg .= '<p>' . $myposeo_texts['mdp_incorrect'] . '</p>';
			if( $mph->xml->message != 'Les identifiants sont incorrects.' ) $op->error_msg .= '<p>' . $mph->xml->message . '</p>';
			$p['password'] = '';
			$p['password_crypted'] = '';
			$p['valid_key'] = false;
			$this->valid_key = false;
			$this->update_options( $op, $p );
		} else {
			$op->error_msg .= '<p>' . $myposeo_texts['erreur_connexion'] . '</p>';
			$p['valid_key'] = false;
			$this->valid_key = false;
			$this->update_options( $op, $p );
			if( $mph->error && preg_match( '/wp_remote_/', $mph->msg ) ) {
				$op->update_msg = '';
				$mph->msg = $myposeo_texts['wp_remote_fopen_error'];
			}
		}
		$op->update_msg .= $mph->msg;
	}
	
	function verifyKey( $op, $p ) {
		global $myposeo_texts;
		$mph = wsApi::getInstance();
		$mph->key		= html_entity_decode( stripslashes( $p['myposeo_api_key'] ) );
		$mph->wsVerifykey();
		$mph->wsParse();
		if( isset( $mph->xml->code ) && $mph->xml->code == 1 ) {
			if( !$this->green_displayed ) {
				echo '<div class="updated below-h2 myposeo_green"><p>' . $myposeo_texts['identification_ok'] . '</p></div>';
				echo '<div class="updated below-h2 myposeo_green"><p>' . $myposeo_texts['lien_recharger'] . '</p></div>';
			}
			$this->green_displayed = true;
		} elseif( isset( $mph->xml->code ) && $mph->xml->code == -1 ) {
			$op->error_msg .= '<p>' . $myposeo_texts['cle_incorrecte'] . '</p>';
			$p['valid_key'] = false;
			$this->valid_key = false;
		} else {
			$op->error_msg .= '<p>' . $myposeo_texts['erreur_connexion'] . '</p>';
		}
		$op->update_msg .= $mph->msg;
	}

	function getUrls( $limit = 1000, $op = null ) {
		$options = get_option( $this->option_key );
		$mph = wsApi::getInstance();
		$mph->key = $options['myposeo_api_key'];
		//$op->update_msg .= '<p>Fetching myposeo data...</p>';
		$mph->wsGeturls( $limit );
		$mph->wsParse();
		if( isset( $mph->xml->urls->url ) ) {
			$a = array();
			foreach ($mph->xml->urls->url as $url ) {
				$a[] = $url;
			}
			$a = array_splice( $a, 0, $limit );
			return $a;
		} elseif( $mph->xml->code == -1 ) {
			//error
			return false;
		} else {
			$a = array();
			return $a;
		}
	}
	
	function getMyposeo( $url_id, $op ) {
		$options = get_option( $this->option_key );
		$mph = wsApi::getInstance();
		$mph->key = $options['myposeo_api_key'];
		//echo 'getMyposeo()<br/>';
		$html = $mph->wsGethtml( $url_id );
		if( $html ) {
			echo $html;
		} else {
			echo '<p>myposeo API error: could not get HTML data for: ' . $url . '</p>';
		}
	}
		
	function renderData() {
		if ( !current_user_can( 'manage_options' ) )  {
   			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
  		}
  		
  		$url_id = $this->getIdfrompageurl();
  		
  		$op = new myposeoPage( &$this );
  		$op->title = 'myposeo';
  		$op->sanitized_name = $this->sanitized_name;
  		$op->plugin_dir = $this->plugin_dir;
  		$op->has_cache = $this->has_cache;
  		$op->plg_tdomain = $this->tdomain;
  		$op->mp_url = $url_id;
  		$op->header();
  		$op->dropDown();
  		$op->embedMyposeo();
  		$op->dropDown();
  		$op->footer();
	}
	
	function listUrl() {
		global $myposeo_texts;
		if ( !current_user_can( 'manage_options' ) )  {
   			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
  		}
  		
  		$op = new myposeoPage( &$this );
  		if ( $_GET['id'] ) {
  			$op->title = 'myposeo';
  		} else {
  			$op->title = $myposeo_texts['titre_listing'];
  		}
  		$op->sanitized_name = $this->sanitized_name;
  		$op->plugin_dir = $this->plugin_dir;
  		$op->has_cache = $this->has_cache;
  		$op->plg_tdomain = $this->tdomain;
  		$op->mp_url = $url_id;
  		$op->header();
  		$op->listMyposeo();
  		$op->footer();
	}
	
	function getIdfrompageurl( $page = false ) {
		if( !$page ) $page = $_GET['page'];
		if( preg_match( '/^tl_myposeo_(\d+)$/', $page, $matches ) ) {
			return $matches[1];
		} else {
			return 0;
		}
	}
	
	function processTexts( $texts_array ) {
		if( !is_array( $texts_array ) ) return;
		$replace_what= array (
			'APIKEYADMINURL'
		);
		$replace_by = array(
			'options-general.php?page=' . sanitize_title( $texts_array['name'] )
		);
		foreach( $texts_array as $key => $value) {
			if( !is_string( $value ) ) continue;
			$value = str_replace( $replace_what, $replace_by, $value );
			$texts_array[$key] = $value;
		}
		return $texts_array;
	}
	
	/** overloaded YD_Plugin core function **/
	function admin_notice() {
		global $myposeo_texts;
		if( $_SERVER['REQUEST_METHOD'] != 'POST' ) {
			$options = get_option( $this->option_key );
			if( !$options['myposeo_api_key'] ) {
				echo '<div class="updated"><p>';
				echo $this->activation_notice;
				echo '</p></div>';
			} elseif( $this->valid_key === false && isset( $options['valid_key'] ) && $options['valid_key'] === false ) {
				echo '<div class="error"><p>';
				echo $myposeo_texts['cle_incorrecte'];
				echo '</p></div>';
			}
		}
	}
	
	/** overloaded YD_Plugin core function **/
	function create_menu() {
		$page = add_options_page(
			'Configuration myposeo', 
			'Config. myposeo',
			'manage_options',
			$this->sanitized_name, 
			array( &$this, 'plugin_options' )
		);
		add_action( 'admin_print_styles-' . $page, array( &$this, 'admin_style' ) );
	}
	
	/** overloaded YD_Plugin core function **/
	function yd_add_menu_page() {
		global $myposeo_texts;
		$options = get_option( $this->option_key );
		$page = add_menu_page( 
			$page_title	= 'myposeo page', 
			$menu_title	= 'myposeo', 
			$capability	= 'manage_options', 
			$menu_slug	= 'tl_' . $this->sanitized_name, 
			$function	= array( &$this, 'plugin_options' ), 
			$icon_url	= WP_PLUGIN_URL . '/' . $this->plugin_dir 
				. self::MYPOSEO_SMALLICON_URL //, 
			//$position 
		);
		add_action( 'admin_print_styles-' . $page, array( &$this, 'admin_style' ) );
		if( !$options['myposeo_api_key'] ) {
			$menu_title =  $myposeo_texts['cle_absente_menu'];
			$valid_key = false;
		} else {
			if( false !== $urls = $this->getUrls( self::URLS_IN_MENU ) ) {
				$valid_key = true;
				$options['valid_key'] = true;
				if( empty( $urls ) ) {
					$menu_title		= $myposeo_texts['liste_vide_menu'];
					$empty_list = true;
				} else {
					$empty_list = false;
				}
			} else {
				$menu_title		= $myposeo_texts['cle_incorrecte_menu'];
				$valid_key = false;		
				$options['valid_key'] = false;
			}
			update_option( $this->option_key, $options );
		}
		if( $valid_key && !$empty_list ) {
			$page = add_submenu_page( 
				$parent_slug	= $menu_slug, 
				$page_title		= 'Configuration', 
				$menu_title		= 'Configuration', 
				$capability		= 'manage_options',
				$submenu_slug	= $menu_slug, 
				$function		= $function
			);
			add_action( 'admin_print_styles-' . $page, array( &$this, 'admin_style' ) );
			foreach( $urls as $url ) {
				$shorturl = $url->location;
				//DEBUG: $shorturl = 'www.url.tres.long.com';
				if( strlen( $shorturl ) > self::SHORT_URL_LENGTH )
					$shorturl = preg_replace( '/^www\./i', '', $shorturl );
				if( strlen( $shorturl ) > self::SHORT_URL_LENGTH )
					$shorturl = preg_replace( '/\.[^\.]*$/', '', $shorturl );
				$page = add_submenu_page( 
					$parent_slug	= $menu_slug, 
					$page_title		= $url->location, 
					$menu_title		= $shorturl, 
					$capability		= 'manage_options', 
					$submenu_slug	= 'tl_myposeo_' . $url['id'], 
					$function		= array( &$this, 'renderData' )
				);
				add_action( 'admin_print_styles-' . $page, array( &$this, 'admin_style' ) );		
			}
			$page = add_submenu_page( 
				$parent_slug	= $menu_slug, 
				$page_title		= 'Toutes les URLs', 
				$menu_title		= 'Toutes les URLs', 
				$capability		= 'manage_options', 
				$submenu_slug	= 'tl_myposeo_list', 
				$function		= array( &$this, 'listUrl' )
			);
			add_action( 'admin_print_styles-' . $page, array( &$this, 'admin_style' ) );	
		} else {
			/** debug **
			$mph = wsApi::getInstance();
			$menu_title .= ' - ' . $mph->msg;
			** /debug **/
			$page = add_submenu_page( 
				$parent_slug	= $menu_slug, 
				$page_title		= $this->plugin_name, 
				$menu_title		= $menu_title, 
				$capability		= 'manage_options', 
				$menu_slug		= $menu_slug, 
				$function
			);
			add_action( 'admin_print_styles-' . $page, array( &$this, 'admin_style' ) );		
		}
	}
	
	/** overloaded YD_Plugin core function **/
	function update_options( $op, $params ) {
		$options = get_option( $this->option_key );
		if( $options['myposeo_api_key'] != $params['myposeo_api_key'] )
			$this->verifyKey( &$op, $params );
		if( !$params['password_crypted'] ) 
			$params['password_crypted'] = $options['password_crypted'];
		parent::update_options( $op, $params );
	}
}

class myposeoPage extends YD_OptionPage {
		
	/** constructor **/
	function myposeoPage( $caller ) {
		parent::YD_OptionPage( $caller );
		global $myposeo_texts;
		//if( $this->title == 'myposeo dashboard' ) $this->title = $myposeo_texts['titre_page_config']; 	
	}
	
	function embedMyposeo() {
		//echo 'embedMyposeo()<br/>';
		$this->caller->getMyposeo( $this->mp_url, &$this );
	}
	
	function listMyposeo() {
		//echo 'listMyposeo()<br/>';
		if( $url_id = $_GET['id'] ) {
			$this->dropDown();
			$this->caller->getMyposeo( $url_id, &$this );
			$this->dropDown();
		} else {
			$urls = $this->caller->getUrls( 1000, &$this );
			if( is_array( $urls ) && count( $urls ) > 0 ) {
				echo '<ul class="myposeolist">';
				$count = 0;
				foreach( $urls as $url ) {
					$count ++;
					echo '<li>';
					if( $count <= myposeoPlugin::URLS_IN_MENU ) {
						$href= '?page=tl_myposeo_' . $url['id'];
					} else {
						$href= '?page=tl_myposeo_list&id=' . $url['id'];
					}
					echo '<a href="' . $href . '" style="text-decoration:none">';
					echo $url->location;
					echo '</a>';
					echo '</li>';
				}
				echo '</ul>';
			} else {
				echo '<p>La liste des URLs myposeo est vide.</p>';
			}
		}
	}
	
	function dropDown() {
		if( isset( $_GET['id'] ) ) {
			$myposeo_id = $_GET['id'];
		} else {
			$myposeo_id = $this->caller->getIdfrompageurl();
		}
		$urls = $this->caller->getUrls( 1000, &$this );
		if( is_array( $urls ) && count( $urls ) > 0 ) {
			echo '<form>';
			echo '<select id="myposeoselect" name="id" class="myposeo_drop">';
			foreach( $urls as $url ) {
				echo '<option value="';
				echo $url['id'];
				echo '" ';
				if( $myposeo_id == $url['id'] ) echo ' selected="selected" ';
				echo '>';
				//echo '<a href="?page=tl_myposeo_list&id=' . $url['id'] . '" style="text-decoration:none">';
				echo $url->location;
				//echo '</a>';
				echo '</option>';
			}
			echo '</select>';
			echo '<input id="page" type="hidden" name="page" value="tl_myposeo_list"/>';
			$script = "if(this.form.myposeoselect.selectedIndex<" . ( myposeoPlugin::URLS_IN_MENU ) . ")"
					//. "{alert( 'coucou' ) };return true;";
					//. "{alert(this.form.myposeoselect.options[this.form.myposeoselect.selectedIndex].value)};return true;";
					. "{this.form.page.value='tl_myposeo_'+this.form.myposeoselect.options[this.form.myposeoselect.selectedIndex].value};"
					. "return true;";
			echo '<span class="submit"><input type="submit" value="Changer" onclick="' . $script . '"/></span>';
			echo '</form>';
		} else {
			//echo '<p>La liste des URLs myposeo est vide.</p>';
		}
	}
}
?>