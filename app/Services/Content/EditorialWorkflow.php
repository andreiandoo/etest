<?php

namespace App\Services\Content;

use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\TestDefinition;
use App\Models\User;
use DomainException;

final class EditorialWorkflow
{
    public function submitForReview(Question|TestDefinition $content, User $actor): void
    {
        if (! in_array($content->status, [PublicationStatus::Draft, PublicationStatus::Review], true)) {
            throw new DomainException('Only draft or review content can be submitted for review.');
        }

        $content->forceFill([
            'status' => PublicationStatus::Review,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'published_by' => null,
        ]);

        if ($content instanceof TestDefinition) {
            $content->published_at = null;
        }

        if ($content instanceof Question) {
            $content->updated_by = $actor->id;
        }

        $content->save();
    }

    public function publish(Question|TestDefinition $content, User $actor): void
    {
        if ($content->status !== PublicationStatus::Review) {
            throw new DomainException('Content must be in review before it can be published.');
        }

        $content->forceFill([
            'status' => PublicationStatus::Published,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'published_by' => $actor->id,
        ]);

        if ($content instanceof TestDefinition) {
            $content->published_at = now();
        }

        if ($content instanceof Question) {
            $content->updated_by = $actor->id;
        }

        $content->save();
    }

    public function returnToDraft(Question|TestDefinition $content, User $actor): void
    {
        $content->forceFill([
            'status' => PublicationStatus::Draft,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'published_by' => null,
        ]);

        if ($content instanceof TestDefinition) {
            $content->published_at = null;
        }

        if ($content instanceof Question) {
            $content->updated_by = $actor->id;
        }

        $content->save();
    }
}
