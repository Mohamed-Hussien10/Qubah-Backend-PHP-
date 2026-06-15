<?php

namespace App\Services;

use App\Models\Content;

class ContentService
{
    /**
     * Get a specific content with its topic relationship.
     */
    public function getContent(int $id): Content
    {
        return Content::with('topic.subject')->findOrFail($id);
    }

    /**
     * Create a new content item.
     */
    public function createContent(array $data): Content
    {
        return Content::create($data);
    }

    /**
     * Update an existing content item.
     */
    public function updateContent(int $id, array $data): Content
    {
        $content = Content::findOrFail($id);
        $content->update($data);

        return $content;
    }
}
