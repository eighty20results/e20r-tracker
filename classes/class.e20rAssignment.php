<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 10/22/14
 * Time: 1:06 PM
 */

class e20rAssignment {

    public $_tables;

    private $article_id = null;

    private $answers = array();
    private $questions = array();

    public function __construct( $article_id = null ) {

        dbg("Loading assignments information");

        global $wpdb;

        $this->_tables = array(
            'questions' => $wpdb->prefix . 'e20r_questions',
            'responses' => $wpdb->prefix . 'e20r_answers',
        );

        $this->article_id = $article_id;

        if ( $this->article_id !== null ) {
            $this->loadQuestions();
            $this->loadAnswers();
        }
    }

    private function loadQuestions() {

        global $wpdb;

        if ( $this->article_id !== null ) {

            $sql = $wpdb->prepare(
                "
                SELECT *
                FROM {$this->tables['questions']}
                WHERE article_id = %d
            ", $this->article_id
            );

            $this->questions = $wpdb->get_results( $sql, OBJECT );
        }
    }

    private function loadAnswers() {

        global $wpdb;

        if ( $this->article_id !== null ) {

            $sql = $wpdb->prepare(
                "
                SELECT *
                FROM {$this->tables['answers']}
                WHERE article_id = %d
            ", $this->article_id
            );

            $this->questions = $wpdb->get_results( $sql, OBJECT );
        }
    }

    /**
     * @param null $article_id -- The ID of the article (from the e20r_articles table)
     * @param null $type -- E20R_QUESTIONS | E20R_ANSWERS (or null == both)
     *
     * @return bool -- Success if we loaded data from the DB.
     */
    public function getFromDB( $article_id = null, $type = null ) {

        // No article ID set. Return error (null)
        if ( ( $article_id === null ) && ( $this->article_id === null ) ) {
            return false;
        }


        return true;
    }

} 