<?php declare(strict_types=1);
/**
 * Change the valid status of a given submission.
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');

if (! IS_ADMIN) {
    error("Admin privileges are required for this operation.");
}

$id  = @$_POST['id'];
$val = @$_POST['val'];
if (empty($id)) {
    error("No submission ID passed to mark as (in)valid.");
}

$cnt = $DB->q('RETURNAFFECTED UPDATE submission s
               SET s.valid = %i WHERE s.submitid = %i AND s.valid != %i',
              $val, $id, $val);

if ($cnt == 0) {
    error("Submission s$id not found or not changed.");
} elseif ($cnt > 1) {
    error("More than one submission found!");
}

$sdata = $DB->q('TUPLE SELECT submitid, cid, teamid, probid
                 FROM submission
                 WHERE submitid = %i', $id);

// KLUDGE: We can't log an "undelete", so we re-"create".
// FIXME: We should also delete/recreate any dependent judging(runs).
eventlog('submission', $id, ($val ? 'create' : 'delete'), $sdata['cid']);
auditlog('submission', $id, 'marked ' . ($val?'valid':'invalid'));

calcScoreRow((int)$sdata['cid'], (int)$sdata['teamid'], (int)$sdata['probid']);

/* redirect back. */
header('Location: submissions/' . urlencode((string)$sdata['submitid']));
