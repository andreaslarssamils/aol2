<?php

use App\Enums\ClaimStatus;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\User;
use App\Notifications\BusinessClaimDecided;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('links an approved applicant into the portal', function () {
    $business = Business::factory()->create(['namn' => 'Café Lyktan']);
    $applicant = User::factory()->create(['name' => 'Anna']);
    $claim = BusinessClaim::factory()->for($business)->for($applicant)->create(['status' => ClaimStatus::Approved]);

    $mail = (new BusinessClaimDecided($claim))->toMail($applicant);

    expect($mail->subject)->toBe('Du har nu tillgång till Café Lyktan')
        ->and($mail->actionUrl)->toBe("http://localhost:8000/portal/{$business->id}")
        ->and($mail->render()->toHtml())->toContain('Hej Anna!');
});

it('explains a rejection without a link', function () {
    $business = Business::factory()->create(['namn' => 'Café Lyktan']);
    $claim = BusinessClaim::factory()->for($business)->create(['status' => ClaimStatus::Rejected]);

    $mail = (new BusinessClaimDecided($claim))->toMail($claim->user);

    expect($mail->subject)->toBe('Ditt anspråk på Café Lyktan avslogs')
        ->and($mail->actionUrl)->toBeNull();
});

it('escapes the business name in the mail', function () {
    $business = Business::factory()->create(['namn' => "O'Reilly <script>alert('xss')</script>"]);
    $claim = BusinessClaim::factory()->for($business)->create(['status' => ClaimStatus::Approved]);

    $html = (new BusinessClaimDecided($claim))->toMail($claim->user)->render()->toHtml();

    expect($html)->not->toContain("<script>alert('xss')</script>")
        ->and($html)->toContain('&lt;script&gt;');
});
