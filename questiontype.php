<?php

/**
 * The question type class for the matrix question type.
 *
 * @copyright &copy; 2009 Penny Leach
 * @author penny@liip.ch
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package matrix
 */
// renderer for the whole question - needs a matching class
// see matrix_qtype::matrix_renderer_options
define('QTYPE_MATRIX_RENDERER_MATRIX', 'matrix'); // more later
// grading methods for rows - need matching classes
// see matrix_qtype::matrix_grading_options
define('QTYPE_MATRIX_GRADING_KPRIME', 'kprime');
define('QTYPE_MATRIX_GRADING_ANY', 'any');
define('QTYPE_MATRIX_GRADING_ALL', 'all');
define('QTYPE_MATRIX_GRADING_NONE', 'none');
define('QTYPE_MATRIX_GRADING_WEIGHTED', 'weighted');

define('QTYPE_MATRIX_NEWHEADER_STARTCOUNT', 1000);

/**
 * Array Get. If there is an element in $data with $key as a key returns it. Otherwise returns $default.
 *
 * @param any $data
 * @param string $key
 * @param any $default
 * @return any
 */
function aget($data, $key, $default = null)
{
    if (is_array($data))
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
    else
    {
        return $default;
    }
}

/**
 * The matrix question class
 *
 * Pretty simple concept - a matrix with a number of different grading methods and options.
 */
class matrix_qtype extends default_questiontype
{

    function name()
    {
        return 'matrix';
    }

    /**
     * @return boolean true if this question type sometimes requires manual grading.
     */
    function is_manual_graded()
    {
        return true;
    }

    /**
     * whether this question can be automatically graded
     * dependent on the grade method
     */
    function is_question_manual_graded($question, $otherquestionsinuse)
    {
        if (!$matrix = get_record('question_matrix', 'questionid', $question->id))
        {
            return false; // sensible default
        }
        $gradeclass = self::grade_class($matrix->grademethod, $matrix->multiple);
        return $gradeclass->is_manual_graded();
    }

    /*
     * @return boolean to indicate success of failure.
     */

    function get_question_options(&$question)
    {
        $data = self::load_all_data($question->id);
        $question->options->rows = $data->rows;
        $question->options->cols = $data->cols;
        $question->options->weights = $data->fullmatrix;
        $question->options->grademethod = $data->grademethod;
        $question->options->multiple = $data->multiple;
        return true;
    }

    /**
     * Save the units and the answers associated with this question.
     * @return boolean to indicate success of failure.
     */
    function save_question_options($question)
    {
        $existingdata = self::load_all_data($question->id);
        if (!empty($question->makecopy))
        {
            $existingdata = false;
        }
        begin_sql();

        $matrix = (object) array(
                    'questionid' => $question->id,
                    'multiple' => $question->multiple,
                    'grademethod' => $question->grademethod,
                    'renderer' => 'matrix'
        );
        if (!$existingdata)
        {
            $matrixid = insert_record('question_matrix', $matrix);
        }
        else
        {
            $matrix->id = $matrixid = $existingdata->id;
            update_record('question_matrix', $matrix);
        }

        $rowcoltemplate = array(
            'matrixid' => $matrixid,
        );

        $rowids = array(); // mapping for indexes to db ids.
        $colids = array();

        $newrowcount = QTYPE_MATRIX_NEWHEADER_STARTCOUNT;
        $newcolcount = QTYPE_MATRIX_NEWHEADER_STARTCOUNT;

        $colcount = matrix_qtype::count_form_rows_or_cols((array) $question, false);
        $rowcount = matrix_qtype::count_form_rows_or_cols((array) $question);
        // rows
        for ($i = 0; $i < $rowcount; $i++)
        {

            $row = (object) array_merge($rowcoltemplate, array(
                        'shorttext' => $question->rowshort[$i],
                        'description' => aget($question->rowlong, $i, ''),
                        'feedback' => $question->rowfeedback[$i],
                        'id' => @$question->rowid[$i],
                            )
            );
            if (!$existingdata || !array_key_exists($row->id, $existingdata->rows))
            {
                $oldid = $row->id;
                unset($row->id);
                $newid = insert_record('question_matrix_rows', $row);
                if (!empty($oldid))
                { // this happens when we copy a question
                    $rowids[$oldid] = $newid;
                }
                else
                {
                    $rowids[$newrowcount] = $newid;
                    $newrowcount++;
                }
            }
            else if (array_key_exists($row->id, $existingdata->rows))
            {
                $rowids[$row->id] = $row->id;
                update_record('question_matrix_rows', $row);
            }
        }

        // cols
        for ($i = 0; $i < $colcount; $i++)
        {
            $col = (object) array_merge($rowcoltemplate, array(
                        'shorttext' => $question->colshort[$i],
                        'description' => $question->collong[$i],
                        'id' => @$question->colid[$i],
                            )
            );
            if (!$existingdata || !array_key_exists($col->id, $existingdata->cols))
            {
                $oldid = $col->id;
                unset($col->id);
                $newid = insert_record('question_matrix_cols', $col);
                if (!empty($oldid))
                {
                    $colids[$oldid] = $newid;
                }
                else
                {
                    $colids[$newcolcount] = $newid;
                    $newcolcount++;
                }
            }
            else if (array_key_exists($col->id, $existingdata->cols))
            {
                $colids[$col->id] = $col->id;
                update_record('question_matrix_cols', $col);
            }
        }


        $matrix = self::formdata_to_matrix((array) $question);
        $gradeclass = matrix_qtype::grade_class($question->grademethod, $question->multiple);
        $percents = $gradeclass->store_percentages();
        global $CFG;
        delete_records_select('question_matrix_weights', 'rowid IN (
                    SELECT id FROM ' . $CFG->prefix . 'question_matrix_rows
                    WHERE matrixid = ' . $matrixid . ')
                OR colid IN (
                    SELECT id FROM ' . $CFG->prefix . 'question_matrix_cols
                    WHERE matrixid = ' . $matrixid . ')');

        foreach ($matrix as $r => $row)
        {
            foreach ($row as $c => $cell)
            {
                if (empty($cell))
                {
                    continue;
                }
                $weight = (object) array(
                            'rowid' => $rowids[$r],
                            'colid' => $colids[$c],
                            'weight' => (($percents) ? $cell : '1'),
                );
                insert_record('question_matrix_weights', $weight);
            }
        }

        if ($rowids)
        {
            delete_records_select('question_matrix_rows', 'matrixid = ' . $matrixid . ' AND id NOT IN (' . implode(',', $rowids) . ')');
        }
        if ($colids)
        {
            delete_records_select('question_matrix_cols', 'matrixid = ' . $matrixid . ' AND id NOT IN (' . implode(',', $colids) . ')');
        }

        return commit_sql();
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @param integer $questionid The question being deleted
     * @return boolean to indicate success of failure.
     */
    function delete_question($questionid)
    {
        global $CFG;
        $firstselect = 'matrixid IN (SELECT id from ' . $CFG->prefix . 'question_matrix
            WHERE questionid = ' . $questionid . ')';
        $secondselect = 'rowid IN (
                    SELECT id FROM ' . $CFG->prefix . 'question_matrix_rows
                    WHERE ' . $firstselect . ' )
                OR colid IN (
                    SELECT id FROM ' . $CFG->prefix . 'question_matrix_cols
                    WHERE ' . $firstselect . ')';
        delete_records_select('question_matrix_weights', $secondselect);
        delete_records_select('question_matrix_rows', $firstselect);
        delete_records_select('question_matrix_cols', $firstselect);
        delete_records('question_matrix', 'questionid', $questionid);
        return true;
    }

    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt)
    {
        $state->responses = array();
        return true;
    }

    function restore_session_and_responses(&$question, &$state)
    {
        if (!is_array($state->responses) || count($state->responses) != 1)
        {
            return false;
        }
        $tmp = array_values($state->responses);
        $tmp = stripslashes($tmp[0]);
        if (!is_string($tmp))
        {
            return false;
        }
        if (!$data = @unserialize($tmp))
        {
            return false;
        }

        $state->responses = array();
        $gradeclass = matrix_qtype::grade_class($question->options->grademethod, $question->options->multiple);
        foreach ($data as $r => $row)
        {
            foreach ($row as $c => $value)
            {
                $cellname = $gradeclass->cellname($r, $c);
                if ($value == 'on')
                { // checkbox
                    $state->responses[$cellname] = $value;
                }
                else if ($value == $c)
                { // radio
                    $state->responses[$cellname] = $c;
                }
            }
        }
        return true;
    }

    function save_session_and_responses(&$question, &$state)
    {
        $matrix = self::cells_to_matrix($state->responses, $question->options->rows, $question->options->cols);
        $responses = serialize($matrix);
        return set_field('question_states', 'answer', $responses, 'id', $state->id);
    }

    /**
     * Returns correct responses. That is all that have a weight greater than 0. 
     */
    function get_correct_responses(&$question, &$state)
    {
        $result = array();
        $weights = $question->options->weights;
        $gradeclass = matrix_qtype::grade_class($question->options->grademethod, $question->options->multiple);
        foreach ($weights as $row_id => $row)
        {
            foreach ($row as $col_id => $weight)
            {
                $weight = (float) $weight;
                if ($weight > 0)
                {
                    $cell_name = $gradeclass->cellname($row_id, $col_id);
                    $result[$cell_name] = $question->options->multiple ? $weight : $col_id;
                }
            }
        }
        return $result;
    }

    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options)
    {
        global $CFG;
        $readonly = empty($options->readonly) ? '' : ' readonly="readonly" ';

        // Print formulation
        $questiontext = $this->format_text($question->questiontext, $question->questiontextformat, $cmoptions);

        //$image = get_question_image($question, $cmoptions->course);
        $image = null; //does not look supported in moodle 2.0 anymore.

        $showcorrect = !empty($options->correct_responses);
        $gradeclass = matrix_qtype::grade_class($question->options->grademethod, $question->options->multiple);
        if ($gradeclass->is_manual_graded())
        {
            $showcorrect = false;
        }

        $matrix = self::render_full_matrix($question, $state->responses, $readonly, $showcorrect);

        include("$CFG->dirroot/question/type/matrix/display.html");
    }

    function grade_responses(&$question, &$state, $cmoptions)
    {
        $gradeclass = matrix_qtype::grade_class($question->options->grademethod, $question->options->multiple);

        if ($gradeclass->is_manual_graded())
        {
            $state->raw_grade = 0;
            $state->penalty = 0;

            return true;
        }
        $gradeclass->set_weights($question->options->weights);
        $subqs = $gradeclass->grade_matrix($state->responses);

        $state->raw_grade = $gradeclass->grade_question($subqs);
        //
        // Make sure we don't assign negative or too high marks.
        $state->raw_grade = min(max((float) $state->raw_grade, 0.0), 1.0) * $question->maxgrade;

        // Update the penalty.
        $state->penalty = $question->penalty * $question->maxgrade;

        // mark the state as graded
        $state->event = ($state->event == QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;
        return true;
    }

    function compare_responses($question, $state, $teststate)
    {
        // compare arrays with array_diff_assoc and not array_diff as the former one compares only values.
        $result = (count(array_diff_assoc($state->responses, $teststate->responses)) == 0
                && count(array_diff_assoc($teststate->responses, $state->responses)) == 0);

        return $result;
    }

    function get_actual_response($question, $state)
    {
        $matrix = self::cells_to_matrix($state->responses, $question->options->rows, $question->options->cols);
        $responses = array();
        foreach ($matrix as $r => $row)
        {
            $response = self::render_matrix_header($question->options->rows[$r], true) . ': ';
            $colcount = 0;
            foreach ($row as $c => $cell)
            {
                if ($cell == 'on' || $cell == $c)
                {
                    $response .= self::render_matrix_header($question->options->cols[$c], true) . ', ';
                    $colcount++;
                }
            }
            if ($colcount)
            {
                $response = substr($response, 0, -2);
            }
            $responses[] = $response;
        }
        return $responses;
    }

    /**
     * Backup the data in the question
     *
     * This is used in question/backuplib.php
     */
    function backup($bf, $preferences, $question, $level=6)
    {
        $status = true;
        if (!$alldata = self::load_all_data($question))
        {
            return $status;
        }
        $status = $status && fwrite($bf, start_tag('MATRIX', $level, true));
        $status = $status && fwrite($bf, full_tag('QUESTIONID', $level + 1, false, $question));
        $status = $status && fwrite($bf, full_tag('GRADEMETHOD', $level + 1, false, $alldata->grademethod));
        $status = $status && fwrite($bf, full_tag('MULTIPLE', $level + 1, false, $alldata->multiple));
        $status = $status && fwrite($bf, full_tag('RENDERER', $level + 1, false, $alldata->renderer));
        $status = $status && fwrite($bf, start_tag('ROWS', $level + 1, true));
        foreach ($alldata->rows as $row)
        {
            $status = $status && fwrite($bf, start_tag('ROW', $level + 2, true));
            $status = $status && fwrite($bf, full_tag('ID', $level + 3, false, $row->id));
            $status = $status && fwrite($bf, full_tag('MATRIXID', $level + 3, false, $row->matrixid));
            $status = $status && fwrite($bf, full_tag('SHORTTEXT', $level + 3, false, $row->shorttext));
            $status = $status && fwrite($bf, full_tag('DESCRIPTION', $level + 3, false, $row->description));
            $status = $status && fwrite($bf, end_tag('ROW', $level + 2, true));
        }
        $status = $status && fwrite($bf, end_tag('ROWS', $level + 1, true));
        $status = $status && fwrite($bf, start_tag('COLS', $level + 1, true));
        foreach ($alldata->cols as $col)
        {
            $status = $status && fwrite($bf, start_tag('COL', $level + 2, true));
            $status = $status && fwrite($bf, full_tag('ID', $level + 3, false, $col->id));
            $status = $status && fwrite($bf, full_tag('MATRIXID', $level + 3, false, $col->matrixid));
            $status = $status && fwrite($bf, full_tag('SHORTTEXT', $level + 3, false, $col->shorttext));
            $status = $status && fwrite($bf, full_tag('DESCRIPTION', $level + 3, false, $col->description));
            $status = $status && fwrite($bf, end_tag('COL', $level + 2, true));
        }
        $status = $status && fwrite($bf, end_tag('COLS', $level + 1, true));
        $status = $status && fwrite($bf, start_tag('WEIGHTS', $level + 1, true));
        foreach ($alldata->rawweights as $weight)
        {
            $status = $status && fwrite($bf, start_tag('WEIGHT', $level + 2, true));
            $status = $status && fwrite($bf, full_tag('ID', $level + 3, false, $weight->id));
            $status = $status && fwrite($bf, full_tag('ROWID', $level + 3, false, $weight->rowid));
            $status = $status && fwrite($bf, full_tag('COLID', $level + 3, false, $weight->colid));
            $status = $status && fwrite($bf, full_tag('WEIGHT', $level + 3, false, $weight->weight));
            $status = $status && fwrite($bf, end_tag('WEIGHT', $level + 2, true));
        }
        $status = $status && fwrite($bf, end_tag('WEIGHTS', $level + 1, true));

        $status = $status && fwrite($bf, end_tag('MATRIX', $level, true));
        $status = question_backup_answers($bf, $preferences, $question);

        return $status;
    }

    /**
     * Restores the data in the question
     *
     * This is used in question/restorelib.php
     */
    function restore($old_question_id, $new_question_id, $info, $restore)
    {
        $status = begin_sql();

        $minfo = $info['#']['MATRIX'];
        $newmatrix = (object) array(
                    'questionid' => $new_question_id,
                    'grademethod' => backup_todb($minfo[0]['#']['GRADEMETHOD']['0']['#']),
                    'multiple' => backup_todb($minfo[0]['#']['MULTIPLE']['0']['#']),
                    'renderer' => backup_todb($minfo[0]['#']['RENDERER']['0']['#']),
        );

        $newmatrix->id = insert_record('question_matrix', $newmatrix);

        $rows = $minfo[0]['#']['ROWS'][0]['#']['ROW']; // why does this get eaten?!
        $rowmapping = array();
        foreach ($rows as $row)
        {
            $row = $row['#']; // more nonsense
            $newrow = (object) array(
                        'matrixid' => $newmatrix->id,
                        'shorttext' => backup_todb($row['SHORTTEXT']['0']['#']),
                        'description' => backup_todb($row['DESCRIPTION']['0']['#']),
            );
            $status = $status && $rowmapping[backup_todb($row['ID']['0']['#'])] = insert_record('question_matrix_rows', $newrow);
        }

        $cols = $minfo[0]['#']['COLS'][0]['#']['COL']; // why does this get eaten?!
        $colmapping = array();
        foreach ($cols as $col)
        {
            $col = $col['#']; // more nonsense
            $newcol = (object) array(
                        'matrixid' => $newmatrix->id,
                        'shorttext' => backup_todb($col['SHORTTEXT']['0']['#']),
                        'description' => backup_todb($col['DESCRIPTION']['0']['#']),
            );
            $status = $status && $colmapping[backup_todb($col['ID']['0']['#'])] = insert_record('question_matrix_cols', $newcol);
        }

        $weights = $minfo[0]['#']['WEIGHTS'][0]['#']['WEIGHT'];
        foreach ($weights as $weight)
        {
            $weight = $weight['#'];
            $newweight = (object) array(
                        'rowid' => $rowmapping[backup_todb($weight['ROWID'][0]['#'])],
                        'colid' => $colmapping[backup_todb($weight['COLID'][0]['#'])],
                        'weight' => backup_todb($weight['WEIGHT'][0]['#']),
            );
            $status = $status && insert_record('question_matrix_weights', $newweight);
        }
        return $status && commit_sql();
    }


    /**
     * Add styles.css to the page's header
     */
    function get_html_head_contributions(&$question, &$state)
    {
        return parent::get_html_head_contributions($question, $state);
    }

    static function matrix_grading_options()
    {
        return array(
            QTYPE_MATRIX_GRADING_KPRIME => get_string('grading.kprime', 'qtype_matrix'),
            QTYPE_MATRIX_GRADING_ANY => get_string('grading.any', 'qtype_matrix'),
            QTYPE_MATRIX_GRADING_ALL => get_string('grading.all', 'qtype_matrix'),
            QTYPE_MATRIX_GRADING_NONE => get_string('grading.none', 'qtype_matrix'),
            QTYPE_MATRIX_GRADING_WEIGHTED => get_string('grading.weighted', 'qtype_matrix'),
        );
    }

    static function matrix_renderer_options()
    {
        return array(
            QTYPE_MATRIX_RENDERER_MATRIX => get_string('matrix', 'qtype_matrix'),
        );
    }

    static function count_form_rows_or_cols($data, $row=true, $returnvals=false)
    {
        $key = ($row) ? 'row' : 'col';
        $count = 0;
        $vals = array();
        $newvalcount = QTYPE_MATRIX_NEWHEADER_STARTCOUNT;

        $short_count = count($data[$key . 'short']);
        $long_count = count($data[$key . 'long']);
        $min = min($short_count, $long_count);
        for ($k = 0; $k < $min; $k++)
        {
            $short = aget($data, $key . 'short');  //isset($data[$key . 'short'][$k]) ? $data[$key . 'short'][$k] : '';
            $short = aget($short, $k);
            $long = aget($data, $key . 'long'); //isset($data[$key . 'long'][$k]) ? $data[$key . 'long'][$k] : '';
            $long = aget($long, $k);
            $feedback = aget($data, $key . 'feedback');
            $feedback = aget($feedback, $k);

            $shorttext = is_array($short) ? reset($short) : $short;
            $description = is_array($long) ? reset($long) : $long;
            $format = is_array($long) ? end($long) : '';

            if (!empty($short) || !empty($long))
            {
                $count++;
                if ($returnvals)
                {
                    if (array_key_exists($key . 'id', $data) && array_key_exists($k, $data[$key . 'id']) && !empty($data[$key . 'id'][$k]))
                    {
                        $thiskey = $data[$key . 'id'][$k];
                    }
                    else
                    {
                        $thiskey = $newvalcount;
                        $newvalcount++;
                    }
                    $vals[$thiskey] = compact('shorttext', 'description', 'format', 'feedback');
                }
            }
        }
        return $returnvals ? $vals : $count;
    }

    static function count_db_rows_or_cols($questionid, $table='rows', $returnvals=false)
    {
        global $CFG;
        $table = 'question_matrix_' . $table;
        $select = 'matrixid IN (SELECT id FROM ' . $CFG->prefix . 'question_matrix WHERE questionid = ' . $questionid . ')';
        if (!$returnvals)
        {
            return (int) count_records_select($table, $select);
        }
        return get_records_select($table, $select);
    }

    /**
     * Returns the grading object
     *
     * @staticvar array $cache
     * @param string $gradetype
     * @param bool $multiple
     * @return matrix_qtype_grading_base
     */
    static function grade_class($gradetype, $multiple)
    {
        static $cache = array();
        $classname = 'matrix_qtype_grading_' . $gradetype;
        if (!array_key_exists($gradetype . '|' . $multiple, $cache))
        {
            if (!class_exists($classname))
            {
                $subdirlib = dirname(__FILE__) . '/grading/' . $gradetype . '/lib.php';
                if (file_exists($subdirfile))
                {
                    include_once($subdirfile);
                }
            }
            if (!class_exists($classname))
            {
                print_error("Invalid grade class $classname");
            }
        }
        $cache[$gradetype . '|' . $multiple] = new $classname($multiple);
        return $cache[$gradetype . '|' . $multiple];
    }

    static function single_default_grademethod()
    {
        return 'all'; // same logic, just n = 1
    }

    static function formdata_to_matrix($data)
    {
        $matrix = array();

        if (!array_key_exists('gradingmatrix', $data))
        {
            return $matrix;
        }

        $rows = self::count_form_rows_or_cols($data, true, true);
        $cols = self::count_form_rows_or_cols($data, false, true);
        $data = $data['gradingmatrix'];

        return self::cells_to_matrix($data, $rows, $cols);
    }

    static function cells_to_matrix($data, $rows, $cols)
    {
        foreach ($rows as $i => $row)
        {
            $matrix[$i] = array();
            foreach ($cols as $j => $col)
            {
                if (isset($data['cell' . $i . 'x' . $j]))
                {
                    $matrix[$i][$j] = $data['cell' . $i . 'x' . $j];
                }
                else if (isset($data['cell' . $i]) && $data['cell' . $i] == $j)
                {
                    $matrix[$i][$j] = $j;
                }
                else
                {
                    $matrix[$i][$j] = null;
                }
            }
        }
        return $matrix;
    }

    static function load_all_data($questionid)
    {

        static $cache = array();
        if (array_key_exists($questionid, $cache))
        {
            return $cache[$questionid];
        }
        if (!$matrix = get_record('question_matrix', 'questionid', $questionid))
        {
            return false;
        }
        if (!$matrix->rows = get_records('question_matrix_rows', 'matrixid', $matrix->id))
        {
            $matrix->rows = array();
            $rowsql = '';
        }
        else
        {
            $rowsql = 'rowid IN ( ' . implode(',', array_keys($matrix->rows)) . ')';
        }
        if (!$matrix->cols = get_records('question_matrix_cols', 'matrixid', $matrix->id))
        {
            $matrix->cols = array();
            $colsql = '';
        }
        else
        {
            $colsql = 'colid IN ( ' . implode(',', array_keys($matrix->cols)) . ')';
        }

        $matrix->gradingmatrix = array();
        $matrix->fullmatrix = array();

        foreach ($matrix->rows as $r)
        {
            $matrix->rowshort[] = $r->shorttext;
            $matrix->rowlong[] = $r->description;
            $matrix->rowfeedback[] = $r->feedback;
            $matrix->fullmatrix[$r->id] = array();
        }
        foreach ($matrix->cols as $c)
        {
            $matrix->colshort[] = $c->shorttext;
            $matrix->collong[] = $c->description;
            // create an empty hole
            foreach ($matrix->fullmatrix as $r => $null)
            {
                $matrix->fullmatrix[$r][$c->id] = null;
            }
        }

        if (empty($rowsql) || empty($colsql))
        {
            $cache[$questionid] = $matrix;
            return $matrix;
        }
        if (!$matrix->rawweights = get_records_select('question_matrix_weights', $rowsql . ' AND ' . $colsql))
        {
            $matrix->rawweights = array();
        }


        foreach ($matrix->rawweights as $w)
        {
            $gradeclass = matrix_qtype::grade_class($matrix->grademethod, $matrix->multiple);
            $cellname = $gradeclass->cellname($w->rowid, $w->colid);
            if (!$matrix->multiple)
            {
                $matrix->gradingmatrix[$cellname] = $w->colid;
            }
            else
            {
                $matrix->gradingmatrix[$cellname] = $w->weight;
            }
            $matrix->fullmatrix[$w->rowid][$w->colid] = $w->weight;
        }
        $cache[$questionid] = $matrix;
        return $matrix;
    }

    static function render_matrix_header($header, $nohtml=false)
    {
        $header = (object) $header;

        $description = $header->description;
        $text = $header->shorttext;

        if ($nohtml)
        {
            return trim(strip_tags($text));
        }

        if (strip_tags($description))
        {
            $description = '<span class="qtypematrixdescription" style="display:none;position:relative;">' . $description . '</span>';
        }
        else
        {
            $description = '';
        }

        $result = '<span class="qtypematrixtitle">' . $text . $description . '</span>';
        return $result;
    }

    static function render_full_matrix(&$question, $responses, $readonly=false, $showcorrect=false)
    {
        global $CFG;
        $alldata = self::load_all_data($question->id);        
        $table = new StdClass;
        $table->head = array('');        
        $table->class  = 'qtypematrixformmatrix';
        $addheader = true;
        $gradeclass = self::grade_class($alldata->grademethod, $alldata->multiple);
        $gradeclass->set_question($question);
        $gradeclass->set_responses($responses);
        $gradeclass->set_weights($alldata->fullmatrix);

        $grades = $showcorrect ? $gradeclass->grade_matrix($responses) : null;

        foreach ($alldata->fullmatrix as $j => $row)
        {
            $thisrow = array(self::render_matrix_header($alldata->rows[$j]));
            foreach ($row as $i => $col)
            {
                $addheader && $table->head[] = self::render_matrix_header($alldata->cols[$i]);
                $thisrow[] = $gradeclass->render_cell($j, $i, $readonly, $showcorrect);
            }
            if ($showcorrect)
            {
                $feedback = $alldata->rows[$j]->feedback;
                $feedback = strip_tags($feedback) ? '&nbsp;' . $feedback : '';
                $thisrow[] = question_get_feedback_image($grades[$j]) . $feedback;
            }
            $addheader && $showcorrect && $table->head[] = '';
            $addheader = false;
            $table->data[] = $thisrow;
        }
        return print_table($table, true);
    }
}

/**
 * base class for grading types
 * this class is responsible for anything to do with grading
 * and does some validation and rendering tasks.
 *
 * @abstract
 */
abstract class matrix_qtype_grading_base
{

    /**
     * whether multiple responses are allowed
     */
    protected $multiple;
    /**
     * the question this belongs to
     */
    protected $question;
    /**
     * the already set weightings/answers for this matrix
     */
    protected $weights;
    /**
     * responses from the user
     */
    protected $responses;

    /**
     * constructor. only parameter always needed is multiple, so accept it here
     *
     * @param boolean whether multiple responses are allowed
     */
    public function __construct($multiple)
    {
        $this->multiple = $multiple;
    }

    /**
     * create the element to insert into the form for the teacher defining the question
     *
     * @param MoodleQuickForm $mform the form to use for ->createElement
     * @param int $x rowid
     * @param int $y colid
     *
     * @return form element.
     */
    abstract public function create_defining_cell_element(MoodleQuickForm &$mform, $x, $y);

    /**
     * grade the matrix rawly - just based on the contents
     * and not taking penalties or anything else into account
     *
     * @param array $matrix containing [$cellname] => on
     *
     * @return array containing raw grades (array indexes to match rowids)
     */
    abstract public function grade_matrix($matrix);

    /**
     * Returns the question's grade. By default it is the average of correct questions.
     * 
     * @param array $subqs
     * @return float 
     */
    public function grade_question($subqs)
    {
        return array_sum($subqs) / count($subqs);
    }

    /**
     * validate the teacher weight definitions
     *
     * @param array $data the raw form data
     *
     * @return array of errors - key on 'gradingmatrix' for the UI
     */
    public function validate_defining_form_matrix($data)
    {
        return array();
    }

    /**
     * whether this grading method expects percentages to be stored for it
     * or just 0/1
     *
     * @return boolean
     */
    public function store_percentages()
    {
        return false;
    }

    /**
     * return the cellname for the given row and column.
     * default is almost always fine.
     *
     * @param int $x row number
     * @param int $y col number
     *
     * @return string cellname
     */
    public function cellname($x, $y)
    {
        if ($this->multiple)
        {
            return 'cell' . $x . 'x' . $y;
        }
        return 'cell' . $x;
    }

    /**
     * render the cell to the user taking the quiz/whatever
     * this will check $this->responses (see {@link set_responses}) for existing values.
     * and also $this->question to look for name prevfix (see {@link set_question})
     *
     * @param int $x row number
     * @param int $y col number
     *
     * @return string some HTML for a form
     */
    abstract public function render_cell($x, $y, $readonly=false, $showcorrect=false);

    /**
     * helper function for render_cell to use.
     * mostly subclasses will call this and then just add on grading info if showing correct.
     */
    public function render_cell_base($x, $y, $readonly)
    {
        $cellname = $this->cellname($x, $y);
        $checked = false;
        if ($this->has_answer($this->responses, $x, $y))
        {
            $checked = true;
        }
        if (!empty($this->question))
        {
            $cellname = $this->question->name_prefix . $cellname;
        }
        if ($this->multiple)
        {
            return '<input type="checkbox" name="' . $cellname . '" ' . (($checked) ? 'checked="checked"' : '') . ($readonly) . '/>';
        }
        return '<input type="radio" name="' . $cellname . '" value="' . $y . '" ' . (($checked) ? 'checked="checked" selected="selected"' : '') . ($readonly) . ' />';
    }

    public function has_answer($matrix, $x, $y)
    {
        $cellname = $this->cellname($x, $y);
        if ($this->multiple)
        {
            return (array_key_exists($cellname, $matrix) && $matrix[$cellname] = 'on');
        }
        else
        {
            return (array_key_exists($cellname, $matrix) && $matrix[$cellname] == $y);
        }
    }

    /**
     * set the question member
     * this is only used in {@link render_cell}
     *
     * @param Object $question
     */
    public function set_question($question)
    {
        $this->question = $question;
    }

    /**
     * set the weights for this matrix
     * only used in {@link grade_matrix}
     *
     * @param array $weights [x][y] = 0.6 for example
     */
    public function set_weights($weights)
    {
        $this->weights = $weights;
    }

    /**
     * set the user responses
     *
     * @param array $responses [$cellname] = on
     */
    public function set_responses($responses)
    {
        $this->responses = $responses;
    }

    /**
     * whether this grade method requires manual intervention
     */
    public function is_manual_graded()
    {
        return false;
    }

}

/**
 * user can select any of the right answers (and none of the wrong ones) to get 100%, else none
 */
class matrix_qtype_grading_any extends matrix_qtype_grading_base
{

    public function create_defining_cell_element(MoodleQuickForm &$mform, $x, $y)
    {
        $cellname = self::cellname($x, $y);
        if ($this->multiple)
        {
            return $mform->createElement('checkbox', $cellname, 'label');
        }
        return $mform->createElement('radio', $cellname, '', '', $y);
    }

    public function validate_defining_form_matrix($data)
    {
        return array();
        // ignoring the code beneath - maybe we want to add a setting for this later
        // but in the meantime, we should be able to have empty rows.
        if (!array_key_exists('gradingmatrix', $data))
        {
            return array(); // nothing yet
        }
        $matrix = matrix_qtype::formdata_to_matrix($data);
        $errors = array();
        foreach ($matrix as $r => $row)
        {
            $rowfound = false;
            foreach ($row as $c => $cell)
            {
                if (!empty($cell))
                {
                    $rowfound = true;
                    break;
                }
            }
            if ($rowfound)
            {
                continue;
            }
            $errors['gradingmatrix'] = get_string('mustselectonevalueperrow', 'qtype_matrix');
        }
        return $errors;
    }

    public function grade_matrix($matrix)
    {
        $subqs = array();
        foreach ($this->weights as $r => $row)
        {
            $anyright = 0;
            $anywrong = 0;
            foreach ($row as $c => $cell)
            {
                if ($this->has_answer($matrix, $r, $c))
                {
                    if (empty($cell))
                    {
                        $anywrong = 1;
                    }
                    else
                    {
                        $anyright = 1;
                    }
                }
            }
            $subqs[$r] = $anyright && !$anywrong;
        }
        return $subqs;
    }

    public function render_cell($x, $y, $readonly=false, $showcorrect=false)
    {
        $base = $this->render_cell_base($x, $y, $readonly);
        if (empty($showcorrect))
        {
            return $base;
        }
        $feedback = '';
        $hasanswer = $this->has_answer($this->responses, $x, $y);
        $weight = (array_key_exists($x, $this->weights) && array_key_exists($y, $this->weights[$x])) ? $this->weights[$x][$y] : 0;

        if ($hasanswer && $weight)
        {
            $feedback = question_get_feedback_image(1);
        }
        else if ($hasanswer && !$weight)
        {
            $feedback = question_get_feedback_image(0);
        }
        else if (!$hasanswer && $weight)
        {
            $feedback = question_get_feedback_image(1);
        }

        return $base . $feedback;
    }

}

/**
 * user must select all of the right answers and none of the wrong ones to get 100%, else none.
 */
class matrix_qtype_grading_all extends matrix_qtype_grading_any
{

    public function grade_matrix($matrix)
    {
        $subqs = array();
        foreach ($this->weights as $r => $row)
        {
            $subqs[$r] = 1;
            foreach ($row as $c => $cell)
            {
                if (empty($cell))
                {  // it's a "wrong" answer
                    if ($this->has_answer($matrix, $r, $c))
                    {
                        // if they answered yes to it, they lose.
                        $subqs[$r] = 0;
                        break;
                    }
                }
                else
                { // else, it's a "right" answer
                    if (!$this->has_answer($matrix, $r, $c))
                    {
                        // so if they didn't check it, they lose
                        $subqs[$r] = 0;
                        break;
                    }
                }
            }
        }
        return $subqs;
    }

}

/**
 * All rows must be correct to get the point
 */
class matrix_qtype_grading_kprime extends matrix_qtype_grading_any
{

    public function grade_question($subqs)
    {
        foreach ($subqs as $mark)
        {
            if ($mark == 0)
            {
                return 0;
            }
        }
        return array_sum($subqs) / count($subqs);
    }

    public function grade_matrix($matrix)
    {
        $subqs = array();
        foreach ($this->weights as $r => $row)
        {
            $subqs[$r] = 1;
            foreach ($row as $c => $cell)
            {
                if (empty($cell))
                {  // it's a "wrong" answer
                    if ($this->has_answer($matrix, $r, $c))
                    {
                        // if they answered yes to it, they lose.
                        $subqs[$r] = 0;
                        break;
                    }
                }
                else
                { // else, it's a "right" answer
                    if (!$this->has_answer($matrix, $r, $c))
                    {
                        // so if they didn't check it, they lose
                        $subqs[$r] = 0;
                        break;
                    }
                }
            }
        }
        return $subqs;
    }

}

/**
 * no grade method -just override a few grade related things
 * basically do nothing
 */
class matrix_qtype_grading_none extends matrix_qtype_grading_any
{

    /**
     * whether this grade method requires manual intervention
     */
    public function is_manual_graded()
    {
        return true;
    }

    public function validate_defining_form_matrix($data)
    {
        return array();
    }

}

/**
 * weighted grading for fuzzier answering. penalises user for wrong answers
 */
class matrix_qtype_grading_weighted extends matrix_qtype_grading_base
{

    private function get_grade_options()
    {
        static $options = array();
        if (empty($options))
        {
            $tmp = get_grade_options();
            $options = $tmp->gradeoptionsfull;
        }
        return $options;
    }

    public function create_defining_cell_element(MoodleQuickForm &$mform, $x, $y)
    {
        $options = $this->get_grade_options();
        $cellname = $this->cellname($x, $y);
        return $mform->createElement('select', $cellname, 'label', $options);
    }

    public function validate_defining_form_matrix($data)
    {
        // each row must have the positive weight value adding up to 100
        $matrix = matrix_qtype::formdata_to_matrix($data);
        $errors = array();
        foreach ($matrix as $row)
        {
            $positivevalue = 0;
            foreach ($row as $cell)
            {
                if ($cell < 0)
                {
                    continue;
                }
                $positivevalue += $cell;
            }
            if (ceil($positivevalue) != 1)
            {
                $errors['gradingmatrix'] = get_string('mustaddupto100', 'qtype_matrix');
            }
        }
        return $errors;
    }

    public function store_percentages()
    {
        return true;
    }

    public function grade_matrix($matrix)
    {
        $subqs = array();
        foreach ($this->weights as $r => $row)
        {
            $positives = 0;
            $negatives = 0;
            foreach ($row as $c => $cell)
            {
                if (empty($cell))
                {
                    continue;
                }
                if ($this->has_answer($matrix, $r, $c))
                {
                    if ($cell >= 0)
                    {
                        $positives += $cell;
                    }
                    else
                    {
                        $negatives -= $cell;
                    }
                }
            }
            $tmp = $positives + ($positives * $negatives);
            if ($tmp < 0)
            {
                $tmp = 0;
            }
            $subqs[$r] = $tmp;
        }
        return $subqs;
    }

    public function render_cell($x, $y, $readonly=false, $showcorrect=false)
    {
        $base = $this->render_cell_base($x, $y, $readonly);
        if (empty($showcorrect))
        {
            return $base;
        }
        $class = '';
        $hasanswer = $this->has_answer($this->responses, $x, $y);
        $weight = (array_key_exists($x, $this->weights) && array_key_exists($y, $this->weights[$x])) ? $this->weights[$x][$y] : 0;

        if ($hasanswer && $weight > 0)
        {
            $class = question_get_feedback_class(1);
        }
        else if ($hasanswer && $weight < 0)
        {
            $class = question_get_feedback_class(0);
        }
        else if (!$hasanswer && $weight > 0)
        {
            $class = question_get_feedback_class(0);
        }
        $weighttext = ($weight) ? '<span class="smalltext ' . $class . '">(' . ($weight * 100) . '%)</span>' : '';
        return $base . $weighttext;
    }

}

// end of grading subclasses
// Register this question type with the system.
question_register_questiontype(new matrix_qtype());
?>
