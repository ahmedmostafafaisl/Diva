# Diva Backend

Laravel API for the Diva lenses store (mobile app + website).
Products are loaded from the Diva WordPress / WooCommerce site.

## Requirements

- PHP 8.1+
- Composer
- MySQL

## Setup

```bash
composer install
cp .env.example .env   # or create .env (see variables below)
php artisan key:generate
php artisan migrate
php artisan serve
```

## Main environment variables

| Variable | Purpose |
|---|---|
| `WP_API_BASE` | Old WordPress API (`/wp-json/procontent/v1`), used for single products |
| `WP_COMMERCE_API_BASE` | New products API (`https://diva.sa/wp-json/diva-commerce/v1`) |
| `WP_COMMERCE_CATEGORY_PARAM` | Query parameter the products API uses for the category (default `category`) |
| `WP_COMMERCE_CACHE_TTL` | Seconds to cache WordPress product lists (default `300`) |
| `DIVA_MONTH_DISCOUNT` | Discount % for "Products of the Month" (default `20`, empty = any discount) |
| `DIVA_MONTH_PRODUCTS_LIMIT` / `DIVA_POPULAR_PRODUCTS_LIMIT` | Section sizes (default `6`) |
| `AWS_BUCKET`, `AWS_DEFAULT_REGION` | S3 storage |
| `TABBY_SECRET_KEY`, `TABBY_MERCHANT_URLS_BASE` | Tabby payments |
| `TAMARA_BASE_URL`, `TAMARA_TOKEN` | Tamara payments |
| `PAYMENT_PLATFORM_NAME` | Platform name sent to Tabby / Tamara (default `Diva`) |

## Useful endpoints

- `GET /api/ref/subcategories/{id}/products`: products of a subcategory (paginated)
- `GET /api/ref/subcategories/{id}/collections`: products grouped into Products of the Month, Most Popular, Her Diva, Chic Diva, Miss Diva and Diva
- `GET /api/ref/products/{id}/sync`: a single product, synced from WordPress
