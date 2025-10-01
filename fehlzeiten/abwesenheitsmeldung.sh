#!/bin/bash

# Parameter:
# $1 = CSV-Datei der Fehlzeiten
# $2 = Filter (e = entschuldigt, u = unentschuldigt, leer = alle)
# $3 = Pfad zur Ausbilder-Datei
# $4 = Pfad zur Sent-DB

fehlzeiten_file="$1"
filter="$2"
ausbilder_file="$3"
sent_db="$4"

tmpfile=/tmp/$$.tmp

# Formular beginnen
echo "<form method='post'>"
echo "<input type='hidden' name='stage' value='1'>"

# Tabellenanfang mit Kopfzeile
echo "<table border='1' cellspacing='0' cellpadding='5' style='border-collapse: collapse; width: 100%;'>"
echo "<tr>
        <th>Name</th>
        <th>Klasse</th>
        <th>Abwesenheitsgrund</th>
        <th>Text</th>
        <th>Von</th>
        <th>Bis</th>
        <th>Mail senden?</th>
        <th>Ausbilder-Mail</th>
      </tr>"

# Filterung nach $2
if [ "$filter" = "e" ]; then
  awk -F $'\t' 'NR>1 { if ($8>0 && $18=="entsch.") print }' "$fehlzeiten_file" > $tmpfile
elif [ "$filter" = "u" ]; then
  awk -F $'\t' 'NR>1 { if ($8>0 && $18!="entsch.") print }' "$fehlzeiten_file" > $tmpfile
else
  awk -F $'\t' 'NR>1 { if ($8>0) print }' "$fehlzeiten_file" > $tmpfile
fi

# Alle eindeutigen Schüler-IDs ermitteln
students=$(awk -F $'\t' '{print $2}' "$tmpfile" | sort | uniq)

for sid in $students
do
  # Alle Einträge zu einem Schüler, sortiert nach Stunde
  grep "$sid" "$tmpfile" | sort -nt $'\t' -k 17 > $tmpfile-1

  von=$(head -1 $tmpfile-1 | awk -F $'\t' '{print $17}')
  bis=$(tail -1 $tmpfile-1 | awk -F $'\t' '{print $17}')
  name=$(head -1 $tmpfile-1 | awk -F $'\t' '{print $1}')
  klasse=$(head -1 $tmpfile-1 | awk -F $'\t' '{print $4}')
  datum=$(head -1 $tmpfile-1 | awk -F $'\t' '{print $5}')

  # Ausbilder-Mail und Sent-Status prüfen
  ausbilder=$(grep "$sid" "$ausbilder_file" | awk -F ';' '{print $2}' | sed 's/"//g')
  sent="$(grep $datum-$sid $sent_db)"

  # Neue Tabellenzeile starten
  echo "<tr>"
  echo "<td>$name</td><td>$klasse</td>"

  # Abwesenheitsgrund und Text
  if [ -n "$filter" ]; then
    head -1 $tmpfile-1 | awk -F $'\t' '{print "<td>"$11"</td><td>"$12"</td>"}'
  else
    head -1 $tmpfile-1 | awk -F $'\t' '{print "<td>(" $18 ") " $11 "</td><td>" $12 "</td>"}'
  fi

  echo "<td>$von</td><td>$bis</td>"

  # Checkbox und Status
  if [ "$filter" = "e" ] || [ -z "$ausbilder" ] || [ -n "$sent" ]; then
    echo "<td><input type='checkbox' name='send[$sid]' value='0' "
    if [ -z "$ausbilder" ]; then echo "disabled='disabled' "; fi
  else
    echo "<td><input type='checkbox' name='send[$sid]' value='1' checked='checked'"
  fi
  echo "/>"
  if [ -n "$sent" ]; then
    echo "<span style='color: #008000'>&#10004;</span>"
  fi
  echo "</td>"

  # Ausbilder-Adresse oder FEHLT!
  if [ -z "$ausbilder" ]; then
    echo "<td><span style='font-weight: bold; color: red;'>FEHLT!</span></td>"
    ausbilder=""
  else
    echo "<td>$ausbilder</td>"
  fi

  # Hidden Inputs für Formular
  echo "<input type='hidden' name='ausbilder[]' value='$ausbilder' />"
  echo "<input type='hidden' name='name[]' value='$name' />"
  echo "<input type='hidden' name='id[]' value='$sid' />"
  echo "<input type='hidden' name='klasse[]' value='$klasse' />"
  echo "<input type='hidden' name='von[]' value='$von' />"
  echo "<input type='hidden' name='bis[]' value='$bis' />"
  echo "<input type='hidden' name='datum[]' value='$datum' />"

  echo "</tr>"

  rm $tmpfile-1
done

echo "</table>"
rm $tmpfile

# Absenden-Button hinzufügen
echo "<input type='hidden' name='file' value='$1' />"
echo "<button type='submit'>E-Mails Absenden</button>"
echo "</form>"


