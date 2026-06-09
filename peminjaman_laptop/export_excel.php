<?php
require 'vendor/autoload.php';
include 'koneksi.php';
session_start();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Cek apakah user adalah admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Membuat Spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Peminjaman');

// Header Kolom
$header = ['No', 'Nama Barang', 'Merk Barang', 'Nama Peminjam', 'Tanggal Peminjaman', 'Bukti Peminjaman', 'Tanggal Pengembalian', 'Bukti Pengembalian', 'Status'];
$sheet->fromArray($header, NULL, 'A1');

// Style Header
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 12
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4a90e2']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
];
$sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

// Ambil Data dari Database
$query = "SELECT * FROM peminjaman";
$result = $conn->query($query);
$rowNum = 2;
$no = 1;

while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A'.$rowNum, $no++);
    $sheet->setCellValue('B'.$rowNum, $row['nama_barang']);
    $sheet->setCellValue('C'.$rowNum, $row['merk_barang']);
    $sheet->setCellValue('D'.$rowNum, $row['nama_peminjam']);
    $sheet->setCellValue('E'.$rowNum, $row['tanggal_peminjaman']);
    $sheet->setCellValue('G'.$rowNum, $row['tanggal_pengembalian']);

    $status = $row['tanggal_pengembalian'] ? "Sudah Dikembalikan" : "Belum Dikembalikan";
    $sheet->setCellValue('I'.$rowNum, $status);

    // Gambar Bukti Peminjaman
    if (!empty($row['bukti_peminjaman']) && file_exists('uploads/'.$row['bukti_peminjaman'])) {
        $drawingPeminjaman = new Drawing();
        $drawingPeminjaman->setName('Bukti Peminjaman');
        $drawingPeminjaman->setDescription('Bukti Peminjaman');
        $drawingPeminjaman->setPath('uploads/'.$row['bukti_peminjaman']);
        $drawingPeminjaman->setHeight(80);
        $drawingPeminjaman->setCoordinates('F'.$rowNum);
        $drawingPeminjaman->setOffsetX(10);
        $drawingPeminjaman->setOffsetY(10);
        $drawingPeminjaman->setWorksheet($sheet);
    } else {
        $sheet->setCellValue('F'.$rowNum, 'Tidak Ada Gambar');
    }

    // Gambar Bukti Pengembalian
    if (!empty($row['bukti_pengembalian']) && file_exists('uploads/'.$row['bukti_pengembalian'])) {
        $drawingPengembalian = new Drawing();
        $drawingPengembalian->setName('Bukti Pengembalian');
        $drawingPengembalian->setDescription('Bukti Pengembalian');
        $drawingPengembalian->setPath('uploads/'.$row['bukti_pengembalian']);
        $drawingPengembalian->setHeight(80);
        $drawingPengembalian->setCoordinates('H'.$rowNum);
        $drawingPengembalian->setOffsetX(10);
        $drawingPengembalian->setOffsetY(10);
        $drawingPengembalian->setWorksheet($sheet);
    } else {
        $sheet->setCellValue('H'.$rowNum, 'Tidak Ada Gambar');
    }

    // Warna Alternating Rows
    if ($rowNum % 2 == 0) {
        $sheet->getStyle('A'.$rowNum.':I'.$rowNum)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'f2f2f2']
            ]
        ]);
    }

    // Border pada tiap sel
    $sheet->getStyle('A'.$rowNum.':I'.$rowNum)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '2A3A4F']
            ]
        ]
    ]);

    // Tinggi baris
    $sheet->getRowDimension($rowNum)->setRowHeight(85);

    $rowNum++;
}

// Text Alignment untuk semua baris
$sheet->getStyle('A2:I'.$rowNum)->applyFromArray([
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ]
]);

// Auto-size untuk semua kolom
foreach (range('A', 'I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Nama file Excel
$filename = 'Data_Peminjaman_'.date('Ymd_His').'.xlsx';

// Output ke Browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
