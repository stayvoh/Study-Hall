<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/dashboard">StudyHall</a>
    <a href="/logout" class="btn btn-outline-light btn-sm">Logout</a>
  </div>
</nav>

<section class="py-5 text-center">
  <div class="container">
    <h1 class="display-5 fw-semibold mb-2">Welcome to Study Hall</h1>
    <p class="lead text-muted mb-4">Learn, Collaborate, Build Together</p>

    <form class="d-flex justify-content-center" method="GET" action="/search" style="max-width:720px;margin:0 auto;">
      <input class="form-control form-control-lg me-2" type="search"
             name="q" placeholder="Search discussions, projects, or people" aria-label="Search">
      <button class="btn btn-primary btn-lg" type="submit">Search</button>
    </form>
  </div>
</section>

<div class="container mb-5" style="max-width: 900px;">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Activity Feed</h4>

    <!-- Toggle: All vs Following -->
    <ul class="nav nav-pills">
      <li class="nav-item">
        <a class="nav-link active" href="/dashboard?feed=all">All</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/dashboard?feed=following">Following</a>
      </li>
    </ul>
  </div>

  <div class="list-group shadow-sm">
    <!-- placeholders; we’ll wire real data next -->
    <a href="#" class="list-group-item list-group-item-action">
      <div class="d-flex w-100 justify-content-between">
        <h6 class="mb-1">Question on Lecture 3</h6>
        <small class="text-muted">1 hour ago</small>
      </div>
      <p class="mb-1 text-muted">Why does this work?</p>
    </a>
    <a href="#" class="list-group-item list-group-item-action">
      <div class="d-flex w-100 justify-content-between">
        <h6 class="mb-1">Project Idea</h6>
        <small class="text-muted">1 day ago</small>
      </div>
      <p class="mb-1 text-muted">Looking for teammate skilled in JS.</p>
    </a>
    <a href="#" class="list-group-item list-group-item-action">
      <div class="d-flex w-100 justify-content-between">
        <h6 class="mb-1">Lost and Found Web App</h6>
        <small class="text-muted">1 week ago</small>
      </div>
      <p class="mb-1 text-muted">New updates pushed to repo.</p>
    </a>
  </div>
</div>

</body>
</html>
