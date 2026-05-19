<?php
/**
 * Template Name: Workbook Report
 */

get_header();

global $wpdb;


function variety_data($group = 'English Spring', $date=date('d F Y')){
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
}
?>

<div style="padding:20px;font-family:Arial;">
	<h2><?php echo esc_html($group); ?> Report</h2>
	<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">
		<thead style="background:#f2f2f2;">
			<tr>
				<th>Group</th>
				<th>Variety</th>
				<th>% in Total</th>
				<th>UK Seed Sales %</th>
				<th>Scottish Sales %</th>
				<th>E/W Sales %</th>
				<th>Count</th>
			</tr>
		</thead>
		<tbody>
			<?php print variety_data(); ?>
		</tbody>
	</table>
</div>
<?php
echo '</div>';
get_footer();