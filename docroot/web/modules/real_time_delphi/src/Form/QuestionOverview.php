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
            '#title' => $this->t('Thesenübersicht'),
            '#prefix' => '<table class="select-table">',
            '#suffix' => '</table>'
          );

        //Kopf der Tabelle wird erstellt
        $form['table']['header'] = array(
            '#type' => 'markup',
            '#markup' => "<th>Nr.</th>
                    <th>These</th>
                    <th>Aktion</th>",
        );

        // load questions
        $question_result = $this->database->query("SELECT * FROM {question} ORDER BY weight, question_id");
        $noQuestions = $question_result->rowCount();

        $id=1;
        foreach ($question_result as $question) {
            // load answer options of the question
            $answer_result = $this->database->query("SELECT * FROM {question_possible_answers} 
                    WHERE question_id = :question_id", [':question_id' => $question->question_id]);
            
            // count the answers
            $quan = $answer_result->rowCount();

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
        }

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        
    }
}