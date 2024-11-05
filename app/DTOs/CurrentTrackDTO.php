<?php

namespace App\DTOs;

class CurrentTrackDTO
{
    public function __construct(
        public string $name,
        public string $spotify_id,
        public string $url,
        public int $duration_ms,
        /** @var ArtistDTO[] */
        public array $artists,
        public string $album_name,
        public string $album_spotify_id,
        public string $album_url,
        public string $album_image_url,
        /** @var string[] */
        public array $album_colors,
    ) {}

    public static function fromArray(array $data): self
    {
        $artists = array_map(
            fn(array $artistData) => ArtistDTO::fromArray($artistData),
            $data['artists']
        );

        return new self(
            name: $data['name'],
            spotify_id: $data['spotify_id'],
            url: $data['url'],
            duration_ms: $data['duration_ms'],
            artists: $artists,
            album_name: $data['album_name'],
            album_spotify_id: $data['album_spotify_id'],
            album_url: $data['album_url'],
            album_image_url: $data['album_image_url'],
            // Added 2024-10-05
            album_colors: $data['album_colors'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'spotify_id' => $this->spotify_id,
            'url' => $this->url,
            'duration_ms' => $this->duration_ms,
            'artists' => array_map(
                fn(ArtistDTO $artist) => $artist->toArray(),
                $this->artists
            ),
            'album_name' => $this->album_name,
            'album_spotify_id' => $this->album_spotify_id,
            'album_url' => $this->album_url,
            'album_image_url' => $this->album_image_url,
            'album_colors' => $this->album_colors,
        ];
    }
}
