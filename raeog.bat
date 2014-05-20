@echo off
color 0a
rem Jeronimo17-Mod
:Inicio
CLS
echo Raego Backup          Jeronimo17-Mod
echo ------------------------------------
echo 1 - Descargar cancion suelta
echo 2 - Descargar lista de canciones
echo.
echo Para lo siguiente necesitas la contrase¤a del usuario:
echo.
echo 3 - Descargar canciones favoritas de un usuario
echo 4 - Descargar todas la canciones subidas por un usuario
echo 5 - Descargar todas la listas de un usuario
echo 6 - Descargar todo de un usuario
echo.
set /p S=Introduce numero:
if %S%==1 goto :Suelta
if %S%==2 goto :Lista
if %S%==3 goto :Favorita
if %S%==4 goto :Subidas
if %S%==5 goto :ListaUsuario
if %S%==6 goto :Todo
if %S% GTR 6 goto Inicio

:Lista
set /p id=Introduce URL de la lista de canciones:
php raeog.php -l=%id%
echo.
echo Listo :~)
pause>nul
goto exit

:Suelta
set /p id=Introduce URL de la cancion suelta:
php raeog.php -s=%id%
echo.
echo Listo :~)
pause>nul
goto exit

:Favorita
set /p us=Introduce Usuario:
set /p pa=Introduce Contrase¤a:
php raeog.php -u=%us% -p=%pa%
echo.
echo Listo :~)
pause>nul
goto exit

:Subida
set /p us=Introduce Usuario:
set /p pa=Introduce Contrase¤a:
php raeog.php -u=%us% -p=%pa% -j
echo.
echo Listo :~)
pause>nul
goto exit

:ListaUsuario
set /p us=Introduce Usuario:
set /p pa=Introduce Contrase¤a:
php raeog.php -u=%us% -p=%pa% -l
echo.
echo Listo :~)
pause>nul
goto exit

:Todo
set /p us=Introduce Usuario:
set /p pa=Introduce Contrase¤a:
php raeog.php -u=%us% -p=%pa% -f -j -l
echo.
echo Listo :~)
pause>nul
goto exit
