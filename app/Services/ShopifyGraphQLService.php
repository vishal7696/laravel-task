<?php

namespace App\Services;

use App\Models\ProductImport;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ShopifyGraphQLService
{
    protected Client $client;

    public function __construct()
    {
        $storeDomain = config('services.shopify.store_domain');
        $apiVersion = config('services.shopify.api_version');

        $this->client = new Client([
            'base_uri' => "https://{$storeDomain}/admin/api/{$apiVersion}/",
            'headers' => [
                'X-Shopify-Access-Token' => config('services.shopify.access_token'),
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    protected function graphql(string $query, array $variables = []): array
    {
        try {
            $response = $this->client->post('graphql.json', [
                'json' => ['query' => $query, 'variables' => $variables],
            ]);
        } catch (GuzzleException $e) {
            throw new Exception('Shopify GraphQL transport error: '.$e->getMessage());
        }

        $body = json_decode((string) $response->getBody(), true);

        if (! empty($body['errors'])) {
            throw new Exception('Shopify GraphQL error: '.json_encode($body['errors']));
        }

        return $body['data'] ?? [];
    }

    public function findProductByHandle(string $handle): ?array
    {
        $query = <<<'GQL'
        query FindProductByHandle($query: String!) {
          products(first: 1, query: $query) {
            edges { node { id title handle } }
          }
        }
        GQL;

        $data = $this->graphql($query, ['query' => "handle:{$handle}"]);

        return $data['products']['edges'][0]['node'] ?? null;
    }

    /**
     * @return array{shopify_product_id: string, action: string}
     */
    public function createOrUpdateProduct(ProductImport $row): array
    {
        $handle = $row->handle ?: str($row->title)->slug();
        $existing = $this->findProductByHandle($handle);

        $input = array_filter([
            'title' => $row->title,
            'descriptionHtml' => $row->body_html,
            'vendor' => $row->vendor,
            'productType' => $row->product_type,
            'tags' => $row->tags ? explode(',', $row->tags) : null,
            'handle' => $handle,
            'status' => $row->published ? 'ACTIVE' : 'DRAFT',
        ], fn ($v) => $v !== null);

        if ($existing) {
            $input['id'] = $existing['id'];

            $mutation = <<<'GQL'
            mutation UpdateProduct($input: ProductInput!) {
              productUpdate(input: $input) {
                product { id }
                userErrors { field message }
              }
            }
            GQL;

            $data = $this->graphql($mutation, ['input' => $input]);
            $result = $data['productUpdate'];
            $action = 'updated';
        } else {
            $mutation = <<<'GQL'
            mutation CreateProduct($input: ProductInput!) {
              productCreate(input: $input) {
                product { id }
                userErrors { field message }
              }
            }
            GQL;

            $data = $this->graphql($mutation, ['input' => $input]);
            $result = $data['productCreate'];
            $action = 'created';
        }

        if (! empty($result['userErrors'])) {
            throw new Exception('Shopify GraphQL userErrors: '.json_encode($result['userErrors']));
        }

        if (empty($result['product']['id'])) {
            throw new Exception('Shopify GraphQL response did not contain a product id.');
        }

        $this->updateVariantAndImage($result['product']['id'], $row);

        return [
            'shopify_product_id' => $result['product']['id'],
            'action' => $action,
        ];
    }


    protected function updateVariantAndImage(string $productGid, ProductImport $row): void
    {
        if ($row->price !== null) {
            $variantId = $this->fetchDefaultVariantId($productGid);

            if ($variantId) {
                $variantInput = array_filter([
                    'id' => $variantId,
                    'price' => (string) $row->price,
                    'compareAtPrice' => $row->compare_at_price !== null ? (string) $row->compare_at_price : null,
                    'inventoryItem' => array_filter([
                        'sku' => $row->sku,
                        'measurement' => $row->weight !== null ? [
                            'weight' => [
                                'value' => (float) $row->weight,
                                'unit' => $this->mapWeightUnit($row->weight_unit),
                            ],
                        ] : null,
                    ], fn ($v) => $v !== null),
                ], fn ($v) => $v !== null && $v !== []);

                $mutation = <<<'GQL'
                mutation SetVariantDetails($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
                  productVariantsBulkUpdate(productId: $productId, variants: $variants) {
                    productVariants { id price }
                    userErrors { field message }
                  }
                }
                GQL;

                $data = $this->graphql($mutation, [
                    'productId' => $productGid,
                    'variants' => [$variantInput],
                ]);

                if (! empty($data['productVariantsBulkUpdate']['userErrors'])) {
                    throw new Exception('Shopify GraphQL variant update userErrors: '.json_encode($data['productVariantsBulkUpdate']['userErrors']));
                }
            }
        }

        if ($row->image_src) {
            try {
                $mutation = <<<'GQL'
                mutation AddProductImage($productId: ID!, $media: [CreateMediaInput!]!) {
                  productCreateMedia(productId: $productId, media: $media) {
                    mediaUserErrors { field message }
                  }
                }
                GQL;

                $data = $this->graphql($mutation, [
                    'productId' => $productGid,
                    'media' => [[
                        'originalSource' => $row->image_src,
                        'alt' => $row->image_alt_text,
                        'mediaContentType' => 'IMAGE',
                    ]],
                ]);

                if (! empty($data['productCreateMedia']['mediaUserErrors'])) {
                    logger()->channel('shopify_import')->warning(
                        'Shopify GraphQL image upload userErrors: '.json_encode($data['productCreateMedia']['mediaUserErrors'])
                    );
                }
            } catch (Exception $e) {
                logger()->channel('shopify_import')->warning('Shopify GraphQL image upload failed: '.$e->getMessage());
            }
        }
    }

    protected function fetchDefaultVariantId(string $productGid): ?string
    {
        $query = <<<'GQL'
        query GetDefaultVariant($id: ID!) {
          product(id: $id) {
            variants(first: 1) {
              edges { node { id } }
            }
          }
        }
        GQL;

        $data = $this->graphql($query, ['id' => $productGid]);

        return $data['product']['variants']['edges'][0]['node']['id'] ?? null;
    }

    protected function mapWeightUnit(?string $unit): string
    {
        return match (strtolower((string) $unit)) {
            'g', 'gram', 'grams' => 'GRAMS',
            'lb', 'lbs', 'pound', 'pounds' => 'POUNDS',
            'oz', 'ounce', 'ounces' => 'OUNCES',
            default => 'KILOGRAMS',
        };
    }
}
