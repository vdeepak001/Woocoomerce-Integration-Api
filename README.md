# WooCommerce Integration API

A Laravel-based REST API wrapper for WooCommerce that provides seamless product and category management through clean, easy-to-use endpoints.

## 📋 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [API Endpoints](#-api-endpoints)
- [Usage Examples](#-usage-examples)
- [Technology Stack](#-technology-stack)
- [Troubleshooting](#-troubleshooting)

## ✨ Features

- **Product Management**: Full CRUD operations for WooCommerce products
- **WooCommerce ID-Based Endpoints**: All product endpoints use WooCommerce product IDs for consistency
- **Dual Data Source**: Fetch data from local database (fast) or live WooCommerce API (real-time)
- **Background Queue Processing**: Asynchronous product creation and updates with retry logic
- **Pagination & Search**: Efficient product listing with filtering capabilities
- **Product Synchronization**: Bulk sync all WooCommerce products to local database
- **Category Management**: List and manage product categories
- **Batch Operations**: Perform bulk operations on multiple products
- **RESTful API**: Clean and intuitive API endpoints
- **Error Handling**: Comprehensive error handling and validation
- **Laravel Integration**: Built on Laravel 12 for modern PHP development

## 🔧 Requirements

- **PHP**: ^8.2
- **Composer**: Latest version
- **Laravel**: ^12.0
- **WooCommerce Store**: Active WooCommerce installation with REST API enabled

## 📦 Installation

Follow these steps to set up the project on your local machine:

### 1. Install Dependencies

```bash
composer install
```

### 2. Environment Setup

Copy the example environment file:

```bash
cp .env.example .env
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Configure WooCommerce Credentials

Add the following to your `.env` file:

```env
WOOCOMMERCE_STORE_URL=https://your-store.com
WOOCOMMERCE_CONSUMER_KEY=your_consumer_key_here
WOOCOMMERCE_CONSUMER_SECRET=your_consumer_secret_here
```

### 5. Run Database Migrations

```bash
php artisan migrate
```

### 6. Start Queue Worker (Required for Background Jobs)

```bash
php artisan queue:work
```

### 7. Start the Development Server

```bash
php artisan serve
```

Your API will be available at `http://localhost:8000`

## 🔑 Configuration

### Getting WooCommerce API Keys

1. Log in to your **WooCommerce store admin panel**
2. Navigate to **WooCommerce → Settings → Advanced → REST API**
3. Click **Add Key** to create new API credentials
4. Set the following:
   - **Description**: Laravel Integration API
   - **User**: Select your admin user
   - **Permissions**: Read/Write
5. Click **Generate API Key**
6. Copy the **Consumer Key** and **Consumer Secret**
7. Add them to your `.env` file

### WooCommerce Package

This project uses the **Automattic WooCommerce REST API PHP Client** for seamless communication with WooCommerce stores.

- **Package**: `automattic/woocommerce` (version ^3.1)
- **Purpose**: Provides a simple and reliable way to access the WooCommerce REST API
- **Documentation**: [WooCommerce REST API Docs](https://woocommerce.github.io/woocommerce-rest-api-docs/)

## 🚀 API Endpoints

All endpoints are prefixed with `/api/woocommerce`

### Products

> **Important**: All product endpoints use **WooCommerce product IDs**, not local database IDs.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/products` | List all products (supports `?source=local` or `?source=live`) |
| `GET` | `/products/{woocommerce_id}` | Get a single product by WooCommerce ID |
| `POST` | `/products` | Create a new product |
| `PUT` | `/products/{woocommerce_id}` | Update an existing product |
| `DELETE` | `/products/{woocommerce_id}` | Delete a product |
| `POST` | `/products/batch` | Batch operations (create, update, delete) |

### Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/categories` | List all categories |

### Synchronization

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/sync` | Sync all products from WooCommerce to local database |

## 💡 Usage Examples

### List All Products (with Pagination & Search)

```bash
# Fetch from local database (default, fast)
GET http://localhost:8000/api/woocommerce/products

# Fetch from live WooCommerce API (real-time data)
GET http://localhost:8000/api/woocommerce/products?source=live

# With pagination (local database)
GET http://localhost:8000/api/woocommerce/products?page=1&per_page=20

# Search by name or SKU (local database)
GET http://localhost:8000/api/woocommerce/products?search=laptop

# Filter by SKU (local database)
GET http://localhost:8000/api/woocommerce/products?sku=PROD-001
```

**Response (Local Source):**
```json
{
  "status": "success",
  "source": "local",
  "data": [
    {
      "id": 1,
      "woocommerce_id": 123,
      "name": "Gaming Laptop",
      "sku": "LAP-001",
      "price": "999.99",
      "sync_status": "synced"
    }
  ],
  "pagination": {
    "total": 50,
    "per_page": 10,
    "current_page": 1,
    "last_page": 5
  }
}
```

### Get Single Product

> **Note**: Use the **WooCommerce product ID**, not the local database ID.

```bash
# Fetch from local database (default, fast)
GET http://localhost:8000/api/woocommerce/products/7917

# Fetch from live WooCommerce API (real-time data)
GET http://localhost:8000/api/woocommerce/products/7917?source=live
```

**Response (Local Source):**
```json
{
  "status": "success",
  "source": "local",
  "product": {
    "id": 2,
    "woocommerce_id": 7917,
    "name": "Muscletech",
    "sku": "MT-001",
    "price": "49.99",
    "sync_status": "synced"
  }
}
```

### Create a Product (Background Queue)

> **Note**: Product creation is now asynchronous. The product is created locally and queued for WooCommerce sync.

```bash
POST http://localhost:8000/api/woocommerce/products
Content-Type: application/json

{
  "name": "Premium T-Shirt",
  "sku": "TSHIRT-001",
  "price": 29.99,
  "description": "High-quality cotton t-shirt",
  "short_description": "Comfortable and stylish",
  "quantity": 100,
  "woocommerce_category_id": [9]
}
```

**Response:**
```json
{
  "status": "success",
  "product_id": 1,
  "sync_status": "pending",
  "message": "Product created locally and queued for WooCommerce sync"
}
```

### Update a Product (Background Queue)

> **Important**: Use the **WooCommerce product ID** in the URL, not the local database ID.

> **Note**: Product updates are asynchronous. Changes are saved locally and queued for WooCommerce sync.

```bash
PUT http://localhost:8000/api/woocommerce/products/7917
Content-Type: application/json

{
  "price": 24.99,
  "quantity": 150
}
```

**Response:**
```json
{
  "status": "success",
  "product_id": 2,
  "woocommerce_id": 7917,
  "sync_status": "pending",
  "message": "Product updated locally and queued for WooCommerce sync"
}
```

### Delete a Product

> **Note**: Use the **WooCommerce product ID**. This will delete from both local database and WooCommerce.

```bash
DELETE http://localhost:8000/api/woocommerce/products/7917
```

**Response:**
```json
{
  "status": "success",
  "message": "Product deleted from local database and WooCommerce."
}
```

### Batch Operations

```bash
POST http://localhost:8000/api/woocommerce/products/batch
Content-Type: application/json

{
  "create": [
    {
      "name": "Product 1",
      "regular_price": "19.99"
    }
  ],
  "update": [
    {
      "id": 123,
      "regular_price": "29.99"
    }
  ],
  "delete": [456]
}
```

### Sync All Products from WooCommerce

Synchronize all products from WooCommerce to your local database:

```bash
POST http://localhost:8000/api/woocommerce/sync
```

**Response:**
```json
{
  "status": "success",
  "message": "Products synchronized successfully",
  "statistics": {
    "total_synced": 150,
    "new_products": 25,
    "updated_products": 125
  }
}
```

## 🛠️ Technology Stack

- **Framework**: Laravel 12.0
- **PHP Version**: 8.2+
- **WooCommerce Client**: automattic/woocommerce ^3.1
- **Authentication**: Laravel Sanctum 4.0
- **Testing**: Pest PHP 3.8
- **Code Quality**: Laravel Pint 1.24

## 🔍 Troubleshooting

### Common Issues

**Issue**: `woocommerce_rest_cannot_create` error

**Solution**: 
- Verify your API keys have **Read/Write** permissions
- Ensure the API keys are correctly added to your `.env` file
- Check that your WooCommerce store URL is correct (include `https://`)

**Issue**: Connection timeout

**Solution**:
- Verify your WooCommerce store is accessible
- Check your firewall settings
- Ensure your store's REST API is enabled

**Issue**: `401 Unauthorized` error

**Solution**:
- Regenerate your WooCommerce API keys
- Double-check the Consumer Key and Consumer Secret in `.env`
- Ensure there are no extra spaces in your credentials

### Testing the Connection

You can test your WooCommerce connection by making a simple GET request:

```bash
curl http://localhost:8000/api/woocommerce/products
```

If configured correctly, you should receive a JSON response with your products.

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

**Need Help?** Feel free to open an issue or contact the development team!



NOTE :: IF YOU ARE USING LAREVEL HERD THEN YOU CAN USE YOUR OWN url INSTEAD OF http://localhost:8000/api/woocommerce/products
