<?php

// Required: $result

/* @var $result Zoetrope_Result */

/* @var $res_multi   float */

?>
<html>
<head>
    <title>
        Selenium test at <?=$result->getBaseUrl() ?> from <?=$result->getTestsDirectory() ?> started
        at <?=date('D M j H:i:s Y', $result->getTimeStart()) ?>
    </title>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <script type="text/javascript" src="fancybox/jquery.fancybox-1.3.4.pack.js"></script>
    <link rel="stylesheet" type="text/css" href="fancybox/jquery.fancybox-1.3.4.css" media="screen"/>
    <script>
        // Allow jQuery state to be stored using hash
        $(function () {
            $(window).bind("hashchange", function () {
                //         Get current hash     OR default to no hash
                var hash = window.location.hash || "";
                // Operations to be done (Hide all with class overview and show hash)
                $(".overview").hide();
                $(hash).toggle();
            });
            $(window).trigger("hashchange");
        });
        $(document).ready(function () {
            $("a.single_image").fancybox();
        });
    </script>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0
        }
        a.single_image:link    {color:lightgrey; text-decoration:none;}
        a.single_image:visited {color:lightgrey; text-decoration:none;}
        a.single_image:hover   {color:black;     text-decoration:none;}
        a.single_image:active  {color:black;     text-decoration:none;}
    </style>
</head>
<body>
<script>
        $(document).ready(function() {
<?php
$videos = '';
$teststack = '';
foreach($result->getTests() as $testcase) {
?>
    $('#videoload-<?= $testcase->getClassName() ?>').click(function () {
        if ($('#video-<?= $testcase->getClassName() ?>').children().size() == 0) {
            $('<video controls="true" width="<?= round( $testcase->getVideoWidth() * $res_multi ) ?>" height="<?= round( $testcase->getVideoHeight() * $res_multi ) ?>"><source type="video/ogg" src="<?= $result->getExternalUrl() . $testcase->getClassName() ?>.ogg" /></video>')
            .appendTo('#video-<?= $testcase->getClassName() ?>');
        }
    });
<?php

    // Add assertions, failures and errors to stacktrace
    $stacktrace = 'Assertions: ' . $testcase->getNumberOfAssertions() . ' Failures: ' . $testcase->getNumberOfFailure() . ' Errors: ' . $testcase->getNumberOfErrors();

    foreach($testcase->getErrors() as $error) {
        $stackstring = htmlify( $error->getContent() );
        $stacktrace = $stacktrace . '<br/>####################################################<br/>## <b>ERROR:</b> ' .
            $error->getType() . '<br/>####################################################<br/>' . auto_link_text( $stackstring );
    }
    foreach($testcase->getFailures() as $failure) {
        $stackstring = htmlify( $failure->getContent() );
        $stacktrace = $stacktrace . '<br/>####################################################<br/>## <b>FAILURE:</b> ' .
            $failure->getType() . '<br/>####################################################<br/>' . auto_link_text( $stackstring );
    }


    // Generate line numbers to use next to source code for easy to find failures and errors
    $linenumbers = '';
    for ( $i = 1; $i <= $testcase->getSourcecodeNumberOfLines(); $i++ ) {
        // Color red if there is an error on this line
        $screenshot = $testcase->getScreenshotOnLine($i);
        if ( $testcase->lineHasError($i)) {
            $linenumbers .= '<font style="BACKGROUND-COLOR: red" color="white">';
            if ($screenshot) {
               $linenumbers .= '<a class="single_image" rel="group-'.$testcase->getClassName().'" href="'.$result->getTestsDirectoryRelative().$screenshot.'">';
            }
            $linenumbers .= $i;
            if ($screenshot) {
               $linenumbers .= '</a>';
            }
            $linenumbers .= '</font><br/>';
        }
        // Color orange if there is a failure on this line
        elseif ( $testcase->lineHasFailure($i)) {
            $linenumbers .= '<font style="BACKGROUND-COLOR: orange" color="white">';
            if ($screenshot) {
               $linenumbers .= '<a class="single_image" rel="group-'.$testcase->getClassName().'" href="'.$result->getTestsDirectoryRelative().$screenshot.'">';
            }
            $linenumbers .= $i;
            if ($screenshot) {
               $linenumbers .= '</a>';
            }
            $linenumbers .= '</font><br/>';
        }
        // No colors, no screenshot, just a line number :-)
        else {
            $linenumbers .= $i . '<br/>';
        }
    }


    // Visual traffic light color code in resultlist OK / SKIPPED / FAIL / ERROR / UNSTABLE
    if ( $testcase->hasErrorTests() ) {
        $hotornot = '<font color="red">ERROR</font>';
    }
    else if ( $testcase->hasFailedTests() ) {
        $hotornot = '<font color="orange">FAIL</font>';
    }
    else if ( $testcase->isUnstableTest() ) {
        $hotornot = '<font color="pink">UNSTABLE</font>';
    }
    else if ( $testcase->hasSkippedTests() ) {
        $hotornot = '<font color="lightgreen">SKIPPED</font>';
    }
    else {
        $hotornot = '<font color="green">OK</font>';
    }

    // Vars if there is no description available
    if ( $testcase->getDescriptionFilename() == '#' ) $description_text = 'No description';
    else $description_text = '<a href="' . $result->getTestsDirectoryRelative() . $testcase->getDescriptionFilename() . '">' . $testcase->getDescriptionFilename() . '</a>';

    $testdescription = $testcase->getDescription();

    // Put first sentence or line (whichever is shortest) of testdescription in shortdescription, and rest in restdescription
    $maxlength = 80; // max length of first sentence
    $firsthtmlcode = strpos( $testdescription, '<' );
    if ( $firsthtmlcode === false ) $firsthtmlcode = $maxlength;
    $firstdot = strpos( $testdescription, '.' );
    if ( $firstdot === false ) $firstdot = $maxlength;
    else $firstdot += 1; // include first dot
    $length = min( $firsthtmlcode, $firstdot, $maxlength );
    $shortdescription = substr( $testdescription, 0, $length );
    if ( substr( $testdescription, $length, 5 ) == '<br/>' ) $length += 5; // Cosmetic to avoid extra newline inside div
    $restdescription = substr( $testdescription, $length );
    $restdescription = trim_newlines( $restdescription, true ); // Trim tailing new lines (<br/>)
    $restdescription_onclick = '';
    if ( ! empty($restdescription) ) {
        $restdescription = ' <a class="expandable expand-' . $testcase->getClassName() . ' longdesc" style="color:blue;">(more ...)</a>' .
            '<div class="expandable expand-' . $testcase->getClassName() . ' longdesc" id="' . $testcase->getClassName() . '-longdesc" style="display:none;">' . $restdescription . '</div>';
        $restdescription_onclick = ' onclick="$(\'.expand-' . $testcase->getClassName() . '\').toggle();" style="cursor:hand;"';
    }

    // Add tests to table
    $teststack = $teststack .
        '<tr>' .
        '<td>' .
        '<a id="videoload-'. $testcase->getClassName() .'" href="#' . $testcase->getClassName() . '-overview">' . $testcase->getClassName() . '</a>' .
        '</td>' .
        '<td>' .
        '<a href="' . $result->getExternalUrl() . $testcase->getClassName() . '.ogg">' . $testcase->getClassName() . '.ogg</a>' .
        '</td>' .
        '<td>' .
        $description_text .
        '</td>' .
        '<td>' .
        '<a href="' . $testcase->getClassName() . '.xml">' . $testcase->getClassName() . '.xml</a>' .
        '</td>' .
        '<td>' .
        '<a href="' . $result->getTestsDirectoryRelative() . $testcase->getClassName() . '.php">' . $testcase->getClassName() . '.php</a>' .
        '</td>' .
        '<td>' .
        '<a href="' . $testcase->getClassName() . '_ffmpeg.log" target="_blank">' . $testcase->getClassName() . '_ffmpeg.log</a>' .
        '</td>' .
        '<td align="center">' .
        duration( $testcase->getDuration() ) .
        '</td>' .
        '<td onclick="$(\'.expand-' . $testcase->getClassName() . '\').toggle();" style="cursor:hand;" align="center">' .
        '<b>' . $hotornot . '</b>' .
        '</td>' .
        '</tr>' .
        '<tr>' .
        '<td colspan=4 valign="top"' . $restdescription_onclick . '>' .
        $shortdescription . $restdescription .
        '</td>' .
        '<td colspan=4 valign="top">' .
        '<font face="Courier New" size=1><pre>' ;

    foreach($testcase->getTests() as $test) {
        if($test->hasError()) {
            $teststack .= ' [ ] <span style="text-decoration:line-through;">'.$test->getName().'</span>'.chr(10);
        }
        else {
            $teststack .= ' [X] '.$test->getName().chr(10);
        }
    }
    $teststack .= '</pre></font>' .
        '</td>' .
        '</tr>';

    $screenshots = $testcase->getScreenshots();
    // How many rows should video span? Depends on if we have screenshots or not
    $ssrowspan = (count($screenshots) > 0) ? '5' : '3';
    // Add video and description to onclick videos at top of page
    $videos .= '<div class="overview" id="' . $testcase->getClassName() . '-overview" style="display:none;">' .
        '<table border=0 cellpadding=0 cellspacing=2>' .
        '<tr>' .
        '<td>' .
        '<b>Video:</b> <a href="' . $result->getExternalUrl() . $testcase->getClassName() . '.ogg">' . $testcase->getClassName() . '.ogg</a>' .
        '</td>' .
        '<td colspan=2>' .
        '<b>jUnit XML log:</b> <a href="' . $testcase->getLogJunitXmlName() . '">' . $testcase->getLogJunitXmlName() . '</a>' .
        '</td>' .
        '</tr>' .
        '<tr>' .
        '<td valign="top" rowspan='.$ssrowspan.'>' .
        '<div id="video-' . $testcase->getClassName() . '"></div>' .
        '<br/>' .
        '<a href="#">HIDE CODE AND VIDEO</a>' .
        '</td>' .
        '<td colspan=2 valign="top">' .
        '<font face="Courier New" size=1>' . $stacktrace . '</font>' .
        '</td>' .
        '</tr>' .
        '<tr>';
    if (count($screenshots) > 0) {
        $videos .= '<td colspan=2 valign="top">' .
            '<b>Screenshots:</b>' .
            '</td>' .
            '</tr>' .
            '<tr>' .
            '<td colspan=2 valign="top">';
        foreach ($screenshots as $screenshotFilename) {
            $videos .= '<a class="single_image" rel="group-'.$testcase->getClassName().'" href="'.$result->getTestsDirectoryRelative().$screenshotFilename.'">' .
                    '<img border=1 width=100 height=100 src="'.$result->getTestsDirectoryRelative().$screenshotFilename.'" />' .
                    '</a>&nbsp;';
        }
        $videos .= '</td>' .
            '</tr>' .
            '<tr>';
    }
    $videos .= '<td colspan=2>' .
        '<b>Source code:</b> <a href="' . $result->getTestsDirectoryRelative() . $testcase->getClassName() . '.php">' . $testcase->getClassName() . '.php</a>' .
        '</td>' .
        '</tr>' .
        '<tr>' .
        '<td valign="top" align="right">' .
        '<font face="Courier New" size=1>' . $linenumbers . '</font>' .
        '</td>' .
        '<td valign="top">' .
        '<font face="Courier New" size=1>' . $testcase->getSourcecode() . '</font>' .
        '</td>' .
        '</tr>' .
        '</table>' .
        '</div>';
}



// Finish document ready and javascript, then post vidoes
echo '});</script>' . $videos;

?>
<b>Selenium test at <i><?=$result->getBaseUrl() ?></i> from <i><?= $result->getTestsDirectory() ?></i>
    started at <i><?= date('D M j H:i:s Y', $result->getTimeStart()) ?></i> Logs:
    <a href="selenium.log" target="_blank">selenium.log</a><?=(! $result->isOnScreen()) ? ', <a href="xvfb.log" target="_blank">xvfb.log</a>' : '' ?>
</b>
<br/>
<br/>
<table border=1 cellpadding=3 cellspacing=0>
    <tr>
        <th>Test overview</th>
        <th>Video</th>
        <th onclick="$('.expandable').toggle();" style="cursor:hand;">Description</th>
        <th>jUnit XML log</th>
        <th>Source</th>
        <th>ffmpeg log</th>
        <th>Runtime</th>
        <th onclick="$('.expandable').toggle();" style="cursor:hand;">Result</th>
    </tr>
<?php

echo $teststack ;

?>
</table>
<br/><b>Finished at <i><?=date('D M j H:i:s Y') ?></i>, total runtime: <i><?=duration($result->getDuration()) ?></i></b>
</body>
</html>
