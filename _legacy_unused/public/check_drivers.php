<?php
echo '<pre>';
echo 'extension_loaded(mysqli): ';
var_dump(extension_loaded('mysqli'));

echo 'extension_loaded(pdo_mysql): ';
var_dump(extension_loaded('pdo_mysql'));

echo 'PDO drivers: ';
print_r(PDO::getAvailableDrivers());
echo '</pre>';