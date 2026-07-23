<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Omahe Kak Jum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #43515e;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-card { 
            max-width: 600px; 
            margin: auto; 
            margin-top: 50px; 
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: bold;
            color: #ddc211 !important;
        }
        .content {
            flex: 1;
            padding-bottom: 30px;
        }
        footer {
            background-color: #1668bb;
            color: white;
            text-align: center;
            padding: 15px 0;
            margin-top: auto;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-secondary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">UMAHE KAK JUM</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard_tenant.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="bayaran.php">Sewaan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="content container">