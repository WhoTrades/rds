<?php
/** @var $new ReleaseRequest */
/** @var $current ReleaseRequest */

Yii::app()->jsdifflib->register();

?>
<input type="checkbox" id="inline" style="float: left" onchange="diffUsingJS();" /><label for="inline">В одну колонку</label>
<br />
<div id="baseText" style="display: none"><?=htmlspecialchars($current->rr_cron_config)?></div>
<div id="newText" style="display: none"><?=htmlspecialchars($new->rr_cron_config)?></div>
<div id="diffoutput"></div>

<script>
    function diffUsingJS() {
        // get the baseText and newText values from the two textboxes, and split them into lines
        var base = difflib.stringAsLines(document.getElementById("baseText").innerHTML);
        var newtxt = difflib.stringAsLines(document.getElementById("newText").innerHTML);

        // create a SequenceMatcher instance that diffs the two sets of lines
        var sm = new difflib.SequenceMatcher(base, newtxt);

        // get the opcodes from the SequenceMatcher instance
        // opcodes is a list of 3-tuples describing what changes should be made to the base text
        // in order to yield the new text
        var opcodes = sm.get_opcodes();
        var diffoutputdiv = $("#diffoutput");
        diffoutputdiv.html('');
        // build the diff view and add it to the current DOM
        diffoutputdiv.html(diffview.buildView({
            baseTextLines: base,
            newTextLines: newtxt,
            opcodes: opcodes,
            // set the display titles for each resource
            baseTextName: "<?=$current->rr_build_version?> - CURRENT VERSION",
            newTextName: "<?=$new->rr_build_version?> - NEW VERSION",
            contextSize: null,
            viewType: $("#inline")[0].checked ? 1 : 0
        }));

        setTimeout(function(){
            $('body').scrollTo($('.insert, .replace, .empty, .delete').first(), 500);
        }, 0);
    }
    diffUsingJS();
</script>
