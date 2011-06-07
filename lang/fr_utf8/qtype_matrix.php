<?php // $Id: qtype_matrix.php,v 1.1.2.5 2009-06-29 14:52:41 mjollnir_ Exp $
/**
 * The language strings for the matrix question type.
 *
 * @copyright &copy; 2009 Penny Leach
 * @author penny@liip.ch
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package matrix
 */

$string['True'] = 'Vraie';
$string['False'] = 'Faux';

// contract strings
$string['addingmatrix'] = 'Ajout Matrice';
$string['editingmatrix'] = 'Modification Matrice';
$string['matrix'] = 'Matrice';
$string['matrixsummary'] = 'Matrice';

// form strings
$string['multipleallowed'] = 'Est-ce que plusieurs réponses sont authoriées?';
$string['grademethod'] = 'Méthode d\'évaluation';
$string['renderer'] = 'Rendu';
$string['rowsheader'] = 'Lignes';
$string['rowsheaderdesc'] = '<p>Le titre est affiché en tête de ligne. La description est utilisée dans un balon d\'aide.<br/></p>
<p>Les étudiants peuvent sélectionner soit une soit plusieurs réponses par ligne en fonction de la configuration. Chaque ligne reçoit une note en fonction de la méthode d\'évaluation choisie.</p>
<p>La note finale est la moyenne des notes de chacune des lignes sauf pour le type kprime ou il faut avoir toutes les réponses correctes pour obtenir les points.</p>';
$string['rowshort'] = 'Titre';
$string['rowlong'] = 'Description';
$string['rowfeedback'] = 'Commentaires';
$string['colsheader'] = 'Colonnes';
$string['colsheaderdesc'] =  '<p>Le titre est affiché en-tête des colonnes. La description est affichée dans un balon d\'aide.<br/></p>
<p>Les étudiants peuvent sélectionner soit une soit plusieurs réponses par ligne en fonction de la configuration. Chaque ligne reçoit une note en fonction de la méthode d\'évaluation choisie.</p>
<p>La note finale est la moyenne des notes de chacune des lignes sauf pour le type Kprime ou il faut avoir toutes les réponses correctes pour obtenir les points.</p>';
$string['colshort'] = 'Titre';
$string['collong'] = 'Description';
$string['addmorerows'] = 'Ajouter {$a} ligne(s) de plus';
$string['addmorecols'] = 'Ajouter {$a} colonne(s) de plus';
$string['mustdefine1by1'] = 'Vous devez définir au minimum une matrice de 1 x 1 avec des titres pour les colonnes et les lignes.';
$string['mustselectonevalueperrow'] = 'Vous devez sélectionner au minimum une réponse par ligne';
$string['mustaddupto100'] = 'La somme de toutes les valeurs non-négative doit être égal à 100%%';
$string['selectcorrectanswers'] = 'Définition des réponses correctes';
$string['updatematrix'] = 'Mettre la matrice à jour pour refléter les nouvelles options choisies';
$string['matrixheader'] = 'Matrice';
$string['weightednomultiple'] = 'Pour choisir une méthode d\'évaluation pondérée il faut activer l\'option "réponses multiples"';

// grading options
$string['grading.kprime'] = 'Kprime';
$string['grading.all'] = 'Toutes les réponses correctes et aucune réponse fausse';
$string['grading.any'] = 'Au moins une réponse correcte et aucune réponse fausse';
$string['grading.none'] = 'Pas d\'évaluation';
$string['grading.weighted'] = 'Pondérée';

$string['grademethod_help'] = '<p>Il y a plusieurs méthodes d\'évaluation pour le type matrice.</p>
<p>Ces méthodes concerne généralement les <b>lignes</b> sauf pour le type Kprime. La note totale est la moyenne des notes pour chacune des lignes sauf pour le type Kprime ou il faut avoir toutes les réponses correctes pour obtenir les points.</p>
<table>
<tr><td><b>Kprime</b></td><td>L\'étudiant doir choisir toutes les réponses correctes parmis celles proposées et aucune réponse fausse pour obtenir 100%. Ceci inclue les lignes. Autrement l\'étudiant obtient 0%. </td></tr>
<tr><td><b>Au moins une réponse correcte et aucune réponse fausse</b></td><td>L\'étudiant doir choisir au minimum une réponse correcte parmis celles proposées et aucune réponse fausse pour obtenir 100%. Autrement l\'étudiant obtient 0%.</td></tr>
<tr><td><b>Toutes les réponses correctes et aucune réponse fausse</b></td><td>L\'étudiant doir choisir toutes les réponses correctes parmis celles proposées et aucune réponse fausse pour obtenir 100%. Autrement l\'étudiant obtient 0%.</td></tr>
<tr><td><b>Pas d\'évaluation</b></td><td>Il n\'y a pas d\'évaluation.</td></tr>
<tr><td><b>Pondérée</b></td><td>Chaque réponse reçoit un poid. La somme des réponses positives pour chaque ligne doit être de 100%.</td></tr></table>';
$string['matrix_help'] = '<p>Ce type de question permet aux enseignants de définir les lignes et les colonnes qui composent une matrice.</p>
<p>Les étudiants peuvent choisir soit une réponse par ligne soit plusieurs, selon la façon dont a été définie la question. Chaque ligne est évaluée selon la méthode d\'évaluation choisie.</p>
<p>La note finale pour la question est la moyenne des notes de chacune des lignes.</p>';
?>
