<?php

/**
 * The editing form code for this question type.
 *
 * @copyright &copy; 2009 Penny Leach
 * @author penny@liip.ch
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package matrix
 */
require_once($CFG->dirroot . '/question/type/edit_question_form.php');
require_once(dirname(__FILE__) . '/form/matrix_form_renderer.class.php');

/**
 * matrix editing form definition.
 *
 * See http://docs.moodle.org/en/Development:lib/formslib.php for information
 * about the Moodle forms library, which is based on the HTML Quickform PEAR library.
 */
class question_edit_matrix_form extends question_edit_form
{
    const DEFAULT_REPEAT_ELEMENTS = 1; //i.e. how many elements are added each time somebody click the add row/add column button.
    const DEFAULT_ROWS = 4; //i.e. how many rows 
    const DEFAULT_COLS = 2; //i.e. how many cols 

    /**
     * PATCHED
     *
     * Method to add a repeating group of elements to a form.
     *
     * @param array $elementobjs Array of elements or groups of elements that are to be repeated
     * @param integer $repeats no of times to repeat elements initially
     * @param array $options Array of options to apply to elements. Array keys are element names.
     *                      This is an array of arrays. The second sets of keys are the option types
     *                      for the elements :
     *                          'default' - default value is value
     *                          'type' - PARAM_* constant is value
     *                          'helpbutton' - helpbutton params array is value
     *                          'disabledif' - last three moodleform::disabledIf()
     *                                           params are value as an array
     * @param string $repeathiddenname name for hidden element storing no of repeats in this form
     * @param string $addfieldsname name for button to add more fields
     * @param int $addfieldsno how many fields to add at a time
     * @param string $addstring name of button, {no} is replaced by no of blanks that will be added.
     * @param boolean $addbuttoninside if true, don't call closeHeaderBefore($addfieldsname). Default false.
     * @return int no of repeats of element in this page
     */
    function repeat_elements($elementobjs, $repeats, $options, $repeathiddenname, $addfieldsname, $addfieldsno=5, $addstring=null, $addbuttoninside=false)
    {
        if ($addstring === null)
        {
            $addstring = get_string('addfields', 'form', $addfieldsno);
        }
        else
        {
            $addstring = str_ireplace('{no}', $addfieldsno, $addstring);
        }
        $repeats = optional_param($repeathiddenname, $repeats, PARAM_INT);
        $addfields = optional_param($addfieldsname, '', PARAM_TEXT);
        if (!empty($addfields))
        {
            $repeats += $addfieldsno;
        }
        $mform = & $this->_form;
        $mform->registerNoSubmitButton($addfieldsname);
        $mform->addElement('hidden', $repeathiddenname, $repeats);
        $mform->setType($repeathiddenname, PARAM_INT);
        //value not to be overridden by submitted value
        $mform->setConstants(array($repeathiddenname => $repeats));
        $namecloned = array();
        for ($i = 0; $i < $repeats; $i++)
        {
            foreach ($elementobjs as $elementobj)
            {
                $elementclone = fullclone($elementobj);
                $name = $elementclone->getName();
                $namecloned[] = $name;
                if (!empty($name))
                {
                    $elementclone->setName($name . "[$i]");
                }
                if (is_a($elementclone, 'HTML_QuickForm_header'))
                {
                    $value = $elementclone->_text;
                    $elementclone->setValue(str_replace('{no}', ($i + 1), $value));
                }
                else
                {
                    $value = $elementclone->getLabel();
                    $elementclone->setLabel(str_replace('{no}', ($i + 1), $value));
                }

                $mform->addElement($elementclone);
            }
        }
        for ($i = 0; $i < $repeats; $i++)
        {
            foreach ($options as $elementname => $elementoptions)
            {
                $pos = strpos($elementname, '[');
                if ($pos !== FALSE)
                {
                    $realelementname = substr($elementname, 0, $pos + 1) . "[$i]";
                    $realelementname .= substr($elementname, $pos + 1);
                }
                else
                {
                    $realelementname = $elementname . "[$i]";
                }
                foreach ($elementoptions as $option => $params)
                {

                    switch ($option)
                    {
                        case 'default' :
                            $mform->setDefault($realelementname, $params);
                            break;
                        case 'helpbutton' :
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'addHelpButton'), $params);
                            break;
                        case 'disabledif' :
                            foreach ($namecloned as $num => $name)
                            {
                                if ($params[0] == $name)
                                {
                                    $params[0] = $params[0] . "[$i]";
                                    break;
                                }
                            }
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'disabledIf'), $params);
                            break;
                        case 'rule' :
                            if (is_string($params))
                            {
                                $params = array(null, $params, null, 'client');
                            }
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'addRule'), $params);
                            break;
                        case 'template':
                            $renderer = & $mform->defaultRenderer();
                            $renderer->setElementTemplate($params, $realelementname);
                            break;
                    }
                }
            }
        }
        $mform->addElement('submit', $addfieldsname, $addstring);

        if (!$addbuttoninside)
        {
            $mform->closeHeaderBefore($addfieldsname);
        }

        return $repeats;
    }

    function definition_inner(&$mform)
    {
        $renderer = & $mform->defaultRenderer();

        // multiple allowed
        $mform->addElement('selectyesno', 'multiple', get_string('multipleallowed', 'qtype_matrix'));
        $mform->setDefault('multiple', true);

        // grading method
        $mform->addElement('select', 'grademethod', get_string('grademethod', 'qtype_matrix'), matrix_qtype::matrix_grading_options(),
                /* array('onblur' => 'alert("change");console.log(this.parent);this.parent.elements["defaultgrade"].value = 0;') */ ''
        );
        //$mform->addHelpButton('grademethod', 'grademethod', 'qtype_matrix');
        $mform->setHelpButton('grademethod', array('grademethod', get_string('grademethod', 'qtype_matrix'), 'qtype_matrix'));

        // renderer to use
        $rends = matrix_qtype::matrix_renderer_options();
        if (count($rends) > 1)
        {
            $mform->addElement('select', 'renderer', get_string('renderer', 'qtype_matrix'), $rends);
        }
        else
        {
            $mform->addElement('hidden', 'renderer', array_pop(array_keys($rends)));
        }

        // rows
        $repeatrows = array();
        $repeatrows[] = $mform->createElement('static', 'betweenrowshr', '', '<hr />');
        $repeatrows[] = $mform->createElement('text', 'rowshort', get_string('rowshort', 'qtype_matrix'));
        $repeatrows[] = $mform->createElement('htmleditor', 'rowlong', get_string('rowlong', 'qtype_matrix'));
        $repeatrows[] = $mform->createElement('htmleditor', 'rowfeedback', get_string('rowfeedback', 'qtype_matrix'));

        if (isset($this->question->id))
        {
            $repeatno = matrix_qtype::count_db_rows_or_cols($this->question->id, 'rows');
            $repeatrows[] = $mform->createElement('hidden', 'rowid');
        }
        else
        {
            $repeatno = self::DEFAULT_ROWS;
        }

        $mform->addElement('header', 'beforerowsheader', get_string('rowsheader', 'qtype_matrix'));
        $mform->addElement('static', 'rowsheader', '', get_string('rowsheaderdesc', 'qtype_matrix'));
        $renderer->setElementTemplate('<td colspan="2">{element}</td>', 'rowsheader');

        $this->repeat_elements($repeatrows, $repeatno, array('betweenrowshr' => array('template' => '<td colspan="2">{element}</td>')), 'option_repeat_rows', 'option_add_rows', self::DEFAULT_REPEAT_ELEMENTS, get_string('addmorerows', 'qtype_matrix', '{no}'));

        // cols
        $repeatcols = array();
        $repeatcols[] = $mform->createElement('static', 'betweencolshr', '', '<hr />');
        $repeatcols[] = $mform->createElement('text', 'colshort', get_string('colshort', 'qtype_matrix'));
        $repeatcols[] = $mform->createElement('htmleditor', 'collong', get_string('collong', 'qtype_matrix'));
        $mform->setDefault('colshort[0]', get_string('True', 'qtype_matrix'));
        $mform->setDefault('colshort[1]', get_string('False', 'qtype_matrix'));

        if (isset($this->question->id))
        {
            $repeatno = matrix_qtype::count_db_rows_or_cols($this->question->id, 'cols');
            $repeatcols[] = $mform->createElement('hidden', 'colid');
        }
        else
        {
            $repeatno = self::DEFAULT_COLS;
        }

        $mform->addElement('header', 'beforecolsheader', get_string('colsheader', 'qtype_matrix'));
        $mform->addElement('static', 'colsheader', '', get_string('colsheaderdesc', 'qtype_matrix'));
        $renderer->setElementTemplate('<td colspan="2">{element}</td>', 'colsheader');
        $this->repeat_elements($repeatcols, $repeatno, array('betweencolshr' => array('template' => '<td colspan="2">{element}</td>')), 'option_repeat_cols', 'option_add_cols', self::DEFAULT_REPEAT_ELEMENTS, get_string('addmorecols', 'qtype_matrix', '{no}'));

        // weights
        $mform->addElement('submit', 'addweights', get_string('selectcorrectanswers', 'qtype_matrix'));
        $mform->registerNoSubmitButton('addweights');
        $mform->disabledIf('addweights', 'grademethod', 'eq', 'none');
        $mform->disabledIf('defaultgrade', 'grademethod', 'eq', 'none');
    }

    function definition_after_data()
    {
        if ($submitted = optional_param('addweights', false, PARAM_CLEAN) || $already = optional_param('weightsadded', false, PARAM_CLEAN))
        {
            if (empty($already) && !$this->validate_defined_fields(true))
            {
                return;
            }
            $this->add_grading_matrix();
            return;
        }
        if (isset($this->question->id))
        {
            $this->add_grading_matrix();
        }
    }

    function add_grading_matrix()
    {
        static $added = false;
        if ($added)
        {
            return;
        }
        $added = true;
        $mform = & $this->_form;
        $data = $mform->exportValues(null, true);
        $cols = matrix_qtype::count_form_rows_or_cols($data, false, true);
        $rows = matrix_qtype::count_form_rows_or_cols($data, true, true);
        $renderer = & $mform->defaultRenderer();
        $colcount = count($cols);
        $rowcount = count($rows);
        $multiple = $data['multiple'];
        if (array_key_exists('grademethod', $data))
        {
            $grademethod = $data['grademethod'];
        }
        else
        {
            $grademethod = matrix_qtype::single_default_grademethod();
        }
        if ($grademethod == QTYPE_MATRIX_GRADING_NONE)
        {
            $added = true;
            return;
        }
        $gradeclass = matrix_qtype::grade_class($grademethod, $multiple);
        $matrix = array();
        $matrix[] = $mform->createElement('static', 'firstcell', '', '');
        $renderer->setElementTemplate('<td>{element}</td>', 'gradingmatrix[firstcell]');

        foreach ($cols as $i => $col)
        {
            $matrix[] = $mform->createElement('static', 'colheader' . $i, 'colheader', matrix_qtype::render_matrix_header($col, true));

            $renderer->setElementTemplate('<td>{element}</td>', 'gradingmatrix[colheader' . $i . ']');
        }
        $matrix[] = $mform->createElement('static', 'placeholderrow0', 'row0');
        $renderer->setElementTemplate('<td>{element}</td></tr><tr>', 'gradingmatrix[placeholderrow0]');
        $thisrow = 0;
        foreach ($rows as $j => $row)
        {
            $matrix[] = $mform->createElement('static', 'rowheader' . $j, 'rowheader', matrix_qtype::render_matrix_header($row, true));
            $renderer->setElementTemplate('<td>{element}</td>', 'gradingmatrix[rowheader' . $j . ']');
            $thiscol = 0;
            foreach ($cols as $i => $col)
            {
                if (!$el = $gradeclass->create_defining_cell_element($mform, $j, $i, $multiple))
                {
                    $el = $mform->createElement('static', $gradeclass->cellname($multiple, $j, $i), '');
                }
                $cellname = $el->getName();
                $matrix[] = $el;
                $renderer->setElementTemplate('<td>{element}</td>', 'gradingmatrix[' . $cellname . ']');
                if ($thiscol == ($colcount - 1) && $thisrow != ($rowcount - 1))
                {
                    $matrix[] = $mform->createElement('static', 'placeholderrow' . ($i + 1), 'row' . ($i + 1));
                    $renderer->setElementTemplate('<td>{element}</td></tr><tr>', 'gradingmatrix[placeholderrow' . ($i + 1) . ']');
                }
                $thiscol++;
            }
            $thisrow++;
        }
        $matrixel = $mform->createElement('group', 'gradingmatrix', '', $matrix, null, true);
        $matrixheader = $mform->createElement('header', 'matrixheader', get_string('matrixheader', 'qtype_matrix'));
        if ($mform->elementExists('buttonar'))
        {
            $mform->insertElementBefore($matrixheader, 'buttonar');
            $mform->insertElementBefore($matrixel, 'buttonar');
        }
        else
        {
            $mform->addElement($matrixheader);
            $mform->addElement($matrixel);
        }
        $renderer->setGroupTemplate('<table class="qtypematrixformmatrix"><tr>{content}</tr></table>', 'gradingmatrix');
        // this is not actually ever used, but necessary to force the above temmplate to be used
        $renderer->setGroupElementTemplate('<td>{element}</td>', 'gradingmatrix');

        $mform->addElement('hidden', 'weightsadded', 1);
        $mform->addElement('hidden', 'rowcount', $rowcount);
        $mform->addElement('hidden', 'colcount', $colcount);
        $mform->getElement('addweights')->setValue(get_string('updatematrix', 'qtype_matrix'));
        $mform->disabledIf('gradingmatrix', 'grademethod', 'eq', 'none');
    }

    function set_data($question)
    {
        if (!empty($question->id) && $matrix = matrix_qtype::load_all_data($question->id))
        {
            $question->multiple = $matrix->multiple;
            $question->grademethod = $matrix->grademethod;
            $question->renderer = $matrix->renderer;
            $question->rowshort = $matrix->rowshort;
            $question->rowlong = $matrix->rowlong;
            $question->rowfeedback = $matrix->rowfeedback;
            $question->colshort = $matrix->colshort;
            $question->collong = $matrix->collong;
            $question->rows = $matrix->rows;
            $question->cols = $matrix->cols;
            $question->rowid = array_keys($matrix->rows);
            $question->colid = array_keys($matrix->cols);
            $question->gradingmatrix = $matrix->gradingmatrix;
        }
        parent::set_data($question);
    }

    function validation($data)
    {
        $errors = parent::validation($data, null);

        $colcount = matrix_qtype::count_form_rows_or_cols($data, false);
        $rowcount = matrix_qtype::count_form_rows_or_cols($data);

        if ($rowcount == 0)
        {
            $errors['rowshort[0]'] = get_string('mustdefine1by1', 'qtype_matrix');
        }
        if ($colcount == 0)
        {
            $errors['colshort[0]'] = get_string('mustdefine1by1', 'qtype_matrix');
        }
        if (array_key_exists('grademethod', $data))
        {
            $grademethod = $data['grademethod'];
        }
        else
        {
            $grademethod = matrix_qtype::single_default_grademethod();
        }
        if ($grademethod == QTYPE_MATRIX_GRADING_WEIGHTED && empty($data['multiple']))
        {
            $errors['multiple'] = get_string('weightednomultiple', 'qtype_matrix');
        }
        $gradeclass = matrix_qtype::grade_class($grademethod, $data['multiple']);
        $matrixerrors = $gradeclass->validate_defining_form_matrix($data);

        $errors = array_merge($errors, $matrixerrors);
        return $errors ? $errors : true;
    }

    function qtype()
    {
        return 'matrix';
    }

}

?>
