<?php

namespace App\Services;

use App\Models\ProductImport;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class ShopifyRestService
{
    protected Client $client;

    protected string $apiVersion;

    public function __construct()
    {
        $storeDomain = config('services.shopify.store_domain');
        $this->apiVersion = config('services.shopify.api_version');

        $this->client = new Client([
            'base_uri' => "https://{$storeDomain}/admin/api/{$this->apiVersion}/",
            'headers' => [
                'X-Shopify-Access-Token' => config('services.shopify.access_token'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    public function findProductByHandle(string $handle): ?array
    {
        try {
            $response = $this->client->get('products.json', [
                'query' => ['handle' => $handle, 'limit' => 1],
            ]);

            $data = json_decode((string) $response->getBody(), true);

            return $data['products'][0] ?? null;
        } catch (GuzzleException $e) {
            Log::channel('shopify_import')->warning('Shopify lookup by handle failed: '.$e->getMessage());

            return null;
        }
    }

    public function createOrUpdateProduct(ProductImport $row): array
    {
        $payload = $this->mapRowToShopifyPayload($row);
        $existing = $this->findProductByHandle($row->handle ?: str($row->title)->slug());

        try {
            if ($existing) {
                $response = $this->client->put("products/{$existing['id']}.json", [
                    'json' => ['product' => $payload],
                ]);
                $action = 'updated';
            } else {
                $response = $this->client->post('products.json', [
                    'json' => ['product' => $payload],
                ]);
                $action = 'created';
            }

            $body = json_decode((string) $response->getBody(), true);

            if (empty($body['product']['id'])) {
                throw new Exception('Shopify response did not contain a product id.');
            }

            return [
                'shopify_product_id' => (string) $body['product']['id'],
                'action' => $action,
            ];
        } catch (GuzzleException $e) {
            $errorBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();
            throw new Exception("Shopify REST API error: {$errorBody}");
        }
    }

    protected function mapRowToShopifyPayload(ProductImport $row): array
    {
        $variant = array_filter([
            'sku' => $row->sku,
            'price' => $row->price !== null ? (string) $row->price : null,
            'compare_at_price' => $row->compare_at_price !== null ? (string) $row->compare_at_price : null,
            'inventory_quantity' => $row->inventory_qty,
            'inventory_management' => 'shopify',
            'weight' => $row->weight !== null ? (float) $row->weight : null,
            'weight_unit' => $row->weight_unit ?: 'kg',
        ], fn ($v) => $v !== null);

        $product = [
            'title' => $row->title,
            'body_html' => $row->body_html,
            'vendor' => $row->vendor,
            'product_type' => $row->product_type,
            'tags' => $row->tags,
            'handle' => $row->handle ?: null,
            'status' => $row->published ? 'active' : 'draft',
            'variants' => [$variant],
        ];

        if ($row->image_src) {
            $product['images'] = [[
                'src' => $row->image_src,
                'alt' => $row->image_alt_text,
            ]];
        }

        return array_filter($product, fn ($v) => $v !== null);
    }
}
