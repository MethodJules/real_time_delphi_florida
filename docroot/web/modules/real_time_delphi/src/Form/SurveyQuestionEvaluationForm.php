<?php

namespace Drupal\real_time_delphi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\real_time_delphi\Traits\QuestionTrait;
use Drupal\real_time_delphi\Graphic\Boxplot;

/**
 * Provides a Real Time Delphi form.
 */
class SurveyQuestionEvaluationForm extends FormBase {
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

    public function getFormId() {
        return 'survey_question_evaluation_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $question_id = null, $user_pass = null) {
        $user_id = $user_pass;

        $boxplot = new Boxplot();

        $questions = $this->get_questions();
        $question_index = 0;
        $questionGroupIndex = 0;
        $questionType = 'question';

        foreach ($questions as $q) {
            if ($q->question_id == $question_id) {
            $questionType = $q->type;
            break;
            }

            if ($q->type === 'group') {
            $questionGroupIndex++;
            }
            $question_index++;
        }

        // Fehlermeldung bei unbekannter question_id
        if (count($questions) === $question_index) {
          $form_state->setError(['Question'], 'Invalid ID');
        }

        // no evaluation page for question groups
        if ($questionType === 'group') {
            $form_state->setRedirect('real_time_delphi.survey_answer', [$question_id, $user_id]);
        }

        // The headline will be created
        $headline = $questions[$question_index]->title;
        $titleLength = mb_strlen($headline);
        $headline = "
        <h1>These " . ($question_index -$questionGroupIndex + 1) . "</h1>
        <h2>" . $headline . "</h2>";

        $stringSelfAssessment = $this->t('Here you can compare your assessments with the averaged values 
        of the other participants and view their comments. You can then stick to your assessment 
        or change it. If you wish, you can give reasons for your assessment in a comment of your own or 
        provide any other comments that are worthy of consideration.');

        //$stringSelfAssessment = t('Hier können Sie Ihre Einschätzung bewerten');    
        $form['headline']['title'] = array(
            '#type' => 'markup',
            '#title' => t('Headline'),
            '#markup' => $headline,
            '#prefix' => '<div class="question-header">',
        );

        $htmlSelfAssessment = '<div class="self-assessment">' . $stringSelfAssessment . '</div>';

        // Da Titel immer fix im Vordergrund steht muss die Content-Region um die Höhe des Titels verschoben werden
        $cssClassContent = 'evaluation-content-wrapper';

        if ($titleLength > 300) {
            $form['headline']['title']['#suffix'] = $htmlSelfAssessment. '</div><div class="four-rows ' . $cssClassContent . '">';
        } else if ($titleLength > 200) {
            $form['headline']['title']['#suffix'] = $htmlSelfAssessment .'</div><div class="three-rows ' . $cssClassContent . '">';
        } else if ($titleLength > 100) {
            $form['headline']['title']['#suffix'] = $htmlSelfAssessment .'</div><div class="two-rows ' . $cssClassContent . '">';
        } else {
            $form['headline']['title']['#suffix'] = $htmlSelfAssessment .'</div><div class="one-row ' . $cssClassContent . '">';
        }

        //In diese Variable werden alle Antwortmöglichkeiten der Frage eingespeichert
        $answers = array();

        //TODO Check for refactoring
        //SQL-> Get lables for radio buttons

        //$button_title_result = db_query("SELECT * FROM {question_buttons_title} WHERE question_id = :question_id ORDER BY answer_id, button_id", array(
        //    ':question_id' => $questions[$question_index]->question_id
        // ));
        $query = $this->database->select('question_buttons_title', 'qbt');
        $query->fields('qbt');
        $query->condition('question_id', $questions[$question_index]->question_id);
        $query->orderBy('answer_id');
        $query->orderBy('button_id');
        $button_title_result = $query->execute();
        $button_title_array = $this->get_button_titles($button_title_result);

        //Die gegebenen Antworten des Nutzers werden geladen
        // $answers_result = db_query("SELECT * FROM {question_user_answers} WHERE user_pw = :user_pw AND question_id = :question_id", array(
        //    ':user_pw' => $user_id,
        //    ':question_id' => $question_id
        // ));
        $query = $this->database->select('question_user_answers', 'qba');
        $query->fields('qba');
        $query->condition('user_pw', $user_id);
        $query->condition('question_id', $question_id);
        $answers_result = $query->execute();

        // Keine Antworten des Benutzers gefunden
        if ($query->countQuery()->execute()->fetchField() === 0) {
            $form_state->setError(['Answers'], 'Invalid User');
        }

        //Die Antworten werden ausgelesen und in ein Array gespeichert
        foreach ($answers_result as $answer) {

            $check = $this->survey_question_evaluation_search_for_answer_ID($answer->answer_id, $answers);

            if ($check > -1) {
                $answers[$check] = $answer;
            } else {
                array_push($answers, $answer);
            }
        }

        $countTextfield = 0;
        for ($i = 0; $i < sizeof($answers); $i++) {

            //Es werden alle Fragen aus der Datenbank ausgelesen
            $questions = $this->get_questions();

            //Die aktuelle Fragen-ID wird ermittelt
            $question_id = $questions[$question_index]->question_id;

            //Die Antwortmöglichkeiten der Frage werden geladen
            //$possible_answers_result = db_query("SELECT * FROM {question_possible_answers} WHERE question_id = :question_id ORDER BY weight", array(
            //    ':question_id' => $question_id
            //));
            $query = $this->database->select('question_possible_answers', 'qba');
            $query->fields('qba');
            $query->condition('question_id', $question_id);
            $query->orderBy('weight');
            $possible_answers_result = $query->execute();

            //In dieses Array werden die Antwortmöglichkeiten der entsprechenden Frage gespeichert
            $possible_answers_array = array();

            //Es wird über alle Antwortmöglichkeiten der Frage iteriert
            foreach ($possible_answers_result as $possible_answer) {
                array_push($possible_answers_array, $possible_answer);
            }

            $form['fieldset' . $i] = array(
                '#type' => 'fieldset',
            );

            //Sollte die aktuelle Antwortmöglichkeit Radio-Buttons beinhalten:
            // TODO duplicate code
            $questionType = $possible_answers_array[$i]->question_type;
            if ($possible_answers_array[$i]->isRadioButton) {

                //Der Boxplot zur Antwort wird geladen
                // Index der Antwortoption "keine Ahnung"
                $indexNoAnswer = count($button_title_array[$i - $countTextfield]) - 1;
                    $boxplot_graphic = $boxplot->get_graphic($i, 1, $indexNoAnswer, $question_id, $user_pass); // TODO quick fix

                    $answerDescription = $possible_answers_array[$i]->description;
                    $form['fieldset' . $i]['#attributes'] = array(
                    'class' => array('evaluation', $questionType));
                    $form['fieldset' . $i]['#title'] = $answerDescription;

                    // Fieldset legend vergrößern bei langen Titeln
                    if (mb_strlen($answerDescription) > 97) {
                    $form['fieldset' . $i]['#attributes'] = array(
                        'class' => array('title-long'));
                    }

                    $form['fieldset' . $i]['my_markup' . $i] = array(
                        '#markup' => $boxplot_graphic,
                    );

                    //dsm($button_title_array[$i - $countTextfield]);

                    $form['fieldset' . $i]['dim' . $i] = array(
                        '#type' => 'radios',
                        '#title' => '',
                        '#options' => $button_title_array[$i - $countTextfield], // TODO quick fix
                        '#default_value' => $answers[$i]->answer,
                        '#attributes' => array(
                            'class' => array('radios-' . count($button_title_array[$i - $countTextfield]))
                        ),
                    );

                    $form['fieldset' . $i]['comment_dim' . $i] = array(
                        '#title' => t('Kommentar'),
                        '#resizable' => FALSE,
                        '#type' => 'textarea',
                        '#default_value' => $answers[$i]->comment,
                    );

                    $form = $this->survey_addUserComments($form, $i, $question_id, $answers[$i]->answer_id, $user_id, $button_title_array[$i - $countTextfield]);

                //Sollte die aktuelle Antwortmöglichkeit eine freie Antwort beinhalten:
                } else if ($questionType === 'year'){
                    $countTextfield++;
                    //Der Boxplot zur Antwort wird geladen
                    $boxplot_graphic = $boxplot->get_graphic($i, 0, array('Min', 'Max'), $question_id, $user_pass);

                    $form['fieldset' . $i]['#attributes'] = array(
                    'class' => array('evaluation', $questionType));

                    $form['fieldset' . $i]['#title'] = $possible_answers_array[$i]->description;

                    $form['fieldset' . $i]['my_markup' . $i] = array(
                        '#markup' => $boxplot_graphic,
                    );

                    // 'answer_NA' ist interne Codierung für 'keine Angabe' und soll nicht angezeigt werden.
                    $isChecked = FALSE;
                    if ($answers[$i]->answer === 'answer_NA') {
                    $answers[$i]->answer = '';
                    $isChecked = TRUE;
                    }

                    $form['fieldset' . $i]['answer' . $i] = array(
                        '#type' => 'textfield',
                        '#title' => $possible_answers_array[$i]->description,
                        '#title_display' => 'invisible',
                        '#size' => 4,
                        '#maxlength' => 4,
                        '#required' => FALSE,
                        '#default_value' => $answers[$i]->answer,
                        '#states'        => array(
                        'disabled' => array(
                            ':input[name="answer_check' . $i . '"]' => array('checked' => TRUE),
                            ),
                        ),
                        '#attributes' => array('class' => array($questionType)),
                    );

                    $form['fieldset' . $i]['answer_check' . $i] = array(
                    '#type' => 'checkbox',
                    '#title' => 'weiß nicht / keine Angabe',
                    '#attributes' => array(
                        'class' => array('text-checkbox')
                    ),
                    '#default_value' => $isChecked,
                    );

                    $form['fieldset' . $i]['comment_dim' . $i] = array(
                        '#title' => t('Kommentar'),
                        '#resizable' => FALSE,
                        '#type' => 'textarea',
                        '#default_value' => $answers[$i]->comment,
                    );

                    $form = $this->survey_addUserComments($form, $i, $question_id, $answers[$i]->answer_id, $user_id, array());

            } else if ($questionType === 'text') {
                $countTextfield++;

                $form['fieldset' . $i]['#title'] = $possible_answers_array[$i]->description;
                $form['fieldset' . $i]['#attributes'] = array('class' => array('evaluation', $questionType));

                // evaluation data
                //$evalAnswers = db_query("SELECT answer FROM {question_user_answers} 
                //    WHERE question_id = :question_id 
                //    AND answer_id = :answer_id
                //    AND user_pw != :user_pw
                //    AND is_last_answer = 1", array(
                //':question_id' => $question_id,
                //':answer_id' => $answers[$i]->answer_id,
                //':user_pw' => $user_id,
                //))->fetchCol();

                $query = $this->database->select('question_user_answers', 'qba');
                $query->addField('qba', 'answer');
                $query->condition('question_id', $question_id);
                $query->condition('answer_id', $answers[$i]->answer_id);
                $query->condition('user_pw', $user_id);
                $query->condition('is_last_answer', 1);
                $evalAnswers = $query->execute()->fetchCol();

                $noEvalAnswers = count($evalAnswers);
                if ($noEvalAnswers >= 1) { //TODO checken
                $labelNa = t('weiß nicht / keine Angabe');
                $evalAnswers = str_replace('answer_NA', $labelNa, $evalAnswers);

                $form['fieldset' . $i]['evaluation' . $i] = array(
                    '#theme' => 'item_list',
                    '#list_type' => 'ul',
                    '#items' => $evalAnswers,
                    '#attributes' => array('class' => array($questionType)),
                );

                } else {
                $evalHtml = '<div class = "boxplot-nodata">' .
                    t('Zu diesem Thema wurden noch nicht genügend Einschätzungen abgegeben.') .
                    '</div>';

                $form['fieldset' . $i]['insufficient_data' . $i] = array(
                    '#markup' => $evalHtml,
                );
                }

                // user's answer TODO duplicate code => universal function for form elements skeleton
                // 'answer_NA' ist interne Codierung für 'keine Angabe' und soll nicht angezeigt werden.
                $isChecked = FALSE;
                if ($answers[$i]->answer === 'answer_NA') {
                $answers[$i]->answer = '';
                $isChecked = TRUE;
                }

                $form['fieldset' . $i]['answer' . $i] = array(
                '#type' => 'textarea',
                '#title' => $possible_answer->description,
                '#title_display' => 'invisible',
                '#resizable' => FALSE,
                '#rows' => 2,
                '#default_value' => $answers[$i]->answer,
                '#required' => FALSE,
                '#states'        => array(
                    'disabled' => array(
                        ':input[name="answer_check' . $i . '"]' => array('checked' => TRUE),
                    ),
                ),
                '#attributes' => array('class' => array($questionType)),
                );

                $form['fieldset' . $i]['answer_check' . $i] = array(
                '#type'          => 'checkbox',
                '#title'         => 'weiß nicht / keine Angabe',
                '#attributes'    => array('class' => array($questionType)),
                '#default_value' => $isChecked,
                );

                // comments of others users
                $form['fieldset' . $i]['comment_dim' . $i] = array(
                '#title'         => t('Kommentar'),
                '#resizable'     => FALSE,
                '#type'          => 'textarea',
                '#default_value' => $answers[$i]->comment,
                );

                $form = $this->survey_addUserComments($form, $i, $question_id, $answers[$i]->answer_id, $user_id, array());
            }

        }

        $form['#attached']['library'][] = 'real_time_delphi/real_time_delphi';

        return $form;

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        
    }

    /*
    * Diese Funktion überprüft, ob die Antwort existiert
    */
    public function survey_question_evaluation_search_for_answer_ID($id, $array) {

        $check = -1;

        foreach ($array as $key => $val) {
            if (!strcmp($val->answer_id, $id)) {
                $check = $key;
            }
        }
        return $check;
    }

    /**
     * Returns all user comments for a given question.
     * Does not display the user's own comment.
     *
     * @param $questionId
     *   ID of the question
     *
     * @return array
     *   All comments associated with the question
     */
    function survey_getComments($questionId, $answerId, $userId) {
        /*
        $commentsResult = db_query("SELECT answer, comment FROM {question_user_answers} 
            WHERE question_id = :question_id AND answer_id = :answer_id AND is_last_answer = 1
            AND user_pw != :user_id AND comment != '' ORDER BY answer DESC", array(
        ':question_id' => $questionId,
        ':answer_id' => $answerId,
        ':user_id' => $userId,
        ));
        */

        $query = $this->database->select('question_user_answers', 'qba');
        $query->fields('qba', ['answer', 'comment']);
        $query->condition('question_id', $questionId);
        $query->condition('answer_id', $answerId);
        $query->condition('user_pw', $userId);
        $commentsResult = $query->execute();
    
        $allComments = array();
        foreach ($commentsResult as $comment) {
            if ($comment->comment) {
                $allComments[$comment->answer][] = $comment->comment;
            }
        }
    
        return $allComments;
    }

    /**
     * Adds a toggleable user comment section to the ansers.
     *
     * @param $form
     *    The form array.
     * @param $i
     *    The current index.
     * @param $question_id
     *    The ID of the current question site.
     * @param $answer_id
     *    The ID of the answer.
     *
     * @return array
     *    The extended form array.
     */
    function survey_addUserComments($form, $i, $question_id, $answer_id, $user_id, $answerLabels) {
        $userComments = $this->survey_getComments($question_id, $answer_id, $user_id);
    
        if (count($userComments) > 0) {
        $form['fieldset' . $i]['toggleComments' . $i] = array(
            '#type' => 'checkbox',
            '#title' => t('Alle Kommentare anzeigen'),
            '#prefix' => '<div class="toggleComments">',
            '#suffix' => '</div>',
            '#attributes' => array(
            'class' => array('button')
            ),
        );
    
        $form['fieldset' . $i]['allComments' . $i] = array(
            '#type' => 'container',
            '#attributes' => array(
            'class' => array('form-item')),
            '#states' => array(
            // Ausblenden, wenn "alle Kommentare anzeigen" nicht ausgewählt ist
            'invisible' => array(
                ':input[name="toggleComments' . $i . '"]' => array('checked' => FALSE),
            )),
        );
    
        $orderedComments = array();
        $answerLabel = '';
    
    
        // convert associative array with answers and comments to strings
        foreach ($userComments as $answer => $comments) {
    
            if (empty($answerLabels)) {
            // int textfield answer
            if ($answer === 'answer_NA') {
                $answerLabel = t('weiß nicht / keine Angabe');
            } else {
                $answerLabel = $answer;
            }
            } else {
            $answerLabel = $answerLabels[$answer];
            }
    
            foreach ($comments as $comment) {
            $orderedComments[] = '['.$answerLabel.'] ' . $comment;
            }
        }
    
        $form['fieldset' . $i]['allComments' . $i]['comments'] = array(
            '#theme' => 'item_list',
            '#list_type' => 'ul',
            '#items' => $orderedComments,
        );
        }
    
        return $form;
    }
  
}