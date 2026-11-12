@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul 2>&1

cd /d "%~dp0"

echo.
echo ============================================================
echo   Nallihan MYO Sanal Okul - Kurulum
echo ============================================================
echo.

rem ------------------------------------------------------------
rem 1) Klasor adi kontrolu
rem    Uygulama adresleri /sanalokul altina gore tanimlidir.
rem ------------------------------------------------------------
for %%I in ("%CD%") do set "KLASOR=%%~nxI"

if /I not "%KLASOR%"=="sanalokul" (
    echo [HATA] Proje klasorunun adi "sanalokul" olmali.
    echo        Su anki ad : %KLASOR%
    echo.
    echo        Cozum: klasoru su konuma tasiyin veya yeniden adlandirin:
    echo               C:\xampp\htdocs\sanalokul
    echo.
    pause
    exit /b 1
)

rem ------------------------------------------------------------
rem 2) mysql.exe bul
rem ------------------------------------------------------------
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"

if not exist "%MYSQL%" (
    where mysql >nul 2>&1
    if errorlevel 1 (
        echo [HATA] mysql.exe bulunamadi.
        echo        XAMPP kurulu mu? Beklenen konum:
        echo        C:\xampp\mysql\bin\mysql.exe
        echo.
        pause
        exit /b 1
    )
    set "MYSQL=mysql"
)

rem ------------------------------------------------------------
rem 3) config.local.php hazirla
rem ------------------------------------------------------------
if not exist "config\config.local.php" (
    if not exist "config\config.local.ornek.php" (
        echo [HATA] config\config.local.ornek.php bulunamadi.
        echo.
        pause
        exit /b 1
    )
    copy /Y "config\config.local.ornek.php" "config\config.local.php" >nul
    echo [OK] config\config.local.php olusturuldu.
) else (
    echo [OK] config\config.local.php mevcut.
)

rem ------------------------------------------------------------
rem 4) MySQL baglantisi (root sifresi varsa bir kez sorulur)
rem ------------------------------------------------------------
set "ROOTARG=-u root"
"%MYSQL%" %ROOTARG% -e "SELECT 1" >nul 2>&1

if errorlevel 1 (
    echo.
    echo MySQL root sifresi gerekiyor olabilir.
    set /p "ROOTPASS=MySQL root sifresi (yoksa bos birakip Enter): "
    if defined ROOTPASS set "ROOTARG=-u root -p!ROOTPASS!"

    "%MYSQL%" !ROOTARG! -e "SELECT 1" >nul 2>&1
    if errorlevel 1 (
        echo.
        echo [HATA] MySQL'e baglanilamadi.
        echo        - XAMPP panelinden MySQL'in calistigini kontrol edin.
        echo        - Root sifresini dogru girdiginizden emin olun.
        echo.
        pause
        exit /b 1
    )
)

echo [OK] MySQL baglantisi kuruldu.

rem ------------------------------------------------------------
rem 5) SQL dosyalarini sirayla calistir
rem ------------------------------------------------------------
echo.
echo [1/6] Veritabani ve uygulama kullanicisi olusturuluyor...
"%MYSQL%" %ROOTARG% --default-character-set=utf8mb4 < "database\00_kurulum.sql"
if errorlevel 1 goto :hata

echo [2/6] Tablolar olusturuluyor...
"%MYSQL%" %ROOTARG% --default-character-set=utf8mb4 sanalokul < "database\01_schema.sql"
if errorlevel 1 goto :hata

echo [3/6] Gorunumler olusturuluyor...
"%MYSQL%" %ROOTARG% --default-character-set=utf8mb4 sanalokul < "database\02_views.sql"
if errorlevel 1 goto :hata

echo [4/6] Tetikleyiciler olusturuluyor...
"%MYSQL%" %ROOTARG% --default-character-set=utf8mb4 sanalokul < "database\03_triggers.sql"
if errorlevel 1 goto :hata

echo [5/6] Prosedurler olusturuluyor...
"%MYSQL%" %ROOTARG% --default-character-set=utf8mb4 sanalokul < "database\04_procedures.sql"
if errorlevel 1 goto :hata

echo [6/6] Demo veriler yukleniyor...
"%MYSQL%" %ROOTARG% --default-character-set=utf8mb4 sanalokul < "database\05_seed.sql"
if errorlevel 1 goto :hata

rem Uygulama kullanicisinin sifresini config ile esitle
"%MYSQL%" %ROOTARG% -e "ALTER USER 'sanalokul_app'@'localhost' IDENTIFIED BY 'SanalOkul_2026^!App';" >nul 2>&1

echo.
echo ------------------------------------------------------------
echo   Veritabani ozeti:
"%MYSQL%" %ROOTARG% -N -B -e "SELECT CONCAT((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='sanalokul' AND table_type='BASE TABLE'), ' tablo, ', (SELECT COUNT(*) FROM information_schema.views WHERE table_schema='sanalokul'), ' view, ', (SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema='sanalokul'), ' trigger, ', (SELECT COUNT(*) FROM information_schema.routines WHERE routine_schema='sanalokul' AND routine_type='PROCEDURE'), ' prosedur')"
echo ------------------------------------------------------------
echo.

echo ============================================================
echo   Kurulum tamamlandi.
echo.
echo   Adres      : http://localhost/sanalokul/
echo   Demo sifre : Sanal1234^^!
echo.
echo   XAMPP'ta Apache ve MySQL acik kalmali.
echo ============================================================
echo.
pause
exit /b 0

:hata
echo.
echo [HATA] Kurulum tamamlanamadi.
echo        Kontrol edin: database\ klasorundeki SQL dosyalari
echo        ve XAMPP MySQL durumu.
echo.
pause
exit /b 1
