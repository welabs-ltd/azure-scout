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
            \App\Models\Hotels::class => [
                "name" => "hotels-quickstart",  
                "fields" => [
                    ["name" => "HotelId", "type" => "Edm.String", "key" => true, "filterable" => true],
                    ["name" => "HotelName", "type" => "Edm.String", "searchable" => true, "filterable" => false, "sortable" => true, "facetable" => false],
                    ["name" => "Description", "type" => "Edm.String", "searchable" => true, "filterable" => false, "sortable" => false, "facetable" => false, "analyzer" => "en.lucene"],
                    ["name" => "Category", "type" => "Edm.String", "searchable" => true, "filterable" => true, "sortable" => true, "facetable" => true],
                    ["name" => "Tags", "type" => "Collection(Edm.String)", "searchable" => true, "filterable" => true, "sortable" => false, "facetable" => true],
                    ["name" => "ParkingIncluded", "type" => "Edm.Boolean", "filterable" => true, "sortable" => true, "facetable" => true],
                    ["name" => "LastRenovationDate", "type" => "Edm.DateTimeOffset", "filterable" => true, "sortable" => true, "facetable" => true],
                    ["name" => "Rating", "type" => "Edm.Double", "filterable" => true, "sortable" => true, "facetable" => true],
                    ["name" => "Address", "type" => "Edm.ComplexType", 
                        "fields" => [
                            ["name" => "StreetAddress", "type" => "Edm.String", "filterable" => false, "sortable" => false, "facetable" => false, "searchable" => true],
                            ["name" => "City", "type" => "Edm.String", "searchable" => true, "filterable" => true, "sortable" => true, "facetable" => true],
                            ["name" => "StateProvince", "type" => "Edm.String", "searchable" => true, "filterable" => true, "sortable" => true, "facetable" => true],
                            ["name" => "PostalCode", "type" => "Edm.String", "searchable" => true, "filterable" => true, "sortable" => true, "facetable" => true],
                            ["name" => "Country", "type" => "Edm.String", "searchable" => true, "filterable" => true, "sortable" => true, "facetable" => true]
                        ]
                    ]
                ]
            ],
        ],
    ],
```
**References:**
- [EDM data types for non-vector fields](https://learn.microsoft.com/en-us/rest/api/searchservice/supported-data-types#edm-data-types-for-nonvector-fields)
- [Examples of Sample type and data](https://github.com/Azure-Samples/azure-search-rest-samples/blob/main/Quickstart/az-search-quickstart.rest)

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
