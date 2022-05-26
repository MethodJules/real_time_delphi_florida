<?php

namespace Drupal\real_time_delphi\Traits;

trait QuestionTrait {

    /**
 * Gibt bereits definierte Antwort-Sets zurück.
 *
 * @return array
 *   Alle bereits definierten Antwort-Sets.
 */
protected function delphi_question_get_answer_sets() {
    $answerSets = array();
  
    $sql = "SELECT answer_id, button_id, title 
            FROM {question_buttons_title}
            WHERE question_id IN (SELECT question_id FROM {question})
            ORDER BY answer_id";
  
    $database = \Drupal::database();
    $query = $database->query($sql);
    $result = $query->fetchAll();
  
    // Zusammengehörige Einträge gruppieren
    foreach($result as $item) {
      $answerSets[$item->answer_id][$item->button_id] = $item->title;
    }
  
    // Doppelte Einträge entfernen und Sets nach Anzahl der Einträge sortieren
    $answerSets = array_unique($answerSets, SORT_REGULAR);
    array_multisort(array_map('count', $answerSets), SORT_ASC, $answerSets);
  
    return $answerSets;
  }
  
}