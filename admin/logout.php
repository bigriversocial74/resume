<?php
require_once __DIR__ . '/../app/bootstrap.php';
logout_user();
redirect_to('/admin/login.php');
