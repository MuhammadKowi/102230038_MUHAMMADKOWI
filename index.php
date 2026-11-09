<?php
require __DIR__ . '/config.php';
if (!empty($_SESSION['user'])) redirect(($_SESSION['user']['role'] ?? '') === 'admin' ? 'admin.php' : 'student.php');
redirect('login.php');
