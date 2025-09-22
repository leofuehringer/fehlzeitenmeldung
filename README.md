# Fehlzeiten-Meldung
## Überblick
Dieses Projekt ermöglicht es, Ausbildungsbetrieben E-Mails zu senden, wenn Auszubildende in WebUntis Fehlzeiten haben. Der Workflow
ist der folgende:
1. In WebUntis wird für den gewünschten Zeitraum ein Fehlzeiten-Bericht erstellt (Klassenbuch->Berichte->Fehlzeiten pro Schüler*in)
1. Diese Datei wird ins Fehlzeiten-System hochgeladen.
1. Nun wird für alle konfigurierten Klassen angezeigt, welche Schülerinnen und Schüler länger als die konfigurierte Anzahl an Stunden abwesend waren oder vorzeitig gegangen sind.
1. Die zu sendenden E-Mails werden über einen Haken neben der Schüler-Fehlzeit im System gekennzeichnet
1. Nach dem Absenden werden noch die als "entschuldigt" eingetragenen Fehlzeiten aufgelistet, der Ablauf ist hier der selbe

Als Vorbereitung muss eine CSV-Datei ins System eingespielt werden, wo für jeden Schüler vermerkt ist, über welche E-Mail-Adresse der Ausbilder des Auszubildenden erreichbar ist. Im Anleitungs-Verzeichnis liegt hierzu eine [exf-Datei](https://gitlab.hhs.karlsruhe.de/Seyfried/fehlzeitenmeldung/-/raw/main/anleitung/Mailadressen_Ansprechpartner.exf?inline=false), mit welcher man beispielsweise die Ansprechpartner aus ASV exportieren kann.

## Installation
### Systemvoraussetzungen
Ein Linux-Server mit PHP (Version 5 oder 7) und Apache2. Speicherbedarf ist moderat, 512 MB genügen.

Die Dateien dieses Projekts sollten so abgelegt werden, dass das Unterverzeichnis `fehlzeiten/` im WebRoot des Webservers liegt. Die Daten `Abwesenheitsmeldung` sollte außerhalb des Webroots liegen.

## Quick deploy
Diese Schritte installieren das System auf einem neu installierten Debian oder Ubuntu-Server:

```bash
apt update
apt install apache2 libapache2-mod-php git -y
cd /var/www/
git clone https://gitlab.hhs.karlsruhe.de/Seyfried/fehlzeitenmeldung.git
mv fehlzeitenmeldung/fehlzeiten/ html/
chown www-data html/fehlzeiten/uploads
cd html/fehlzeiten
cp config.php.sample config.php
nano config.php # Anpassungen vornehmen
nano /var/www/fehlzeitenmeldung/Abwesenheitsmeldung # Klassen und Pfad zu ausbilder_Emails.csv anpassen
```

## Konfiguration
Die Konfiguration des PHP-Teils erfolgt in der Datei `config.php`. Eine Beispieldatei ist mit `config.php.sample` vorgegeben, diese muss angepasst und als `config.php` in dem Verzeichnis, wo die `index.php` liegt, abgelegt werden.

In der `config.php` sind unten die E-Mail-Templates im Quelltext eingebaut, die angepasst werden müssen.

Auch im Shell-Teil muss in den ersten Zeilen der Datei `Abwesenheitsmeldung` der gewünschte Pfad konfiguriert werden und die Variable `ausbilder_file` muss auf den gleichen Dateipfad wie in der PHP-Config zeigen.
In der Variable `klassen` ist ein RegEx-Pattern anzugeben, welche Klassen für die Fehlzeitenmeldung überhaupt in Frage kommen.
