<?php
/**
 * Created by PhpStorm.
 * User: jonaskortum
 * Date: 16.02.17
 * Time: 10:21
 */

require_once("survey_question_evaluation.inc");


function survey_menu()
{

    $items['start_survey'] = array(
        'title' => t('Umfrage starten'),
        'page callback' => 'drupal_get_form',
        'access callback' => 'user_is_logged_in',
        'page arguments' => array('survey_start_form'),
        'file' => 'survey.form.survey_start.inc',
    );

    $items['survey/%'] = array(
        'title' => t('Umfrage starten'),
        'page callback' => 'drupal_get_form',
        'page arguments' => array('survey_start_form'),
        'access callback' => TRUE,
        'file' => 'survey.form.survey_start.inc',
    );

    $items['survey_question/%/%'] = array(
        'page callback' => 'drupal_get_form',
        'access callback' => TRUE,
        'page arguments' => array('survey_form'),
        'file' => 'survey_answer_question.inc'
    );

    $items['survey_question_evaluation/%/%'] = array(
        'page callback' => 'drupal_get_form',
        'access callback' => TRUE,
        'page arguments' => array('survey_question_evaluation_form'),
        'file' => 'survey_question_evaluation.inc'
    );

    $items['finish_survey/%'] = array(
        'title' => 'Ende der Umfrage',
        'access callback' => TRUE,
        'page callback' => 'drupal_get_form',
        'page arguments' => array('survey_finish_survey_form'),
        'file' => 'survey.form.survey_finish.inc',
    );

    $items['survey/tokens'] = array(
      'page callback' => 'drupal_get_form',
      'type' => MENU_NORMAL_ITEM,
      'title' => t('Survey Tokens'),
      'access arguments' => array('administer users'),
      'page arguments' => array('survey_token_form'),
      'file' => 'survey.form.survey_token.inc',
    );

    $items['survey/tokens/delete'] = array(
      'page callback' => 'drupal_get_form',
      'access arguments' => array('administer users'),
      'page arguments' => array('survey_tokens_delete_form'),
      'file' => 'survey.form.survey_delete_all_and_associated_answers.inc',
    );

    $items['survey/tokens/%/delete'] = array(
      'page callback' => 'drupal_get_form',
      'access arguments' => array('administer users'),
      'access callback' => TRUE,
      'page arguments' => array('survey_token_delete_form', 2),
    );

    $items['survey/configure'] = array(
      'page callback' => 'drupal_get_form',
      'title' => t('Config'),
      'access arguments' => array('administer users'),
      'page arguments' => array('survey_configure_form'),
      'file' => 'survey_configure.inc'
    );

  $items['survey/delete'] = array(
    'access arguments' => array('administer users'),
    'page callback' => 'drupal_get_form',
    'title' => t('Delete'),
    'page arguments' => array('survey_delete_form'),
    'file' => 'survey.form.survey_delete_all.inc'
  );

    $items['survey/feedback'] = array(
      'page callback' => 'drupal_get_form',
      'title' => t('Feedback Anmerkungen'),
      'access arguments' => array('administer users'),
      'page arguments' => array('survey_show_feedback_form'),
      'file' => 'survey_configure.inc',
    );

    $items['survey/feedback_experts'] = array(
      'page callback' => 'drupal_get_form',
      'title' => t('Feedback Experten'),
      'access arguments' => array('administer users'),
      'page arguments' => array('survey_show_feedback_experts_form'),
      'file' => 'survey_configure.inc',
    );

    $items['survey/tokens/download'] = array(
      'title' => t('Zugangsschlüssel CSV Export'),
      'page callback' => '_survey_token_csv_export_callback',
      'type' => MENU_CALLBACK,
      'access arguments' => array('administer users'),
    );



    return $items;
}



function survey_continue_survey($form, &$form_state) {
  $path = "survey_question/" . $form_state['#continue_qid'] . "/" . $form_state['#continue_uid'];
  $form_state['redirect'] = $path;
}




/*
 * Diese Funktion lädt alle verfügbaren Fragen der Umfrage aus der Datenbank und sortiert sie aufsteigend nach
 * Benutzer-Gewichtung und der ID
 */
function survey_get_all_questions()
{
    $questions = array();
    $question_result = db_query("SELECT * FROM {question} ORDER BY weight, question_id");

    foreach ($question_result as $question) {
        array_push($questions, $question);
    }

    return $questions;
}

/*
 * Diese Funktion ermittelt alle verfügbaren Antwortmöglichkeiten einer Frage und gibt sie sortiert nach der ID
 * aufsteigend zurück. Die ID der entsprechenden Frage wird als Parameter übergeben
 */
function survey_get_answers_from_question($question_id)
{
    $answers = array();
    $answer_result = db_query("SELECT * FROM {question_possible_answers} WHERE question_id = :question_id ORDER BY weight", array(
        'question_id' => $question_id
    ));
    foreach ($answer_result as $answer) {
        array_push($answers, $answer);
    }

    return $answers;
}


//Diese Funktion startet die Umfrage. Es wird ein einzigartiges Passwort für den Nutzer erstellt und in die DB
//geschrieben. Anschließend wird der Nutzer zur ersten Frage weitergeleitet.
function survey_start_survey()
{
    if(arg(0) === 'survey') {
      $user_check_string = arg(1);
    } else {
      $user_check_string = survey_create_token();
    }

    //Die erste Frage wird geladen
    $question_id = survey_get_questions(0);
    drupal_goto("survey_question/" . $question_id . "/" . $user_check_string);
}

//Diese Funktion generiert eine einzigartige ID für jeden Nutzer die 30 Stellen beinhaltet.
function survey_generate_random_string($length)
{
    $valid_characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $valid_characters = '0123456789abc';
    $characters_length = strlen($valid_characters);
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $valid_characters[rand(0, $characters_length - 1)];
    }
    return $random_string;
}

/**
 * Generates and saves a unique user token.
 *
 * @return string
 *   The generated unique user token.
 */
function survey_create_token() {
  //Einzigartige ID für den Nutzer wird angelegt...
  do {
    $user_check_string = survey_generate_random_string(30);
    $success = true;

    //...und abgespeichert
    try {
      db_query("INSERT INTO {survey_users} (user_pw) VALUES (:user_pw)", array(
        ':user_pw' => $user_check_string
      ));
    } catch (PDOException $e) {
      // generated token already used, try again
      $success = false;
    }
  } while (!$success);

  return $user_check_string;
}


/**
 * @param $token
 *   Unique string user token.
 *
 * @return string
 *   Full link to the survey.
 */
function survey_get_survey_link($token) {
  global $base_url;
  //$link = check_plain($base_url . '/survey/' . $token);

  $link = $base_url . '/survey/' . $token;
  dsm($link);
  return $link;
}






/**
 * Deletes a single survey token and the associated saved answers.
 */
function survey_token_delete_form($form, &$form_state, $token) {
  $result = _survey_delete_token($token);
  $noToken = $result[0];
  $noAnswers = $result[1];

  if ($noToken > 0) {
    drupal_set_message(t('@noToken Zugangsschlüssel (@token) inklusive @noAnswers abgegebener Antworten gelöscht.',
      array('@noToken' => $noToken, '@noAnswers' => $noAnswers, '@token' => $token)),
      'status');
  } else {
    drupal_set_message(t('Zugangsschlüssel @token unbekannt!',
      array('@token' => $token)),
      'error');
  }

  drupal_goto($path = 'survey/tokens');
}


/**
 * Exports survey links as a csv file.
 * @see http://drupalpeople.com/blog/output-data-csv-file-drupal-download
 */
function _survey_token_csv_export_callback($form, &$form_state) {
  // add necessary headers for browsers
  drupal_add_http_header('Content-Type', 'text/csv; utf-8');
  drupal_add_http_header('Content-Disposition', 'attachment; filename = survey_tokens.csv');

  //instead of writing down to a file we write to the output stream
  $fh = fopen('php://output', 'w');

  //form header
  fputcsv($fh, array(t('Token'), t('Link')));

  //write data in the CSV format
  foreach ($form['tokens_table']['#rows'] as $row) {
    fputcsv($fh, array($row['token'], $row['link']));
  }

  //close the stream
  fclose($fh);
  drupal_exit();
}


//Diese Funktion holt sich die zuletzt abgegebenen Antworten eines Nutzers zu einer bestimmten Frage aus der DB und gibt
//diese als Array zurück.
function survey_get_answers($question_id, $user_passcode)
{
    $answers = array();

    //Antworten eines Nutzers zu einer Frage werden geladen
    $answer_result = db_query("SELECT * FROM {question_user_answers} WHERE user_pw = :user_pw AND question_id = :question_id
                              ORDER BY question_user_answers_id ASC", array(
        ':user_pw' => $user_passcode,
        ':question_id' => $question_id
    ));

    foreach ($answer_result as $answer) {
        // Nur die zuletzt abgegebene Antwort zurückgeben.
        $answers[$answer->answer_id] = $answer;
    }

    return $answers;

}

//Diese Funktion überprüft, ob die eingelesene Nutzer-ID aus der Taskleiste valide ist.
function survey_check_valid_user_pw($user_pw)
{

    $counter = 0;
    $result = db_query("SELECT * FROM {survey_users} WHERE user_pw = :user_pw", array(
        ':user_pw' => $user_pw
    ));

    foreach ($result as $item) {
        $counter++;
    }

    if ($counter == 1) {
        return true;
    } else {
        return false;
    }
}

//Diese Funktion sucht sich anhand der aktuellen Fragen-ID die nächste Frage aus der DB und gibt diese zurück. Sollte
//keine Frage mehr folgen, wird eine -1 zurückgegeben.
function survey_get_questions($current_question_id)
{

    $questions = array();
    $question_result = db_query("SELECT * FROM {question} ORDER BY weight, question_id");

    foreach ($question_result as $question) {
        array_push($questions, $question->question_id);
    }

    $question_index = array_search($current_question_id, $questions);

    if (sizeof($questions) > $question_index) {
        return $questions[$question_index];
    } else {
        return -1;
    }
}

/**
 * Überprüft, ob es sich um eine gültige Benutzereingabe für das Jahre-Textfeldhandelt.
 * - Eingabe ist eine nichtnegative Ganzzahl
 * - Eingabe maximal 50 (Jahre)
 * - Eingabe auch als Jahreszahl maximal 50 Jahre in die Zukunft möglich
 *
 * Wurde 'keine Angabe' ausgewählt wird dies als 'answer_NA' codiert gespeichert.
 *
 * @param $element
 *   Das zu überprüfende Form-Element
 */
function survey_textfield_validate(&$element, &$form_state) {
  $value = $element['#value'];

  // extract the ID of the textfield to be validated to check the associated 'k. A.' checkbox
  $elementId = intval(preg_replace('/[^0-9]+/', '', $element['#name']), 10);

  $currentYear = date('Y');
  $yearRange = 50;

  // don't validate the field if 'keine Angabe' has been selected
  if ($form_state['values']['answer_check' . $elementId] === 1) {
    form_set_value($element, 'answer_NA', $form_state);
    $element['#value'] = 'answer_NA';

  // reject no input if 'keine Angabe' has not been selected ('answer_NA')
  } elseif ($value === '') {
    form_error($element, t('Das Feld „%name” ist erforderlich.', array(
      '%name' => $element['#title'],
    )));

  // reject negative and non-integer input
  } elseif ($value !== '' && (!is_numeric($value) || intval($value) != $value || $value < 0)) {
    form_error($element, t('„%name” muss eine nichtnegative Ganzzahl sein.', array(
      '%name' => $element['#title'],
    )));

  // accept input up to the year range (e.g. 0-50) and continue
  } elseif ($value >= 0 && $value <= $yearRange) {

  // accept input up to the valid year range (e.g. 50 years into the future 2017-2067) and convert it to years
  } elseif ($value >= $currentYear && $value <= $currentYear + $yearRange) {
    $convertedYear = $value - $currentYear;
    form_set_value($element, $convertedYear, $form_state);
    $element['#value'] = $convertedYear;

  // reject input outside the valid year range (e.g. 51-2016 and 2068+)
  } elseif ($value > $yearRange || $value < $currentYear || $value > $currentYear + $yearRange) {
    form_error($element, t('„%name” darf nicht in der Vergangenheit und maximal %yearRange Jahre in der Zukunft liegen.', array(
      '%name' => $element['#title'],
      '%yearRange' => $yearRange,
    )));
  }
}

/**
 * Validates that the textarea if not empty if NA is not selected.
 * If NA is selected encode it as 'answer_NA'.
 *
 * @param array $element
 *   The element to validate.
 * @param array $form_state
 *   The submitted form that contains the elements to validate.
 *
 */
function _survey_textarea_validate(&$element, &$form_state) {

  // extract the ID of the textarea to be validated
  $elementId = intval(preg_replace('/[^0-9]+/', '', $element['#name']), 10);

  // no validation necessary if NA is selected
  if ($form_state['values']['answer_check' . $elementId] === 1) {
    form_set_value($element, 'answer_NA', $form_state);
    $element['#value'] = 'answer_NA';

  } else if (empty($element['#value'])) {
    form_error($element, t('Das Feld „%field” ist erforderlich.', array(
        '%field' => $element['#title']
      )
    ));
  }

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
function _survey_get_question_type($questionId) {
  $type = '';
  try {
    $result = db_query("SELECT type FROM {question} WHERE question_id = :question_id", array(
      'question_id' => $questionId,
    ));
    $type = $result->fetchField();

  } catch (PDOException $e) {
    $type = 'error';
  }

  return $type;
}

/**
 * Returns the survey configuration data.
 *
 * @param $surveyId
 *  The survey ID to be looked up.
 *
 * @return array
 *  Associative array of the survey data.
 */
function _survey_get_survey($surveyId) {
  try {
    $result = db_query("SELECT * FROM {survey} WHERE survey_id = :surveyId", array(
      'surveyId' => $surveyId,
    ));
    $survey = $result->fetchAssoc();

  } catch (PDOException $e) {
    $survey = array();
  }

  return $survey;
}

/**
 * Returns the survey configuration data.
 *
 * @param $userId
 *  The user ID to be looked up.
 *
 * @return array
 *  Associative array of the user's data.
 */
function _survey_get_user($userId) {
  try {
    $result = db_query("SELECT * FROM {survey_users} WHERE user_pw = :userId", array(
      ':userId' => $userId,
    ));
    $user = $result->fetchAssoc();

  } catch (PDOException $e) {
    $user = array();
  }

  return $user;
}

/**
 * Saves the user's survey feedback.
 *
 * @param string $userId
 *   The user token.
 * @param string $feedback
 *   The user feedback to be saved.
 * @param string $experts
 *   The suggested experts to be saved.
 *
 * @return bool
 *   TRUE if the feedback has been saved, otherwise FALSE.
 */
function _survey_save_feedback($userId, $feedback, $experts) {
  $success = TRUE;
  try {
    db_query("UPDATE {survey_users} SET feedback = :user_feedback, feedback_experts = :user_experts
                WHERE user_pw = :userId", array(
      ':userId' => $userId,
      ':user_feedback' => $feedback,
      ':user_experts' => $experts,
    ));
  } catch (PDOException $e) {
    $success = FALSE;
  }

  return $success;
}

/**
 * Returns the first question the user did not answer.
 *
 * @param $userToken
 *  The unique user token.
 *
 * @return int
 *  The ID of the user's first unanswered question or -1 if all questions have been answered or -2 if an error occured.
 */
function _survey_get_first_open_question($userToken) {
  try {
    $result = db_query("
      SELECT q.question_id FROM {question} as q
      LEFT OUTER JOIN
        (SELECT question_id FROM {question_user_answers}
          WHERE user_pw = :userToken
          GROUP BY question_id
        ) as answer
      ON q.question_id = answer.question_id
      WHERE answer.question_id IS null
      ORDER BY q.weight ASC
      LIMIT 1", array(
        'userToken' => $userToken,
    ));

    $questionID = $result->fetchField(0);

    // false if all questions have been answered
    if ($questionID === FALSE) {
      $questionID = -1;
    }
  } catch (PDOException $e) {
    $questionID = -2;
  }

  return $questionID;
}

/**
 * Returns all answered questions of a user.
 *
 * @param $userToken
 *   The unique user token.
 *
 * @return array
 *   Associative array of all answered quesstions.
 */
function _survey_get_answered_questions($userToken) {
  $answeredQuestions = array();
  try {
    $result = db_query("
      SELECT question_id FROM {question_user_answers}
      WHERE user_pw = :userToken
      GROUP BY question_id", array(
        'userToken' => $userToken,
    ));

    $answeredQuestions = $result->fetchAllAssoc('question_id');

  } catch (PDOException $e) {
    $answeredQuestions = array();
  }

  return $answeredQuestions;
}

/**
 * Builds a status bar indicating how many questions have already been answered.
 *
 * @param $userToken
 *   The unique user token.
 *
 * @return string
 *   HTML code of the user's survey progress bar.
 */
function _survey_get_status_bar($userToken) {
  $questionsCount = count(survey_get_all_questions());
  $answeredCount = count(_survey_get_answered_questions($userToken));

  $progressPercent = $answeredCount / $questionsCount * 100;

  $html = '';
  $html .= '<div class="progress-bar">';
  $html .= '<div class="progress" style="width: ' . $progressPercent . '%">' . round($progressPercent) . '%</div></div>';

  return $html;
}

/**
 * @param string $token
 *   Unique user token.
 *
 * @return string
 *   Link to delete the token.
 */
function survey_get_token_delete_link($token) {
  global $base_url;
  $link = l(t('delete'), 'survey/tokens/' . $token . '/delete',
    array('attributes' => array('class' => array('token-delete',)))
  );

  return $link;
}

/**
 * Deletes the token and it's associated answers.
 *
 * @param string $token
 *  The user token to delete.
 *
 * @return array
 *   Returns the number of deleted tokens (0/1) and the number of deleted answers.
 */
function _survey_delete_token($token) {
  $countToken = 0;
  $countAnswers = 0;

  try {
    $countToken = db_delete('survey_users')
      ->condition('user_pw', $token)
      ->execute();

    if ($countToken > 0) {
      $countAnswers = db_delete('question_user_answers')
        ->condition('user_pw', $token)
        ->execute();
    }

  } catch (PDOException $e) {
    return array($countToken, $countAnswers);
  }
  return array($countToken, $countAnswers);
}

function _get_all_respondent_identifieres() {
    $query = "SELECT user_pw FROM {survey_users} GROUP BY user_pw ORDER BY user_id ASC;";
    $result = db_query($query);
    $respondent_identifieres = $result->fetchAll();

    return $respondent_identifieres;
}