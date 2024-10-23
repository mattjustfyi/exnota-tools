<?php

namespace App\DTOs;

class CurrentTrackDTO
{
    public function __construct(
        public string $name,
        public string $spotify_id,
        public string $url,
        public int $duration_ms,
        public string $artist_name,
        public string $artist_spotify_id,
        public string $artist_url,
        public string $artist_image_url,
        public string $album_name,
        public string $album_spotify_id,
        public string $album_url,
        public string $album_image_url,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            spotify_id: $data['spotify_id'],
            url: $data['url'],
            duration_ms: $data['duration_ms'],
            artist_name: $data['artist_name'],
            artist_spotify_id: $data['artist_spotify_id'],
            artist_url: $data['artist_url'],
            artist_image_url: $data['artist_image_url'],
            album_name: $data['album_name'],
            album_spotify_id: $data['album_spotify_id'],
            album_url: $data['album_url'],
            album_image_url: $data['album_image_url'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'spotify_id' => $this->spotify_id,
            'url' => $this->url,
            'duration_ms' => $this->duration_ms,
            'artist_name' => $this->artist_name,
            'artist_spotify_id' => $this->artist_spotify_id,
            'artist_url' => $this->artist_url,
            'artist_image_url' => $this->artist_image_url,
            'album_name' => $this->album_name,
            'album_spotify_id' => $this->album_spotify_id,
            'album_url' => $this->album_url,
            'album_image_url' => $this->album_image_url,
        ];
    }
}
