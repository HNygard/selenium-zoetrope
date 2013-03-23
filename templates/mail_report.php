<?php

// Required: $result

/* @var $result Zoetrope_Result */

// It is discouraged to use embedded or external style sheets in e-mails.
// To make this content easier to style, we use PHP to define colors and styles.
// See http://groundwire.org/support/articles/css-and-email-newsletters

//
// BASE64 IMG STRINGS
//
$green  = 'data:image/png;base64,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';
$yellow = 'data:image/png;base64,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';
$red    = 'data:image/png;base64,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';

//
// COLORS AND STATUS SPECIFIC
//
$color   = array();
$bigball = array();
/* @var $build_error boolean */
if ( $build_error ) {
    $bigball['src'] = $red;
    $bigball['alt'] = 'RED BALL';
    $heading        = 'BUILD FAILED';
    
    $color['black']  = '#000000';
    $color['dark']   = '#EF2929';
    $color['medium'] = '#F03535';
    $color['light']  = '#F35151';
    $color['white']  = '#FFFFFF';
}
/* @var $build_unstable boolean */
else if ( $build_unstable ) {
    $bigball['src'] = $yellow;
    $bigball['alt'] = 'YELLOW BALL';
    $heading        = 'BUILD UNSTABLE';
    
    $color['black']  = '#000000';
    $color['dark']   = '#FFB738';
    $color['medium'] = '#FDC041';
    $color['light']  = '#FAD457';
    $color['white']  = '#FFFFFF';
}
else { // success
    $bigball['src'] = $green;
    $bigball['alt'] = 'GREEN BALL';
    $heading        = 'BUILD SUCCESS';
    
    $color['black']  = '#000000';
    $color['dark']   = '#529A00';
    $color['medium'] = '#62A700';
    $color['light']  = '#7EBE00';
    $color['white']  = '#FFFFFF';
}

//
// STYLES
//
$h1 = array(
    'font_face' => 'Lucida Console',
    'font_size' => '5',
    'color'     => $color['black'],
    'bold'      => true,
    'underline' => false,
    'italic'    => false
);
$h2 = array(
    'font_face' => 'Verdana',
    'font_size' => '2',
    'color'     => $color['white'],
    'bold'      => true,
    'underline' => false,
    'italic'    => false
);
$h3 = array(
    'font_face' => 'Verdana',
    'font_size' => '2',
    'color'     => $color['white'],
    'bold'      => false,
    'underline' => false,
    'italic'    => false
);
$default = array(
    'font_face' => 'Verdana',
    'font_size' => '2',
    'color'     => $color['black'],
    'bold'      => false,
    'underline' => false,
    'italic'    => false
);
$strace = array(
    'font_face' => 'Courier New',
    'font_size' => '1',
    'color'     => $color['black'],
    'bold'      => false,
    'underline' => false,
    'italic'    => false
);

//
// HTML HEADINGS
//
$h1_ = '<font face="'.$h1['font_face'].'" size='.$h1['font_size'].' color="'.$h1['color'].'">'.($h1['bold'] ? '<b>' : '').($h1['italic'] ? '<i>' : '').($h1['underline'] ? '<u>' : '');
$_h1 = ($h1['underline'] ? '</u>' : '').($h1['italic'] ? '</i>' : '').($h1['bold'] ? '</b>' : '').'</font>';
$h2_ = '<font face="'.$h2['font_face'].'" size='.$h2['font_size'].' color="'.$h2['color'].'">'.($h2['bold'] ? '<b>' : '').($h2['italic'] ? '<i>' : '').($h2['underline'] ? '<u>' : '');
$_h2 = ($h2['underline'] ? '</u>' : '').($h2['italic'] ? '</i>' : '').($h2['bold'] ? '</b>' : '').'</font>';
$h3_ = '<font face="'.$h3['font_face'].'" size='.$h3['font_size'].' color="'.$h3['color'].'">'.($h3['bold'] ? '<b>' : '').($h3['italic'] ? '<i>' : '').($h3['underline'] ? '<u>' : '');
$_h3 = ($h3['underline'] ? '</u>' : '').($h3['italic'] ? '</i>' : '').($h3['bold'] ? '</b>' : '').'</font>';

// Default text style
$d_ = '<font face="'.$default['font_face'].'" size='.$default['font_size'].' color="'.$default['color'].'">'.($default['bold'] ? '<b>' : '').($default['italic'] ? '<i>' : '').($default['underline'] ? '<u>' : '');
$_d = ($default['underline'] ? '</u>' : '').($default['italic'] ? '</i>' : '').($default['bold'] ? '</b>' : '').'</font>';

// Stack trace text style
$st_ = '<font face="'.$strace['font_face'].'" size='.$strace['font_size'].' color="'.$strace['color'].'">'.($strace['bold'] ? '<b>' : '').($strace['italic'] ? '<i>' : '').($strace['underline'] ? '<u>' : '');
$_st = ($strace['underline'] ? '</u>' : '').($strace['italic'] ? '</i>' : '').($strace['bold'] ? '</b>' : '').'</font>';


// Result content
$bigball['width']  = '32';
$bigball['height'] = '32';

$build_url      = 'TODO: Find a way to list URL to Jenkins build';
$build_report   = 'TODO: Find a way to list URL to HTML report';
$build_project  = 'TODO: Find a way to list project';

$build_date     = date('D M j H:i:s Y', $result->getTimeStart());
$build_duration = duration($result->getDuration());

$changes        = 'TODO: Find a way to list changes'
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
 <body>
  <table border=0 cellpadding=0 cellspacing=4 width="100%">
   <tr>
    <th align="right" width="100"><img src="<?=$bigball['src']?>" width=<?=$bigball['width']?> height=<?=$bigball['height']?> alt="<?=$bigball['alt']?>" /></th>
    <th align="left"><?=$h1_?><?=$heading?><?=$_h1?></th>
   </tr>
   <tr>
    <td><?=$d_?>Build URL:<?=$_d?></td>
    <td><?=$d_?><a href="<?=$build_url?>" target="_blank"><?=$build_url?></a><?=$_d?></td>
   </tr>
   <tr>
    <td><?=$d_?>Project:<?=$_d?></td>
    <td><?=$d_?><?=$build_project?><?=$_d?></td>
   </tr>
   <tr>
    <td><?=$d_?>Date of build:<?=$_d?></td>
    <td><?=$d_?><?=$build_date?><?=$_d?></td>
   </tr>
   <tr>
    <td><?=$d_?>Build duration:<?=$_d?></td>
    <td><?=$d_?><?=$build_duration?><?=$_d?></td>
   </tr>
  </table>
  
  <br/>
  
  <table border=0 cellpadding=2 cellspacing=0 width="100%">
   <tr>
    <th align="left" bgcolor="<?=$color['dark']?>"><?=$h2_?>CHANGES<?=$_h2?></th>
   </tr>
   <tr>
    <td><?=$d_?><?=$changes?><?=$_d?></td>
   </tr>
  </table>

<?
if ($build_error || $build_unstable) {
?>
  <br/>
  
  <table border=0 cellpadding=2 cellspacing=1 width="100%">
   <tr>
    <th align="left" bgcolor="<?=$color['dark']?>"><?=$h2_?>ERRORS / FAILURES:<?=$_h2?></th>
   </tr>
<?
    $prevclass = '';
    foreach ($result->getTests() as $testcase) {
        foreach (array_merge($testcase->getErrors(), $testcase->getFailures()) as $error) {
            // Replace newlines with <br/>
            $content = htmlify($error->getContent());
            // Split up error info
            preg_match_all('#((\w+)::test\w+)(.+)#', $content, $matches, PREG_PATTERN_ORDER);
            $testclass_testname = $matches[1][0];
            $testclass          = $matches[2][0];
            // Trim all <br />'s at start of string
            $rest_contents      = preg_replace('#(?i)^(?:<br ?\/>)*#', '', $matches[3][0]);
            // Make URLs clickable
            $rest_contents      = auto_link_text($rest_contents);
            
            if ($testclass != $prevclass) {
                $prevclass = $testclass;
?>
   <tr>
    <td align="left" bgcolor="<?=$color['medium']?>"><?=$h2_?><?=$testclass?> - Assertions: <?=$testcase->getNumberOfAssertions()?> Failures: <?=$testcase->getNumberOfFailure()?> Errors: <?=$testcase->getNumberOfErrors()?><?=$_h2?></td>
   </tr>
<?
            }
?>
   <tr>
    <td align="left" bgcolor="<?=$color['light']?>"><?=$h3_?>Name: <?=$testclass_testname?><?=$_h3?></td>
   </tr>
   <tr>
    <td align="left">
     <?=$st_?><?=$rest_contents?><?=$_st?>
    </td>
   </tr>
<?
        }
    }
?>
  </table>
<?
}
?>
  
 </body>
</html>
