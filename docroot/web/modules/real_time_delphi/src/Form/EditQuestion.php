<?php

namespace Drupal\real_time_delphi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\real_time_delphi\Traits\QuestionTrait;

class EditQuestion extends FormBase {
    use QuestionTrait;

    protected $database;

    /**
     * {@inheritdoc}
     */
    public function __construct(Connection $database) {
        return $this->database = $database;
    }

    public static function create(ContainerInterface $container){
        return new static(
            $container->get('database')
        );
    }

    public function getFormId()
    {
        return 'edit_question_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $question_id = NULL, $quantity_id = NULL) {
            
            //$question_id = 2;
            $number_of_answers = $quantity_id;
            $question_title = "";
            $pageType = "";

            // Load question from the database
            $query = $this->database->select('question', 'q');
            $query->fields('q', ['question_id','title', 'weight', 'type']);
            $query->condition('question_id', $question_id, '=');
            $sql = $query->__toString();
            $question_result = $query->execute();

            // Eigenschaften der Frage werden ermittelt
        foreach ($question_result as $question) {
            $question_title = $question->title;
            $pageType = $question->type;
        }

        $title = $this->t("Title of the thesis");
        if ($pageType === 'group') {
            $title = $this->t("Title of the topicarea");
            $form['#attributes']['class'][] = 'question-group-form';
        }

        //Textfeld, in das der Titel der Frage eingesetzt wird
        $form['question'] = array(
            '#type' => 'textarea',
            '#required' => TRUE,
            '#title' => $title,
            '#resizable' => FALSE,
            '#rows' => 2,
            '#default_value' => $question_title,
            '#attributes' => array(
                'id' => 'question-field',
            ),
            '#maxlength' => 1000, // DB field type is varchar(1023)
        );

        //Textfeld, in das die Anzahl der Antwortmöglichkeiten eingegeben werden soll
    $form['quantity'] = array(
        '#type' => 'textfield',
        '#title' => $this->t("Count of the answeroptions"),
        '#size' => 2,
        '#maxlength' => 2,
        '#default_value' => $number_of_answers,
    );

    $answer_array = array();
    $check_array = array();
    $answer_id_array = array();
    $allQuestionsOnPage = array();
    $answer_counter = 0;

    // Get the answer options from the database
    $query = $this->database->select('question_possible_answers', 'qpa');
    $query->fields('qpa', ['answers_id', 'description', 
        'isRadioButton', 'question_id', 'weight', 'question_type']);
    $query->condition('question_id', $question_id, '=');
    $query->orderBy('question_id');
    $query->orderBy('weight');
    $query->orderBy('answers_id');

    $question_result = $query->execute();

    foreach ($question_result as $item) {
        array_push($answer_array, $item->description);
        array_push($check_array, $item->isRadioButton);
        array_push($answer_id_array, $item->answers_id);
        $allQuestionsOnPage[] = $item;
        $answer_counter++;
    }

    //Damit die nachfolgende Schleife funktioniert, muss das answer-array mindestens 6 groß sein
    while (sizeof($answer_array) < 6) {
        array_push($answer_array, "");
    }

    $answerSets = $this->delphi_question_get_answer_sets();

    //Diese Schleife wird so oft durchgeführt, wie viele Antwortmöglichkeiten es geben soll
    for ($i = 1; $i <= $number_of_answers; $i++) {

        // TODO these should be all available question types
        $answer_choose_array = array('rating' => t('Radio-Buttons'),
                                     'year' => "Year",
                                     'text' => t('Text'),
                                     //'knowledgeMap' => t('Knowledge Map')
        );
  
          //Für jede Antwortmöglichkeit wird ein Fieldset zur Übersichtlichkeit angelegt
          $form['content' . $i] = array(
              '#title' => $i . ". " . $this->t("Answeroption"),
              '#type' => 'fieldset',
              '#description' => ""
          );
  
          //Für jede Antwortmöglichkeit muss eine Bezeichnung festgelegt werden.
          $form['content' . $i]['test' . $i] = array(
              '#type' => 'textarea',
              '#rows' => 1,
              '#resizable' => FALSE,
              '#title' => "<b>" . $this->t('Label of the answeroption:') . "</b>",
              '#default_value' => $answer_array[$i - 1],
              '#required' => TRUE,
              '#maxlength' => 1000,
              '#attributes' => array(
                'class' => array('item-title'),
              ),
          );
  
          //Für jede Antwortmöglichkeit muss bestimmt werden, ob die Antwort per Radio-Buttons oder per Textfeld
          //gegeben werden soll
          $form['content' . $i]['radios' . $i] = array(
              '#type' => 'radios',
              '#title' => $this->t('Type of the answer option:'),
              '#default_value' => $allQuestionsOnPage[$i - 1]->question_type,
              '#options' => $answer_choose_array,
          );
  
          $button_counter_result = 0;
          $button_title_array = array();
  
          //Wenn es sich bei der Antwortmöglichkeit um Radio-Buttons handelt
          if ($check_array[$i - 1]) {
  
              //Die Titel der Buttons werden geladen...
              //$button_result = db_query("SELECT * FROM {question_buttons_title} WHERE answer_id = :answer_id", array(
              //    ':answer_id' => $answer_id_array[$i - 1]
              // ));

              $query = $this->database->select('question_buttons_title', 'qbt');
              $query->fields('qbt', ['question_button_title_id', 'question_id', 'answer_id', 'button_id', 'title']);
              $query->condition('answer_id', $answer_id_array[$i -1]);
              $button_result = $query->execute();
  
              //... und abgespeichert
              foreach ($button_result as $item_button_result) {
                  $button_counter_result = $button_counter_result + 1;
                  array_push($button_title_array, $item_button_result->title);
              }
  
              while (sizeof($button_title_array) < 6) {
                  array_push($button_title_array, "Dimension");
              }
          } else {
              array_push($button_title_array, "Dimension");
              array_push($button_title_array, "Dimension");
              array_push($button_title_array, "Dimension");
              array_push($button_title_array, "Dimension");
              array_push($button_title_array, "Dimension");
              array_push($button_title_array, "Dimension");
              array_push($button_title_array, "Dimension");
              $button_counter_result = 4;
          }
  
          $button_array = array(2 => "2", 4 => "4", 5 => "5", 6 => "6");
          $var = $i;
  
          //Sollte es sich bei der Antwortmöglichkeit um Radio-Buttons handeln, muss festgelegt werden, viele viele
          //Buttons für die Antwort vorgesehen sind.
          $form['content' . $i]['button_radios' . $i] = array(
              '#type' => 'radios',
              '#title' => "<b>" . $this->t('Count of the current radio boxes') . "</b>",
              '#default_value' => $button_array[$button_counter_result],
              '#options' => $button_array,
              '#states' => array(
                  'visible' => array(
                      ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
                  ),
              ),
  
          );
  
        // Auswahlliste mit bereits verwendeten Antwort-Sets
        if (!empty($answerSets)) {
            $selectOptions = array();
  
            foreach ($answerSets as $key => $set) {
                //dsm($set);
              $selectOptions[] = count($set) . ' ' . implode(', ', $set) ;
            }
  
            $form['content' . $i]['answer_sets' . $i] = array(
              '#title' => t('Antwort-Set'),
              '#type' => 'select',
              '#options' => $selectOptions,
              '#empty_option' => t(''),
              '#states' => array(
                'visible' => array(
                  ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
                ),
              ),
              '#attributes' => array(
                'class' => array('answer-sets'),
              ),
            );
          }
  
          $form['content' . $i]['textfield_first_button' . $i] = array(
              '#type' => 'textfield',
              '#require' => TRUE,
              '#title' => "Bezeichnung der ersten Radio-Box",
              '#default_value' => $button_title_array[0],
              '#required' => TRUE,
              '#states' => array(
                  'visible' => array(
                      ':input[name="button_radios' . $var . '"]' => array(
                          array('value' => t('2')),
                          array('value' => t('4')),
                          array('value' => t('5')),
                          array('value' => t('6')),
                      ),
                      ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
                  ),
              ),
  
          );
  
          $form['content' . $i]['textfield_second_button' . $i] = array(
              '#type' => 'textfield',
              '#require' => TRUE,
              '#title' => "Bezeichnung der zweiten Radio-Box",
              '#default_value' => $button_title_array[1],
              '#required' => TRUE,
              '#states' => array(
                  'visible' => array(
                      ':input[name="button_radios' . $var . '"]' => array(
                          array('value' => t('2')),
                          array('value' => t('4')),
                          array('value' => t('5')),
                          array('value' => t('6')),
                      ),
                      ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
                  ),
              ),
          );
  
          $form['content' . $i]['textfield_third_button' . $i] = array(
              '#type' => 'textfield',
              '#require' => TRUE,
              '#title' => "Bezeichnung der dritten Radio-Box",
              '#default_value' => $button_title_array[2],
              '#required' => TRUE,
              '#states' => array(
                  'visible' => array(
                      ':input[name="button_radios' . $var . '"]' => array(
                          array('value' => t('4')),
                          array('value' => t('5')),
                          array('value' => t('6')),
                      ),
                      ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
                  ),
              ),
          );
  
          $form['content' . $i]['textfield_fourth_button' . $i] = array(
              '#type' => 'textfield',
              '#require' => TRUE,
              '#title' => "Bezeichnung der vierten Radio-Box",
              '#default_value' => $button_title_array[3],
              '#required' => TRUE,
              '#states' => array(
                  'visible' => array(
                      ':input[name="button_radios' . $var . '"]' => array(
                          array('value' => t('4')),
                          array('value' => t('5')),
                          array('value' => t('6')),
                      ),
                      ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
                  ),
              ),
          );
  
          $form['content' . $i]['textfield_fiveth_button' . $i] = array(
              '#type' => 'textfield',
              '#require' => TRUE,
              '#title' => "Bezeichnung der fünften Radio-Box",
              '#default_value' => $button_title_array[4],
              '#required' => TRUE,
              '#states' => array(
                  'visible' => array(
                      ':input[name="button_radios' . $var . '"]' => array(
                          array('value' => t('5')),
                          array('value' => t('6')),
                      ),
                      ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
                  ),
              ),
          );
  
          $form['content' . $i]['textfield_sixth_button' . $i] = array(
              '#type' => 'textfield',
              '#require' => TRUE,
              '#title' => "Bezeichnung der sechsten Radio-Box",
              '#default_value' => $button_title_array[5],
              '#required' => TRUE,
              '#states' => array(
                  'visible' => array(
                      ':input[name="button_radios' . $var . '"]' => array(
                          array('value' => t('6')),
                      ),
                      ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
                  ),
              ),
  
          );
      }

    //Button, der die Änderung der Anzahl von Antwortmöglichkeiten übernimmt
    $form['submit'] = array(
        '#question_id' => $question_id,
        '#quantity_id' => $quantity_id,
        '#type' => 'submit',
        '#value' => $this->t('Change'),
    );

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $title = $form_state->getValue('question');
        $question_id = $form_state->getTriggeringElement()['#question_id'];
    }

}