<?php

namespace PhpSoft\Users\Middleware;

use Closure;
use Validator;

class Validate
{
    public function handle($request, Closure $next, $classValidate)
    {
        $validator = Validator::make($request->all(), $classValidate::rules());

        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        return $next($request);
    }
}
