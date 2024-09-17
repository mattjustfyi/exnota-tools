<?php

namespace App\Helpers;

use App\Enums\NotionObjectType;
use App\Models\RemoteNotionObject;
use Carbon\Carbon;
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

    public static function createRemoteNotionObject($notionResult): RemoteNotionObject
    {
        if ($notionResult instanceof Page) {
            $type = NotionObjectType::PAGE;
        } elseif ($notionResult instanceof Database) {
            $type = NotionObjectType::DATABASE;
        } else {
            throw new \InvalidArgumentException('Unsupported Notion object type');
        }

        return new RemoteNotionObject(
            $notionResult->id,
            self::getTitleFromNotionResult($notionResult),
            $type,
            $notionResult->properties,
            Carbon::parse($notionResult->createdTime),
            Carbon::parse($notionResult->lastEditedTime)
        );
    }
}
