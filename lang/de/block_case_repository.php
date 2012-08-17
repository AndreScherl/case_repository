<?php
/*
 * Copyright (C) 2007, Gert Sauerstein
 * Edited by Andre Scherl, 17.09.2012
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */
$string['pluginname']= 'Lerneradaption'; // Name to display for format
$string['case_repository']= 'iLMS Fallbibliothek';
$string['footer']='';

$string['menu_dependencies']='Semantische Beziehungen';
$string['menu_case_repository']='Fallbibliothek';
$string['apply_metadata_presets']= 'Metadaten mit Standardwerten f&uuml;llen';
$string['configure_metadata_presets']= 'Standardwerte f&uuml;r Metadaten konfigurieren';
$string['menu_info']='iLMS documentation';

$string['count_records']= 'Datensätze gefunden.';
$string['more_records']= 'Die eingegebenen Parameter liefern sehr viele Datensätze, sodass nicht alle angezeigt werden. Schränken Sie die Anfrage weiter ein, um weitere Datensätze zu finden.';
$string['submit']= 'Fälle suchen';

$string['header_searchform']='Suchkriterien und Filter';
$string['header_results']='Gefundene Fälle';
$string['legend_usermeta']='Metadaten des Lerners';
$string['legend_activity_meta']='Metadaten der Lernaktivitäten';
$string['legend_semantic_net']='Semantische Beziehungen';
$string['legend_solution']='Vorgeschlagene Lösung';
$string['legend_appliance']='Bewertung des Falls';
$string['legend_history']='Besuchte Lernaktivitäten';

// Berechtigungen
$string['case_repository:apply_metadata_presets'] = "Metadaten mit Standardwerten befüllen";
$string['case_repository:store'] = "Eigenes Verhalten als Fall in Datenbank speichern";
$string['case_repository:view_dependencies'] = "Relationen zwischen Aktivitäten betrachten";

// Definitionen für Zustände
$string['type_state']='Zustand';
$string['state_not_attempted']='Nicht begonnen';
$string['state_incomplete']='Begonnen';
$string['state_complete']='Abgeschlossen';

// Definitionen für Bewertung
$string['used_count']='Relevanz (Häufigkeit)';
$string['appliance']='Bewertung';
$string['time_visited']='Letzter Zugriff';

$string['error_invalid_course']='Ungültige Kurs-ID.<br/>Die Eigenschaften des Kurses können nicht angezeigt oder verändert werden, da der gewünschte Kurs nicht existiert.';
$string['error_invalid_user']='Ungültige Benutzer-ID.<br/>Die Eigenschaften des Kurses können nicht verändert werden, da der gewählte Benutzer nicht existiert.';
$string['error_invalid_activity']='Ungültige ID für Lernaktivitäten.<br/>Die Eigenschaften der gewählten Lernaktivität können nicht verändert werden, da sie in diesem Kurs nicht existiert.';

// Suchformular
$string['search_min']='Von';
$string['search_max']='Bis';

// Konfiguration
$string['config_submit']='Konfiguration speichern';
$string['config_submit_success']='Neue Konfiguration wurde gespeichert.';
$string['config_legend_general']='Allgemeine Grenzwerte';
$string['config_legend_similarity']='Gewichtung beim Fallvergleich';
$string['config_legend_defaults']='Standardwerte für Metadaten der Lernaktivitäten';
$string['config_legend_adjust']='Grenzwerte/Stärke der Parameteradjustierung von Fällen';
$string['config_legend_difftype']='Bedingung';
$string['config_legend_limit']='Grenzwert';
$string['config_legend_compareto']='Vergleich mit';
$string['config_legend_action']='Art der Anpassung';
$string['config_legend_strength']='Stärke';
$string['config_legend_autoadjust']='Automatische Anpassung der Lernereigenschaften';
$string['config_difftype_lesser_than']='Wert kleiner als';
$string['config_difftype_greater_than']='Wert größer als';
$string['config_difftype_diff']='Differenz größer als';
$string['config_difftype_none']='(niemals)';
$string['config_action_reject']='Fall verwerfen';
$string['config_action_adjust']='Lösung anpassen';
$string['config_description_yellow_markup']='Dieser Wert (im Interval zwischen 0.0 und 1.0, Vorgabe 0.6) gibt an, ab welchem Prozentsatz den Lernern mit gelben Rauten markierte Alternativlösungen vorgeschlagen werden.<br/> Ein Wert von 0.6 bedeutet beispielsweise, dass die Lösung des aktuellen Falls mit dem Referenzfall um mindestens 60 Prozent übereinstimmen muss, damit sie als Alternativlösung in Betracht gezogen wird.';
$string['config_description_case_count']='Dieser Wert gibt die maximale Anzahl der in der Fallbasis zu speichernden Referenzfälle (als positive ganze Zahl, Vorgabe 1000) an. Wird er überschritten, werden ungeeignete Referenzfälle ersetzt, um Platz für neue zu speichernde Fälle zu schaffen.<br/>Die maximale Fallanzahl sollte passend gewählt sein. Ist der Wert zu groß, dauern die Berechnungen für die Adaption aufgrund der großen zu durchsuchenden Fallmenge unter Umständen sehr lang. Ist der Wert zu klein, können unter Umständen zu wenige repräsentative Fälle gespeichert werden, sodass keine optimalen Adaptionsergebnisse erzielt werden.';
$string['config_description_replace']='Dieser Wert gibt an, ab welcher Grenze (als Wert zwischen 0.0 und 1.0, Vorgabe 0.9) ähnliche Fälle in der Fallbasis zu einem Fall zusammengefasst werden dürfen. Ein Wert von 0.95 bedeutet beispielsweise, dass zwei Fälle mit einer Ähnlichkeit von 95 Prozent oder mehr zu einem Fall zusammengefasst werden können, um Platz in der Fallbasis zu sparen.';
$string['config_description_casecompare']='Die obigen Gewichtswerte werden zum Vergleich von Fällen verwendet um anzugeben, wie stark die Ähnlichkeit von untergrordneten Attribute bzw. Attributgruppen in die Berechnung der Ähnlichkeit von übergeordneten Attributgruppen bzw. der Fälle eingehen.<br/>Es dürfen numerische Werte im Berech von 0.0 bis 1.0 eingegeben werden. Ein Wert von 0.05 bedeuted beispielsweise, dass ein Attribut ein Gewicht von 5 Prozent innerhalb der Attributgruppe besitzt. Die Summe aller Gewichte einer Attributgruppe bzw. eines Falls <em>muss deswegen insgesamt 1.0 betragen</em> (also 100 Prozent). Die einem Fall untergeordneten Attributgruppen der <cite>Lerner-Eigenschaften</cite> und <cite>Metadaten der Lernaktivitäten</cite> bestehen wiederum aus einzelnen Attributen, sodas deren Gewichte einmal einzeln und einmal für die gesamte Attributgruppe eingestellt werden können.';
$string['config_description_defaults']='Standardmäßig sind die Metadatenwerte für alle Lernaktivitäten leer.<br/>Mit diesen Einstellungen können eigene Vorgabewerte für Metadaten-Attribute definiert werden, die standardmäßig beim Erstellen einer Lernaktivität gesetzt werden. Diese Vorgabewerte können in Abhängigkeit vom Lernmodul vergeben und später für Lernaktivität individuell angepasst werden.';
$string['config_description_adjust_checkbox']='Mit der Auswahl in dieser Box kann die Parameteranpassung für die Lerneradaption aktiviert werden, die zu besseren Lösungsvorschlägen führen kann. Der Nachteil dabei ist, dass etwas mehr Rechenzeit benötigt wird, um die adaptive Kursstruktur darzustellen.';
$string['config_description_adjust']='Mit diesen Regeln kann die Parameteranpassung der Lerneradaption konfiguriert werden. Alle Regeln werden kumulativ angewendet.<br/>Die Regeln bestehen aus einer Bedingung und einer Aktion. Wenn die Bedingung einer Regel erfüllt ist, wird die mit der Regel verbundene Aktion ausgeführt. Bedingungen basieren auf Vergleichen von Attributwerten und bestehen aus der Art des Vergleichs, einem Grenzwert, ab dem die Bedingung als eingetreten angenommen wird und einem Attribut des Referenzfalls, mit dem der Wert des aktuellen Attributs verglichen werden soll. Aktionen bestehen aus der Art der auszuführenden Aktion sowie (nur bei Parameter-Anpassungen) der Stärke der ausgeführten Anpassung.';
$string['config_description_adjust2']=' <p>Es gibt folgende Arten von Bedingungen:</p><dl><dt>(niemals):</dt> <dd>Für dieses Attribut ist keine Regel definiert. Alle anderen Angaben werden ignoriert.</dd><dt>Wert kleiner als:</dt> <dd>Die Differenz aus beiden Attributwerten wird gebildet. Ist sie negativ und kleiner als der angegebene Grenzwert, d. h. der Wert des Referenzfalls ist mindestens um den Grenzwert kleiner als der Wert des aktuellen Falls.</dd><dt>Wert größer als:</dt> <dd>Die Differenz aus beiden Attributwerten wird gebildet. Sie ist positiv und größer als der angegebene Grenzwert, d. h. der Wert des Referenzfalls ist mindestens um den Grenzwert größer als der Wert des aktuellen Falls.</dd><dt>Differenz größer als:</dt> <dd>Die Differenz aus beiden Attributwerten wird gebildet. Das Vorzeichen wird ignoriert, der Betrag der Differenz ist größer als der angegebene Grenzwert, d. h. der Wert des Referenzfalls ist mindestens um den Grenzwert größer als der Wert des aktuellen Falls oder mindestens um den Grenzwert kleiner als dieser.</dd></dl><p>Es gibt folgende Typen von Vergleichsattributen:</p><dl><dt>Metadaten des Lerners:</dt> <dd>Der Vergleich der Eigenschaften des aktuellen Lerners erfolgt mit den Eigenschaften des Lerners aus dem Referenzfall.</dd><dt>Metadaten der Lernaktivitäten:</dt> <dd>Der Vergleich der Eigenschaften des aktuellen Lerners erfolgt mit den Metadaten der vorgeschlagenen Lernaktivität (Lösung) aus dem Referenzfall.</dd></dl><p>Folgende Arten von Aktionen sind möglich:</p><dl><dt>Fall verwerfen:</dt> <dd>Wenn die Bedingung erfüllt ist, soll der betrachtete Referenzfall als unbrauchbar gelten und wird deswegen bei der Berechnung einer optimalen Lösung nicht weiter betrachtet.</dd><dt>Lösung anpassen:</dt> <dd>Wenn die Bedingung erfüllt ist, soll der Fall nur als eingeschränkt brauchbar gelten. Er kann deshalb vom System vorgeschlagen werden, die Bewertung/Relevanz wird aber pauschal um einen bestimmten Betrag gesenkt. Der Betrag zur <cite>Stärke</cite> der Anpassung muss als numerischer Wert angegegeben werden (0.3 entspricht einer Abwertung von 30 Prozent, es verbleiben also 70 Prozent der ursprünglichen Relevanz des Falls).</dd></dl>';
$string['config_description_leak_limit']='Dieser Wert gibt an, ab welcher Grenze die logische Sicherheit so sehr abgenommen hat, dass der betreffende Wert der Lerner-Eigenschaft aus Gründen der Unsicherheit aus der Datenbank entfernt wird.<br/>Ein Wert von 0.1 gibt beispielsweise an, dass ab einer verbleibenden Sicherheit von 10 Prozent ein Wert gelöscht wird. Bei einer Halbwertszeit von 1 Jahr entspricht dies in etwa der Zeit von 3 Jahren und 4 Monaten, die seit der Eingabe oder Ermittlung eines Werts vergehen muss, bis der Wert entfernt wird.';
$string['config_description_half_value_time']='Die Halbwertszeit für die logische Sicherheit gibt an, wie stark das Vertrauen in den angegebenen Attributwert einer Lernereigenschaft mit der Zeit sinkt bzw. wie stark die Unsicherheit mit der Zeit zunimmt. Der angegebene Wert entspricht dabei der Zeitdauer in SI-Sekunden, ab der die Sicherheit nur noch die Hälfte des ursprünglichen Werts beträgt.<br/>Beträgt die Halbwertszeit zum Beispiel 31557600 SI-Sekunden (das entspricht 1 Jahr) und war der Attributwert ursprünglich zu 80 Prozent sicher, so ist die Sicherheit nach 1 Jahr auf 40 Prozent gesunken. Nach einem weiteren Jahr hat sich die logische Sicherheit erneut auf 20 Prozent halbiert.';
$string['ILMS_YELLOW_MARKUP_LIMIT']='Grenzwert für gelbe Markierung (Raute)';
$string['MAX_ILMS_CASE_COUNT']='Maximale Anzahl Fälle der Fallbasis';
$string['REPLACE_CASE_SIMILARITY_LIMIT']='Ähnlichkeitswert, ab dem Fälle wiederverwendet (ersetzt) werden';
$string['REPLACE_CASE_APPLIANCE_LIMIT']='Benotungsunterschied, bis zu dem Fälle wiederverwendet (ersetzt) werden';
$string['WEIGHT_FACTOR_LEARNERMETA']='Fälle: Gewicht der Lerner-Eigenschaften';
$string['WEIGHT_FACTOR_ACTIVITYMETA']='Fälle: Gewicht der Metadaten für Lernaktivitäten';
$string['WEIGHT_FACTOR_FOLLOW_RELATIONS']='Fälle: Gewicht der semantischen Beziehungen';
$string['WEIGHT_FACTOR_CURRENT_ACTIVITY']='Fälle: Gewicht der aktuellen Lernaktivität';
$string['WEIGHT_FACTOR_HISTORY']='Fälle: Gewicht der Historie';
$string['WEIGHT_FACTOR_STATES']='Fälle: Gewicht der Zustände aller Lernaktivitäten';
$string['WEIGHTS_LEARNERMETA']='Lerner-Eigenschaften: Gewicht für ';
$string['WEIGHTS_ACTIVITYMETA']='Metadaten der Lernaktivitäten: Gewicht für ';
$string['ENABLE_REUSE_ADJUSTMENTS']='Parameteranpassung für Fälle aktivieren';
$string['HALF_VALUE_TIME']='Halbwertszeit für logische Sicherheit (in SI-Sekunden)';
$string['LEAK_LIMIT']='Verlust-Grenzwert für logische Sicherheit';
$string['edit_presets']='Standardwerte für die Metadaten der Lernmodule anzeigen&nbsp;/ anpassen';
$string['apply_presets']='Durch das Füllen der Metadaten werden die Metadaten für alle Lernaktivitäten dieses Kurses durch die Standardwerte ersetzt.';
$string['apply_presets_question']='Sollen die Metadaten gefüllt und gegebenenfalls ersetzt werden?';

/**
 * Ab hier werden die Strings des ursprünglichen Kurs-Formats hizugefügt
 */
 
$string['formatilms']='Adaptiver iLMS-Kurs'; // Name to display for format
$string['nameilms']='Kursabschnitt'; // Name of a section within your format

$string['headline_editing']='Alle Lernaktivitäten (Editiermodus)';
$string['headline_last_activity']='Zuletzt besuchte Lernaktivität';
$string['headline_startup']='Lernaktivitäten zu Beginn dieses Kurses';
$string['headline_recommended_activities']='Weiterführende Lernaktivitäten';
$string['headline_alternate_activities']='Alternative Lernaktivitäten';
$string['headline_unavailable_activities']='Nicht verfügbare Lernaktivitäten';
$string['headline_dependency_table']='Tabelle der semantischen Abhängigkeiten';
$string['description_startup']='Die folgenden Lernaktivitäten stehen zur Verfügung, um diesen Kurs zu beginnen.';
$string['description_editing']='Diese Seite zeigt die Lernaktivitäten des Kurses zur Bearbeitung im Editiermodus.<br/>Im Präsentationsmodus werden sie später anhand der iLMS Statistiken gruppiert, sodass den Teilnehmern in Abhängigkeit ihrer persönlichen Eigenschaften und Präferenzen einzelne Lernaktivitäten gesondert empfohlen werden können.';
$string['description_last_activity']='Die folgende Lernaktivität wurde zuletzt besucht und kann fortgesetzt oder wiederholt werden.';
$string['description_recommended_activities']='Um weiter im Kurs voranzuschreiten wird empfohlen, eine der folgenden Lernaktivitäten als nächstes zu besuchen.<br/>Andere Teilnehmer mit ähnlichen Eigenschaften und Präferenzen waren in der Vergangenheit sehr erfolgreich, in dem sie die mit einem grünen Stern markierten Lernaktivitäten als nächstes besuchten.';
$string['description_alternate_activities']='Zur Vollständigkeit stehen außerdem noch die folgenden Lernaktivitäten in diesem Kurs zur Verfügung.';

$string['usermeta_update']='<p>Du solltest deine persönlichen Eigenschaften aktualisieren, damit das Lernsystem eine optimale Kursstruktur bereitstellen kann.</p>';
$string['usermeta_update_headline']='Deine persönlichen Lerneigenschaften sind nicht mehr aktuell';
$string['usermeta_empty']='<p>Du hast deine persönlichen Lerneigenschaften noch nicht angepasst.<br/>Verwende den Block <cite><em>Persönliche Lerneigenschaften</em></cite>, um deine persönlichen Eigenschaften, Fähigkeiten und Vorlieben einzugeben. Dadurch ist das System in der Lage, dir passende Lernaktivitäten zu empfehlen.</p>';
$string['usermeta_empty_headline']='Deine persönlichen Lerneigenschaften wurden noch nicht gesetzt';
$string['usermeta_feedback']='<p></p>'; // TODO
$string['usermeta_feedback_headline']='Deine Meinung zur letzten Lernaktivität?';
$string['usermeta_feedback_submit']='Bewerten';

$string['feedback_difficulty_level1']='Ich konnte mit dem Lerninhalt nichts anfangen';
$string['feedback_difficulty_level2']='Der Lerninhalt war schwierig';
$string['feedback_difficulty_level3']='Der Schwierigkeitsgrad war in Ordnung';
$string['feedback_difficulty_level4']='Der Lerninhalt war einfach';
$string['feedback_difficulty_level5']='Ich weiß das bereits und finde es langweilig';
$string['feedback_none']='Ich habe keine besonderen Kritikpunkte';
$string['feedback_linguistic_requirement_high']='Das Niveau des Texts ist mir zu einfach/trivial';
$string['feedback_linguistic_requirement_low']='Der Text könnte einfacher, knapper und verständlicher sein';
$string['feedback_logical_requirement_high']='Ich fand die Zusammenhänge unlogisch';
$string['feedback_logical_requirement_high2']='Eine kurze Darstellung in Formeln fände ich besser';
$string['feedback_logical_requirement_low']='Der logische Aufbau war mir zu komplex';
$string['feedback_logical_requirement_low2']='Die mathematischen Berechnungen fielen mir schwer';
$string['feedback_social_requirement_high']='Die Gruppenarbeit hat mir Spaß gemacht';
$string['feedback_social_requirement_low']='Die Zusammenarbeit mit den anderen Gruppenmitgliedern war schlecht';
$string['feedback_learningstyle_perception_high']='Auf die generellen Zusammenhänge wird zu wenig eingegangen';
$string['feedback_learningstyle_perception_low']='Mir fehlen konkrete Beispiele und Anwendungen';
$string['feedback_learningstyle_organization_high']='Die Verdeutlichung der Zusammenhänge an Beispielen wäre schön';
$string['feedback_learningstyle_organization_low']='Ich hätte zur Einführung zuerst gern einige Beispiele';
$string['feedback_learningstyle_perspective_high']='Mir fehlt die Betrachtung von Anwendungen und Alternativen';
$string['feedback_learningstyle_perspective_low']='In der Darstellung vermisse ich den Überblick';
$string['feedback_learningstyle_input_high']='Ich vermisse eine ausführliche Diskussion des Themas';
$string['feedback_learningstyle_input_low']='Mir fehlen Grafiken und Diagramme zur Erläuterung';
$string['feedback_learningstyle_processing_high']='Die selbständig zu bearbeitenden Aufgaben fallen mir schwer';
$string['feedback_learningstyle_processing_low']='Ich hätte gern mehr interaktive Aufgaben';

$string['panel_details']='Details';
$string['panel_metadata']='Metadaten';
$string['panel_dependencies']='Abhängige Lernaktivitäten';
$string['panel_dependent_on']='Übergeordnete Lernaktivitäten';
$string['legend_dependency_add']='Neue abhängige Lernaktivität hinzufügen';
$string['legend_dependent_on_add']='Neue Abhängigkeit von einer übergeordneten Lernaktivität hinzufügen';
$string['label_source_activity']='Abhängige Lernaktivität';
$string['label_target_activity']='Übergeordnete Lernaktivität';
$string['label_dependency_type']='Art der Abhängigkeit';
$string['details_unknown']='Für diese Lernaktivität sind keine Details verfügbar.';
$string['no_dependencies']='Es gibt keine abhängigen Lernaktivitäten zu dieser Lernaktivität.';
$string['no_dependent_on']='Diese Lernaktivität ist von keiner anderen Lernaktivität abhängig.';

$string['markup_good']='Empfohlene Lernaktivität';
$string['markup_medium']='Alternative Lernaktivität';
$string['markup_bad']='Weniger geeignete Lernaktivität';
$string['markup_unknown']='Unbekannt';
$string['markup2_good']='Dieser Lerninhalt sollte als nächstes besucht werden';
$string['markup2_medium']='Dieser Lerninhalt kann ebenfalls als nächstes besucht werden';
$string['markup2_bad']='Dieser Lerninhalt sollte nur in speziellen Fällen besucht werden';
$string['markup2_unknown']='Das iLMS-Adaptionsmodul weiß noch nicht, ob diese Lernaktivität im Moment geeignet ist, da noch keine entsprechenden Falldaten vorliegen. Falldaten werden erst im Lauf der Zeit automatisch vervollständigt.';

$string['button_details']='Details zu dieser Lernaktivität anzeigen/verbergen';
$string['button_metadata']='Metadaten zu dieser Lernaktivität anzeigen/verbergen';
$string['button_dependencies']='Lernaktivitäten, die von dieser Lernaktivität abhängig sind, anzeigen/verbergen';
$string['button_dependent_on']='Übergeordnete Lernaktivitäten, von denen diese Lernaktivität abhängig ist, anzeigen/verbergen';
$string['edit_button_add']='Hinzufügen';
$string['edit_button_modify']='Ändern';
$string['edit_button_delete']='Löschen';
$string['edit_button_save_changes']='Änderungen speichern';
$string['edit_continue']='Weiter ...';

// Definition für Metadaten

$string['activity_linguistic_requirement']='sprachlicher Anspruch';
$string['activity_logical_requirement']='Mathematisch-logischer Anspruch';
$string['activity_social_requirement']='Bearbeitungsmodus';
$string['activity_learningstyle_perception']='Inhalt';
$string['activity_learningstyle_organization']='Vorgehensweise';
$string['activity_learningstyle_perspective']='Aufbau';
$string['activity_learningstyle_input']='Präsentation';
$string['activity_difficulty']='Schwierigkeitsgrad';
$string['activity_learningstyle_processing']='Interaktivitätstyp';
$string['activity_age']='Mittlere Altersklasse';
$string['activity_learning_time']='Zeitbedarf';
$string['activity_module']='Lernmodul';
$string['activity_id']='ID';
$string['activity_language']='Sprache';
$string['value_unknown']='Nicht bekannt';

$string['activity_linguistic_requirement_level1']='sehr gering';
$string['activity_linguistic_requirement_level2']='gering';
$string['activity_linguistic_requirement_level3']='durchschnittlich';
$string['activity_linguistic_requirement_level4']='hoch';
$string['activity_linguistic_requirement_level5']='sehr hoch';
$string['activity_logical_requirement_level1']='sehr gering';
$string['activity_logical_requirement_level2']='gering';
$string['activity_logical_requirement_level3']='durchschnittlich';
$string['activity_logical_requirement_level4']='hoch';
$string['activity_logical_requirement_level5']='sehr hoch';
$string['activity_social_requirement_level1']='selbständig';
$string['activity_social_requirement_level2']='eher selbständig';
$string['activity_social_requirement_level3']='gemischt';
$string['activity_social_requirement_level4']='eher kollaborativ';
$string['activity_social_requirement_level5']='kollaborativ';
$string['activity_learningstyle_perception_level1']='vorwiegend konkret';
$string['activity_learningstyle_perception_level2']='eher konkret';
$string['activity_learningstyle_perception_level3']='gemischt';
$string['activity_learningstyle_perception_level4']='eher abstrakt';
$string['activity_learningstyle_perception_level5']='vorwiegend abstrakt';
$string['activity_learningstyle_organization_level1']='vorwiegend deduktiv';
$string['activity_learningstyle_organization_level2']='eher deduktiv';
$string['activity_learningstyle_organization_level3']='gemischt';
$string['activity_learningstyle_organization_level4']='eher induktiv';
$string['activity_learningstyle_organization_level5']='vorwiegend induktiv';
$string['activity_learningstyle_perspective_level1']='sequenziell';
$string['activity_learningstyle_perspective_level2']='eher sequenziell';
$string['activity_learningstyle_perspective_level3']='gemischt';
$string['activity_learningstyle_perspective_level4']='eher global';
$string['activity_learningstyle_perspective_level5']='global';
$string['activity_learningstyle_input_level1']='vorwiegend visuell';
$string['activity_learningstyle_input_level2']='eher visuell';
$string['activity_learningstyle_input_level3']='gemischt';
$string['activity_learningstyle_input_level4']='eher verbal';
$string['activity_learningstyle_input_level5']='vorwiegend verbal';
$string['activity_difficulty_level1']='sehr leicht';
$string['activity_difficulty_level2']='einfach';
$string['activity_difficulty_level3']='durchschnittlich';
$string['activity_difficulty_level4']='anspruchsvoll';
$string['activity_difficulty_level5']='sehr schwierig';
$string['activity_learningstyle_processing_level1']='aktiv';
$string['activity_learningstyle_processing_level2']='eher aktiv';
$string['activity_learningstyle_processing_level3']='gemischt';
$string['activity_learningstyle_processing_level4']='eher expositiv';
$string['activity_learningstyle_processing_level5']='expositiv';
$string['activity_age_level']='(in Jahren)';
$string['activity_learning_time_level']='(in Stunden)';

$string['relation_vertieft']='vertieft';
$string['relation_erlaeutert']='erläutert';
$string['relation_beispiel']='ist Beispiel zu';
$string['relation_anwendung']='ist Anwendung zu';
$string['relation_illustriert']='illustriert';
$string['relation_querverweis']='ist Querverweis zu';
$string['relation_exkurs']='ist Exkurs zu';
$string['relation_fasstzusammen']='ist Zusammenfassung zu';
$string['relation_bautauf']='baut auf';
$string['relation_wiederholt']='wiederholt';
$string['relation_setztvoraus']='benötigt';
$string['relation_prueft']='prüft den Lernstoff zu';

$string['error_invalid_course']='Ungültige Kurs-ID.<br/>Die Eigenschaften des Kurses können nicht angezeigt oder verändert werden, da der gewünschte Kurs nicht existiert.';
$string['error_invalid_user']='Ungültige Benutzer-ID.<br/>Die Eigenschaften des Kurses können nicht verändert werden, da der gewählte Benutzer nicht existiert.';
$string['error_invalid_activity']='Ungültige ID für Lernaktivitäten.<br/>Die Eigenschaften der gewählten Lernaktivität können nicht verändert werden, da sie in diesem Kurs nicht existiert.';
$string['error_invalid_sql_add']='SQL Datenbankfehler beim Hinzufügen des neuen Attributs';
$string['error_invalid_sql_set']='SQL Datenbankfehler beim Ändern des Attributwerts';
$string['error_invalid_sql_delete']='SQL Datenbankfehler beim Löschen des angegebenen Attributs';

$string['vertical']='vertikal';
$string['horizontal']='horizontal';
$string['appliance']='Relevanz';

$string['capability_no_store']='<b>Hinweis</b>: Für Deine Eingaben werden keine neuen Fälle in der Lerner-Adaption angelegt, da Du nicht in der Rolle eines <cite>Lerner</cite>s bist.';

$string['config_windowheader_preset']='Vorgabewerte für Metadaten';
$string['config_header_preset']='Vorgabewerte für Metadaten definieren';
$string['config_save_preset']='Vorgabewerte für dieses Lernmodul speichern';
