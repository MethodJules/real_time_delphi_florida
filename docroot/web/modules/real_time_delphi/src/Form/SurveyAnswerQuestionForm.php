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

        // TODO: hier weiterschreiben /survey/survey_answer_question.inc zeile 54

    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        
    }

    //Diese Funktion holt sich die zuletzt abgegebenen Antworten eines Nutzers zu einer bestimmten Frage aus der DB und gibt
    //diese als Array zurück.
    function survey_get_answers($question_id, $user_passcode) {
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
}
