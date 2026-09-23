<?php

namespace App\Filament\Support;

use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\TestDefinition;
use App\Models\User;
use App\Services\Content\EditorialWorkflow;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Acțiunile fluxului editorial, comune întrebărilor și testelor.
 *
 * Amândouă trec prin același `EditorialWorkflow`, singurul loc unde se schimbă
 * starea publicării. Butoanele de aici nu scriu niciodată direct în model:
 * altfel regula „se publică doar ce a trecut prin revizuire” ar exista în
 * serviciu, dar ar putea fi ocolită din panou.
 */
final class EditorialActions
{
    public static function submitForReview(): Actions\Action
    {
        return Actions\Action::make('submitForReview')
            ->label('Trimite în revizuire')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('warning')
            ->visible(fn (Question|TestDefinition $record): bool => $record->status === PublicationStatus::Draft)
            ->action(fn (Question|TestDefinition $record) => self::run(
                $record,
                fn (EditorialWorkflow $workflow, Question|TestDefinition $content, User $actor) => $workflow->submitForReview($content, $actor),
                'Trimis în revizuire',
            ));
    }

    public static function publish(): Actions\Action
    {
        return Actions\Action::make('publish')
            ->label('Publică')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('După publicare, conținutul devine vizibil pe site.')
            ->visible(fn (Question|TestDefinition $record): bool => $record->status === PublicationStatus::Review)
            ->action(fn (Question|TestDefinition $record) => self::run(
                $record,
                fn (EditorialWorkflow $workflow, Question|TestDefinition $content, User $actor) => $workflow->publish($content, $actor),
                'Publicat',
            ));
    }

    public static function returnToDraft(): Actions\Action
    {
        return Actions\Action::make('returnToDraft')
            ->label('Înapoi la ciornă')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Conținutul iese de pe site și se întoarce în lucru.')
            ->visible(fn (Question|TestDefinition $record): bool => $record->status !== PublicationStatus::Draft)
            ->action(fn (Question|TestDefinition $record) => self::run(
                $record,
                fn (EditorialWorkflow $workflow, Question|TestDefinition $content, User $actor) => $workflow->returnToDraft($content, $actor),
                'Întors la ciornă',
            ));
    }

    /**
     * Publicarea în bloc a cozii de revizuire.
     *
     * Ce nu e în revizuire e sărit, nu respins: o selecție de tipul „tot ce e
     * pe ecran” nu trebuie să eșueze din cauza unui rând deja publicat.
     * Raportul final spune câte au trecut și câte nu.
     */
    public static function publishBulk(): Actions\BulkAction
    {
        return Actions\BulkAction::make('publishSelected')
            ->label('Publică selectate')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Se publică doar rândurile aflate în revizuire. Restul rămân neatinse.')
            ->deselectRecordsAfterCompletion()
            ->action(fn (Collection $records) => self::publishMany($records));
    }

    /**
     * @param  Collection<int, Model>  $records
     */
    private static function publishMany(Collection $records): void
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            return;
        }

        $workflow = app(EditorialWorkflow::class);
        $published = 0;
        $skipped = 0;

        foreach ($records as $record) {
            if (! $record instanceof Question && ! $record instanceof TestDefinition) {
                $skipped++;

                continue;
            }

            try {
                $workflow->publish($record, $actor);
                $published++;
            } catch (Throwable) {
                $skipped++;
            }
        }

        Notification::make()
            ->title($published.' publicate')
            ->body($skipped === 0 ? 'Toată selecția a trecut.' : $skipped.' rânduri nu erau în revizuire și au fost sărite.')
            ->color($published > 0 ? 'success' : 'warning')
            ->send();
    }

    /**
     * Rulează o tranziție și transformă refuzul serviciului într-un mesaj.
     *
     * @param  callable(EditorialWorkflow, Question|TestDefinition, User): void  $transition
     */
    private static function run(Question|TestDefinition $record, callable $transition, string $title): void
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            return;
        }

        try {
            $transition(app(EditorialWorkflow::class), $record, $actor);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Acțiunea nu a putut fi făcută')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title($title)
            ->success()
            ->send();
    }
}
