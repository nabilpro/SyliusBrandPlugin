<?php

declare(strict_types=1);

namespace Tests\Loevgaard\SyliusBrandPlugin\Controller;

use Loevgaard\SyliusBrandPlugin\Entity\BrandInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class BrandApiTest extends AbstractApiTestCase
{
    /**
     * @test
     */
    public function it_does_not_allow_to_show_brands_list_when_access_is_denied()
    {
        $this->loadDefaultFixtureFiles([
            'resources/brands.yml',
        ]);

        $this->client->request('GET', $this->getBrandUrl());

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'authentication/access_denied_response', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_show_brand_when_it_does_not_exist()
    {
        $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
            'resources/brands.yml',
        ]);

        $this->client->request('GET', $this->getBrandUrl(-1), [], [], static::$authorizedHeaderWithAccept);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'error/not_found_response', Response::HTTP_NOT_FOUND);
    }

    /**
     * @test
     */
    public function it_allows_indexing_brands()
    {
        $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
            'resources/brands.yml',
        ]);

        $this->client->request('GET', $this->getBrandUrl(), [], [], static::$authorizedHeaderWithAccept);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'brand/index_response', Response::HTTP_OK);
    }

    /**
     * @test
     */
    public function it_allows_paginating_and_limiting_the_index_of_brands()
    {
        $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
            'resources/brands.yml',
            'resources/many_brands.yml',
        ]);

        $this->client->request('GET', $this->getBrandUrl(), ['page' => 2, 'limit' => 3], [], static::$authorizedHeaderWithAccept);
        $response = $this->client->getResponse();
        $this->assertResponse($response, 'brand/index_response_paginated');
    }

    /**
     * @test
     */
    public function it_allows_sorting_the_index_of_brands()
    {
        $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
            'resources/brands.yml',
        ]);

        $this->client->request('GET', $this->getBrandUrl(), ['sorting' => ['slug' => 'desc']], [], static::$authorizedHeaderWithAccept);
        $response = $this->client->getResponse();
        $this->assertResponse($response, 'brand/index_response_sorted');
    }

    /**
     * @test
     */
    public function it_allows_filtering_the_index_of_brands()
    {
        $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
            'resources/brands.yml',
        ]);

        $this->client->request('GET', $this->getBrandUrl(), ['criteria' => ['search' => ['type' => 'contains', 'value' => 'sylius']]], [], static::$authorizedHeaderWithAccept);
        $response = $this->client->getResponse();
        $this->assertResponse($response, 'brand/index_response_filtered');
    }

    /**
     * @test
     */
    public function it_denies_showing_brand_for_non_authenticated_user()
    {
        $entities = $this->loadDefaultFixtureFiles([
            'resources/brands.yml',
        ]);

        $this->client->request('GET', $this->getBrandUrl($entities['brand_sylius']));

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'authentication/access_denied_response', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function it_allows_showing_brand()
    {
        $entities = $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
            'resources/brands.yml',

            // We load products, but they shouldn't be at response.
            // As it could be huge. There are separate endpoint for this.
            'resources/products.yml',
        ]);

        $this->client->request('GET', $this->getBrandUrl($entities['brand_setono']), [], [], static::$authorizedHeaderWithAccept);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'brand/show_response', Response::HTTP_OK);
    }

    /**
     * @test
     */
    public function it_denies_brand_deletion_for_non_authenticated_user()
    {
        $entities = $this->loadDefaultFixtureFiles([
            'resources/brands.yml',
        ]);

        $this->client->request('DELETE', $this->getBrandUrl($entities['brand_sylius']));

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'authentication/access_denied_response', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function it_does_not_allow_delete_brand_if_it_does_not_exist()
    {
        $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
        ]);

        $this->client->request('DELETE', $this->getBrandUrl(-1), [], [], static::$authorizedHeaderWithAccept);

        $response = $this->client->getResponse();

        $this->assertResponse($response, 'error/not_found_response', Response::HTTP_NOT_FOUND);
    }

    /**
     * @test
     */
    public function it_allows_delete_brand()
    {
        $entities = $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
            'resources/brands.yml',
        ]);

        $this->client->request('DELETE', $this->getBrandUrl($entities['brand_setono']), [], [], static::$authorizedHeaderWithContentType);

        $response = $this->client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_NO_CONTENT);

        $this->client->request('GET', $this->getBrandUrl($entities['brand_setono']), [], [], static::$authorizedHeaderWithAccept);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'error/not_found_response', Response::HTTP_NOT_FOUND);
    }

    /**
     * @test
     */
    public function it_denies_brand_creation_for_non_authenticated_user()
    {
        $this->loadDefaultFixtureFiles([
            'resources/brands.yml',
        ]);

        $this->client->request('POST', $this->getBrandUrl(), [], [], self::$headerWithContentType);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'authentication/access_denied_response', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_create_brand_without_required_fields()
    {
        $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
        ]);

        $this->client->request('POST', $this->getBrandUrl(), [], [], static::$authorizedHeaderWithContentType);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'brand/create_validation_fail_response', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_create_brand_with_too_long_name_and_slug()
    {
        $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
        ]);

        $longString = str_repeat('s', 192);

        $data =
<<<EOT
        {
            "name": "{$longString}",
            "slug": "{$longString}"
        }
EOT;

        $this->client->request('POST', $this->getBrandUrl(), [], [], static::$authorizedHeaderWithContentType, $data);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'brand/create_with_long_name_and_slug_validation_fail_response', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_create_brand_with_too_short_name()
    {
        $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
        ]);

        $data =
            <<<EOT
        {
            "name": "s",
            "slug": "valid-slug"
        }
EOT;

        $this->client->request('POST', $this->getBrandUrl(), [], [], static::$authorizedHeaderWithContentType, $data);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'brand/create_with_short_name_validation_fail_response', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     */
    public function it_allows_create_brand()
    {
        $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
        ]);

        $data =
            <<<EOT
        {
            "name": "Brand name",
            "slug": "brand-slug"
        }
EOT;

        $this->client->request('POST', $this->getBrandUrl(), [], [], static::$authorizedHeaderWithContentType, $data);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'brand/create_response', Response::HTTP_CREATED);
    }

    /**
     * @test
     */
    public function it_allows_creating_brand_with_images()
    {
        $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
        ]);

        $data =
<<<EOT
        {
            "name": "PHP",
            "slug": "php",
            "images": [
                {
                    "type": "logo"
                },
                {
                    "type": "logo"
                }
            ]
        }
EOT;

        $this->client->request(
            'POST',
            $this->getBrandUrl(),
            [],
            ['images' => [
                ['file' => new UploadedFile(sprintf('%s/../Resources/fixtures/php-logo.png', __DIR__), 'php-logo')],
                ['file' => new UploadedFile(sprintf('%s/../Resources/fixtures/php-logo-transparent-background.png', __DIR__), 'php-logo-transparent-background')],
            ]],
            static::$authorizedHeaderWithContentType,
            $data
        );

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'brand/create_with_images_response', Response::HTTP_CREATED);
    }

    /**
     * @test
     */
    public function it_denies_partial_updating_brand_for_non_authenticated_user()
    {
        $entities = $this->loadDefaultFixtureFiles([
            'resources/brands.yml',
        ]);

        $this->client->request('PATCH', $this->getBrandUrl($entities['brand_sylius']), [], [], self::$headerWithContentType);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'authentication/access_denied_response', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function it_allows_updating_partial_information_about_brand()
    {
        $entities = $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
            'resources/brands.yml',
        ]);

        $data =
<<<EOT
        {
            "name": "Updated name"
        }
EOT;
        $this->client->request('PATCH', $this->getBrandUrl($entities['brand_symfony']), [], [], static::$authorizedHeaderWithContentType, $data);
        $response = $this->client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_NO_CONTENT);

        $this->client->request('GET', $this->getBrandUrl($entities['brand_symfony']), [], [], static::$authorizedHeaderWithAccept);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'brand/show_after_partial_update_response');
    }


    /**
     * @test
     */
    public function it_denies_updating_brand_for_non_authenticated_user()
    {
        $entities = $this->loadDefaultFixtureFiles([
            'resources/brands.yml',
        ]);

        $this->client->request('PUT', $this->getBrandUrl($entities['brand_sylius']), [], [], self::$headerWithContentType);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'authentication/access_denied_response', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function it_allows_updating_brand()
    {
        $entities = $this->loadDefaultFixtureFiles([
            'authentication/api_administrator.yml',
            'resources/brands.yml',
        ]);

        $data =
<<<EOT
        {
              "name": "Updated name",
              "slug": "updated-slug"
        }
EOT;
        $this->client->request('PUT', $this->getBrandUrl($entities['brand_symfony']), [], [], static::$authorizedHeaderWithContentType, $data);
        $response = $this->client->getResponse();

        $this->assertResponseCode($response, Response::HTTP_NO_CONTENT);

        $this->client->request('GET', $this->getBrandUrl('updated-slug'), [], [], static::$authorizedHeaderWithAccept);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'brand/show_after_update_response');
    }

    /**
     * @param BrandInterface|string $brand
     * @return string
     */
    private function getBrandUrl($brand = '')
    {
        return sprintf('/api/v1/brands/%s',
            $brand instanceof BrandInterface ? $brand->getSlug() : $brand
        );
    }
}
