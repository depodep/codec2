<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Login - SIATRACK'); ?></title>

    <!-- Tailwind CSS CDN & FontAwesome -->
    <!-- ADD THIS -->
<?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Global Custom CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('css/global.css')); ?>">

    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="bg-[#d1d5db] min-h-screen flex items-center justify-center p-4 lg:p-8">
    <?php echo $__env->yieldContent('content'); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views/layouts/guest.blade.php ENDPATH**/ ?>