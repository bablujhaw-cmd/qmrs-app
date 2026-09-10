<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
$u=find_user_by_id(current_user_id());
if(!$u||$u['ACCOUNT_STATUS']!=='ACTIVE')logout_user();
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>QMRS Dashboard</title><link rel="stylesheet" href="/assets/css/common.css"></head><body><main class="page"><section class="card"><span class="eyebrow">ACCOUNT ACTIVE</span><h1>Welcome, <?=htmlspecialchars($u['FULL_NAME'],ENT_QUOTES,'UTF-8')?></h1><p class="intro">Account type: <?=htmlspecialchars($u['USER_TYPE'],ENT_QUOTES,'UTF-8')?></p><form method="post" action="/api/auth/logout.php"><button class="submit">LOG OUT</button></form></section></main></body></html>
