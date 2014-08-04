#!/usr/bin/php
<?php
	
/**
 * Raeog (goear BACKUP DOWNLOADER)
 *
 * Copyright (c) 2012 Antonio López Vivar
 * 
 * LICENSE:
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

	
error_reporting(E_ALL ^ E_NOTICE);
ini_set('open_basedir', FALSE);

require_once('lib/FastCurl/FastCurl.php');

define('SCRIPT_VERSION', '3.5');
define('GOEAR_HOME', 'http://www.goear.com');
define('COOKIE_FILE', '.fastcurl_cookies');
define('GOEAR_SONG_METADATA', 'http://www.goear.com/playersong/\1');
define('GOEAR_SONG_PLAYER', 'http://www.goear.com/libs/swf/soundmanager2_flash9.swf');
define('GOEAR_SONG_REGEX', '/goear\.com\/listen\/([^\/"\']+)/i');
define('GOEAR_TRACKER', 'http://www.goear.com/action/sound/get/\1');
define('GOEAR_FAVORITES_ARCHIVE', 'http://www.goear.com/\1/favorites_archive');
define('GOEAR_FAVORITES', 'http://www.goear.com/\1/favorites/all');
define('GOEAR_FAVORITES_SONG_REGEX', '/"favsound\_(?P<id_song>.*?)"/i');
define('GOEAR_FAVORITES_PLAYLIST_REGEX', '/"favplaylist\_(.*?)".*?\1\/(.*?)"/is');
define('GOEAR_AUDIOS', 'http://www.goear.com/\1/sounds');
define('GOEAR_AUDIOS_SONG_REGEX', '/"sound\_(?P<id_song>.*?)"/i');
define('GOEAR_PLAYLIST', 'http://www.goear.com/\1/playlist/\2');
define('GOEAR_PLAYLIST_URL', 'http://www.goear.com/playlist/\1');
define('GOEAR_PLAYLIST_XML', 'http://www.goear.com/playerplaylist/\1');
define('GOEAR_PLAYLIST_REGEX', '/goear\.com\/playlist\/(.*?)\/([^\/"\']+)/i');
define('GOEAR_LOGIN_POST_DATA', 'back=http%3A%2F%2Fwww.goear.com%2F&user_name=\1&password=\2');
define('GOEAR_LOGIN_POST_URL', 'http://www.goear.com/action/users/login');
define('GOEAR_LOGIN_POST_REFERER', 'http://www.goear.com/lightbox/login');
define('LOG_TEMP_FILE', '.raeog'.uniqid().'_do_not_remove');
define('ANTIFLOOD', FALSE);
define('CURL_USERAGENT', 'Mozilla/5.0 (X11; Linux x86_64; rv:27.0) Gecko/20100101 Firefox/27.0');
define('CURL_BUFFER_SIZE', 1024);

echo "\n*****goear BACKUP DOWNLOADER (".SCRIPT_VERSION.")*****\n\n";

if(($opt=getopt('u::p::s::l::j::', array('proxy::'))))
{
	$fc = new FastCurl();
	$fc->cookiefile=($fc->cookiejar=COOKIE_FILE);
	$fc->useragent=CURL_USERAGENT;
	
	$fc->progressfunction=function($ch, $ds, $d, $us, $u) use (&$down_progress, $fc)
	{ 
            if($down_progress<0)
            {
                    console_progress_bar();
                    $down_progress=0;
            }
            else if($ds>0)
            {
		if($fc->buffersize < round($ds/150)) {
			$fc->buffersize=round($ds/150);
		}

		if(($p=round(($d/$ds)*100))>$down_progress) {
			console_progress_bar(($down_progress=$p));
		}
            }
	};
	
	if(is_readable(LOG_TEMP_FILE))
	{		
		$log_data=file(LOG_TEMP_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$temp_log=array();
		foreach($log_data as $l)
		{
			list($k, $v)=explode('#', $l);
			
			$temp_log[$k]=$v;
		}
		
		unset($log_data);
	}
	
	if(isset($opt['proxy']))
	{
		echo "\nWARNING! Using proxy: {$opt['proxy']}\n";
		$fc->proxy=$opt['proxy'];
	}
	
	if(($user=$opt['u']) && ($pass=$opt['p']))
	{
		$fc->url=GOEAR_HOME;
			
		echo "\nLOGIN goear ($user)... ";
		
		if(!$fc->fetch('/goear\.com\/'.preg_quote($user, '/').'/i')) {
	            
		    $fc->add_header(array('X-Requested-With' => 'XMLHttpRequest'));
                    $fc->enable_post(str_replace(array('\1', '\2'), array($user, $pass), GOEAR_LOGIN_POST_DATA), NULL, GOEAR_LOGIN_POST_URL, GOEAR_LOGIN_POST_REFERER);
	            $fc->exec();
                    $fc->delete_header('X-Requested-With');
		    $fc->url=GOEAR_HOME;
                    $fc->referer=null;
		    $login=$fc->fetch('/goear\.com\/'.preg_quote($user, '/').'/i');
		}
		else
                    $login=true;
                
		if($login)
		{
			echo "OK\n";

			//$lf=fopen(LOG_TEMP_FILE, 'a+');
			
			if(isset($opt['f']) || (!isset($opt['l']) && !isset($opt['j'])))
			{
				/* FAVORITES SONGS */
				
				$fc->url=str_replace('\1', $user, GOEAR_FAVORITES);
				$fc->referer=null;
				$songs=$fc->fetch(array('id_song' => GOEAR_FAVORITES_SONG_REGEX),TRUE);
				
				if(($tot_songs=count($songs))>0)
				{
					if(!is_dir(($favorites_dir="./raeog_{$user}_favorites")))
					{
						echo "\nWARNING! DIR $favorites_dir does not exist. Creating it... ";
						
						if(mkdir($favorites_dir))
							echo "OK\n";
						else
							die("ERROR\n");
					}
					
					echo "[$tot_songs] songs in favorites archive.\n";
					
					$i=1;
					
					foreach($songs as $song_id)
					{
						echo "\n(".$i++."/$tot_songs) ";
						
						if(!isset($temp_log) || !array_key_exists($song_id, $temp_log) || !file_exists($temp_log[$song_id]))
						{
							download_song($fc, $song_id, $favorites_dir, $lf);			
							
							if(is_numeric(ANTIFLOOD) && ANTIFLOOD>0 && $i<=$tot_songs)
							{
								echo "\nAnti-flood (1-".ANTIFLOOD." seg): ".($af=mt_rand(1,ANTIFLOOD))." seg...\n";
								sleep($af);
							}
						}
						else
							echo "FILE [{$temp_log[$song_id]}] EXISTS! -> Song <$song_id> SKIPPED\n";
					}
					
					echo "\n\nFavorites ($user) archive BACKUP FINISHED :)\n\n";
				}
                                
                                /* FAVORITES PLAYLISTS*/
                                
                                $fc->url=str_replace('\1', $user, GOEAR_FAVORITES);
				$fc->referer=null;
				$playlists=$fc->fetch(GOEAR_FAVORITES_PLAYLIST_REGEX,TRUE);

				if(($tot_playlists=count($playlists))>0)
				{
                                    for($i=0; $i<$tot_playlists; $i++)
                                    {
                                        download_playlist($fc, $playlists[1][$i], "{$user}_{$playlists[2][$i]}", $lf);
                                    }
                                }  
                                
			}
			
			if(isset($opt['j']))
			{
				/* UPLOADED BY USER */
				
				$fc->url=str_replace('\1', $user, GOEAR_AUDIOS);
				$fc->referer=null;
				$songs=$fc->fetch(array('id_song' => GOEAR_AUDIOS_SONG_REGEX),TRUE);
				
				if(($tot_songs=count($songs))>0)
				{
					if(!is_dir(($favorites_dir="./raeog_{$user}_uploaded")))
					{
						echo "\nWARNING! DIR $favorites_dir does not exist. Creating it... ";
						
						if(mkdir($favorites_dir))
							echo "OK\n";
						else
							die("ERROR\n");
					}
					
					echo "[$tot_songs] songs in uploaded archive.\n";
					
					$i=1;
					
					foreach($songs as $song_id)
					{
						echo "\n(".$i++."/$tot_songs) ";
						
						if(!isset($temp_log) || !array_key_exists($song_id, $temp_log) || !file_exists($temp_log[$song_id]))
						{
							download_song($fc, $song_id, $favorites_dir, $lf);			
							
							if(is_numeric(ANTIFLOOD) && ANTIFLOOD>0 && $i<=$tot_songs)
							{
								echo "\nAnti-flood (1-".ANTIFLOOD." seg): ".($af=mt_rand(1,ANTIFLOOD))." seg...\n";
								sleep($af);
							}
						}
						else
							echo "FILE [{$temp_log[$song_id]}] EXISTS! -> Song <$song_id> SKIPPED\n";
					}
					
					echo "\n\nUploaded audios ($user) archive BACKUP FINISHED :)\n\n";
				}
			}
			
			if(isset($opt['l']))
			{
				/* PLAYLISTS */
				
				$i=0;
				
				do
				{
					$fc->noprogress=TRUE;
					
					$fc->url=str_replace(array('\1', '\2'), array($user, $i++), GOEAR_PLAYLIST);
					
					if($i>1)
						$fc->referer=str_replace(array('\1', '\2'), array($user, $i-1), GOEAR_PLAYLIST);
					else
						$fc->referer=NULL;
						
					$fetch_pl=$fc->fetch(GOEAR_PLAYLIST_REGEX, TRUE);
					
					for($j=0, $tot=count($fetch_pl[0]); $j<$tot; $j++)
						download_playlist($fc, $fetch_pl[1][$j], $fetch_pl[2][$j], $lf);
				
				}while($tot>0);
				
				echo "\n\nPlaylists ($user) BACKUP FINISHED :)\n\n";	
			}
			
			fclose($lf);
			
			unlink(LOG_TEMP_FILE);
		}
		else
			echo "FAILED!\n";
	}
	else if(isset($opt['l']) && preg_match(GOEAR_PLAYLIST_REGEX, $opt['l'], $playlist))
	{
		//$lf=fopen(LOG_TEMP_FILE, 'a+');
				
		download_playlist($fc, $playlist[1], $playlist[2], $lf);
		
		//unlink(LOG_TEMP_FILE);
                
                echo "\n\nPlaylist BACKUP FINISHED :)\n\n";
	}
	else if(isset($opt['s']) && preg_match(GOEAR_SONG_REGEX, $opt['s'], $song))
		download_song($fc, $song[1], './');
	else
		$arg_error=TRUE;
		
	unset($fc);
	
	if(file_exists(COOKIE_FILE))
		unlink(COOKIE_FILE);
}
else
	$arg_error=TRUE;
	
if($arg_error)
{
	?>
Use: ./raeog.php { {-u=nick -p=password [-f] [-j] [-l]} | {-l=PLAYLIST_URL} | {-s=SONG_URL} } [--proxy=IP:PORT]
	
Download ONE song:
$ ./raeog.php -s=http://www.goear.com/listen/1234567/bla

Download ONE playlist:
$ ./raeog.php -l=http://www.goear.com/playlist/7654321/alb

Download ALL user favorites songs:
$ ./raeog.php -u=alice -p=password

Download ALL uploaded songs by user:
$ ./raeog.php -u=bob -p=password -j

Download ALL user playlists:
$ ./raeog.php -u=alice -p=password -l

Download EVERYTHING:
$ ./raeog.php -u=bob -p=password -f -j -l

<?php
}
	

function download_song($fc, $song, $download_dir, $lf=NULL)
{
	global $down_progress;
	
        echo "\nReading song <$song> metadata... ";

        $fc->noprogress=TRUE;

        $fc->url=str_replace('\1', $song, GOEAR_SONG_METADATA);
        
        $fc->referer=null;

        if(($xml = simplexml_load_string($fc->fetch()))!==false) {
            $metadata['title']=str_replace('/', '', html_entity_decode($xml->playlist->track['title']));
        
            $fc->url=str_replace('\1', $song, GOEAR_TRACKER);
            $fc->referer=GOEAR_SONG_PLAYER;

             echo "OK\n\nDownloading [".($fname=strtr($metadata['title'], array(
																				"'" => "",
																				'.' => ' ',
																			   "\\" => "",
																				"?" => "",
																				"/" => "",
																				">" => "",
																				"<" => "",
																				":" => "",
																				"|" => "",
																				"*" => "",
																				'"' => '')).'.mp3')."]...\n";

            if(!file_exists(($fpath=rtrim($download_dir, '/').'/'.ltrim($fname, '/'))))
            {
                    $fc->noprogress=FALSE;
                    $fc->buffersize=CURL_BUFFER_SIZE;
                    $down_progress=-1;

                    if(file_put_contents($fpath, $fc->exec())===FALSE)
                            die("WRITE ERROR!: $fpath\n");
                    else
                            echo " OK\n";
            }
            else
                    echo "FILE EXISTS! -> Song <{$song}> SKIPPED\n";

            if($lf)
                    fwrite($lf, "{$song}#$fpath\n");
        } else {
            echo "ERROR!\n";
        }
}


function download_playlist($fc, $id, $name, $lf=NULL)
{
	global $temp_log;
	
	if(!is_dir(($playlist_dir="./raeog_{$name}_{$id}")))
	{
		echo "\nWARNING! DIR $playlist_dir does not exist. Creating it... ";
		
		if(mkdir($playlist_dir))
			echo "OK\n";
		else
			die("ERROR\n");
	}

	$fc->url=str_replace('\1', $id, GOEAR_PLAYLIST_URL);
	$fc->referer=null;
	$fc->exec();
	
	echo "Reading playlist <$id> metadata... ";
	
	$fc->url=str_replace('\1', $id, GOEAR_PLAYLIST_XML);
	$fc->referer=null;
	$fc->noprogress=TRUE;
	
	if(($xml = simplexml_load_string($fc->fetch())) !== FALSE && ($tot_songs=count($xml->playlist->track))>0)
	{
		echo "OK\n$tot_songs songs in playlist [$name].\n";
		
		$i=1;
		
		foreach($xml->playlist->track as $song)
		{
			echo "\n(".$i++."/$tot_songs) ";
                        
                        preg_match(GOEAR_SONG_REGEX, $song['target'], $song);
                        
			if(!isset($temp_log) || !array_key_exists((string)$song[1], $temp_log) || !file_exists($temp_log[(string)$song[1]]))
			{
				download_song($fc, $song[1], $playlist_dir, $lf);				
				
				if(is_numeric(ANTIFLOOD) && ANTIFLOOD>0 && $i<=$tot_songs)
				{
					echo "\nAnti-flood (1-".ANTIFLOOD." seg): ".($af=mt_rand(1,ANTIFLOOD))." seg...\n";
					sleep($af);
				}
			}
			else
				echo "FILE [{$temp_log[(string)$song[1]]}] EXISTS! -> Song <{$song[1]}> SKIPPED\n";
		}
	}
	else
		echo "ERROR!\n";
}


function console_progress_bar($pos=0, $size=100, $bar_width=50)
{
	if($pos>0)
	{
		printf("%c[57D", 0x1B);
		printf("%c[K", 0x1B);
	}
	
	printf("%s % 3d%%", "[".str_pad(NULL, ($i=round(($j=($pos/$size)*100)/(100/$bar_width))), '#').str_pad(NULL, ($bar_width-$i), '-')."]", round($j));
}

?>
