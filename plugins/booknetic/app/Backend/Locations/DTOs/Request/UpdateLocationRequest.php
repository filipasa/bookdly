<?php

namespace BookneticApp\Backend\Locations\DTOs\Request;

use BookneticApp\Providers\Core\Attributes\Validation\Required;

class UpdateLocationRequest
{
    #[Required]
    public string $name;

    public string $address = '';

    public string $phone = '';

    public string $note = '';

    public string $latitude = '';

    public string $longitude = '';

    public string $addressComponents = '';

    public int $categoryId = 0;

    public string $translations = '';
}
