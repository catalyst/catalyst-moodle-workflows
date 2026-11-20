<?php

const MAX_WHOLE_DIGITS = 20;

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php normalise-mssql-snapshot.php <snapshot.zip> <moodle-dir>\n");
    exit(1);
}

[$script, $snapshot, $moodle] = $argv;

define('MOODLE_INTERNAL', true);
$columninfofile = is_file("$moodle/public/lib/dml/database_column_info.php")
    ? "$moodle/public/lib/dml/database_column_info.php"
    : "$moodle/lib/dml/database_column_info.php";

if (!is_file($columninfofile)) {
    fwrite(STDERR, "Could not locate Moodle's database_column_info.php\n");
    exit(1);
}

require_once($columninfofile);

$zip = new ZipArchive();
if ($zip->open($snapshot) !== true) {
    fwrite(STDERR, "Could not open PHPUnit snapshot: $snapshot\n");
    exit(1);
}

$structureentry = null;
$structurefilename = 'tablestructure.ser';
for ($index = 0; $index < $zip->numFiles; $index++) {
    $name = $zip->getNameIndex($index);
    if (substr($name, -strlen($structurefilename)) === $structurefilename) {
        $structureentry = $name;
        break;
    }
}

if ($structureentry === null) {
    $zip->close();
    fwrite(STDERR, "Could not find tablestructure.ser in PHPUnit snapshot\n");
    exit(1);
}

$serialised = $zip->getFromName($structureentry);
$tablestructure = unserialize($serialised, ['allowed_classes' => [database_column_info::class]]);
if (!is_array($tablestructure)) {
    $zip->close();
    fwrite(STDERR, "Invalid tablestructure.ser in PHPUnit snapshot\n");
    exit(1);
}

$properties = [
    'name', 'type', 'max_length', 'scale', 'not_null', 'primary_key',
    'auto_increment', 'binary', 'has_default', 'default_value',
    'unique', 'meta_type',
];
$normalised = 0;

foreach ($tablestructure as $table => $columns) {
    foreach ($columns as $name => $column) {
        if ($column->meta_type !== 'N') {
            continue;
        }

        $type = strtolower((string) $column->type);
        $decimals = (int) ($column->scale ?: 0);
        $maxlength = (int) ($column->max_length ?: 0);
        $newtype = $type === 'real' ? 'float' : $column->type;
        $newlength = $maxlength > MAX_WHOLE_DIGITS + $decimals
            ? MAX_WHOLE_DIGITS + $decimals
            : $maxlength;

        if ($newtype === $column->type && $newlength === $maxlength) {
            continue;
        }

        // database_column_info is immutable, so rebuild it with normalised metadata.
        $data = new stdClass();
        foreach ($properties as $property) {
            $data->$property = $column->$property;
        }
        $data->type = $newtype;
        $data->max_length = $newlength;
        $tablestructure[$table][$name] = new database_column_info($data);
        $normalised++;
    }
}

if (!$zip->addFromString($structureentry, serialize($tablestructure))) {
    $zip->close();
    fwrite(STDERR, "Could not update tablestructure.ser in PHPUnit snapshot\n");
    exit(1);
}

$zip->close();
echo "Normalised $normalised MSSQL numeric column definitions in $snapshot\n";
