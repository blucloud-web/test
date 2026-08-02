-- ============================================================
-- سیستم بانکداری خانوادگی (Family Banking System)
-- ساختار کامل دیتابیس MySQL به همراه داده‌های اولیه (Seed Data)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `system_logs`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `central_fund_logs`;
DROP TABLE IF EXISTS `loan_installments`;
DROP TABLE IF EXISTS `loans`;
DROP TABLE IF EXISTS `deposit_interest_logs`;
DROP TABLE IF EXISTS `deposits`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `bank_accounts`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. جدول کاربران (users)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NULL,
  `role` ENUM('admin', 'accountant', 'member', 'readonly') NOT NULL DEFAULT 'member',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `mobile` VARCHAR(20) NULL,
  `national_id` VARCHAR(20) NULL,
  `address` TEXT NULL,
  `avatar` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. جدول حساب‌های بانکی (bank_accounts)
CREATE TABLE `bank_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `account_number` VARCHAR(30) NOT NULL UNIQUE,
  `account_type` ENUM('current', 'qard', 'short_term', 'long_term') NOT NULL DEFAULT 'current',
  `balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active', 'blocked', 'closed') NOT NULL DEFAULT 'active',
  `open_date` DATE NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_user_accounts` (`user_id`),
  INDEX `idx_account_number` (`account_number`),
  INDEX `idx_account_type` (`account_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. جدول تراکنش‌ها (transactions)
CREATE TABLE `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tracking_code` VARCHAR(40) NOT NULL UNIQUE,
  `source_account_id` INT NULL,
  `dest_account_id` INT NULL,
  `user_id` INT NOT NULL,
  `type` ENUM('deposit', 'withdrawal', 'transfer', 'member_transfer', 'admin_adjustment', 'interest_payment', 'loan_disbursement', 'loan_repayment') NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `category` VARCHAR(50) NULL DEFAULT 'عمومی',
  `status` ENUM('completed', 'pending', 'cancelled') NOT NULL DEFAULT 'completed',
  `description` TEXT NULL,
  `attachment` VARCHAR(255) NULL,
  `created_by` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`source_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`dest_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_tracking` (`tracking_code`),
  INDEX `idx_trans_type` (`type`),
  INDEX `idx_trans_user` (`user_id`),
  INDEX `idx_trans_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. جدول سپرده‌ها (deposits)
CREATE TABLE `deposits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `account_id` INT NOT NULL,
  `deposit_number` VARCHAR(30) NOT NULL UNIQUE,
  `principal_amount` DECIMAL(15,2) NOT NULL,
  `interest_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `term_months` INT NOT NULL DEFAULT 12,
  `auto_renew` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('active', 'matured', 'cancelled') NOT NULL DEFAULT 'active',
  `open_date` DATE NOT NULL,
  `maturity_date` DATE NOT NULL,
  `total_interest_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  INDEX `idx_deposit_user` (`user_id`),
  INDEX `idx_deposit_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. جدول لاگ پرداخت سود سپرده (deposit_interest_logs)
CREATE TABLE `deposit_interest_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `deposit_id` INT NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`deposit_id`) REFERENCES `deposits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. جدول وام‌ها (loans)
CREATE TABLE `loans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `loan_number` VARCHAR(30) NOT NULL UNIQUE,
  `amount` DECIMAL(15,2) NOT NULL,
  `interest_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `term_months` INT NOT NULL DEFAULT 12,
  `monthly_installment` DECIMAL(15,2) NOT NULL,
  `total_repayment` DECIMAL(15,2) NOT NULL,
  `guarantor_user_id` INT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'active', 'settled') NOT NULL DEFAULT 'pending',
  `request_date` DATE NOT NULL,
  `approval_date` DATE NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`guarantor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_loan_user` (`user_id`),
  INDEX `idx_loan_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. جدول اقساط وام (loan_installments)
CREATE TABLE `loan_installments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `loan_id` INT NOT NULL,
  `installment_number` INT NOT NULL,
  `due_date` DATE NOT NULL,
  `principal_amount` DECIMAL(15,2) NOT NULL,
  `interest_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `penalty_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `status` ENUM('pending', 'paid', 'overdue') NOT NULL DEFAULT 'pending',
  `payment_date` DATE NULL,
  `transaction_id` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL,
  INDEX `idx_inst_loan` (`loan_id`),
  INDEX `idx_inst_due` (`due_date`),
  INDEX `idx_inst_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. جدول گردش صندوق مرکزی (central_fund_logs)
CREATE TABLE `central_fund_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` ENUM('income', 'expense', 'capital_injection') NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `created_by` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_fund_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. جدول اعلان‌ها (notifications)
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'warning', 'success', 'danger') NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `link` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_notif_user` (`user_id`),
  INDEX `idx_notif_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. جدول تنظیمات سیستم (system_settings)
CREATE TABLE `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `description` VARCHAR(255) NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. جدول لاگ‌های سیستم (system_logs)
CREATE TABLE `system_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_log_module` (`module`),
  INDEX `idx_log_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- داده‌های اولیه (Seed Data)
-- رمز تمام کاربران اولیه: 123456
-- (پسورد hash شده با bcrypt): $2y$10$e0MYzXyjpJS7Pd0RVvHwHe1q.zH1sJ9XhFfUvC/e9D2xW8vBwO5Sm
-- ============================================================

INSERT INTO `users` (`id`, `full_name`, `username`, `password`, `email`, `role`, `status`, `mobile`, `national_id`, `address`, `notes`) VALUES
(1, 'محمد حسینی (مدیر کل)', 'admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1q.zH1sJ9XhFfUvC/e9D2xW8vBwO5Sm', 'admin@familybank.local', 'admin', 'active', '09121111111', '0012345678', 'تهران، خیابان آزادی، پلاک ۱۰', 'مدیر ارشد صندوق و سیستم بانکداری خانوادگی'),
(2, 'رضا حسینی (حسابدار)', 'accountant', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1q.zH1sJ9XhFfUvC/e9D2xW8vBwO5Sm', 'acc@familybank.local', 'accountant', 'active', '09122222222', '0023456789', 'تهران، خیابان ولیعصر، پلاک ۲۵', 'حسابدار رسمی صندوق خانوادگی'),
(3, 'سارا حسینی (عضو خانواده)', 'sara', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1q.zH1sJ9XhFfUvC/e9D2xW8vBwO5Sm', 'sara@familybank.local', 'member', 'active', '09123333333', '0034567890', 'اصفهان، خیابان چهارباغ، پلاک ۱2', 'عضو فعال خانواده'),
(4, 'علی حسینی (عضو خانواده)', 'ali', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1q.zH1sJ9XhFfUvC/e9D2xW8vBwO5Sm', 'ali@familybank.local', 'member', 'active', '09124444444', '0045678901', 'مشهد، خیابان احمدآباد، پلاک ۵', 'عضو خانواده'),
(5, 'مریم حسینی (بازرس / فقط خواندنی)', 'viewer', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1q.zH1sJ9XhFfUvC/e9D2xW8vBwO5Sm', 'viewer@familybank.local', 'readonly', 'active', '09125555555', '0056789012', 'شیراز، خیابان زند، پلاک ۱8', 'دسترسی مشاهده گزارش‌ها و آمار');

-- تنظیمات اولیه سیستم
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('default_loan_interest_rate', '4.00', 'نرخ کارمزد/سود وام‌ها (درصد)'),
('default_deposit_interest_rate', '18.00', 'نرخ سود سپرده‌های سرمایه‌گذاری (درصد سالانه)'),
('late_penalty_rate', '1.50', 'نرخ جریمه دیرکرد اقساط (درصد ماهانه)'),
('central_fund_balance', '500000000.00', 'موجودی کل صندوق مرکزی خانواده (تومان)'),
('bank_name', 'صندوق بانکداری خانوادگی حسینی', 'نام صندوق / سیستم بانکداری'),
('currency', 'تومان', 'واحد پول سیستم');

-- حساب‌های اولیه اعضا
INSERT INTO `bank_accounts` (`id`, `user_id`, `account_number`, `account_type`, `balance`, `status`, `open_date`, `description`) VALUES
(1, 1, 'ACC-1001-8842', 'current', 125000000.00, 'active', '2025-01-01', 'حساب جاری اصلی مدیر'),
(2, 2, 'ACC-1002-3109', 'current', 45000000.00, 'active', '2025-01-05', 'حساب جاری حسابدار'),
(3, 3, 'ACC-1003-9214', 'short_term', 80000000.00, 'active', '2025-01-10', 'حساب کوتاه‌مدت پس‌انداز سارا'),
(4, 3, 'ACC-1003-4410', 'current', 15000000.00, 'active', '2025-01-12', 'حساب جاری روزمره سارا'),
(5, 4, 'ACC-1004-7712', 'long_term', 60000000.00, 'active', '2025-02-01', 'حساب سرمایه‌گذاری بلندمدت علی');

-- تراکنش‌های اولیه نمونه
INSERT INTO `transactions` (`id`, `tracking_code`, `source_account_id`, `dest_account_id`, `user_id`, `type`, `amount`, `category`, `status`, `description`, `created_by`, `created_at`) VALUES
(1, 'TRX-20260101-9981', NULL, 1, 1, 'deposit', 100000000.00, 'سرمایه اولیه', 'completed', 'واریز اولیه سرمایه به حساب مدیر', 1, '2025-01-01 10:00:00'),
(2, 'TRX-20260105-4412', 1, 2, 2, 'transfer', 45000000.00, 'انتقال داخلی', 'completed', 'انتقال بودجه جاری به حسابدار', 1, '2025-01-05 14:30:00'),
(3, 'TRX-20260110-1049', 1, 3, 3, 'member_transfer', 80000000.00, 'پس‌انداز خانوادگی', 'completed', 'انتقال اعتبار از مدیر به سارا', 1, '2025-01-10 11:15:00'),
(4, 'TRX-20260201-8831', NULL, 5, 4, 'deposit', 60000000.00, 'واریز پس‌انداز', 'completed', 'افتتاح پس‌انداز بلندمدت توسط علی', 4, '2025-02-01 09:00:00');

-- سپرده نمونه
INSERT INTO `deposits` (`id`, `user_id`, `account_id`, `deposit_number`, `principal_amount`, `interest_rate`, `term_months`, `auto_renew`, `status`, `open_date`, `maturity_date`, `total_interest_paid`) VALUES
(1, 3, 3, 'DEP-9001-3312', 50000000.00, 18.00, 12, 1, 'active', '2025-01-15', '2026-01-15', 4500000.00);

-- وام نمونه
INSERT INTO `loans` (`id`, `user_id`, `loan_number`, `amount`, `interest_rate`, `term_months`, `monthly_installment`, `total_repayment`, `guarantor_user_id`, `status`, `request_date`, `approval_date`, `notes`) VALUES
(1, 4, 'LON-7001-5521', 30000000.00, 4.00, 10, 3100000.00, 31000000.00, 1, 'active', '2025-02-05', '2025-02-06', 'وام خرید تجهیزات با تایید و ضمانت مدیر');

-- اقساط وام نمونه
INSERT INTO `loan_installments` (`id`, `loan_id`, `installment_number`, `due_date`, `principal_amount`, `interest_amount`, `penalty_amount`, `total_amount`, `status`, `payment_date`, `transaction_id`) VALUES
(1, 1, 1, '2025-03-05', 3000000.00, 100000.00, 0.00, 3100000.00, 'paid', '2025-03-04', NULL),
(2, 1, 2, '2025-04-05', 3000000.00, 100000.00, 0.00, 3100000.00, 'paid', '2025-04-05', NULL),
(3, 1, 3, '2025-05-05', 3000000.00, 100000.00, 0.00, 3100000.00, 'pending', NULL, NULL),
(4, 1, 4, '2025-06-05', 3000000.00, 100000.00, 0.00, 3100000.00, 'pending', NULL, NULL);

-- ثبت اولیه گردش صندوق
INSERT INTO `central_fund_logs` (`id`, `type`, `amount`, `title`, `description`, `created_by`, `created_at`) VALUES
(1, 'capital_injection', 500000000.00, 'سرمایه اولیه صندوق مرکزی', 'تامین اولیه سرمایه صندوق توسط هیئت اعضا', 1, '2025-01-01 08:00:00');

-- اعلان اولیه
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `link`) VALUES
(1, 4, 'خوش‌آمدگویی', 'به سیستم بانکداری خانوادگی خوش آمدید. حساب شما فعال است.', 'info', 0, 'dashboard.php'),
(2, 4, 'تایید درخواست وام', 'درخواست وام شماره LON-7001-5521 با موفقیت تایید و پرداخت گردید.', 'success', 0, 'loans.php');
