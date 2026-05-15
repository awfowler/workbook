<?php
/**
 * Template Name: Workbook Importer
 */
require_once get_template_directory() . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

get_header();

global $wpdb;

function wb_import_excel($file_path, $table_name, $sheet, $firstrow = 4){
	global $wpdb;

	if (!file_exists($file_path)) {return "File not found.";}

	$spreadsheet = IOFactory::load($file_path);
	$sheet = $spreadsheet->getSheetByName($sheet);

	if (!$sheet) {return "Sheet 'Control data' not found.";}

	$highestRow = $sheet->getHighestRow();

	$inserted = 0;
	$skipped = 0;

	for ($row = $firstrow; $row <= $highestRow; $row++) {
		if($table_name=='wb_controldata'){
			$analysis_date_raw = $sheet->getCell("A$row")->getValue();
			$analysis_time	 = strtolower(trim($sheet->getCell("B$row")->getValue()));
			$dumas = $sheet->getCell("C$row")->getValue();
			$nir = $sheet->getCell("D$row")->getValue();
			$company= $sheet->getCell("E$row")->getValue();

			if (!$analysis_date_raw && !$company) {
				continue;
			}

			$analysis_date = null;
			if (is_numeric($analysis_date_raw)) {
				$analysis_date = gmdate("Y-m-d", ($analysis_date_raw - 25569) * 86400);
			} else {
				$analysis_date = date("Y-m-d", strtotime($analysis_date_raw));
			}
			
			$exists = $wpdb->get_var($wpdb->prepare("SELECT ID FROM wb_controldata WHERE Company = %s AND AnalysisDate = %s AND AnalysisTime = %s LIMIT 1",$company,$analysis_date,$analysis_time));
			if ($exists) {$skipped++;continue;}
			$wpdb->insert(
				'wb_controldata',
				[
					'Company'	=> $company,
					'CompanyId'	=> null,
					'OriginalNIR'	=> null,
					'BiasedNIR'	=> null,
					'AnalysisDate'  => $analysis_date,
					'AnalysisTime'  => $analysis_time,
					'DUMAS'		=> $dumas,
					'DUMASToggl'	=> 0,
					'NIR'		=> $nir,
					'NIRToggle'	=> 0,
					'CreateTimesta' => current_time('Y-m-d'),
					'ModifyTimesta' => null,
					'Counter'	=> null
				],
				[
					'%s','%d','%f','%f','%s','%s','%f','%d','%f','%d','%s','%s','%d'
				]
			);

			$inserted++;
		}
	}

	return "Imported $excel_file <br/> Using sheet $sheet and skiping to row $firstrow <br> Inserted: $inserted | Skipped (duplicates): $skipped";
}

echo '<div style="padding:20px;font-family:Arial;">';
echo '<h2>Workbook Importer</h2>';

$excel_file = get_template_directory()  . '/csv/Foss - Crisp - Workbook Data 2025_08-07-2025_12-16-43.xlsx';


if (isset($_GET['run_import']) && $_GET['run_import'] == 1) {
	$result = wb_import_excel($excel_file, 'wb_controldata', 'Control data');
	echo "<p><strong>$result</strong></p>";

} else {
	echo '<p>Click below to import workbook data.</p>';
	echo '<a href="?run_import=1" style="padding:10px 14px;background:#0073aa;color:#fff;text-decoration:none;">Run Import</a>';
}

echo '</div>';

get_footer();