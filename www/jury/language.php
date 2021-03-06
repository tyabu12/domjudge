<?php declare(strict_types=1);
/**
 * View a language
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');

$id = getRequestID(false);
$title = ucfirst((empty($_GET['cmd']) ? '' : specialchars($_GET['cmd']) . ' ') .
                 'language' . ($id ? ' '.specialchars(@$id) : ''));

if (isset($_POST['cmd'])) {
    $pcmd = $_POST['cmd'];
} elseif (isset($_GET['cmd'])) {
    $cmd = $_GET['cmd'];
}

if (!empty($pcmd)) {
    if (empty($id)) {
        error("Missing language id");
    }

    if (isset($pcmd['toggle_submit'])) {
        $DB->q('UPDATE language SET allow_submit = %i WHERE langid = %s',
               $_POST['val']['toggle_submit'], $id);
        auditlog('language', $id, 'set allow submit', $_POST['val']['toggle_submit']);
    }

    if (isset($pcmd['toggle_judge'])) {
        $DB->q('UPDATE language SET allow_judge = %i WHERE langid = %s',
               $_POST['val']['toggle_judge'], $id);
        auditlog('language', $id, 'set allow judge', $_POST['val']['toggle_judge']);
    }
}

require(LIBWWWDIR . '/header.php');

if (!empty($cmd)):

    requireAdmin();

    echo "<h2>$title</h2>\n\n";

    echo addForm('edit.php');

    echo "<table>\n";

    if ($cmd == 'edit') {
        $row = $DB->q('MAYBETUPLE SELECT * FROM language WHERE langid = %s', $id);
        if (!$row) {
            error("Missing or invalid language id");
        }

        echo "<tr><td>Language ID/ext:</td><td>" .
            addHidden('keydata[0][langid]', $row['langid']) .
            specialchars($row['langid']);
    } else {
        echo "<tr><td><label for=\"data_0__langid_\">Language ID/ext:</label></td><td>";
        echo addInput('data[0][langid]', null, 8, 8, 'required pattern="' . IDENTIFIER_CHARS . '+" title="alphanumerics only"');
    }
    echo "</td></tr>\n";

?>
<tr><td><label for="data_0__name_">Language name:</label></td>
<td><?php echo addInput('data[0][name]', @$row['name'], 20, 255, 'required')?></td></tr>

<tr><td>Require entry point:</td>
<td><?php echo addRadioButton('data[0][require_entry_point]', (!isset($row['require_entry_point']) || $row['require_entry_point']), 1)?> <label for="data_0__require_entry_point_1">yes</label>
<?php echo addRadioButton('data[0][require_entry_point]', (isset($row['require_entry_point']) && !$row['require_entry_point']), 0)?> <label for="data_0__require_entry_point_0">no</label></td></tr>

<tr><td><label for="data_0__entry_point_description_">Entry point description:</label></td>
<td><?php echo addInput('data[0][entry_point_description]', (isset($row['entry_point_description'])?$row['entry_point_description']:''), 20, 255)?></td></tr>

<tr><td>Allow submit:</td>
<td><?php echo addRadioButton('data[0][allow_submit]', (!isset($row['allow_submit']) || $row['allow_submit']), 1)?> <label for="data_0__allow_submit_1">yes</label>
<?php echo addRadioButton('data[0][allow_submit]', (isset($row['allow_submit']) && !$row['allow_submit']), 0)?> <label for="data_0__allow_submit_0">no</label></td></tr>

<tr><td>Allow judge:</td>
<td><?php echo addRadioButton('data[0][allow_judge]', (!isset($row['allow_judge']) || $row['allow_judge']), 1)?> <label for="data_0__allow_judge_1">yes</label>
<?php echo addRadioButton('data[0][allow_judge]', (isset($row['allow_judge']) && !$row['allow_judge']), 0)?> <label for="data_0__allow_judge_0">no</label></td></tr>

<tr><td><label for="data_0__time_factor_">Time factor:</label></td>
<td><?php echo addInputField('number', 'data[0][time_factor]', (isset($row['time_factor'])?$row['time_factor']:1), ' min="0" step="any" required')?> &times;</td></tr>
<tr><td><label for="data_0__compile_script_">Compile script:</label></td>
<td>
<?php
$execmap = $DB->q("KEYVALUETABLE SELECT execid,description FROM executable
                   WHERE type = 'compile' ORDER BY execid");
$execmap[''] = 'none';
echo addSelect('data[0][compile_script]', $execmap, @$row['compile_script'], true);
?>
</td></tr>
<tr><td><label for="data_0__extensions_">Extensions:</label></td>
<td><?php echo addInput('data[0][extensions]', @$row['extensions'], 20, 255, 'required')?> (as JSON encoded array, for example <kbd>["cpp","cc","c++"]</kbd>)</td></tr>
</table>

<?php
echo addHidden('cmd', $cmd) .
    addHidden('table', 'language') .
    addHidden('referrer', @$_GET['referrer']) .
    addSubmit('Save') .
    addSubmit('Cancel', 'cancel', null, true, 'formnovalidate') .
    addEndForm();

require(LIBWWWDIR . '/footer.php');
return;

endif;

$data = $DB->q('TUPLE SELECT * FROM language WHERE langid = %s', $id);

if (! $data) {
    error("Missing or invalid language id");
}

echo "<h1>Language ".specialchars($data['name'])."</h1>\n\n";

echo addForm($pagename . '?id=' . urlencode((string)$id)) . "<p>\n" .
    addHidden('id', $id) .
    addHidden('val[toggle_judge]', (string)!$data['allow_judge']) .
    addHidden('val[toggle_submit]', (string)!$data['allow_submit']).
    "</p>\n";

?>
<table>
<tr><td>ID/extension:</td><td><?php echo specialchars((string)$data['langid'])?></td></tr>
<tr><td>Name:        </td><td><?php echo specialchars($data['name'])?></td></tr>

<tr><td>Entry point: </td><td><?php echo printyn((bool)$data['require_entry_point']) .
    ($data['require_entry_point'] && !empty($data['entry_point_description']) ?
    ' (' . specialchars($data['entry_point_description']) . ')' : '') ?>
</td></tr>

<tr><td>Allow submit:</td><td><?php echo printyn((bool)$data['allow_submit']) . ' '.
    addSubmit(
        'toggle',
        'cmd[toggle_submit]',
        "return confirm('" . ($data['allow_submit'] ? 'Disallow' : 'Allow') .
        " submissions for this language?')"
    ); ?>
</td></tr>
<tr><td>Allow judge: </td><td><?php echo printyn((bool)$data['allow_judge']) . ' ' .
    addSubmit(
        'toggle',
        'cmd[toggle_judge]',
        "return confirm('" . ($data['allow_judge'] ? 'Disallow' : 'Allow') .
        " judging for this language?')"
    ); ?>
</td></tr>
<tr><td>Time factor:  </td><td><?php echo specialchars($data['time_factor'])?> &times;</td></tr>
<tr><td>Compile script:</td><td class="filename">
<?php
if (empty($data['compile_script'])) {
        echo '<span class="nodata">none specified</span>';
    } else {
        echo '<a href="executable.php?id=' . urlencode($data['compile_script']) . '">' .
        specialchars($data['compile_script']) . '</a>';
    }
?>
</td></tr>
<tr><td>Extensions:  </td><td><?php
echo specialchars(implode(', ', dj_json_decode($data['extensions']))) ?></td></tr>
</table>

<?php
echo addEndForm();

if (IS_ADMIN) {
    echo "<p>" .
        editLink('language', $data['langid']) . "\n" .
        delLink('language', 'langid', $data['langid']) . "</p>\n\n";
}

echo rejudgeForm('language', $data['langid']) . "<br />\n\n";

echo "<h2>Submissions in " . specialchars($data['name']) . "</h2>\n\n";

$restrictions = array( 'langid' => $id );
putSubmissions($cdatas, $restrictions);

require(LIBWWWDIR . '/footer.php');
