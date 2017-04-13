<?php 
# Mit Hilfe dieses Skripts soll eine einfach Möglchkeit geschafften werden. Texte/Inhalte der Adminseite zu bearbeiten
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
 <head>
  <title>Administration Alarmdisplay</title>
 <link type="text/css" href="../css/screen.css" rel="stylesheet" />
 <link type="text/css" href="../css/blitzer/jquery-ui-1.8.21.custom.css" rel="stylesheet" />
<script type="text/javascript" src="../js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="../js/jquery-ui-1.8.21.custom.min.js"></script>
<script type="text/javascript">
$(function(){
	// Accordion
	$("#accordion").accordion({ header: "h3" });

	// Tabs
	$('#tabs').tabs();

	// Dialog
	$('#dialog').dialog({
		autoOpen: false,
		width: 600,
		buttons: {
			"Ok": function() {
				$(this).dialog("close");
			},
			"Cancel": function() {
				$(this).dialog("close");
			}
		}
	});

	// Dialog Link
	$('#dialog_link').click(function(){
		$('#dialog').dialog('open');
		return false;
	});

	// Datepicker
	$('#datepicker').datepicker({
		inline: true
	});

	// Slider
	$('#slider').slider({
		range: true,
		values: [17, 67]
	});

	// Progressbar
	$("#progressbar").progressbar({
		value: 20
	});

	//hover states on the static widgets
	$('#dialog_link, ul#icons li').hover(
		function() { $(this).addClass('ui-state-hover'); },
		function() { $(this).removeClass('ui-state-hover'); }
	);
});

jQuery(function(jQuery){  
     jQuery.datepicker.regional['de'] = {clearText: 'löschen', clearStatus: 'aktuelles Datum löschen',  
                closeText: 'schließen', closeStatus: 'ohne änderungen schließen',  
                prevText: '<zurück', prevStatus: 'letzten Monat zeigen',  
                nextText: 'Vor>', nextStatus: 'nächsten Monat zeigen',  
                currentText: 'heute', currentStatus: '',  
                monthNames: ['Januar','Februar','März','April','Mai','Juni',  
                'Juli','August','September','Oktober','November','Dezember'],  
                monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun',  
                'Jul','Aug','Sep','Okt','Nov','Dez'],  
                monthStatus: 'anderen Monat anzeigen', yearStatus: 'anderes Jahr anzeigen',  
                weekHeader: 'Wo', weekStatus: 'Woche des Monats',  
                dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],  
                dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],  
                dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],  
                dayStatus: 'Setze DD als ersten Wochentag', dateStatus: 'Wähle D, M d',  
                dateFormat: 'dd.mm.yy', firstDay: 1,   
                initStatus: 'Wähle ein Datum', isRTL: false};  
     jQuery.datepicker.setDefaults(jQuery.datepicker.regional['de']);  
});  
		</script>

</head><body>

<?php
require('auth.php');
require('../config.inc.php');
require('functions.php');

// Verbindung zur Datenbank herstellen und an Handle $db übergeben
$db = mysqli_connect(DBHOST, DBUSER, DBPASS, DBNAME) or die ('Verbindung zur Datenbank fehlgeschlagen.');
$db->set_charset("utf8");

/*
// Wird das Formular zum Speichern aufgerufen?
if (isset($_POST['submit'])){
	// Ja, wir bekommen Daten. Die müssen wir an die Datenbank melden.
	foreach ($_POST as $k=>$v){
		if ($k != "submit") {
			$result=$db->query("UPDATE tbl_adm_params SET wert = '".$v."' WHERE parameter = '".$k."'");
		}
	}
	
	echo "<body onload='javascript:alert(\"Daten gespeichert!\")'>";
	
}else{
	// nur Body ausgeben
	echo "<body>";
	
}
*/
#Falls keine GET-Variablen oder eine leere Tabnummer übergeben werden erscheint ein Auswahlfeld mit den bisherigen Tabs bzw. die Möglichkeit einen neuen Tab anzulegen
#Alle Daten in GET-Variablen, POST nur zum Speichern in der Datenbank
if(!isset($_GET['tab']) or trim($_GET['tab'])=="" and !isset($_POST['submit'])){
	
	# Abruf aller bisherigen tabs aus der Datenbank
	$tabs = $db->query("SELECT tab, title FROM tbl_adm_params WHERE (acc=0 AND line=0) GROUP BY tab");
	
	#Erzeuge Auswahlfelder
	echo "<p>
	<form action=\"\" method=\"GET\">
	Wähle Tab bzw. auszuführende Aktion:
	<select name=\"tab\">
	<option value=\"\">Select...</option>\n";
	
	while ($row = $tabs->fetch_row()){
		echo "<option value=\"$row[0]\">".$row[1]."</option>\n";
	}
	echo "<option value=\"new\">Neuer Tab</option>\n";
	echo "<option value=\"delete\">Tab Löschen</option>\n";
	echo "</select>\n
	<input type=submit value=\"auswählen\"/></form></p>";

	#Ende der Ausgaben bei keiner Übergabe von GET-Variablen
}else{
	var_dump($_POST);
	#Falls POST werden die Werte gespeichert sonst geht die Auswahl der Bearbeitungsmöglichkeiten weiter
	if(isset($_POST['submit'])){
		switch ($_POST['submit']){
			#Anlegen neuer Tabs
			case "Tab anlegen":
				#Test ob leere Felder übergeben wurden
				$name = test_input($_POST['name']);
				$text = test_input($_POST['text']);
				if(strlen($name)==0 or strlen($text)==0){
					die("Bitte ALLE Felder ausfüllen!<br><a href=".$_SERVER['HTTP_REFERER'].">Zurück</a>");
				}
				if(strlen($text)>200){
					die("Die Beschreibung darf maximal 200 Zeichen lang sein.<br><a href=".$_SERVER['HTTP_REFERER'].">Zurück</a>");
				}
				#Gibt es keine Beanstandungen wird der neue Tab in die Datenbank eingefügt
				
				#zuerst Anzahl der bisherigen Tabs bestimmen
				$tabs = $db->query("SELECT * FROM tbl_adm_params WHERE (acc=0 AND line=0) GROUP BY tab");
				$anzahl = $tabs->num_rows;
				#Nummer des neuen Tabs festlegen
				$tabnumber =$anzahl+1;
				
				#Einfügebefehl
				$insert = $db->query("INSERT INTO `tbl_adm_params` (`id`, `parameter`, `wert`, `type`, `tab`, `acc`, `line`, `title`, `beschreibung`) VALUES (NULL, NULL, NULL, NULL, '$tabnumber', '0', '0', '$name', '$text');");
				header("Location: adminedit.php");
				break;
			
			#Löscht Einträge aus der Datenbank
			case "Löschen":
				
				break;
		}
	}else{
		#Soll ein neuer Tab angelegt werden?
		switch ($_GET['tab']){
			case "new":
				#Eingabemaske für Tabnamen und Beschreibung
				echo "<div id=center>\n";
				echo "Neuen Tab anlegen \n
				<form action='' method='POST'>\n
				<div>
				Name: <input type=text name='name' value''/><br>\n
				Beschreibung:  <textarea id=\"text\" name=\"text\"></textarea><br>\n
				<input type=submit name='submit' value='Tab anlegen'>\n
				</div>\n
				</form>\n";
				echo "</div>\n";
				break;
			
			case "delete":
				# Abruf aller bisherigen tabs aus der Datenbank
				$tabs = $db->query("SELECT tab, title FROM tbl_adm_params WHERE (acc=0 AND line=0) GROUP BY tab");
				
				#Erzeuge Auswahlfelder
				echo "<p>
				<form action=\"\" method=\"POST\">
				Wähle Tab: 
				<select name=\"tab\">
				<option value=\"\">Select...</option>\n";
				while ($row = $tabs->fetch_row()){
					echo "<option value=\"$row[0]\">".$row[1]."</option>\n";
				}
				echo "</select>\n<br>
				<input type=\"submit\" name=\"submit\" value=\"Löschen\"/> <input type=\"submit\" value='Abbrechen' <a href=\"#\" onclick=\"history.back();\"</a></input></form>\n
				</p>";
				
		}
	}
	
}

?>
</body>
</html>