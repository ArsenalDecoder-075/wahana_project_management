<?php
$input = fopen("users.csv", "r");
$output = fopen("users_hashed.csv", "w");

$header = fgetcsv($input);
fputcsv($output, $header);

while (($row = fgetcsv($input)) !== FALSE) {
    // Asumsi kolom password ada di index ke-5 (ubah sesuai CSV kamu)
    $row[5] = password_hash($row[5], PASSWORD_BCRYPT);
    fputcsv($output, $row);
}

fclose($input);
fclose($output);

echo "Done! File baru: users_hashed.csv\n";
