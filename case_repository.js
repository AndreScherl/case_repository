/*
 * Script to change execution of case based reasoning after every navigation step from server to client
 * to achieve a better performance
 *
 * Original PHP source code made by Gert Sauerstein (iLMS Plugin). 
 * 
 * Translated to JS and edited by Andre Scherl.
 *
 * Copyright (C) 2012, Andre Scherl
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */
 
 M.block_case_repository = {
 	//hier kommen die ganzen globalen variablen von ilms hin
 	Y: null,
 	current_case: null,
 	ilms_globals:null
 };
 
 M.block_case_repository.init = function(Y) {
 	this.Y = Y;
 	//console.log("init case repository");
 	
 	this.Y.io(M.cfg['wwwroot']+"/blocks/case_repository/get_ilms_config.php", {
 		on: {
 			success: this.Y.bind(function(id, o) {
 				this.ilms_globals = JSON.parse(o.responseText);
 				
 				// Jede Stunde wird die Falldatenbank in den local Storage kopiert
 				var now = new Date().getTime();
 				if(!localStorage.getItem("db_time") || ((now - parseInt(localStorage.getItem("db_time")))/1000 > 3600)){
 					this.all_cases_to_local_storage();
 					console.log("copy database to local storage");
 				}
 				
 				// Elemente der Benutzeroberflöche deaktivieren
 				this.deactivate_interface_elements();
 				
 				// Den aktuellen Fall ermitteln und Lösungen bestimmen
 				this.get_current_case(this.Y.bind(function(currentcase){
 					var retrieved_solutions = this.retrieve_cases(currentcase, 3).solutions;
 					var solutions = this.revise_cases(retrieved_solutions);
 					
 					// Die Lösung in PHP Session übertragen
 					this.Y.io(M.cfg['wwwroot']+"/blocks/semantic_web/put_iLMS_solutions_to_session.php", {
 						method: "POST",
 						data: "solutions="+JSON.stringify(this.Y.Object.values(solutions))
 					});
 					
 					// Die beste nächste Lernaktivität für adaptive direct guidance ermitteln
 					var best_next_activity = this.get_best_next_activity(solutions, currentcase);
 					this.Y.io(M.cfg['wwwroot']+"/blocks/semantic_web/get_session_vars.php", {
				 	  	on: {
				 	    	success: this.Y.bind(function(id, o) {
				 	      		// Session Variable setzen
				 	      		var dasisSession = JSON.parse(o.responseText);
				 	      		// Ziel des NEXT-Buttons auf beste nächste Lernaktivität setzen
				 	      		if(dasisSession["path"] == "adapt") {
				 	      			if(best_next_activity != null) {
				 	      				Y.one("#id_button_nextNode").set("value", M.cfg['wwwroot']+"/blocks/case_repository/start.php?id="+best_next_activity.id+"&foreward=true");
				 	      			} else {
				 	      				Y.one("#id_button_nextNode").set("value", "#");
				 	      			}	
				 	      		}
				 	      		// Interface-Elemente wieder aktivieren
				 	      		this.activate_interface_elements();
				 	      		// Links zu Lernaktivitäten über start.php umleiten
				 	      		if(dasisSession["case_collection"] != 0 || dasisSession["adaption"] != 0){
					    	   		Y.all("div.mod-indent a").on("click", this.redirect_activity_links);
					    	   		Y.all("div.box.generalbox.center.clearfix a").on("click", this.redirect_activity_links);
					    	   		Y.all("div.block_navigation.block li.type_activity a").on("click", this.redirect_activity_links);
					    	   	}
				 	      		
				 	      	}, this)
				 	   	}
				 	 });
				 	 
 				}, this));
 				
 			}, this)
 		}
 	});
 	
 	
 	
 };

/*! Case Retrieval */

/*
 * get the current case
 * 
 * @param  	 callback
 * @callback json object
 */
 M.block_case_repository.get_current_case = function(callback) {
 	this.Y.io(M.cfg['wwwroot']+"/blocks/case_repository/get_current_case.php", {
 		on: {
 			success: function(id, o) {
 				callback(JSON.parse(o.responseText));
 			}
 		}
 	});
 };

/* 
 * copy cases table of online database to local storage for later use
 *
 */
 M.block_case_repository.all_cases_to_local_storage = function() {
 	// DB-Abfrage mit success callback
  	this.Y.io(M.cfg['wwwroot']+"/blocks/case_repository/get_all_cases.php", {
 		on: {
 			success: this.Y.bind(function(id, o) {
            	localStorage.setItem("ilms_cases", o.responseText);
            	localStorage.setItem("db_time", ""+(new Date().getTime()));
 			}, this)
 		} 
 	});
 };
 
 /*
  * get all cases
  *
  * @return array of json objects
  */
  M.block_case_repository.get_all_cases = function() {
  	var all_cases = JSON.parse(localStorage.getItem("ilms_cases"));
  	return all_cases;	
  }
  
 /* 
  * retrieve cases
  * 
  * @param Object 	similarcase
  * @param int 		count
  * @return Array	count cases similar to similarcase
  */
  M.block_case_repository.retrieve_cases = function(similarcase, count) {
  	var result = {
  		best_case: null,
  		worst_case: null,
  		solutions: new Object()
  	};
  	
  	var all_cases = this.get_all_cases();
  	this.Y.Object.each(all_cases, this.Y.bind(function(value, key){
  		var the_case = JSON.parse(value.serialized_case.replace("iLMSCase", "stdClass"));
  		the_case.id = value.id;
  		the_case.used_count = value.used_count;
  		the_case.appliance = value.appliance;
  		
  		var compare_result = this.compare_cases(similarcase, the_case);
  		
  		if(the_case.solution_activityid == null) {
  			//continue; // Keine Lösung des Falls, also weiter zum nächsten Fall
  			return;
  		} else {
  			if(!(the_case.solution_activityid in the_case.activity_meta)){
  				//continue; // Ungültige Lösung, also weiter zum nächsten Fall
  				return;
  			}
  			// Bestimme Mapping für Lösung
  			if(!(the_case.solution_activityid in compare_result.mapping21)){
  				//continue; // Zwar eine Lösung, aber kein Mapping für die Lösung auf diesen Fall vorhanden, also weiter zum nächsten Fall
  				return;
  			}
  			var mapped_key = compare_result.mapping21[the_case.solution_activityid];
  			if(mapped_key < 0) {
  				//continue; // Zwar eine Lösung, aber kein Mapping für die Lösung auf diesen Fall vorhanden, also weiter zum nächsten Fall
  				return;
  			}
  			var solution = similarcase.activity_meta[mapped_key];
  		}
  		the_case.similarity = compare_result.similarity;
  		the_case.similarity_activity_meta = compare_result.similarity_activity_meta;
  		the_case.similarity_history = compare_result.similarity_history;
  		the_case.similarity_learner_meta = compare_result.similarity_learner_meta;
  		the_case.similarity_relations = compare_result.similarity_relations;
  		the_case.similarity_states = compare_result.similarity_states;
  		the_case.similarity_current_activity = compare_result.similarity_current_activity;
  		if(this.ilms_globals.enable_reuse_adjustments) {
  			// Reuse-Schritt: Eignung des Falls prüfen, ggf. Fallparameter adjustieren oder Fall verwerfen
  			if(!this.reuse_case(similarcase, the_case)) {
  				//continue;
  				return;
  			}
  		}
  		the_case.mappingn = compare_result.mapping12;
  		the_case.reverse_mapping = compare_result.mapping21;
  		the_case.history_mapping = compare_result.history_mapping;
  		// 1. Ergebnis: Bestimmt des besten passenden Fall (für RETAIN / STORE benötigt)
  		if(result.best_case == null || compare_result.similarity > result.best_case.similarity) {
  			result.best_case = the_case;
  		}
  		// 2. Ergebnis: Bestimme den am wenigsten passenden/repräsentativen Fall (für RETAIN / STORE benötigt)
  		var similarity_factor = compare_result.similarity*(Math.log(the_case.used_count)/Math.LN10 + 1); // Hier wird die Häfigkeit (used_count) in logarithmisierter Form mitberücksichtigt, damit oft verwendete Fälle nicht so leicht aus der Fallbasis entfernt werden.
  		if(result.worst_case == null || similarity_factor < result.worst_case.similarity_factor) {
  			result.worst_case = the_case;
  			result.worst_case.similarity_factor = similarity_factor;
  		}
  		// 3. Bestimmem für möglichst jede Folgelernaktivität bis zu n = 3 ähnlichste Fälle
  		if(!(solution.id in result.solutions)) {
  			result.solutions[solution.id] = new Array();
  		}
  		if(result.solutions[solution.id].length < count) {
  			result.solutions[solution.id].push(the_case);
  		} else {
  			this.Y.Array.each(result.solutions[solution.id], function(value, key){
  				if(compare_result.similarity > value.similarity) {
  					for(var j = parseInt(key)+1; j < count; ++j) {
  						result.solutions[solution.id][j] = result.solutions[solution.id][j-1];
  					}
  					result.solutions[solution.id][key] = the_case;
  					//break;
  					return;
  				}
  			});
  		}
  	}, this));
  	
  	return result;
  }
  
 /* 
  * reuse case
  * 
  * @param Object 	similarcase
  * @param Object 	the_case
  * @return bool	true, falls die Lösung des Referenzfalls zur Lösung des Suchfalls geeignet ist
  */
  M.block_case_repository.reuse_case = function(similarcase, the_case){
  	this.Y.Object.each(similarcase.learner_meta, this.Y.bind(function(value, key){
  		var a = value.attribute;
  		if(!(a in this.ilms_globals.reuse_learner_actions)) {
  			//continue;
  			return;
  		}
  		var rule = this.ilms_globals.reuse_learner_actions[a];
  		// Bestimme den Vergleichswert
  		switch(rule["COMPARETO"]){
  			case "activity":
  				if(the_case.solution_activityid == null) {
  					//continue 2;
  					return;
  				}
  				var compare_value = the_case.activity_meta[the_case.solution_activityid][a];
  				break;
  			case "learner":
  				var compare_value = (key in the_case.learner_meta) ? the_case.learner_meta[key].value : null;
  				break;
  			default: return; //continue 2;
  		}
  		
  		// Fallunterscheindung der einzelnen Typen von Bedingungen, Auswertung der Bedingung
  		if(value.value == null || compare_value == null) {
  			//continue;
  			return;
  		}
  		switch(rule["DIFFTYPE"]) {
  			case "gt":
  				var condition = value.value > compare_value + rule["LIMIT"];
  				break;
  			case "lt":
  				var condition = value.value < compare_value - rule["LIMIT"];
  				break;
  			case "diff":
  				var condition = Math.abs(value.value - compare_value) > rule["LIMIT"];
  				break;
  			default: condition = false;
  		}
  		
  		// Falls Bedingung eingetreten -> Aktion ausführen
  		if(condition) {
  			the_case.action = rule["ACTION"]; // Die zuletzt angewendete Aktion ist diejenige, die für den gesamten Fall gilt
  			switch(rule["ACTION"]) {
  				case "reject": return false;
  				case "adjust":
  					the_case.similarity = the_case.similarity*(1.0-rule["STRENGTH"]);
  			}
  		}
  	}, this));
  	return true;
  }
  
 /* 
  * revise cases
  * 
  * @param Object 	solutions_to_revise (als assoziatives Array)
  * @return Object	ein Object (als assoziatives Array), welches jeder Lösungs-Lernaktivität eine Bewertung zuweist
  */
  M.block_case_repository.revise_cases = function(solutions_to_revise){
  	var solutions = new Object();
  	// Berechne die Bewertungen und markiere das Maximum
  	var max_appliance = -Infinity;
  	var max = null;
  	
  	this.Y.Object.each(solutions_to_revise, this.Y.bind(function(value, key){
  		var the_new = {
  			id: key,
  			appliance: 0.0,
  			maximum: false
  		};
  		var count = 0;
  		// Aktuelle Bewertungsfunktion: Nach Häufigkeit gewichtetes arithmetisches Mittel üder dem Produkt aus SIMILARITY und APPLIANCE
  		this.Y.Object.each(value, function(sol, s){
  			the_new.appliance += sol.similarity*sol.appliance;
  			count++;
  		});
  		the_new.appliance = the_new.appliance/count;
  		the_new.count = count;
  		if(the_new.appliance >= max_appliance) {
  		    max_appliance = the_new.appliance;
  		    max = key;
  		}
  		solutions[key] = the_new;
  	}, this));
  	if(max != null){
  		solutions[max].maximum = true;
  	}
  	return solutions;
  }


/*! Case Compare */
 
 /* 
  * compare cases
  * 
  * @param Object 	erster Fall
  * @param Object 	zweter Fall, mit dem der erste verglichen werden soll
  * @return Object	ein Objekt mit den beiden Attributen similarity und mapping
  */
  M.block_case_repository.compare_cases = function(case1, case2){
  	// Spare mir die Klassendefinition und lege das Object direkt als JSON an
  	result = {
  		similarity: null,
  		similarity_learner_meta: null,
  		similarity_activity_meta: null,
  		similarity_history: null,
  		similarity_relations: null,
  		similarity_states: null,
  		similarity_current_activity: null,
  		mapping12: null,
  		mapping21: null,
  		history_mapping: null
  	};
  	
  	// Lese Metadaten aus und vergleiche sie
  	result.similarity_learner_meta = this.compare_learner_meta(this.analyze_learner_meta(case1.learner_meta), this.analyze_learner_meta(case2.learner_meta));
  	// Vergleiche die Metadaten und Zustände der Lernaktivitäten im Kurs
  	if(this.Y.Object.size(case1.activity_meta) < 1 || this.Y.Object.size(case2.activity_meta) < 1){
  		result.mapping12 = new Object();
  		result.mapping21 = new Object();
  	} else {
  		var r = this.compare_best_matching_activities(this.Y.Object.values(case1.activity_meta), this.Y.Object.values(case2.activity_meta));
  		result.similarity_activity_meta = r.sum/Math.max(Math.min(this.Y.Object.size(case1.activity_meta), this.Y.Object.size(case2.activity_meta)), 1);
  		result.mapping12 = new Object();
  		result.mapping21 = new Object();
  		this.Y.Object.each(r.partner, function(r2, r1){
  			result.mapping12[r1] = r2;
  			result.mapping21[r2] = r1;
  		});
  	}
  	
  	// Vergleiche die Historie der Fälle
  	var r2 = this.compare_history(this.Y.Object.values(case1.history), this.Y.Object.values(case2.history), result.mapping12);  	result.similarity_history = r2.sum/Math.max(this.Y.Object.size(case1.history), this.Y.Object.size(case2.history), 1);
  	result.similarity_current_activity = this.compare_current_activity(this.Y.Object.values(case1.history), this.Y.Object.values(case2.history), result.mapping12);
  	result.history_mapping = r2.mapping;
  	// Vergleiche im semantischen Netz
  	result.similarity_relations = this.compare_activity_relations(this.Y.Object.values(case1.relations), this.Y.Object.values(case2.relations), result.mapping12);
  	// Vergleichen der Zustände
  	result.similarity_states = this.compare_states(case1.activity_meta, case2.activity_meta, result.mapping12);
  	// Gib das Ergebnis zurück
  	result.similarity = this.ilms_globals.weight_factor_learnermeta*result.similarity_learner_meta+this.ilms_globals.weight_factor_activitymeta*result.similarity_activity_meta+this.ilms_globals.weight_factor_current_activity*result.similarity_current_activity+this.ilms_globals.weight_factor_history*result.similarity_history+this.ilms_globals.weight_factor_follow_relations*result.similarity_relations+this.ilms_globals.weight_factor_states*result.similarity_states;
  	return result;
  }
  
 /* 
  * analyze learner meta
  * 
  * @param Array 	learner_meta Array mit den Metadatensätzen aus der Tabelle ilms_case_usermeta
  * @return Array	Ein Array, das allen vorhandenen Attributentypen ein weiteres Array der Form Attribut => Wert mit allen Metadateninformationen der Eingabe zuweist
  */
  M.block_case_repository.analyze_learner_meta = function(learner_meta) {
  	var result = new Object();
  	this.Y.Array.each(learner_meta, function(u){
  		if(u.attribute in result) {
  			// Annahme, dass es zu jedem Attribut nur einen Wert gibt
  			// -> in current_case() findet eine SQL-Aggregation statt, damit dies für den Vergleichsfall sichergestellt wird
  			result[u.attribute][u.subtype] = u.value;
  		} else {
  			var arr = new Object();
  			arr[u.subtype] = u.value;
  			result[u.attribute] = arr;
  		}
  	});
  	return result;
  }
  
 /* 
  * compare learner meta
  * 
  * @param Object 	learner_meta1 Objekt mit den Metadaten der Lerner aus Fall 1 als Attribute
  * @param Object 	learner_meta2 Objekt mit den Metadaten der Lerner aus Fall 2 als Attribute
  * @return float	Ähnlichkeit als numerischer Wert im Intervall [0;1]
  */
  M.block_case_repository.compare_learner_meta = function(learner_meta1, learner_meta2){
  	var result = 0.0;
  	this.Y.Object.each(this.ilms_globals.weights_learnermeta, this.Y.bind(function(w, t){
  		// Für jeden Attributtyp: Mengenvergleich
  		var exists1 = t in learner_meta1;
  		var exists2 = t in learner_meta2;
  		if(exists1 == exists2) {
  			if(!exists1 && !exists2 || this.Y.Object.size(learner_meta1[t]) == 0 && this.Y.Object.size(learner_meta2) == 0) {
  				// Falls in den beiden Metadaten keine Attribute für diesen Typ vorhanden sind, sind beide gleich -> HIER Gleichheit und weiter, da sonst später Division durch 0
  				result += w;
  				//continue;
  				return;
  			}
  			var schnittmenge_count = 0;
  			var schnittmenge_value = 0;
  			this.Y.Object.each(learner_meta1[t], function(v, a){
  				if(a in learner_meta2[t]) {
  					// LOP-1 geändert: Differenzvergleich im Intervall [0;1] statt Identitätsvergleich
  					schnittmenge_count += 1;
  					if(v == null || learner_meta2[t][a] == null) {
  						schnittmenge_value += 1;
  					} else {
  						if(t == "age") {
  							schnittmenge_value += 1-Math.abs(Math.log(v)/Math.LN10-Math.log(learner_meta2[t][a])/Math.LN10)/(Math.log(100)/Math.LN10-Math.log(5)/Math.LN10);
  						} else {
  							schnittmenge_value += 1-Math.abs(v-learner_meta2[t][a]);
  						}
  					}
  				}
  			});
  			result += w*schnittmenge_value/(this.Y.Object.size(learner_meta1[t])+this.Y.Object.size(learner_meta2[t])-schnittmenge_count);
  		}
  	}, this));
  	return result;
  }
  
 /* 
  * compare activity
  * 
  * @param Object 	activity1 Objekt Erste zu vergleichende Lernaktivität (mit den üblichen Attributen der Tabelle dasis_modmeta)
  * @param Object 	activity2 Objekt Zweite zu vergleichende Lernaktivität
  * @return float	Ähnlichkeit als numerischer Wert im Intervall [0;1]
  */
  M.block_case_repository.compare_activity = function(activity1, activity2){
  	// Vergleiche die einzelnen Attribute
  	var result = 0.0;
  	if(activity1 == null || activity2 == null) {
  		return activity1 == null && activity2 == null;
  	}
  	
  	this.Y.Object.each(this.ilms_globals.weights_activitymeta, function(w, t){
  		if(activity1[t] || activity2[t]) {
  			result += w;
  			//continue;
  			return;
  		}
  		switch(t) {
  			case "learnining_time":
  				result += w*sim_lower_bound(activity1[t], activity2[t]);
  				break;
  			case "age":
  				result += w*sim_logarithm(activity1[t], activity2[t], 5.0, 100.0);
  				break;
  			case "language":
  			case "id":
  			case "module":
  			case "state":
  				if(activity1[t] === activity2[t]) {
  					result += w;
  				}
  				break;
  			default:
  				result += w*(1-Math.abs(activity1[t]-activity2[t]));
  		}
  	});
  	return result;
  }
  
 /* 
  * compare best matching activities
  * 
  * @param Array 	activities1 Ein Array mit Lernaktivitäts-Metadaten aus dem ersten zu vergleichenden Fall
  * @param Array 	activities2 Ein Array mit Lernaktivitäts-Metadaten aus dem zweiten zu vergleichenden Fall
  * @return Object	EIn Objekt mit den beiden Attributen sum (Übereinstimmung der Fälle) und partner (bestes Matching)
  */
  M.block_case_repository.compare_best_matching_activities = function(activities1, activities2){
  	// Initialisieren Teil 1
  	var result = {
  		sum: 0.0,
  		partner: new Object()
  	};
  	
  	// Arrays mit ids der Lernaktivitäten als Key und Value für späteres Mapping erstellen
  	activity_ids1 = new Object();
  	this.Y.Array.each(activities1, function(activity){
  		activity_ids1[activity.id] = activity.id;
  	});
  	activity_ids2 = new Object();
  	this.Y.Array.each(activities2, function(activity){
  		activity_ids2[activity.id] = activity.id;
  	});
  	// Schnittmenge der Lernaktivitäten ermitteln und das Mapping für diese Lernaktivitäten schon mal setzen
  	var activity_intersection = new Object();
   	if(this.Y.Object.size(activity_ids1) >= this.Y.Object.size(activity_ids2)) {
  		this.Y.Object.each(activity_ids2, function(v, k){
  			if(k in activity_ids1) {
  				activity_intersection[k] = v;
  			}
  		});
  	} else {
  		this.Y.Object.each(activity_ids1, function(v, k){
  			if(k in activity_ids2) {
  				activity_intersection[k] = v;
  			}
  		});
  	}
  	result.sum += this.Y.Object.size(activity_intersection); // Alle Kanten werden mit 1 bewertet
  	result.partner = activity_intersection; // Die Schnittmenge sind feste Partner
  	
  	// Differenzen der Lernaktivitäten der Fälle zu deren Schnittmenge ermitteln
  	var diff1 = this.Y.Array.reject(this.Y.Object.values(activity_ids1), function(item){
  		if(item in activity_intersection) {
  			return true;
  		} else {
  			return false;
  		}
  	});
  	var diff2 = this.Y.Array.reject(this.Y.Object.values(activity_ids2), function(item){
  		if(item in activity_intersection) {
  			return true;
  		} else {
  			return false;
  		}
  	});
  	
  	// --- Nun den Algorithmus von Gert Sauerstein auf die Differenzen anwenden ---
  	
  	// Initialisieren Teil 2
  	var V = diff1.length+diff2.length;
  	var diff1_activities = new Array();
  	this.Y.Array.each(diff1, function(d1){
  		diff1_activities.push(activities1[d1]);
  	});
  	var diff2_activities = new Array();
  	this.Y.Array.each(diff2, function(d2){
  		diff2_activities.push(activities2[d2]);
  	});
  	var adjacence = this.get_adjacence_matrix(diff1_activities, diff2_activities);
  	var temp_partner = new Object();
  	
  	for(var i=0; i<V; ++i) {
  		temp_partner[i] = -1;
  	}
  	
  	// Alle Knoten aus S1 durchlaufen: Für jeden Knoten aus S1 kann es höchstens 1 maximalen augmentierten Pfad geben
  	for(var vStart=0; vStart<diff1.length; ++vStart) {
  		// Initialisiere Kanten-Markierungs-Array
  		var scanned = new Array();
  		for(var i=0; i<V; ++i) {
  			scanned[i] = new Array();
  			for(var j=0; j<V; ++j) {
  				scanned[i][j] = false;
  			}
  		}
  		// Suche nach maximalem augemtierten Pfad (Breitensuche mit Prioritätswarteschlange)
  		var found = new PriorityQueue();
  		var queue = new Queue();
  		queue.insert(new AugmentingPath(vStart));
  		while(queue.elements.length > 0) {
  			var path = queue.delete_next();
  			var v = path.vertex;
  			// Suche alle Nachfolgeknoten und füge sie zur Queue hinzu
  			for(var u=0; u<V; ++u) {
  				if(u==v || scanned[v][u] || adjacence[v][u]==0 || ((path.size%2)==1)==(temp_partner[v]==u)) {
  					continue; //Kante wurde schon besucht und wird übersprungen
  				}
  				scanned[v][u] = true;
  				// Pfad mit neuer Kante für nächste Rekursionsebene erstellen und zur Queue hinzufügen
  				var newPath = new AugmentingPath(u, path.size+1, path.sum+adjacence[v][u], path);
  				queue.insert(newPath);
  				// Prüfe, ob der Zielknoten frei ist -> es wurde ein vergrößernder Pfad gefunden
  				if(temp_partner[u] < 0) {
  					found.insert(newPath);
  				}
  			}
  		}
  		var path = found.delete_max();
  		
  		if(path != null) {
  			// Falls der Pfad die Zuordnung vergrößert, wird er übernommen
  			if(path.sum > 0.0) {
  				result.sum += path.sum;
  				// Übernahme des Pfads durch Umwandeln von GEBUNDENEN in FREIE KANTEN (und umgekehrt)
  				var newPartner = new Object();
  				for(var i=0; i<V; ++i) {
  					newPartner[i] = temp_partner[i];
  				}
  				for(var v=path; v.next != null; v=v.next) {
  					newPartner[v.vertex] = -1;
  					newPartner[v.next.vertex] = -1;
  				}
  				for(var v=path; v.next != null; v=v.next) {
  					if(temp_partner[v.vertex] < 0 || temp_partner[v.vertex] != v.next.vertex) {
  						newPartner[v.vertex] = v.next.vertex;
  						newPartner[v.next.vertex] = v.vertex;
  					}
  				}
  				temp_partner = newPartner;
  			}
  		}
  	}
  	this.Y.Object.each(temp_partner, function(tp_val, tp_key){
  		result.partner[tp_key] = tp_val;
  	});
  	
  	return result;
  }

/* 
 * get adjacence matrix
 * 
 * @param Array 	activities1 Ein Array mit Lernaktivitäts-Metadaten aus dem ersten zu vergleichenden Fall
 * @param Array 	activities2 Ein Array mit Lernaktivitäts-Metadaten aus dem zweiten zu vergleichenden Fall
 * @return Array	Ein zweidimensionales Array mit den Kantengewichten (Ähnlichkeitsmaße zwischen jeweils zwei Lernaktivitäten)
 */
 M.block_case_repository.get_adjacence_matrix = function(activities1, activities2){
 	var result = new Array();
 	var V1 = activities1.length;
 	var V2 = activities2.length;
 	var V = V1 + V2;
 	for(var i=0; i<V; ++i) {
 		result[i] = new Array();
 		for(var j=0; j<V; ++j) {
 			result[i][j] = 0.0;
 			if(i<V1 && j>=V1) {
 				result[i][j] = this.compare_activity(activities1[i], activities2[j-V1]);
 				if(result[i][j] < this.ilms_globals.ilms_edge_limit) {
 					result[i][j] = 0;
 				}
 				continue;
 			}
 			if(i >= V1 && j < V1) {
 				result[i][j] = -result[j][i];
 			}
 		}
 	}
 	return result;
 }
 
/* 
 * compare_current_activity
 * 
 * @param Array 	history1 Historie des ersten Falls, welcher die aktuelle Lernaktivität als Schlüssel mit höchstem Wert enthält
 * @param Array 	history2 Historie des zweiten Falls, welcher die aktuelle Lernaktivität als Schlüssel mit höchstem Wert enthält
 * @param Array		mapping12 Bestmögliches Mapping der Lernaktivitäten des ersten Falls auf Lernaktivitäten des zweiten Falls
 * @return float	Ähnlichkeit als numerischer Wert im Intervall [0;1]
 */
 M.block_case_repository.compare_current_activity = function(history1, history2, mapping12){
 	var c1 = history1.length;
 	var c2 = history2.length;
 	if(c1 < 1 && c2 < 1) {
 		return 1.0;
 	}
 	if(c1 < 1 || c2 < 1) {
 		return 0.0;
 	}
 	var activity1 = history1[c1-1];
 	var activity2 = history2[c2-1];
 	if((activity1.id in mapping12) && mapping12[activity1.id] >= 0) {
 		return mapping12[activity1.id] == activity2.id ? 1.0 : 0.0;
 	} else {
 		return 0.0;
 	}
 }

/*
 * Vergleicht die Beziehungen zwischen den Folgelernaktivitäten und der jeweils aktuellen Lernaktivität im semantischen Netz.
 * @param   Array   relations1     Ein Array mit Daten für bei der aktuellen Lernaktivität des ersten Falls beginnenden Beziehungen
 * @param   Array   relations2     Ein Array mit Daten für bei der aktuellen Lernaktivität des zweiten Falls beginnenden Beziehungen
 * @param   Array   mapping12        Über MAXIMUM SET SIMILARITY bestimmtes bestmögliches Mapping der Folgelernaktivitäten (Schlüssel und Werte sind IDs der Lernaktivitäten)
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
 M.block_case_repository.compare_activity_relations = function(relations1, relations2, mapping12) {
     var rel1 = this.get_relation_array(relations1);
     var rel2 = this.get_relation_array(relations2);
     if(rel1.length < 1 && rel2.length < 1) {
     	return 1.0;
     }
     var schnittmenge = 0;
     var r1_count = 0;
     var r2_count = 0;
     // Durchlaufe alle semantische Beziehungen aus Fall 1 und prüfe auf Entsprechungen in Fall 2
     this.Y.Array.each(rel1, this.Y.bind(function(relations, a){
     	if(!(a in mapping12) || (a2 = mapping12[a]) < 0) {
     		//continue;
     		return;
     	}
        c1 = relations.length;
        r1_count += c1;
        c2 = (a2 in rel2) ? rel2[a2].length : 0;
        r2_count += c2;
        if(c1 > 0 && c2 > 0) {
        	this.Y.Array.each(relations, function(r){
        		if(rel2[a2].indexOf(r) > -1) {
        	   		++schnittmenge;
             	}
        	});
        }
     }, this));
     // Normalisiere die Übereinstimung in Abhängigkeit der Anzahl Lernaktivitäten im Matching
     return schnittmenge == 0 ? 0.0 : schnittmenge/(r1_count+r2_count-schnittmenge);        	
 }

/*
 * Analysiert ein Array mit semantischen Beziehungen (wird intern in compare_activity_relations() benutzt).
 * @param Array relations  Ein Array mit Daten für bei der aktuellen Lernaktivität des Falls beginnenden semantischen Beziehungen. Jedes Element ist ein Objekt mit den Eigenschaften activityid (ID der Ziel-Lernaktivität) und semantic_type (Art der Beziehung). 
 * @return Array Ein assoziatives Array, das der ID jeder Lernaktivität des Kurses ein (nicht-assoziatives) Array mit alle semantischen Beziehungen zuweist, die zwischen der aktuellen Lernaktivität und der jeweiligen Lernaktivität bestehen.
 */
 M.block_case_repository.get_relation_array = function(relations) {
     var result = new Array();
     this.Y.Object.each(relations, function(r){
     	if(r.activityid in result) {
            result[r.activityid].push(r.semantic_type);
        } else {
      		result[r.activityid] = new Array(r.semantic_type);
        }
     });
     return result;
 }

/*
 * Vergleicht die Zustände der aktuellen und Folge-Lernaktivitäten zwischen zwei Fällen.
 * @param   Array   activities1    Array mit Objekten, die im Attribut state den Zustand der Lernaktivitäten des ersten Falls enthalten
 * @param   Array   activities2    Array mit Objekten, die im Attribut state den Zustand der Lernaktivitäten des zweiten Falls enthalten
 * @param   Array   mapping12      Über MAXIMUM SET SIMILARITY bestimmtes bestmögliches Mapping der Lernaktivitäten (Schlüssel und Werte sind IDs der Lernaktivitäten)
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
 M.block_case_repository.compare_states = function(activities1, activities2, mapping12) {
 	var result = 0.0;
    var activity_count = 0;
    if(this.Y.Object.size(activities1) < 1 && this.Y.Object.size(activities2) < 1) {
    	return 1.0;
    }
    this.Y.Object.each(activities1, function(a){
    	if((a2 = mapping12[a.id]) < 0) {
       		//continue;
       		return;
        }
        ++activity_count;
        
        if(a.state == null || activities2[mapping12[a.id]].state == null || a.state === activities2[mapping12[a.id]].state) {
            result += 1.0;
        }
    });
    return activity_count == 0 ? 0.0 : result/activity_count;
 }
 
/**
 * Vergleicht die Liste der bereits besuchten Lernaktivitäten nach dem HISTORY COMPARE-Algorithmus.
 * @param Array history1       Nach nullbasierten Schlüssel sortierte Liste mit History-Einträgen des 1. Falls
 * @param Array activities1    Liste mit Meta- und Zustandsdaten der Lernaktivitäten des 1. Falls // !(AS) Wird in der Funktion nicht verwendtet und deswegen von mir gelöscht
 * @param Array history2       Nach nullbasierten Schlüssel sortierte Liste mit History-Einträgen des 2. Falls
 * @param Array activities2    Liste mit Meta- und Zustandsdaten der Lernaktivitäten des 2. Falls // !(AS) Wird in der Funktion nicht verwendtet und deswegen von mir gelöscht
 * @param Array mapping12      Eine Mapping-Liste, welche den IDs von Lernaktivitäten aus dem 1. Fall IDs von Lernaktivitäten des 2. Falls zuweist, wenn sich diese ähnlich sind. Der Wert -1 wird verwendet, wenn die Lernaktivität keinen Partner besitzt, mit dem sie in Beziehung steht.
 * @return Object  Ein Objekt mit den Eigenschaften sum (Anzahl der übereinstimmenden Lernaktivitäten in der History) und mapping (Gefundene Zuordnung von Lernaktivitäten in der History).
 */
 M.block_case_repository.compare_history = function(history1, history2, mapping12) {
     var result = new Object();
     var solutions = new Array();
     var max_solution_index = null; 
     if(history1.length == 0 && history2.length == 0) {
         result.mapping = new Array();
         result.sum = 1.0;
         return result;
     }
     this.Y.Array.each(history1, this.Y.bind(function(h1){
     	if(!(h1.id in mapping12) || mapping12[h1.id] < 0) {
     		// Die Lernaktivität besitzt keine Entsprechung im 2. Fall -> ist irrelevant, überspringen
            //continue;
        	return;
     	}
     	if(solutions.length < 1) {
     		// Es wurde überhaupt noch keine Lösung gefunden -> Suche passende Lösung in Lernaktiväten des 2. Falls
     		this.Y.Array.each(history2, function(h2){
     			if(h2.id == mapping12[h1.id]) {
             		// Teillösung gefunden -> hinzufügen und weiter mit nächster Aktivität h1
             		var arr = new Array();
             		arr[h1.idx] = h2.idx;
                    solutions[0] = arr;
                    max_solution_index = 0;
                    //break;
                    return;
             	}
     		});
     	} else {
     		var min = null;
            var found = false;
     		// Falls schon einer oder mehrere Lösungen vorhanden sind, betrachte jede einzeln nacheinander
            this.Y.Array.each(solutions, function(sol, sol_index){
            	// Suche passende Lernaktivität, welche NACH der letzten gefundenen Lernaktivität liegt
                last = sol[sol.length-1];
                if(min == null || last < min) {
                    min = last;
                }
                for(var h2 = last + 1; h2 < history2.length; h2++) { //! (AS) hier war ein "<=". Änderung könnte große Folgen haben!
                	if(history2[h2].id == mapping12[h1.id]) {
                		// Gefundene Teillösung kann verbessert werden -> Erhöhe um 1 Kante und weiter mit nächster Aktivität $h1
                        solutions[sol_index][h1.idx] = h2;
                        if(max_solution_index == null || solutions[sol_index].length > solutions[max_solution_index].length) {
                        	max_solution_index = sol_index;
                        }
                        found = true;
                        break;
                	}
                }
            }); 
            
            // Falls überhaupt keine Lösung gefunden wird, suche nach einer neuen Lösung
            if(!found && !(min == null)) {
            	for(var h2 = 1; h2 < min; h2++) { //! (AS) hier war ein "<=". Änderung könnte große Folgen haben!
            		if(history2[h2].id == mapping12[h1.id]) {
            			// Neue Lösung erstellen
            			var arr = new Array();
            			arr[h1.idx] = h2;
                        //solutions[][h1.idx] = h2; // (GS) Der Maximum-Zähler braucht nicht erhöht zu werden, da die neue lediglich Lösung 1 zählt und damit zunächst kein Maximum wird
                        solutions.push(arr);
                        break;
            		}
            	}
            }
     	}
     }, this));
     if(max_solution_index == null) {
         result.mapping = new Array();
         result.sum = 0;
         return result;
     }
     result.mapping = solutions[max_solution_index];
     result.sum = solutions[max_solution_index].length;
     return result;
 }

/*
 * Bestimmt die logarithmische Ähnlichkeit zweier numerischer Werte
 * @param   float   value1 Erster zu vergleichender Wert
 * @param   float   value1 Zweiter zu vergleichender Wert
 * @param   float   a      Untere Schranke des Intervalls zulässiger Werte
 * @param   float   b      Obere Schranke des Intervalls zulässiger Werte
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
 M.block_case_repository.sim_logarithm = function(value1, value2, a, b) {
 	return 1.0-Math.abs(Math.log(value1)-Math.log(value2))/(Math.log(b)-Math.log(a));
 }

/*
 * Bestimmt die numerische Ähnlichkeit mit unterer Grenze a.
 * @param   float   value1 Erster zu vergleichender Wert
 * @param   float   value1 Zweiter zu vergleichender Wert
 * @param   float   a      Untere Schranke des Intervalls zulässiger Werte (Vorgabe: 0.0)
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
 M.block_case_repository.sim_lower_bound = function(value1, value2, a) {
 	a = (a != "undefined") ? a : 0.0;
 	var max = Math.max(value1, value2);
    //! Änderung von Andre Scherl: vermeide Division durch Null
    if(max-a == 0){
    	return 1.0;
    }else{
    	return 1.0-Math.abs(value1-value2)/(max-a);
    }
 }

/*
 * Bestimmt die numerische Ähnlichkeit mit unterer Grenze a.
 * @param   float   value1 Erster zu vergleichender Wert
 * @param   float   value1 Zweiter zu vergleichender Wert
 * @param   float   b      Obere Schranke des Intervalls zulässiger Werte
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
 M.block_case_repository.sim_upper_bound = function(value1, value2, b) {
     var min = Math.min(value1, value2);
     //! Änderung von Andre Scherl: vermeide Division durch Null
     if(b-min == 0){
     	return 1.0;
     }else{
     	return 1.0-Math.abs(value1-value2)/(b-min);
     }
 }

  
/*! Queues */ 

/*
 * AugmentingPath
 *
 * @param int 		vStart Startknoten/letzter Knoten des augmentierten Pfads
 * @param int 		initial_size Anfängliche Länge des Pfads
 * @param float		initial_sum Anfängliches Gewicht des Pfads
 * @param Object	remaining_path Restpfad
 */
 function AugmentingPath(vStart, initial_size, initial_sum, remaining_path) {
	this.vertex = vStart;
	this.size = (initial_size != "undefined") ? initial_size : 1;
	this.sum = (initial_sum != "undefined") ? initial_sum : 0.0;
	this.next = (remaining_path != "undefined") ? remaining_path : null;
 } 

/*
 * Priority Queue
 */
 function PriorityQueue() {
 	this.heap = new Array();
 	this.count = 0;
 }
/*
 * Liefert das Element mit der größten Priorität und entfernt es aus der Prioritätswarteschlange
 *
 * @return AugmentingPath Element mit der größten Priorität
 */
 PriorityQueue.prototype.delete_max = function() {
 	if(this.count == 0) {
 		return null;
 	}
 	// Bestimme das maximale/oberste Element im Heap
 	var head = this.elements[0];
 	// Ersetze das erste Element durch das Letzte und verkleinere den Heap
 	var last = --this.count;
 	// Suche nach einem neuen Platz für das kleinste Element im Heap, beginnend bei dem (zu löschenden) obersten Element
 	var current = 0;
 	var next = 1;
 	while(next < last){
 		// Wähle das größere der beiden Folgeelemente
 		if(next+1 < last && this.elements[next+1].sum > this.elements[next].sum){
 			++next;
 		}
 		// Tausche das größere Element mit dem zu löschenden Platz, falls notwendig
 		if(this.elements[next].sum <= this.elements[last].sum){
 			break;
 		} 
 		this.elements[current] = this.elements[next];
 		// Such bei diesem Kind weiter
 		current = next;
 		next = (next << 1) + 1;
 	}
 	// Fülle den zu löschenden Platz mit dem letzten Element und lösche dieses dafür
 	this.elements[current] = this.elements[last];
 	this.elements.splice(last, 1);
 	return head;
 }
/*
 * Fügt ein Element mizt der angegebenen Priorität in die Prioritätswarteschlange ein.
 *
 * @param AugmentingPath	element hinzuzufügendes Element.
 */
 PriorityQueue.prototype.insert = function(element) {
 	// Erzeuge einen neuen freien Platz für das neue Element nach dem letzten Element
 	var current = this.count++;
 	var next = (current-1) >> 1;
 	while((current > 0) && (element.sum > this.elements[next].sum)){
 		// Solange das letzte Element kleiner ist -> tauche es mit dem neuen freien Platz
 		this.elements[current] = this.elements[next];
 		// Suche rekursiv weiter oben im Baum
 		current = next;
 		next = (current - 1) >> 1;
 	}
 	// Füge das Element am freien Platz ein
 	this.elements[current] = element;
 }
 
/*
 * Queue
 */
 function Queue() {
 	this.elements = new Array();
 }
/*
 * Liefert das erste Element und entfernt es aus der Warteschlange
 * @return mixed 	das erste Element der Warteschlange
 */
 Queue.prototype.delete_next = function() {
 	return this.elements.shift();
 }
/*
 * Fügt ein Element am Ende der Warteschlange ein
 * @param mixed 	element hinzuzufügendes Element
 */
 Queue.prototype.insert = function(element) {
 	this.elements.push(element);	
 }
 
 
/*! Semantic Web Interaction */

/**
 * catch the activity links in course view and go over start.php
 */
 M.block_case_repository.redirect_activity_links = function(e){
    if(e.currentTarget.get("parentNode").hasClass("commands") == false){
    	var modlink = e.currentTarget.get("href");
    	e.currentTarget.set("href", "#");
    	var id = modlink.split("id=");
    	if(id[1]){
    		id = id[1].split("&");
    		id = id[0];
    		if(e.currentTarget.get("parentNode").hasClass("mod-indent")) {
    			document.location = M.cfg['wwwroot']+"/blocks/case_repository/start.php?id="+id+"&nav=cv";
    		} else {
    			if(e.currentTarget.get("parentNode").hasClass("tree_item")) {
    				document.location = M.cfg['wwwroot']+"/blocks/case_repository/start.php?id="+id+"&nav=tree";
    			} else {
    				document.location = M.cfg['wwwroot']+"/blocks/case_repository/start.php?id="+id+"&nav=txt";
    			}
    			
    		}
    	}
    }
 }

/*
 * Aktivate/Deactivate UI Elements
 */
 M.block_case_repository.activate_interface_elements = function () {
 	if(this.Y.one("#id_bundle_selection") != null) this.Y.one("#id_bundle_selection").set("disabled", ""); 
	if(this.Y.one("#id_path_select") != null) this.Y.one("#id_path_select").set("disabled", "");
	if(this.Y.one("#id_button_nextNode") != null) this.Y.one("#id_button_nextNode").set("disabled", "");
 }
 M.block_case_repository.deactivate_interface_elements = function () {
 	if(this.Y.one("#id_bundle_selection") != null) this.Y.one("#id_bundle_selection").set("disabled", "disabled"); 
	if(this.Y.one("#id_path_select") != null) this.Y.one("#id_path_select").set("disabled", "disabled");
	if(this.Y.one("#id_button_nextNode") != null) this.Y.one("#id_button_nextNode").set("disabled", "disabled");
 }
  
/*
 * Get best next learning activity
 *
 * @param Object	solutions
 * @return Object	best_activity
 */
 M.block_case_repository.get_best_next_activity = function (solutions, currentcase) {
 	var best_activity = new Object();
 	
 	var visited_activities = this.Y.Object.values(currentcase.history);
 	visited_activities.reverse();
 	
 	this.Y.Object.each(solutions, function(value, key){
 		// Die beste Lernaktivität ist die mit der höchsten Appliance und keine der letzten beiden besuchten Aktivtäten
 		if((value.appliance > best_activity.appliance || best_activity.appliance == null) && (visited_activities[0] != null && value.id != visited_activities[0].id) && (visited_activities[1] != null && value.id != visited_activities[1].id)) {
 			best_activity = value;
 		}
 	});
 	
 	return best_activity;
 }
  