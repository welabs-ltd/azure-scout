- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)

### Installation

Require the package in your Laravel project (if not in monorepo, use local path):

    composer require welabs/azure-scout

### Configuration
1. Set the credentials to `.env`
```php
AZURE_SEARCH_ENDPOINT= https://{your-azure-search-endpoint}.search.windows.net
AZURE_SEARCH_API_KEY='your-azure-search-api-key'
```
2. In `config/scout.php`, set:

```php
'azure' => [
        'endpoint' => env('AZURE_SEARCH_ENDPOINT', 'https://{your-azure-search-endpoint}.search.windows.net'),
        'api_key' => env('AZURE_SEARCH_API_KEY', 'your-azure-search-api-key'),
        'index-settings' => [
            \Enzaime\Pharmacy\Inventory\Models\Product::class => [
                "name" => "products",
                "fields" => [
                  [ "name" => "id", "type" => "Edm.String", "key" => true, "filterable" => true ],
                  [ "name" => "name", "type" => "Edm.String", "searchable" => true ],
                  [ "name" => "code", "type" => "Edm.String", "searchable" => true, "filterable" => true ],
                  [ "name" => "strength", "type" => "Edm.String", "searchable" => true ],
                  [ "name" => "dosage", "type" => "Edm.String", "searchable" => true ],
                  [ "name" => "pack_size", "type" => "Edm.String", "searchable" => true ],
                  [ "name" => "pack_qty", "type" => "Edm.String", "searchable" => true ],
                  [ "name" => "category", "type" => "Edm.String", "searchable" => true, "filterable" => true ],
                  [ "name" => "sales_price", "type" => "Edm.Double", "filterable" => true, "sortable" => true ],
                  [ "name" => "name_bn", "type" => "Edm.String", "searchable" => true ],
                  [ "name" => "company_id", "type" => "Edm.String", "filterable" => true ],
                  [ "name" => "generic_name", "type" => "Edm.String", "searchable" => true ],
                  [ "name" => "is_active", "type" => "Edm.Boolean", "filterable" => true ],
                ]
            ],
        ],
    ],
```
### Usage
Use Azure Laravel Scout as usual on your models.

```shell
php artisan scout:import "Enzaime\Pharmacy\Inventory\Models\Product"
php artisan scout:index "Enzaime\Pharmacy\Inventory\Models\Product" 
php artisan scout:flush "Enzaime\Pharmacy\Inventory\Models\Product"
```


---
### Development References

1. https://learn.microsoft.com/en-us/rest/api/searchservice/search-documents
1. https://learn.microsoft.com/en-us/azure/search/search-query-odata-filter
1. https://github.com/Azure-Samples/azure-search-rest-samples/tree/main
1. https://learn.microsoft.com/en-us/rest/api/searchservice/addupdate-or-delete-documents
1. https://learn.microsoft.com/en-us/rest/api/searchservice/create-index
1. https://learn.microsoft.com/en-us/rest/api/searchservice/create-data-source
