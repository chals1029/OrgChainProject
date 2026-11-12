<?php
require '/srv/orgchain/current/vendor/autoload.php';
$app = require '/srv/orgchain/current/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$soUsers = App\Models\OfficeUser::where('office_role', 'so')->with('studentOrganization')->get();
$updated = 0;
foreach ($soUsers as $u) {
    $org = $u->studentOrganization;
    if ($org && filled($org->short_name)) {
        $acronym = trim($org->short_name);
        if ($u->name !== $acronym) {
            echo "Updating User #{$u->id} ({$u->email}): '{$u->name}' -> '{$acronym}'\n";
            $u->name = $acronym;
            $u->save();
            $updated++;
        }
    }
}
echo "Total updated: {$updated}\n";
