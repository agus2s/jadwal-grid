<?php
require __DIR__ . '/config/database.php';
$pdo = db();

$year = $pdo->query('SELECT * FROM school_years WHERE active=1 LIMIT 1')->fetch();
if (!$year) {
    $year = ['name' => 'Belum ditentukan'];
}

$classes = $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
$rows = $pdo->query("SELECT s.*, c.name class_name, t.name teacher_name, sub.name subject_name FROM schedules s JOIN classes c ON c.id = s.class_id JOIN teachers t ON t.id = s.teacher_id JOIN subjects sub ON sub.id = s.subject_id WHERE s.school_year_id = " . (int) ($year['id'] ?? 0) . " ORDER BY s.day, s.jp, c.name")->fetchAll();

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

$days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$header = ['Hari', 'JP', 'Rombel', 'Mata Pelajaran', 'Guru'];

$xmlRows = [];
$xmlRows[] = '<table:table-row>' . ods_cell_text('Tahun Ajaran') . ods_cell_text($year['name']) . '</table:table-row>';
$xmlRows[] = '<table:table-row></table:table-row>';
$xmlRows[] = '<table:table-row>' . implode('', array_map('ods_cell_text', $header)) . '</table:table-row>';

foreach ($rows as $row) {
    $xmlRows[] = '<table:table-row>'
        . ods_cell_text($row['day'])
        . ods_cell_number((int) $row['jp'])
        . ods_cell_text($row['class_name'])
        . ods_cell_text($row['subject_name'])
        . ods_cell_text($row['teacher_name'])
        . '</table:table-row>';
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
      <table:table table:name="Jadwal Pelajaran">
        ' . implode("\n", $xmlRows) . '
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
    <dc:title>Jadwal Pelajaran</dc:title>
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
header('Content-Disposition: attachment; filename="jadwal-pelajaran-' . date('YmdHis') . '.ods"');
header('Content-Length: ' . filesize($zipPath));
header('Pragma: no-cache');
header('Expires: 0');
readfile($zipPath);
unlink($zipPath);
