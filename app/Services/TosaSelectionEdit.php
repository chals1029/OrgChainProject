<?php

namespace App\Services;

use App\Models\OfficeUser;
use App\Models\TosaShortlist;
use Illuminate\Http\Request;

final class TosaSelectionEdit
{
    public function isEditing(Request $request, OfficeUser $office, TosaShortlist $shortlist): bool
    {
        return $office->is_active && $office->office_role === 'oso' && $shortlist->published_at === null;
    }
}
