<?xml version="1.0" encoding="ISO-8859-1"?>
<!-- Generated by Hand -->
<!--
Copyright (C) 2011 Julia Longtin <julia.longtin@gmail.com>

This program is free software; you can redistribute it and/or
Modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
 -->
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" omit-xml-declaration="yes"/>
<xsl:include href="common_objects.xslt"/>
<xsl:include href="field_objects.xslt"/>
<xsl:strip-space elements="*"/>
<xsl:template match="/">
<xsl:apply-templates select="form"/>
</xsl:template>
<!-- The variable telling field_objects.xslt what form is calling it -->
<xsl:variable name="page">new</xsl:variable>
<!-- if fetchrow has contents, a variable with that name will be created by field_objects.xslt, and all fields created by it will retreive values from it. -->
<xsl:variable name="fetchrow"></xsl:variable>
<xsl:template match="form">
<xsl:text disable-output-escaping="yes"><![CDATA[<?php
/*
 * The page shown when the user requests a new form. allows the user to enter form contents, and save.
 */

/* for $GLOBALS[], ?? */
require_once('../../globals.php');
require_once($GLOBALS['srcdir'].'/api.inc');
/* for generate_form_field, ?? */
require_once($GLOBALS['srcdir'].'/options.inc.php');
/* note that we cannot include options_listadd.inc here, as it generates code before the <html> tag */

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Core\Header;

]]></xsl:text>
<!-- These templates generate PHP code -->
<xsl:apply-templates select="table|RealName|safename|acl|style"/>
<!-- no call to table's fetch, don't fetch form contents from the DB. -->
<xsl:apply-templates select="layout|manual" mode="head"/>
<xsl:if test="//table[@type='extended']">
<xsl:text disable-output-escaping="yes"><![CDATA[
/* new is only called from encounters. */
$submiturl = $GLOBALS['rootdir'].'/forms/'.$form_folder.'/save.php?mode=new&amp;return=encounter';]]></xsl:text>
</xsl:if>
<xsl:if test="//table[@type='form']">
<xsl:text disable-output-escaping="yes"><![CDATA[$submiturl = $GLOBALS['rootdir'].'/forms/'.$form_folder.'/save.php?mode=new&amp;return=encounter';]]></xsl:text>
</xsl:if>
<xsl:text disable-output-escaping="yes"><![CDATA[
/* no get logic here */
]]></xsl:text>
<!-- FIXME: this needs to work for layout based fields, as well. ideas? -->
<!-- no call to split_timeofday, no data from the db in the new form. -->
<!-- no check data call, as no data from the db goes into a new form -->
<xsl:text disable-output-escaping="yes"><![CDATA[
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>

<!-- declare this document as being encoded in UTF-8 -->
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" ></meta>

<!-- assets -->
<?php Header::setupHeader('datetime-picker'); ?>
<!-- Form Specific Stylesheet. -->
<link rel="stylesheet" href="../../forms/<?php echo $form_folder; ?>/style.css" type="text/css"/>

<script type="text/javascript">
// this line is to assist the calendar text boxes
var mypcc = '<?php echo $GLOBALS['phone_country_code']; ?>';

<!-- a validator for all the fields expected in this form -->
function validate() {
  return true;
}

<!-- a callback for validating field contents. executed at submission time. -->
function submitme() {
 var f = document.forms[0];
 if (validate(f)) {
  top.restoreSession();
  f.submit();
 }
}

</script>

]]></xsl:text>
<xsl:if test="//field[@type='dropdown_list_add']">
<xsl:text disable-output-escaping="yes"><![CDATA[<?php

/* support for the list-add selectbox feature. must be included inside of the <html> tags. */
require_once($GLOBALS['srcdir'].'/options_listadd.inc');

?>]]></xsl:text>
</xsl:if>
<xsl:text disable-output-escaping="yes"><![CDATA[

<title><?php echo htmlspecialchars('New '.$form_name); ?></title>

</head>
<body class="body_top">

<div id="title">
<a href="<?php echo $GLOBALS['form_exit_url']; ?>" onclick="top.restoreSession()">
<span class="title"><?php xl($form_name,'e'); ?></span>
<span class="back">(<?php xl('Back','e'); ?>)</span>
</a>
</div>

<form method="post" action="<?php echo $submiturl; ?>" id="<?php echo $form_folder; ?>">

<!-- Save/Cancel buttons -->
<div id="top_buttons" class="top_buttons">
<fieldset class="top_buttons">
<input type="button" class="save" value="<?php xl('Save','e'); ?>" />
<input type="button" class="dontsave" value="<?php xl('Don\'t Save','e'); ?>" />
</fieldset>
</div><!-- end top_buttons -->

<!-- container for the main body of the form -->
<div id="form_container">
<fieldset>

]]></xsl:text>
<xsl:apply-templates select="H2|H3|H4|manual|layout"/>
<xsl:text disable-output-escaping="yes"><![CDATA[
</fieldset>
</div> <!-- end form_container -->

<!-- Save/Cancel buttons -->
<div id="bottom_buttons" class="button_bar">
<fieldset>
<input type="button" class="save" value="<?php xl('Save','e'); ?>" />
<input type="button" class="dontsave" value="<?php xl('Don\'t Save','e'); ?>" />
</fieldset>
</div><!-- end bottom_buttons -->
</form>
<script type="text/javascript">
// jQuery stuff to make the page a little easier to use

$(function () {
    $(".save").click(function() { top.restoreSession(); document.forms["<?php echo $form_folder; ?>"].submit(); });
    $(".dontsave").click(function() { location.href='parent.closeTab(window.name, false)'; });

	$(".sectionlabel input").click( function() {
    	var section = $(this).attr("data-section");
		if ( $(this).attr('checked' ) ) {
			$("#"+section).show();
		} else {
			$("#"+section).hide();
		}
    });

    $(".sectionlabel input").attr( 'checked', 'checked' );
    $(".section").show();

    $('.datepicker').datetimepicker({
        <?php $datetimepicker_timepicker = false; ?>
        <?php $datetimepicker_showseconds = false; ?>
        <?php $datetimepicker_formatInput = false; ?>
        <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php'); ?>
        <?php // can add any additional javascript settings to datetimepicker here; need to prepend first setting with a comma ?>
    });
    $('.datetimepicker').datetimepicker({
        <?php $datetimepicker_timepicker = true; ?>
        <?php $datetimepicker_showseconds = false; ?>
        <?php $datetimepicker_formatInput = false; ?>
        <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php'); ?>
        <?php // can add any additional javascript settings to datetimepicker here; need to prepend first setting with a comma ?>
    });
});
</script>
</body>
</html>
]]></xsl:text>
</xsl:template>
</xsl:stylesheet>
