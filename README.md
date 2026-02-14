# QueryCraft 🔍

**A Laravel performance analysis dashboard for detecting N+1 queries, slow queries, missing indexes, and duplicate queries — in real time.**

![Laravel](https://img.shields.io/badge/Laravel-10%2B%20%7C%2011%2B%20%7C%2012%2B-red?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue?style=flat-square&logo=php)
![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)
![Packagist](https://img.shields.io/packagist/v/romdh4ne/laravel-querycraft?style=flat-square)

---

## ✨ Features

- 🔁 **N+1 Detection** — catches repeated query patterns caused by missing eager loading
- 🐢 **Slow Query Detection** — flags queries exceeding your configured time limit
- 🗂 **Missing Index Detection** — identifies full table scans
- 📋 **Duplicate Query Detection** — finds identical queries fired multiple times in one request
- 📍 **Source Location** — shows the exact file and line number in your code that triggered the issue
- 💯 **Performance Score** — grades your endpoint from 0–100 with a letter grade
- 🛠 **Live Config Panel** — toggle detectors and adjust thresholds from the dashboard UI
- 🌙 **Dark Mode** — built-in dark/light mode toggle
- 🚨 **500 Error Inspector** — displays full exception details when your endpoint crashes

---

## 📦 Installation

### 1. Require the package

```bash
composer require romdh4ne/laravel-querycraft
```

### 2. Publish the config file

```bash
php artisan vendor:publish --tag=querycraft-config
```

### 3. Publish the views (optional — only if you want to customize the UI)

```bash
php artisan vendor:publish --tag=querycraft-views
```

### 4. Clear config cache

```bash
php artisan config:clear
```

### 5. Visit the dashboard

```
http://your-app.test/querycraft
```

---

## ⚙️ Configuration

After publishing, a config file is created at `config/querycraft.php`.

You can override all values via your `.env` file:

```env
# Enable or disable the package entirely
QUERY_DEBUGGER_ENABLED=true

# Detectors — enable/disable individually
QUERYCRAFT_DETECTOR_N1=true
QUERYCRAFT_DETECTOR_SLOW_QUERY=true
QUERYCRAFT_DETECTOR_MISSING_INDEX=true
QUERYCRAFT_DETECTOR_DUPLICATE_QUERY=true

# Thresholds
QUERY_DEBUGGER_N1_THRESHOLD=5        # Flag N+1 after this many repetitions
QUERY_DEBUGGER_SLOW_THRESHOLD=100    # Flag queries slower than this (ms)
QUERYCRAFT_DUPLICATE_COUNT=2         # Flag duplicates after this many repeats

# Score weights (must total 100)
QUERYCRAFT_WEIGHT_QUERY_COUNT=40
QUERYCRAFT_WEIGHT_QUERY_TIME=30
QUERYCRAFT_WEIGHT_ISSUES=30

# Dashboard route prefix (default: querycraft)
QUERYCRAFT_DASHBOARD_ROUTE=querycraft
```

### Config file reference

```php
// config/querycraft.php

return [
    'enabled' => env('QUERY_DEBUGGER_ENABLED', true),

    'detectors' => [
        'n1'             => env('QUERYCRAFT_DETECTOR_N1', true),
        'slow_query'     => env('QUERYCRAFT_DETECTOR_SLOW_QUERY', true),
        'missing_index'  => env('QUERYCRAFT_DETECTOR_MISSING_INDEX', true),
        'duplicate_query'=> env('QUERYCRAFT_DETECTOR_DUPLICATE_QUERY', true),
    ],

    'thresholds' => [
        'n1_count'        => env('QUERY_DEBUGGER_N1_THRESHOLD', 5),
        'slow_query_ms'   => env('QUERY_DEBUGGER_SLOW_THRESHOLD', 100),
        'duplicate_count' => env('QUERYCRAFT_DUPLICATE_COUNT', 2),
    ],

    'weights' => [
        'query_count' => env('QUERYCRAFT_WEIGHT_QUERY_COUNT', 40),
        'query_time'  => env('QUERYCRAFT_WEIGHT_QUERY_TIME', 30),
        'issues'      => env('QUERYCRAFT_WEIGHT_ISSUES', 30),
    ],
];
```

> **Tip:** You can also change all settings directly from the dashboard UI using the ⚙️ config panel — changes are written back to your `.env` automatically.

---

## 🖥 Dashboard Usage

### Opening the dashboard

Navigate to:
```
http://your-app.test/querycraft
```

### Analyzing an endpoint

1. Enter your endpoint URL (e.g. `/api/users`)
2. Select the HTTP method (`GET`, `POST`, `PUT`, etc.)
3. Optionally add headers or a request body
4. Click **Analyze Request**

QueryCraft will fire an internal request to your endpoint, collect all queries executed, run them through all detectors, and display the results.

### Reading the results

| Element | Description |
|---|---|
| **Score** | 0–100 performance grade for the endpoint |
| **Issue cards** | Each detected problem with severity, stats, source location and fix suggestion |
| **Source Location** | Exact file path and line number in your code that triggered the query |
| **All Queries** | Collapsible list of every query fired with execution time |

### Using the config panel

Click the ⚙️ icon in the top-right header to open the config panel. From here you can:
- Toggle each detector on/off
- Adjust thresholds using sliders
- Tune score weights
- Save changes (written to `.env`) or reset to defaults

---

## 🔬 How Detectors Work

### 🔁 N+1 Detection

Detects when the same query pattern is executed repeatedly in a loop — a classic sign of missing eager loading.

**Example of a N+1 problem:**
```php
$posts = Post::all();

foreach ($posts as $post) {
    echo $post->user->name; // fires a new query for every post!
}
```

**Fix:**
```php
$posts = Post::with('user')->get();
```

QueryCraft flags this when the same normalized query runs more than `n1_count` times (default: 5).

---

### 🐢 Slow Query Detection

Flags any individual query that takes longer than `slow_query_ms` milliseconds (default: 100ms).

**Common causes:**
- Missing indexes on filtered/sorted columns
- Large unscoped queries (`SELECT *` with no `WHERE`)
- Complex joins without optimization

---

### 🗂 Missing Index Detection

Detects queries that perform full table scans — usually caused by filtering or sorting on unindexed columns.

**Example:**
```php
User::where('email', $email)->first(); // no index on email column
```

**Fix:**
```php
// In a migration
$table->index('email');
```

---

### 📋 Duplicate Query Detection

Catches when the exact same query is fired more than once in a single request — a sign of missing caching or poor query architecture.

**Example:**
```php
$settings = Setting::all();
// ... later in the same request ...
$settings = Setting::all(); // exact duplicate!
```

**Fix:**
```php
$settings = Cache::remember('settings', 3600, fn() => Setting::all());
```

---

## 🚨 500 Error Inspector

When your endpoint throws an exception, QueryCraft catches it and displays:

- The **exception class** (e.g. `ErrorException`, `QueryException`)
- The **error message**
- The **file and line** in your code where it crashed (vendor files filtered out)
- A **stack trace** showing only your app files
- How many **queries were captured** before the crash

> **Tip:** Make sure `APP_DEBUG=true` is set in your `.env` so Laravel returns full exception details as JSON.

---

## 🔒 Security

QueryCraft is intended for **local development only**. It is strongly recommended to disable it in production:

```env
# .env.production
QUERY_DEBUGGER_ENABLED=false
```

---

## 🤝 Contributing

Contributions are welcome! Here's how to get started:

### 1. Fork and clone

```bash
git clone https://github.com/YOUR_USERNAME/laravel-querycraft.git
cd laravel-querycraft
composer install
```

### 2. Create a feature branch

```bash
git checkout -b feat/your-feature-name
```

### 3. Make your changes

- New detectors go in `src/Analyzers/`
- Collector logic is in `src/Collectors/`
- Dashboard views are in `resources/views/`

### 4. Run tests

```bash
composer test
```

### 5. Open a Pull Request

Make sure your PR:
- Has a clear title and description
- Includes tests for new functionality
- Follows PSR-12 coding standards


---

## 📄 License

QueryCraft is open-source software licensed under the [MIT license](LICENSE).

---

## 👨‍💻 Author

Made by [Romdh4ne](https://github.com/Romdh4ne)

If you find this package useful, consider giving it a ⭐ on GitHub!
