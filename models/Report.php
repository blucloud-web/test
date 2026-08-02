<?php
/**
 * Family Banking System - Report Model
 */

if (!defined('APP_INIT')) die('Direct access not permitted');

class Report {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getDashboardStats() {
        $userModel = new User();
        $accountModel = new Account();
        $loanModel = new Loan();
        $depositModel = new Deposit();
        $fundModel = new CentralFund();

        return [
            'total_users' => $userModel->countAll(),
            'active_accounts' => $accountModel->countActive(),
            'total_bank_balance' => $accountModel->getTotalBalance(),
            'central_fund_balance' => $fundModel->getBalance(),
            'active_loans_total' => $loanModel->getTotalActiveLoansAmount(),
            'active_deposits_total' => $depositModel->getTotalPrincipal()
        ];
    }

    public function getMonthlyTransactionChartData() {
        // گزارش تراکنش‌های ۶ ماه گذشته برای نمودار
        $labels = [];
        $deposits = [];
        $withdrawals = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $labels[] = $month;

            $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM transactions WHERE status = 'completed' AND type IN ('deposit', 'interest_payment') AND strftime('%Y-%m', created_at) = ?");
            if (DB_TYPE === 'mysql') {
                $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM transactions WHERE status = 'completed' AND type IN ('deposit', 'interest_payment') AND DATE_FORMAT(created_at, '%Y-%m') = ?");
            }
            $stmt->execute([$month]);
            $d = $stmt->fetch();
            $deposits[] = (float)($d['total'] ?? 0);

            $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM transactions WHERE status = 'completed' AND type = 'withdrawal' AND strftime('%Y-%m', created_at) = ?");
            if (DB_TYPE === 'mysql') {
                $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM transactions WHERE status = 'completed' AND type = 'withdrawal' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
            }
            $stmt->execute([$month]);
            $w = $stmt->fetch();
            $withdrawals[] = (float)($w['total'] ?? 0);
        }

        return [
            'labels' => $labels,
            'deposits' => $deposits,
            'withdrawals' => $withdrawals
        ];
    }
}
