<?php

function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function canonical(){
    global $route_slug, $page, $userid;
    $canonical=BASE_URL;
    if(!empty($route_slug)) $canonical.=$route_slug."/";
    if(!empty($page)) $canonical.=$page."/";
    if(!empty($userid)) $canonical.=$userid;
    return $canonical;
}

function version(){
    $version="A4";
    $version=time();
    return $version;
}
?>

<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml" xmlns:og="http://opengraphprotocol.org/schema/" xmlns:fb="http://www.facebook.com/2008/fbml">
<head profile="http://gmpg.org/xfn/11">
    <title>Geogram - <?= e($pagename) ?></title>
    <base href="/">
    <link rel="shortcut icon" href="/favicon.ico" >
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, viewport-fit=cover">

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="canonical" href="<?= canonical() ?>">

    <meta name="keywords" content="Tracker, GPS, GPX, Garmin, Spot, Trail, Bikepacking, Bike">
    <meta name="description" content="<?= e(DESCRIPTION) ?>">
    <meta name="author" content="<?= e(AUTHOR) ?>">

    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Geogram">
    <link rel="manifest" href="/manifest.json">

    <meta property="og:description" content="<?= e(DESCRIPTION) ?>">
    <meta property="og:url" content="<?= canonical() ?>">
    <meta property="og:site_name" content="Geogram">
    <meta property="article:publisher" content="https://www.facebook.com/ThierryCrouzetAuteur/">
    <meta property="article:author" content="https://www.facebook.com/ThierryCrouzetAuteur/">

    <?php if(isset($route["creationdate"])): ?>
        <meta property="article:published_time" content="<?= date('Y-m-d\TH:i:s\+00:00', strtotime($route["creationdate"])) ?>">
    <?php else: ?>
        <meta property="article:published_time" content="2024-05-02T15:04:27+00:00">
    <?php endif; ?>
    <meta property="article:modified_time" content="<?= date('Y-m-d\TH:i:s\+00:00', time()) ?>">

    <?php if(isset($route["photolog"])): ?>
        <meta property="og:image" content="<?= $route["photolog"] ?>">
        <meta property="og:image:width" content="640">
        <meta property="og:image:height" content="640">
    <?php else: ?>
        <meta property="og:image" content="<?= BASE_URL ?>assets/img/geogram-logo.svg">
        <meta property="og:image:width" content="768">
        <meta property="og:image:height" content="768">
    <?php endif; ?>
    <meta property="og:image:type" content="image/jpeg">
    <meta name="author" content="<?= e(AUTHOR) ?>">
    <meta name="twitter:card" content="summary_large_image">

    <?php if($OnMap): ?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdn.jsdelivr.net/npm/exif-js"></script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="/assets/js/apiService.js"></script>

    <link rel='stylesheet' id='style-css' href='assets/css/styles.css?<?= version() ?>' type='text/css' media='screen' />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
</head>
<body>
