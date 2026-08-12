<?php
require_once __DIR__ . '/../config/database.php';

class FeeController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getTemplates(): array
    {
        return $this->db->query('SELECT * FROM fee_templates ORDER BY category')->fetchAll();
    }

    public function getInvoices(?int $studentId = null): array
    {
        $sql = 'SELECT fi.*, ft.name AS template_name, ft.category, u.full_name AS student_name
                FROM fee_invoices fi
                LEFT JOIN fee_templates ft ON fi.template_id = ft.id
                LEFT JOIN users u ON fi.student_id = u.id';
        if ($studentId) {
            $stmt = $this->db->prepare($sql . ' WHERE fi.student_id = ? ORDER BY fi.created_at DESC');
            $stmt->execute([$studentId]);
            return $stmt->fetchAll();
        }
        return $this->db->query($sql . ' ORDER BY fi.created_at DESC')->fetchAll();
    }

    public function getFinancialSummary(): array
    {
        $total = $this->db->query('SELECT COALESCE(SUM(amount), 0) AS total FROM fee_invoices')->fetch();
        $collected = $this->db->query("SELECT COALESCE(SUM(amount), 0) AS total FROM fee_invoices WHERE status = 'paid'")->fetch();
        $pending = $this->db->query("SELECT COALESCE(SUM(amount), 0) AS total FROM fee_invoices WHERE status = 'pending'")->fetch();
        $overdue = $this->db->query("SELECT COALESCE(SUM(amount + penalty), 0) AS total FROM fee_invoices WHERE status = 'overdue'")->fetch();

        return [
            'total_billed' => (float) $total['total'],
            'collected' => (float) $collected['total'],
            'pending' => (float) $pending['total'],
            'overdue' => (float) $overdue['total'],
            'collection_rate' => $total['total'] > 0 ? round(($collected['total'] / $total['total']) * 100, 1) : 0
        ];
    }

    public function markAsPaid(int $invoiceId): bool
    {
        $stmt = $this->db->prepare("UPDATE fee_invoices SET status = 'paid', paid_at = NOW() WHERE id = ?");
        return $stmt->execute([$invoiceId]);
    }

    public function calculatePenalty(int $invoiceId): float
    {
        $stmt = $this->db->prepare(
            'SELECT fi.amount, ft.penalty_percent, ft.due_date
             FROM fee_invoices fi
             JOIN fee_templates ft ON fi.template_id = ft.id
             WHERE fi.id = ?'
        );
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch();
        if (!$invoice || !$invoice['due_date'] || $invoice['due_date'] >= date('Y-m-d')) {
            return 0;
        }
        return round($invoice['amount'] * ($invoice['penalty_percent'] / 100), 2);
    }
}
