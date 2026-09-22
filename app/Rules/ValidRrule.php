<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Sabre\VObject\Recur\RRuleIterator;
use Throwable;

class ValidRrule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        try {
            // RRuleIterator parses the pattern eagerly in its constructor,
            // so constructing it is enough to validate the syntax.
            new RRuleIterator($value, new \DateTimeImmutable('1970-01-05'));
        } catch (Throwable) {
            $fail('The :attribute is not a valid recurrence rule.');
        }
    }
}
