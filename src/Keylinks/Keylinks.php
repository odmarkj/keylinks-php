<?php
require_once("phpfastcache/phpfastcache.php");
/**
 * Filter the HTML for Keylinks
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Joshua Odmark <support@keylinks.co>
 * @copyright     Copyright 2015, Joshua Odmark <support@keylinks.co>
 * @link          https://github.com/odmarkj/keylinks-api
 * @package       Keylinks
 * @since         0.0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

//namespace Keylinks;

/**
 * Keylinks Class
 *
 * Filtering HTML for Keylinks
 */
class Keylinks{
	
	public static $keylinks = array();
	public static $getLinksApiLink = 'http://keylinks.co/links/get.json';
	public static $storageKey = "keylinks";

    /**
     * Filter the HTML
     *
     * Used when restarting the worker
     *
     * @param  array $arr An array of configuration options outlined below
     *
     * install_id = The installation id of this website as provded by keylinks
     * html = The html to filter
     * url = The url of the webpage on the Internet
     * keylinks = The keylinks for this page as provided by Keylinks
     * cache = An array of config values for automatic caching
     ** type = The type of cache to use, acceptable values are (files|redis)
     ** files_folder = The folder where files can be stored for the files cache method
     ** redis_host = The host for the redis connection
     ** redis_port = The port for the redis connection
     ** redis_database = The database to use for redis
     ** redis_password = The password for the redis connection
     *
     */
    public static function filter($arr = array()){
	    $remote = false;
	    if(array_key_exists('keylinks', $arr) && count($arr['keylinks']) > 0){
		    Keylinks::$keylinks = $keylinks;
	    }elseif(array_key_exists('cache', $arr)){
		    $saveCache = true;
			$keylinks = Keylinks::get($arr, Keylinks::$storageKey);
			if($keylinks != null){
				Keylinks::$keylinks = $keylinks;
				$saveCache = false;
			}else{
				$remote = true;
			}
		}else{
			$remote = true;
		}
		
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTML($arr['html']);
		$xpath = new DOMXPath($dom);
		$keylinks_found = $xpath->query("//keylinks");

		if(count($keylinks_found) > 0){
			if($remote === true){
				Keylinks::$keylinks = Keylinks::getRemoteKeylinks($arr);
		    }
		    if(count(Keylinks::$keylinks) > 0){
				foreach($keylinks_found as $oldNode) {
					foreach(Keylinks::$keylinks as $keylink){
						//if($keylink['Link']['page_id'] == md5($arr['url'])){
							$regex = base64_decode($keylink['Link']['regex']);
							preg_match_all($regex, $oldNode->nodeValue, $matches);
							if(count($matches[0]) > 0){
								$length = strlen(preg_replace("/[^a-zA-Z0-9]+/", "", $regex));
								$diff = strlen($oldNode->nodeValue) / $length;
								if($diff < .6 && $diff > .4){
									//echo $diff."\n";
									//echo $regex."\n".$oldNode->nodeValue."\n\n";
									$oldNode->setAttribute('href', $keylink['Link']['destination_link']);
									$newNode = $oldNode->ownerDocument->createElement('a');
									if ($oldNode->attributes->length) {
									    foreach ($oldNode->attributes as $attribute) {
									        $newNode->setAttribute($attribute->nodeName, $attribute->nodeValue);
									    }
									}
									while ($oldNode->firstChild) {
									    $newNode->appendChild($oldNode->firstChild);
									}
									$oldNode->parentNode->replaceChild($newNode, $oldNode);
									break;
								}
							}
						//}
					}
				}
			}
		}
		
		$arr['html'] = $dom->saveHTML();
		
		$arr['html'] = preg_replace('/\<[\/]{0,1}keylinks[^\>]*\>/i', '', $arr['html']);
		
		return $arr['html'];
		
		$keylinks_found = array();
		$pattern = '%(<keylinks[^>]*>(.*?)</keylinks>)%i';
		//$pattern = '%<keylinks\s+>[^<>]*(<keylinks[^>]*>(?:[^<>]*|(?1))*</keylinks>)[^<>]*</keylinks>%i';
		//$pattern = '/<(\w*\s\w*="\w*"\s\w*="\w*")>/';
		//$pattern = "/<keylinks\s(.+?)>(.+?)<\/keylinks>/is";
		
		try{
		    preg_match_all($pattern, $arr['html'], $regs, PREG_OFFSET_CAPTURE);
		    if(count($regs[0]) > 0){
			    $keylinks_found = $regs;
		    }
		} catch (Exception $e) {
		    // $e->getMessage();
		}
		
		if($remote === true){
			if(count($keylinks_found) > 0){
				Keylinks::$keylinks = Keylinks::getRemoteKeylinks($arr);
		    }
		}
		
		// Replace the links
	    if(count(Keylinks::$keylinks) > 0){
		    $diff = 0;
			foreach($keylinks_found as $index => $kf){
				var_dump($keylinks_found); return;
				foreach(Keylinks::$keylinks as $keylink){
					//if($keylink['Link']['page_id'] == md5($arr['url'])){
						$regex = base64_decode($keylink['Link']['regex']);
						//var_dump($regs[2][$index]);
						preg_match_all($regex, $regs[2][$index], $matches);
						if(count($matches[0]) > 0){
							//$replace = str_ireplace('keylink', 'a', $regs[0][$index][0]);
							$replace = preg_replace($regex, "<a href='".$keylink['Link']['destination_link']."' target='_blank'>".$matches[0][0]."</a>", $regs[2][$index], 1);
							//$replace = str_ireplace('<a ','<a href="'.Keylinks::$keylinks[$index]['Link']['destination_link'].'" ', $replace);
							$arr['html'] = substr_replace($arr['html'], $replace, ($regs[2][$index] - $diff), strlen($regs[0][$index]));
							$diff += (strlen($regs[0][$index]) - strlen($replace));
							break;
						}
					//}
				}
				/*
				$regex = base64_decode($internal_link['Link']['regex']);
				preg_match_all($regex, $content, $matches);
				
				if(count($matches[0]) > 0){
					$content = preg_replace($regex, "<a href='".$internal_link['Link']['destination_link']."' target='_blank'>".$matches[0][0]."</a>", $content, 1);
				}
				*/
				// TODO, create a regex to replace the text ensuring it is replacing HTML tags only
				/*$replace = str_ireplace('keylink', 'a', $regs[0][$index][0]);
				$replace = str_ireplace('<a ','<a href="'.Keylinks::$keylinks[$index]['Link']['destination_link'].'" ', $replace);
				$arr['html'] = substr_replace($arr['html'], $replace, ($regs[0][$index][1] - $diff), strlen($regs[0][$index][0]));
				$diff += (strlen($regs[0][$index][0]) - strlen($replace));*/
			}
		}
		
		if(array_key_exists('cache', $arr) && $saveCache){
			Keylinks::save($arr);
		}
		
        return $arr['html'];
    }
    
    /**
     * Get the keylinks for the website
     *
     * This is used on every page load to replace text with these links on the page
     *
     * @param  array $arr An array of configuration options outlined below
     *
     * install_id = The installation id of this website as provded by keylinks
     * html = The html to filter
     * url = The url of the webpage on the Internet
     * keylinks = The keylinks for this page as provided by Keylinks
     * cache = An array of config values for automatic caching
     ** type = The type of cache to use, acceptable values are (files|redis)
     ** files_folder = The folder where files can be stored for the files cache method
     ** redis_host = The host for the redis connection
     ** redis_port = The port for the redis connection
     ** redis_database = The database to use for redis
     ** redis_password = The password for the redis connection
     *
     */
    private static function getRemoteKeylinks($arr = array()){
	    $keylinks = array();
	    
	    if(function_exists('curl_version')){
		    
		    try{
			    //open connection
				$ch = curl_init();
				
				$arr['html'] = base64_encode($arr['html']);
				/*
				If we have already sent the HTML, don't continue to send it every single day if we can help it
				*/
				if(array_key_exists('cache', $arr)){
					$sentHtml = Keylinks::get( $arr, md5($arr['url']) );
					if($sentHtml != null) {
						// Do not send the HTML to save bandwidth
						$arr['html'] = "";
					}else{
						// Only send the HTML once every 6 months, or when this file is not present
						Keylinks::set($arr, md5($arr['url']), 'true', 15552000);
					}
				}
				
				//set the url, number of POST vars, POST data
				curl_setopt($ch,CURLOPT_URL, Keylinks::$getLinksApiLink);
				curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($arr));
				curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); 
				curl_setopt($ch, CURLOPT_TIMEOUT, 15); //timeout in seconds
				
				//execute post
				$result = curl_exec($ch);
				
				//close connection
				curl_close($ch);
				
				$response = json_decode($result, true);
				
				if($response && count($response['Install']['Internal']) > 0){
					foreach($response['Install']['Internal'] as $r){
						$keylinks[] = $r;
					}
				}
			} catch (Exception $e) {
			    // $e->getMessage();
			}
	    }
	    
	    return $keylinks;
    }
    
    private static function save($config = array()){		
		$set = false;
	    $cache = Keylinks::setupCache($config);
		if($cache !== false){
			try{
			    $set = $cache->set( Keylinks::$storageKey , Keylinks::$keylinks , 86400 );
			} catch (Exception $e) {
	    		// $e->getMessage();
				return false;
			}
		}
		return $set;
    }
    
    private static function set($config, $key = '', $value = '', $expiration = 86400){
	    $set = false;
	    $cache = Keylinks::setupCache($config);
		if($cache !== false){
			try{
			    $set = $cache->set($key, $value, $expiration);
			} catch (Exception $e) {
	    		// $e->getMessage();
				return false;
			}
		}
		return $set;
    }
    
    private static function get($config, $key = ''){
	    $get = false;
	    $cache = Keylinks::setupCache($config);
		if($cache !== false){
			try{
			    $get = $cache->get($key);
			} catch (Exception $e) {
	    		// $e->getMessage();
				return false;
			}
		}
		return $get;
    }
    
    private static function setupCache($config = array()){
	    if($config['cache']['type'] == "files"){
		    if(file_exists($config['cache']['files_folder']) && is_writable($config['cache']['files_folder'])){
			    try{
				    phpFastCache::setup("storage","files");
			    	phpFastCache::setup("path",$config['cache']['files_folder']);
					$cache = phpFastCache();
				} catch (Exception $e) {
		    		// $e->getMessage();
					return false;
				}
			}else{
				try{
					$cache = phpFastCache("files");
				} catch (Exception $e) {
			    	// $e->getMessage();
			    	return false;
				}
			}
		}elseif($config['cache']['type'] == "redis"){
			try{
				phpFastCache::setup("storage","redis");
		    	phpFastCache::setup("redis",array(
			    	"host" => (strlen($config['cache']['redis_host']) > 0 ? $config['cache']['redis_host'] : "127.0.0.1"),
			    	"port" => (strlen($config['cache']['redis_port']) > 0 ? $config['cache']['redis_port'] : ""),
			    	"password" => (strlen($config['cache']['redis_password']) > 0 ? $config['cache']['redis_password'] : ""),
			    	"database" => (strlen($config['cache']['redis_database']) > 0 ? $config['cache']['redis_database'] : ""),
		    	));
				$cache = phpFastCache();
			} catch (Exception $e) {
		    	// $e->getMessage();
		    	return false;
			}
	    }else{
		    try{
				$cache = phpFastCache();
			} catch (Exception $e) {
		    	// $e->getMessage();
		    	return false;
			}
			
			return $cache;
	    }
	    
	    return false;
    }
}

function match_tags($str, $open_tag, $close_tag) {  
     
    $open_length = strlen($open_tag); 
    $close_length = strlen($close_tag); 
    $stack  = array();  
    $result = array();  
    $pos = -1;  
    $end = strlen($str) + 1;  

    while(TRUE){  
        $p1 = strpos($str, $open_tag, $pos + 1);  
        $p2 = strpos($str, $close_tag, $pos + 1);  
        $pos = min(($p1 === FALSE) ? $end : $p1, ($p2 === FALSE) ? $end : $p2);  
        
        if($pos == $end){  
            break;  
        } 
        if(substr($str, $pos, $open_length) == $open_tag){  
            array_push($stack, $pos);  
        } 
        else{ 
            if(substr($str, $pos, $close_length) == $close_tag){  
                if(!count($stack)){ 
                    user_error('Odd closebrace at offset '.$pos);  
                } 
                else{  
                    $result[array_pop($stack)] = $pos;  
                } 
            } 
        }  
    }  
    if(count($stack)){  
        user_error('odd openbrace at offset '.array_pop($stack));  
    } 
    ksort($result);  
    return $result;  
}

function print_matches($str, $matches, $open_tag, $close_tag) {  
        
    $open_length = strlen($open_tag); 
    $close_length = strlen($close_tag); 
    $open_tags = array(); 
    $close_tags = array(); 
    $attrs = array();  
    foreach($matches as $start => $end){  
        $attrs[$start] = $attrs[$end] = sprintf('#%06x', rand(0, 0xFFEEDD));  
        $open_tags[$start] = $start + $open_length; 
        $close_tags[$end] = $end + $close_length; 
    } 
    for($i=0, $str_length=strlen($str); $i<$str_length; $i++){  
        if(in_array($i, array_keys($open_tags)) || in_array($i, $open_tags)){ 
            if($open_tags[$i] == ($i + $open_length)){ 
                echo '<b style="color:white; background:'.$attrs[$i].'">'; 
            } 
            else{ 
                echo '</b>'; 
            } 
            echo htmlentities($str{$i}); 
        } 
        elseif(in_array($i, array_keys($close_tags)) || in_array($i, $close_tags)){ 
            if($close_tags[$i] == ($i + $close_length)){ 
                echo '<b style="color:white; background:'.$attrs[$i].'">'; 
            } 
            else{ 
                echo '</b>'; 
            } 
            echo htmlentities($str{$i}); 
        } 
        else{ 
            echo htmlentities($str{$i}); 
        } 
    } 
}
?>