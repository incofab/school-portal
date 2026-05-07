<?php

namespace App\Models;

use App\Enums\InstitutionStatus;
use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionGroup extends Model
{
    use HasFactory, HasMedia;

    public $guarded = [];

    public $casts = [
        'partner_user_id' => 'integer',
        'user_id' => 'integer',
        'credit_wallet' => 'float',
        'debt_wallet' => 'float',
        'loan_limit' => 'float',
        'status' => InstitutionStatus::class,
    ];

    public static function getQueryForManager(User $user)
    {
        return $user->isAdmin()
          ? InstitutionGroup::query()
          : $user->partnerInstitutionGroups();
    }

    public function isOwing(): bool
    {
        return $this->debt_wallet > 0;
    }

    public function institutions()
    {
        return $this->hasMany(Institution::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_user_id');
    }

    public function fundings()
    {
        return $this->hasMany(Funding::class)->latest();
    }

    public function priceLists()
    {
        return $this->hasMany(PriceList::class);
    }

    public function canGetLoan($amount): bool
    {
        $newDebtBalance = $this->debt_wallet + $amount;

        return $newDebtBalance <= $this->loan_limit;
    }

    public function schemeOfWorks()
    {
        return $this->hasMany(SchemeOfWork::class);
    }

    public function bankAccounts()
    {
        return $this->morphMany(BankAccount::class, 'accountable');
    }

    public function withdrawals()
    {
        return $this->morphMany(Withdrawal::class, 'withdrawable');
    }

    public function latestResultPublication()
    {
        return $this->hasOne(ResultPublication::class)
            ->with('academicSession')
            ->latestOfMany();
    }
}
