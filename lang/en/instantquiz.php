<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English strings for instantquiz
 *
 * @package    mod_instantquiz
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Instant quiz';
$string['modulenameplural'] = 'Instant quizzes';
$string['modulename_help'] = 'Create quizzes that don\'t require grading and don\'t have correct answers. Find out what personality types the students have.';
$string['instantquizname'] = 'Name of quiz or test';
$string['instantquizname_help'] = 'This name will appear in activities list on course view page and on the top of the quiz/test itself';
$string['instantquiz'] = 'instantquiz';
$string['noinstantquizzes'] = 'There are no instances of Instant quiz module';
$string['pluginadministration'] = 'instantquiz administration';
$string['pluginname'] = 'Instant quiz';
$string['subplugintype_instantquiztmpl'] = 'Template';
$string['subplugintype_instantquiztmpl_plural'] = 'Templates';
$string['manage'] = 'Edit instant quiz';
$string['summary'] = 'Summary';
$string['summarybycriterion'] = 'By criterion';
$string['summarybyfeedback'] = 'By feedback';
$string['summarybyquestion'] = 'By question';
$string['defaulttemplate'] = 'Default template';

$string['attemptslimit'] = 'Limit number of attempts';
$string['attemptslimit_help'] = 'Maximum number of times the user is allowed to answer the questions';
$string['attemptduration'] = 'Limit attempt time';
$string['attemptduration_help'] = 'Maximum time allowed to answer the questions';
$string['timeopen'] = 'Allow answers from';
$string['timeopen_help'] = 'The earliest date when participant is allowed to answer the questions';
$string['timeclose'] = 'Allow answers till';
$string['timeclose_help'] = 'Answer will not be accepted if it is submitted after this date even if participant started answering before this deadline.';
$string['displayresult'] = 'Display results';
$string['resultafteranswer'] = 'After an answer';
$string['resultafteranswer_help'] = 'User must submit an answer in order to view the results';
$string['resultmindate'] = 'After date';
$string['resultmindate_help'] = 'Results are only display after this date';
$string['resultminanswers'] = 'Minimum answers';
$string['resultminanswers_help'] = 'Display results only when at least this number of participants have answered';

$string['defcriterionname'] = 'Criterion {$a}';
$string['addcriterion'] = 'Add new evaluation criterion';
$string['criterion_name'] = 'Criterion';

$string['deffeedback'] = 'Feedback {$a}';
$string['addfeedback'] = 'Add new feedback';
$string['feedback_preview'] = 'Feedback';
$string['feedbackformulalegend'] = 'Expressions ${Criterion name} will be substituted with the number of points on this criterion. Also you can use numbers and operators <. <=, >, >=, ==, !=, AND, OR, &&, || and parenthesis';

$string['defquestion'] = 'Question {$a}';
$string['addquestion'] = 'Add new question';
$string['question_preview'] = 'Question';

$string['questionoption'] = 'Option';
$string['questionaddoption'] = 'Add option';

$string['attemptnotfound'] = 'Attempt not found';
$string['continueattempt'] = 'Continue unfinished attempt started at {$a->timestarted}';
$string['viewcurrentattempt'] = 'View current attempt started at {$a->timestarted} and finished at {$a->timefinished}';
$string['viewpreviousattempt'] = 'View previous attempt started at {$a->timestarted} and finished at {$a->timefinished}';
$string['startattempt'] = 'Start new attempt';

$string['instantquiz:view'] = 'View and attempt instantquiz ';
$string['instantquiz:addinstance'] = 'Add a new instantquiz';

$string['attempttimedout'] = 'The submission is overdue';
$string['attemptslimitreached'] = 'You are only allowed to submit {$a} times';
$string['cannotviewsummary'] = 'You are not allowed to view summary';

$string['optionslimit'] = 'User must select at least {$a->minoptions} and at most {$a->maxoptions} options';
$string['errorminoptions'] = 'Please select at least {$a} option(s)';
$string['errormaxoptions'] = 'Please select no more than {$a} option(s)';
$string['erroroption'] = 'Please select an option';

$string['comment'] = 'Comment';
$string['commentnone'] = 'No comment';
$string['commentpublic'] = 'Public comment';
$string['commentanonymous'] = 'Anonymous comment';
$string['commentprivate'] = 'Private comment';
$string['commentoptional'] = 'optional';
$string['commentrequired'] = 'required';
$string['errorcommentrequired'] = 'Comment is required';
