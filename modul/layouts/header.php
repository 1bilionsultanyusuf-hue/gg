<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Prototype</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome CDN -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style/css.css" />

    <style>
        /* Sidebar transition */
        .sidebar {
            transition: all 0.3s;
        }

        /* Collapsed sidebar */
        .sidebar.collapsed {
            width: 5rem !important;
        }

        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .logo-text {
            display: none;
        }

        /* Content margin adjustment when sidebar collapsed */
        .sidebar.collapsed ~ .content {
            margin-left: 5rem;
        }

        /* Content transition */
        .content {
            transition: all 0.3s;
        }

        /* Menu item hover */
        .menu-item:hover {
            background-color: #e0e7ff;
        }
    </style>
</head>

<body class="bg-gray-100">