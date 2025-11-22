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

### 5. Start the Development Server

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

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/products` | List all products |
| `GET` | `/products/{id}` | Get a single product by ID |
| `POST` | `/products` | Create a new product |
| `PUT` | `/products/{id}` | Update an existing product |
| `DELETE` | `/products/{id}` | Delete a product |
| `POST` | `/products/batch` | Batch operations (create, update, delete) |

### Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/categories` | List all categories |

## 💡 Usage Examples

### List All Products

```bash
GET http://localhost:8000/api/woocommerce/products
```

### Get Single Product

```bash
GET http://localhost:8000/api/woocommerce/products/123
```

### Create a Product

```bash
POST http://localhost:8000/api/woocommerce/products
Content-Type: application/json

{
  "name": "Premium T-Shirt",
  "type": "simple",
  "regular_price": "29.99",
  "description": "High-quality cotton t-shirt",
  "short_description": "Comfortable and stylish",
  "categories": [
    {
      "id": 9
    }
  ]
}
```

### Update a Product

```bash
PUT http://localhost:8000/api/woocommerce/products/123
Content-Type: application/json

{
  "regular_price": "24.99",
  "sale_price": "19.99"
}
```

### Delete a Product

```bash
DELETE http://localhost:8000/api/woocommerce/products/123
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
