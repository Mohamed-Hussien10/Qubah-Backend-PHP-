<?php

namespace App\Services;

use App\Models\Topic;

class TopicService
{
    /**
     * Get a single topic with its contents eagerly loaded.
     */
    public function getTopicWithContents(int $id): Topic
    {
        return Topic::with('contents')->findOrFail($id);
    }

    /**
     * Create a new topic.
     */
    public function createTopic(array $data): Topic
    {
        return Topic::create($data);
    }

    /**
     * Update an existing topic.
     */
    public function updateTopic(int $id, array $data): Topic
    {
        $topic = Topic::findOrFail($id);
        $topic->update($data);

        return $topic;
    }
}
