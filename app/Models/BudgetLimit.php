<?php
/**
 * BudgetLimit.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class BudgetLimit.
 *
 * @property Budget $budget
 * @property int    $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $amount
 * @property int    $budget_id
 * @property string spent
 */
class BudgetLimit extends Model
{
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
                        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    protected $fillable = ['budget_id', 'start_date', 'end_date', 'amount'];

    /**
     * @param string $value
     *
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public static function routeBinder(string $value): BudgetLimit
    {
        if (auth()->check()) {
            $budgetLimitId = (int)$value;
            $budgetLimit   = self::where('budget_limits.id', $budgetLimitId)
                                 ->leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                                 ->where('budgets.user_id', auth()->user()->id)
                                 ->first(['budget_limits.*']);
            if (null !== $budgetLimit) {
                return $budgetLimit;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
