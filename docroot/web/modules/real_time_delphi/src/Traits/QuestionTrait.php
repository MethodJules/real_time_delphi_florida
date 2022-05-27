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

  /**
  * Returns the type of a question.
  *
  * @param $questionId
  *  The question ID to be looked up.
  *
  * @return string
  *  The question type or -1 if the query failed.
  */
  protected function get_question_type($questionId) {
    $type = '';
    $database = \Drupal::database();
    $query = $database->select('question', 'q');
    $query->addField('q', 'type');
    $query->condition('question_id', $questionId);

    $type = $query->execute();

    return $type->fetchField();
  }

  /**
   * Get the last commited answer of a user
   */
  protected function get_answers($question_id, $user_passcode) {
    $answers = [];

    $database = \Drupal::database();
    $query = $database->select('question_user_answers', 'qba');
    $query->fields('qba');
    $query->condition('user_pw', $user_passcode);
    $query->condition('question_id', $question_id);
    $answer_result = $query->execute();

    foreach ($answer_result as $answer) {
        // Nur die zuletzt abgegebene Antwort zurückgeben.
        $answers[$answer->answer_id] = $answer;
    }

    return $answers;

  }

  /**
   * Get questions
   */
  protected function get_questions() {
    $database = \Drupal::database();
    $query = $database->select('question', 'q');
    $query->fields('q');
    $query->orderBy('weight');
    $query->orderBy('question_id');

    $question_result = $query->execute();

    foreach ($question_result as $question) {
      $questions[] = $question;
    }

    return $question;
  }

  /**
   * Get answers from question
   */
  protected function get_answers_from_question($question_id) {
    $database = \Drupal::database();
    $query = $database->select('question_possible_answers', 'qba');
    $query->fields('qba');
    $query->condition('question_id', $question_id);
    $answer_result = $query->execute();

    foreach ($answer_result as $answer) {
      $answers[] = $answer;
    }

    return $answers;
  }

  /**
   * Get button titles
   */
  protected function get_button_titles($button_title_result) {

    //In diese Variable werden die Antwortmöglichkeiten für jede Frage eingefügt
    $button_title_array = array();

    //Diese Variable zählt die Durchgänge innerhalb der foreach-Schleife
    $iterations = 0;

    //Diese Variable zählt die Antwortmöglichkeiten jeder Antwort-ID
    $response_option_counter = 0;

    //Diese Variable dient zum Vergleich zweier Antwort-IDs
    $answer_id = 0;

    //Diese Variable zählt die Anzahl der Antworten
    $answer_id_counter = 0;

    //Es wird über alle Antwortmöglichkeiten iteriert
    foreach ($button_title_result as $button_title) {

      //Sollte es mindestens der zweite Durchlauf sein:
      if ($iterations > 0) {

        //Sollte die ID der aktuellen Antwort mit der vorigen übereinstimmen:
        if ($button_title->answer_id == $answer_id) {

          //Die Antwortmöglichkeit wird um eins erhöht und eingefügt
          $response_option_counter++;
          $button_title_array[$answer_id_counter][$response_option_counter] = $button_title->title;
        } else {
          //Sollte die ID der aktuellen Antwort nicht mit der vorigen übereinstimmen:
          $response_option_counter++;
          $button_title_array[$answer_id_counter][$response_option_counter] = "do not know / no information";
          $response_option_counter = 0;
          $answer_id_counter++;
          $button_title_array[$answer_id_counter][$response_option_counter] = $button_title->title;
          $answer_id = $button_title->answer_id;
        }
      } else {

        //Im ersten Durchlauf wird das Array mit der ersten Antwort-ID und der ersten Antwortmöglichkeit befüllt.
        $button_title_array[$answer_id_counter][$response_option_counter] = $button_title->title;
        $answer_id = $button_title->answer_id;
      }
      $iterations++;
    }

    $response_option_counter++;
    $button_title_array[$answer_id_counter][$response_option_counter] = "do not know / no information";


    return $button_title_array;
  }




}

