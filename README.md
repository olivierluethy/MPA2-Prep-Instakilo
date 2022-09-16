<!-- PROJECT LOGO -->
<br />
<p align="center">
  <a href="http://192.168.100.57:3000/Olivier_Luethy/Instakilo.git">
    <img src="assets/favicon.ico" alt="Logo" width="80" height="80">
  </a>

  <h3 align="center">MPA Prep Instakilo</h3>
  <h4 align="center">Ein Instagram Klon. Dient als Vorbereitung auf die Mini PA.</h4>

  <p align="center">
    Hier erkläre ich die Idee
    <br />
    <a href="http://192.168.100.57:3000/Olivier_Luethy/Instakilo.git/README.md"><strong>Explore the docs »</strong></a>
    <br />
    <br />
    <a href="http://192.168.100.57:3000/Olivier_Luethy/Instakilo.git">View Demo</a>
    ·
    <a href="http://192.168.100.57:3000/Olivier_Luethy/Instakilo.git/issues">Report Bug</a>
    ·
    <a href="http://192.168.100.57:3000/Olivier_Luethy/Instakilo.git/issues">Request Feature</a>
  </p>
</p>

<!-- TABLE OF CONTENTS -->
<details open="open">
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#über-das-projekt">Über das Projekt</a>
    </li>
    <li>
      <a href="#was-soll-die-applikation-können?">Was soll die Applikation können?</a>
    </li>
    <li>
      <a href="#installation">Installation</a>
    </li>
    <li>
      <a href="#verwendete-quellen">Verwendete Quellen</a>
    </li>
  </ol>
</details>

<!-- ABOUT THE PROJECT -->

## Über das Projekt

Wie oben schon beschrieben, dient dieses Projekt als Vorbereitung auf die Mini PA, die am 8. August stattfinden wird. Dieses Projekt richtet sich an die Aufgabenstellung der Mini PA.

  

<!-- Possibilities -->

## Was soll die Applikation können?

Natürlich wird diese App nicht alles können, was Instagram selber zu bieten hat. Grob soll die App nur folgendes können:

 1. Wenn Benutzer <strong>NICHT</strong> eingeloggt ist:
	- Kann nur <strong>öffentliche</strong> Bilder sehen
	- Mehr nicht
	
<strong>Die Ausgabe der Bilder wird nach den meisten Likes eines öffentlichen Bildes sortiert.</strong>
	<br>
1. Wenn Benutzer <strong>EINGELOGGT</strong> ist:

	- Kann Bilder hochladen, bearbeiten und löschen <strong>(nur seine eigene)</strong>
	- Kann Benutzer folgen und entfolgen, um private Bilder von ihnen zu sehen
	-	Kann Bilder von Benutzer sehen die er folgt, die öffentlich und privat sind.
	- Kann unter seinem Profil folgendes noch ändern:

		- Passwort ändern

		- Benutzername ändern

		- Hochgeladene Bilder bearbeiten

		- Beschreibung ändern

<strong>Die Ausgabe der Bilder wird nach den meisten Likes einer gefolgten Person sortiert<strong>

> <strong>Wie man somit sieht, lohnt es sich kaum kein Konto zu haben ;)</strong>

<!-- INSTALLATION -->

## Installation

1. Als erstes müssen Sie git auf Ihrem lokalen Computer installieren. Dazu müssen Sie diese [Website] (https://git-scm.com/downloads) besuchen.

2. Suchen Sie in Ihrem Windows-Explorer nach einem geeigneten Speicherort für das Projekt

3. Klicken Sie mit der rechten Maustaste auf den Ordner oder Ort und dann auf "Git Bash Here".

4. Schließlich öffnet sich etwas wie die Windows-Eingabeaufforderung. Wenn Sie das tun, müssen Sie nur folgendes eingeben

```sh

git clone http://192.168.100.57:3000/Olivier_Luethy/Instakilo.git

```

5. Wenn Sie das Projekt erfolgreich geklont haben, benötigen Sie eine lokale Datenbank. Ich habe [XAMPP](https://www.apachefriends.org/de/index.html) verwendet. Wenn Sie es auch verwenden möchten, stellen Sie bitte sicher, dass Sie die neueste Version davon herunterladen. Sonst funktioniert es nicht wie erwartet.

6. Wenn du alles richtig installiert hast, kannst du die Projekte im Webbrowser starten, indem du diesen Befehl eintippst:

```sh

http://localhost/Instakilo/

```

<!-- Verwendete Quellen-->

## Verwendete Quellen
Hier werden alle Quellen aufgelistet, die während der Vorbereitung gebraucht wurden.

Frage 1: Wie Variable in Header Location PHP einfügen?
Antwort: https://stackoverflow.com/questions/9773152/insert-variable-into-header-location-php

Frage 2: Wie eine hochgeladene Datei nach Kriterien überprüfen?
Antwort: https://stackoverflow.com/questions/9314164/php-uploading-files-image-only-checking
Antwort: https://www.w3schools.com/php/php_file_upload.asp

Frage 3: Wie grossen Dateipfad in die "require_once" Methode reintun?
Antwort: https://www.php.net/manual/de/function.require-once.php

Frage 4: Wie fügt man eine Modal-Box in Webseite ein?
Antwort: https://www.w3schools.com/howto/howto_css_modals.asp
<!-- DOCUMENTATION -->

Die Dokumentation zu diesem Framework finden Sie im Ordner <strong>doc</strong>!