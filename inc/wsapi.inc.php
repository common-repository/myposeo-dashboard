<?php
class wsApi {
		
	const MP_AUTH_URL = 'https://www.myposeo.com/api/login/xml';
	const MP_LIST_URL = 'https://www.myposeo.com/api/urls/xml';
	const MP_HTML_URL = 'https://www.myposeo.com/api/url/format/html';
	const MP_VERIFY_URL = 'https://www.myposeo.com/api/verify/key';
	const HTTP_WRAPPER = 'wp_remote_get'; // wp_remote_get | wp_remote_fopen
	
	public $login 		= '';
	public $password 	= '';
	public $result 		= false;
	public $url 		= '';
	public $msg 		= '';
	public $error 		= false;
	public $xml			= '';
	public $key			= '';
	
	private $debug 		= false;
	private $timeout 	= 10; 	// en secondes
	
	/** Constructor **/
	function wsApi() {
		
	}
	
	/** Singleton **/
	function getInstance() {
		static $obj;
		if( isset( $obj ) === false ) $obj = new wsApi();
		return $obj;
	}
	
	function wsRequest() {

		$url = esc_url( $this->url, null, 'url' );
		
		$url = $this->wsAddtracker( $url );

		if( $this->debug ) $this->msg .= 'Request Url: ' . $url . '<br/>';

		if ( 
			( self::HTTP_WRAPPER == 'wp_remote_fopen' 
				&& !$r = wp_remote_fopen( $url ) 
			)
		|| 
			( self::HTTP_WRAPPER == 'wp_remote_get' 
				&& !( $r = wp_remote_get( $url, array( 'sslverify' => 0 ) ) && is_array( $r ) )
			)
		) {
			$this->error = true;
			$this->msg .= 'Could not perform ' . self::HTTP_WRAPPER . '<br/>';
			return false;
		} else {
			if( self::HTTP_WRAPPER == 'wp_remote_fopen' ) {
				$this->result = $r;
			} else {
				$this->result = $r['body'];
			}
			//var_dump( $r );
			return $this->result;
		}
	}
	
	function wsAddtracker( $url ) {
		if( preg_match( '/\?/', $url ) ) {
			$sep = '&';
		} else {
			$sep = '?';
		}
		$url .= $sep;
		$url .= 'myposeo_client=wordpress';
		$url .= '&wordpress_version=' . urlencode( get_bloginfo( 'version' ) );
		return $url;
	}
	
	function wsGetkey() {
		$qs = 	'?login=' . urlencode( $this->login );
		$qs .= 	'&password=' . urlencode( $this->password );
		$this->url = self::MP_AUTH_URL . $qs;
		return $this->wsRequest();
	}

	function wsCryptgetkey() {
		$qs = 	'?login=' . urlencode( $this->login );
		$qs .= 	'&password=' . urlencode( $this->password );
		$qs .= 	'&out_password_crypted=1';
		$this->url = self::MP_AUTH_URL . $qs;
		return $this->wsRequest();
	}

	function wsGetkeycrypt() {
		$qs = 	'?login=' . urlencode( $this->login );
		$qs .= 	'&password=' . urlencode( $this->password );
		$qs .= 	'&in_password_crypted=1';
		$qs .= 	'&out_password_crypted=1';
		$this->url = self::MP_AUTH_URL . $qs;
		return $this->wsRequest();
	}
	
	function wsVerifykey() {
		$qs = 	'?key=' . $this->key;
		$this->url = self::MP_VERIFY_URL . $qs;
		return $this->wsRequest();
	}
	
	function wsGeturls( $limit ) {
		$qs = 	'?key=' . $this->key;
		$this->url = self::MP_LIST_URL . $qs;
		return $this->wsRequest();
	}
	
	function wsGethtml( $url_id ) {
		$qs = 	'?key=' . $this->key;
		$qs .=	'&url=' . $url_id;
		$this->url = self::MP_HTML_URL . $qs;
		return $this->wsRequest();
	}
	
	function wsParse() {
		if( !$this->result ) {
			$this->xml = false;
			return false;
		}

		if( $this->debug ) $this->msg .= 'Parsing...<br/>';
		
		@$root = simplexml_load_string( $this->result );
		
		if( $root === false ) {
			$this->error = true;
			$this->msg .= 'Could not parse XML stream<br/>';
			$this->xml = false;
			
			if( $this->debug ) {
				$errors = libxml_get_errors();
				foreach( $errors as $error ) {
					$this->msg .= 'Err. ';
					$this->msg .= 'Line: ' . $error->line . ' - ';
					$this->msg .= 'Col: ' . $error->column . ' : ';
					$this->msg .= '(' . $error->code . ') ';
					$this->msg .= $error->message;
					$this->msg .= '<br/>';
				}
			}
			return false;
		} else {
			if( $this->debug ) {
				$mp = $root; //->myposeo;
				$this->msg .= 'Code: ' . $mp->code . '<br/>';
				$this->msg .= 'Key: ' . $mp->key . '<br/>';
				if( is_object( $mp ) ) {
					foreach( $mp->children() as $name => $elt ) {
						$this->msg .= $name . ' &rarr; ' . $elt . '<br/>'; 
					}
				} else {
					$this->msg .= '$mp is not an object.<br/>';
				}
				//$this->msg .= serialize( $mp );
			}
		}
		
		$this->xml = $root;
		
		if( $this->debug && is_object( $root ) ) $this->msg .= $root->asXml();
	}
}
?>