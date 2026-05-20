<?php
/**
 * Template Name: Workbook Report
 */

get_header();
global $wpdb;
$vdata=array();
$companies=array();
$results=$wpdb->get_results('SELECT * FROM `wb_companies`');
foreach ($results as $company){
	$companies[$company->ID]=array('CompanyName' => $company->CompanyName,'Colour' => $company->Colour);
}

function variety_data($date, $group = 'English Spring'){
	global $wpdb;
	$html='<h2>'.esc_html($group).' Report</h2>';
	$html.='<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">';
	$html.='<thead style="background:#f2f2f2;">';
	$html.='	<tr>';
	$html.='		<th>Group</th>';
	$html.='		<th>Variety</th>';
	$html.='		<th>% in Total</th>';
	$html.='		<th>UK Seed Sales %</th>';
	$html.='		<th>Scottish Sales %</th>';
	$html.='		<th>E/W Sales %</th>';
	$html.='		<th>Count</th>';
	$html.='	</tr>';
	$html.='</thead>';
	$html.='<tbody>';
	
	$html.='<div style="padding:20px;font-family:Arial;">';
	$html.='	<h2>UK NIR Grain Network - Barley Workbook Report</h2>';
	$html.='	<h3>Report ID:</h3>';
	$html.='	<h3>Generated: '.$date.'</h3>';
	$html.='</div>';
	$sql = $wpdb->prepare("SELECT v.GroupID AS group_name, v.VarietyName AS variety, COUNT(d.ID) AS total_count,
	(SELECT COUNT(*) FROM wb_varietydata d2 JOIN wb_variety v2 ON d2.Variety = v2.VarietyName WHERE v2.GroupID = %s) AS group_total, v.PercUKbyType, v.PercScotbyType, v.PercEWbyType
	FROM wb_varietydata d INNER JOIN wb_variety v ON d.Variety = v.VarietyName
	WHERE v.GroupID = %s
	GROUP BY v.VarietyName, v.GroupID, v.PercUKbyType, v.PercScotbyType, v.PercEWbyType
	ORDER BY total_count DESC
	", $group, $group);
	$results = $wpdb->get_results($sql);
	$total_group_count = 0;
	foreach ($results as $r) {
		$total_group_count = $r->group_total;
	}
	if (!empty($results)){
		foreach ($results as $row){
			$percent_total = $total_group_count > 0 ? round(($row->total_count / $total_group_count) * 100, 1) : 0;				
			$html.='<tr>';
			$html.='<td>'.esc_html($row->group_name).'</td>';
			$html.='<td>'.esc_html($row->variety).'</td>';
			$html.='<td>'.esc_html($percent_total).'</td>';
			$html.='<td>'.esc_html($row->PercUKbyType).'</td>';
			$html.='<td>'.esc_html($row->PercScotbyType).'</td>';
			$html.='<td>'.esc_html($row->PercEWbyType).'</td>';
			$html.='<td>'.esc_html($row->total_count).'</td>';
			$html.='</tr>';
		}
		$html.='<tr style="font-weight:bold;background:#eee;">';
		$html.='	<td>Total for Group</td>';
		$html.='	<td>'.esc_html($group).'</td>';
		$html.='	<td>100%</td>';
		$html.='	<td></td>';
		$html.='	<td></td>';
		$html.='	<td></td>';
		$html.='	<td>'.esc_html($total_group_count).'</td>';
		$html.='</tr>';
	}else{
		$html.='<tr><td colspan="7">No data found for '.esc_html($group).'</td></tr>';
	}
	$html.='</tbody>';
	$html.='</table>';
	return $html;
}
function variety_data_company(){
	global $wpdb;

	$results = $wpdb->get_results(
		'SELECT ROUND(DUMAS / 0.05) * 0.05 AS RoundedValue, COUNT(*) AS Frequency 
		 FROM wb_varietydata 
		 WHERE DUMAS IS NOT NULL 
		 GROUP BY RoundedValue 
		 ORDER BY RoundedValue'
	);

	$labels = [];
	$data = [];

	foreach($results as $row){
		$labels[] = $row->RoundedValue;
		$data[] = $row->Frequency;
	}

	$yMax = (ceil(max($data) / 10) * 10) + 10;

	return '
	<canvas id="chartVD" width="400" height="200"></canvas>

	<script>
	(function () {

		new Chart(
			document.getElementById("chartVD").getContext("2d"),
			{
				type: "bar",
				data: {
					labels: ' . json_encode($labels) . ',
					datasets: [{
						label: "Frequency of BiasNIR",
						data: ' . json_encode($data) . ',
						backgroundColor: "purple"
					}]
				},
				options: {
					scales: {
						yAxes: [{
							ticks: {
								beginAtZero: true,
								max: ' . $yMax . '
							}
						}]
					}
				}
			}
		);

	})();
	</script>';
}


function dumas_control(){
	global $wpdb;
	$endDate = '2025-07-28';
	$startDate = date('Y-m-d', strtotime($endDate . ' -9 days'));
	// $sql = 'SELECT Company, AnalysisDate, AVG(DUMAS) AS DUMAS FROM wb_varietydata WHERE AnalysisDate BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' GROUP BY Company, AnalysisDate ORDER BY AnalysisDate ASC';	
	$sql = 'SELECT Company, AnalysisDate, AVG(DUMAS) AS DUMAS FROM wb_varietydata WHERE AnalysisDate  GROUP BY Company, AnalysisDate ORDER BY AnalysisDate ASC';	

	$results = $wpdb->get_results($sql);
	$dates = [];
	$series = [];	
	foreach($results as $row) {	
		$dates[$row->AnalysisDate] = $row->AnalysisDate;
    		$series[$row->Company][$row->AnalysisDate] = (float)$row->DUMAS;
	}
	ksort($dates);	
	$labels = array_values($dates);	
	$datasets = [];	
	$colors = ['red','blue','green','purple','orange'];
	$i = 0;	
	foreach ($series as $company => $values) {
	    $data = [];	
	    foreach ($labels as $date) {
	        $data[] = $values[$date] ?? null;
	    }	
	    $datasets[] = [
	        'label' => $company,
	        'data' => $data,
	        'borderColor' => $colors[$i % count($colors)],
	        'fill' => false
	    ];	
	    $i++;
	}

	$html='<h1>DUMAS Control (Raw Data)</h1><canvas id="chartDCRD" width="400" height="200"></canvas>';
	$html .= "
	<script>
	(() => {
	var DCctx = document.getElementById('chartDCRD').getContext('2d');
	new Chart(DCctx, {
	    type: 'line',
	    data: {
	        labels: ".json_encode($labels).",
	        datasets: ".json_encode($datasets)."
	    },
	    options: {
	        responsive: true,
	        elements: {
	            line: {
	                tension: 0.2
	            }
	        },
	        scales: {
	            yAxes: [{
	                ticks: {
	                    beginAtZero: false
	                }
	            }]
	        }
	    }
	});
	})();
</script>";
	return $html;
}

function mean($arr) {
	return count($arr) ? array_sum($arr) / count($arr) : 0;
}

function stddev($arr) {
	if (!is_countable($arr) || count($arr) < 2) return 0;
	$m = mean($arr);
	$sum = 0;
	foreach ($arr as $v) {
		$sum += pow($v - $m, 2);
	}
	return sqrt($sum / (count($arr) - 1));
}

function dumas_control_report_old($endDate = '2025-07-07') {
    global $wpdb;
    $startDate = date('Y-m-d', strtotime($endDate . ' -9 days'));
    $sql = $wpdb->prepare("SELECT Company, AnalysisDate, DUMAS FROM wb_controldatan WHERE DUMAS IS NOT NULL AND AnalysisDate <= %s ORDER BY Company, AnalysisDate", $endDate);

    $results = $wpdb->get_results($sql);
    $data = [];
    foreach ($results as $r) {
        $c = $r->Company;
        if (!isset($data[$c])) {
            $data[$c] = [
                'initial' => [],
                'harvest' => []
            ];
        }
        if ($r->AnalysisDate < $startDate) {
            $data[$c]['initial'][] = $r->DUMAS;
        } else {
            $data[$c]['harvest'][] = $r->DUMAS;
        }
    }
    $html = "<h2>DUMAS Control Report</h2>";
    $html .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;width:100%'>";
    $html .= "
    <tr>
        <th>Company</th>
        <th>Initial Harvest Mean</th>
        <th>Initial Harvest Std Dev</th>
        <th>Initial Harvest Correction</th>
        <th>Initial Harvest Count</th>
        <th>Harvest Mean</th>
        <th>Harvest Std Dev</th>
        <th>Harvest Count</th>
        <th>Current Running Mean</th>
        <th>Current Correction</th>
        <th>Current Harvest Count</th>
    </tr>";

    $allInitial = [];
    $allHarvest = [];
    foreach ($data as $company => $d) {
        $iMean = mean($d['initial']);
        $iSD   = stddev($d['initial']);
        $hMean = mean($d['harvest']);
        $hSD   = stddev($d['harvest']);
        $hCnt  = count($d['harvest']);
        $correction = 0;
        $currentMean = $hMean;
        $currentCorrection = $correction;
        $html .= "<tr>
            <td>$company</td>
            <td>".round($iMean,3)."</td>
            <td>".round($iSD,3)."</td>
            <td>".round($correction,3)."</td>
            <td>0</td>
            <td>".round($hMean,3)."</td>
            <td>".round($hSD,3)."</td>
            <td>$hCnt</td>
            <td>".round($currentMean,3)."</td>
            <td>".round($currentCorrection,3)."</td>
            <td>0</td>
        </tr>";

        $allInitial = array_merge($allInitial, $d['initial']);
        $allHarvest = array_merge($allHarvest, $d['harvest']);
    }

    $iMeanAll = mean($allInitial);
    $hMeanAll = mean($allHarvest);

    $html .= "<tr style='font-weight:bold;background:#eee'>
        <td>ALL</td>
        <td>".round($iMeanAll,3)."</td>
        <td>".round(stddev($allInitial),3)."</td>
        <td>".round($hMeanAll - $iMeanAll,3)."</td>
        <td>".count($allInitial)."</td>
        <td>".round($hMeanAll,3)."</td>
        <td>".round(stddev($allHarvest),3)."</td>
        <td>".count($allHarvest)."</td>
        <td>".round($hMeanAll,3)."</td>
        <td>".round($hMeanAll - $iMeanAll,3)."</td>
        <td>".count($allHarvest)."</td>
    </tr>";
    $html .= "</table>";
    return $html;
}

function dumas_control_report($endDate = '2025-07-07') {
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare("SELECT Company, AnalysisDate, DUMAS FROM wb_controldata WHERE DUMAS IS NOT NULL AND AnalysisDate <= %s ORDER BY Company, AnalysisDate", $endDate));
    $company = [];

    foreach ($rows as $r) {
        $company[$r->Company][] = (float)$r->DUMAS;
    }

    $initial = [];
    $allInitial = [];
    foreach ($company as $c => $vals) {
        $initial[$c] = array_slice($vals, 0, 10);
        $allInitial = array_merge($allInitial, $initial[$c]);
    }

    $allInitialMean = mean($allInitial);
    $flatHarvest = [];
    foreach ($company as $vals) {
        $flatHarvest = array_merge($flatHarvest, $vals);
    }

    $hMean = mean($flatHarvest);
    $hSD   = stddev($flatHarvest);
    $clean = [];
    foreach ($company as $c => $vals) {
        foreach ($vals as $v) {

            if (abs($v - $hMean) <= (3 * $hSD)) {
                $clean[$c][] = $v;
            }
        }
    }

    $html = "<h2>DUMAS Control Report</h2>";
    $html .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;width:100%'>";
    $html .= "
    <tr>
        <th>Company</th>
        <th>Initial Harvest Mean</th>
        <th>Initial Harvest SD</th>
        <th>Initial Harvest Correction</th>
        <th>Initial Count</th>
        <th>Harvest Mean</th>
        <th>Harvest SD</th>
        <th>Harvest Count</th>
        <th>Current Running Mean</th>
        <th>Current Correction</th>
        <th>Current Count</th>
    </tr>";

    foreach ($company as $c => $vals) {

        $i = $initial[$c] ?? [];
        $h = $clean[$c] ?? [];


        $iMean = mean($i);
        $iSD   = stddev($i);
        $iCnt  = count($i);

        $hMean = mean($h);
        $hSD   = stddev($h);
        $hCnt  = count($h);

        $last10 = array_slice($h, -10);
        $runningMean = mean($last10);

        $currentCorrection = $allInitialMean - $runningMean;
        $html .= "<tr>
            <td>{$c}</td>
            <td>".round($iMean,3)."</td>
            <td>".round($iSD,3)."</td>
            <td>".round($allInitialMean - $iMean,3)."</td>
            <td>{$iCnt}</td>

            <td>".round($hMean,3)."</td>
            <td>".round($hSD,3)."</td>
            <td>{$hCnt}</td>

            <td>".round($runningMean,3)."</td>
            <td>".round($currentCorrection,3)."</td>
            <td>{$hCnt}</td>
        </tr>";
    }

    $html .= "</table>";

    return $html;
}

function dumas_control_chart($endDate = '2025-08-31') {
	global $wpdb;
	$startDate = '2025-06-22';
	$rows = $wpdb->get_results($wpdb->prepare("SELECT AnalysisDate, AnalysisTime, DUMAS FROM wb_controldata WHERE AnalysisDate BETWEEN %s AND %s AND DUMAS IS NOT NULL ORDER BY AnalysisDate ASC",$startDate,$endDate));

	$data = [];
	foreach ($rows as $r) {		$date = date('d/m/Y', strtotime($r->AnalysisDate));

		if (!isset($data[$date])) {
			$data[$date] = ['am' => null, 'pm' => null];
		}

		$time = strtolower($r->AnalysisTime);
		$data[$date][$time] = (float)$r->DUMAS;
	}
	ksort($data);

	$labels = [];
	$amData = [];
	$pmData = [];
	foreach ($data as $date => $vals) {
		$labels[] = $date;
		$amData[] = $vals['am'] ?? null;
		$pmData[] = $vals['pm'] ?? null;
	}

	// $labelsJs = json_encode($labels);
	// $amJs = json_encode($amData);
	// $pmJs = json_encode($pmData);

	return '
	<h2>DUMAS Control Chart</h2>
	<canvas id="chartDC" height="120"></canvas>
	<script>
	(function () {
		var ctx = document.getElementById("chartDC").getContext("2d");
		var targetLine = {
			afterDatasetsDraw: function(chart) {
				var ctx = chart.ctx;
				var left = chart.chartArea.left;
				var right = chart.chartArea.right;
				var yScale = chart.scales["y-axis-0"];
				var yPos = yScale.getPixelForValue(1.5);
				ctx.save();
				ctx.beginPath();
				ctx.strokeStyle = "gold";
				ctx.lineWidth = 2;
				ctx.moveTo(left, yPos);
				ctx.lineTo(right, yPos);
				ctx.stroke();
				ctx.restore();
			}
		};

		new Chart(ctx, {
			type: "line",
			data: {
				labels: ' . json_encode($labels) . ',
				datasets: [
					{
						label: "AM Control",
						data: ' . json_encode($amData) . ',
						borderColor: "purple",
						backgroundColor: "purple",
						pointStyle: "rect",
						pointRadius: 6,
						showLine: false,
						spanGaps: true
					},
					{
						label: "PM Control",
						data: ' . json_encode($pmData) . ',
						borderColor: "purple",
						backgroundColor: "purple",
						pointStyle: "rect",
						pointRadius: 6,
						showLine: false,
						spanGaps: true
					}
				]
			},
			options: {
				responsive: true,
				scales: {
					yAxes: [{
						ticks: {
							min: 1.4,
							max: 1.6
						}
					}]
				}
			},
			plugins: [targetLine]
		});
	})();
	</script>';
}

// Can you produce the graph and the table. The graph has lines on it in a colour representing the company eg, sciantec and might go between say 0.04 and -0.04 The table looks like this :- Statistics,Samples,% Outlier,Min,Max,SD,Bias,SEP,RMSEO,% +/- 0.04, % +/- NIR,380,0,1.21,2.31,1.79,.17,-0.07,0.35,0.36,78,98 DUMAs,380,,1.21,2.38,1.8,.18,,,,, Do you need a copy of the variety data?
function bias_bar_chart($variety = 'SY Kingsbarn (F) (Winter)') {
	global $wpdb, $companies, $vdata;
	$sql='SELECT ID, Company, CompanyId, AnalysisDate, NIR, DUMAS, NIR - DUMAS as bias FROM wb_varietydata WHERE NIR IS NOT NULL AND DUMAS IS NOT NULL AND Variety=\''.$variety.'\' ORDER BY AnalysisDate ASC, ID ASC';
	$rows = $wpdb->get_results($sql);
	
	$displayDates = 'true';
	$labels = [];
	$data = [];
	$colors = [];
	$nir = [];
	$dumas = [];
	$html = '';
	
	foreach ($rows as $r) {
		$labels[] = $r->AnalysisDate . ' #' . $r->ID;
		$nir[] = $r->NIR;
		$dumas[] = $r->DUMAS;
		$data[] = $r->bias;
		$colors[] = $companies[$r->CompanyId]['Colour'];
	}
	$countNIR = count($nir);
	if($countNIR > 0){
		$sumSquaredErrors = 0;	
		$sumBiasCorrected = 0;
		$within04 = 0;
		$within08 = 0;
		$bias = array_sum($data) / $countNIR;
		foreach ($data as $e) {
			$sumSquaredErrors += pow($e, 2);
			$sumBiasCorrected += pow(($e - $bias), 2);
			if (abs($e) <= 0.04) {$within04++;}	
			if (abs($e) <= 0.08) {$within08++;}
		}		
		$rmsep = sqrt($sumSquaredErrors / $countNIR);
		$sep = sqrt($sumBiasCorrected / ($countNIR - 1));
		$percent04 = round(($within04 / $countNIR) * 100, 2);	
		$percent08 = round(($within08 / $countNIR) * 100, 2);
	}else{
		$bias = 0;
		$rmsep = 0;
		$sep = 0;
		$percent04 = 0;
		$percent08 = 0;
	}
	
	if(count($data)>0){
		$html.='
		<h2>Bias Control (Bar Chart)</h2>
		<canvas id="biasBarChart" height="120"></canvas>
		<script>
		(function () {
			new Chart(document.getElementById("biasBarChart"), {
				type: "bar",
				data: {
					labels: ' . json_encode($labels) . ',
					datasets: [{
						label: "Bias (NIR - DUMAS)",
						data: ' . json_encode($data) . ',
						backgroundColor: ' . json_encode($colors) . ',
						borderWidth: 0
					}]
				},
				options: {
					responsive: true,
					scales: {
						xAxes: [{
							ticks: {
								display: '.$displayDates.'
							}
						}],
						yAxes: [{
							ticks: {
								suggestedMin: -0.16,
								suggestedMax: 0.16
							}
						}]
					},
					tooltips: {
						callbacks: {
							label: function(tooltipItem) {
								return "Bias: " + tooltipItem.yLabel;
							}
						}
					}
				}
			});
		})();
		</script>';
	}else{
		$html.='Unable to find any data for '.$variety.'<br>';	
	}
	
	$vdata[$variety]['NIR']=array('samples' => $countNIR, 'outlier' => '?', 'min' => min($nir) ,'max' => max($nir), 'mean'  => mean($nir),'sd' => stddev($nir),'bias' => $bias,'sep' => $sep,'rmsep' =>  $rmsep,'percent04' => $percent04, 'percent08' => $percent08);
	$vdata[$variety]['DUMAS']=array('samples' => count($dumas) , 'outlier' => '?', 'min' => min($dumas),'max' => max($dumas), 'mean'  => mean($dumas),'sd' => stddev($dumas));
	
	
	$html.='<h2>Observation by Analysis Date</h2>';
	$html.='<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">';
	$html.='<thead style="background:#f2f2f2;">';
	$html.='	<tr>';
	$html.='		<th>Statistics</th>';
	$html.='		<th>Samples</th>';
	$html.='		<th>% Outlier</th>';
	$html.='		<th>Min</th>';
	$html.='		<th>Max</th>';
	$html.='		<th>Mean</th>';
	$html.='		<th>SD</th>';
	$html.='		<th>Bias</th>';
	$html.='		<th>SEP</th>';
	$html.='		<th>RMSEO</th>';
	$html.='		<th>% +/- 0.04</th>';
	$html.='		<th>% +/- 0.08</th>';
	$html.='	</tr>';
	$html.='</thead>';
	$html.='<tbody>';
	$html.='	<tr>';
	$html.='		<th>NIR</th>';
	$html.='		<th>'.$vdata[$variety]['NIR']['samples'].'</th>';
	$html.='		<th></th>';
	$html.='		<th>'.$vdata[$variety]['NIR']['min'].'</th>';
	$html.='		<th>'.$vdata[$variety]['NIR']['max'].'</th>';
	$html.='		<th>'.$vdata[$variety]['NIR']['mean'].'</th>';
	$html.='		<th>'.$vdata[$variety]['NIR']['sd'].'</th>';
	$html.='		<th>'.$vdata[$variety]['NIR']['bias'].'</th>';
	$html.='		<th>'.$vdata[$variety]['NIR']['sep'].'</th>';
	$html.='		<th>'.$vdata[$variety]['NIR']['rmsep'].'</th>';
	$html.='		<th>'.$vdata[$variety]['NIR']['percent04'].'</th>';
	$html.='		<th>'.$vdata[$variety]['NIR']['percent08'].'</th>';
	$html.='    </tr>';
	$html.='	<tr>';
	$html.='		<th>DUMAS</th>';
	$html.='		<th>'.$vdata[$variety]['DUMAS']['samples'].'</th>';
	$html.='		<th></th>';
	$html.='		<th>'.$vdata[$variety]['DUMAS']['min'].'</th>';
	$html.='		<th>'.$vdata[$variety]['DUMAS']['max'].'</th>';
	$html.='		<th>'.$vdata[$variety]['DUMAS']['mean'].'</th>';
	$html.='		<th>'.$vdata[$variety]['DUMAS']['sd'].'</th>';
	$html.='		<th></th>';
	$html.='		<th></th>';
	$html.='		<th></th>';
	$html.='		<th></th>';
	$html.='		<th></th>';
	$html.='	</tr>';
	$html.='</tbody>';
	$html.='</table>';
	
	return $html;
}


function nir_dumas_scatter_chart($variety = 'SY Kingsbarn (F) (Winter)') {
	global $wpdb;
	$sql = "SELECT ID, NIR, DUMAS FROM wb_varietydata WHERE NIR IS NOT NULL AND DUMAS IS NOT NULL AND Variety='".$variety."' ORDER BY AnalysisDate ASC, ID ASC";
	$rows = $wpdb->get_results($sql);
	$points = [];
	$x = [];
	$y = [];
	foreach ($rows as $r) {
		$points[] = ['x' => (float)$r->NIR, 'y' => (float)$r->DUMAS];
		$x[] = (float)$r->NIR;
		$y[] = (float)$r->DUMAS;
	}

	$count = count($x);
	$sumX = array_sum($x);
	$sumY = array_sum($y);
	$sumXY = 0;
	$sumXX = 0;
	for ($i = 0; $i < $count; $i++) {
		$sumXY += $x[$i] * $y[$i];
		$sumXX += $x[$i] * $x[$i];
	}

	$slope = (($count * $sumXY) - ($sumX * $sumY)) / (($count * $sumXX) - ($sumX * $sumX));
	$intercept = ($sumY - ($slope * $sumX)) / $count;
	$minX = min($x);
	$maxX = max($x);
	$regressionLine = [
		['x' => $minX, 'y' => ($slope * $minX) + $intercept],
		['x' => $maxX, 'y' => ($slope * $maxX) + $intercept]
	];

	$html="<canvas id=\"nirDumasScatter\"></canvas>
	<script>
	(function () {
		var ctx = document.getElementById('nirDumasScatter');
		new Chart(ctx, {
			type: 'scatter',
			data: {
				datasets: [
					{
						label: 'Samples',
						data: ".json_encode($points).",
						backgroundColor: 'blue',
						pointStyle: 'rect',
						pointRadius: 5
					},
					{
						label: 'Regression',
						data: ".json_encode($regressionLine).",
						type: 'line',
						borderColor: 'black',
						borderWidth: 2,
						pointRadius: 0,
						fill: false
					}
				]
			},
			options: {
				responsive: true,
				scales: {
					x: {
						title: {
							display: true,
							text: 'NIR N'
						}
					},
					y: {
						title: {
							display: true,
							text: 'DUMAS N'
						}
					}
				}
			}
		});
	})();
	</script>";
	return $html;
}

?>
<div style="padding:20px;font-family:Arial;">
	<?php 
		print variety_data(date('d F Y'),'English Spring'); 
        	print '<div>'.variety_data_company().'</div>';
        	print '<div>'.dumas_control().'</div>';
		print '<div>'.dumas_control_report().'</div>';
		print '<div>'.dumas_control_chart().'</div>';
		print '<div>'.bias_bar_chart().'</div>';
		print '<div>'.nir_dumas_scatter_chart().'</div>';
	?>
</div>

<?php

echo '</div>';
get_footer();