<?php
// Version
define('VERSION', '1.0.8');

// Configuration
require_once('../admin/config.php');
//require_once(DIR_CONFIG . 'config_tuning.php');

// Startup
require_once(DIR_SYSTEM . 'startup.php');

// Application Classes
require_once(DIR_SYSTEM . 'library/currency.php');
require_once(DIR_SYSTEM . 'library/user.php');
require_once(DIR_SYSTEM . 'library/weight.php');
require_once(DIR_SYSTEM . 'library/length.php');

// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Config
$config = new Config();
$registry->set('config', $config);

// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

// Settings
$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting");
 
foreach ($query->rows as $setting) {
	$config->set($setting['key'], $setting['value']);
}

// Log 
$log = new Log($config->get('config_error_filename'));
$registry->set('log', $log);

// Error Handler
function error_handler($errno, $errstr, $errfile, $errline) {
	global $config, $log;

	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$error = 'Notice';
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$error = 'Warning';
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$error = 'Fatal Error';
			break;
		default:
			$error = 'Unknown';
			break;
	}

	if ($config->get('config_error_display')) {
		echo '<b>' . $error . '</b>: ' . $errstr . ' in <b>' . $errfile . '</b> on line <b>' . $errline . '</b>';
	}
	
	if ($config->get('config_error_log')) {
		$log->write('PHP ' . $error . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
	}

	return TRUE;
}

// Error Handler
set_error_handler('error_handler');

// Request
$request = new Request();
$registry->set('request', $request);

// Response
$response = new Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');
$registry->set('response', $response); 

// Session
$registry->set('session', new Session());

// Cache
$registry->set('cache', new Cache());

// Document
$registry->set('document', new Document());

// Language
$languages = array();

$query = $db->query("SELECT * FROM " . DB_PREFIX . "language"); 

foreach ($query->rows as $result) {
	$languages[$result['code']] = array(
		'language_id' => $result['language_id'],
		'name'        => $result['name'],
		'code'        => $result['code'],
		'locale'      => $result['locale'],
		'directory'   => $result['directory'],
		'filename'    => $result['filename']
	);
}

$config->set('config_language_id', $languages[$config->get('config_admin_language')]['language_id']);

$language = new Language($languages[$config->get('config_admin_language')]['directory']);
$language->load($languages[$config->get('config_admin_language')]['filename']);	
$registry->set('language', $language);

// Currency
$registry->set('currency', new Currency($registry));

// Weight
$registry->set('weight', new Weight($registry));

// Length
$registry->set('length', new Length($registry));

// User
$registry->set('user', new User($registry));

// Front Controller
$controller = new Front($registry);

$headers = apache_request_headers();

// Router
if (isset($request->get['mode'])) {

	if( $request->get['mode'] == 'checkauth') {
	
		$action = new Action('module/exchange1c/modeCheckauth');
		
	}else{
		if (isset($headers['Cookie']) && $headers['Cookie'] == session_name()."=".session_id()){
			if( $request->get['mode'] == 'init') {
	
				$action = new Action('module/exchange1c/modeInit');
		
			} elseif( $request->get['mode'] == 'file') {
	
				$action = new Action('module/exchange1c/modeFile');
		
			} elseif( $request->get['mode'] == 'import') {
	
				$action = new Action('module/exchange1c/modeImport');
		
			} elseif( $request->get['mode'] == 'query') {
		
				$action = new Action('module/exchange1c/modeQuery');
			
			} else {
				echo "failure.\n";
				echo "Bad command";
				exit;
			}
		} else {
			echo "failure.\n";
			echo "Bad authorization.";
			exit;
		}
	}

} else {
	echo "Success\n";
	exit;
}

// Dispatch
$controller->dispatch($action, new Action('error/not_found'));

// Output
$response->output();
?>