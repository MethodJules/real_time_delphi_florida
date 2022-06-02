<?php

namespace Drupal\real_time_delphi\Graphic;
use Drupal\real_time_delphi\Traits\DataTrait;

class Boxplot {
    use DataTrait;
    //Create boxplot
    public function get_graphic($id, $isRadioButton, $indexNoAnswer, $question_id, $user_pw) {

        //Add js
        //drupal_add_js(drupal_get_path('module', 'survey') . '/js/survey_create_boxplot.js', 'footer');
        //drupal_add_css(drupal_get_path('module', 'survey') . '/css/survey_create_boxplot.css');

        $boxplotNoData = [
            '#type' => 'markup',
            '#markup' => '<div class = "boxplot-nodata">' . t('Zu diesem Thema wurden noch nicht genügend Einschätzungen abgegeben.') . '</div>',
        ];

        //$question_id = arg(1);
        //$user_pw = arg(2);

        $testArrayOverall = array();
        $testArrayOverall2 = array();

        /*
        $quantity_check_result = db_query("SELECT * FROM {question_user_answers} WHERE question_id = :question_id AND answer_id = :answer_id AND is_last_answer = 1", array(
            ':question_id' => $question_id,
            ':answer_id' => $id
        ));
        */

        $database = \Drupal::database();
        $query = $database->select('question_user_answers', 'qba');
        $query->fields('qba');
        $query->condition('question_id', $question_id);
        $query->condition('answer_id', $id);
        $query->condition('is_last_answer', 1);
        $quantity_check_result = $query->execute();

        $threshold = \Drupal::config('real_time_delphi.settings')->get('threshold');


        //Sollten es mindestens fünf Antworten geben, kann der Boxplot erstellt werden
        if ($query->countQuery()->execute()->fetchField() > $threshold) { //TODO Change to config

            foreach ($quantity_check_result as $question_user_answer) {

                //Die Antwort des aktuellen Nutzers wird nicht eingerechnet
                if (strcmp($user_pw, $question_user_answer->user_pw)) {


                    $user = $question_user_answer->user_pw;
                    $check = $this->survey_create_boxplot_search_for_user_ID($user, $testArrayOverall2);

                    if ($check > -1) {
                        unset($testArrayOverall2[$check]);
                        unset($testArrayOverall[$check]);
                    }
                    array_push($testArrayOverall2, $question_user_answer);

                    // "keine Ahnung" nicht als Antwort für Boxplot Berechnungen nutzen
                    if ($question_user_answer->answer != $indexNoAnswer && $question_user_answer->answer != 'answer_NA') {
                        array_push($testArrayOverall, $question_user_answer->answer);
                    }
                }
            }

            // corner case if ALL given answers are 'keine Angabe', thus no boxplot can be plotted.
            if (empty($testArrayOverall)) {
                return $boxplotNoData;
            }

            //Die Antworten werden der Größe nach geordnet
            sort($testArrayOverall);

            //Wenn es sich bei der Antwortmöglichkeit um Radio-Buttons handelt, kann ein Boxplot erstellt werden
            if ($isRadioButton) {

                //Die einzelnen Bestandteile eines Boxplots werden ermittelt
                $min = 0;
                // Maximum der Boxplot-Skala, k.A. nicht Teil der Skala
                $max = $indexNoAnswer - 1;
                // TODO Berechnungfunktionen prüfen und refaktorieren, unnötige Unterscheidung, die nur wieder +1 addiert
                $avg = $this->survey_get_average($testArrayOverall, $isRadioButton, $max);
                $median = $this->survey_get_median($testArrayOverall, $isRadioButton, $max);
                $first_quantil = $this->survey_get_first_quantil($testArrayOverall, $isRadioButton, $max);
                $third_quantil = $this->survey_get_third_quantil($testArrayOverall, $isRadioButton, $max);
                $third_quantil2 = $third_quantil["procent"] - $first_quantil["procent"];

                //Für freie Antworten muss ein eigener Boxplot erstellt werden
            } else {

                $max = $testArrayOverall[count($testArrayOverall)-1];
                $avg = $this->survey_get_average($testArrayOverall, $isRadioButton, $max);
                $median = $this->survey_get_median($testArrayOverall, $isRadioButton, $max);
                $first_quantil = $this->survey_get_first_quantil($testArrayOverall, $isRadioButton, $max);
                $third_quantil = $this->survey_get_third_quantil($testArrayOverall, $isRadioButton, $max);
                $min = array_shift($testArrayOverall);
                $third_quantil2 = $third_quantil["procent"] - $first_quantil["procent"];
            }


            $links = array(0 => "Lower Quartile", 1 => "Average", 2 => "Median", 3 => "Upper Quartile");

            $boxplot_string = array(
                'container' => array(
                    '#prefix' => '<div id="eins">',
                    '#suffix' => '</div>',
                    'boxplot' => array(
                        '#prefix' => '<div class="boxplot">',
                        '#suffix' => '</div>',
                        'boxlinie' => array(
                            '#prefix' => '<div class="box linie">',
                            '#suffix' => '</div>',
                        ),
                        'boxwhisker' => array(
                            '#prefix' => '<div class="box whisker">',
                            '#suffix' => '</div>',
                        ),
                        'boxinterquant' => array(
                            '#prefix' => '<div class="box interquart" style="left: ' . $first_quantil["procent"] . '%;">',
                            '#suffix' => '</div>',
                        ),
                        'boxthirdquant' => array(
                            '#prefix' => '<div class="box thirdquant" style="left: calc(' . $third_quantil2 . '% + ' . $first_quantil["procent"] . '%); width: ' . $third_quantil2 . '%; margin-left: -' . $third_quantil2 . '%;">',
                            '#suffix' => '</div>',
                        ),
                        'boxmedian' => array(
                            '#prefix' => '<div class="box median" style="left: ' . $median["procent"] . '%;">',
                            '#suffix' => '</div>',
                        ),
                        'boxmittel' => array(
                            '#prefix' => '<div class="box mittel" style="left: ' . $avg["procent"] . '%;">',
                            '#suffix' => '</div>',
                        ),
                        's_min' => array(
                            '#prefix' => '<span class="schild s_min" style="left: 0%;">',
                            '#suffix' => '</span>',
                            'markup' => array(
                                //'#markup' => _formatNumber($min+1),
                                '#markup' => 'min(' . $this->formatNumber($min + 1) . ')',
                            ),
                        ),
                        's_average' => array(
                            '#prefix' => '<span class="schild min s_average" style="margin-left: ' . $avg["procent"] . '%;">',
                            '#suffix' => '</span>',
                            'markup' => array(
                                '#markup' => $this->formatNumber($avg["absolut"] + 1),
                            ),
                        ),
                        's_median' => array(
                            '#prefix' => '<span class="schild min s_median" style="margin-left: ' . $median["procent"] . '%;"> ',
                            '#suffix' => '</span>',
                            'markup' => array(
                                '#markup' => $this->formatNumber($median["absolut"] + 1),
                            ),
                        ),
                        's_third_quantil' => array(
                            '#prefix' => '<span class="schild min s_third_quantil" style="margin-left: ' . $third_quantil["procent"] . '%;"> ',
                            '#suffix' => '</span>',
                            'markup' => array(
                                '#markup' => $this->formatNumber($third_quantil["absolut"] + 1),
                            ),
                        ),
                        's_first_quantil' => array(
                            '#prefix' => '<span class="schild s_first_quantil" style="margin-left: ' . $first_quantil["procent"] . '%;"> ',
                            '#suffix' => '</span>',
                            'markup' => array(
                                '#markup' => $this->formatNumber($first_quantil["absolut"] + 1),
                            ),
                        ),
                        's_max' => array(
                            '#prefix' => '<span class="schild min s_max" style="margin-left: 100%;"> ',
                            '#suffix' => '</span>',
                            'markup' => array(
                                //'#markup' => _formatNumber($max+1),
                                '#markup' => 'max(' . $this->formatNumber($max+1) . ')'
                            ),
                        ),
                    ),
                    'legend' => array(
                        '#theme' => 'item_list',
                        '#items' => $links,
                        '#type' => 'ul',
                        '#prefix' => '<div class="legend">',
                        '#suffix' => '</div>',
                        '#attributes' => array('class' => 'my-list'),
                    ),
                    'tooltip' => array(
                        '#prefix' => '<div class="tooltip"><span>Infos zum Boxplot</span><div class="boxplot_description">',
                        '#suffix' => '</div></div>',
                        '#theme' => 'table',
                        '#header' => array('Kennzahl', 'Beschreibung'),
                        '#rows' => array(
                            array('Lower Quartile', 'The smallest 25% of the data values are less than or equal to this charateristic value'),
                            array('Median', 'The smallest 50% of the data values are less than or equal to this charateristic value'),
                            array('Upper Quartile', 'The smallest 75% of the data values are less than or equal to this charateristic value'),
                            array('Maximum', 'Biggest Value of the dataset')
                        ), 
                    ),
                ),


            );

        } else {
            $boxplot_string = $boxplotNoData;
        }


        return \Drupal::service('renderer')->render($boxplot_string);
    }

    /**
     * Formatiert Rationale Zahlen auf eine Nachkommastelle mit Komma als Dezimaltrennzeichen. Ganze Zahlen werden ohne
     * Nachkommastellen ausgegeben.
     *
     * @param number|string $number
     *   Unformatierte Zahl
     *
     * @return number|string
     *   Formatierte Zahl
     */
    public function formatNumber($number) {
        if (is_numeric($number) && floor($number) != $number) {
        $number = number_format($number,1, ",", "");
        } else {
        $number = number_format($number,0);
        }
    
        return $number;
    }

    /*
    * Diese Funktion überprüft, ob das User-PW valide ist
    */
    public function survey_create_boxplot_search_for_user_ID($id, $array)
    {

        $check = -1;

        foreach ($array as $key => $val) {
            if (!strcmp($val->user_pw, $id)) {
                $check = $key;
            }
        }
        return $check;
    }
}