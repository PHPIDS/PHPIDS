<?php

require_once ('Factory/Interface.php') ;

/**
 *
 */
class IDS_Caching {

	public static function createCaching($config, $type) {

		switch(strtolower($config['caching'])) {
			
			case 'session':
				require_once 'Session.php';
				$caching = IDS_Caching_Session::getInstance($type, $config);
				break;
				
            case 'file':
                require_once 'File.php';
                $caching = IDS_Caching_File::getInstance($type, $config);
                break;

            case 'database':
                require_once 'Database.php';
                $caching = IDS_Caching_Database::getInstance($type, $config);
                break;

            case 'memcached':
                require_once 'Memcached.php';
                $caching = IDS_Caching_Memcached::getInstance($type, $config);
                break;  
            
            default:
            	$caching = false;                 
		}
		
		return $caching;
	}
}

/**
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */