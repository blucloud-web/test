<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
