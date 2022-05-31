<?php

namespace Drupal\real_time_delphi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;

class QuestionOverview extends FormBase {

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

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'question_overview_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        global $base_path;

        $linkText = '<span class="fa fa-angle-left"></span>'. $this->t('Add topic area');
        $form['actions']['add_topic_area'] = [
        '#type' => 'link',
        '#title' => Markup::create($linkText),
        '#attributes' => [
            'id' => 'goto-step-three',
            'class' => [
            'btn',
            'btn-green',
            'btn--goback-step-one',
            ],
            'data-twig-id' => 'goto-step-one',
        ],
        '#weight' => 0,
        '#url' => Url::fromRoute('real_time_delphi.example'),
        ];

        $linkText = '<span class="fa fa-angle-left"></span>'. $this->t('Add thesis');
        $form['actions']['add_thesis'] = [
        '#type' => 'link',
        '#title' => Markup::create($linkText),
        '#attributes' => [
            'id' => 'goto-step-three',
            'class' => [
            'btn',
            'btn-green',
            'btn--goback-step-one',
            ],
            'data-twig-id' => 'goto-step-one',
        ],
        '#weight' => 1,
        '#url' => Url::fromRoute('real_time_delphi.add_question', ['answer_quantity_id' => 1]),
        ];

        $form['table'] = array(
            '#type' => 'markup',
            '#title' => $this->t('Thesis Overview'),
            '#prefix' => '<table class="select-table">',
            '#suffix' => '</table>'
          );

        //Kopf der Tabelle wird erstellt
        $form['table']['header'] = array(
            '#type' => 'markup',
            '#markup' => "<th>Nr.</th>
                    <th>" . $this->t('Thesis') . "</th>
                    <th>" . $this->t('Action') . "</th>",
        );

        // load questions
        $query = $this->database->select('question', 'q');
        $query->fields('q');
        $query->orderBy('weight');
        $query->orderBy('question_id');
        $sql = $query->__toString();
        $question_result = $query->execute();
        $noQuestions = $query->countQuery()->execute()->fetchField();
        // $noQuestions = $question_result->fetchField();

        $id=1;

        foreach ($question_result as $question) {
            // load answer options of the question
            //$answer_result = $this->database->query("SELECT * FROM {question_possible_answers}
            //        WHERE question_id = :question_id", [':question_id' => $question->question_id])->execute();

            // count the answers
            // $quan = count($answer_result);
            $query = $this->database->select('question_possible_answers', 'qba');
            $query->fields('qpa', ['answers_id', 'description', 
                        'isRadioButton', 'question_id', 'weight', 'question_type']);
            $query->condition('question_id', $question->question_id);
            $quan = $query->countQuery()->execute()->fetchField();


            // build table row
            $rowClass = 'question-row';
            if ($question->type === 'group') {
                $rowClass = 'question-group-row';
            }

            $form['table']['rows'][$id] = [
                '#type' => 'markup',
                '#prefix' => '<tr class="' . $rowClass . '">',
                '#suffix' => '</tr>',
            ];

            $options = array_combine(range(1, $noQuestions), range(1, $noQuestions));
            $form['table']['rows'][$id]['weight' . $id] = array(
                '#type' => 'select',
                '#title' => $this->t(''),
                '#options' => $options,
                '#default_value' => $id,
                '#id' => $question->question_id,
                '#attributes' => array(
                  'class' => array('weight-table-question'),
                  // 'onChange' => 'this.form.submit();',
                ),
                '#prefix' => '<td>',
                '#suffix' => '</td>',
              );

            $form['table']['rows'][$id]['title'] = array(
                '#type' => 'markup',
                '#titel' => '',
                '#markup' => '<td>' . $question->title . '</td>',
            );
            // create links to edit or delete a question
            $linkDelete = Link::fromTextAndUrl(t('Delete'), Url::fromRoute('real_time_delphi.add_question', ['answer_quantity_id' => 1]))->toString();
            $linkEdit = Link::fromTextAndUrl(t('Edit'), Url::fromRoute('real_time_delphi.edit_question', 
                            ['question_id' => $question->question_id, 'quantity_id' => $quan]))->toString();

            $form['table']['rows'][$id]['links'] = array(
                '#type' => 'markup',
                '#markup' => '<td>'
                  . $linkEdit
                  . '<br/>'
                  . $linkDelete
                  . '</td>'
              );



            $id++;
        }


        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save Changes'),
        ];

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        // \Drupal::messenger()->addMessage('dfdfffff');
        foreach ($form['table']['rows'] as $key => $row) {
            $rowData = $row['weight' . $key];
            $weightOld = $rowData['#default_value'];
            $weightNew = $rowData['#options'][$rowData['#value']];

            // Update only if the weight changed.
            // Break on first change since only one weight change is possible at a time (form gets updated).
            if ($weightNew !== $weightOld) {
                $questionId = $rowData['#id'];
                $this->delphi_question_update_question_order($questionId, $weightOld, $weightNew);
                break;
            }
        }
        $form_state->setRebuild();
    }

    /**
     * Updates the weight of a question and dependent questions to change the question order.
     *
     * @param $questionId
     *   The ID of the question to be updated.
     * @param $weightOld
     *   The question weight before the update.
     * @param $weightNew
     *   The new question weight.
     */
    public function delphi_question_update_question_order($questionId, $weightOld, $weightNew) {

        // Updates all question weights that are influenced by the new ordering (question in between the old und new weight).
        if ($weightNew > $weightOld) {
        while ($weightNew !== $weightOld) {
            $qId = $this->delphi_question_get_question_by_weight($weightOld + 1);
            $this->delphi_question_update_question_weight($qId, $weightOld);
            $weightOld++;
        }
    
        $this->delphi_question_update_question_weight($questionId, $weightNew);
    
        } else if ($weightNew < $weightOld) {
        while ($weightNew !== $weightOld) {
            $qId = $this->delphi_question_get_question_by_weight($weightOld - 1);
            $this->delphi_question_update_question_weight($qId, $weightOld);
            $weightOld--;
        }
    
        $this->delphi_question_update_question_weight($questionId, $weightNew);
        }
    }

    /**
     * Returns the questions ID that matches the given weight.
     *
     * @param $weight
     *   Question weight to look for.
     *
     * @return int
     *   Question ID.
     */
    public function delphi_question_get_question_by_weight($weight) {
        $query = $this->database->select('question', 'q');
        $query->addField('q', 'question_id');
        $query->condition('weight', $weight, '=');
        $id = $query->execute()->fetchField();
        //$id = db_query("SELECT question_id FROM {question} WHERE weight = :w",
        //array(':w' => $weight))->fetchField();
    
        return $id;
    }

    /**
     * Updates the weight of a question.
     *
     * @param $questionId
     *   ID of the question to be updated
     * @param $newWeight
     *   New question weight.
     */
    function delphi_question_update_question_weight($questionId, $newWeight) {
        /*
        db_update('question')
        ->fields(array(
            'weight' => $newWeight,
        ))
        ->condition('question_id', $questionId, '=')
        ->execute();
        */

        $query = $this->database->update('question');
        $query->fields([
            'weight' => $newWeight,
        ]);
        $query->condition('question_id', $questionId, '=');
        $query->execute();
    }
}
