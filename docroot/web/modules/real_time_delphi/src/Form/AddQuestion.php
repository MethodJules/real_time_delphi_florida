<?php

namespace Drupal\real_time_delphi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Provides a Real Time Delphi form.
 */
class AddQuestion extends FormBase {

  protected $database;

  public function __construct(Connection $database) {
    return $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('database')
    );
  }

   /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'real_time_delphi_add_question';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $answer_quantity_id = 1) {

    $isQuestionGroup = FALSE;
    $answer_choose_array = ['rating' => $this->t('Radio-Buttons'),
                                 'year' => $this->t("Year"),
                                 'text' => $this->t('Text'),
                                 'ranking' => $this->t('Ranking'),
                                 //'knowledgeMap' => t('Knowledge Map')
    ];

    $button_array = [2 => "2", 4 => "4", 5 => "5", 6 => "6"];


    // Textfield for the question title
    $form['question'] = [
        '#type' => 'textarea',
        '#required' => FALSE,
        '#title' => $this->t("Title of the topicarea"),
        //'#format' => 'full_html',
        '#attributes' => array(
            'id' => 'question-field',
        ),
        '#maxlength' => 1000, // DB field type is varchar(1023)
    ];
    
    $form['quantity'] = [
        '#type' => 'textfield',
        '#title' => $this->t("Number of answer oportunities"),
        '#size' => 2,
        '#maxlength' => 2,
        '#default_value' => $answer_quantity_id,
    ];

    //Button, der die Änderung der Anzahl von Antwortmöglichkeiten übernimmt
    
    $form['back'] =  [
        '#type' => 'submit',
        '#value' => $this->t('Change'),
        '#submit' => ['delphi_question_add_question_change_quantity']
    ];

     //Submit-Button
     $form['submit'] = array(
        '#name' => $answer_quantity_id,
        '#type' => 'submit',
        '#value' => 'Speichern',
        // '#submit' => array('delphi_question_add_question_save_question')
    );

    // create form elements for the answer quantity
    for ($i = 1; $i <= $answer_quantity_id; $i++) {

      $form['content' . $i] = [
        '#title' => $i . ". " . $this->t("Answeroption"),
        '#type' => 'fieldset',
      ];

      // Foreach answeroportunity create a label
      $form['content' . $i]['test' . $i] = [
        '#type' => 'textarea',
        '#rows' => 1,
        '#resizable' => FALSE,
        '#title' => "<b>" . $this->t("Label of the Answeroption") . "</b>",
        '#required' => TRUE,
        '#maxlength' => 1000,
        '#attributes' => [
          'class' => ['item-title'],
        ]
      ];

      $var = $i;

      if ($isQuestionGroup) {
        $form['content' . $i]['radios' . $i] = array(
          '#type' => 'radios',
          '#title' => $this->t('Answertype:'),
          '#default_value' => 'rating',
          '#options' => $answer_choose_array,
        );

        $form['content' . $i]['button_radios' . $i] = array(
          '#type'          => 'radios',
          '#title'         => "<b>" . $this->t("Number of radio boxes") . "</b>",
          '#default_value' => 5,
          '#options'       => $button_array,
        );

      } else {

        //Für jede Antwortmöglichkeit muss bestimmt werden, ob die Antwort per Radio-Buttons oder per Textfeld
        //gegeben werden soll
        $form['content' . $i]['radios' . $i] = array(
          '#type' => 'radios',
          '#title' => 'Art der Antwortmöglichkeit:',
          '#default_value' => 'rating',
          '#options' => $answer_choose_array,
        );
        $form['content' . $i]['button_radios' . $i] = array(
          '#type'          => 'radios',
          '#title'         => "<b>Anzahl der vorhandenen Radio-Boxen</b>",
          '#default_value' => $button_array[4],
          '#options'       => $button_array,
          '#states'        => array(
            'visible' => array(
              ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
            ),
          ),
        );

        $form['content' . $i]['ranking' .$i] = array(
            '#type' => 'radios',
            '#title' => $this->t('Anzahl der Ranking Items'),
            '#default_value' => 2,
            '#options' => array(2 => '2', 3 => '3', 5 => '5'),
            '#states' => array(
                'visible' => array(
                    ':input[name="radios' . $var . '"]' => array('value' => 'ranking'),
                ),
            ),
        );
      }

      // Auswahlliste mit bereits verwendeten Antwort-Sets
      if (!empty($answerSets)) {
        $selectOptions = array();

        foreach ($answerSets as $key => $set) {
          $selectOptions[] = count($set) . ' ' . implode(', ', $set) ;
        }

        $form['content' . $i]['answer_sets' . $i] = array(
          '#title' => $this->t('Antwort-Set'),
          '#type' => 'select',
          '#options' => $selectOptions,
          '#empty_option' => $this->t(''),
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

      $defaultValues = array_fill(0, 6, 'Dimension ' . $var);
      if ($isQuestionGroup) {
        $defaultValues = array(
          '... kenne ich mich überhaupt nicht aus.',
          '.',
          '.',
          '.',
          '... kenne ich mich sehr gut aus.',
          '.'
        );
      }

      //Textfelder für die ersten beiden Ranking Felder

      $form['content' . $i]['textfield_first_ranking' . $i] = array(
          '#type' => 'textfield',
          '#require' => TRUE,
          '#title' => "Bezeichnung der ersten Ranking-Box",
          '#default_value' => $this->t('Rang 1'),
          '#required' => TRUE,
          '#states' => array(
              'visible' => array(
                  ':input[name="ranking' . $var . '"]' => array(
                      array('value' => $this->t('2')),
                      array('value' => $this->t('3')),
                      array('value' => $this->t('5')),
                  ),
                  ':input[name="radios' . $var . '"]' => array('value' => 'ranking'),
              ),
          ),
      );

      $form['content' . $i]['textfield_second_ranking' . $i] = array(
          '#type' => 'textfield',
          '#require' => TRUE,
          '#title' => "Bezeichnung der zweiten Ranking-Box",
          '#default_value' => $this->t('Rang 2'),
          '#required' => TRUE,
          '#states' => array(
              'visible' => array(
                  ':input[name="ranking' . $var . '"]' => array(
                      array('value' => $this->t('2')),
                      array('value' => $this->t('3')),
                      array('value' => $this->t('5')),
                  ),
                  ':input[name="radios' . $var . '"]' => array('value' => 'ranking'),
              ),
          ),
      );

      //Textfelder für das dritte Ranking Felder

      $form['content' . $i]['textfield_third_ranking' . $i] = array(
          '#type' => 'textfield',
          '#require' => TRUE,
          '#title' => "Bezeichnung der drittten Ranking-Box",
          '#default_value' => $this->t('Rang 3'),
          '#required' => TRUE,
          '#states' => array(
              'visible' => array(
                  ':input[name="ranking' . $var . '"]' => array(
                      array('value' => $this->t('3')),
                      array('value' => $this->t('5')),
                  ),
                  ':input[name="radios' . $var . '"]' => array('value' => 'ranking'),
              ),
          ),
      );

      //Textfelder für die fünf Ranking Felder
      $form['content' . $i]['textfield_fourth_ranking' . $i] = array(
          '#type' => 'textfield',
          '#require' => TRUE,
          '#title' => "Bezeichnung der vierten Ranking-Box",
          '#default_value' => $this->t('Rang 4'),
          '#required' => TRUE,
          '#states' => array(
              'visible' => array(
                  ':input[name="ranking' . $var . '"]' => array(
                      array('value' => $this->t('5')),
                  ),
                  ':input[name="radios' . $var . '"]' => array('value' => 'ranking'),
              ),
          ),
      );

      $form['content' . $i]['textfield_fifth_ranking' . $i] = array(
          '#type' => 'textfield',
          '#require' => TRUE,
          '#title' => "Bezeichnung der fünften Ranking-Box",
          '#default_value' => $this->t('Rang 5'),
          '#required' => TRUE,
          '#states' => array(
              'visible' => array(
                  ':input[name="ranking' . $var . '"]' => array(
                      array('value' => $this->t('5')),
                  ),
                  ':input[name="radios' . $var . '"]' => array('value' => 'ranking'),
              ),
          ),
      );



      //Textfeld für den ersten Button
      $form['content' . $i]['textfield_first_button' . $i] = array(
          '#type' => 'textfield',
          '#require' => TRUE,
          '#title' => "Bezeichnung der ersten Radio-Box",
          '#default_value' => $this->t($defaultValues[0]),
          '#required' => TRUE,
          '#states' => array(
              'visible' => array(
                  ':input[name="button_radios' . $var . '"]' => array(
                      array('value' => $this->t('2')),
                      array('value' => $this->t('4')),
                      array('value' => $this->t('5')),
                      array('value' => $this->t('6')),
                  ),
                  ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
              ),
          ),
      );

      //Textfeld für den zweiten Button
      $form['content' . $i]['textfield_second_button' . $i] = array(
          '#type' => 'textfield',
          '#require' => TRUE,
          '#title' => "Bezeichnung der zweiten Radio-Box",
          '#default_value' => $this->t($defaultValues[1]),
          '#required' => TRUE,
          '#states' => array(
              'visible' => array(
                  ':input[name="button_radios' . $var . '"]' => array(
                      array('value' => $this->t('2')),
                      array('value' => $this->t('4')),
                      array('value' => $this->t('5')),
                      array('value' => $this->t('6')),
                  ),
                  ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
              ),
          ),
      );

      //Textfeld für den dritten Button
      $form['content' . $i]['textfield_third_button' . $i] = array(
          '#type' => 'textfield',
          '#require' => TRUE,
          '#title' => "Bezeichnung der dritten Radio-Box",
          '#default_value' => $this->t($defaultValues[2]),
          '#required' => TRUE,
          '#states' => array(
              'visible' => array(
                  ':input[name="button_radios' . $var . '"]' => array(
                      array('value' => $this->t('4')),
                      array('value' => $this->t('5')),
                      array('value' => $this->t('6')),
                  ),
                  ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
              ),
          ),
      );

      //Textfeld für den vierten Button
      $form['content' . $i]['textfield_fourth_button' . $i] = array(
          '#type' => 'textfield',
          '#require' => TRUE,
          '#title' => "Bezeichnung der vierten Radio-Box",
          '#default_value' => $this->t($defaultValues[3]),
          '#required' => TRUE,
          '#states' => array(
              'visible' => array(
                  ':input[name="button_radios' . $var . '"]' => array(
                      array('value' => $this->t('4')),
                      array('value' => $this->t('5')),
                      array('value' => $this->t('6')),
                  ),
                  ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
              ),
          ),
      );

      //Textfeld für den fünften Button
      $form['content' . $i]['textfield_fiveth_button' . $i] = array(
          '#type' => 'textfield',
          '#require' => TRUE,
          '#title' => "Bezeichnung der fünften Radio-Box",
          '#default_value' => $this->t($defaultValues[4]),
          '#required' => TRUE,
          '#states' => array(
              'visible' => array(
                  ':input[name="button_radios' . $var . '"]' => array(
                      array('value' => $this->t('5')),
                      array('value' => $this->t('6')),
                  ),
                  ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
              ),
          ),
      );

      //Textfeld für den sechsten Button
      $form['content' . $i]['textfield_sixth_button' . $i] = array(
          '#type' => 'textfield',
          '#require' => TRUE,
          '#title' => "Bezeichnung der sechsten Radio-Box",
          '#default_value' => $this->t($defaultValues[5]),
          '#required' => TRUE,
          '#states' => array(
              'visible' => array(
                  ':input[name="button_radios' . $var . '"]' => array(
                      array('value' => $this->t('6')),
                  ),
                  ':input[name="radios' . $var . '"]' => array('value' => 'rating'),
              ),
          ),
      );
      
    }
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $answer_quantity_id = $form_state->getTriggeringElement()['#name'];
    $question_title = $form_state->getValue('question');
    $isQuestionGroup = FALSE;

    //question group has only one
    if ($answer_quantity_id === 'group') {
      $number_of_answers = 1;
      $isQuestionGroup = TRUE;
      $questionType = 'group';
    } else {
      $number_of_answers = $answer_quantity_id;
      $questionType = 'question';
    }

    $query = $this->database->query("SELECT COUNT(*) FROM {question}");
    $noQuestions = $query->fetchField();



    $questionType = "";
    \Drupal::messenger()->addMessage(
      'AnswerNr:' .$answer_quantity_id . 
      'noQuestion:' . $noQuestions);
    
    
    $form_state->setRedirect('<front>');
  }

  public function delphi_question_add_question_change_quantity(array &$form, FormStateInterface $form_state) {
      
  }
}