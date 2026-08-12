<?php
require __DIR__ . '/config/database.php';
$pdo = db();

$type = $_GET['type'] ?? 'teacher';

$year = $pdo->query('SELECT * FROM school_years WHERE active=1 LIMIT 1')->fetch();
$classes = $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
$classNames = array_column($classes, 'name');

if (!$year) {
    $year = ['name' => 'Belum ditentukan'];
}

$rows = [];
if ($type === 'subject') {
    $stmt = $pdo->prepare("SELECT s.subject_id, sub.name subject_name, c.name class_name, COUNT(*) AS jp_count FROM schedules s JOIN subjects sub ON sub.id = s.subject_id JOIN classes c ON c.id = s.class_id WHERE s.school_year_id = ? GROUP BY s.subject_id, c.id ORDER BY sub.name, c.name");
    $stmt->execute([$year['id'] ?? 0]);
    foreach ($stmt->fetchAll() as $row) {
        $key = (int) $row['subject_id'];
        if (!isset($rows[$key])) {
            $rows[$key] = ['label' => $row['subject_name'], 'values' => array_fill_keys($classNames, 0), 'total' => 0];
        }
        $rows[$key]['values'][$row['class_name']] = (int) $row['jp_count'];
        $rows[$key]['total'] += (int) $row['jp_count'];
    }
} else {
    $stmt = $pdo->prepare("SELECT s.teacher_id, t.name teacher_name, s.subject_id, sub.name subject_name, c.name class_name, COUNT(*) AS jp_count FROM schedules s JOIN teachers t ON t.id = s.teacher_id JOIN subjects sub ON sub.id = s.subject_id JOIN classes c ON c.id = s.class_id WHERE s.school_year_id = ? GROUP BY s.teacher_id, s.subject_id, c.id ORDER BY t.name, sub.name, c.name");
    $stmt->execute([$year['id'] ?? 0]);
    foreach ($stmt->fetchAll() as $row) {
        $key = $row['teacher_id'] . '|' . $row['subject_id'];
        if (!isset($rows[$key])) {
            $rows[$key] = ['label' => $row['teacher_name'], 'subject' => $row['subject_name'], 'values' => array_fill_keys($classNames, 0), 'total' => 0];
        }
        $rows[$key]['values'][$row['class_name']] = (int) $row['jp_count'];
        $rows[$key]['total'] += (int) $row['jp_count'];
    }
}

function ods_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function ods_cell_text($value): string
{
    return '<table:table-cell office:value-type="string"><text:p>' . ods_escape($value) . '</text:p></table:table-cell>';
}

function ods_cell_number($value): string
{
    return '<table:table-cell office:value-type="float" office:value="' . (float) $value . '"><text:p>' . ods_escape((string) $value) . '</text:p></table:table-cell>';
}

$sheetTitle = $type === 'subject' ? 'Rekap Mapel' : 'Rekap Guru';

$headerCells = [];
if ($type === 'subject') {
    $headerCells[] = ods_cell_text('Mapel');
} else {
    $headerCells[] = ods_cell_text('Guru');
    $headerCells[] = ods_cell_text('Mapel');
}
foreach ($classNames as $className) {
    $headerCells[] = ods_cell_text($className);
}
$headerCells[] = ods_cell_text('Jumlah');

$contentRows = [
    '<table:table-row>' . ods_cell_text('Tahun Ajaran') . ods_cell_text($year['name']) . '</table:table-row>',
    '<table:table-row></table:table-row>',
    '<table:table-row>' . implode('', $headerCells) . '</table:table-row>',
];

foreach ($rows as $row) {
    $cells = [];
    $cells[] = ods_cell_text($row['label']);
    if ($type !== 'subject') {
        $cells[] = ods_cell_text($row['subject']);
    }
    foreach ($classNames as $className) {
        $cells[] = ods_cell_number($row['values'][$className] ?? 0);
    }
    $cells[] = ods_cell_number($row['total']);
    $contentRows[] = '<table:table-row>' . implode('', $cells) . '</table:table-row>';
}

$contentXml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
    office:version="1.2">
  <office:automatic-styles/>
  <office:body>
    <office:spreadsheet>
      <table:table table:name="' . $sheetTitle . '">
        ' . implode("\n", $contentRows) . '
      </table:table>
    </office:spreadsheet>
  </office:body>
</office:document-content>';

$metaXml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    office:version="1.2">
  <office:meta>
    <meta:generator>jadwal-grid</meta:generator>
    <dc:title>' . ods_escape($sheetTitle) . '</dc:title>
  </office:meta>
</office:document-meta>';

$stylesXml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    office:version="1.2">
  <office:styles/>
</office:document-styles>';

$manifestXml = '<?xml version="1.0" encoding="UTF-8"?>
<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.2">
  <manifest:file-entry manifest:media-type="application/vnd.oasis.opendocument.spreadsheet" manifest:full-path="/"/>
  <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="content.xml"/>
  <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="styles.xml"/>
  <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="meta.xml"/>
</manifest:manifest>';

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo 'ZipArchive tidak tersedia pada server PHP ini.';
    exit;
}

$zipPath = tempnam(sys_get_temp_dir(), 'ods_');
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::OVERWRITE);
$zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.spreadsheet');
$zip->addFromString('content.xml', $contentXml);
$zip->addFromString('styles.xml', $stylesXml);
$zip->addFromString('meta.xml', $metaXml);
$zip->addFromString('META-INF/manifest.xml', $manifestXml);
$zip->setCompressionName('mimetype', ZipArchive::CM_STORE);
$zip->close();

header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
header('Content-Disposition: attachment; filename="' . $sheetTitle . '-' . date('YmdHis') . '.ods"');
header('Content-Length: ' . filesize($zipPath));
header('Pragma: no-cache');
header('Expires: 0');
readfile($zipPath);
unlink($zipPath);
