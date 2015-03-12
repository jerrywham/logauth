<?php
/**
 * Plugin logauth
 *
 * @package	PLX
 * @version	1.1
 * @date	23/07/2012
 * @author	Cyril MAGUIRE
 **/
class logauth extends plxPlugin {

	/**
	 * Constructeur de la classe logauth
	 *
	 * @param	default_lang	langue par défaut utilisée par PluXml
	 * @return	null
	 * @author	Stephane F
	 **/
	public function __construct($default_lang) {

		# Appel du constructeur de la classe plxPlugin (obligatoire)
		parent::__construct($default_lang);
		# droits pour accèder à la page admin.php du plugin
		$this->setAdminProfil(PROFIL_ADMIN);
		# Déclarations des hooks
		$this->addHook('AdminAuthPrepend', 'AdminlogauthPrepend');
	}

	/**
	 * Méthode ajoutant une entrée au logfile
	 * @param type $str string Chaine d'informations concernant la connexion/déconnexion
	 * @param type $action string Chaine résumant l'action réalisée
	 * @return file
	 */
	public function addInLogFile($str, $action)  {
		
		$dh = fopen(PLX_PLUGINS."logauth/admin.php","a");
		fputs($dh, "\n".$str."$action\n</pre>\n");
		fclose($dh);
	}

	
	/**
	 * Méthode retournant l'hostname de l'adresse IP 
	 * @param type $ip string
	 * @return $host array
	 */
	public function getHostname($ip)  {
	  $ptr = implode(".",array_reverse(explode(".",$ip))).".in-addr.arpa";
	  $host = dns_get_record($ptr);
	  return $host;
	}

	/**
	 * Méthode permettant d'archiver les logs de connexion
	 * @return type
	 */
	public function archiveLogs() {
		$entete = file_get_contents(PLX_PLUGINS."logauth/admin.php",NULL,NULL,0,1019);
		$archive = file_get_contents(PLX_PLUGINS."logauth/admin.php",NULL,NULL,1019);
		if (!empty($archive)) {
			file_put_contents(PLX_PLUGINS."logauth/history.php", $archive, FILE_APPEND | LOCK_EX);
			file_put_contents(PLX_PLUGINS."logauth/admin.php", $entete);
			$_SESSION['info'] = 'Logs archivés';
			header('location:'.PLX_CORE.'admin/plugin.php?p=logauth');
			exit();
		} else {
			$_SESSION['error'] = 'Aucun log à archiver';
		}
		
	}

	/**
	 * Méthode permettant d'afficher l'historique des logs de connexion/déconnexion
	 * @return file
	 */
	public function showHistory(){
		$entete = file_get_contents(PLX_PLUGINS."logauth/history.php",NULL,NULL,0,262);
		$history = file_get_contents(PLX_PLUGINS."logauth/history.php",NULL,NULL,262);
		if (!empty($history)) {
			echo $entete.$history;
		} else {
			$_SESSION['error'] = "Aucune archive à afficher";
			header('location:'.PLX_CORE.'admin/plugin.php?p=logauth');
			exit();
		}
	}

	public function purgeLogs(){
		$entete = file_get_contents(PLX_PLUGINS."logauth/history.php",NULL,NULL,0,262);
		$history = file_get_contents(PLX_PLUGINS."logauth/history.php",NULL,NULL,262);
		if (!empty($history)) {
			file_put_contents(PLX_PLUGINS."logauth/history.php", $entete);
			$_SESSION['info'] = 'Archives des logs supprimées';
			header('location:'.PLX_CORE.'admin/plugin.php?p=logauth');
			exit();
		} else {
			$_SESSION['error'] = "Aucune archive à supprimer";
			header('location:'.PLX_CORE.'admin/plugin.php?p=logauth');
			exit();
		}
	}

	/**
	 * Méthode qui permet l'enregistrement des logs en modifiant le formulaire de connexion
	 *
	 * @return	stdio
	 * @author	Cyril MAGUIRE
	 **/	
	public function AdminlogauthPrepend() {
		
		$string = '
			// Et enfin on note quelques informations de l\'utilisateur 
			$ip  = htmlspecialchars($_SERVER[\'REMOTE_ADDR\']); // Oui, je suis paranoïaque
			$usr = htmlspecialchars($_SERVER[\'HTTP_USER_AGENT\']);
			$h   = print_r($plxAdmin->plxPlugins->aPlugins["logauth"]->getHostname($ip), true);

			# Initialisation variable erreur
			$error = "";
			$msg = "";

			# Control et filtrage du parametre $_GET[\'p\']
			$redirect=$plxAdmin->aConf[\'racine\'].\'core/admin/\';
			if(!empty($_GET[\'p\'])) {
				$racine = parse_url($plxAdmin->aConf[\'racine\']);
				$get_p = parse_url(urldecode($_GET[\'p\']));
				$error = (!$get_p OR (isset($get_p[\'host\']) AND $racine[\'host\']!=$get_p[\'host\']));
				if(!$error AND !empty($get_p[\'path\']) AND file_exists(PLX_ROOT.\'core/admin/\'.basename($get_p[\'path\']))) {
					# filtrage des parametres de l\'url
					$query=\'\';
					if(isset($get_p[\'query\'])) {
						$query=strtok($get_p[\'query\'],\'=\');
						$query=($query[0]!=\'d\'?\'?\'.$get_p[\'query\']:\'\');
					}
					# url de redirection
					$redirect=$get_p[\'path\'].$query;
				}
			}

			# Déconnexion
			if(!empty($_GET[\'d\']) AND $_GET[\'d\']==1) {

				// Modification ajoutant au logfile les deconnexions du panel admin
  				$plxAdmin->plxPlugins->aPlugins["logauth"]->addInLogFile("<pre>".date("d-m-Y")." @ ".date("H:i")." ".$ip."\n".$usr."\n".$h."\n","Admin disconnect");

				$_SESSION = array();
				session_destroy();
				header(\'Location: auth.php\');
				exit;

				$formtoken = $_SESSION[\'formtoken\']; # sauvegarde du token du formulaire
				$_SESSION = array();
				session_destroy();
				session_start();
				$msg = L_LOGOUT_SUCCESSFUL;
				$_GET[\'p\']=\'\';
				$_SESSION[\'formtoken\']=$formtoken; # restauration du token du formulaire
				unset($formtoken);
			}

			# Authentification
			if(!empty($_POST[\'login\']) AND !empty($_POST[\'password\'])) {
				$connected = false;
				foreach($plxAdmin->aUsers as $userid => $user) {
					if ($_POST[\'login\']==$user[\'login\'] AND sha1($user[\'salt\'].md5($_POST[\'password\']))==$user[\'password\'] AND $user[\'active\'] AND !$user[\'delete\']) {
						$_SESSION[\'user\'] = $userid;
						$_SESSION[\'profil\'] = $user[\'profil\'];
						$_SESSION[\'hash\'] = plxUtils::charAleatoire(10);
						$_SESSION[\'domain\'] = $session_domain;
						$connected = true;
					}
				}
				if($connected) {
					// Modification ajoutant les connexions au log 
				    $_SESSION[\'isConnected\'] = 1;
				    $plxAdmin->plxPlugins->aPlugins["logauth"]->addInLogFile("<pre>".date("d-m-Y")." @ ".date("H:i")." ".$ip."\n".$usr."\n".$h."\n","Login successfull");
					header(\'Location: \'.htmlentities($redirect));
					exit;
				} else {
					$msg = L_ERR_WRONG_PASSWORD;
					$error = \'error\';
					// Ajoute les essais de connexions erronés au logfile 
				    $l = htmlspecialchars($_POST[\'login\']);
				    $pwd = htmlspecialchars($_POST[\'password\']);
				    $plxAdmin->plxPlugins->aPlugins["logauth"]->addInLogFile("<pre class=\"failed\">".date("d-m-Y")." @ ".date("H:i")." ".$ip."\n".$usr."\n".$h."\n","Login failed : $l / $pwd ");
				}
			}
			return true;
		';

		echo '<?php '.$string.' ?>';

	}

}
?>