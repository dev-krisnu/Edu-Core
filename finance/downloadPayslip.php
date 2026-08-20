<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/SimplePDF.php';

requireRole(['finance']);
$pdo = getDbConnection();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('Missing payslip id');
}

$stmt = $pdo->prepare("
    SELECT p.*, u.full_name, u.email, u.role
    FROM payroll p
    JOIN users u ON u.id = p.staff_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$slip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slip) {
    http_response_code(404);
    exit('Payslip not found');
}

$monthLabel = (new DateTime($slip['pay_month']))->format('F Y');

$pdf = new SimplePDF();
$pdf->addTitle('EduCore Institute — Pay Slip');
$pdf->addSubtitle('Pay Period: ' . $monthLabel);
$pdf->addSpacer(10);
$pdf->addRule();
$pdf->addSpacer(6);
$pdf->addRow('Employee Name:', $slip['full_name']);
$pdf->addRow('Email:', $slip['email']);
$pdf->addRow('Role:', ucwords(str_replace('_', ' ', $slip['role'])));
$pdf->addSpacer(10);
$pdf->addRule();
$pdf->addSpacer(6);
$pdf->addRow('Basic Pay:', 'Rs. ' . number_format((float) $slip['basic'], 2));
$pdf->addRow('Allowances:', 'Rs. ' . number_format((float) $slip['allowances'], 2));
$pdf->addRow('Deductions:', '- Rs. ' . number_format((float) $slip['deductions'], 2));
$pdf->addSpacer(6);
$pdf->addRule();
$pdf->addSpacer(6);
$pdf->addLine('Net Pay: Rs. ' . number_format((float) $slip['net_pay'], 2), 14, true);
$pdf->addSpacer(20);
$pdf->addLine('This is a system-generated payslip.', 9);

$filename = 'payslip_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $slip['full_name']) . '_' . $slip['pay_month'] . '.pdf';
$pdf->output($filename);
