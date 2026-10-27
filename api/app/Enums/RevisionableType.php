<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Innehållstyper som går genom godkännandeflödet. Värdet är morph-aliaset i
 * revisions.revisionable_type (se Relation::enforceMorphMap i AppServiceProvider).
 */
enum RevisionableType: string implements HasLabel
{
    case Business = 'business';
    case BusinessLocation = 'business_location';
    case Event = 'event';
    case Article = 'article';
    case LunchMenu = 'lunch_menu';
    case Offer = 'offer';
    case Page = 'page';

    public function getLabel(): string
    {
        return match ($this) {
            self::Business => 'Verksamhet',
            self::BusinessLocation => 'Lokalisering',
            self::Event => 'Evenemang',
            self::Article => 'Artikel',
            self::LunchMenu => 'Lunchmeny',
            self::Offer => 'Erbjudande',
            self::Page => 'Sida',
        };
    }
}
