<?php
require 'app/models/Database.php';
require 'app/models/UiModel.php';

$pdo = Database::getConnection();
$uiModel = new UiModel();

// Reflect to access division label map
$reflector = new ReflectionClass('UiModel');
$fetchMeta = $reflector->getMethod('fetchMasterDivisionChartMeta');
$fetchMeta->setAccessible(true);
$chartMeta = $fetchMeta->invoke($uiModel, $pdo);
$divisionLabelMap = $chartMeta['map'] ?? [];
$labels = $chartMeta['labels'] ?? [];

$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));

$m3 = $now;
$y3 = (int) $m3->format('Y');
$mon3 = (int) $m3->format('n');

$m2 = $now->modify('-1 month');
$y2 = (int) $m2->format('Y');
$mon2 = (int) $m2->format('n');

$m1 = $now->modify('-2 months');
$y1 = (int) $m1->format('Y');
$mon1 = (int) $m1->format('n');

echo "Month 1: $y1-$mon1\n";
echo "Month 2: $y2-$mon2\n";
echo "Month 3: $y3-$mon3\n";

$sql = '
    SELECT divisi, YEAR(tanggal) AS yr, MONTH(tanggal) AS mon, COUNT(*) AS total 
    FROM it_support_request 
    WHERE (YEAR(tanggal) = :y1 AND MONTH(tanggal) = :mon1)
       OR (YEAR(tanggal) = :y2 AND MONTH(tanggal) = :mon2)
       OR (YEAR(tanggal) = :y3 AND MONTH(tanggal) = :mon3)
    GROUP BY divisi, YEAR(tanggal), MONTH(tanggal)
';
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'y1' => $y1, 'mon1' => $mon1,
    'y2' => $y2, 'mon2' => $mon2,
    'y3' => $y3, 'mon3' => $mon3,
]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$normalize = $reflector->getMethod('normalizeDivisionLabelForDisplay');
$normalize->setAccessible(true);

$data1 = array_fill_keys($labels, 0);
$data2 = array_fill_keys($labels, 0);
$data3 = array_fill_keys($labels, 0);

foreach ($rows as $row) {
    $div = $normalize->invoke($uiModel, (string) ($row['divisi'] ?? ''), $divisionLabelMap);
    if ($div === '' || !in_array($div, $labels, true)) {
        continue;
    }
    $yr = (int) $row['yr'];
    $mon = (int) $row['mon'];
    $total = (int) $row['total'];

    if ($yr === $y1 && $mon === $mon1) {
        $data1[$div] += $total;
    } elseif ($yr === $y2 && $mon === $mon2) {
        $data2[$div] += $total;
    } elseif ($yr === $y3 && $mon === $mon3) {
        $data3[$div] += $total;
    }
}

// Convert to sequential arrays matching $labels ordering
$seriesData1 = [];
$seriesData2 = [];
$seriesData3 = [];
foreach ($labels as $lbl) {
    $seriesData1[] = $data1[$lbl];
    $seriesData2[] = $data2[$lbl];
    $seriesData3[] = $data3[$lbl];
}

$shorten = $reflector->getMethod('shortenDivisionLabel');
$shorten->setAccessible(true);

$items = [];
foreach ($labels as $lbl) {
    $items[] = [
        'label' => $lbl,
        'short_label' => $shorten->invoke($uiModel, $lbl),
        'total' => $data3[$lbl], // current month total
    ];
}

$monthName = $reflector->getMethod('monthName');
$monthName->setAccessible(true);
$monthShortName = $reflector->getMethod('monthShortName');
$monthShortName->setAccessible(true);

$res = [
    'labels' => $labels,
    'series' => [
        [
            'label' => strtoupper($monthShortName->invoke($uiModel, $mon1)) . ' ' . $y1,
            'color' => '#5B8DEF',
            'data' => $seriesData1,
        ],
        [
            'label' => strtoupper($monthShortName->invoke($uiModel, $mon2)) . ' ' . $y2,
            'color' => '#6FCF97',
            'data' => $seriesData2,
        ],
        [
            'label' => strtoupper($monthShortName->invoke($uiModel, $mon3)) . ' ' . $y3,
            'color' => '#F2A541',
            'data' => $seriesData3,
        ],
    ],
    'items' => $items,
    'total' => array_sum($seriesData3),
    'month' => strtoupper($monthName->invoke($uiModel, $mon3)),
    'month_short' => strtoupper($monthShortName->invoke($uiModel, $mon3)),
    'year' => $y3,
    'period_label' => strtoupper($monthName->invoke($uiModel, $mon3)) . ' ' . $y3,
];

print_r($res);
