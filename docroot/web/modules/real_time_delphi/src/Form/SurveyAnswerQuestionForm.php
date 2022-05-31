<?php

namespace Drupal\real_time_delphi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\real_time_delphi\Traits\QuestionTrait;

/**
 * Provides a Real Time Delphi form.
 */
class SurveyAnswerQuestionForm extends FormBase {
    use QuestionTrait;

    protected $database;

    public function __construct(Connection $database) {
        return $this->database = $database;
    }

    public static function create(ContainerInterface $container) {
        return new static (
        $container->get('database')
        );
    }

    public function getFormId()
    {
        return 'survey_question_answer_form';
    }

    // TODO: question id und user pass dynamisch machen
    public function buildForm(array $form, FormStateInterface $form_state, $question_id = 1, $user_pass = '92700a6218a7c745cc1ba13720b5a6') {
        // Get the user answers
        $user_answers = $this->get_answers($question_id, $user_pass);

        // check the question type
        $questionType = $this->get_question_type($question_id);
        if (!empty($user_answers) && $questionType != 'group') {
            // TODO: redirect site to
            // drupal_goto("survey_question_evaluation/" . $question_id . "/" . $user_pass);
        }

        // Load all available questions from the database
        $questions = $this->get_questions();
        $number_of_questions = false;
        $question_index = 0;
        $questionGroupIndex = 0;

        foreach ($questions as $q) {
            if ($q->question_id == $question_id) {
                $number_of_questions = true;
                break;
            }

            if ($q->type === 'group') {
              $questionGroupIndex++;
            }
            $question_index++;
        }


        if (!is_null($user_pass) && $number_of_questions) {
            // Load title of the question
            $text = $questions[$question_index]->title;
            $questionType = $questions[$question_index]->type;

            if ($questionType === 'group') {
                $headline = "
            <h1>Themenbereich " . ($questionGroupIndex + 1) . "</h1>
            <h2>" . $text . "</h2>";
            } else {
                $headline = "
            <h1>". $this->t('Thesis ') . $question_index - $questionGroupIndex + 1 . "</h1>
            <h2>" . $text . "</h2>";
            }

            // Load answers of the question from database
            $answers = $this->get_answers_from_question($questions[$question_index]->question_id);

            $query = $this->database->select('question_buttons_title', 'qbt');
            $query->fields('qbt');
            $query->condition('question_id', $questions[$question_index]->question_id, '=');
            $query->orderBy('answer_id');
            $query->orderBy('button_id');
            $result = $query->execute();

            // Load button titles
            $button_title_array =$this->get_button_titles($result);

            $form["question"]['title'] = array(
                '#type' => 'markup',
                '#title' => t('survey quesion title'),
                '#markup' => $headline,
                '#prefix' => '<div class="question-header">',
            );

             // Da Titel immer fix im Vordergrund steht muss die Content-Region um die Höhe des Titels verschoben werden
            $cssClassContent = 'question-content-wrapper';
            $titleLength = mb_strlen($text);

            if ($titleLength > 300) {
            $form['question']['title']['#suffix'] = '</div><div class="four-rows ' . $cssClassContent . '">';
            } else if ($titleLength > 200) {
            $form['question']['title']['#suffix'] = '</div><div class="three-rows ' . $cssClassContent . '">';
            } else if ($titleLength > 100) {
            $form['question']['title']['#suffix'] = '</div><div class="two-rows ' . $cssClassContent . '">';
            } else {
            $form['question']['title']['#suffix'] = '</div><div class="one-row ' . $cssClassContent . '">';
            }

            //Es wird über jede Antwortmöglichkeit iteriert
            $countTextfield = 0;
            for ($i = 0; $i < sizeof($answers); $i++) {

                //Sollte es bereits eine Antwort vom Nutzer in der Datenbank geben, wird diese als Standardwert
                //eingetragen
                if (!isset($user_answers[$i]->answer)) {
                    $default_value = "";
                } else {
                    $default_value = $user_answers[$i]->answer;
                }

                //Sollte es sich bei der Antwortmöglichkeit um Radio-Buttons handeln
                $questionType = $answers[$i]->question_type;
                if ($answers[$i]->isRadioButton) {
                    $form['dim' . $i] = array(
                        '#type' => 'radios',
                        '#prefix' => '<div id ="visualization' . $i . '" class = "answers">',
                        '#suffix' => '</div>',
                        '#title' => $answers[$i]->description,
                        '#required' => true,
                    // TODO quick fix
                    // $button_title_array enthält eine Eintrag pro Radio-Button Antwortmöglichkeit. Textfeld
                    // Antwortmöglichkeiten werden außer acht gelassen. Bei 5 Antwortmöglichkeiten von denen 3 Textfelder
                    // sind würde $answers 5 Eintrage haben, $button_title_array jedoch nur 2 (-> Undefined offset).
                    // $countTextfield soll dies kompensieren.
                        '#options' => $button_title_array[$i - $countTextfield],
                    );

                    // Antwort vorauswählen, wenn die Frage bereits beantwortet wurde
                    if (isset($user_answers[$i])) {
                    $form['dim' . $i]['#default_value'] = $user_answers[$i]->answer;
                    }

                    //Sollte es sich bei der Antwortmöglichkeit um ein Textfeld handeln
                } else if ($questionType === 'year') {
                    $countTextfield++;

                    // 'answer_NA' ist interne Codierung für 'keine Angabe' und soll nicht angezeigt werden.
                    // TODO Typ DB-Feld für Antworten ändern?!
                    $isChecked = FALSE;
                    if ($default_value === 'answer_NA') {
                    $default_value = '';
                    $isChecked = TRUE;
                    }

                    $form['answer' . $i] = array(
                        '#type' => 'textfield',
                        '#prefix' => '<div id ="visualization' . $i . '" class = "answers">',
                        '#title' => $answers[$i]->description,
                        '#size' => 4,
                        '#maxlength' => 4,
                        '#default_value' => $default_value,
                        '#required' => FALSE,
                        '#states'        => array(
                            'disabled' => array(
                                ':input[name="answer_check' . $i . '"]' => array('checked' => TRUE),
                            ),
                        ),
                    );
                    $form['answer_check' . $i] = array(
                    '#type' => 'checkbox',
                    '#title' => 'weiß nicht / keine Angabe',
                    '#attributes' => array(
                        'class' => array('text-checkbox')
                    ),
                    '#default_value' => $isChecked,
                    '#suffix' => '</div>',
                    );

                } else if ($questionType === 'text') {
                $countTextfield++;

                // 'answer_NA' ist interne Codierung für 'keine Angabe' und soll nicht angezeigt werden.
                $isChecked = FALSE;
                if ($default_value === 'answer_NA') {
                    $default_value = '';
                    $isChecked = TRUE;
                }

                $divClasses = 'answers ' . $questionType;

                $form['answer' . $i] = array(
                    '#type' => 'textarea',
                    '#prefix' => '<div id ="visualization' . $i . '" class = "' . $divClasses .'">',
                    '#title' => $answers[$i]->description,
                    '#resizable' => FALSE,
                    '#rows' => 2,
                    '#default_value' => $default_value,
                    '#required' => FALSE,
                    '#states'        => array(
                        'disabled' => array(
                            ':input[name="answer_check' . $i . '"]' => array('checked' => TRUE),
                        ),
                    ),
                );
                $form['answer_check' . $i] = array(
                    '#type' => 'checkbox',
                    '#title' => 'weiß nicht / keine Angabe',
                    '#attributes' => array(
                    'class' => array('text-checkbox')
                    ),
                    '#default_value' => $isChecked,
                    '#suffix' => '</div>',
                );
                } else if ($answers[$i]->question_type === 'knowledgeMap') {
                    $countTextfield++;

                    // TODO: implement knowledge map
                }
            }

            $form['content-wrapper-close'] = array(
                '#type' => 'markup',
                '#markup' => '</div>',
            );
      
            // Damit 'Enter' den Weiter-Button triggert, muss dieser zuerst angelegt werden. Sollten weitere Buttons
            // eingefügt werden, werden diese per CSS nach links verschoben
            $form['submit-wrapper-open'] = array(
                '#type' => 'markup',
                '#markup' => '<div class="buttons-submit">',
            );
            $form['submit'] = array(
                '#type' => 'submit',
                '#value' => 'Weiter',
                '#question_id' => $question_id,
                '#user_pass' => $user_pass,
            );

            //Sollte es sich nicht um die erste Frage handeln
            if ($question_index != 0) {
                $form['back'] = array(
                    '#type' => 'submit',
                    '#value' => 'Zurück',
                    '#submit' => ['::survey_question_evaluation_get_back_to_last_question'],
                    '#question_id' => $question_id,
                    '#user_pass' => $user_pass,
                    // skip form vaildation on back button
                    '#limit_validation_errors' => array(),
                );
            }
                
            $form['submit-wrapper-close'] = array(
                '#type' => 'markup',
                '#markup' => '</div>',
            );

            // The survey progress bar
            $form['progress_bar'] = array(
                '#markup' => $this->survey_get_status_bar($user_pass),
            );
        }

        return $form;

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $question = $form_state->getTriggeringElement()['#question_id'];
        $user_passcode = $form_state->getTriggeringElement()['#user_pass'];

        $questions = $this->get_questions();
        $answers = $this->get_answers_from_question($question);
        if (empty($answers)) {
            \Drupal::messenger()->addMessage($this->t('Please check the URL.'));
        }

        //Es wird über alle Antworten iteriert
        $noAnswers = count($answers);
        for ($i = 0; $i < $noAnswers; $i++) {

            if ($answers[$i]->isRadioButton) {
            $userAnswer = $form_state->getValue('dim' . $i);
            } else {
            $userAnswer = $form_state->getValue('answer' . $i);
            }

            //Sollten bereits Antworten vorliegen, werden sie aktualisiert. Sonst werden neue Einträge angelegt
            //$result = db_query("SELECT * FROM {question_user_answers} WHERE answer_id = :answer_id AND question_id = :question_id AND user_pw = :user_pw and is_last_answer = 1", array(
            //    ':answer_id' => $i,
            //    ':question_id' => $question,
            //    ':user_pw' => $user_passcode
            //));
            $query = $this->database->select('question_user_answers', 'qba');
            $query->fields('qba');
            $query->condition('answer_id', $i);
            $query->condition('question_id', $question);
            $query->condition('user_pw', $user_passcode);
            $query->condition('is_last_answer', 1);
            $result = $query->execute();

            if ($query->countQuery()->execute()->fetchField()) {
            // Es gibt bereits Antworten vom Benutzer. Geänderte Antworten speichern und Änderungen in der DB behalten.
            foreach ($result as $item) {
                // Nur wenn Benutzer seine Antwort ändert, diese als neue Antwort speichern. Vorherige Antwort markieren (is_last_answer)
                if (strcmp($userAnswer, $item->answer)) {
                    //db_query("UPDATE {question_user_answers} SET is_last_answer = 0 WHERE answer_id = :answer_id AND question_id = :question_id AND user_pw = :user_pw", array(
                    //    ':answer_id'   => $i,
                    //    ':question_id' => $question,
                    //    ':user_pw'     => $user_passcode
                    // ));
                    $query = $this->database->update('question_user_answers');
                    $query->fields(['is_last_answer' => 0]);
                    $query->condition('answer_id', $i);
                    $query->condition('question_id', $question);
                    $query->condition('user_pw', $user_passcode);
                    $query->execute();

                    //db_insert('question_user_answers')
                    //    ->fields(array(
                    //    'question_id' => $question,
                    //    'answer_id' => $i,
                    //    'answer' => $userAnswer,
                    //    'user_pw' => $user_passcode,
                    //    'comment' => $item->comment,
                    //    'is_last_answer' => 1,
                    //    ))
                    //    ->execute();

                    $query = $this->database->insert('question_user_answers');
                    $query->fields([
                        'question_id' => $question,
                        'answer_id' => $i,
                        'answer' => $userAnswer,
                        'user_pw' => $user_passcode,
                        'comment' => $item->comment,
                        'is_last_answer' => 1,
                    ]);
                    $query->execute();
                }
            }

            // es gibt noch keine Antworten, also nur speichern
            } else {
                //db_insert('question_user_answers')
                //    ->fields(array(
                //        'question_id' => $question,
                //        'answer_id' => $i,
                //        'answer' => $userAnswer,
                //        'user_pw' => $user_passcode,
                //        'comment' => "",
                //        'is_last_answer' => 1,
                //    ))
                //    ->execute();

                $query = $this->database->insert('question_user_answers');
                $query->fields([
                        'question_id' => $question,
                        'answer_id' => $i,
                        'answer' => $userAnswer,
                        'user_pw' => $user_passcode,
                        'comment' => "",
                        'is_last_answer' => 1,
                ]);
                $query->execute();
            }
        }

        // if the question type is a group
        $questionType = $this->get_question_type($question);
        if ($questionType === 'group') {
            $nextQuestionId = $this->question_evaluation_get_next_question($question);
            if ($nextQuestionId == -1) {
                $form_state->setRedirect('real_time_delphi.survey_finish');
            } else {
                $form_state->setRedirect('real_time_delphi.survey_answer', ['question_id' => $nextQuestionId, 'user_pass' => $user_passcode]);
            }
        } else {
            // TODO: Redirect zur Seite mit dem Boxplot s. survey_question_evaluation.inc
            \Drupal::messenger()->addMessage('Hier folgt der Boyplot');
            $form_state->setRedirect('real_time_delphi.survey_answer_evaluate', ['question_id' => $question, 'user_pass' => $user_passcode]);

        }
    }

    public function survey_question_evaluation_get_back_to_last_question (array &$form, FormStateInterface $form_state) {
        $question = $form_state->getTriggeringElement()['#question_id'];
        $user_passcode = $form_state->getTriggeringElement()['#user_pass'];

        $form_state->setRedirect('real_time_delphi.survey_answer', ['question_id' => $question - 1 , 'user_pass' => $user_passcode]);
    }

    //Diese Funktion holt sich die zuletzt abgegebenen Antworten eines Nutzers zu einer bestimmten Frage aus der DB und gibt
    //diese als Array zurück.
    public function survey_get_answers($question_id, $user_passcode) {
        $answers = array();

        //Antworten eines Nutzers zu einer Frage werden geladen
        $query = $this->database->select('question_user_answers', 'qba');
        $query->fields('qba',
                        ['question_user_answers_id', 'question_id',
                            'answer_id', 'answer', 'user_pw',
                            'comment', 'is_last_answer']);
        $query->condition('user_pw', $user_passcode, '=');
        $query->condition('question_id', $question_id);
        $query->orderBy('question_user_answers_id');
        $answer_result = $query->execute();

        foreach ($answer_result as $answer) {
            // Nur die zuletzt abgegebene Antwort zurückgeben.
            $answers[$answer->answer_id] = $answer;
        }

        return $answers;
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
    public function survey_get_status_bar($userToken) {
        $questionsCount = count($this->get_questions());
        $answeredCount = count($this->get_answered_questions($userToken));
    
        $progressPercent = $answeredCount / $questionsCount * 100;
    
        $html = '';
        $html .= '<div class="progress-bar">';
        $html .= '<div class="progress" style="width: ' . $progressPercent . '%">' . round($progressPercent) . '%</div></div>';
    
        return $html;
    }
}
