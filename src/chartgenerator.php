<?php
/*
	*********************************************************************
	* phpLogCon - http://www.phplogcon.org
	* -----------------------------------------------------------------
	* Export Code File											
	*																	
	* -> This file will create gfx of charts, and handle image caching
	*																	
	* All directives are explained within this file
	*
	* Copyright (C) 2008 Adiscon GmbH.
	*
	* This file is part of phpLogCon.
	*
	* PhpLogCon is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* PhpLogCon is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with phpLogCon. If not, see <http://www.gnu.org/licenses/>.
	*
	* A copy of the GPL can be found in the file "COPYING" in this
	* distribution				
	*********************************************************************
*/

// *** Default includes	and procedures *** //
define('IN_PHPLOGCON', true);
$gl_root_path = './';

// Now include necessary include files!
include($gl_root_path . 'include/functions_common.php');
include($gl_root_path . 'include/functions_frontendhelpers.php');
include($gl_root_path . 'include/functions_filters.php');

// Include LogStream facility
include($gl_root_path . 'classes/logstream.class.php');

InitPhpLogCon();
InitSourceConfigs();
InitFrontEndDefaults();	// Only in WebFrontEnd
InitFilterHelpers();	// Helpers for frontend filtering!
// ---

// --- READ CONTENT Vars
$content['error_occured'] = false;

if ( isset($_GET['type']) ) 
	$content['chart_type'] = intval($_GET['type']);
else
	$content['chart_type'] = CHART_CAKE;

if ( isset($_GET['width']) ) 
{
	$content['chart_width'] = intval($_GET['width']);
	
	// Limit Chart Size for now
	if		( $content['chart_width'] < 100 ) 
		$content['chart_width'] = 100;
	else if	( $content['chart_width'] > 1000 ) 
		$content['chart_width'] = 1000;
}
else
	$content['chart_width'] = 100;

if ( isset($_GET['byfield']) )
{
	if ( isset($fields[ $_GET['byfield'] ]) )
	{
		$content['chart_field'] = $_GET['byfield'];
		$content['chart_fieldtype'] = $fields[SYSLOG_UID]['FieldType'];
	}
	else
	{
		$content['error_occured'] = true;
		$content['error_details'] = $content['LN_GEN_ERROR_INVALIDFIELD'];
	}
}
else
{
	$content['error_occured'] = true;
	$content['error_details'] = $content['LN_GEN_ERROR_MISSINGCHARTFIELD'];
}

if ( isset($_GET['maxrecords']) ) 
	$content['maxrecords'] = intval($_GET['maxrecords']);
else
	$content['maxrecords'] = 10;
// ---

// --- BEGIN CREATE TITLE
$content['TITLE'] = InitPageTitle();
// --- END CREATE TITLE

// --- BEGIN Custom Code

// Get data and print on the image!
if ( !$content['error_occured'] )
{
	if ( isset($content['Sources'][$currentSourceID]) ) 
	{
		// Include basic files needed
		require_once ($gl_root_path . "classes/jpgraph/jpgraph.php");

		// Obtain and get the Config Object
		$stream_config = $content['Sources'][$currentSourceID]['ObjRef'];

		// Create LogStream Object 
		$stream = $stream_config->LogStreamFactory($stream_config);
		
		$res = $stream->Open( $content['Columns'], true );
		if ( $res == SUCCESS )
		{
			// Obtain data from the logstream!
			$chartData = $stream->GetCountSortedByField($content['chart_field'], $content['chart_fieldtype'], $content['maxrecords']);

			// If data is valid, we have an array!
			if ( is_array($chartData) )
			{
				// Create Y array!
				foreach( $chartData as $myKey => $myData)
				{
//					echo $myKey . "<br>";
					$YchartData[] = intval($myData);
					$XchartData[] = strlen($myKey) > 0 ? $myKey : "Unknown";
				}

				if ( $content['chart_type'] == CHART_CAKE )
				{
					// Include additional code filers for this chart!
					include_once ($gl_root_path . "classes/jpgraph/jpgraph_pie.php");
					include_once ($gl_root_path . "classes/jpgraph/jpgraph_pie3d.php");

					// Create Basic Image, and set basic properties!
					$graph = new PieGraph($content['chart_width'], $content['chart_width'], 'auto');
					$graph->SetMargin(60,20,30,30);	// Adjust margin area
					$graph->SetScale("textlin");
					$graph->SetMarginColor('white');
					$graph->SetBox();					// Box around plotarea

					// Set up the title for the graph
//					$graph->title->Set('Messagecount sorted by "' . $content[ $fields[$content['chart_field']]['FieldCaptionID'] ] . '"');
//					$graph->title->SetFont(FF_VERDANA,FS_NORMAL,12);
//					$graph->title->SetColor("darkred");

					// Setup the tab title
					$graph->tabtitle->Set('Messagecount sorted by "' . $content[ $fields[$content['chart_field']]['FieldCaptionID'] ] . '"');
					$graph->tabtitle->SetFont(FF_ARIAL,FS_BOLD,10);


					// Setup font for axis
					$graph->xaxis->SetFont(FF_VERDANA,FS_NORMAL,10);
					$graph->yaxis->SetFont(FF_VERDANA,FS_NORMAL,10);

					// Show 0 label on Y-axis (default is not to show)
					$graph->yscale->ticks->SupressZeroLabel(false);


					// Create
					$p1 = new PiePlot3D($YchartData);
					$p1->SetLegends($XchartData);
//					$targ=array("pie3d_csimex1.php?v=1","pie3d_csimex1.php?v=2","pie3d_csimex1.php?v=3",
//								"pie3d_csimex1.php?v=4","pie3d_csimex1.php?v=5","pie3d_csimex1.php?v=6");
//					$alts=array("val=%d","val=%d","val=%d","val=%d","val=%d","val=%d");
//					$p1->SetCSIMTargets($targ,$alts);

					// Use absolute labels
					$p1->SetLabelType(1);
					$p1->value->SetFormat("%d");

					// Move the pie slightly to the left
					$p1->SetCenter(0.4,0.5);

					$graph->Add($p1);
				}
				else if ( $content['chart_type'] == CHART_BARS_VERTICAL )
				{
					// Include additional code filers for this chart!
					include_once ($gl_root_path . "classes/jpgraph/jpgraph_bar.php");
					include_once ($gl_root_path . "classes/jpgraph/jpgraph_line.php");

					// Create Basic Image, and set basic properties!
					$graph = new Graph($content['chart_width'], $content['chart_width'], 'auto');
					$graph->SetMargin(60,20,30,30);	// Adjust margin area
					$graph->SetScale("textlin");
					$graph->SetMarginColor('white');
					$graph->SetBox();					// Box around plotarea

					// Set up the title for the graph
			//		$graph->title->Set("Bar gradient (Left reflection)");
			//		$graph->title->SetFont(FF_VERDANA,FS_NORMAL,12);
			//		$graph->title->SetColor("darkred");

					// Setup font for axis
					$graph->xaxis->SetFont(FF_VERDANA,FS_NORMAL,10);
					$graph->yaxis->SetFont(FF_VERDANA,FS_NORMAL,10);

					// Show 0 label on Y-axis (default is not to show)
					$graph->yscale->ticks->SupressZeroLabel(false);


					// Setup the tab title
					$graph->tabtitle->Set('Messagecount sorted by "' . $content[ $fields[$content['chart_field']]['FieldCaptionID'] ] . '"');
					$graph->tabtitle->SetFont(FF_ARIAL,FS_BOLD,10);

					// Setup the X and Y grid
					$graph->ygrid->SetFill(true,'#DDDDDD@0.5','#BBBBBB@0.5');
					$graph->ygrid->SetLineStyle('dashed');
					$graph->ygrid->SetColor('gray');
					$graph->xgrid->Show();
					$graph->xgrid->SetLineStyle('dashed');
					$graph->xgrid->SetColor('gray');

					// Setup X-axis labels
					$graph->xaxis->SetTickLabels($XchartData);
					$graph->xaxis->SetFont(FF_ARIAL,FS_NORMAL,8);
					$graph->xaxis->SetLabelAngle(0);

					// Create a bar pot
					$bplot = new BarPlot($YchartData);
					$bplot->SetWidth(0.6);
					$fcol='#440000';
					$tcol='#FF9090';
					$bplot->SetFillGradient($fcol,$tcol,GRAD_LEFT_REFLECTION);
					$graph->Add($bplot);

					// Create filled line plot
					$lplot = new LinePlot($YchartData);
					$lplot->SetFillColor('skyblue@0.5');
					$lplot->SetColor('navy@0.7');
					$lplot->SetBarCenter();

					$lplot->mark->SetType(MARK_SQUARE);
					$lplot->mark->SetColor('blue@0.5');
					$lplot->mark->SetFillColor('lightblue');
					$lplot->mark->SetSize(6);

					$graph->Add($lplot);
				}
				else if ( $content['chart_type'] == CHART_BARS_HORIZONTAL )
				{
					$content['error_occured'] = true;
					$content['error_details'] = $content['LN_GEN_ERROR_INVALIDTYPE'];

				}
				else
				{
					$content['error_occured'] = true;
					$content['error_details'] = $content['LN_GEN_ERROR_INVALIDTYPE'];
				}
			}
			else
			{
				$content['error_occured'] = true;
				$content['error_details'] = GetErrorMessage($chartData);
				if ( isset($extraErrorDescription) )
					$content['error_details'] .= "\n\n" . GetAndReplaceLangStr( $content['LN_SOURCES_ERROR_EXTRAMSG'], $extraErrorDescription);
			}


//$fields[SYSLOG_UID]['FieldID']

		}
		else
		{
			// This will disable to Main SyslogView and show an error message
			$content['error_occured'] = true;
			$content['error_details'] = GetErrorMessage($res);
			if ( isset($extraErrorDescription) )
				$content['error_details'] .= "\n\n" . GetAndReplaceLangStr( $content['LN_SOURCES_ERROR_EXTRAMSG'], $extraErrorDescription);
		}

		// Close file!
		$stream->Close();
	}
	else
	{
		$content['error_occured'] = true;
		$content['error_details'] = GetAndReplaceLangStr( $content['LN_GEN_ERROR_SOURCENOTFOUND'], $currentSourceID);
	}
}

if ( $content['error_occured'] )
{
	// QUICK AND DIRTY!
	$myImage = imagecreatetruecolor( $content['chart_width'], $content['chart_width']);

/*	// create basic colours
	$red = ImageColorAllocate($myImage, 255, 0, 0); 
	$green = ImageColorAllocate($myImage, 0, 255, 0);
	$gray = ImageColorAllocate($myImage, 128, 128, 128);
	$black = ImageColorAllocate($myImage, 0, 0, 0);
	$white = ImageColorAllocate($myImage, 255, 255, 255);

	// Fill image with colour, and create a border
	imagerectangle( $myImage, 0, 0, $content['chart_width']-1, $content['chart_width']-1, $gray );
	imagefill( $myImage, 1, 1, $white );
*/

	$text_color = imagecolorallocate($myImage, 255, 0, 0);
	imagestring($myImage, 3, 10, 10, $content['LN_GEN_ERRORDETAILS'], $text_color);
	imagestring($myImage, 3, 10, 25, $content['error_details'], $text_color);

	header ("Content-type: image/png");
	imagepng($myImage);		// Outputs the image to the browser
	imagedestroy($myImage); // Clean Image resource

	exit;
}
// --- 

// --- Output the image
$graph->Stroke(); 
//$graph->StrokeCSIM(); 
// --- 

?>