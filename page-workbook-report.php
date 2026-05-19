<?php
/**
 * Template Name: Workbook Report
 */

get_header();

global $wpdb;


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
	$sql = 'SELECT ROUND(DUMAS / 0.05) * 0.05 AS RoundedValue, COUNT(*) AS Frequency FROM wb_varietydata WHERE DUMAS IS NOT NULL GROUP BY RoundedValue ORDER BY RoundedValue';
	$results = $wpdb->get_results($sql);	
	$labels = [];
	$data = [];	
	foreach($results as $row) {	
	    $labels[] = $row->RoundedValue;
	    $data[] = $row->Frequency;
	}
	$maxValue = max($data);
    $yMax = ceil($maxValue * 1.1);  
    $yMax = (ceil($maxValue / 10) * 10) + 10;
	
	$labels_json = json_encode($labels);
	$data_json = json_encode($data);

	$html='<canvas id="myChart" width="400" height="200"></canvas>';
	$html.="
<script>
var labels = ".$labels_json.";
var data = ".$data_json.";
var yMax = ".$yMax."

var ctx = document.getElementById('myChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Frequency of BiasNIR',
            data: data,
            backgroundColor: 'purple'
        }]
    },
    options: {
        scales: {
            yAxes: [{
                ticks: {
                    min: 0,
                    max: yMax
                }
            }]
        }
    }
});
</script>
";
	return $html;

}



?>
<div style="padding:20px;font-family:Arial;">
	<?php 
		print variety_data(date('d F Y'),'English Spring'); 
		print '<div>'.variety_data_company().'</div>';
	
	?>
</div>

<?php

echo '</div>';
get_footer();