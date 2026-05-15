<?php
/**
 * Template Name: Workbook Report
 */

get_header();

global $wpdb;




print '<div style="padding:20px;font-family:Arial;">';
print 	'<h2>UK NIR Grain Network - Barley Workbook Report</h2>';
print 	'<h3>Report ID:</h3>';
print 	'<h3>Generated: '.date('d F Y').'</h3>';
print '</div>';

$group = 'English Spring';

$sql = $wpdb->prepare("
    SELECT 
        v.GroupID AS group_name,
        v.VarietyName AS variety,

        COUNT(d.ID) AS total_count,

        (
            SELECT COUNT(*)
            FROM wb_varietydata d2
            JOIN wb_variety v2 ON d2.Variety = v2.VarietyName
            WHERE v2.GroupID = %s
        ) AS group_total,

        v.PercUKbyType,
        v.PercScotbyType,
        v.PercEWbyType

    FROM wb_varietydata d
    INNER JOIN wb_variety v ON d.Variety = v.VarietyName
    WHERE v.GroupID = %s
    GROUP BY v.VarietyName, v.GroupID, v.PercUKbyType, v.PercScotbyType, v.PercEWbyType
    ORDER BY total_count DESC
", $group, $group);

$results = $wpdb->get_results($sql);

$total_group_count = 0;
foreach ($results as $r) {
    $total_group_count = $r->group_total;
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

        <?php if (!empty($results)) : ?>
            <?php foreach ($results as $row) : ?>

                <?php
                    $percent_total = $total_group_count > 0
                        ? round(($row->total_count / $total_group_count) * 100, 1)
                        : 0;
                ?>

                <tr>
                    <td><?php echo esc_html($row->group_name); ?></td>
                    <td><?php echo esc_html($row->variety); ?></td>
                    <td><?php echo esc_html($percent_total); ?></td>
                    <td><?php echo esc_html($row->PercUKbyType); ?></td>
                    <td><?php echo esc_html($row->PercScotbyType); ?></td>
                    <td><?php echo esc_html($row->PercEWbyType); ?></td>
                    <td><?php echo esc_html($row->total_count); ?></td>
                </tr>

            <?php endforeach; ?>

            <!-- TOTAL ROW -->
            <tr style="font-weight:bold;background:#eee;">
                <td>Total for Group</td>
                <td><?php echo esc_html($group); ?></td>
                <td>100%</td>
                <td></td>
                <td></td>
                <td></td>
                <td>
                    <?php echo esc_html($total_group_count); ?>
                </td>
            </tr>

        <?php else : ?>
            <tr>
                <td colspan="7">No data found for <?php echo esc_html($group); ?></td>
            </tr>
        <?php endif; ?>

        </tbody>
    </table>

</div>

echo '</div>';

get_footer();