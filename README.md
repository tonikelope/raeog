raeog
=====

Raeog (goear BACKUP DOWNLOADER)

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