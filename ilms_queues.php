<?php /* Funktionsbibliothek für spezielle Warteschlangen und Prioritätswarteschlangen
       *
	   * Copyright (C) 2007, Gert Sauerstein
  	   * Edited by Andre Scherl, 17.09.2012
       * You should have received a copy of the GNU General Public License
       * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
       */

/** Implementiert einen augmentierenden Pfad. */
class AugmentingPath {
            
    /** @var int Knotenindex des letzten Knotens im augmentierenden Pfad */
    var $vertex;
    /** @var int Anzahl der Kanten im augmentierenden Pfad */
    var $size;
    /** @var float Summe der Gewichte im augmentierenden Pfad */
    var $sum;
    /** @var Object Restpfad (wieder ein AugmentingPath-Objekt), mit dem der letzte Knoten über eine Kante verbunden ist */
    var $next;

    /**
     * Erzeugt einen neuen augmentierenden Pfad mit den angebenen Parametern.
     * @param int       $vStart         Startknoten/letzter Knoten des augmentierenden Pfads 
     * @param int       $inital_size    Anfängliche Länge des Pfads (Vorgabe 1 -- Pfad besteht nur aus dem Startknoten)
     * @param float     $inital_sum     Anfängliches Gewicht des Pfads (Vorgabe 0.0 -- keine Kanten im Pfad)
     * @param Object    $remaining_path Restpfad (Vorgabe null -- Pfad besteht nur aus dem Startknoten)
     */
    function AugmentingPath($vStart, $inital_size = 1, $inital_sum = 0.0, $remaining_path = null) {
        $this->vertex = $vStart;
        $this->size = $inital_size;
        $this->sum = $inital_sum;
        $this->next = $remaining_path;
    }
}

/** Implementiert eine Prioritätswarteschlange zur Suche nach MAXIMALen augmentierenden Pfaden
 *  mit Hilfe eines Binärbaums (Heap). */
class PriorityQueue {
	
    /** @var Array Heap der Prioritätswarteschlange */
	var $heap = array();
    /** @var int    Anzahl der Elemente der Prioritätswarteschlange */
    var $count = 0;    
    
	/**
	 * Liefert das Element mit der größten Priorität und entfernt es aus der Prioritätswarteschlange.
	 * @return AugmentingPath  Element mit der größten Priorität
	 */
	function delete_max() {
		if($this->count == 0) {
			return null;
		}
        // Bestimme das maximale/oberste Element im Heap
		$head = $this->elements[0];
        // Ersetze das erste Element durch das letzte und verkleinere den Heap
        $last = --$this->count;
        // Suche nach einem neuen Platz für das kleinste Element im Heap, beginnend bei dem (zu löschenden) obersten Element
        $current = 0;
        $next = 1;
        while($next < $last) {

            // Wähle das größere der beiden Folgeelemente
            if($next+1 < $last && $this->elements[$next+1]->sum > $this->elements[$next]->sum) {
                ++$next;
        	}

            // Tausche das größere Element mit dem zu löschenden Platz, falls notwendig
            if($this->elements[$next]->sum <= $this->elements[$last]->sum) {

            	break;
            }
            $this->elements[$current] = $this->elements[$next];
            // Suche an bei diesem Kind weiter
            $current = $next;
            $next = ($next << 1) + 1;

        }
        // Fülle den zu löschenden Platz mit dem letzten Element und lösche dieses dafür
        $this->elements[$current] = $this->elements[$last];
        unset($this->elements[$last]);
        return $head;
	}
    
	/**
	 * Fügt ein Element mit der angebenenen Priorität in die Prioritätswarteschlange ein.
	 * @param AugmentingPath     $element    Hinzuzufügendes Element
	 */
	function insert(&$element) {
		// Erzeuge einen neuen freien Platz für das neue Element nach dem letzten Element
        $current = $this->count++;
        $next = ($current-1) >> 1;
        while(($current > 0) && ($element->sum > $this->elements[$next]->sum)) {
        	// Solange das letzte Element kleiner ist -> tausche es mit dem neuen freien Platz
            $this->elements[$current] = $this->elements[$next];
            // Suche rekursiv weiter oben im Baum
            $current = $next;
        	$next = ($current-1) >> 1;
        }
        // Füge das Element am freien Platz ein
        $this->elements[$current] = $element;
	}    
}

/** Implementiert eine einfache Warteschlange für augmentierende Pfade mit Hilfe einer linearen Liste (Array). */
class Queue {
	
    /** @var Array Elemente der Warteschlange */
	var $elements = array ();
    
	/**
	 * Liefert das erste Element und entfernt es aus der Warteschlange
	 * @return mixed das erste Element der Warteschlange 
	 */
	function delete_next() {
		return array_shift($this->elements);
	}
    
	/**
	 * Fügt ein Element ans Ende der Warteschlange ein.
	 * @param mixed     $element    hinzuzufügendes Element
	 */
	function insert(&$element) {
		$this->elements[] = $element;
	}
}

?>
