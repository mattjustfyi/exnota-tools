<?php

namespace App\Helpers;

use Notion\Common\RichText;
use Notion\Databases\Database;
use Notion\Pages\Page;

class NotionHelper
{
    public static function getTitleFromNotionResult($notionResult): string
    {
        if ($notionResult instanceof Page) {
            return $notionResult->title()->toString();
        } elseif ($notionResult instanceof Database) {
            return collect($notionResult->title)
                ->map(fn(RichText $richText) => $richText->toString())
                ->join('');
        }

        return '';
    }
}
