<?php
require_once 'bibli_bookshop.php';

session_start();

$redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';

sessionExit($redirectUrl);

