<?php
/**
 * Global Header Component
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$siteName = get_setting('site_name', 'MukeshSahu');
$pageTitle = isset($pageTitle) ? e($pageTitle) . ' | ' . e($siteName) : e(get_setting('seo_meta_title', 'MukeshSahu – Personal Learning & Technology Platform'));
$metaDesc = isset($metaDesc) ? e($metaDesc) : e(get_setting('seo_meta_description', 'Explore IT Opportunities, Learn New Technologies, Discover AI Tools, and Stay Updated.'));
$metaKeywords = get_setting('seo_meta_keywords', 'Mukesh Sahu, IT Jobs, Courses, AI Tools, Technology Blogs');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <!-- Meta tags for SEO & Social -->
    <meta name="description" content="<?= $metaDesc ?>">
    <meta name="keywords" content="<?= e($metaKeywords) ?>">
    <meta name="author" content="Mukesh Sahu">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= APP_URL ?>">
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= $metaDesc ?>">
    <meta property="og:image" content="<?= ASSETS_URL ?>/images/profile-mukesh.png">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.5.2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=<?= APP_VERSION ?>">

    <!-- Global JS Config -->
    <script>
        window.APP_URL = "<?= APP_URL ?>";
    </script>
</head>
<body class="d-flex flex-column min-vh-100">
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="flex-grow-1">
<?= display_flash(); ?>
