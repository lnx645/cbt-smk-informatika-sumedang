---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Use CASE expression for ordered day names in PostgreSQL
When ordering by a 'hari' (day name) column in PostgreSQL, use a CASE expression instead of MySQL's FIELD() function, which doesn't exist in PostgreSQL. Example: orderByRaw("CASE WHEN hari = 'Senin' THEN 1 ... END").

## Use App\Support\Toast for flash messages
Use Toast::success() / Toast::error() / Toast::warning() / Toast::info() instead of Inertia::flash('toast', [...]). Return Redirect::back() after flashing Toast (not Redirect::route).
