<?php
 
/**
 * MediaWiki KittenAuth extension
 * Add a confirmation form to certain pages where users have to pick
 *  an image of a cat out of a list of images to prove they're not bots
 * @version 1.0
 * @author Litso (Main author)
 */
 
$wgExtensionCredits['other'][] = array(
                'name' => 'KittenAuth',
                'author' => 'Litso',
                'descriptionmsg' => 'kittenauth-desc',
                'url' => 'http://www.mediawiki.org/wiki/Extension:KittenAuth',
                'version' => '0.9'
                );
$wgExtensionMessagesFiles['KittenAuth'] = dirname( __FILE__ )."/KittenAuth.i18n.php";
KittenAuth::setup();


class KittenAuth {
 
	private static $instance = null;
	
	private $trigger = '';	
	private $action = '';
  	private $notkittens = array();	
  	private $badLoginAttempts = 0;


	
	/*** the following variables can be set/overridden in LocalSettings.php ***/
	
	/**
	 * An array of images that are kittens (filenames only, e.g. array('1.gif', 'pic.jpg', 'image.gif') )
	 * MUST be set to at least one image to properly function, else a broken image will appear
	 * 
	 * The plugin comes with default images but bots may pick up on these quickly, so it is advised to 
	 * change these on installation
	 */
	public $kittens = array('2.png', '5.png', '7.png', '11.png', '14.png', '19.png', '22.png', '25.png');	
	
	//default is the plugin's 'images' folder as set in the __construct() function
	//can be overwritten though
	public $kittenDir;
	
	//allow users with confirmed email to skip kittenauth 
	public $skipConfirmedEmail = false;
	
	//max bad attempts to login before triggering kittenauth
	//@TODO: make this work
	public $maxBadLoginAttempts = 3; 
	
	//external urls that don't need to be kittenauth'd
	public $CaptchaWhitelist = false;
	
	//IP's that don't have to be kittenauth'd
	public $IPWhitelist = false;
	
	//run this function from LocalSettings with an array to set skipped usergroups 
	//example: KittenAuth::setSkippedUserGroups(array( 'user' => true, 'sysop' => false) );
	public function setSkippedUserGroups($settings) {
		global $wgGroupPermissions;
		
		if(is_array($settings)) {
			foreach($settings as $usergroup => $skip)
				$wgGroupPermissions[$usergroup]['skipkittens'] = $skip;
		}
	}
	
	//run this function from LocalSettings with an array to set kittenauth triggers
	//example: KittenAuth::setKittenTriggers(array( 'edit' => false, 'addurl' => true) );
	public function setKittenTriggers($settings) {
		global $wgKittenTriggers;
		
		if(is_array($settings)) {
			foreach($settings as $trigger => $value)
				$wgKittenTriggers[$trigger] = $value;
		}
	}	
	
	
	
	
	
	
	/**
     * Initialise the KittenAuth plugin.
     * @return KittenAuth the plugin object, which you can use to set settings.
     */
    public static function setup() {
 
        // check for wiki
        if (!defined('MEDIAWIKI')) {
                throw new Exception('This is an extension to the MediaWiki software and cannot be used standalone.');
        }
 
        // create plugin
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
 
    }
    

    public function __construct() {
    	
    	$this->kittenDir = dirname(__FILE__);
		

    	/***************
         * Permissions *
         ***************/ 

		global $wgGroupPermissions, $wgKittenTriggers;

		// default usergroups that don't have to pick a kitten 
		$wgGroupPermissions['*'            ]['skipkittens'] = false;
		$wgGroupPermissions['user'         ]['skipkittens'] = true;
		$wgGroupPermissions['autoconfirmed']['skipkittens'] = true;
		$wgGroupPermissions['bot'          ]['skipkittens'] = true; // registered bots
		$wgGroupPermissions['sysop'        ]['skipkittens'] = true;
		$wgAvailableRights[] = 'skipkittens';

		// default actions that require kittenauth to run 
		$wgKittenTriggers = array();
		$wgKittenTriggers['edit']          = false; 	// Would check on every edit
		$wgKittenTriggers['create']		   = true;		// Check on page creation.
		$wgKittenTriggers['addurl']        = true;  	// Check on edits that add URLs, even if 'edit' trigger is set to false
		$wgKittenTriggers['createaccount'] = true;  	// Special:Userlogin&type=signup
		$wgKittenTriggers['badlogin']      = false;  	// Special:Userlogin after failure @TODO: make this work



        /*********
         * Add hooks *
         *********/
 
        global $wgHooks;

		if ( defined( 'MW_SUPPORTS_EDITFILTERMERGED' ) ) {
			$wgHooks['EditFilterMerged'][] = $this;
		} else {
			$wgHooks['EditFilter'][] = $this;
		}

		$wgHooks['UserCreateForm'][] = $this;
		$wgHooks['AbortNewAccount'][] = $this;
		$wgHooks['LoginAuthenticateAudit'][] = $this;
		$wgHooks['UserLoginForm'][] = $this;
		$wgHooks['AbortLogin'][] = $this;

    }

    
    
 
    /*********
     * Hooks *
     *********/


	/**
	 * The main callback run on edit attempts.
	 * @param EditPage $editPage
	 * @param string $newtext
	 * @param string $section
	 * @param bool $merged
	 * @return bool true to continue saving, false to abort and show KittenAuth
	 */
	public function onEditFilter( $editPage, $newtext, $section, $merged = false ) { 
		
		wfDebug("KittenAuth: onEditFilter hook activated (merged = $merged)  \n");
		
		if ( defined( 'MW_API' ) ) { wfDebug("MW_API true \n");
			# API mode
			# The Kitten was already checked and approved
			return true;
		}
		if ( !$this->doConfirmEdit( $editPage, $newtext, $section, $merged ) ) { wfDebug("doConfirmEdit false \n");
			$editPage->showEditForm( array( &$this, 'editCallback' ) );
			return false;
		}
		return true;
	}
 
	/**
	 * A more efficient edit filter callback based on the text after section merging
	 * @param EditPage $editPage
	 * @param string $newtext
	 */
	public function onEditFilterMerged( $editPage, $newtext, $section, $merged = false ) {
		return $this->onEditFilter( $editPage, $newtext, false, true );
	}
	
 
	/**
	 * Inject kittenauth in user create formn
	 * @fixme if multiple thingies insert a header, could break
	 * @param SimpleTemplate $template
	 * @return bool true to keep running callbacks
	 */
	public function onUserCreateForm( &$template ) {
		
		wfDebug("KittenAuth: onUserCreateForm hook activated  \n");
		
		global $wgKittenTriggers, $wgOut, $wgUser;
		if ( $wgKittenTriggers['createaccount'] ) {
			if ( $wgUser->isAllowed( 'skipkittens' ) ) {
				wfDebug( "KittenAuth: user group allows skipping kittenauth on account creation \n" );
				return true;
			}
			$this->addCSS(&$wgOut);
			$template->set( 'header',
				"<div class='kittens'>" .
				$wgOut->parse( $this->getMessage( 'createaccount' ) ) .
				$this->getForm() .
				"</div> \n" );
		}
		return true;
	}	

	/**
	 * Hook for user creation form submissions.
	 * @param User $u
	 * @param string $message
	 * @return bool true to continue, false to abort user creation
	 */
	function onAbortNewAccount( $u, &$message ) {
		global $wgKittenTriggers, $wgUser;
		if ( $wgKittenTriggers['createaccount'] ) {
			if ( $wgUser->isAllowed( 'skipkittens' ) ) {
				wfDebug( "KittenAuth: user group allows skipping captcha on account creation \n" );
				return true;
			}
			if ( $this->isIPWhitelisted() )
				return true;

			$this->trigger = "new account '" . $u->getName() . "'";
			if ( !$this->passKittenAuth() ) {
				$message = wfMsg( 'kittenauth-createaccount-fail' );
				return false;
			}
		}
		return true;
	}	

	
	/**
	 * Inject a KittenAuth into the user login form after a failed
	 * password attempt as a speedbump for mass attacks.
	 * @fixme if multiple thingies insert a header, could break
	 * @param SimpleTemplate $template
	 * @return bool true to keep running callbacks
	 */
	function onUserLoginForm( &$template ) {
		
		wfDebug("KittenAuth: onUserLoginForm hook activated  \n");
		
		if ( $this->isBadLoginTriggered()) {
			global $wgOut;
			$this->addCSS(&$wgOut);
			$template->set( 'header',
				"<div class='kittens'>" .
				$wgOut->parse( $this->getMessage( 'badlogin' ) ) .
				$this->getForm() .
				"</div> \n" );
		}
		return true;
	}

	
	
	/**
	 * Hook for user login form submissions.
	 * @param User $u
	 * @param string $message
	 * @return bool true to continue, false to abort user creation
	 */
	function onAbortLogin( $u, $pass, &$retval ) {
		if ( $this->isBadLoginTriggered() ) {
			if ( $this->isIPWhitelisted() )
				return true;

			$this->trigger = "post-badlogin login '" . $u->getName() . "'";
			if ( !$this->passKittenAuth() ) {
				$message = wfMsg( 'kittenauth-badlogin-fail' );
				// Emulate a bad-password return to confuse the shit out of attackers
				$retval = LoginForm::WRONG_PASS;
				return false;
			}
		}
		return true;
	}
	
	/**
	 * When a bad login attempt is made, increment an expiring counter
	 * in the memcache cloud. Later checks for this may trigger a
	 * captcha display to prevent too many hits from the same place.
	 * @param User $user
	 * @param string $password
	 * @param int $retval authentication return value
	 * @return bool true to keep running callbacks
	 */
	function onLoginAuthenticateAudit( $user, $password, $retval ) {
		
		global $wgKittenTriggers, $wgMemc;
		if ( $retval == LoginForm::WRONG_PASS && $wgKittenTriggers['badlogin'] ) {

			$key = wfMemcKey( 'kittenauth', 'badlogin', 'ip', wfGetIP() );
			$count = $wgMemc->get( $key );
			if ( !$count ) {
				$wgMemc->add( $key, 0, 5*60 );
			}
			$count = $wgMemc->incr( $key );
		}
		return true;
	}

 

 
    /*************
     * Functions *
     *************/

	private function getNotKittens() {

		$folder = opendir($this->kittenDir . "/images"); // Use 'opendir(".")' if the PHP file is in the same folder as your images. Or set a relative path 'opendir("../path/to/folder")'.
		$pic_types = array("jpg", "jpeg", "gif", "png");
		$notkittens = array();
 
		while ($file = readdir ($folder)) {
		  if(in_array(substr(strtolower($file), strrpos($file,".") + 1),$pic_types) && !in_array($file, $this->kittens))
			{
				array_push($notkittens,$file);
			}
		}
		 
		closedir($folder);	
		return $notkittens;
	}
 


	/**
	 * Backend function for onEditFilter() and onEditFilterMerged()
	 * @return bool false if the CAPTCHA is rejected, true otherwise
	 */
	private function doConfirmEdit( $editPage, $newtext, $section, $merged = false ) {

		if ( $this->shouldCheck( $editPage, $newtext, $section, $merged ) ) {

			return $this->passKittenAuth();

		} else {
			wfDebug( "KittenAuth: no need to show captcha. \n" );
			return true;
		}
	}


	/**
	 * @param EditPage $editPage
	 * @param string $newtext
	 * @param string $section
	 * @return bool true if the captcha should run
	 */
	function shouldCheck( &$editPage, $newtext, $section, $merged = false ) {
		
		wfDebug("KittenAuth: function shouldCheck() started  \n");
		
		global $wgKittenTriggers;
				
		$this->trigger = '';
		$title = $editPage->mArticle->getTitle();

		global $wgUser;
		if ( $wgUser->isAllowed( 'skipkittens' ) ) {
			wfDebug( "KittenAuth: user group allows skipping captcha \n" );
			return false;
		}
		if ( $this->isIPWhitelisted() )
			return false;


		global $wgEmailAuthentication;
		if ( $wgEmailAuthentication && $this->skipConfirmedEmail && $wgUser->isEmailConfirmed() ) {
			wfDebug( "KittenAuth: user has confirmed mail, skipping captcha \n" );
			return false;
		}

		if ( $wgKittenTriggers['edit'] ) {
			// Check on all edits
			global $wgUser;
			$this->trigger = sprintf( "edit trigger by '%s' at [[%s]]",
				$wgUser->getName(),
				$title->getPrefixedText() );
			$this->action = 'edit';
			wfDebug( "KittenAuth: checking all edits... \n" );
			return true;
		}

		if ( $wgKittenTriggers['create']  && !$editPage->mTitle->exists() ) {
			// Check if creating a page
			global $wgUser;
			$this->trigger = sprintf( "Create trigger by '%s' at [[%s]]",
				$wgUser->getName(),
				$title->getPrefixedText() );
			$this->action = 'create';
			wfDebug( "KittenAuth: checking on page creation... \n" );
			return true;
		}

		if ( $wgKittenTriggers['addurl'] ) {
			wfDebug("KittenAuth: checking for added urls \n");
			// Only check edits that add URLs
			if ( $merged ) {
				wfDebug("KittenAuth: \$merged = true \n");
				// Get links from the database
				$oldLinks = $this->getLinksFromTracker( $title );
				
				// Share a parse operation with Article::doEdit()
				$editInfo = $editPage->mArticle->prepareTextForEdit( $newtext );
				$newLinks = array_keys( $editInfo->output->getExternalLinks() );
			} else {
				wfDebug("KittenAuth: \$merged = false \n");
				// Get link changes in the slowest way known to man
				$oldtext = $this->loadText( $editPage, $section );
				$oldLinks = $this->findLinks( $editPage, $oldtext );
				$newLinks = $this->findLinks( $editPage, $newtext );
			}

			$unknownLinks = array_filter( $newLinks, array( &$this, 'filterLink' ) );
			$addedLinks = array_diff( $unknownLinks, $oldLinks );
			$numLinks = count( $addedLinks );

			if ( $numLinks > 0 ) {
				wfDebug("KittenAuth: urls were added \n");
				global $wgUser;
				$this->trigger = sprintf( "%dx url trigger by '%s' at [[%s]]: %s",
					$numLinks,
					$wgUser->getName(),
					$title->getPrefixedText(),
					implode( ", ", $addedLinks ) );
				$this->action = 'addurl';
				return true;
			}
		}

		return false;
	}

	
	
	/***** KittenAuth verification functions *****/
	
	
	/**
	 * Given a required captcha run, test form input for correct
	 * input on the open session.
	 * @return bool if passed, false if failed or new session
	 */
	function passKittenAuth() {
		
		$token = $this->getToken();
		
		if(isset($_POST['kitten']) && isset($_POST['kAuth']))	{
			
			$kitten = $_POST['kitten'];
			$kAuth = $_POST['kAuth'];
			
			//check if the right image was selected
			if( md5($kitten . $token) == $kAuth) {
				wfDebug("Passed kitten validation! \n");
				return true;	
			}
		}
			
		wfDebug("Failed kitten validation \n");
		return false;
	}
	
	
	/**
	 * Get a token to hash the kitten key with
	 * Make it harder for bots to figure out the right answer
	 * 
	 * @return bool
	 * @access private
	 */	
	private function getToken() {
		global $wgArticle, $wgTitle;
		
		if(isset($wgArticle->mTitle->mArticleID))
			return $wgArticle->mTitle->mArticleID;
		else {
			return $wgTitle->mUrlform;
		}
	}
		
	/**
	 * Check if the IP is allowed to skip captchas
	 */
	function isIPWhitelisted() {
		if ( $this->IPWhitelist ) {
			$ip = wfGetIp();
			foreach ( $this->IPWhitelist as $range ) {
				if ( IP::isInRange( $ip, $range ) ) {
					return true;
				}
			}
		}
		return false;
	}

	
	
	
	
	/***** KittenAuth form insertion functions *****/
	

	/**
	 * Insert the captcha prompt into an edit form.
	 * @param OutputPage $out
	 */
	function editCallback( &$out ) {
		$out->addWikiText( $this->getMessage( $this->action ) );
		$out->addHTML( $this->getForm() );
		$this->addCSS(&$out);
	}

	/**
	 * Show a message asking the user to enter a captcha on edit
	 * The result will be treated as wiki text
	 *
	 * @param $action Action being performed
	 * @return string
	 */
	function getMessage( $action ) {
		$name = 'kittenauth-' . $action;
		$text = wfMsg( $name );

		if(empty($this->kittens)) { 
			$text .= "<br />'''" . wfMsg( 'kittenauth-nokittens' ) . "'''";
		}
		
		# Obtain a more tailored message, if possible, otherwise, fall back to
		# the default for edits
		return wfEmptyMsg( $name, $text ) ? wfMsg( 'kittenauth-edit' ) : $text;
	}

	/**
	 * Insert a captcha prompt into the edit form.
	 * This sample implementation generates a simple arithmetic operation;
	 * it would be easy to defeat by machine.
	 *
	 * Override this!
	 *
	 * @return string HTML
	 */
	function getForm() {
		
		global $wgScriptPath;
		$token = $this->getToken();
		$this->notkittens = $this->getNotKittens();
		
		//get 1 kitten
		$randomKitten = $this->kittens[rand(1,count($this->kittens)) - 1];
		
		//get non-kitten images
		$randomImages = array();
		$rand = array_rand($this->notkittens, 4); //4 random keys

		foreach($rand as $key)  //get the values
			$randomImages[] = $this->notkittens[$key];
		
		//combine and shuffle the images to display a random list later
		$combined = $randomImages;
		$combined[] = $randomKitten;
		shuffle($combined);
		
		//make make into form
		$out = '<div id="KittenAuth">';
		foreach($combined as $image) {
			
			$out .= "
			<label>
				<img src='$wgScriptPath/extensions/KittenAuth/images/$image' height='50' width='50' />
				<input name='kitten' type='radio' value='$image' tabindex='1' />
			</label>";
		}
		//add hidden element with md5 hash
		$out .=	Xml::element(
				'input', array(
					'type' 	 => 'hidden',
					'name' 	 => 'kAuth',
					 'id'  	 => 'kAuth',
					 'value' => md5($randomKitten . $token)
				) );			
		$out .= "</div>";
				
		return $out;
		
	}	

	private function addCSS(&$out) {
		$out->addScript( '
			<style type="text/css">
				/* clear the float without adding an extra div */
				#KittenAuth:after {
				  content: "\0020";
				  display: block;
				  height: 0;
				  clear: both;
				  visibility: hidden;
				  overflow:hidden;
				}
				#KittenAuth label {
					float: left;
					margin-bottom: .5em;
				}
				#KittenAuth img {
					display: block;
					cursor: pointer;
					margin: 3px;
				}
				#KittenAuth input[type=radio] {
					width: 50px;
				}
			</style>' );
	}

	
	
	
	/***** External URL functions *****/
	
	
	/**
	 * Load external links from the externallinks table
	 */
	function getLinksFromTracker( $title ) {
		$dbr =& wfGetDB( DB_SLAVE );
		$id = $title->getArticleId(); // should be zero queries
		$res = $dbr->select( 'externallinks', array( 'el_to' ),
			array( 'el_from' => $id ), __METHOD__ );
		$links = array();
		while ( $row = $dbr->fetchObject( $res ) ) {
			$links[] = $row->el_to;
		}
		return $links;
	}

	/**
	 * Retrieve the current version of the page or section being edited...
	 * @param EditPage $editPage
	 * @param string $section
	 * @return string
	 * @access private
	 */
	function loadText( $editPage, $section ) {
		$rev = Revision::newFromTitle( $editPage->mTitle );
		if ( is_null( $rev ) ) {
			return "";
		} else {
			$text = $rev->getText();
			if ( $section != '' ) {
				return Article::getSection( $text, $section );
			} else {
				return $text;
			}
		}
	}
	
	/**
	 * Extract a list of all recognized HTTP links in the text.
	 * @param string $text
	 * @return array of strings
	 */
	function findLinks( &$editpage, $text ) {
		global $wgParser, $wgUser;

		$options = new ParserOptions();
		$text = $wgParser->preSaveTransform( $text, $editpage->mTitle, $wgUser, $options );
		$out = $wgParser->parse( $text, $editpage->mTitle, $options );

		return array_keys( $out->getExternalLinks() );
	}

	/**
	 * Filter callback function for URL whitelisting
	 * @param string url to check
	 * @return bool true if unknown, false if whitelisted
	 * @access private
	 */
	function filterLink( $url ) {
		$source = wfMsgForContent( 'captcha-addurl-whitelist' );

		$whitelist = wfEmptyMsg( 'captcha-addurl-whitelist', $source )
			? false
			: $this->buildRegexes( explode( "\n", $source ) );

		$cwl = $this->CaptchaWhitelist !== false ? preg_match( $this->CaptchaWhitelist, $url ) : false;
		$wl  = $whitelist          !== false ? preg_match( $whitelist, $url )          : false;

		return !( $cwl || $wl );
	}
	
	/**
	 * Build regex from whitelist
	 * @param string lines from [[MediaWiki:Captcha-addurl-whitelist]]
	 * @return string Regex or bool false if whitelist is empty
	 * @access private
	 */
	function buildRegexes( $lines ) {
		# Code duplicated from the SpamBlacklist extension (r19197)

		# Strip comments and whitespace, then remove blanks
		$lines = array_filter( array_map( 'trim', preg_replace( '/#.*$/', '', $lines ) ) );

		# No lines, don't make a regex which will match everything
		if ( count( $lines ) == 0 ) {
			wfDebug( "No lines\n" );
			return false;
		} else {
			# Make regex
			# It's faster using the S modifier even though it will usually only be run once
			// $regex = 'http://+[a-z0-9_\-.]*(' . implode( '|', $lines ) . ')';
			// return '/' . str_replace( '/', '\/', preg_replace('|\\\*/|', '/', $regex) ) . '/Si';
			$regexes = '';
			$regexStart = '/^https?:\/\/+[a-z0-9_\-.]*(';
			$regexEnd = ')/Si';
			$regexMax = 4096;
			$build = false;
			foreach ( $lines as $line ) {
				// FIXME: not very robust size check, but should work. :)
				if ( $build === false ) {
					$build = $line;
				} elseif ( strlen( $build ) + strlen( $line ) > $regexMax ) {
					$regexes .= $regexStart .
						str_replace( '/', '\/', preg_replace( '|\\\*/|', '/', $build ) ) .
						$regexEnd;
					$build = $line;
				} else {
					$build .= '|' . $line;
				}
			}
			if ( $build !== false ) {
				$regexes .= $regexStart .
					str_replace( '/', '\/', preg_replace( '|\\\*/|', '/', $build ) ) .
					$regexEnd;
			}
			return $regexes;
		}
	}

	
	
	/***** Login functions *****/
	
	
	/**
	 * Check if a bad login has already been registered for this
	 * IP address. If so, require a kitten.
	 * @return bool
	 * @access private
	 */
	function isBadLoginTriggered() {
		
		global $wgMemc;
		return intval( $wgMemc->get( wfMemcKey( 'kittenauth', 'badlogin', 'ip', wfGetIP() ) ) ) >= $this->maxBadLoginAttempts;
	}


}