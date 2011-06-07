<?php // $Id: qtype_matrix.php,v 1.1.2.5 2009-06-29 14:52:41 mjollnir_ Exp $
/**
 * The language strings for the matrix question type.
 *
 * @copyright &copy; 2009 Penny Leach
 * @author penny@liip.ch
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package matrix
 */
$string['True'] = 'True';
$string['False'] = 'False';

// contract strings
$string['addingmatrix'] = 'Adding Matrix';
$string['editingmatrix'] = 'Editing Matrix';
$string['matrix'] = 'Matrix';
$string['matrixsummary'] = 'Matrix';

// form strings
$string['multipleallowed'] = 'Multiple responses allowed?';
$string['grademethod'] = 'Grading method';
$string['renderer'] = 'Renderer';
$string['rowsheader'] = 'Matrix rows';
$string['rowsheaderdesc'] = '<p>Shorttext will be used when it\'s present, with the longer text as a tooltip.<br />Be mindful of how this will be displayed</p>
<p>Students can select multiple or single columns per row, depending on how the question has been configured, and each row recieves a grade, defined by one of the grading methods.</p>
<p>The final grade for the question is an average of their grades for each of the rows with the exeption of the Kprime type where all answers have to be correct.</p>';
$string['rowshort'] = 'Row text';
$string['rowlong'] = 'Row description';
$string['rowfeedback'] = 'Row feedback';
$string['colsheader'] = 'Matrix columns';
$string['colsheaderdesc'] = '<p>Shorttext will be used when it\'s present, with the longer text as a tooltip.<br />Be mindful of how this will be displayed.</p>
<p>Students can select multiple or single columns per row, depending on how the question has been configured, and each row recieves a grade, defined by one of the grading methods.</p>
<p>The final grade for the question is an average of their grades for each of the rows with the exeption of the Kprime type where all answers have to be correct.</p>';
$string['colshort'] = 'Column text';
$string['collong'] = 'Column description';
$string['addmorerows'] = 'Add {$a} more rows';
$string['addmorecols'] = 'Add {$a} more columns';
$string['mustdefine1by1'] = 'You must define at least a 1 x 1 matrix; with either short or long answer defined for each row and column';
$string['mustselectonevalueperrow'] = 'You must select at least one value per row';
$string['mustaddupto100'] = 'The sum of all non negative weights in each row must be 100%%';
$string['selectcorrectanswers'] = 'Start defining correct cells';
$string['updatematrix'] = 'Update matrix to reflect new options';
$string['matrixheader'] = 'Grading matrix';
$string['weightednomultiple'] = 'It doesn\'t make sense to choose weighted grading with multiple answers not allowed';

// grading options
$string['grading.kprime'] = 'Kprime';
$string['grading.all'] = 'All correct, and none wrong';
$string['grading.any'] = 'Any correct, and none wrong';
$string['grading.none'] = 'No grading';
$string['grading.weighted'] = 'Weighted grading';

$string['grademethod_help'] = '<p align="center"><b>Grade Method</b></p>
<p>There are a few options for the grading method for matrix question types:</p>
<p>Each of these, but Kprime, relate to how each <b>row</b> is graded, with the total grade for the question being the average of all the rows. Kprime requires that all rows must be correct to get the point. If it is not the case the studend receives 0.</p>
<table>
  <tr><td><b>Kprime</b></td><td>The student must choose all correct answers, and none of the wrong ones, to get 100%, else 0%. Including rows. If one row is wrong then the mark for the question is 0.</td></tr>
  <tr><td><b>Any correct, and none wrong</b></td><td>The student must choose at least one of the correct answers, and none of the wrong ones, to get 100%, else 0%</td></tr>
  <tr><td><b>All correct, and none wrong</b></td><td>The student must choose exactly all of the correct answers, and none of the wrong ones, to get 100%, else 0%</td></tr>
  <tr><td><b>No grading</b></td><td>There is no grading used for this question (use this for Likert Scales for example)</td></tr>
  <tr><td><b>Weighted grading</b></td><td>Each cell recieves a weighting, and the positive values for each row must add up to 100%</td></tr>
</table>';
$string['matrix_help'] = '<p align="center"><b>Matrix</b></p><p>This question type allows the teacher to define the rows and columns to make up a matrix.</p><p>Students can select multiple or single columns per row, depending on how the question has been configured, and each row recieves a grade, defined by one of the grading methods.</p><p>The final grade for the question is an average of their grades for each of the rows</p>';
?>
