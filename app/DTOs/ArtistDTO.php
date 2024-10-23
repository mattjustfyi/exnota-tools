<?php

namespace App\DTOs;

readonly class ArtistDTO
{
    public function __construct(
        public string $name,
        public string $spotify_id,
        public string $url,
        public string $image_url,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            spotify_id: $data['spotify_id'],
            url: $data['url'],
            image_url: $data['image_url'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'spotify_id' => $this->spotify_id,
            'url' => $this->url,
            'image_url' => $this->image_url,
        ];
    }
}
