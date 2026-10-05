<?php

$database = new PDO('sqlite:'.dirname(__DIR__).'/database/database.sqlite');
$queries = [
    'active_commodities' => 'select count(*) from komoditas_harga where is_active = 1',
    'november_reports' => "select count(*) from laporan_harga where tanggal between '2025-11-01' and '2025-11-30 23:59:59'",
    'november_details' => "select count(*) from laporan_harga_detail d join laporan_harga h on h.id = d.laporan_harga_id where h.tanggal between '2025-11-01' and '2025-11-30 23:59:59'",
    'unavailable_details' => "select count(*) from laporan_harga_detail d join laporan_harga h on h.id = d.laporan_harga_id where h.tanggal between '2025-11-01' and '2025-11-30 23:59:59' and d.harga is null",
];

foreach ($queries as $label => $query) {
    echo $label.'='.$database->query($query)->fetchColumn().PHP_EOL;
}
