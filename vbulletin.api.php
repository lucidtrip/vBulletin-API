<?php

/*******************************************************************************

VBulletin API

autor:          bebop
last update:    03.12.2013

beispiele:
$vb = new VB;
$vb->login(URL, USER, PASS);
$vb->new_thread(ID, TITEL, TEXT);
$vb->postreply(ID, TITEL, TEXT);


have fun!

bitcoin:    1H7YZLC1TzeA6qQVFHLtMMmDx8GsxLucid

*******************************************************************************/

/***
  
***/
function debugger($code)
{
    $handler = fopen("vbulletin_error_log.html", "w+");
    fwrite($handler, $code);
    fclose($handler);
}

function grab_page($url, $ref_url = false, $data = false, $login = false){
	  $ch = curl_init();
	  $cookie=parse_url($url, PHP_URL_HOST).".cookie";
	  if( $login ) {
	      $handler=fopen($cookie, "w+");
	      fclose($handler);
	  }
	  // if you need a proxy than change this!
	  if (false) {
		  curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
		  curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
		  curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1:9150");
	  }
	  curl_setopt($ch, CURLOPT_URL, $url);
	  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
	  curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0.2) Gecko/20100101 Firefox/10.0.2");
	  curl_setopt($ch, CURLOPT_TIMEOUT, 40);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	  curl_setopt($ch, CURLOPT_HEADER, TRUE);
	  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	  curl_setopt($ch, CURLOPT_POST, TRUE);
	  if( $ref_url ) curl_setopt($ch, CURLOPT_REFERER, $ref_url);
	  if( $data ) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	  //ob_start();
	  $exec = curl_exec ($ch); // execute the curl command
	  $info = curl_getinfo($ch);
	  //ob_end_clean();
	  curl_close ($ch);
	  unset($ch);
	  return array( $exec, $info );
}

class vbulletin
{
    var $forum;
    var $user;
    var $pass;
    var $host;
    var $cookie;
    
    function login ( $forum, $user, $pass, $erkennung = "userid")
    {
        $forum = rtrim($forum, '/') . '/';
        $this->forum = $forum;
        $this->user = $user;
        $this->pass = md5($pass);
        $this->host = parse_url($forum, PHP_URL_HOST);
        
        $post = array(
                        "do" => "login",
                        "url" => "/index.php",
                        "vb_login_md5password" => $this->pass,
                        "vb_login_username" => $this->user,
                        "cookieuser" => 1
                     );
        $f_login = "login.php?do=login";
        $login = grab_page( $this->forum.$f_login, $this->forum, $post, true );
        if(strpos($erkennung, $login[0])) {
            return array("code" => true);
        }
        if( strpos($login[0], "CloudFlare") ) {
            return array("code" => false, "text" => "CloudFlare");
        }
        else {
            debugger($login[0]);
            return array("code" => false, "text" => "Login fehlgeschlagen (".$this->host.")");
        }
    }
    
    function new_thread($id, $titel, $text, $prefixid = "", $iconid = "0", $signature = "1", $parseurl = "1")
    {
        $newthread = grab_page( $this->forum."newthread.php?do=newthread&f=".$id, $this->forum );
        //print_r($newthread);
        if(preg_match('#SECURITYTOKEN = "(.*)";#sU',$newthread[0] , $token))
        {
            $post = array("subject" => utf8_decode($titel),
                          "message" => utf8_decode($text),
                          "wysiwyg" => "0",
                          "iconid" => $iconid,
                          "prefixid" => $prefixid,
                          "s" => "",
                          "securitytoken" => trim($token[1]),
                           "f" => $id,
                          "do" => "postthread",
                          //"sbutton" => "Thema+erstellen",
                          "signature" => $signature,
                          //"posthash" => "721d44e23d741066312f868e700b0db6",
                          "parseurl" => $parseurl
                          );
            
            $posted = grab_page($this->forum."newthread.php?do=postthread&f=".$id, $this->forum."newthread.php?do=newthread&f=".$id, $post );
            if(preg_match("#Location: (.*)\n#iU", $posted[0], $location))
            {
                return array("code" => "true", "text" => trim($location[1]));
            }
            else
            {
                if(preg_match("#This forum requires that you wait [\d]+ seconds between posts\. Please try again in [\d]+ seconds\.#i", $posted[0], $timerror))
                {
                    return array("code" => false, "text" => $timerror[0]);
                }
                elseif(preg_match("#This thread is a duplicate of a thread that you have posted in the last five minutes. You will be redirected to the thread listings.#i", $posted[0], $duperr))
                {
                    return array("code" => false, "text" => $duperr[0]);
                }
                elseif(preg_match("#Invalid Forum specified\. If you followed a valid link\, please notify the \<a href\=\".*\">administrator\<\/a\>#i", $posted[0], $inverr))
                {
                    return array("code" => false, "text" => strip_tags($inverr[0]));
                }
                elseif(preg_match("#Themenpräfix#i", $posted[0], $inverr))
                {
                    return array("code" => false, "text" => "Es muss ein Themenpräfix ausgewählt werden.");
                }
                elseif(preg_match("nur alle [\d]+ Sekunden einen Beitrag erstellen#i", $posted[0], $inverr))
                {
                    return array("code" => false, "text" => "Zeitsprerre!");
                }
                debugger($posted[0]);
                return array("code" => false, "text" => "Fehler beim erstellen des Threads (".$this->host.")");
            }
        }
        else{
            debugger($newthread[0]);
            return array("code" => false, "text" => "Securitytoken nicht gefunden (".$this->host.")");
        }
    }

    function postreply($id, $titel, $text)
    {
        $ant = grab_page($this->forum."showthread.php?t=".$id, $this->forum );
        if(preg_match('#SECURITYTOKEN = "(.*)";#sU',$ant[0] , $token))
        {
            $post = array( "message" => $text,
                            "wysiwyg" => "0",
                            "signature" => "1",
                            //"sbutton"=>"Antworten",
                            //"fromquickreply"=>"1",
                            //"s"=>,
                            "securitytoken"=>trim($token[1]),
                            "do"=>"postreply",
                            "t"=>$id,
                            //"p"=>"who+cares",
                            "specifiedpost"=>"0",
                            "parseurl"=>"1"
                            //"loggedinuser"=>"1288",
                            );
            $posted = grab_page($this->forum."newreply.php?do=postreply&t=".$id, $this->forum."showthread.php?t=".$id, $post );
            if(preg_match("#Location: (.*)\n#iU", $posted[0], $location))
            {
                return trim($location[1]);
            }
            else
            {
                debugger($posted[0]);
                return array( "code" => false, "text" => "Fehler beim erstellen des Posts (".$this->host.")" );
            }
        }
        else{
            return array( "code" => false, "text" => "Securitytoken nicht gefunden (".$this->host.")" );
        }
    }
}
?>
